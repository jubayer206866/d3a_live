<?php

defined('BASEPATH') or exit('No direct script access allowed');


$aColumns = [
    'pur_order_number',
    db_prefix().'projects.name as project',
    db_prefix().'pur_vendor.company as vendor_company',
    db_prefix().'clients.company as client_company',
    'total',
    'order_date',
    'delivery_date',
    'delivery_status',
    ];


$base_currency = get_base_currency_pur();

$sIndexColumn = 'id';
$sTable       = db_prefix().'pur_orders';
$join         = [
                    'LEFT JOIN '.db_prefix().'pur_vendor ON '.db_prefix().'pur_vendor.userid = '.db_prefix().'pur_orders.vendor',
                    'LEFT JOIN '.db_prefix().'projects ON '.db_prefix().'projects.id = '.db_prefix().'pur_orders.project',
                    'LEFT JOIN '.db_prefix().'clients ON '.db_prefix().'clients.userid = '.db_prefix().'pur_orders.clients',
                ];
$i = 0;


$where = [];

array_push($where, 'AND ((delivery_date >= "'.date('Y-m-d').'" AND delivery_date <= "'.date('Y-m-d', strtotime('+7 day', strtotime(date('Y-m-d')) )).'" ) OR delivery_status IN (0,2,3))');

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix().'pur_orders.id as id',db_prefix().'pur_vendor.company as vendor_company','pur_order_number','expense_convert', 'number', 'currency', db_prefix().'clients.company as client_company'
],
'',
'ORDER BY '.db_prefix().'pur_orders.delivery_date ASC'
);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

   for ($i = 0; $i < count($aColumns); $i++) {
        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } else {
            $_data = $aRow[$aColumns[$i]];
        }

        if($aRow['currency'] != 0){
            $base_currency = pur_get_currency_by_id($aRow['currency']);
        }

        if($aColumns[$i] == 'pur_order_number'){

            $numberOutput = '';
    
            $numberOutput = '<a href="' . admin_url('purchase/purchase_order/' . $aRow['id']) . '"  onclick="init_pur_order(' . $aRow['id'] . '); return false;" >'.$aRow['pur_order_number']. '</a>';
            
          

            $_data = $numberOutput;

        }elseif(strpos($aColumns[$i], 'vendor_company') !== false){
            $_data = '<a href="' . admin_url('purchase/vendor/' . $aRow['vendor']) . '" >' . $aRow['vendor_company'] . '</a>';
        }elseif($aColumns[$i] == 'client_company'){
            $_data = $aRow['client_company'];
        }elseif($aColumns[$i] == 'total'){
            $_data = app_format_money($aRow['total'],$base_currency->symbol);
        }elseif ($aColumns[$i] == 'order_date') {
            $_data = _d($aRow['order_date']);
        }elseif($aColumns[$i] == 'delivery_status'){
            $delivery_status = '';

            if($aRow['delivery_status'] == 0){
                $delivery_status = '<span class="inline-block label label-danger" task-status-table="undelivered">'._l('waiting_to_receive');
            }else if($aRow['delivery_status'] == 1){
                $delivery_status = '<span class="inline-block label label-success" task-status-table="completely_delivered">'._l('received');
            }
            $delivery_status .= '</span>';
            $_data = $delivery_status;
        }elseif($aColumns[$i] == 'delivery_date'){
            $_data = _d($aRow['delivery_date']);
        }

        $row[] = $_data;
    }
    $output['aaData'][] = $row;

}
