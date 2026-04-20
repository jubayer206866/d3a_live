<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('d3a_albania_model');

$aColumns = [
    '1',
    db_prefix() . 'alb_expenses.id as id',
    db_prefix() . 'expenses_categories.category_type as category_type',
    db_prefix() . 'expenses_categories.name as category_name',
    'amount',
    'date',
    // db_prefix() . 'projects.name as project_name',
];

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'alb_expenses.clientid',
    'LEFT JOIN ' . db_prefix() . 'expenses_categories ON ' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'alb_expenses.category',
    // 'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'alb_expenses.project_id',
    'LEFT JOIN ' . db_prefix() . 'files ON ' . db_prefix() . 'files.rel_id = ' . db_prefix() . 'alb_expenses.id AND rel_type="alb_expenses"',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'alb_expenses.currency',
];

$customFieldsColumns = [];
$custom_fields = get_table_custom_fields('alb_expenses');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'alb_expenses.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$where = [];
$filter_type     = $CI->input->post('filter_type');
$filter_category = $CI->input->post('filter_category');
$filter_customer = $CI->input->post('filter_customer');

if ($filter_category != '') {
    if (is_array($filter_category)) {
        $categoryIds = array_map(function($id) use ($CI) {
            return $CI->db->escape($id);
        }, $filter_category);
        $where[] = 'AND ' . db_prefix() . 'alb_expenses.category IN (' . implode(',', $categoryIds) . ')';
    } else {
        $where[] = 'AND ' . db_prefix() . 'alb_expenses.category = ' . $CI->db->escape($filter_category);
    }
}

if ($filter_customer != '') {
    if (is_array($filter_customer)) {
        $customerIds = array_map(function($id) use ($CI) {
            return $CI->db->escape($id);
        }, $filter_customer);
        $where[] = 'AND ' . db_prefix() . 'alb_expenses.clientid IN (' . implode(',', $customerIds) . ')';
    } else {
        $where[] = 'AND ' . db_prefix() . 'alb_expenses.clientid = ' . $CI->db->escape($filter_customer);
    }
}

if ($filter_type != '') {
    $where[] = 'AND ' . db_prefix() . 'expenses_categories.category_type = ' . $CI->db->escape($filter_type);
}

if (isset($clientid) && $clientid != '') {
    array_push($where, 'AND ' . db_prefix() . 'alb_expenses.clientid=' . $CI->db->escape_str($clientid));
}

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'alb_expenses';

$aColumns = hooks()->apply_filters('alb_expenses_table_sql_columns', $aColumns);

if (count($custom_fields) > 4) {
    @$CI->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'billable',
    db_prefix() . 'currencies.name as currency_name',
    db_prefix() . 'alb_expenses.clientid',
    'tax',
    'tax2',
    // 'project_id',
    'recurring',
]);
$output  = $result['output'];
$rResult = $result['rResult'];

$CI->load->model('payment_modes_model');

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';

    // ID with Edit/Delete actions
    $idOutput = $aRow['id'];
    $idOutput .= '<div class="row-options">';
    
    if (is_admin() || staff_can('edit_al_expense', 'd3a_albania')) {
        $idOutput .= '<a href="' . admin_url('d3a_albania/expense/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }

    if (is_admin() || staff_can('delete_al_expense', 'd3a_albania')) {
        if (is_admin() || staff_can('edit_al_expense', 'd3a_albania')) {
            $idOutput .= ' | ';
        }
        $idOutput .= '<a href="' . admin_url('d3a_albania/delete_expense/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
    }
    
    $idOutput .= '</div>';
    $row[] = $idOutput;

    // Category Type
    $categoryTypeOutput = '';
    if (strtolower($aRow['category_type']) == 'operational') {
        $categoryTypeOutput = 'Operational';
    } elseif (strtolower($aRow['category_type']) == 'customer') {
        $categoryTypeOutput = 'Customer';
    } else {
        $categoryTypeOutput = ucfirst(strtolower($aRow['category_type']));
    }
    $row[] = $categoryTypeOutput;

    // Expense Category
    $categoryOutput = '<span class="tw-font-medium">' . e($aRow['category_name']) . '</span>';
    
    if ($aRow['recurring'] == 1) {
        $categoryOutput .= ' <span class="label label-primary">' . _l('expense_recurring_indicator') . '</span>';
    }
    
    $row[] = $categoryOutput;

    $total = $aRow['amount'];
    $row[] = e(app_format_money($total, $aRow['currency_name']));

    $row[] = e(_d($aRow['date']));

    // $row[] = '<a href="' . admin_url('projects/view/' . $aRow['project_id']) . '">' . e($aRow['project_name']) . '</a>';

    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row['DT_RowClass'] = 'has-row-options';

    $row = hooks()->apply_filters('alb_expenses_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}