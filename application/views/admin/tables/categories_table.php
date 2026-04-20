<?php
defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'category',
    'use_category_floor',
    'floor_percent_of_pv',
    'floor_euro_per_cbm',
    'customs_duty',
    'vat_integre_percent',
    'excise_type',
    'excise_value',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'categories';

$where = [];
$join  = [];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {

    $row = [];

    $link = admin_url('calculators/category/' . $aRow['id']); 

    $categoryName = '<a href="' . $link . '" class="tw-font-medium">' . ucfirst($aRow['category']) . '</a>';
    $categoryName .= '<div class="row-options">';
    $categoryName .= '<a href="#" onclick="edit_category(' . $aRow['id'] . '); return false;">'
    . _l('edit') . '</a> | ';

    $categoryName .= '<a href="' . admin_url('calculators/delete_category/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
    $categoryName .= '</div>';

    $row[] = $aRow['id'];
    $row[] = $categoryName;
    $row[] = ($aRow['use_category_floor'] == 'Y') ? _l('yes') : _l('no');
    $row[] = $aRow['floor_percent_of_pv'] . '%';
    $row[] = $aRow['floor_euro_per_cbm'];
    $row[] = $aRow['customs_duty'];
    $row[] = $aRow['vat_integre_percent'] !== null && $aRow['vat_integre_percent'] !== '' ? $aRow['vat_integre_percent'] . '%' : '';
    $row[] = ucfirst(str_replace('_', ' ', $aRow['excise_type']));
    $row[] = $aRow['excise_value'];

    $output['aaData'][] = $row;
}


