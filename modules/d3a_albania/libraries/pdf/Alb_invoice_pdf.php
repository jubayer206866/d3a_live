<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once APPPATH . 'libraries/pdf/App_pdf.php';

class Alb_invoice_pdf extends App_pdf
{
    protected $invoice;

    private $invoice_number;

    public function __construct($invoice, $tag = '')
    {
        $invoice = hooks()->apply_filters('alb_invoice_html_pdf_data', $invoice);
        parent::__construct();
        $this->invoice = $invoice;
        $this->invoice_number = format_alb_invoice_number_custom($this->invoice);
        $this->SetTitle(_l('alb_invoice'));
    }

    public function prepare()
    {
        if (!isset($this->ci->d3a_albania_model)) {
            $this->ci->load->model('d3a_albania_model');
        }

        $base_currency = $this->ci->d3a_albania_model->get_invoice_currency($this->invoice);
        $invoice_detail = $this->ci->d3a_albania_model->get_alb_invoice_items_for_preview(
            $this->invoice->id,
            $base_currency
        );

        $client_name = '';
        if (!empty($this->invoice->client)) {
            $client_name = $this->invoice->client->company ?? ($this->invoice->deleted_customer_name ?? '');
        }

        $this->set_view_vars([
            'invoice_number'   => $this->invoice_number,
            'invoice'         => $this->invoice,
            'invoice_detail'  => $invoice_detail,
            'base_currency'   => $base_currency,
            'client_name'     => $client_name,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'alb_invoice';
    }

    protected function file_path()
    {
        return module_dir_path(D3A_ALBANIA_MODULE_NAME, 'views/invoices/alb_invoicepdf.php');
    }

    public function get_format_array()
    {
        $format = get_option('pdf_format_alb_invoice');
        return empty($format) ? get_pdf_format('pdf_format_invoice') : get_pdf_format('pdf_format_alb_invoice');
    }
}
