<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once APPPATH . 'libraries/App_items_table_template.php';

/**
 * ALB Invoice items table - same structure as main invoice (Marks And No, Description, Quantity, Unit, Currency, Amount)
 * Uses get_items_table_data() like invoice_preview_html
 */
class Alb_items_table extends App_items_table_template
{
    public function __construct($transaction, $type, $for = 'html', $admin_preview = false)
    {
        $this->type          = strtolower($type);
        $this->admin_preview = $admin_preview;
        $this->for           = $for;
        $this->set_transaction($transaction);
        $this->set_items($transaction->items ?? []);
        parent::__construct();
        $this->custom_fields_for_table = [];
        $this->tax_per_item  = false;
        $this->set_headings('invoice');
    }

    /**
     * Build items table rows (Marks And No, Description, Quantity, Unit, Currency, Amount)
     */
    public function items()
    {
        $client_name = '';
        if (!empty($this->transaction->client)) {
            $client_name = $this->transaction->client->company ?? ($this->transaction->deleted_customer_name ?? '');
        }
        $client_name = $client_name ?: '-';
        $currency_display = $this->transaction->currency_name ?? '';
        $sum_qty   = 0;
        $sum_amount = 0;
        $html      = '';
        $count     = count($this->items);
        $format_currency = $this->transaction->currency_name ?? '';
        if (isset($this->ci->currencies_model)) {
            $curr_id = $this->transaction->invoice_currency ?? $this->transaction->currency ?? 0;
            if ($curr_id) {
                $curr = $this->ci->currencies_model->get($curr_id);
                if ($curr) {
                    $format_currency = $curr;
                }
            }
            if (empty($format_currency)) {
                $format_currency = $this->ci->currencies_model->get_base_currency();
            }
        }

        $border = 'border: 1px solid black;';
        foreach ($this->items as $i => $item) {
            $qty    = (float)($item['qty'] ?? 1);
            $rate   = (float)($item['rate'] ?? 0);
            $amount = $rate;
            $sum_qty += $qty;
            $sum_amount += $amount;
            $description = $item['description'] ?? ($item['long_description'] ?? '');
            $unit = trim($item['unit'] ?? '');
            $unit = $unit === '' ? '-' : $unit;

            $tr_attr = $this->admin_preview ? ' class="dragger item_no"' : '';
            $html .= '<tr style="' . $border . '"' . $tr_attr . '>';
            if ($i === 0 && $count > 0) {
                $html .= '<td style="' . $border . '; text-align:center;" rowspan="' . $count . '" width="24%">' . e($client_name) . '</td>';
            } elseif ($i > 0) {
                // Rowspan covers first N-1 rows, no extra td
            }
            $html .= '<td style="' . $border . '; text-align:left;" class="description" width="26%">' . e($description) . '</td>';
            $html .= '<td style="' . $border . '; text-align:center;" width="15%">' . e($qty) . '</td>';
            $html .= '<td style="' . $border . '; text-align:center;" width="10%">' . e($unit) . '</td>';
            $html .= '<td style="' . $border . '; text-align:center;" width="10%">' . e($currency_display) . '</td>';
            $html .= '<td style="' . $border . '; text-align:right;" width="15%">' . app_format_money($amount, $format_currency) . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr class="bold" style="' . $border . '">';
        $html .= '<td style="' . $border . '; text-align:center;" width="24%"></td>';
        $html .= '<td style="' . $border . '; text-align:left;" class="description" width="26%">' . _l('invoice_total') . '</td>';
        $html .= '<td style="' . $border . '; text-align:center;" width="15%">' . $sum_qty . '</td>';
        $html .= '<td style="' . $border . '; text-align:center;" width="10%"></td>';
        $html .= '<td style="' . $border . '; text-align:center;" width="10%"></td>';
        $html .= '<td style="' . $border . '; text-align:right;" width="15%">' . app_format_money($sum_amount, $format_currency) . '</td>';
        $html .= '</tr>';

        return $html;
    }

    public function html_headings()
    {
        $border = 'border: 1px solid black;';
        $html = '<tr>';
        $html .= '<th style="' . $border . '; text-align:center;" width="24%">' . _l('marks_and_no') . '</th>';
        $html .= '<th style="' . $border . '; text-align:left;" class="description" width="26%">' . _l('invoice_table_item_description') . '</th>';
        $html .= '<th style="' . $border . '; text-align:center;" width="15%">' . _l('alb_invoice_table_quantity') . '</th>';
        $html .= '<th style="' . $border . '; text-align:center;" width="10%">' . _l('unit') . '</th>';
        $html .= '<th style="' . $border . '; text-align:center;" width="10%">' . _l('currency') . '</th>';
        $html .= '<th style="' . $border . '; text-align:right;" width="15%">' . _l('invoice_table_amount_heading') . '</th>';
        $html .= '</tr>';
        return $html;
    }

    public function pdf_headings()
    {
        $html = '<tr style="background-color:#e8e8e8;">';
        $html .= '<th style="text-align:center;">' . _l('marks_and_no') . '</th>';
        $html .= '<th style="text-align:left;">' . _l('invoice_table_item_description') . '</th>';
        $html .= '<th style="text-align:center;">' . _l('alb_invoice_table_quantity') . '</th>';
        $html .= '<th style="text-align:center;">' . _l('unit') . '</th>';
        $html .= '<th style="text-align:center;">' . _l('currency') . '</th>';
        $html .= '<th style="text-align:right;">' . _l('invoice_table_amount_heading') . '</th>';
        $html .= '</tr>';
        return $html;
    }
}
