<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'goods_receipt.id',
    'goods_receipt_code',
    db_prefix() . 'clients.company as customer_name',
    db_prefix() . 'projects.name as project_name',
    'supplier_name',
    'buyer_id',
    'pr_order_id',
    'date_add',
    'total_goods_money',
    db_prefix() . 'pur_orders.delivery_status as delivery_status',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'goods_receipt';
$join = [
    'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'goods_receipt.project',
    'LEFT JOIN ' . db_prefix() . 'pur_orders ON ' . db_prefix() . 'pur_orders.id = ' . db_prefix() . 'goods_receipt.pr_order_id',
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'pur_orders.clients',
];
$where = [];

if ($this->ci->input->post('day_vouchers')) {
    $day_vouchers = to_sql_date($this->ci->input->post('day_vouchers'));
}
// Customer filter
$customers = $this->ci->input->post('customer');
if(!empty($customers)) {
    $where[] = 'AND tblpur_orders.clients IN (' . implode(',', array_map('intval', $customers)) . ')';
}

// Project filter
$projects = $this->ci->input->post('project');
if(!empty($projects)) {
    $where[] = 'AND tblgoods_receipt.project IN (' . implode(',', array_map('intval', $projects)) . ')';
}

// Supplier filter
$suppliers = $this->ci->input->post('supplier');
if(!empty($suppliers)) {
    $where[] = 'AND tblgoods_receipt.supplier_code IN (' . implode(',', array_map('intval', $suppliers)) . ')';
}

// Buyer filter
$buyers = $this->ci->input->post('buyer');
if(!empty($buyers)) {
    $where[] = 'AND tblgoods_receipt.buyer_id IN (' . implode(',', array_map('intval', $buyers)) . ')';
}

if (isset($day_vouchers)) {

    $where[] = 'AND tblgoods_receipt.date_add <= "' . $day_vouchers . '"';

}
if (!has_permission('wh_stock_import', '', 'view')) {
    array_push($where, 'AND (' . db_prefix() . 'goods_receipt.addedfrom=' . get_staff_user_id() . ' OR ' . db_prefix() . 'goods_receipt.buyer_id=' . get_staff_user_id() . ')');
}


$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'goods_receipt.id as id', 'date_add', 'date_c', 'goods_receipt_code', 'supplier_code', 'pur_inv_id', db_prefix() . 'projects.status as project_status',]);

