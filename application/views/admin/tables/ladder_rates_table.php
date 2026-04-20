<?php
defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'min_cbm',
    'max_cbm',
    'rate',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'ladder_rates';

$where = [];
$join  = [];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $minCbmCell = '<a href="#" class="tw-font-medium" onclick="edit_ladder_rate(' . $aRow['id'] . '); return false;">' . $aRow['min_cbm'] . '</a>';
    $minCbmCell .= '<div class="row-options">';
    $minCbmCell .= '<a href="#" onclick="edit_ladder_rate(' . $aRow['id'] . '); return false;">' . _l('edit') . '</a> | ';
    $minCbmCell .= '<a href="' . admin_url('calculators/delete_ladder_rate/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
    $minCbmCell .= '</div>';

    $row[] = $aRow['id'];
    $row[] = $minCbmCell;
    $row[] = $aRow['max_cbm'];
    $row[] = $aRow['rate'];

    $output['aaData'][] = $row;
}
