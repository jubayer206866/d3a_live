<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'alb_po_invoice_payment.id as id',
    db_prefix() . 'alb_po_invoice_payment.pur_invoice as po_invoice_id',
    db_prefix() . 'alb_po_invoice_payment.paymentmode as paymentmode',
    // db_prefix() . 'alb_po_invoice_payment.transactionid as transactionid',
    db_prefix() . 'pur_vendor. company as vendor_name',
    db_prefix() . 'alb_po_invoice_payment.amount as amount',
    db_prefix() . 'alb_po_invoices.total as invoice_amount',
    db_prefix() . 'alb_po_invoice_payment.date as date',
];
$join = [
    'LEFT JOIN ' . db_prefix() . 'alb_po_invoices ON ' . db_prefix() . 'alb_po_invoices.id = ' . db_prefix() . 'alb_po_invoice_payment.pur_invoice',
    'LEFT JOIN ' . db_prefix() . 'pur_vendor ON ' . db_prefix() . 'pur_vendor.userid = ' . db_prefix() . 'alb_po_invoices.vendor',
    'LEFT JOIN ' . db_prefix() . 'payment_modes ON ' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'alb_po_invoice_payment.paymentmode',
];

$where = [];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'alb_po_invoice_payment';
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'pur_vendor. company as vendor_name',
    db_prefix() . 'payment_modes.name as payment_mode_name',
    db_prefix() . 'payment_modes.id as paymentmodeid',
    'invoice_number',
]);


$output  = $result['output'];
$rResult = $result['rResult'];
$CI =& get_instance();
$CI->load->model('payment_modes_model');
$CI->load->model('currencies_model');
$payment_gateways = $CI->payment_modes_model->get_payment_gateways(true);

foreach ($rResult as $aRow) {
    $row = [];

    // Get vendor currency
    $vendor_currency_id = get_vendor_currency($aRow['po_invoice_id'], 'pur_invoice');
    $currency = $CI->currencies_model->get_base_currency();
    if ($vendor_currency_id && $vendor_currency_id != 0) {
        $currency = pur_get_currency_by_id($vendor_currency_id);
    }

    $link = admin_url('d3a_albania/payment_po_invoice/' . $aRow['id']);

    $numberOutput = '<a href="' . $link . '" class="tw-font-medium">' . e($aRow['id']) . '</a>';

    $hasPermissionDelete = is_admin() || staff_can('delete_al_purchase_payment', 'd3a_albania');
    $numberOutput .= '<div class="row-options">';
    $numberOutput .= '<a href="' . $link . '">' . _l('view') . '</a>';
    if ($hasPermissionDelete) {
        $numberOutput .= ' | <a href="' . admin_url('d3a_albania/delete_po_payment/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
    }
    $numberOutput .= '</div>';

    $row[] = $numberOutput;

    $row[] = '<a href="' . admin_url('d3a_albania/po_invoice/' . $aRow['po_invoice_id']) . '">' . e($aRow['invoice_number']) . '</a>';

    $outputPaymentMode = e($aRow['payment_mode_name']);

    // Since version 1.0.1
    if (is_null($aRow['paymentmodeid'])) {
        foreach ($payment_gateways as $gateway) {
            if ($aRow['paymentmode'] == $gateway['id']) {
                $outputPaymentMode = e($gateway['name']);
            }
        }
    }
    $row[] = $outputPaymentMode;

    // $row[] = e($aRow['transactionid']);

    $row[] = e($aRow['vendor_name']);

    $row[] = '<span class="tw-font-medium">' . e(app_format_money($aRow['amount'], $currency->name)) . '</span>';
    $row[] = '<span class="tw-font-medium">' . e(app_format_money($aRow['invoice_amount'], $currency->name)) . '</span>';

    $row[] = e(_d($aRow['date']));

    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}