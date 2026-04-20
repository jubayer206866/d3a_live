<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column='<b style="font-size:20px; font-weight:600; text-align:center">D3A TRADING COMPANY LIMITED</b>.<br>';
$info_right_column.='<b style="font-size:20px; text-align:center">' . _l('invoice_pdf_heading') . '</b>';
// Add logo
$info_left_column .= pdf_logo_url();
// Write top left logo and right column info/text
pdf_third_row('',$info_left_column, '', $pdf);
pdf_third_row('',$info_right_column, '', $pdf,30,90,0);




$organization_info = '<div style="color:#424242;">';

$organization_info .= format_organization_info();

$organization_info .= '</div>';

// Bill to
$invoice_info = '<b>' . _l('invoice_bill_to') . ':</b>';
$invoice_info .= '<div style="color:#424242;">';
$invoice_info .= format_customer_info($invoice, 'invoice', 'billing');
$invoice_info .= '</div>';
$invoice_info .= _l('invoice_customer_number') . ' ' . _d($invoice->client->phonenumber) . '<br />';
$invoice_info .=  _l('invoice_data_date') . ' ' . _d($invoice->date) . '<br />';

$invoice_info = hooks()->apply_filters('invoice_pdf_header_after_date', $invoice_info, $invoice);

if (! empty($invoice->duedate)) {
    $invoice_info .= _l('invoice_data_duedate') . ' ' . _d($invoice->duedate) . '<br />';
    $invoice_info = hooks()->apply_filters('invoice_pdf_header_after_due_date', $invoice_info, $invoice);
}

if ($invoice->sale_agent && get_option('show_sale_agent_on_invoices') == 1) {
    $invoice_info .= _l('sale_agent_string') . ': ' . get_staff_full_name($invoice->sale_agent) . '<br />';
    $invoice_info = hooks()->apply_filters('invoice_pdf_header_after_sale_agent', $invoice_info, $invoice);
}

if ($invoice->project_id && get_option('show_project_on_invoice') == 1) {
    $invoice_info .= _l('project') . ': ' . get_project_name_by_id($invoice->project_id) . '<br />';
    $invoice_info = hooks()->apply_filters('invoice_pdf_header_after_project_name', $invoice_info, $invoice);
}

$invoice_info = hooks()->apply_filters('invoice_pdf_header_before_custom_fields', $invoice_info, $invoice);

foreach ($pdf_custom_fields as $field) {
    $value = get_custom_field_value($invoice->id, $field['id'], 'invoice');
    if ($value == '') {
        continue;
    }
    $invoice_info .= $field['name'] . ': ' . $value . '<br />';
}

$invoice_info      = hooks()->apply_filters('invoice_pdf_header_after_custom_fields', $invoice_info, $invoice);
$organization_info = hooks()->apply_filters('invoicepdf_organization_info', $organization_info, $invoice);
$invoice_info      = hooks()->apply_filters('invoice_pdf_info', $invoice_info, $invoice);

$left_info  = $swap == '1' ? $invoice_info : $organization_info;
$right_info='Invoice No:<b style="color:#4e4e4e;"># ' . $invoice_number . '</b><br>';
$right_info .= $swap == '1' ? $organization_info : $invoice_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$items = get_items_table_data($invoice, 'invoice', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');
$tbltotal .= '</table>';
$pdf->writeHTML($tbltotal, true, false, false, false, '');


if (! empty($invoice->clientnote)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('bank_information'), 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $invoice->clientnote, 0, 1, false, true, 'L', true);
}

if (! empty($invoice->terms)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('bank_information') . ':', 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $invoice->terms, 0, 1, false, true, 'L', true);
}
// 2. Get current Y (where content ended)
$currentY = $pdf->GetY();

// 3. Define your image size
$imageWidth = 50;  // mm
$imageHeight = 0;   // 0 = auto-calc height to preserve aspect ratio

// 4. Calculate X for right alignment
$pageWidth = $pdf->getPageWidth();
$margin = $pdf->getMargins();
$x = $pageWidth - $margin['right'] - $imageWidth;

// 5. Small gap after content
$gap = 5; // mm

// 6. New Y position
$y = $currentY + $gap;

// 7. Check if image will overflow bottom margin
if ($y + $imageWidth * ($imageHeight / $imageWidth) > $pdf->getPageHeight() - $margin['bottom']) {
    // not enough space: add new page and reset Y
    $pdf->AddPage();
    $y = $margin['top'];
}
$signatureImage = get_option('signature_image');

$signaturePath = FCPATH . 'uploads/company/' . $signatureImage;
$signatureExists = file_exists($signaturePath);

$pdf->Image(
    $signaturePath,
    $x,
    $y,
    $imageWidth,
    $imageHeight
);