<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/App_items_table_template.php');

class App_items_table extends App_items_table_template
{
    public function __construct($transaction, $type, $for = 'html', $admin_preview = false)
    {
        // Required
        $this->type = strtolower($type);
        $this->admin_preview = $admin_preview;
        $this->for = $for;

        $this->set_transaction($transaction);
        $this->set_items($transaction->items);

        parent::__construct();
    }

    /**
     * Builds the actual table items rows preview
     * @return string
     */
    public function items()
    {

        $client_name = count($this->items) ? get_client_name_from_invoice($this->items[0]['rel_id']) : '';

        $html = '';
        $i = 1;
        $count = count(value: $this->items);
        $total = 0;
        
     
        foreach ($this->items as $item) {
            $itemHTML = '';
            $itemHTML .= '<tr style="border: 1px solid black;"' . $this->tr_attributes($item) . '>';
            if ($i == 1) {
                $itemHTML .= '<td style="border: 1px solid black;" align="center" rowspan="' . $count . '"width="24%">' . $client_name . '</td>';
            }

            $itemHTML .= '<td style="border: 1px solid black;" class="description" align="center;" width="26%">' . $this->period_merge_field($item['description']);
            $itemHTML .= '</td>';

            $itemHTML .= '<td style="border: 1px solid black; "align="center" width="15%">' . e(floatVal($item['qty'])) . '</td>';
            $itemHTML .= '<td style="border: 1px solid black; "align="center" width="10%">' . $item['unit'] . '</td>';
            $itemHTML .= '<td style="border: 1px solid black; "align="center" width="10%">' . $this->transaction->currency_name . '</td>';
            $itemHTML .= '<td style="border: 1px solid black; "align="right" width="15%">' . floatVal($item['rate']) . '</td>';
            $qty+= $item['qty'];
            $total += $item['rate'];
            $itemHTML .= '</tr>';
            $html .= $itemHTML;
            $i++;
        }

        $itemHTML = '';
        $itemHTML .= '<tr style="border: 1px solid black;"' . $this->tr_attributes($item) . '>';
        $itemHTML .= '<td style="border: 1px solid black;" align="center" width="24%"></td>';

        $itemHTML .= '<td style="border: 1px solid black;" class="description" align="center;" width="26%">' . _l('total');
        $itemHTML .= '</td>';

        $itemHTML .= '<td style="border: 1px solid black; "align="center" width="15%">'  . floatVal($qty) .'</td>';
        $itemHTML .= '<td style="border: 1px solid black; "align="center" width="10%"></td>';
        $itemHTML .= '<td style="border: 1px solid black; "align="center" width="10%"></td>';
        $itemHTML .= '<td style="border: 1px solid black; "align="right" width="15%">' . floatVal($total) . '</td>';

        $total += $item['rate'];
        $itemHTML .= '</tr>';
        $html .= $itemHTML;
        $i++;


        return $html;
    }

    /**
     * Html headings preview
     * @return string
     */
    public function html_headings()
    {
        $html = '<tr>';
        $html .= '<th style="border: 1px solid black;" width="24%" align="center">' . _l('marks_and_no') . '</th>';
        $html .= '<th  style="border: 1px solid black;" class="description" width="26%" align="center">' . _l('item_description_placeholder') . '</th>';
        $html .= '<th style="border: 1px solid black;"  width="15%" align="center">' . _l('unit') . '</th>';
        $html .= '<th style="border: 1px solid black;"  width="10%" align="center">' . _l('item_quantity_placeholder') . '</th>';
        $html .= '<th style="border: 1px solid black;" width="10%"  align="center">' . _l('currency') . '</th>';
        $html .= '<th style="border: 1px solid black;" width="15%"  align="right">' . $this->amount_heading() . '</th>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * PDF headings preview
     * @return string
     */
    public function pdf_headings()
    {

        $tblhtml = '<tr  bgcolor="' . get_option('pdf_table_heading_color') . '" style="color:' . get_option('pdf_table_heading_text_color') . ';">';
        $tblhtml .= '<th width="24%" align="center" height="30">' . _l('marks_and_no') . '</th>';
        $tblhtml .= '<th width="26%" align="center" height="30">' . _l('item_description_placeholder') . '</th>';
        $tblhtml .= '<th width="15%" align="center" height="30">' . _l('item_quantity_placeholder') . '</th>';
        $tblhtml .= '<th width="10%" align="center" height="30">' . _l('unit') . '</th>';
        $tblhtml .= '<th width="10%" align="center" height="30">' . _l('currency') . '</th>';
        $tblhtml .= '<th width="15%" align="right" height="30">' . $this->amount_heading() . '</th>';

        $tblhtml .= '</tr>';

        return $tblhtml;
    }

    /**
     * Check for period merge field for recurring invoices
     *
     * @return string
     */
    protected function period_merge_field($text)
    {
        if ($this->type != 'invoice') {
            return $text;
        }

        // Is subscription invoice
        if (!property_exists($this->transaction, 'recurring_type')) {
            return $text;
        }

        $startDate = $this->transaction->date;
        $originalInvoice = $this->transaction->is_recurring_from ?
            $this->ci->invoices_model->get($this->transaction->is_recurring_from) :
            $this->transaction;

        if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $startDate)) {
            $startDate = to_sql_date($startDate);
        }

        if ($originalInvoice->custom_recurring == 0) {
            $originalInvoice->recurring_type = 'month';
        }

        $nextDate = date('Y-m-d', strtotime(
            '+' . $originalInvoice->recurring . ' ' . strtoupper($originalInvoice->recurring_type),
            strtotime($startDate)
        ));

        return str_ireplace('{period}', _d($startDate) . ' - ' . _d(date('Y-m-d', strtotime('-1 day', strtotime($nextDate)))), $text);
    }

    protected function get_description_item_width()
    {
        $item_width = hooks()->apply_filters('item_description_td_width', 38);

        // If show item taxes is disabled in PDF we should increase the item width table heading
        return $this->show_tax_per_item() == 0 ? $item_width + 15 : $item_width;
    }

    protected function get_regular_items_width($adjustment)
    {
        $descriptionItemWidth = $this->get_description_item_width();
        $customFieldsItems = $this->get_custom_fields_for_table();
        // Calculate headings width, in case there are custom fields for items
        $totalheadings = $this->show_tax_per_item() == 1 ? 4 : 3;
        $totalheadings += count($customFieldsItems);

        return (100 - ($descriptionItemWidth + $adjustment)) / $totalheadings;
    }
}