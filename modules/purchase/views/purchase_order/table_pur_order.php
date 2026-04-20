<?php

defined('BASEPATH') or exit('No direct script access allowed');

$custom_fields = get_custom_fields('pur_order', [
    'show_on_table' => 1,
    ]);

$aColumns = [
    'pur_order_name',
    'project',
    'total_cbm',
    db_prefix().'pur_orders.total as total',
    'deposit',
    db_prefix().'pur_vendor.company as vendor',
    db_prefix().'clients.company as company',
    'order_date',
    'delivery_date',
    'delivery_status',
    // 'payment_due_date',
    // 'pur_order_name',
    // 'approve_status',
    // 'number',
    // 'expense_convert',
    ];

if(isset($vendor) || isset($project)){
    $aColumns = [
    'pur_order_number',
    'total_cbm',
    db_prefix().'pur_orders.total as total',
    'vendor', 
    'order_date',
    'delivery_date',
    'number',
    'delivery_status',
    
    ];
}

$sIndexColumn = 'id';
$sTable       = db_prefix().'pur_orders';
$join = [
    'LEFT JOIN '.db_prefix().'pur_vendor ON '.db_prefix().'pur_vendor.userid = '.db_prefix().'pur_orders.vendor',
    'LEFT JOIN '.db_prefix().'clients ON '.db_prefix().'clients.userid = '.db_prefix().'pur_orders.clients',
    'LEFT JOIN '.db_prefix().'projects ON '.db_prefix().'projects.id = '.db_prefix().'pur_orders.project',
    'LEFT JOIN (
        SELECT pur_order, SUM(total_cbm) AS total_cbm
        FROM '.db_prefix().'pur_order_detail
        GROUP BY pur_order
    ) AS cbm_sum ON cbm_sum.pur_order = '.db_prefix().'pur_orders.id',
];
$i = 0;
foreach ($custom_fields as $field) {
    $select_as = 'cvalue_' . $i;
    if ($field['type'] == 'date_picker' || $field['type'] == 'date_picker_time') {
        $select_as = 'date_picker_cvalue_' . $i;
    }
    array_push($aColumns, 'ctable_' . $i . '.value as ' . $select_as);
    array_push($join, 'LEFT JOIN '.db_prefix().'customfieldsvalues as ctable_' . $i . ' ON '.db_prefix().'pur_orders.id = ctable_' . $i . '.relid AND ctable_' . $i . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $i . '.fieldid=' . $field['id']);
    $i++;
}

$where = [];

if(isset($vendor)){
    array_push($where, ' AND '.db_prefix().'pur_orders.vendor = '.$vendor);
}

if(isset($project)){
    array_push($where, ' AND '.db_prefix().'pur_orders.project = '.$project);
}

if ($this->ci->input->post('from_date')
    && $this->ci->input->post('from_date') != '') {
    array_push($where, 'AND order_date >= "'.$this->ci->input->post('from_date').'"');
}

if ($this->ci->input->post('to_date')
    && $this->ci->input->post('to_date') != '') {
    array_push($where, 'AND order_date <= "'.$this->ci->input->post('to_date').'"');
}
if ($this->ci->input->post('from_due_date')
    && $this->ci->input->post('from_due_date') != '') {
    array_push($where, 'AND payment_due_date >= "'.$this->ci->input->post('from_due_date').'"');
}

if ($this->ci->input->post('to_due_date')
    && $this->ci->input->post('to_due_date') != '') {
    array_push($where, 'AND payment_due_date <= "'.$this->ci->input->post('to_due_date').'"');
}


// if ($this->ci->input->post('status') && count($this->ci->input->post('status')) > 0) {
//     array_push($where, 'AND approve_status IN (' . implode(',', $this->ci->input->post('status')) . ')');
// }

if ($this->ci->input->post('vendor')
    && count($this->ci->input->post('vendor')) > 0) {
    array_push($where, 'AND vendor IN (' . implode(',', $this->ci->input->post('vendor')) . ')');
}

if ($this->ci->input->post('project')
    && count($this->ci->input->post('project')) > 0) {
    array_push($where, 'AND project IN (' . implode(',', $this->ci->input->post('project')) . ')');
}

if ($this->ci->input->post('clientid')
    && count($this->ci->input->post('clientid')) > 0) {
    array_push($where, 'AND clients IN (' . implode(',', $this->ci->input->post('clientid')) . ')');
}

if ($this->ci->input->post('delivery_status')
    && count($this->ci->input->post('delivery_status')) > 0) {
    array_push($where, 'AND delivery_status IN (' . implode(',', $this->ci->input->post('delivery_status')) . ')');
}

