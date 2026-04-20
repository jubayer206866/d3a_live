<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Check ALB invoice restrictions for public view (id + hash)
 *
 * @param mixed  $id   ALB invoice ID
 * @param string $hash Invoice hash
 */
function check_alb_invoice_restrictions($id, $hash)
{
    $CI = &get_instance();
    $CI->load->model('d3a_albania/d3a_albania_model');
    if (!$hash || !$id) {
        show_404();
    }
    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_invoice_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }
    $invoice = $CI->d3a_albania_model->get_invoice($id);
    if (!$invoice || (isset($invoice->hash) && $invoice->hash != $hash)) {
        show_404();
    }
    // Staff may only open the public preview URL if they have "Preview AL invoice" (admins bypass).
    if (is_staff_logged_in() && ! is_admin()) {
        if (! staff_can('preview_al_invoice', 'd3a_albania')) {
            show_404();
        }
    }
    if (!is_staff_logged_in()) {
        if (get_option('view_invoice_only_logged_in') == 1) {
            if ($invoice->clientid != get_client_user_id()) {
                show_404();
            }
        }
    }
}

function format_alb_invoice_number_custom($id)
{
    $CI = &get_instance();
    
    if (is_numeric($id)) {
        $invoice = $CI->d3a_albania_model->get_invoice($id);
    } elseif (is_object($id)) {
        $invoice = $id;
    } elseif (is_array($id) && isset($id['id'])) {
        $invoice = (object) $id;
    } else {
        return '';
    }
    if (!$invoice) {
        return '';
    }
    
    $is_draft = false;
    if (is_object($invoice) && property_exists($invoice, 'status')) {
        $is_draft = ($invoice->status == 6);
    } elseif (is_array($invoice) && isset($invoice['status'])) {
        $invoice = (object) $invoice;
        $is_draft = ($invoice->status == 6);
    }
    
    if ($is_draft) {
        return format_alb_draft_number_custom($invoice->number);
    } else {

        $formatted_number = $invoice->formatted_number;
        
        return $formatted_number;
    }
}

function get_next_alb_invoice_number_custom()
{
    $CI = &get_instance();
    
    $CI->db->where('option_name', 'alb_next_inv_number');
    $number_row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    
    return $number_row ? (int)$number_row->option_val : 1;
}

/**
 * Get ALB invoice number padding
 * @return int
 */
function get_alb_number_padding_custom()
{
    $CI = &get_instance();
    
    $CI->db->where('option_name', 'alb_number_padding');
    $padding_row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    
    return $padding_row ? (int)$padding_row->option_val : 6;
}

/**
 * Get ALB invoice prefix
 * @return string
 */
function get_alb_invoice_prefix_custom()
{
    $CI = &get_instance();
    
    $CI->db->where('option_name', 'alb_inv_prefix');
    $prefix_row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    
    return $prefix_row ? $prefix_row->option_val : 'ALB-INV-';
}

/**
 * Get next ALB draft number
 * @return int
 */
function get_next_alb_draft_number_custom()
{
    $CI = &get_instance();
    
    $CI->db->where('option_name', 'alb_next_draft_number');
    $number_row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    
    return $number_row ? (int)$number_row->option_val : 1;
}

/**
 * Increment next ALB draft number
 * @return int
 */
function increment_next_alb_draft_number_custom()
{
    $CI = &get_instance();
    
    $CI->db->where('option_name', 'alb_next_draft_number');
    $number_row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    $current_number = $number_row ? (int)$number_row->option_val : 1;
    $next_number = $current_number + 1;
    
    $CI->db->where('option_name', 'alb_next_draft_number');
    $CI->db->update(db_prefix() . 'purchase_option', ['option_val' => $next_number]);
    
    return $next_number;
}

/**
 * Format ALB draft number with prefix and padding
 * @param  mixed $number   draft number
 * @return string
 */
function format_alb_draft_number_custom($number)
{
    $CI = &get_instance();
    
    // Get ALB invoice prefix
    $CI->db->where('option_name', 'alb_inv_prefix');
    $prefix_row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    $prefix = $prefix_row ? $prefix_row->option_val : 'ALB-INV-';
    
    // Get number padding
    $CI->db->where('option_name', 'alb_number_padding');
    $padding_row = $CI->db->get(db_prefix() . 'purchase_option')->row();
    $padding = $padding_row ? (int)$padding_row->option_val : 7;
    
    // Format the number with leading zeros
    $formatted_number = $prefix . str_pad($number, $padding, '0', STR_PAD_LEFT);
    
    return $formatted_number;
}

/**
 * Handle ALB company logo upload (module-specific)
 */
