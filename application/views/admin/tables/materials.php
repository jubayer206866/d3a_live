<?php
defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'material',
    'excise_type',
    'excise_value',
    'vat_on_excise',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'materials';

$where = [];
$join  = [];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {

    $row = [];

    $link = admin_url('calculators/material/' . $aRow['id']);

    $materialName  = '<a href="' . $link . '" class="tw-font-medium">'
        . ucfirst($aRow['material']) . '</a>';

    $materialName .= '<div class="row-options">';
    $materialName .= '<a href="#" onclick="edit_material(' . $aRow['id'] . '); return false;">'
        . _l('edit') . '</a> | ';
    $materialName .= '<a href="' . admin_url('calculators/delete_material/' . $aRow['id']) . '" class="_delete">'
        . _l('delete') . '</a>';
    $materialName .= '</div>';

    $row[] = $aRow['id'];
    $row[] = $materialName;
    $row[] = ucfirst(str_replace('_', ' ', $aRow['excise_type']));
    $row[] = $aRow['excise_value'];
    $row[] = ($aRow['vat_on_excise'] == 'Y') ? _l('yes') : _l('no');

    $output['aaData'][] = $row;
}
