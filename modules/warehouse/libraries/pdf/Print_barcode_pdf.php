<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once APPPATH . 'libraries/pdf/App_pdf.php';

/**
 *  Print_barcode pdf
 */
class Print_barcode_pdf extends App_pdf {
	protected $print_barcode;

	/**
	 * construct
	 * @param object
	 */
	public function __construct($print_barcode) {

		$print_barcode = hooks()->apply_filters('request_html_pdf_data', $print_barcode);
		$GLOBALS['print_barcode_pdf'] = $print_barcode;

		parent::__construct();

		// CRITICAL: Ensure TCPDF's re_space is initialized BEFORE any HTML processing
		// This prevents null regex patterns that cause preg_replace to fail at line 16560
		// Always explicitly initialize it, even if parent constructor should have done it
		$this->setSpacesRE('/[^\S\xa0]/');
		
		// Double-check re_space is properly set and never null/empty
		if (!isset($this->re_space) || !is_array($this->re_space)) {
			$this->re_space = array('p' => '[\s]', 'm' => '');
		}
		if (empty($this->re_space['p']) || $this->re_space['p'] === null) {
			$this->re_space['p'] = '[\s]';
		}
		if (!isset($this->re_space['m']) || $this->re_space['m'] === null) {
			$this->re_space['m'] = '';
		}
		
		log_message('debug', 're_space initialized: p=' . $this->re_space['p'] . ', m=' . $this->re_space['m']);

		// Ensure print_barcode is never null or empty
		if (empty($print_barcode) || $print_barcode === null) {
			log_message('error', 'print_barcode is null or empty in Print_barcode_pdf constructor');
			$print_barcode = ''; // Set to empty string instead of null
		}
		
		$this->print_barcode = $print_barcode;

		$this->SetTitle('print_barcode');

		// Optimize TCPDF settings for large batches (1000+ items)
		$this->setImageScale(1.0); // Reduce image scale to save memory
		$this->setCompression(true); // Enable compression
		
		// Configure page break settings to prevent splitting table rows
		// Set a smaller page break trigger to ensure rows fit on page
		$this->SetAutoPageBreak(true, 10); // 10mm bottom margin
		
		// Enable table row keep-together feature
		// This helps prevent rows from being split across pages
		$this->setHtmlVSpace(array('table' => array(0 => array('h' => 0, 'n' => 0), 1 => array('h' => 0, 'n' => 0))));
		
		# Don't remove these lines - important for the PDF layout
		// Only call fix_editor_html if content is not empty
		if (!empty($this->print_barcode) && $this->print_barcode !== null) {
			$html_before_fix = strlen($this->print_barcode);
			log_message('debug', 'Before fix_editor_html: HTML size = ' . $html_before_fix . ' bytes');
			$this->print_barcode = $this->fix_editor_html($this->print_barcode);
			$html_after_fix = strlen($this->print_barcode);
			log_message('debug', 'After fix_editor_html: HTML size = ' . $html_after_fix . ' bytes');
			
			// Check if HTML was corrupted
			if ($html_after_fix == 0 || ($html_before_fix > 1000 && $html_after_fix < 100)) {
				log_message('error', 'fix_editor_html may have corrupted HTML. Before: ' . $html_before_fix . ', After: ' . $html_after_fix);
				log_message('error', 'HTML after fix (first 500 chars): ' . substr($this->print_barcode, 0, 500));
			}
			
			// Check if table tag exists
			if (strpos($this->print_barcode, '<table') === false) {
				log_message('error', 'WARNING: Table tag not found in HTML after fix_editor_html!');
			}
		} else {
			log_message('error', 'Skipping fix_editor_html because print_barcode is empty or null');
			$this->print_barcode = '';
		}
	}
	
	/**
	 * Override fix_editor_html to handle null/empty content safely and prevent null in preg_replace
	 * @param string $content
	 * @return string
	 */
	public function fix_editor_html($content) {
		// Ensure content is never null
		if ($content === null) {
			log_message('error', 'fix_editor_html received null content');
			return '';
		}
		
		// Convert to string if not already
		if (!is_string($content)) {
			$content = (string)$content;
		}
		
		// If empty after conversion, return empty string
		if (empty($content)) {
			return '';
		}

		// Safely perform all preg_replace operations with null checks
		// Add <br /> tag and wrap over div element every image to prevent overlaping over text
		$content = preg_replace('/(<img[^>]+>(?:<\/img>)?)/i', '<div>$1</div>', $content);
		if ($content === null) {
			log_message('error', 'preg_replace returned null after img wrap');
			return '';
		}
		
		// Fix BLOG images from TinyMCE Mobile Upload, could help with desktop too
		$content = preg_replace('/data:image\/jpeg;base64/m', '@', $content);
		if ($content === null) {
			log_message('error', 'preg_replace returned null after base64 fix');
			return '';
		}

		// Replace <img src="" width="100%" height="auto">
		$content = preg_replace('/width="(([0-9]*%)|auto)"|height="(([0-9]*%)|auto)"/mi', '', $content);
		if ($content === null) {
			log_message('error', 'preg_replace returned null after width/height removal');
			return '';
		}

		// Add cellpadding to all tables inside the html
		$content = preg_replace('/(<table\b[^><]*)>/i', '$1 cellpadding="4">', $content);
		if ($content === null) {
			log_message('error', 'preg_replace returned null after table cellpadding');
			return '';
		}

		// Remove white spaces cased by the html editor ex. <td>  item</td>
		$content = preg_replace('/[\t\n\r\0\x0B]/', '', $content);
		if ($content === null) {
			log_message('error', 'preg_replace returned null after whitespace removal');
			return '';
		}
		
		$content = preg_replace('/([\s])\1+/', ' ', $content);
		if ($content === null) {
			log_message('error', 'preg_replace returned null after space normalization');
			return '';
		}

		// Tcpdf does not support float css we need to adjust this here
		$content = str_replace('float: right', 'text-align: right', $content);
		$content = str_replace('float: left', 'text-align: left', $content);
		$content = str_replace('float:right', 'text-align: right', $content);
		$content = str_replace('float:left', 'text-align: left', $content);

		// Image center
		$content = str_replace('margin-left: auto; margin-right: auto;', 'text-align:center;', $content);

		// Remove any inline definitions for font family
		$content = preg_replace('/font-family.+?;/m', '', $content);
		if ($content === null) {
			log_message('error', 'preg_replace returned null after font-family removal');
			return '';
		}
		
		// Final safety check
		if (!is_string($content)) {
			$content = (string)$content;
		}
		
		return $content;
	}

