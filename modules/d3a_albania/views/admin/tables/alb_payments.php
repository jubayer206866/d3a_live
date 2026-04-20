<?php

defined('BASEPATH') or exit('No direct script access allowed');

$hasPermissionDelete = is_admin() || staff_can('delete_al_payment', 'd3a_albania');
$canEditPayment      = is_admin() || staff_can('add_al_payment', 'd3a_albania');

$aColumns = [
    db_prefix() . 'alb_payments.id as id',
    db_prefix() . 'alb_payments.invoiceid as invoiceid',
    db_prefix() . 'alb_payments.paymentmode as paymentmode',
    // db_prefix() . 'alb_payments.transactionid as transactionid',
    get_sql_select_client_company(),
    db_prefix() . 'alb_payments.amount as amount',
    db_prefix() . 'alb_payments.invoice_amount as invoice_amount',
    db_prefix() . 'alb_payments.date as date',
];

$join = [
    'LEFT JOIN ' . db_prefix() . 'alb_invoices ON ' . db_prefix() . 'alb_invoices.id = ' . db_prefix() . 'alb_payments.invoiceid',
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'alb_invoices.clientid',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'alb_invoices.invoice_currency',
    'LEFT JOIN ' . db_prefix() . 'payment_modes ON ' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'alb_payments.paymentmode',
];

$where = [];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'alb_payments';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'clientid',
    db_prefix() . 'currencies.id as currency_id',
    db_prefix() . 'currencies.name as currency_name',
    db_prefix() . 'payment_modes.name as payment_mode_name',
    db_prefix() . 'payment_modes.id as paymentmodeid',
    'paymentmethod',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

$this->ci->load->model('payment_modes_model');
$payment_gateways = $this->ci->payment_modes_model->get_payment_gateways(true);

foreach ($rResult as $aRow) {
    $row = [];

    $link = admin_url('d3a_albania/payment/' . $aRow['id']);

    $numberOutput = $canEditPayment
        ? '<a href="' . $link . '" class="tw-font-medium">' . e($aRow['id']) . '</a>'
        : '<span class="tw-font-medium">' . e($aRow['id']) . '</span>';

    $numberOutput .= '<div class="row-options">';
    if ($canEditPayment) {
        $numberOutput .= '<a href="' . $link . '">' . _l('view') . '</a>';
    }
    if ($hasPermissionDelete) {
        $numberOutput .= ' | <a href="' . admin_url('d3a_albania/delete_payment/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
    }
    $numberOutput .= '</div>';


    $row[] = $numberOutput;

    $invId = $aRow['invoiceid'];
    $invNum = e(format_alb_invoice_number_custom($invId));
    if (is_admin() || staff_can('edit_al_invoice', 'd3a_albania')) {
        $row[] = '<a href="' . admin_url('d3a_albania/invoice/' . $invId) . '">' . $invNum . '</a>';
    } elseif (staff_can('view_al_invoice', 'd3a_albania')) {
        $row[] = '<a href="' . admin_url('d3a_albania/alb_invoice/' . $invId) . '">' . $invNum . '</a>';
    } else {
        $row[] = $invNum;
    }

    $outputPaymentMode = e($aRow['payment_mode_name']);

    // Since version 1.0.1
    if (is_null($aRow['paymentmodeid'])) {
        foreach ($payment_gateways as $gateway) {
            if ($aRow['paymentmode'] == $gateway['id']) {
                $outputPaymentMode = e($gateway['name']);
            }
        }
    }

    if (! empty($aRow['paymentmethod'])) {
        $outputPaymentMode .= ' - ' . e($aRow['paymentmethod']);
    }
    $row[] = $outputPaymentMode;

    // $row[] = e($aRow['transactionid']);

    $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '">' . e($aRow['company']) . '</a>';

    $row[] = '<span class="tw-font-medium">' . e(app_format_money($aRow['amount'], $aRow['currency_id'])) . '</span>';
    $row[] = '<span class="tw-font-medium">' . e(app_format_money($aRow['invoice_amount'], $aRow['currency_id'])) . '</span>';

    $row[] = e(_d($aRow['date']));

    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
    
}