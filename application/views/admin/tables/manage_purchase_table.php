<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$currency = get_base_currency_pur()->symbol;
$project_id = $CI->input->get('project_id');

$aColumns = [
    'gr.id',
    'gr.goods_receipt_code',
    'p.name as project_name',
    'gr.supplier_name',
    'po.pur_order_number',
    'gr.total_goods_money',
    'cbm_sum.total_cbm',
    'po.delivery_status',
    'gr.buyer_id'
];

$sIndexColumn = 'gr.id';
$sTable = db_prefix() . 'goods_receipt as gr';

$join = [
    'LEFT JOIN ' . db_prefix() . 'projects p ON p.id = gr.project',
    'LEFT JOIN ' . db_prefix() . 'pur_orders po ON po.id = gr.pr_order_id',
    'LEFT JOIN (
        SELECT goods_receipt_id, SUM(CAST(total_cbm AS DECIMAL(12,6))) AS total_cbm 
        FROM ' . db_prefix() . 'goods_receipt_detail 
        GROUP BY goods_receipt_id
    ) cbm_sum ON cbm_sum.goods_receipt_id = gr.id'
];

$where = [];
if (!empty($project_id)) {
    $where[] = 'AND gr.project=' . $CI->db->escape_str($project_id);
}

$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    ['gr.id', 'gr.approval', 'gr.pr_order_id']
);

$output  = $result['output'];
$rResult = $result['rResult'];

$grand_total_goods = 0;
$grand_total_cbm = 0;

foreach ($rResult as $aRow) {
    $row = [];

    $docket_html = $aRow['goods_receipt_code'] . '<div class="row-options">';
    $docket_html .= '<a href="' . admin_url('warehouse/edit_purchase/' . $aRow['id']) . '">' . _l('view') . '</a>';
    $docket_html .= '</div>';
    $row[] = $docket_html;

    $row[] = $aRow['project_name'];
    $row[] = $aRow['supplier_name'];
    $row[] = $aRow['pur_order_number'];
    $row[] = app_format_money($aRow['total_goods_money'], $currency);
    $row[] = number_format($aRow['total_cbm'], 2) . ' m³';

    $status_html = '';
    if (isset($aRow['delivery_status'])) {
        if ($aRow['delivery_status'] == 0) {
            $status_html = '<span class="label label-warning">' . _l('waiting_to_receive') . '</span>';
        } elseif ($aRow['delivery_status'] == 1) {
            $status_html = '<span class="label label-success">' . _l('received') . '</span>';
        }

        if (has_permission('purchase_orders', '', 'edit') || is_admin()) {
            $status_html .= '<div class="dropdown inline-block mleft5">';
            $status_html .= '<a href="#" class="dropdown-toggle text-dark" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
            $status_html .= '<i class="fa fa-caret-down"></i></a>';
            $status_html .= '<ul class="dropdown-menu dropdown-menu-right">';
            if ($aRow['delivery_status'] == 0) {
                $status_html .= '<li><a href="#" onclick="update_po_delivery_status(1, ' . $aRow['pr_order_id'] . '); return false;">' . _l('received') . '</a></li>';
            }
            if ($aRow['delivery_status'] == 1) {
                $status_html .= '<li><a href="#" onclick="update_po_delivery_status(0, ' . $aRow['pr_order_id'] . '); return false;">' . _l('waiting_to_receive') . '</a></li>';
            }
            $status_html .= '</ul></div>';
        }
    }
    $row[] = $status_html;

    $buyer_html = '';
    if (!empty($aRow['buyer_id'])) {
        $buyer_html .= '<a href="' . admin_url('staff/profile/' . $aRow['buyer_id']) . '">' . staff_profile_image($aRow['buyer_id'], ['staff-profile-image-small']) . '</a>';
        $buyer_html .= ' <a href="' . admin_url('staff/profile/' . $aRow['buyer_id']) . '">' . get_staff_full_name($aRow['buyer_id']) . '</a>';
    }
    $row[] = $buyer_html;

    $output['aaData'][] = $row;

    $grand_total_goods += $aRow['total_goods_money'];
    $grand_total_cbm += $aRow['total_cbm'];
}

if (count($rResult) > 0) {
    $footer_row = [
        '<strong>Total</strong>',
        '', '', '',
        '<strong>' . app_format_money($grand_total_goods, $currency) . '</strong>',
        '<strong>' . number_format($grand_total_cbm, 2) . ' m³</strong>',
        '', ''
    ];
    $output['aaData'][] = $footer_row;
}

echo json_encode($output);
exit;
