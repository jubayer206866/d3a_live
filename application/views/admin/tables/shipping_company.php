<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = ['id', 'name', 'link'];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'shipping_company';

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], []);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    foreach ($aColumns as $column) {
        $_data = $aRow[$column];

        if ($column == 'link') {
            $_data = '<a href="' . $_data . '" target="_blank">' . $_data . '</a>';
        }

        $row[] = $_data;
    }

    $options = '<div class="tw-flex tw-items-center tw-space-x-2">';
    $options .= '<a href="javascript:void(0);" onclick="edit_shipping_company(' . $aRow['id'] . ')" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"><i class="fa-regular fa-pen-to-square fa-lg"></i></a>';
    $options .= '<a href="' . admin_url('Settings/shipping_company_delete/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete"><i class="fa-regular fa-trash-can fa-lg"></i></a>';
    $options .= '</div>';

    $row[] = $options;

    $output['aaData'][] = $row;
}