$output = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];

    for ($i = 0; $i < count($aColumns); $i++) {

        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'supplier_name') {

            if (get_status_modules_wh('purchase') && ($aRow['supplier_code'] != '') && ($aRow['supplier_code'] != 0)) {
                $_data = wh_get_vendor_company_name($aRow['supplier_code']);
            } else {
                $_data = $aRow['supplier_name'];
            }

        } elseif ($aColumns[$i] == db_prefix() . 'clients.company as customer_name') {
            $_data = $aRow['customer_name'] ? $aRow['customer_name'] : '';
        } elseif ($aColumns[$i] == 'buyer_id') {
            $_data = '<a href="' . admin_url('staff/profile/' . $aRow['buyer_id']) . '">' . staff_profile_image($aRow['buyer_id'] ?? 0, [
                'staff-profile-image-small',
            ]) . '</a>';
            $_data .= ' <a href="' . admin_url('staff/profile/' . $aRow['buyer_id']) . '">' . get_staff_full_name($aRow['buyer_id']) . '</a>';
        } elseif ($aColumns[$i] == 'date_add') {
            $_data = _d($aRow['date_add']);
        } elseif ($aColumns[$i] == 'total_tax_money') {
            $_data = app_format_money((float) $aRow['total_tax_money'], '');
        } elseif ($aColumns[$i] == 'goods_receipt_code') {
            $name = '<a href="' . admin_url('warehouse/view_purchase/' . $aRow['id']) . '" onclick="init_goods_receipt(' . $aRow['id'] . '); return false;">' . $aRow['goods_receipt_code'] . '</a>';

            $name .= '<div class="row-options">';

            $name .= '<a href="' . admin_url('warehouse/edit_purchase/' . $aRow['id']) . '" >' . _l('view') . '</a>';

            if ((has_permission('wh_stock_import', '', 'edit') || is_admin())) {
                if ($aRow['project_status'] != 4) {
                    $name .= ' | <a href="' . admin_url('warehouse/manage_goods_receipt/' . $aRow['id']) . '" >' . _l('edit') . '</a>';
                }
            }

            if ((has_permission('wh_stock_import', '', 'delete') || is_admin()) && ($aRow['approval'] == 0)) {
                $name .= ' | <a href="' . admin_url('warehouse/delete_goods_receipt/' . $aRow['id']) . '" class="text-danger _delete" >' . _l('delete') . '</a>';
            }

            if (get_warehouse_option('revert_goods_receipt_goods_delivery') == 1) {
                if ((has_permission('wh_stock_import', '', 'delete') || is_admin()) && ($aRow['approval'] == 1)) {
                    $name .= ' | <a href="' . admin_url('warehouse/revert_goods_receipt/' . $aRow['id']) . '" class="text-danger _delete" >' . _l('delete_after_approval') . '</a>';
                }
            }

            // if ((!$aRow['pur_inv_id']) && ($aRow['delivery_status'] == 0)) {
            //     $name .= ' | <a href="' . admin_url('warehouse/convert_pur_inv_from_irv/' . $aRow['id']) . '" class="_delete">' . _l('convert_to_pur_invoice') . '</a>';
            // }




            $name .= '</div>';

            $_data = $name;
        } elseif ($aColumns[$i] == 'total_goods_money') {
            $_data = app_format_money((float) $aRow['total_goods_money'], '');
        } if ($aColumns[$i] == db_prefix() . 'pur_orders.delivery_status as delivery_status') {
            $delivery_status_html = '';

            if ($aRow['delivery_status'] == 0) {
                $delivery_status_html = '<span class="inline-block label label-danger" id="delivery_span_'.$aRow['id'].'" task-status-table="waiting_to_receive">'._l('waiting_to_receive');
            } else if ($aRow['delivery_status'] == 1) {
                $delivery_status_html = '<span class="inline-block label label-success" id="delivery_span_'.$aRow['id'].'" task-status-table="received">'._l('received');
            } else if ($aRow['delivery_status'] == 2) {
                $delivery_status_html = '<span class="inline-block label label-default" id="delivery_span_'.$aRow['id'].'" task-status-table="cancelled">'._l('cancelled');
            }

            if(has_permission('purchase_orders', '', 'edit') || is_admin()){
                if($aRow['delivery_status'] == 0){
                    $delivery_status_html .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
                    $delivery_status_html .= '<a href="#" class="dropdown-toggle text-dark" id="tableDeliveryStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $delivery_status_html .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
                    $delivery_status_html .= '</a>';

                    $delivery_status_html .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableDeliveryStatus-' . $aRow['id'] . '">';
                    $delivery_status_html .= '<li><a href="#" onclick="change_delivery_status(1, '.$aRow['id'].'); return false;">'._l('received').'</a></li>';
                    $delivery_status_html .= '<li><a href="#" onclick="change_delivery_status(2, '.$aRow['id'].'); return false;">'._l('cancelled').'</a></li>';
                    $delivery_status_html .= '</ul></div>';
                }
            }

            $delivery_status_html .= '</span>';
            $_data = $delivery_status_html;
        } elseif ($aColumns[$i] == 'pr_order_id') {
            $get_pur_order_name = '';
            if (get_status_modules_wh('purchase')) {
                if (($aRow['pr_order_id'] != '') && ($aRow['pr_order_id'] != 0)) {
                    $get_pur_order_name .= '<a href="' . admin_url('purchase/purchase_order/' . $aRow['pr_order_id']) . '" >' . get_pur_order_name($aRow['pr_order_id']) . '</a>';
                }
            }

            $_data = $get_pur_order_name;

        } elseif ($aColumns[$i] == db_prefix() . 'projects.name as project_name') {
            $_data = $aRow['project_name'];
        }



        $row[] = $_data;
    }
    $output['aaData'][] = $row;

}
