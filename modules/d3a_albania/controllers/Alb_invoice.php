<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Public ALB invoice view controller (invoice/36/hash style URL).
 * Extends ClientsController for public/client-facing layout.
 */
class Alb_invoice extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('d3a_albania/d3a_albania_model');
        $this->load->helper('d3a_albania/d3a_albania');
    }

    /**
     * Public ALB invoice view
     *
     * @param string $id   ALB invoice ID
     * @param string $hash Invoice hash for access validation
     */
    public function index($id = '', $hash = '')
    {
        check_alb_invoice_restrictions($id, $hash);
        $invoice = $this->d3a_albania_model->get_invoice($id);

        $invoice = hooks()->apply_filters('before_client_view_alb_invoice', $invoice);

        if (!is_client_logged_in()) {
            load_client_language($invoice->clientid);
        }

        // Handle PDF download (POST from view form)
        if ($this->input->post('invoicepdf')) {
            try {
                $pdf = app_pdf('alb_invoice', module_dir_path(D3A_ALBANIA_MODULE_NAME, 'libraries/pdf/Alb_invoice_pdf'), $invoice);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }
            $invoice_number = format_alb_invoice_number_custom($invoice);
            $companyname    = get_option('alb_invoice_company_name') ?: get_option('invoice_company_name');
            if ($companyname != '') {
                $invoice_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }
            $pdf->Output(mb_strtoupper(slug_it($invoice_number), 'UTF-8') . '.pdf', 'D');
            die();
        }

        $this->load->model('currencies_model');
        $currency_id = isset($invoice->invoice_currency) ? $invoice->invoice_currency : (isset($invoice->currency) ? $invoice->currency : 0);
        $base_currency = $this->currencies_model->get_base_currency();
        if ($currency_id != 0 && $currency_id != '') {
            $base_currency = $this->currencies_model->get($currency_id);
        }
        if (!$base_currency) {
            $base_currency = $this->currencies_model->get_base_currency();
        }

        $data['invoice_detail'] = $this->d3a_albania_model->get_alb_invoice_items_for_preview($id, $base_currency);
        $data['base_currency']  = $base_currency;
        $data['title']         = format_alb_invoice_number_custom($invoice);
        $this->disableNavigation();
        $this->disableSubMenu();
        $data['hash']      = $hash;
        $data['invoice']   = hooks()->apply_filters('alb_invoice_html_pdf_data', $invoice);
        $data['bodyclass'] = 'viewinvoice';
        $this->data($data);
        $this->view('invoices/alb_invoicehtml');
        hooks()->do_action('alb_invoice_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }
}