// if ($this->ci->input->post('purchase_request')
//     && count($this->ci->input->post('purchase_request')) > 0) {
//     array_push($where, 'AND pur_request IN (' . implode(',', $this->ci->input->post('purchase_request')) . ')');
// }

if(!has_permission('purchase_orders', '', 'view')){
   array_push($where, 'AND (' . db_prefix() . 'pur_orders.addedfrom = '.get_staff_user_id().' OR ' . db_prefix() . 'pur_orders.buyer = '.get_staff_user_id().' OR ' . db_prefix() . 'pur_orders.vendor IN (SELECT vendor_id FROM ' . db_prefix() . 'pur_vendor_admin WHERE staff_id=' . get_staff_user_id() . ') OR '.get_staff_user_id().' IN (SELECT staffid FROM ' . db_prefix() . 'pur_approval_details WHERE ' . db_prefix() . 'pur_approval_details.rel_type = "pur_order" AND ' . db_prefix() . 'pur_approval_details.rel_id = '.db_prefix().'pur_orders.id))');
}

$type = $this->ci->input->post('type');
if (isset($type)) {
    $where_type = '';
    foreach ($type as $t) {
        if ($t != '') {
            if ($where_type == '') {
                $where_type .= ' AND (tblpur_orders.type = "' . $t . '"';
            } else {
                $where_type .= ' or tblpur_orders.type = "' . $t . '"';
            }
        }
    }
    if ($where_type != '') {
        $where_type .= ')';
        array_push($where, $where_type);
    }
}

//tags filter
$tags_ft = $this->ci->input->post('item_filter');
if (isset($tags_ft)) {
    $where_tags_ft = '';
    foreach ($tags_ft as $commodity_id) {
        if ($commodity_id != '') {
            if ($where_tags_ft == '') {
                $where_tags_ft .= ' AND (tblpur_orders.id = "' . $commodity_id . '"';
            } else {
                $where_tags_ft .= ' or tblpur_orders.id = "' . $commodity_id . '"';
            }
        }
    }
    if ($where_tags_ft != '') {
        $where_tags_ft .= ')';
        array_push($where, $where_tags_ft);
    }
}
$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    [
        db_prefix().'pur_orders.id as id',
        db_prefix().'clients.company as company',
        db_prefix().'pur_orders.pur_order_number',
        'expense_convert',
        db_prefix().'projects.name as project_name',
        'currency',
        'payment_due_date',
        'cbm_sum.total_cbm as total_cbm',
        db_prefix().'pur_orders.total as total',
    ],
);

$output  = $result['output'];
$rResult = $result['rResult'];


$this->ci->load->model('purchase/purchase_model');
$grand_total_cbm = 0;
$grand_total = 0;

