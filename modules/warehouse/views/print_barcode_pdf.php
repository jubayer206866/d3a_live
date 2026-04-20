<?php defined('BASEPATH') or exit('No direct script access allowed');

// Ensure print_barcode is never null
if (!isset($print_barcode) || $print_barcode === null) {
	$print_barcode = '';
	log_message('error', 'print_barcode is null in view file, setting to empty string');
}

// Convert to string if not already
if (!is_string($print_barcode)) {
	$print_barcode = (string)$print_barcode;
}

// Log the content received in view file
log_message('debug', 'View file: print_barcode size = ' . strlen($print_barcode) . ' bytes');
log_message('debug', 'View file: print_barcode first 200 chars = ' . substr($print_barcode, 0, 200));

// Theese lines should aways at the end of the document left side. Dont indent these lines
$html = <<<EOF
<div>
$print_barcode
</div>
EOF;

// Log the HTML before writing
log_message('debug', 'View file: HTML size before writeHTML = ' . strlen($html) . ' bytes');

// Ensure HTML is valid before writing
if (empty($html) || $html === null) {
	log_message('error', 'HTML is empty or null before writeHTML');
	$html = '<div>No content available</div>';
}

// Check if HTML only contains empty div
if (trim(strip_tags($html)) == '' && strpos($html, '<table') === false) {
	log_message('error', 'HTML appears to be empty (no table found). HTML content: ' . substr($html, 0, 500));
}

try {
	log_message('debug', 'View file: Calling writeHTML with HTML size = ' . strlen($html) . ' bytes');
	$pdf->writeHTML($html, true, false, true, false, '');
	log_message('debug', 'View file: writeHTML completed successfully');
} catch (Exception $e) {
	log_message('error', 'Error in writeHTML: ' . $e->getMessage());
	log_message('error', 'File: ' . $e->getFile() . ' Line: ' . $e->getLine());
	log_message('error', 'Trace: ' . $e->getTraceAsString());
	throw $e;
} catch (Error $e) {
	log_message('error', 'Fatal error in writeHTML: ' . $e->getMessage());
	log_message('error', 'File: ' . $e->getFile() . ' Line: ' . $e->getLine());
	log_message('error', 'Trace: ' . $e->getTraceAsString());
	throw $e;
}
