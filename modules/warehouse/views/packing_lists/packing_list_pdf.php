<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('wh_packing_list') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . $packing_list_number . '</b>';


// Add logo
$info_left_column .= pdf_logo_url();

// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

$organization_info = '<div style="color:#424242;">';

$organization_info .= format_organization_info();

$organization_info .= '</div>';

// Bill to
$invoice_info = '<b>' . _l('invoice_bill_to') . ':</b>';
$invoice_info .= '<div style="color:#424242;">';
$invoice_info .= format_customer_info($packing_list, 'invoice', 'billing');
$invoice_info .= '</div>';

// ship to to
$invoice_info .= '<br /><b>' . _l('ship_to') . ':</b>';
$invoice_info .= '<div style="color:#424242;">';
$invoice_info .= format_customer_info($packing_list, 'invoice', 'shipping');
$invoice_info .= '</div>';

$invoice_info .= '<br />' . _l('packing_date') . ' ' . _d($packing_list->datecreated) . '<br />';



$left_info = $swap == '1' ? $invoice_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $invoice_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
// $items = get_items_table_data($invoice, 'invoice', 'pdf');
$item_description_width = 15;
$pdf_display_rate = get_option('packing_list_pdf_display_rate');
$pdf_display_tax = get_option('packing_list_pdf_display_tax');
$pdf_display_subtotal = get_option('packing_list_pdf_display_subtotal');
$pdf_display_discount_percent = get_option('packing_list_pdf_display_discount_percent');
$pdf_display_discount_amount = get_option('packing_list_pdf_display_discount_amount');
$pdf_display_totalpayment = get_option('packing_list_pdf_display_totalpayment');
$pdf_display_summary = get_option('packing_list_pdf_display_summary');
if ($pdf_display_rate == 0) {
	$item_description_width += 10;
}
if ($pdf_display_tax == 0) {
	$item_description_width += 10;
}
if ($pdf_display_subtotal == 0) {
	$item_description_width += 15;
}
if ($pdf_display_discount_percent == 0) {
	$item_description_width += 10;
}
if ($pdf_display_discount_amount == 0) {
	$item_description_width += 10;
}
if ($pdf_display_totalpayment == 0) {
	$item_description_width += 10;
}

$table_font_size = 'font-size:12px;';
$items = '';
$items .= '<table class="table">

<thead>';

$items .= '<tr>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 11%; text-align:center;">
  Product Code
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 11%; text-align:center;">
Product Name
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 8%; text-align:center;">
  Cartons
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
 Pieces/Carton
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
Total/Pieces
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
Price
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
Price/Total
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
Gross Weight
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
Total Gross Weight
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
Net Weight
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
Total Net Weight
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
CBM
</th>
<th style="color: #fff; background-color: #343a40; border: 1px solid #454d55; width: 7%; text-align:center;">
Total CBM
</th>

		</tr>

<tbody class="tbody-main" style="' . $table_font_size . '">';
$koli = 0;
$total_koli = 0;
$price_total = 0;
$total_net_weight = 0;
$total_gross_weight = 0;
$total_cbm = 0;
// render item table start
foreach ($packing_list->packing_list_detail as $key => $packing_list_detail) {
	if ($packing_list_detail['koli']) {
		$itemHTML = '';
		$itemHTML .= '<tr style="' . $table_font_size . '">';

		$itemHTML .= '<td align="center" width="11%">' . ($packing_list_detail['commodity_name']) . '</td>';
		$itemHTML .= '<td align="center" width="11%">' . ($packing_list_detail['commodity_long_description']) . '</td>';
		$itemHTML .= '<td align="center" width="8%">' . ($packing_list_detail['koli']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . ($packing_list_detail['cope_koli']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . ($packing_list_detail['total_koli']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . ($packing_list_detail['price']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . ($packing_list_detail['price_total']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . clean_number($packing_list_detail['gross_weight']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . clean_number($packing_list_detail['total_gross_weight']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . clean_number($packing_list_detail['net_weight']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . clean_number($packing_list_detail['total_net_weight']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . clean_number($packing_list_detail['cbm_koli']) . '</td>';
		$itemHTML .= '<td align="center" width="7%">' . clean_number($packing_list_detail['total_cbm']) . '</td>';
		// Close table row
		$itemHTML .= '</tr>';
		$items .= $itemHTML;
		$koli += $packing_list_detail['koli'];
		$total_koli += $packing_list_detail['total_koli'];
		$price_total += preg_replace('/[^0-9.]/', '', $packing_list_detail['price_total']);
		$total_net_weight += $packing_list_detail['total_net_weight'];
		$total_gross_weight += $packing_list_detail['total_gross_weight'];
		$total_cbm += $packing_list_detail['total_cbm'];
	}
}
// render item table end

$items .= '</tbody>
</table>';

$tblhtml = $items;
$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->Ln(8);

$tbltotal = '';
$tbltotal .= '<table cellpadding="6" style="font-size:' . ($font_size + 4) . 'px">';
$tbltotal .= '
		<tr>
		<td align="right" width="85%"><strong>Cartons</strong></td>
		<td align="right" width="15%">' . $koli . '</td>
		</tr>';

$tbltotal .= '
		<tr>
		<td align="right" width="85%"><strong>Total/Pieces</strong></td>
		<td align="right" width="15%">' . $total_koli . '</td>
		</tr>';
$tbltotal .= '
		<tr>
		<td align="right" width="85%"><strong>Price/Total</strong></td>
		<td align="right" width="15%">' . $price_total . '</td>
		</tr>';
$tbltotal .= '
		<tr>
		<td align="right" width="85%"><strong>Total Gross Weight</strong></td>
		<td align="right" width="15%"> ' . clean_number($total_gross_weight) . '</td>
		</tr>';
$tbltotal .= '
		<tr>
		<td align="right" width="85%"><strong>Total Net Weight</strong></td>
		<td align="right" width="15%"> ' . clean_number($total_net_weight) . '</td>
		</tr>';
$tbltotal .= '
		<tr>
		<td align="right" width="85%"><strong>Total CBM</strong></td>
		<td align="right" width="15%"> ' .clean_number ($total_cbm) . '</td>
		</tr>';



$tbltotal .= '</table>';
if ($pdf_display_summary == 1) {
	$pdf->writeHTML($tbltotal, true, false, false, false, '');
}



