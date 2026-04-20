<?php

defined('BASEPATH') or exit('No direct script access allowed');

$custom_fields = get_custom_fields('pur_invoice', [
    'show_on_table' => 1,
]);

$aColumns = [
    'vendor_invoice_number',
    db_prefix() . 'pur_invoices.vendor',
    db_prefix() . 'clients.company',
    'goods_receipt_code',
    'invoice_date',
    db_prefix() . 'pur_invoices.subtotal',
    'payment_status',
    'vendor_note'
];
$sIndexColumn = 'id';
$sTable       = db_prefix() . 'pur_invoices';

$join = [
    'LEFT JOIN ' . db_prefix() . 'goods_receipt ON ' . db_prefix() . 'goods_receipt.id = ' . db_prefix() . 'pur_invoices.goods_receipt_id',
    'LEFT JOIN ' . db_prefix() . 'pur_orders ON ' . db_prefix() . 'pur_orders.id = ' . db_prefix() . 'goods_receipt.pr_order_id',
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'pur_orders.clients'
];

$i = 0;
foreach ($custom_fields as $field) {
    $select_as = 'cvalue_' . $i;
    if ($field['type'] == 'date_picker' || $field['type'] == 'date_picker_time') {
        $select_as = 'date_picker_cvalue_' . $i;
    }
    array_push($aColumns, 'ctable_' . $i . '.value as ' . $select_as);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $i . ' ON ' . db_prefix() . 'pur_invoices.id = ctable_' . $i . '.relid AND ctable_' . $i . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $i . '.fieldid=' . $field['id']);
    $i++;
}

$where = [];

if ($this->ci->input->post('from_date') && $this->ci->input->post('from_date') != '') {
    array_push($where, 'AND invoice_date >= "' . to_sql_date($this->ci->input->post('from_date')) . '"');
}

if (isset($vendor)) {
    array_push($where, ' AND ' . db_prefix() . 'pur_invoices.vendor = ' . $vendor);
}

if ($this->ci->input->post('to_date') && $this->ci->input->post('to_date') != '') {
    array_push($where, 'AND invoice_date <= "' . to_sql_date($this->ci->input->post('to_date')) . '"');
}

if (!has_permission('purchase_invoices', '', 'view')) {
    array_push($where, 'AND (' . db_prefix() . 'pur_invoices.add_from = ' . get_staff_user_id() . ' OR ' . db_prefix() . 'pur_invoices.vendor IN (SELECT vendor_id FROM ' . db_prefix() . 'pur_vendor_admin WHERE staff_id=' . get_staff_user_id() . '))');
}

$clients = $this->ci->input->post('clients');
if (!empty($clients)) {
    $where[] = 'AND ' . db_prefix() . 'pur_orders.clients IN (' . implode(',', array_map('intval', $clients)) . ')';
}

$vendors = $this->ci->input->post('vendors');
if (isset($vendors)) {
    $where_vendors = '';
    foreach ($vendors as $t) {
        if ($t != '') {
            if ($where_vendors == '') {
                $where_vendors .= ' AND (' . db_prefix() . 'pur_invoices.vendor = "' . $t . '"';
            } else {
                $where_vendors .= ' or ' . db_prefix() . 'pur_invoices.vendor = "' . $t . '"';
            }
        }
    }
    if ($where_vendors != '') {
        $where_vendors .= ')';
        array_push($where, $where_vendors);
    }
}

$statuses = $this->ci->input->post('payment_status');
if (!empty($statuses) && !in_array('all', $statuses)) {
    $escaped_statuses = array_map(function($s) {
        return $this->ci->db->escape_str($s);
    }, $statuses);
    $where[] = 'AND ' . db_prefix() . 'pur_invoices.payment_status IN ("' . implode('","', $escaped_statuses) . '")';
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'pur_invoices.id as id',
    db_prefix() . 'pur_invoices.currency as invoice_currency',
    db_prefix() . 'goods_receipt.goods_receipt_code as goods_receipt_code',
    db_prefix() . 'clients.company as client_name',
    db_prefix() . 'pur_invoices.subtotal',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

$this->ci->load->model('purchase/purchase_model');

foreach ($rResult as $aRow) {
    $row = [];

    for ($i = 0; $i < count($aColumns); $i++) {
        $base_currency = get_base_currency_pur();
        if ($aRow['invoice_currency'] != 0) {
            $base_currency = pur_get_currency_by_id($aRow['invoice_currency']);
        }

        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } else {
            $_data = $aRow[$aColumns[$i]];
        }

        if ($aColumns[$i] == 'vendor_invoice_number') {
            $numberOutput = '<a href="' . admin_url('purchase/purchase_invoice/' . $aRow['id']) . '"  >' . $aRow['vendor_invoice_number'] . '</a>';
            $numberOutput .= '<div class="row-options">';
            if (has_permission('purchase_invoices', '', 'view') || has_permission('purchase_invoices', '', 'view_own')) {
                $numberOutput .= ' <a href="' . admin_url('purchase/purchase_invoice/' . $aRow['id']) . '" >' . _l('view') . '</a>';
            }
            if ((has_permission('purchase_invoices', '', 'edit') || is_admin())) {
               // $numberOutput .= ' | <a href="' . admin_url('purchase/pur_invoice/' . $aRow['id']) . '">' . _l('edit') . '</a>';
            }
            if (has_permission('purchase_invoices', '', 'delete') || is_admin()) {
               // $numberOutput .= ' | <a href="' . admin_url('purchase/delete_pur_invoice/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $numberOutput .= '</div>';
            $_data = $numberOutput;
        } elseif ($aColumns[$i] == 'invoice_date') {
            $_data = _d($aRow['invoice_date']);
        } elseif ($aColumns[$i] == db_prefix() . 'pur_invoices.subtotal') {
            $_data = app_format_money($aRow['subtotal'], $base_currency->symbol);
        } elseif ($aColumns[$i] == 'payment_status') {
            $class = '';
            if ($aRow['payment_status'] == 'unpaid') {
                $class = 'danger';
            } elseif ($aRow['payment_status'] == 'paid') {
                $class = 'success';
            } elseif ($aRow['payment_status'] == 'partially_paid') {
                $class = 'warning';
            }

            $_data = '<span class="label label-' . $class . ' s-status invoice-status-3">' . _l($aRow['payment_status']) . '</span>';
        } elseif ($aColumns[$i] == 'client_name') {
            $_data = $aRow['client_name'];
        } elseif ($aColumns[$i] == 'goods_receipt_code') {
            $_data = $aRow['goods_receipt_code'];
        } elseif ($aColumns[$i] == db_prefix() . 'pur_invoices.vendor') {
            $_data = '<a href="' . admin_url('purchase/vendor/' . $aRow[db_prefix() . 'pur_invoices.vendor']) . '" >' .  get_vendor_company_name($aRow[db_prefix() . 'pur_invoices.vendor']) . '</a>';
        } else {
            if (strpos($aColumns[$i], 'date_picker_') !== false) {
                $_data = (strpos($_data, ' ') !== false ? _dt($_data) : _d($_data));
            }
        }

        $row[] = $_data;
    }

    $output['aaData'][] = $row;
}
