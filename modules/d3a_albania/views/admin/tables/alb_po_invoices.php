<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$CI->db->query("SET SESSION group_concat_max_len = 1000000");

$aColumns = [
    'invoice_number',
    'item_names',
    'descriptions',
    db_prefix() . 'pur_vendor.company as vendor_company',
    'total',
    db_prefix() . 'currencies.name as currency_name',
    'payment_status'
];

$sIndexColumn = 'unique_invoices.id';

$sTable = "(SELECT 
    ai.id,
    ai.invoice_number,
    ai.vendor,
    ai.invoice_date,
    ai.duedate,
    ai.subtotal,
    ai.total,
    ai.currency,
    ai.payment_status,
    GROUP_CONCAT(aoi.item_name SEPARATOR ', ') as item_names,
    GROUP_CONCAT(aoi.description SEPARATOR ', ') as descriptions
FROM " . db_prefix() . "alb_po_invoices ai
LEFT JOIN " . db_prefix() . "alb_po_invoice_items aoi 
    ON aoi.pur_invoice = ai.id
GROUP BY ai.id
) as unique_invoices";

$join = [
    'LEFT JOIN ' . db_prefix() . 'pur_vendor ON ' . db_prefix() . 'pur_vendor.userid = unique_invoices.vendor',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = unique_invoices.currency'
];


$where = [];
$invoice_currency = $CI->input->post('invoice_currency');
$date_filter      = $CI->input->post('date_filter');
$status_filter    = $CI->input->post('status_filter');
$report_from      = $CI->input->post('report_from');
$report_to        = $CI->input->post('report_to');
/* Currency Filter */
if (isset($invoice_currency) && is_array($invoice_currency) && count($invoice_currency) > 0) {
    $currency_where = '';
    foreach ($invoice_currency as $currency_id) {
        $currency_id = $CI->db->escape_str($currency_id);
        if ($currency_where == '') {
            $currency_where .= ' AND (currency = "' . $currency_id . '"';
        } else {
            $currency_where .= ' OR currency = "' . $currency_id . '"';
        }
    }
    $currency_where .= ')';
    $where[] = $currency_where;
}
/* Status Filter */
if (isset($status_filter) && is_array($status_filter) && count($status_filter) > 0) {
    $status_where = '';
    foreach ($status_filter as $status) {
        $status = $CI->db->escape_str($status);
        if ($status_where == '') {
            $status_where .= ' AND (payment_status = "' . $status . '"';
        } else {
            $status_where .= ' OR payment_status = "' . $status . '"';
        }
    }
    $status_where .= ')';
    $where[] = $status_where;
}
if ($date_filter == 'this_month') {
    $where[] = 'AND MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE())';
}


if ($date_filter == '1') {
    $where[] = 'AND MONTH(invoice_date) = MONTH(CURDATE() - INTERVAL 1 MONTH)';
}

if ($date_filter == 'this_year') {
    $where[] = 'AND YEAR(invoice_date) = YEAR(CURDATE())';
}

if ($date_filter == 'last_year') {
    $where[] = 'AND YEAR(invoice_date) = YEAR(CURDATE() - INTERVAL 1 YEAR)';
}

if ($date_filter == '3') {
    $where[] = 'AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)';
}

if ($date_filter == '6') {
    $where[] = 'AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)';
}

if ($date_filter == '12') {
    $where[] = 'AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)';
}

if ($date_filter == 'custom') {
    if ($report_from != '' && $report_to != '') {
        $where[] = 'AND invoice_date BETWEEN "' . $report_from . '" AND "' . $report_to . '"';
    }
}

if ($CI->input->post('from_date') && $CI->input->post('from_date') != '') {
    array_push($where, 'AND invoice_date >= "' . $CI->input->post('from_date') . '"');
}
if ($CI->input->post('to_date') && $CI->input->post('to_date') != '') {
    array_push($where, 'AND invoice_date <= "' . $CI->input->post('to_date') . '"');
}


$vendor_ft = $CI->input->post('vendor_ft');
if (isset($vendor_ft) && is_array($vendor_ft) && count($vendor_ft) > 0) {
    $where_vendors = '';
    foreach ($vendor_ft as $vendor_id) {
        if ($vendor_id != '') {
            if ($where_vendors == '') {
                $where_vendors .= ' AND (vendor = "' . $vendor_id . '"';
            } else {
                $where_vendors .= ' OR vendor = "' . $vendor_id . '"';
            }
        }
    }
    if ($where_vendors != '') {
        $where_vendors .= ')';
        array_push($where, $where_vendors);
    }
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'unique_invoices.id',
    'unique_invoices.invoice_number',
    'unique_invoices.vendor'
]);

$output  = $result['output'];
$rResult = $result['rResult'];

$CI->load->model('currencies_model');
$base_currency = $CI->currencies_model->get_base_currency();

foreach ($rResult as $aRow) {
    $row = [];

    // Invoice Number
    $formattedNumber = $aRow['invoice_number'];
    $numberOutput = e($formattedNumber);
    $numberOutput .= '<div class="row-options">';
    $numberOutput .= '<a href="' . admin_url('d3a_albania/albania_po_invoice/' . $aRow['id']) . '">' . _l('view') . '</a>';
    if (is_admin() || staff_can('edit_al_purchase_invoice', 'd3a_albania')) {
        $numberOutput .= ' | <a href="' . admin_url('d3a_albania/po_invoice/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }
    if (is_admin() || staff_can('delete_al_purchase_invoice', 'd3a_albania')) {
        $numberOutput .= ' | <a href="' . admin_url('d3a_albania/delete_po_invoices/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
    }
    $numberOutput .= '</div>';
    $row[] = $numberOutput;

    // Item Names
    $row[] = $aRow['item_names'] ?? '-';

    // Descriptions
    $row[] = $aRow['descriptions'] ?? '-';

    // Vendor
    if ($aRow['vendor_company']) {
        $row[] = get_vendor_company_name($aRow['vendor']);
    } else {
        $row[] = $aRow['vendor'] ? $aRow['vendor'] : '-';
    }

    // Total Amount
    $row[] = app_format_money($aRow['total'], $aRow['currency_name']);

    // Paid Amount
    $left_to_pay = po_invoice_left_to_pay($aRow['id']);
    $paid_amount = $aRow['total'] - $left_to_pay;
    $row[] = app_format_money($paid_amount, $aRow['currency_name']);

    // Currency
    $row[] = $aRow['currency_name'];

    // Status
    $class = '';
    if ($aRow['payment_status'] == 'unpaid') {
        $class = 'danger';
    } elseif ($aRow['payment_status'] == 'paid') {
        $class = 'success';
    } elseif ($aRow['payment_status'] == 'partially_paid') {
        $class = 'warning';
    }
    $row[] = '<span class="label label-' . $class . ' s-status">' . _l($aRow['payment_status'], $aRow['payment_status']) . '</span>';

    $output['aaData'][] = $row;
}

echo json_encode($output);
