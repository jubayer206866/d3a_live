<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
	'tblwh_packing_lists.id as id',
	'packing_list_number',
	'goods_delivery_code',
	'clientid',
	'volume',
	'total_amount',
	'datecreated',
	'tblwh_packing_lists.approval as approval',
	'tblwh_packing_lists.delivery_status as delivery_status',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'wh_packing_lists';
$join = [];
$join = [
	'LEFT JOIN ' . db_prefix() . 'goods_delivery ON ' . db_prefix() . 'goods_delivery.id = ' . db_prefix() . 'wh_packing_lists.delivery_note_id',

];
$where = [];

if ($this->ci->input->post('from_date')) {
	array_push($where, "AND date_format(datecreated, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime(to_sql_date($this->ci->input->post('from_date')))) . "'");
}
if ($this->ci->input->post('to_date')) {
	array_push($where, "AND date_format(datecreated, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime(to_sql_date($this->ci->input->post('to_date')))) . "'");
}
if ($this->ci->input->post('staff_id') && $this->ci->input->post('staff_id') != '') {
	array_push($where, 'AND staff_id IN (' . implode(', ', $this->ci->input->post('staff_id')) . ')');
}

if ($this->ci->input->post('status_id') && $this->ci->input->post('status_id') != '') {
	$status_arr = $this->ci->input->post('status_id');
	if (in_array(5, $this->ci->input->post('status_id'))) {
		$status_arr[] = 0;
	}
	array_push($where, 'AND tblwh_packing_lists.approval IN (' . implode(', ', $status_arr) . ')');

}

if ($this->ci->input->post('delivery_id') && $this->ci->input->post('delivery_id') != '') {
	array_push($where, 'AND delivery_note_id IN (' . implode(', ', $this->ci->input->post('delivery_id')) . ')');
}

if (!has_permission('wh_packing_list', '', 'view')) {
	array_push($where, 'AND (' . db_prefix() . 'wh_packing_lists.staff_id=' . get_staff_user_id() . ')');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['tblwh_packing_lists.id as id', 'packing_list_name', 'width', 'height', 'lenght', 'volume', 'tblwh_packing_lists.additional_discount as additional_discount']);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
	$row = [];

	$row[] = $aRow['id'];

	$name = '<a href="' . admin_url('warehouse/view_packing_list/' . $aRow['id']) . '" onclick="init_packing_list(' . $aRow['id'] . '); return false;">' . $aRow['packing_list_number'] . ' - ' . $aRow['packing_list_name'] . '</a>';

	$name .= '<div class="row-options">';
	$name .= '<a href="' . admin_url('warehouse/manage_packing_list/' . $aRow['id']) . '" >' . _l('view') . '</a>';

	if ((has_permission('wh_packing_list', '', 'edit') || is_admin()) && ($aRow['approval'] == 0)) {
		$name .= ' | <a href="' . admin_url('warehouse/packing_list/' . $aRow['id']) . '" >' . _l('edit') . '</a>';
	}

	if ((has_permission('wh_packing_list', '', 'delete') || is_admin()) && ($aRow['approval'] == 0)) {
		$name .= ' | <a href="' . admin_url('warehouse/delete_packing_list/' . $aRow['id']) . '" class="text-danger _delete" >' . _l('delete') . '</a>';
	}

	$name .= '</div>';

	$row[] = $name;
	$row[] = get_company_name($aRow['clientid']);
	$row[] = ($aRow['goods_delivery_code']);
	$cbm_summary = get_packing_list_cbm_summary($aRow['id']);
	$row[] = number_format($cbm_summary['total_cbm'], 2);
	$row[] = app_format_money($aRow['total_amount'], '');
	$row[] = _dt($aRow['datecreated']);

	$approve_data = '';
	if ($aRow['approval'] == 1) {
		$approve_data = '<span class="label label-tag tag-id-1 label-tab1"><span class="tag">' . _l('approved') . '</span><span class="hide">, </span></span>&nbsp';
	} elseif ($aRow['approval'] == 0) {
		$approve_data = '<span class="label label-tag tag-id-1 label-tab2"><span class="tag">' . _l('not_yet_approve') . '</span><span class="hide">, </span></span>&nbsp';
	} elseif ($aRow['approval'] == -1) {
		$approve_data = '<span class="label label-tag tag-id-1 label-tab3"><span class="tag">' . _l('reject') . '</span><span class="hide">, </span></span>&nbsp';
	}

	$row[] = $approve_data;

	$row[] = render_delivery_status_html($aRow['id'], 'packing_list', $aRow['delivery_status']);


	$output['aaData'][] = $row;

}