function handle_alb_company_logo_upload($index)
{
    if (!isset($_FILES[$index]) || empty($_FILES[$index]['name'])) {
        return false;
    }
    if (function_exists('_perfex_upload_error') && _perfex_upload_error($_FILES[$index]['error'])) {
        set_alert('warning', _perfex_upload_error($_FILES[$index]['error']));
        return false;
    }
    $path = get_upload_path_by_type('alb_company');
    $tmpFilePath = $_FILES[$index]['tmp_name'];
    if (empty($tmpFilePath)) {
        return false;
    }
    $extension = strtolower(pathinfo($_FILES[$index]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
    if (!in_array($extension, $allowed)) {
        set_alert('warning', 'Image extension not allowed.');
        return false;
    }
    $filename = md5($index . time()) . '.' . $extension;
    $newFilePath = $path . $filename;
    _maybe_create_upload_path($path);
    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
        update_option($index, $filename);
        return true;
    }
    return false;
}

/**
 * Handle ALB company signature/stamp upload
 */
function handle_alb_signature_upload()
{
    if (!isset($_FILES['alb_signature_image']) || empty($_FILES['alb_signature_image']['name'])) {
        return false;
    }
    if (function_exists('_perfex_upload_error') && _perfex_upload_error($_FILES['alb_signature_image']['error'])) {
        set_alert('warning', _perfex_upload_error($_FILES['alb_signature_image']['error']));
        return false;
    }
    $path = get_upload_path_by_type('alb_company');
    $tmpFilePath = $_FILES['alb_signature_image']['tmp_name'];
    if (empty($tmpFilePath)) {
        return false;
    }
    $extension = strtolower(pathinfo($_FILES['alb_signature_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];
    if (!in_array($extension, $allowed)) {
        set_alert('warning', 'Image extension not allowed.');
        return false;
    }
    $filename = 'alb_signature_' . md5(time()) . '.' . $extension;
    $newFilePath = $path . $filename;
    _maybe_create_upload_path($path);
    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
        update_option('alb_signature_image', $filename);
        return true;
    }
    return false;
}

/**
 * Map of ALB company form fields to option names
 */
function get_alb_company_option_map()
{
    return [
        'invoice_company_name' => 'alb_invoice_company_name',
        'invoice_company_address' => 'alb_invoice_company_address',
        'invoice_company_city' => 'alb_invoice_company_city',
        'company_state' => 'alb_company_state',
        'invoice_company_country_code' => 'alb_invoice_company_country_code',
        'invoice_company_postal_code' => 'alb_invoice_company_postal_code',
        'invoice_company_phonenumber' => 'alb_invoice_company_phonenumber',
        'company_vat' => 'alb_company_vat',
        'company_info_format' => 'alb_company_info_format',
        'bank_information' => 'alb_bank_information',
    ];
}

/**
 * Format ALB company info for invoices/PDFs (uses alb_* options)
 */
function format_alb_organization_info()
{
    $alb_name = get_option('alb_invoice_company_name');
    $alb_format = get_option('alb_company_info_format');
    if (empty($alb_name) && empty($alb_format)) {
        return format_organization_info();
    }
    $format = !empty($alb_format) ? $alb_format : get_option('company_info_format');
    $vat = get_option('alb_company_vat') ?: '';
    $alb_phone = get_option('alb_invoice_company_phonenumber') ?: '';

    if (!empty($alb_phone) && strpos($format, '{phone}') === false) {
        $format .= '<br />{phone}';
    }

    $format = _info_format_replace('company_name', '<b style="color:black" class="company-name-formatted">' . ($alb_name ?: '') . '</b>', $format);
    $format = _info_format_replace('address', get_option('alb_invoice_company_address') ?: '', $format);
    $format = _info_format_replace('city', get_option('alb_invoice_company_city') ?: '', $format);
    $format = _info_format_replace('state', get_option('alb_company_state') ?: '', $format);
    $format = _info_format_replace('zip_code', get_option('alb_invoice_company_postal_code') ?: '', $format);
    $format = _info_format_replace('country_code', get_option('alb_invoice_company_country_code') ?: '', $format);
    $format = _info_format_replace('phone', $alb_phone, $format);
    $format = _info_format_replace('vat_number', $vat, $format);
    $format = _info_format_replace('vat_number_with_label', $vat == '' ? '' : _l('company_vat_number') . ': ' . $vat, $format);

    $format = _maybe_remove_first_and_last_br_tag($format);
    $format = preg_replace('/\s+/', ' ', $format);
    $format = trim($format);

    return $format;
}

/**
 * Get ALB signature/stamp image path for ALB invoice PDFs
 */
function alb_pdf_signature_image()
{
    $albCompanyPath = get_upload_path_by_type('alb_company');
    $companyPath = get_upload_path_by_type('company');
    $filename = get_option('alb_signature_image');
    if ($filename && file_exists($albCompanyPath . $filename)) {
        return $albCompanyPath . $filename;
    }
    $filename = get_option('signature_image');
    if ($filename && file_exists($companyPath . $filename)) {
        return $companyPath . $filename;
    }
    return '';
}

/**
 * Get logo HTML for ALB invoice PDF - prefers ALB-specific logo
 */
function alb_pdf_logo_url()
{
    $albCompanyPath = get_upload_path_by_type('alb_company');
    $companyUploadPath = get_upload_path_by_type('company');
    $logoUrl           = '';
    $width             = get_option('pdf_logo_width') ?: 120;

    if (get_option('alb_company_logo_dark') != '' && file_exists($albCompanyPath . get_option('alb_company_logo_dark'))) {
        $logoUrl = $albCompanyPath . get_option('alb_company_logo_dark');
    } elseif (get_option('alb_company_logo') != '' && file_exists($albCompanyPath . get_option('alb_company_logo'))) {
        $logoUrl = $albCompanyPath . get_option('alb_company_logo');
    } elseif (get_option('custom_pdf_logo_image_url') != '') {
        $logoUrl = get_option('custom_pdf_logo_image_url');
    } elseif (get_option('company_logo_dark') != '' && file_exists($companyUploadPath . get_option('company_logo_dark'))) {
        $logoUrl = $companyUploadPath . get_option('company_logo_dark');
    } elseif (get_option('company_logo') != '' && file_exists($companyUploadPath . get_option('company_logo'))) {
        $logoUrl = $companyUploadPath . get_option('company_logo');
    }

    if ($logoUrl != '') {
        $logoUrl = str_replace('\\', '/', $logoUrl);
        return '<img width="' . $width . 'px" src="' . $logoUrl . '">';
    }

    if (!function_exists('get_po_logo')) {
        $CI = &get_instance();
        $CI->load->helper('purchase/purchase');
    }
    if (function_exists('get_po_logo')) {
        $po_img = get_po_logo($width, '', 'pdf');
        if (!empty($po_img) && preg_match('/src="([^"]+)"/', $po_img, $m)) {
            $path = str_replace('\\', '/', $m[1]);
            return '<img width="' . $width . 'px" src="' . $path . '">';
        }
    }

    return '';
}

/**
 * Get amount left to pay for ALB invoice.
 * Uses invoice_amount if present, otherwise total. Sums payments from alb_payments.
 *
 * @param int $invoice_id ALB invoice ID
 * @return float
 */
function alb_invoice_left_to_pay($invoice_id)
{
    if (!is_numeric($invoice_id) || $invoice_id <= 0) {
        return 0;
    }
    $CI = &get_instance();
    $CI->db->select('total, invoice_amount');
    $CI->db->from(db_prefix() . 'alb_invoices');
    $CI->db->where('id', (int) $invoice_id);
    $invoice = $CI->db->get()->row();
    if (!$invoice) {
        return 0;
    }
    $invoice_total = 0;
    if ($CI->db->field_exists('invoice_amount', db_prefix() . 'alb_invoices') && isset($invoice->invoice_amount) && (float) $invoice->invoice_amount > 0) {
        $invoice_total = (float) $invoice->invoice_amount;
    } else {
        $invoice_total = (float) ($invoice->total ?? 0);
    }
    $CI->load->model('d3a_albania/d3a_albania_model');
    $total_paid = (float) $CI->d3a_albania_model->get_invoice_total_paid($invoice_id);
    return max(0, $invoice_total - $total_paid);
}

function get_alb_invoices_item_taxes($itemid)
{
    $CI = &get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'alb_invoice');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}

function po_invoice_left_to_pay($id)
{
    $CI = & get_instance();
    $CI->load->model('d3a_albania/d3a_albania_model');

    $CI->db->select('total')
        ->where('id', $id);
        $invoice_total = $CI->db->get(db_prefix() . 'alb_po_invoices')->row()->total;

    $CI->db->where('pur_invoice',$id);
    $CI->db->where('approval_status', 2);
    $payments = $CI->db->get(db_prefix().'alb_po_invoice_payment')->result_array();

    $debits  = $CI->d3a_albania_model->get_po_applied_invoice_debits($id);

    $payments = array_merge($payments, $debits);
    
    
    $totalPayments = 0;


    foreach ($payments as $payment) {
        
        $totalPayments += $payment['amount'];
        
    }
    
    return ($invoice_total - $totalPayments);
}

function po_get_currency_by_id($id){
    $CI   = & get_instance();

    $CI->db->where('id', $id);
    return  $CI->db->get(db_prefix().'currencies')->row();
}

function get_po_invoice_number($id){
    $CI = & get_instance();
    $CI->db->where('id',$id);
    $inv = $CI->db->get(db_prefix().'alb_po_invoices')->row();
    if($inv){
        return $inv->invoice_number;
    }else{
        return '';
    }
}

/**
 * Staff permission for D3a_albania module (register_staff_capabilities feature: d3a_albania).
 * Administrators always pass.
 *
 * @param string $capability e.g. view_al_invoice, create_al_expense
 * @param string $staff_id   optional staff id for staff_can()
 */
function d3a_albania_staff_can($capability, $staff_id = '')
{
    return is_admin() || staff_can($capability, 'd3a_albania', $staff_id);
}