foreach ($rResult as $aRow) {
    $row = [];

   for ($i = 0; $i < count($aColumns); $i++) {
        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } else {
            $_data = $aRow[$aColumns[$i]];
        }

        $base_currency = get_base_currency_pur();
        if($aRow['currency'] != 0){
            $base_currency = pur_get_currency_by_id($aRow['currency']);
        }

       if($aColumns[$i] == 'pur_order_name'){

            $numberOutput = '';
    
            $numberOutput = '<a href="' . admin_url('purchase/purchase_order/' . $aRow['id']) . '"  onclick="init_pur_order(' . $aRow['id'] . '); return false;" >'.$aRow['pur_order_number']. '</a>';
            
            $numberOutput .= '<div class="row-options">';

            if (has_permission('purchase_orders', '', 'view') || has_permission('purchase_orders', '', 'view_own')) {
                $numberOutput .= ' <a href="' . admin_url('purchase/purchase_order/' . $aRow['id']) . '" onclick="init_pur_order(' . $aRow['id'] . '); return false;" >' . _l('view') . '</a>';
            }

                $numberOutput .= ' | <a href="' . admin_url('purchase/pur_order/' . $aRow['id']) . '">' . _l('edit') . '</a>';
            
            if (has_permission('purchase_orders', '', 'delete') || is_admin()) {
               // $numberOutput .= ' | <a href="' . admin_url('purchase/delete_pur_order/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }
            $numberOutput .= '</div>';

            $_data = $numberOutput;

        }elseif($aColumns[$i] == 'vendor'){
            $_data = '<a href="' . admin_url('purchase/vendor/' . $aRow['vendor']) . '" >' .  $aRow['company'] . '</a>';
        }elseif ($aColumns[$i] == 'payment_due_date') {
            $_data = _d($aRow['payment_due_date']);
        }elseif ($aColumns[$i] == '') {
            $_data = _d($aRow['order_date']);
        }elseif($aColumns[$i] == 'approve_status'){
            $_data = get_status_approve($aRow['approve_status']);
        }elseif($aColumns[$i] == 'expense_convert'){
            if($aRow['expense_convert'] == 0){
                $_data = '<a href="javascript:void(0)" onclick="convert_expense('.$aRow['id'].','.$aRow['total'].'); return false;" class="btn btn-warning btn-icon">'._l('convert').'</a>';
            }else{
                $_data = '<a href="'.admin_url('expenses/list_expenses/'.$aRow['expense_convert']).'" class="btn btn-success btn-icon">'._l('view_expense').'</a>';
            }
        } elseif ($aColumns[$i] == 'total' || strpos($aColumns[$i], 'total as total') !== false) {
            $_data = app_format_money($aRow['total'], $base_currency->symbol);
        } elseif ($aColumns[$i] == 'deposit') {
            $_data = app_format_money($aRow['deposit'], $base_currency->symbol);
        }elseif($aColumns[$i] == 'subtotal'){
            $_data = app_format_money($aRow['subtotal'],$base_currency->symbol);
        }elseif($aColumns[$i] == 'project'){
            $_data = $aRow['project_name'];
        } elseif($aColumns[$i] == 'delivery_status'){
            $delivery_status = '';

        if($aRow['delivery_status'] == 0){
            $delivery_status = '<span class="inline-block label label-danger" id="status_span_'.$aRow['id'].'" task-status-table="waiting_to_receive">'._l('waiting_to_receive');
        }else if($aRow['delivery_status'] == 1){
            $delivery_status = '<span class="inline-block label label-success" id="status_span_'.$aRow['id'].'" task-status-table="received">'._l('received');
        }elseif($aRow['delivery_status'] == 2){
            $delivery_status = '<span class="inline-block label label-default" id="status_span_'.$aRow['id'].'" task-status-table="cancelled">'. _l('cancelled');
        }
        // if(has_permission('purchase_orders', '', 'edit') || is_admin()){
        //     $delivery_status .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        //     $delivery_status .= '<a href="#" class="dropdown-toggle text-dark" id="tablePurOderStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        //     $delivery_status .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        //     $delivery_status .= '</a>';

        //     $delivery_status .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePurOderStatus-' . $aRow['id'] . '">';

        //     if($aRow['delivery_status'] == 0){
        //         $delivery_status .= '<li><a href="#" onclick="change_delivery_status(1, '.$aRow['id'].'); return false;">'._l('received').'</a></li>';
        //     } else {
        //         $delivery_status .= '<li><a href="#" onclick="change_delivery_status(0, '.$aRow['id'].'); return false;">'._l('waiting_to_receive').'</a></li>';
        //     }

        //     $delivery_status .= '</ul></div>';
        // }

            $delivery_status .= '</span>';
            $_data = $delivery_status;
        } elseif($aColumns[$i] == 'delivery_date'){
            $_data = _d($aRow['delivery_date']);
        } if ($aColumns[$i] == 'total_cbm') {
            $_data = number_format($aRow['total_cbm'], 2) . ' m³';
        }else if($aColumns[$i] == 'number'){
            $paid = $aRow['total'] - purorder_inv_left_to_pay($aRow['id']);

            $percent = 0;

            if($aRow['total'] > 0){

                $percent = ($paid / $aRow['total']) * 100;

            }

            

            $_data = '<div class="progress">

                          <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="' .round($percent).'"

                          aria-valuemin="0" aria-valuemax="100" style="width:'.round($percent).'%" data-percent="' .round($percent).'">

                           ' .round($percent).' % 

                          </div>

                        </div>';

        }else {
            if (strpos($aColumns[$i], 'date_picker_') !== false) {
                $_data = (strpos($_data, ' ') !== false ? _dt($_data) : _d($_data));
            }
        }

        $row[] = $_data;
    }
    $grand_total_cbm += $aRow['total_cbm'];
    $grand_total += $aRow['total'];
    $output['aaData'][] = $row;
}
$footer_row = [];
$base_symbol = get_base_currency_pur()->symbol;

$grand_total_deposit = 0;
foreach ($rResult as $aRow) {
    $grand_total_deposit += $aRow['deposit'];
}
foreach ($aColumns as $col) {

    if ($col == 'project') {
        $footer_row[] = '<strong>Total</strong>';

    } elseif ($col == 'total_cbm') {
        $footer_row[] = number_format($grand_total_cbm, 2) . ' m³';

    } elseif ($col == db_prefix().'pur_orders.total as total' || $col == 'total') {
        $footer_row[] = $base_symbol . ' ' . number_format($grand_total, 2);

    } elseif ($col == 'deposit') {
        $footer_row[] = $base_symbol . ' ' . number_format($grand_total_deposit, 2);

    } else {
        $footer_row[] = '';
    }
}
$output['aaData'][] = $footer_row;