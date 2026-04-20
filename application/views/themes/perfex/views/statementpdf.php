<?php

defined('BASEPATH') or exit('No direct script access allowed');
$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column = '<div style="color:#424242;">';
$info_right_column .= format_organization_info();
$info_right_column .= '</div>';

$info_left_column .= pdf_logo_url();
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

$y = $pdf->getY();

$client_details = '<b>' . _l('statement_bill_to') . '</b>';
$client_details .= '<div style="color:#424242;">';
$client_details .= format_customer_info($statement['client'], 'statement', 'billing');
$client_details .= '</div>';

$pdf->writeHTMLCell(($dimensions['wk'] / 2) - $dimensions['lm'] + 15, '', '', $y, $client_details, 0, 0, false, true, 'J', true);

$display_beginning_balance = isset($statement['beginning_balance']) ? $statement['beginning_balance'] : 0;
$display_invoiced_amount = isset($statement['invoiced_amount']) ? $statement['invoiced_amount'] : 0;
$display_amount_paid = isset($statement['amount_paid']) ? $statement['amount_paid'] : 0;
$display_balance_due = isset($statement['balance_due']) ? $statement['balance_due'] : 0;

if (isset($statement['statements']) && !empty($statement['statements'])) {
    $display_invoiced_amount = 0;
    $display_amount_paid = 0;
    $display_balance_due = 0;
    foreach ($statement['statements'] as $r) {
        $display_invoiced_amount += floatval($r['invoice_amount']);
        $display_amount_paid += floatval($r['invoice_total']);
        $display_balance_due += floatval($r['balance']);
    }
}

$summary = '';
$summary .= '<h2>' . _l('account_summary') . '</h2>';
$from_display = !empty($statement['from']) ? _d($statement['from']) : '';
$to_display   = !empty($statement['to']) ? _d($statement['to']) : '';
$date_range_text = ($from_display || $to_display) ? _l('statement_from_to', [$from_display, $to_display]) : 'All dates';
$summary .= '<div style="color:#676767;">' . $date_range_text . '</div>';
$summary .= '<hr />';
$summary .= '
<table cellpadding="4" border="0" style="color:#424242;" width="100%">
   <tbody>
      <tr>
          <td align="left"><br /><br />' . _l('statement_beginning_balance') . ':</td>
          <td><br /><br />' . app_format_money($display_beginning_balance, $statement['currency']) . '</td>
      </tr>
      <tr>
          <td align="left">' . _l('invoiced_amount') . ':</td>
          <td>' . app_format_money($display_invoiced_amount, $statement['currency']) . '</td>
      </tr>
      <tr>
          <td align="left">' . _l('amount_paid') . ':</td>
          <td>' . app_format_money($display_amount_paid, $statement['currency']) . '</td>
      </tr>
  </tbody>
  <tfoot>
      <tr>
        <td align="left"><b>' . _l('balance_due') . '</b>:</td>
        <td>' . app_format_money($display_balance_due, $statement['currency']) . '</td>
    </tr>
  </tfoot>
</table>';

$pdf->writeHTMLCell(($dimensions['wk'] / 2) - $dimensions['rm'] - 15, '', '', '', $summary, 0, 1, false, true, 'R', true);

$summary_info = '
<div style="text-align: center;">
    ' . _l('customer_statement_info', [
    _d($statement['from']),
    _d($statement['to']),
]) . '
</div>';

$pdf->ln(9);
$pdf->writeHTMLCell($dimensions['wk'] - ($dimensions['rm'] + $dimensions['lm']), '', '', $pdf->getY(), $summary_info, 0, 1, false, true, 'C', false);
$pdf->ln(9);

$tblhtml = '<table width="100%" cellspacing="0" cellpadding="5" border="0" style="font-size:14px;">
<thead>
 <tr height="10" bgcolor="#e8e8e8" style="color:#424242;">
     <th width="10%"><b>' . _l('date') . '</b></th>
     <th width="10%"><b>' . _l('Details') . '</b></th>
     <th align="center" width="10%"><b>' . _l('Products Value') . '</b></th>
     <th align="center" width="10%"><b>' . _l('Service Fee') . '</b></th>
     <th align="center" width="10%"><b>' . _l('Other Expenses') . '</b></th>
     <th align="center" width="10%"><b>' . _l('Total') . '</b></th>
     <th align="center" width="10%"><b>' . _l('Invoice Amount') . '</b></th>
     <th align="center" width="10%"><b>' . _l('RMB Received') . '</b></th>
     <th align="center" width="10%"><b>' . _l('Balance') . '</b></th>
     <th width="10%"><b>' . _l('Clients Admin') . '</b></th>
 </tr>
</thead>
<tbody>';
$count = 0;

if (isset($statement['statements']) && !empty($statement['statements'])) {
    foreach ($statement['statements'] as $row) {
        $tblhtml .= '<tr' . (++$count % 2 ? ' bgcolor="#f6f5f5"' : '') . '>
  <td width="10%">' . _d($row['start_date']) . '</td>
  <td width="10%">' . e($row['project_name']) . '</td>
  <td align="left" width="10%">' . app_format_money($row['total_goods_value'], $statement['currency'], true) . '</td>
  <td align="left" width="10%">' . app_format_money($row['service_fee'], $statement['currency'], true) . '</td>
  <td align="left" width="10%">' . app_format_money($row['others_expenses'], $statement['currency'], true) . '</td>
  <td align="left" width="10%">' . app_format_money($row['total'], $statement['currency'], true) . '</td>
  <td align="left" width="10%">' . app_format_money($row['invoice_amount'], $row['currency_name'], true) . '</td>
  <td align="left" width="10%">' . app_format_money($row['invoice_total'], $statement['currency'], true) . '</td>
  <td align="left" width="10%">' . app_format_money($row['balance'], $statement['currency'], true) . '</td>
  <td width="10%">' . e($row['admin_names']) . '</td>
            </tr>';
    }
}
$tblhtml .= '</tbody>
 </table>';

$pdf->writeHTML($tblhtml, true, false, false, false, '');