	/**
	 * Override writeHTML to ensure HTML is never null before TCPDF processes it
	 * @param string $html
	 * @param bool $ln
	 * @param bool $fill
	 * @param bool $reseth
	 * @param bool $cell
	 * @param string $align
	 * @return mixed
	 */
	public function writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='') {
		// Ensure HTML is never null before passing to parent
		if ($html === null) {
			log_message('error', 'writeHTML received null HTML, converting to empty string');
			$html = '';
		}
		
		// Convert to string if not already
		if (!is_string($html)) {
			$html = (string)$html;
		}
		
		// Ensure it's not empty (at least have a div)
		if (empty($html)) {
			$html = '<div></div>';
		}
		
		// Clean HTML to prevent TCPDF regex issues
		// Remove any null bytes or invalid characters that might cause preg_replace to fail
		$html = str_replace("\0", '', $html);
		
		// Remove control characters that might break regex
		$html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $html);
		if ($html === null) {
			log_message('error', 'HTML cleaning preg_replace returned null');
			$html = '<div>Content processing error</div>';
		}
		
		// Don't pre-process HTML here - let TCPDF handle it
		// Pre-processing was causing issues with blank pages
		// TCPDF's internal processing will handle the HTML correctly
		
		// Only do minimal validation
		if (!is_string($html) || $html === null) {
			log_message('error', 'HTML is not a valid string');
			$html = '<div>Content processing error</div>';
		}
		
		// Log HTML size for debugging
		log_message('debug', 'writeHTML override: HTML size = ' . strlen($html) . ' bytes');
		if (strpos($html, '<table') === false) {
			log_message('error', 'WARNING: Table tag not found in HTML passed to writeHTML!');
		}
		
		// CRITICAL: Ensure TCPDF's re_space is initialized before calling parent writeHTML
		// This prevents the null error at line 16560 in TCPDF
		if (!isset($this->re_space) || !is_array($this->re_space)) {
			$this->setSpacesRE('/[^\S\xa0]/');
		}
		if (empty($this->re_space['p']) || $this->re_space['p'] === null) {
			$this->re_space['p'] = '[\s]';
		}
		if (!isset($this->re_space['m']) || $this->re_space['m'] === null) {
			$this->re_space['m'] = '';
		}
		
		// Call parent method with safe HTML
		try {
			return parent::writeHTML($html, $ln, $fill, $reseth, $cell, $align);
		} catch (Exception $e) {
			log_message('error', 'Exception in parent::writeHTML: ' . $e->getMessage());
			log_message('error', 'HTML size was: ' . strlen($html) . ' bytes');
			throw $e;
		} catch (Error $e) {
			log_message('error', 'Fatal error in parent::writeHTML: ' . $e->getMessage());
			log_message('error', 'HTML size was: ' . strlen($html) . ' bytes');
			throw $e;
		}
	}

	/**
	 * prepare
	 * @return
	 */
	public function prepare() {
		try {
			log_message('debug', 'PDF prepare() called. HTML size: ' . number_format(strlen($this->print_barcode)) . ' bytes');
			
			$this->set_view_vars('print_barcode', $this->print_barcode);
			
			log_message('debug', 'Calling build() method');
			$result = $this->build();
			
			if (!$result) {
				log_message('error', 'build() method returned false');
			} else {
				log_message('debug', 'build() method completed successfully');
			}
			
			return $result;
		} catch (Exception $e) {
			log_message('error', 'Exception in PDF prepare(): ' . $e->getMessage());
			log_message('error', 'File: ' . $e->getFile() . ' Line: ' . $e->getLine());
			log_message('error', 'Trace: ' . $e->getTraceAsString());
			throw $e;
		} catch (Error $e) {
			log_message('error', 'Fatal error in PDF prepare(): ' . $e->getMessage());
			log_message('error', 'File: ' . $e->getFile() . ' Line: ' . $e->getLine());
			throw $e;
		}
	}

	/**
	 * type
	 * @return
	 */
	protected function type() {
		return 'print_barcode';
	}

	/**
	 * file path
	 * @return
	 */
	protected function file_path() {
		$customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_requestpdf.php';
		$actualPath = APP_MODULES_PATH . '/warehouse/views/print_barcode_pdf.php';

		if (file_exists($customPath)) {
			$actualPath = $customPath;
		}

		return $actualPath;
	}
}