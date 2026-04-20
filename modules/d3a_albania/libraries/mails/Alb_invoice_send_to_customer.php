<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ALB Invoice send to customer email - uses public link alb_invoice/{id}/{hash}
 */
class Alb_invoice_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $invoice;

    protected $contact;

    public $slug = 'invoice-send-to-client';

    public $rel_type = 'alb_invoice';

    public function __construct($invoice, $contact, $cc = '')
    {
        parent::__construct();

        $this->invoice = $invoice;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        $this->ci->load->model('currencies_model');
        $invoice_link   = site_url('alb_invoice/' . $this->invoice->id . '/' . $this->invoice->hash);
        $invoice_number = function_exists('format_alb_invoice_number_custom') ? format_alb_invoice_number_custom($this->invoice) : ('#'.$this->invoice->id);
        $base_currency  = $this->ci->currencies_model->get_base_currency();
        $currency_id    = isset($this->invoice->invoice_currency) ? $this->invoice->invoice_currency : (isset($this->invoice->currency) ? $this->invoice->currency : 0);
        if ($currency_id) {
            $base_currency = $this->ci->currencies_model->get($currency_id) ?: $base_currency;
        }
        $invoice_total  = isset($this->invoice->invoice_amount) ? (float) $this->invoice->invoice_amount : (float) ($this->invoice->total ?? 0);

        $alb_merge = [
            '{invoice_link}'    => $invoice_link,
            '{invoice_number}'  => $invoice_number,
            '{invoice_duedate}' => $this->invoice->duedate ? _d($this->invoice->duedate) : '',
            '{invoice_date}'   => _d($this->invoice->date),
            '{invoice_total}'   => app_format_money($invoice_total, $base_currency),
            '{invoice_status}' => format_invoice_status($this->invoice->status, '', false),
        ];
        if (isset($this->invoice->total_left_to_pay)) {
            $alb_merge['{invoice_amount_due}'] = app_format_money($this->invoice->total_left_to_pay, $base_currency);
        } else {
            $alb_merge['{invoice_amount_due}'] = app_format_money($invoice_total, $base_currency);
        }

        $this->to($this->contact->email)
            ->set_rel_id($this->invoice->id)
            ->set_merge_fields('client_merge_fields', $this->invoice->clientid, $this->contact->id)
            ->set_merge_fields($alb_merge);
    }
}
