<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('d3a_albania_model');

// Extract clientid from passed data
if (isset($data) && is_array($data)) {
    extract($data);
}

if (!isset($clientid)) {
    $clientid = '';
}

$aColumns = [
    db_prefix().'alb_invoices.formatted_number as formatted_number',
    db_prefix().'alb_invoices.invoice_amount as invoice_amount',
    db_prefix().'alb_invoices.date as date',
    db_prefix().'clients.company as company',
    db_prefix().'alb_invoices.status as status'
];

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'alb_invoices.clientid',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'alb_invoices.invoice_currency',
    'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'alb_invoices.project_id',
];

$customFieldsColumns = [];
$custom_fields = get_table_custom_fields('invoice');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'alb_invoices.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$where = [];
$invoice_currency = $CI->input->post('invoice_currency');
$date_filter      = $CI->input->post('date_filter');
$report_from      = $CI->input->post('report_from');
$report_to        = $CI->input->post('report_to');
$customer         = $CI->input->post('filter_customer');
$status           = $CI->input->post('status_filter');
    if ($invoice_currency != '') {
        $where[] = 'AND ' . db_prefix() . 'alb_invoices.invoice_currency=' . $CI->db->escape($invoice_currency);
    }
    if ($customer != '') {
        if (is_array($customer)) {
            $customerIds = array_map(function($id) use ($CI) {
                return $CI->db->escape($id);
            }, $customer);
            $where[] = 'AND ' . db_prefix() . 'alb_invoices.clientid IN (' . implode(',', $customerIds) . ')';
        } else {
            $where[] = 'AND ' . db_prefix() . 'alb_invoices.clientid=' . $CI->db->escape($customer);
        }
    }
    if ($status != '') {
        // Handle multiple status selection
        if (is_array($status)) {
            $statusIds = array_map(function($id) use ($CI) {
                return $CI->db->escape($id);
            }, $status);
            $where[] = 'AND ' . db_prefix() . 'alb_invoices.status IN (' . implode(',', $statusIds) . ')';
        } else {
            $where[] = 'AND ' . db_prefix() . 'alb_invoices.status=' . $CI->db->escape($status);
        }
    }
if ($date_filter != '') {

    if ($date_filter == 'this_month') {
        $where[] = 'AND MONTH(' . db_prefix() . 'alb_invoices.date)=MONTH(CURDATE())
                    AND YEAR(' . db_prefix() . 'alb_invoices.date)=YEAR(CURDATE())';
    }

    if ($date_filter == '1') { // last month
        $where[] = 'AND MONTH(' . db_prefix() . 'alb_invoices.date)=MONTH(CURDATE()-INTERVAL 1 MONTH)
                    AND YEAR(' . db_prefix() . 'alb_invoices.date)=YEAR(CURDATE()-INTERVAL 1 MONTH)';
    }

    if ($date_filter == 'this_year') {
        $where[] = 'AND YEAR(' . db_prefix() . 'alb_invoices.date)=YEAR(CURDATE())';
    }

    if ($date_filter == 'last_year') {
        $where[] = 'AND YEAR(' . db_prefix() . 'alb_invoices.date)=YEAR(CURDATE()-INTERVAL 1 YEAR)';
    }

    if ($date_filter == '3') {
        $where[] = 'AND ' . db_prefix() . 'alb_invoices.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)';
    }

    if ($date_filter == '6') {
        $where[] = 'AND ' . db_prefix() . 'alb_invoices.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)';
    }

    if ($date_filter == '12') {
        $where[] = 'AND ' . db_prefix() . 'alb_invoices.date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)';
    }

    if ($date_filter == 'custom' && !empty($report_from) && !empty($report_to)) {
        $where[] = 'AND ' . db_prefix() . 'alb_invoices.date >= "' . $CI->db->escape_str($report_from) . '" 
                    AND ' . db_prefix() . 'alb_invoices.date <= "' . $CI->db->escape_str($report_to) . '"';
    }

}

if ($clientid != '') {
    array_push($where, 'AND ' . db_prefix() . 'alb_invoices.clientid=' . $CI->db->escape_str($clientid));
}

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'alb_invoices';

$aColumns = hooks()->apply_filters('alb_invoices_table_sql_columns', $aColumns);

if (count($custom_fields) > 4) {
    @$CI->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'alb_invoices.id',
    db_prefix() . 'currencies.name as currency_name',
    db_prefix() . 'alb_invoices.clientid',
    'project_id',
    'hash',
]);
$output  = $result['output'];
$rResult = $result['rResult'];

$canViewAlInvoice    = is_admin() || staff_can('view_al_invoice', 'd3a_albania');
$canPreviewAlInvoice = is_admin() || staff_can('preview_al_invoice', 'd3a_albania');

foreach ($rResult as $aRow) {
    $row = [];

    $numberOutput = '';

    if ($canViewAlInvoice) {
        $numberOutput = '<a href="' . admin_url('d3a_albania/invoices#' . $aRow['id']) . '" class="tw-font-medium" onclick="if(typeof init_alb_invoice==\'function\'){ init_alb_invoice(' . (int)$aRow['id'] . '); return false; }">' . e($aRow['formatted_number']) . '</a>';
    } else {
        $numberOutput = '<span class="tw-font-medium text-muted">' . e($aRow['formatted_number']) . '</span>';
    }

    $rowOpts = [];
    if ($canPreviewAlInvoice && ! empty($aRow['hash'])) {
        $rowOpts[] = '<a href="' . site_url('alb_invoice/' . $aRow['id'] . '/' . $aRow['hash']) . '" target="_blank">View</a>';
    }
    if (is_admin() || staff_can('edit_al_invoice', 'd3a_albania')) {
        $rowOpts[] = '<a href="' . admin_url('d3a_albania/invoice/' . $aRow['id']) . '">Edit</a>';
    }
    if (is_admin() || staff_can('delete_al_invoice', 'd3a_albania')) {
        $rowOpts[] = '<a href="' . admin_url('d3a_albania/delete_alb_invoice/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
    }
    $numberOutput .= '<div class="row-options">' . implode(' | ', $rowOpts) . '</div>';

    $row[] = $numberOutput;

    $row[] = '<span class="tw-font-medium">' . e(app_format_money($aRow['invoice_amount'], $aRow['currency_name'])) . '</span>';

    $row[] = e(_d($aRow['date']));
  
    $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '">' . e($aRow['company']) . '</a>';

    $row[] = format_invoice_status($aRow['status']);

    $row['DT_RowClass'] = 'has-row-options';
    $row['DT_RowId'] = 'row_' . $aRow['id'];

    $output['aaData'][] = $row;
}