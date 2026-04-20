<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$currency   = get_base_currency_pur()->symbol;
$project_id = $CI->input->get('project_id');

$aColumns = [
    'po.id',
    'po.pur_order_number',
    'po.total',
    'po.deposit',
    'IFNULL(pod.total_cbm, 0) as total_cbm',
    'po.delivery_date',
    'p.name as project_name',
    'v.company as vendor_name',
    'c.company as client_name',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'pur_orders as po';

$join = [
    'LEFT JOIN ' . db_prefix() . 'pur_vendor v ON v.userid = po.vendor',
    'LEFT JOIN (
        SELECT pur_order, SUM(CAST(total_cbm AS DECIMAL(12,6))) AS total_cbm
        FROM ' . db_prefix() . 'pur_order_detail
        GROUP BY pur_order
    ) pod ON pod.pur_order = po.id',
    'LEFT JOIN ' . db_prefix() . 'projects p ON p.id = po.project',
    'LEFT JOIN ' . db_prefix() . 'clients c ON c.userid = po.clients',
];

$where = [];
if (!empty($project_id)) {
    $where[] = 'AND po.project=' . $CI->db->escape_str($project_id);
}

$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    ['po.id']
);

$output  = $result['output'];
$rResult = $result['rResult'];

$grand_total = 0;
$grand_total_deposit = 0;
$grand_total_cbm = 0;

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = '<a href="' . admin_url('purchase/pur_order/' . $aRow['id']) . '" class="tw-font-medium">'
        . e($aRow['pur_order_number']) . '</a>';
    
    $row[] = app_format_money($aRow['total'], $currency);
    $row[] = app_format_money($aRow['deposit'], $currency);
    $row[] = number_format($aRow['total_cbm'], 2) . ' m³';
    $row[] = _d($aRow['delivery_date']);
    $row[] = e($aRow['project_name']);
    $row[] = e($aRow['vendor_name']);
    $row[] = e($aRow['client_name']);

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;

    $grand_total += $aRow['total'];
    $grand_total_deposit += $aRow['deposit'];
    $grand_total_cbm += $aRow['total_cbm'];
}

if (count($rResult) > 0) {
    $footer_row = [
        '<strong>Total</strong>',
        '<strong>' . app_format_money($grand_total, $currency) . '</strong>',
        '<strong>' . app_format_money($grand_total_deposit, $currency) . '</strong>',
        '<strong>' . number_format($grand_total_cbm, 2) . ' m³</strong>',
        '', '', '', ''
    ];
    $output['aaData'][] = $footer_row;
}

echo json_encode($output);
exit;