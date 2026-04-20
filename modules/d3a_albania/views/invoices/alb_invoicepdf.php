<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$alb_company_name = get_option('alb_invoice_company_name') ?: 'D3A TRADING COMPANY LIMITED';
$logo_html = function_exists('alb_pdf_logo_url') ? alb_pdf_logo_url() : pdf_logo_url();
if (!empty($logo_html) && strpos($logo_html, '<img') !== false) {
    $logo_html = str_replace('<img ', '<img style="display:block; margin-left:auto; margin-right:auto;" ', $logo_html);
}

// Centered header: Logo (middle) + Company name + COMMERCIAL INVOICE, then space before other info
$header_html = '<table cellpadding="0" cellspacing="0" style="width:100%;"><tr><td align="center" style="text-align:center;">';
$header_html .= '<div style="text-align:center; margin:0 auto;">' . $logo_html . '</div>';
$header_html .= '<b style="font-size:20px; font-weight:600;">' . htmlspecialchars($alb_company_name) . '</b><br>';
$header_html .= '<b style="font-size:20px;">' . _l('invoice_pdf_heading') . '</b>';
$header_html .= '</td></tr></table>';
$pdf->writeHTML($header_html, true, false, false, false, '');
$pdf->Ln(8);

$organization_info = '<div style="color:#424242;">';
$organization_info .= function_exists('format_alb_organization_info') ? format_alb_organization_info() : format_organization_info();
$organization_info .= '</div>';

// Bill to
$invoice_info = '<b>' . _l('invoice_bill_to') . ':</b>';
$invoice_info .= '<div style="color:#424242;">';
$invoice_info .= format_customer_info($invoice, 'invoice', 'billing');
$invoice_info .= '</div>';
if (!empty($invoice->client) && !empty($invoice->client->phonenumber)) {
    $invoice_info .= _l('invoice_customer_number') . ' ' . _d($invoice->client->phonenumber) . '<br />';
}
$invoice_info .= _l('invoice_data_date') . ' ' . _d($invoice->date) . '<br />';

$left_info  = $swap == '1' ? $invoice_info : $organization_info;
$right_info = 'Invoice No: <b style="color:#4e4e4e;"># ' . $invoice_number . '</b><br>';
$right_info .= $swap == '1' ? $organization_info : $invoice_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// Build items table (Marks And No, Description, Quantity, Unit, Currency, Amount)
$currency_display = $base_currency ? ($base_currency->name ?? $base_currency->symbol ?? '') : '';
$sum_amount = 0;
$sum_qty = 0;

// Use tbody for header row - TCPDF renders thead inconsistently; dark text on light bg for visibility
$tblhtml = '<table cellpadding="4" style="width:100%; border-collapse:collapse; border:1px solid #333;">';
$tblhtml .= '<tr style="background-color:#e8e8e8;"><td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:center;">' . _l('marks_and_no') . '</td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:left;">' . _l('invoice_table_item_description') . '</td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:center;">' . _l('alb_invoice_table_quantity') . '</td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:center;">' . _l('unit') . '</td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:center;">' . _l('currency') . '</td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:right;">' . _l('invoice_table_amount_heading') . '</td></tr>';

foreach ($invoice_detail as $es) {
    $amount = (float)($es['amount'] ?? 0);
    $qty = (float)($es['qty'] ?? 0);
    $sum_amount += $amount;
    $sum_qty += $qty;
    $marks_and_no = $client_name ?: '-';
    $unit_val = trim($es['unit'] ?? '');
    if ($unit_val === '') {
        $unit_val = '-';
    }
    $tblhtml .= '<tr nobr="true">';
    $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . htmlspecialchars($marks_and_no) . '</td>';
    $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:left;">' . htmlspecialchars($es['item_group'] ?? '') . '</td>';
    $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . htmlspecialchars((string)($es['qty'] ?? '')) . '</td>';
    $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . htmlspecialchars($unit_val) . '</td>';
    $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . htmlspecialchars($currency_display ?: '-') . '</td>';
    $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:right;">' . app_format_money($amount, $base_currency, true) . '</td>';
    $tblhtml .= '</tr>';
}
$tblhtml .= '<tr style="font-weight:bold;">';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;"></td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:left;">Total</td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . $sum_qty . '</td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;"></td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;"></td>';
$tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:right;">' . app_format_money($sum_amount, $base_currency, true) . '</td>';
$tblhtml .= '</tr>';
$tblhtml .= '</table>';

$pdf->writeHTML($tblhtml, true, false, false, false, '');

// Bank Information - from Company Information when set, else invoice clientnote/terms
$bank_info = get_option('alb_bank_information');
if (empty($bank_info)) {
    $bank_info = !empty($invoice->clientnote) ? $invoice->clientnote : (!empty($invoice->terms) ? $invoice->terms : '');
}
if (!empty($bank_info)) {
    $bank_info = clear_textarea_breaks($bank_info, "\n");
    $bank_info = _info_format_replace('company_name', get_option('alb_invoice_company_name') ?: '', $bank_info);
    $bank_info = nl2br(htmlspecialchars($bank_info, ENT_QUOTES, 'UTF-8'));
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('alb_bank_information') . ':', 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $bank_info, 0, 1, false, true, 'L', true);
}

// Signature / Stamp (ALB-specific or main)
$currentY = $pdf->GetY();
$imageWidth = 50;
$imageHeight = 0;
$pageWidth = $pdf->getPageWidth();
$margin = $pdf->getMargins();
$x = $pageWidth - $margin['right'] - $imageWidth;
$gap = 5;
$y = $currentY + $gap;
$signaturePath = function_exists('alb_pdf_signature_image') ? alb_pdf_signature_image() : '';
if (empty($signaturePath) && get_option('signature_image')) {
    $signaturePath = get_upload_path_by_type('company') . get_option('signature_image');
}
if (!empty($signaturePath) && file_exists($signaturePath)) {
    $signaturePath = str_replace('\\', '/', $signaturePath);
    if ($y + $imageWidth > $pdf->getPageHeight() - $margin['bottom']) {
        $pdf->AddPage();
        $y = $margin['top'];
    }
    $pdf->Image($signaturePath, $x, $y, $imageWidth, $imageHeight);
}
