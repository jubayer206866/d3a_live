<?php

defined('BASEPATH') or exit('No direct script access allowed');

class D3a_albania extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('d3a_albania_model');
        $this->load->helper('d3a_albania/d3a_albania');
    }

    /** D3A Albania module staff capability (feature d3a_albania). */
    protected function d3a_require($capability)
    {
        if (! d3a_albania_staff_can($capability)) {
            access_denied('d3a_albania');
        }
    }

    protected function d3a_require_ajax($capability)
    {
        if (! d3a_albania_staff_can($capability)) {
            ajax_access_denied();
        }
    }

    // ALB Invoices
    public function invoices($id = '')
    {
        $this->d3a_require('view_al_invoice');
        $data['currencies'] = $this->d3a_albania_model->get_currencies();
        $this->load->model('clients_model');
        $data['customers'] = $this->clients_model->get();
        $data['title'] = 'AL Invoice';
        $data['invoiceid'] = $id;
        $this->load->view('d3a_albania/invoices/manage', $data);
    }

    /**
     * Get ALB invoice preview HTML for AJAX load (split view)
     */
    public function get_alb_invoice_data_ajax($id)
    {
        if (! d3a_albania_staff_can('view_al_invoice')) {
            echo _l('access_denied');
            die;
        }
        if (!$id) {
            die(_l('invoice_not_found'));
        }

        $data['alb_invoice'] = $this->d3a_albania_model->get_invoice($id);
        if (!$data['alb_invoice'] || !$this->user_can_view_alb_invoice($id)) {
            echo _l('invoice_not_found');
            die;
        }

        $this->load->model('staff_model');
        $this->load->model('currencies_model');
        $this->load->model('payment_modes_model');

        $data['payment_modes'] = $this->payment_modes_model->get('', ['expenses_only !=' => 1]);

        $currency_id = isset($data['alb_invoice']->invoice_currency) ? $data['alb_invoice']->invoice_currency : (isset($data['alb_invoice']->currency) ? $data['alb_invoice']->currency : 0);
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        if ($currency_id != 0 && $currency_id != '') {
            $data['base_currency'] = $this->currencies_model->get($currency_id);
        }
        if (!$data['base_currency']) {
            $data['base_currency'] = $this->currencies_model->get_base_currency();
        }

        $data['invoice_detail'] = $this->d3a_albania_model->get_alb_invoice_items_for_preview($id, $data['base_currency']);
        $data['payment'] = $this->d3a_albania_model->get_invoice_payments($id);
        $data['alb_invoice_attachments'] = $this->d3a_albania_model->get_attachments($id);
        $data['members'] = $this->staff_model->get('', ['active' => 1]);
        $data['title'] = format_alb_invoice_number_custom($id);
        $data['debits_available'] = 0;
        $data['applied_debits'] = [];

        $data['activity'] = $this->d3a_albania_model->get_invoice_activity($id);
        $data['totalNotes'] = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'alb_invoice']);

        // Mail data for send-to-client modal (embedded in preview like main invoice)
        if (!empty($data['alb_invoice']->clientid)) {
            $invoice = $data['alb_invoice'];
            $template_name = 'invoice_send_to_customer';
            if (isset($invoice->sent) && $invoice->sent == 1 && !empty($invoice->datesend)) {
                $template_name = 'invoice_send_to_customer_already_sent';
            }
            $mail_data = prepare_mail_preview_data($template_name, $invoice->clientid);
            $data = array_merge($data, $mail_data);
            $contact_id = get_primary_contact_user_id($invoice->clientid);
            if (!$contact_id) {
                $contacts_with_email = $this->clients_model->get_contacts($invoice->clientid, ['active' => 1, 'invoice_emails' => 1]);
                $contact_id = !empty($contacts_with_email) ? $contacts_with_email[0]['id'] : 0;
            }
            $merge_fields = $this->_get_alb_invoice_email_merge_fields($invoice);
            if ($contact_id) {
                $client_merge = $this->app_merge_fields->format_feature('client_merge_fields', $invoice->clientid, $contact_id);
                $merge_fields = array_merge($merge_fields, $client_merge);
            }
            $data['template'] = parse_email_template_merge_fields($data['template'], $merge_fields);
        }

        $this->load->view('d3a_albania/invoices/alb_invoice_preview_template', $data);
    }

    /**
     * Get ALB invoice send-to-email modal HTML (fallback for AJAX load - kept for compatibility)
     */
    public function get_alb_invoice_send_modal($id)
    {
        if (! $this->user_can_view_alb_invoice($id)) {
            echo '';
            return;
        }
        $invoice = $this->d3a_albania_model->get_invoice($id);
        if (!$invoice || empty($invoice->clientid)) {
            echo '';
            return;
        }
        $data['alb_invoice'] = $invoice;

        $template_name = 'invoice_send_to_customer';
        if (isset($invoice->sent) && $invoice->sent == 1 && !empty($invoice->datesend)) {
            $template_name = 'invoice_send_to_customer_already_sent';
        }
        $mail_data = prepare_mail_preview_data($template_name, $invoice->clientid);
        $data = array_merge($data, $mail_data);

        $contact_id = get_primary_contact_user_id($invoice->clientid);
        if (!$contact_id) {
            $contacts_with_email = $this->clients_model->get_contacts($invoice->clientid, ['active' => 1, 'invoice_emails' => 1]);
            $contact_id = !empty($contacts_with_email) ? $contacts_with_email[0]['id'] : 0;
        }

        $merge_fields = $this->_get_alb_invoice_email_merge_fields($invoice);
        if ($contact_id) {
            $client_merge = $this->app_merge_fields->format_feature('client_merge_fields', $invoice->clientid, $contact_id);
            $merge_fields = array_merge($merge_fields, $client_merge);
        }
        $data['template'] = parse_email_template_merge_fields($data['template'], $merge_fields);

        $this->load->view('d3a_albania/invoices/alb_invoice_send_to_client', $data);
    }

    /**
     * Build merge fields for ALB invoice email (manual, no mail class)
     */
    protected function _get_alb_invoice_email_merge_fields($invoice)
    {
        $this->load->model('currencies_model');
        $invoice_link  = site_url('alb_invoice/' . $invoice->id . '/' . ($invoice->hash ?? ''));
        $invoice_number = function_exists('format_alb_invoice_number_custom') ? format_alb_invoice_number_custom($invoice) : ('#' . $invoice->id);
        $base_currency = $this->currencies_model->get_base_currency();
        $currency_id   = isset($invoice->invoice_currency) ? $invoice->invoice_currency : (isset($invoice->currency) ? $invoice->currency : 0);
        if ($currency_id) {
            $curr = $this->currencies_model->get($currency_id);
            if ($curr) {
                $base_currency = $curr;
            }
        }
        $invoice_total = isset($invoice->invoice_amount) ? (float) $invoice->invoice_amount : (float) ($invoice->total ?? 0);
        $amount_due   = isset($invoice->total_left_to_pay) ? (float) $invoice->total_left_to_pay : $invoice_total;

        return [
            '{invoice_link}'       => $invoice_link,
            '{invoice_number}'     => $invoice_number,
            '{invoice_duedate}'    => !empty($invoice->duedate) ? _d($invoice->duedate) : '',
            '{invoice_date}'       => _d($invoice->date),
            '{invoice_total}'      => app_format_money($invoice_total, $base_currency),
            '{invoice_status}'     => format_invoice_status($invoice->status, '', false),
            '{invoice_amount_due}' => app_format_money($amount_due, $base_currency),
        ];
    }

    /**
     * Send ALB invoice to client email (from preview send button)
     */
    public function send_alb_invoice_to_email($id)
    {
        $this->d3a_require('edit_al_invoice');
        if (! $this->user_can_view_alb_invoice($id)) {
            access_denied('d3a_albania');
        }
        try {
            $success = $this->d3a_albania_model->send_invoice_to_client($id, '', true, '', false);
        } catch (Exception $e) {
            $success = false;
        }
        if ($success) {
            set_alert('success', _l('invoice_sent_to_client_success'));
        } else {
            set_alert('danger', _l('invoice_sent_to_client_fail'));
        }
        redirect(admin_url('d3a_albania/invoices/' . $id));
    }

    /**
     * Get notes for ALB invoice (for notes tab in preview)
     */
    public function get_notes($id)
    {
        if (! d3a_albania_staff_can('view_al_invoice') || ! $this->user_can_view_alb_invoice($id)) {
            return;
        }
        $data['notes'] = $this->misc_model->get_notes($id, 'alb_invoice');
        $this->load->view('admin/includes/sales_notes_template', $data);
    }

    public function invoice($id = '')
    {
        if ($id == '') {
            $this->d3a_require('create_al_invoice');
        } else {
            $this->d3a_require('edit_al_invoice');
        }
        if ($this->input->post()) {
            $invoice_data = $this->input->post();
            if ($id == '') {
                $id = $this->d3a_albania_model->add_invoice($invoice_data);
                if ($id) {
                    if (isset($invoice_data['save_and_send'])) {
                        set_alert('success', _l('invoice_sent_to_client_success'));
                    } else {
                        set_alert('success', _l('added_successfully', _l('invoice')));
                    }
                    $redUrl = admin_url('d3a_albania/invoices/' . $id);

                    if (isset($invoice_data['save_and_record_payment'])) {
                        $this->session->set_userdata('record_payment', true);
                    } elseif (isset($invoice_data['save_and_send_later'])) {
                        $this->session->set_userdata('send_later', true);
                    }

                    redirect($redUrl);
                }
            } else {
                if (hooks()->apply_filters('validate_invoice_number', true) && isset($invoice_data['number'])) {
                    $number = trim(ltrim($invoice_data['number'], '0'));
                    if (
                        total_rows(db_prefix() . 'alb_invoices', [
                            'YEAR(date)' => (int) date('Y', strtotime(to_sql_date($invoice_data['date']))),
                            'number' => $number,
                            'id !=' => $id,
                        ])
                    ) {
                        set_alert('warning', _l('invoice_number_exists'));
                        redirect(admin_url('d3a_albania/invoice/' . $id));
                    }
                }

                $success = $this->d3a_albania_model->update_invoice($invoice_data, $id);
                if ($success) {
                    if (isset($invoice_data['save_and_send'])) {
                        set_alert('success', _l('invoice_sent_to_client_success'));
                    } else {
                        set_alert('success', _l('updated_successfully', _l('invoice')));
                    }
                }

                redirect(admin_url('d3a_albania/invoices/' . $id));
            }
        }
        if ($id == '') {
            $title = 'Create New AL Invoice';
            $data['billable_tasks'] = [];
            $data['invoices_to_merge'] = [];
            $data['expenses_to_bill'] = [];
        } else {
            $invoice = $this->d3a_albania_model->get_invoice($id);

            if (!$invoice) {
                blank_page(_l('invoice_not_found'));
            }

            if (!isset($invoice->number) && isset($invoice->invoice_number)) {
                $invoice->number = $invoice->invoice_number;
            }
            
            if (!isset($invoice->prefix)) {
                $invoice->prefix = '';
            }
            if (!isset($invoice->number_format)) {
                $invoice->number_format = 1;
            }
            if (!isset($invoice->date)) {
                $invoice->date = date('Y-m-d');
            }
            if (!isset($invoice->status)) {
                $invoice->status = 1;
            }

            $data['invoice'] = $invoice;
            $data['edit'] = true;
            $data['billable_tasks'] = [];
            $data['invoices_to_merge'] = [];
            $data['expenses_to_bill'] = [];

            $title = _l('edit', _l('AL Invoice')) . ' - ' . format_alb_invoice_number_custom($invoice);
        }

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        $data['payment_modes'] = $this->d3a_albania_model->get_payment_modes('', [
            'expenses_only !=' => 1,
        ]);

        $data['taxes'] = $this->d3a_albania_model->get_taxes();

        $data['ajaxItems'] = false;
        $data['items'] = $this->d3a_albania_model->get_items_grouped();
        $data['items_groups'] = $this->d3a_albania_model->get_items_groups();

        $data['currencies'] = $this->d3a_albania_model->get_currencies();
        $data['base_currency'] = $this->d3a_albania_model->get_base_currency();

        $this->load->model('staff_model');
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);
        $data['title'] = $title;
        $data['bodyclass'] = 'invoice';
        $this->load->view('d3a_albania/invoices/invoice', $data);
    }

    public function alb_invoices($clientid = '')
    {
        if ($clientid != '') {
            check_permission('customers', $clientid);
        } else {
            $this->d3a_require_ajax('view_al_invoice');
        }

        $data = [];
        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }
        $data['clientid'] = $clientid;
        
        $tableData = $this->app->get_table_data(
            module_views_path('d3a_albania', 'admin/tables/alb_invoices'),
            [
                'data' => $data,
            ]
        );
        echo json_encode($tableData);
        die; 
    }

    // ALB Payments
    public function payments()
    {
        $this->d3a_require('view_al_payment');
        $data['title'] = 'AL Payments';
        $this->load->view('d3a_albania/payments/manage', $data);
    }

    public function payment($id = '')
    {
        $this->d3a_require('view_al_payment');
        if ($this->input->post()) {
            $this->d3a_require('add_al_payment');
            if ($id == '') {
                $id = $this->d3a_albania_model->add_payment($this->input->post());
                if ($id) {
                    set_alert('success', 'Payment added successfully');
                    redirect(admin_url('d3a_albania/payment/' . $id));
                }
            } else {
                $success = $this->d3a_albania_model->update_payment($this->input->post(), $id);
                if ($success) {
                    set_alert('success', 'Payment updated successfully');
                }
                redirect(admin_url('d3a_albania/payment/' . $id));
            }
        }

        if ($id == '') {
            $data['title'] = 'New AL Payments';
            $data['payment'] = null;
            $data['invoices'] = $this->d3a_albania_model->get_unpaid_invoices();
            $data['preselected_invoice_id'] = $this->input->get('invoiceid');
        } else {
            $data['title'] = 'Edit AL Payments';
            $data['payment'] = $this->d3a_albania_model->get_payment($id);
            if (!$data['payment']) {
                set_alert('danger', 'Payment not found');
                redirect(admin_url('d3a_albania/payments'));
            }
            $data['invoices'] = $this->d3a_albania_model->get_all_invoices();
            $data['preselected_invoice_id'] = null;
        }

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get();
        $this->load->view('d3a_albania/payments/payment', $data);
    }

    public function payments_table()
    {
        $this->d3a_require_ajax('view_al_payment');
        $tableData = $this->app->get_table_data(
            module_views_path('d3a_albania', 'admin/tables/alb_payments')
        );
        echo json_encode($tableData);
        die;
    }

    /**
     * Delete ALB payment (from payments list page).
     *
     * @param int $id Payment ID
     */
    public function delete_payment($id)
    {
        $this->d3a_require('delete_al_payment');
        $payment = $this->d3a_albania_model->get_payment($id);
        $response = $this->d3a_albania_model->delete_payment($id);
        if ($response) {
            set_alert('success', _l('deleted', _l('payment')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('payment')));
        }
        if ($payment && isset($payment->invoiceid)) {
            redirect(admin_url('d3a_albania/alb_invoice/' . $payment->invoiceid));
        } else {
            redirect(admin_url('d3a_albania/payments'));
        }
    }

    // ALB Expenses
    public function expenses()
    {
        $this->d3a_require('view_al_expense');
        $data['title'] = 'AL Expenses';
        $this->load->model('expenses_model');
        $data['categories'] = $this->expenses_model->get_category();
        $this->load->model('clients_model');
        $data['customers'] = $this->clients_model->get();

        $this->load->view('d3a_albania/expenses/manage', $data);
    }

    public function expense($id = '')
    {
        if ($this->input->post()) {
            if ($id == '') {
                $this->d3a_require('create_al_expense');
                $id = $this->d3a_albania_model->add_expense($this->input->post());
                if ($id) {
                    set_alert('success', 'Expense added successfully');
                    echo json_encode([
                        'expenseid' => $id,
                        'url'       => admin_url('d3a_albania/expenses'),
                    ]);
                    die;
                }
            } else {
                $this->d3a_require('edit_al_expense');
                $success = $this->d3a_albania_model->update_expense($this->input->post(), $id);
                if ($success) {
                    set_alert('success', 'Expense updated successfully');
                }
                echo json_encode([
                    'expenseid' => $id,
                    'url'       => admin_url('d3a_albania/expenses'),
                ]);
                die;
            }
        }

        if ($id == '') {
            $this->d3a_require('create_al_expense');
            $data['title'] = 'Add New AL Expenses';
            $data['expense'] = null;
        } else {
            $this->d3a_require('edit_al_expense');
            $data['title'] = 'Edit ALB AL Expenses';
            $data['expense'] = $this->d3a_albania_model->get_expense($id);
            if (!$data['expense']) {
                set_alert('danger', 'Expense not found');
                redirect(admin_url('d3a_albania/expenses'));
            }
        }

        $this->load->model('expenses_model');
        $this->load->model('clients_model');
        $this->load->model('payment_modes_model');
        $this->load->model('staff_model');
        $this->load->model('currencies_model');
        $this->load->model('taxes_model');
        
        $data['categories'] = $this->expenses_model->get_category();
        $data['clients'] = $this->clients_model->get();
        $data['payment_modes'] = $this->payment_modes_model->get();
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);
        $data['currencies'] = $this->currencies_model->get();
        $data['taxes'] = $this->taxes_model->get();
        
        // Group categories by type
        $data['categories_by_type'] = [
            'operational' => [],
            'customer' => []
        ];
        foreach ($data['categories'] as $category) {
            if (isset($category['category_type'])) {
                $type = $category['category_type'];
                if ($type === 'operational' || $type === 'customer') {
                    $data['categories_by_type'][$type][] = $category;
                }
            }
        }
        
        $data['expenseid'] = $id;
        $this->load->view('d3a_albania/expenses/expense', $data);
    }

    public function expenses_table()
    {
        $this->d3a_require_ajax('view_al_expense');

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [], true);

        $this->app->get_table_data(module_views_path('d3a_albania', 'admin/tables/alb_expenses'), [
            'data' => $data,
        ]);
    }


    public function delete_expense($id)
    {
        $this->d3a_require('delete_al_expense');

        if (!$id) {
            redirect(admin_url('d3a_albania/expenses'));
        }

        $response = $this->d3a_albania_model->delete_expense($id);

        if ($response) {
            set_alert('success', _l('deleted', _l('expense')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('expense')));
        }

        redirect(admin_url('d3a_albania/expenses'));
    }

    // ALB PO Invoices
    public function po_invoices()
    {
        $this->d3a_require('view_al_purchase_invoice');
        $data['title'] = 'AL Purchase Invoice ';
        $data['currencies'] = $this->d3a_albania_model->get_currencies();
        // Load data for filters
        if ($this->app_modules->is_active('purchase')) {
           $this->load->model('d3a_albania_model');
            $data['vendors'] = $this->d3a_albania_model->get_vendor();
        } else {
            $data['vendors'] = [];
        }
        
        $this->load->view('d3a_albania/po_invoices/manage', $data);
    }
    
    public function list_albania_invoices($id = '')
    {
        $this->d3a_require('view_al_invoice');

        close_setup_menu();

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [], true);
        $data['invoiceid'] = $id;
        $data['title'] = 'AL Invoice';
        $data['invoices_years'] = $this->d3a_albania_model->get_invoices_years();
        $data['invoices_sale_agents'] = $this->d3a_albania_model->get_sale_agents();
        $data['invoices_statuses'] = $this->d3a_albania_model->get_statuses();
        $data['invoices_table'] = App_table::find('invoices');

        $this->load->view('admin/invoices/manage', $data);
    }

    public function po_invoice($id = '')
    {
        if ($id == '') {
            $this->d3a_require('add_al_purchase_invoice');
            $data['title'] = _l('Add Purchase Invoice');
            // calculate next invoice number based on last record to avoid relying solely on option
            $lastNumber = $this->d3a_albania_model->get_last_po_invoice_number();
            $data['next_number'] = $lastNumber + 1;
        } else {
            $this->d3a_require('edit_al_purchase_invoice');
            $data['title'] = _l('Edit Purchase Invoice');
        }

        $data['contracts'] = $this->d3a_albania_model->get_contract();
        $data['taxes'] = $this->d3a_albania_model->get_taxes();
        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        if ($this->app_modules->is_active('d3a_albania')) {
            $data['vendors'] = $this->d3a_albania_model->get_vendor();
         } else {
            $data['vendors'] = [];
         }
        $pur_invoice_row_template = $this->d3a_albania_model->create_albania_invoice_row_template();
        $data['base_currency'] = $this->currencies_model->get_base_currency();


        // Edit mode
        if ($id != '') {
            $data['pur_orders'] = $this->d3a_albania_model->get_albania_approved();
            $data['pur_invoice'] = $this->d3a_albania_model->get_po_invoice($id);
            $data['pur_invoice_detail'] = $this->d3a_albania_model->get_pur_invoice_detail($id);

            if (!$data['pur_invoice']) {
                set_alert('danger', 'PO Invoice not found');
                redirect(admin_url('d3a_albania/po_invoices'));
            }

            $currency_rate = 1;
            if ($data['pur_invoice']->currency != 0 && $data['pur_invoice']->currency_rate != null) {
                $currency_rate = $data['pur_invoice']->currency_rate;
            }

            $to_currency = $data['base_currency']->name;
            if ($data['pur_invoice']->currency != 0 && $data['pur_invoice']->to_currency != null) {
                $to_currency = $data['pur_invoice']->to_currency;
            }

            // Build invoice items row template
            if (count($data['pur_invoice_detail']) > 0) {
                $index_order = 0;
                foreach ($data['pur_invoice_detail'] as $inv_detail) {
                    $index_order++;
                    $unit_name = pur_get_unit_name($inv_detail['unit_id']);
                    $taxname = $inv_detail['tax_name'];
                    $item_name = $inv_detail['item_name'];

                    if (strlen($item_name) == 0) {
                        $item_name = pur_get_item_variatiom($inv_detail['item_code']);
                    }

                    $pur_invoice_row_template .= $this->d3a_albania_model->create_albania_invoice_row_template(
                        'items[' . $index_order . ']',
                        $item_name,
                        $inv_detail['description'],
                        $inv_detail['quantity'],
                        $unit_name,
                        $inv_detail['unit_price'],
                        $taxname,
                        $inv_detail['item_code'],
                        $inv_detail['unit_id'],
                        $inv_detail['tax_rate'],
                        $inv_detail['total_money'],
                        $inv_detail['discount_percent'],
                        $inv_detail['discount_money'],
                        $inv_detail['total'],
                        $inv_detail['into_money'],
                        $inv_detail['tax'],
                        $inv_detail['tax_value'],
                        $inv_detail['id'],
                        true,
                        $currency_rate,
                        $to_currency
                    );
                }
            } else {
                // fallback for single row
                $item_name = $data['pur_invoice']->invoice_number;
                $description = $data['pur_invoice']->adminnote;
                $quantity = 1;
                $taxname = '';
                $tax_rate = 0;
                $tax = get_tax_rate_item($id);
                if ($tax && !is_array($tax)) {
                    $taxname = $tax->name;
                    $tax_rate = $tax->taxrate;
                }

                $total = $data['pur_invoice']->subtotal + $data['pur_invoice']->tax;
                $index = 0;

                $pur_invoice_row_template .= $this->d3a_albania_model->create_albania_invoice_row_template('newitems[' . $index . ']', $item_name, $description, $quantity, '', $data['pur_invoice']->subtotal, $taxname, null, null, $tax_rate, $data['pur_invoice']->total, 0, 0, $total, $data['pur_invoice']->subtotal, $data['pur_invoice']->tax_rate, $data['pur_invoice']->tax, '', true);
            }
        } else {
            $data['pur_orders'] = $this->d3a_albania_model->get_pur_order_approved_for_inv();
        }

        $data['pur_invoice_row_template'] = $pur_invoice_row_template;

        // Determine if items should load via AJAX
        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->d3a_albania_model->pur_get_grouped('can_be_purchased');
        } else {
            $data['items'] = [];
            $data['ajaxItems'] = true;
        }

        $this->load->view('d3a_albania/po_invoices/po_invoice', $data);
    }
    
    public function create_invoice_form($id = '')
    {
        if ($this->input->post()) {
            $data = $this->input->post();

            if (empty($data['id'])) {
                $this->d3a_require('add_al_purchase_invoice');

                unset($data['id']);

                $insert_id = $this->d3a_albania_model->add_po_invoice($data);

                if ($insert_id) {
                    set_alert('success', 'PO Invoice added successfully');
                } else {
                    set_alert('warning', 'Add PO Invoice failed');
                }
            } else {
                $this->d3a_require('edit_al_purchase_invoice');

                $id = $data['id'];
                unset($data['id']);

                $success = $this->d3a_albania_model->update_po_invoice($id, $data);

                if ($success !== false) {
                    set_alert('success', 'PO Invoice updated successfully');
                } else {
                    set_alert('warning', 'Update PO Invoice failed');
                }
            }

            redirect(admin_url('d3a_albania/po_invoices'));
        }

    }


    public function po_invoices_table()
    {
        $this->d3a_require_ajax('view_al_purchase_invoice');
        $this->load->view('d3a_albania/admin/tables/alb_po_invoices');
    }

    // ALB PO Payments
    public function po_payments()
    {
        $this->d3a_require('view_al_purchase_payment');
        $data['title'] = 'AL Purchase Payments';
        $this->load->view('d3a_albania/po_payments/manage', $data);
    }

    public function po_payment($id = '')
    {
        $this->d3a_require('view_al_purchase_payment');
        if ($this->input->post()) {
            $this->d3a_require('add_al_purchase_payment');
            if ($id == '') {
                $id = $this->d3a_albania_model->add_po_payment($this->input->post());
                if ($id) {
                    set_alert('success', 'PO Payment added successfully');
                    redirect(admin_url('d3a_albania/po_payment/' . $id));
                }
            } else {
                $success = $this->d3a_albania_model->update_po_payment($this->input->post(), $id);
                if ($success) {
                    set_alert('success', 'PO Payment updated successfully');
                }
                redirect(admin_url('d3a_albania/po_payment/' . $id));
            }
        }

        if ($id == '') {
            $data['title'] = 'New AL Purchase Payments';
            $data['po_payment'] = null;
            $data['po_invoices'] = $this->d3a_albania_model->get_unpaid_po_invoices();
        } else {
            $data['title'] = 'Edit AL Purchase Payments';
            $data['po_payment'] = $this->d3a_albania_model->get_po_payment($id);
            if (!$data['po_payment']) {
                set_alert('danger', 'PO Payment not found');
                redirect(admin_url('d3a_albania/po_payments'));
            }
            $data['po_invoices'] = $this->d3a_albania_model->get_all_po_invoices();
        }

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get();
        $this->load->view('d3a_albania/po_payments/po_payment', $data);
    }

    public function po_payments_table()
    {
        $this->d3a_require_ajax('view_al_purchase_payment');
        $tableData = $this->app->get_table_data(
            module_views_path('d3a_albania', 'admin/tables/alb_po_payments')
        );
        echo json_encode($tableData);
        die;
    }

    public function get_albania_invoice_row_template()
    {
        if (! d3a_albania_staff_can('add_al_purchase_invoice') && ! d3a_albania_staff_can('edit_al_purchase_invoice')) {
            ajax_access_denied();
        }
        $name = $this->input->post('name');
        $item_name = $this->input->post('item_name');
        $item_description = $this->input->post('item_description');
        $quantity = $this->input->post('quantity');
        $unit_name = $this->input->post('unit_name');
        $unit_price = $this->input->post('unit_price');
        $taxname = $this->input->post('taxname');
        $item_code = $this->input->post('item_code');
        $unit_id = $this->input->post('unit_id');
        $tax_rate = $this->input->post('tax_rate');
        $discount = $this->input->post('discount');
        $item_key = $this->input->post('item_key');
        $currency_rate = $this->input->post('currency_rate');
        $to_currency = $this->input->post('to_currency');

        echo $this->d3a_albania_model->create_albania_invoice_row_template($name, $item_name, $item_description, $quantity, $unit_name, $unit_price, $taxname, $item_code, $unit_id, $tax_rate, '', $discount, '', '', '', '', '', $item_key, false, $currency_rate, $to_currency);
    }



    public function get_albania_invoice_data_ajax($id)
    {
        if (! d3a_albania_staff_can('view_al_purchase_invoice')) {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die(_l('invoice_not_found'));
        }

        $invoice = $this->d3a_albania_model->get_invoice($id);

        if (!$invoice || !$this->user_can_view_alb_invoice($id)) {
            echo _l('invoice_not_found');
            die;
        }

        $template_name = 'invoice_send_to_customer';

        if ($invoice->sent == 1) {
            $template_name = 'invoice_send_to_customer_already_sent';
        }

        $data = prepare_mail_preview_data($template_name, $invoice->clientid);

        // Check for recorded payments
        $this->load->model('payments_model');
        $data['invoices_to_merge'] = $this->d3a_albania_model->check_for_merge_invoice($invoice->clientid, $id);
        $data['members'] = $this->staff_model->get('', ['active' => 1]);
        $data['payments'] = $this->payments_model->get_invoice_payments($id);
        $data['activity'] = $this->d3a_albania_model->get_invoice_activity($id);
        $data['totalNotes'] = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'invoice']);
        $data['invoice_recurring_invoices'] = $this->d3a_albania_model->get_invoice_recurring_invoices($id);

        $data['applied_credits'] = $this->credit_notes_model->get_applied_invoice_credits($id);
        // This data is used only when credit can be applied to invoice
        if (credits_can_be_applied_to_invoice($invoice->status)) {
            $data['credits_available'] = $this->credit_notes_model->total_remaining_credits_by_customer($invoice->clientid);

            if ($data['credits_available'] > 0) {
                $data['open_credits'] = $this->credit_notes_model->get_open_credits($invoice->clientid);
            }

            $customer_currency = $this->clients_model->get_customer_default_currency($invoice->clientid);
            $this->load->model('currencies_model');

            if ($customer_currency != 0) {
                $data['customer_currency'] = $this->currencies_model->get($customer_currency);
            } else {
                $data['customer_currency'] = $this->currencies_model->get_base_currency();
            }
        }

        $data['invoice'] = $invoice;

        $data['record_payment'] = false;
        $data['send_later'] = false;

        if ($this->session->has_userdata('record_payment')) {
            $data['record_payment'] = true;
            $this->session->unset_userdata('record_payment');
        } elseif ($this->session->has_userdata('send_later')) {
            $data['send_later'] = true;
            $this->session->unset_userdata('send_later');
        }

        $this->load->view('d3a_albania/po_invoices/albania_invoice_preview_template', $data);
    }

    private function user_can_view_alb_invoice($id, $staff_id = false)
    {
        $staff_id = $staff_id ? $staff_id : get_staff_user_id();

        if (! staff_can('view_al_invoice', 'd3a_albania', $staff_id)) {
            return false;
        }

        $this->db->select('id');
        $this->db->from(db_prefix() . 'alb_invoices');
        $this->db->where('id', $id);

        return (bool) $this->db->get()->row();
    }

    public function validate_invoice_number()
    {
        $isedit = $this->input->post('isedit');
        if ($isedit === 'true' || $isedit === true) {
            if (! d3a_albania_staff_can('edit_al_invoice')) {
                echo 'false';
                die;
            }
        } elseif (! d3a_albania_staff_can('create_al_invoice')) {
            echo 'false';
            die;
        }
        $number = $this->input->post('number');
        $date = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number = trim($number);
        $number = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        // Build the condition array based on whether we're editing or creating
        $condition = [
            'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
            'number' => $number,
        ];
        
        // Add id exclusion only if invoice_id is provided (edit mode)
        $invoice_id = $this->input->post('invoice_id');
        if (!empty($invoice_id) && is_numeric($invoice_id)) {
            $condition['id !='] = $invoice_id;
        }
        
        if (
            total_rows(db_prefix() . 'alb_invoices', $condition) > 0
        ) {
            echo 'false';
        } else {
            echo 'true';
        }
    }
    public function get_vendor_currency($vendor_id = '')
    {
        if (! d3a_albania_staff_can('add_al_purchase_invoice') && ! d3a_albania_staff_can('edit_al_purchase_invoice')) {
            echo json_encode(['success' => false]);
            return;
        }
        if ($this->input->is_ajax_request() && $vendor_id != '') {
            $this->load->helper('purchase/purchase');
            
            $vendor_currency = get_vendor_currency($vendor_id);
            
            if ($vendor_currency != 0) {
                echo json_encode([
                    'success' => true,
                    'currency_id' => $vendor_currency
                ]);
            } else {
                $this->load->model('currencies_model');
                $base_currency = $this->currencies_model->get_base_currency();
                echo json_encode([
                    'success' => true,
                    'currency_id' => $base_currency->id
                ]);
            }
        } else {
            echo json_encode(['success' => false]);
        }
    }
    /**
     * View ALB Invoice - same format and design as purchase invoice preview
     *
     * @param int $id ALB invoice ID
     */
    public function alb_invoice($id)
    {
        if (!$id) {
            redirect(admin_url('d3a_albania/invoices'));
        }

        $data['alb_invoice'] = $this->d3a_albania_model->get_invoice($id);

        if (!$data['alb_invoice']) {
            show_404();
        }

        if (! $this->user_can_view_alb_invoice($id)) {
            access_denied('d3a_albania');
        }

        $this->load->model('staff_model');
        $this->load->model('currencies_model');
        $this->load->model('payment_modes_model');

        $data['payment_modes'] = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);

        // Get currency (support both invoice_currency and currency columns)
        $currency_id = isset($data['alb_invoice']->invoice_currency) ? $data['alb_invoice']->invoice_currency : (isset($data['alb_invoice']->currency) ? $data['alb_invoice']->currency : 0);
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        if ($currency_id != 0 && $currency_id != '') {
            $data['base_currency'] = $this->currencies_model->get($currency_id);
        }
        if (!$data['base_currency']) {
            $data['base_currency'] = $this->currencies_model->get_base_currency();
        }

        // Transform alb invoice items to match purchase invoice detail format for the table
        $data['invoice_detail'] = $this->d3a_albania_model->get_alb_invoice_items_for_preview($id, $data['base_currency']);
        $data['payment'] = $this->d3a_albania_model->get_invoice_payments($id);
        $data['alb_invoice_attachments'] = $this->d3a_albania_model->get_attachments($id);
        $data['members'] = $this->staff_model->get('', ['active' => 1]);

        $data['title'] = format_alb_invoice_number_custom($id);
        $data['debits_available'] = 0;
        $data['applied_debits'] = [];

        $this->load->view('d3a_albania/invoices/alb_invoice_preview', $data);
    }

    /**
     * ALB Invoice PDF - view or download
     *
     * @param int $id ALB invoice ID
     */
    public function alb_invoice_pdf($id)
    {
        if (!$id) {
            redirect(admin_url('d3a_albania/invoices'));
        }

        if (! $this->user_can_view_alb_invoice($id)) {
            access_denied('d3a_albania');
        }

        $invoice = $this->d3a_albania_model->get_invoice($id);
        if (!$invoice) {
            show_404();
        }

        $invoice_number = format_alb_invoice_number_custom($invoice);

        try {
            $pdf = app_pdf('alb_invoice', module_dir_path(D3A_ALBANIA_MODULE_NAME, 'libraries/pdf/Alb_invoice_pdf'), $invoice);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';
        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }
        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(mb_strtoupper(slug_it($invoice_number)) . '.pdf', $type);
    }

    /**
     * Get notes for ALB invoice (for notes tab in preview)
     */
    public function get_notes_alb_invoice($id)
    {
        if (! $this->user_can_view_alb_invoice($id)) {
            access_denied('d3a_albania');
        }
        $this->load->model('misc_model');
        $data['notes'] = $this->misc_model->get_notes($id, 'alb_invoice');
        $this->load->view('admin/includes/sales_notes_template', $data);
    }

    /**
     * Delete ALB invoice (and all related data).
     *
     * @param int $id ALB invoice ID
     */
    public function delete_alb_invoice($id)
    {
        $this->d3a_require('delete_al_invoice');
        if (! $this->user_can_view_alb_invoice($id)) {
            access_denied('d3a_albania');
        }
        $success = $this->d3a_albania_model->delete($id, true);
        if ($success) {
            set_alert('success', _l('alb_invoice_deleted_successfully'));
        } else {
            set_alert('danger', _l('problem_deleting', _l('invoice')));
        }
        redirect(admin_url('d3a_albania/invoices'));
    }

    /**
     * Delete ALB invoice payment (from alb_invoice preview page).
     *
     * @param int $payment_id Payment ID
     * @param int $invoice_id ALB invoice ID (for redirect)
     */
    public function delete_alb_invoice_payment($payment_id, $invoice_id)
    {
        $this->d3a_require('delete_al_payment');
        if (! $this->user_can_view_alb_invoice($invoice_id)) {
            access_denied('d3a_albania');
        }
        $response = $this->d3a_albania_model->delete_payment($payment_id);
        if ($response) {
            set_alert('success', _l('deleted', _l('payment')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('payment')));
        }
        redirect(admin_url('d3a_albania/alb_invoice/' . $invoice_id));
    }

    /**
     * Add payment for ALB invoice (from modal on alb_invoice preview page).
     *
     * @param int $id ALB invoice ID
     */
    public function add_alb_invoice_payment($id)
    {
        $this->d3a_require('add_al_payment');
        if (! $this->user_can_view_alb_invoice($id)) {
            access_denied('d3a_albania');
        }
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['invoiceid'] = $id;
            $success = $this->d3a_albania_model->add_payment($data);
            if ($success) {
                set_alert('success', _l('added_successfully', _l('payment')));
            } else {
                set_alert('warning', _l('something_went_wrong'));
            }
            redirect(admin_url('d3a_albania/invoices/' . $id));
        }
    }

    /**
     * Add note to ALB invoice (from preview notes tab)
     */
    public function add_note_alb_invoice($id)
    {
        $this->d3a_require('edit_al_invoice');
        if (! $this->user_can_view_alb_invoice($id)) {
            access_denied('d3a_albania');
        }
        if ($this->input->post()) {
            $this->misc_model->add_note($this->input->post(), 'alb_invoice', $id);
            if ($this->input->is_ajax_request()) {
                echo $id;
                return;
            }
            set_alert('success', _l('added_successfully', _l('note')));
        }
        redirect(admin_url('d3a_albania/invoices/' . $id) . '#tab_notes');
    }

    /**
     * Add note to ALB invoice (legacy/alternate)
     */
    public function add_alb_invoice_note($id)
    {
        $this->add_note_alb_invoice($id);
    }

    /**
     * Company Information - save ALB-specific company name, logo, stamp and company details
     * Uses separate options (alb_*) - independent from main Settings > Company
     */
    public function company_information()
    {
        $this->d3a_require('view_al_settings');

        if ($this->input->post()) {
            $this->load->helper('d3a_albania/d3a_albania');

            handle_alb_company_logo_upload('alb_company_logo');
            handle_alb_company_logo_upload('alb_company_logo_dark');
            handle_alb_signature_upload();

            $settings = $this->input->post('settings');
            $option_map = get_alb_company_option_map();
            if (is_array($settings)) {
                foreach ($option_map as $form_key => $option_name) {
                    if (isset($settings[$form_key])) {
                        $value = $settings[$form_key];
                        if ($form_key === 'bank_information' && is_string($value)) {
                            $value = clear_textarea_breaks($value, "\n");
                        }
                        update_option($option_name, $value);
                    }
                }
            }

            set_alert('success', _l('settings_updated'));
            redirect(admin_url('d3a_albania/company_information'));
        }

        $data['title'] = _l('alb_company_information');
        $this->load->view('d3a_albania/company_information/index', $data);
    }

    /**
     * Remove ALB company logo
     */
    public function remove_alb_company_logo($type = '')
    {
        $this->d3a_require('view_al_settings');
        $option_name = ($type == 'dark') ? 'alb_company_logo_dark' : 'alb_company_logo';
        $filename = get_option($option_name);
        if ($filename) {
            $path = get_upload_path_by_type('alb_company') . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
            update_option($option_name, '');
        }
        redirect(admin_url('d3a_albania/company_information'));
    }

    /**
     * Remove ALB company signature/stamp
     */
    public function remove_alb_signature_image()
    {
        $this->d3a_require('view_al_settings');
        $filename = get_option('alb_signature_image');
        if ($filename) {
            $path = get_upload_path_by_type('alb_company') . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
            update_option('alb_signature_image', '');
        }
        redirect(admin_url('d3a_albania/company_information'));
    }

    //Rifat Start........................
    public function albania_po_invoice($id)
    {
        $this->d3a_require('view_al_purchase_invoice');
        $data['pur_invoice'] = $this->d3a_albania_model->get_po_invoice($id);
        if (! $data['pur_invoice']) {
            show_404();
        }
        $this->load->model('staff_model');
        $this->load->model('currencies_model');

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);

        $data['applied_debits'] = $this->d3a_albania_model->get_po_applied_invoice_debits($id);
        $data['debits_available'] = $this->d3a_albania_model->total_po_remaining_debits_by_vendor($data['pur_invoice']->vendor, $data['pur_invoice']->currency);

        if ($data['debits_available'] > 0) {
            $data['open_debits'] = $this->d3a_albania_model->get_po_open_debits($data['pur_invoice']->vendor);
        }
        $vendor_currency_id = get_vendor_currency($data['pur_invoice']->vendor);
        $data['vendor_currency'] = $this->currencies_model->get_base_currency();
        if ($vendor_currency_id != 0) {
            $data['vendor_currency'] = pur_get_currency_by_id($vendor_currency_id);
        }

        $data['invoice_detail'] = $this->d3a_albania_model->get_pur_invoice_detail($id);

        $data['tax_data'] = $this->d3a_albania_model->get_po_html_tax_pur_invoice($id);

        $data['title'] = $data['pur_invoice']->invoice_number;
        $data['members'] = $this->staff_model->get('', ['active' => 1]);
        $data['payment'] = $this->d3a_albania_model->get_po_payment_invoice($id);
        $data['pur_invoice_attachments'] = $this->d3a_albania_model->get_purchase_invoice_attachments($id);

        $this->load->view('d3a_albania/po_invoices/albania_po_invoice_preview', $data);
    }

    public function albania_po_invoice_pdf($invoice_id)
    {
        $this->d3a_require('view_al_purchase_invoice');
        if (!$invoice_id) {
            redirect(admin_url('purchase/invoices'));
        }

        $po_invoice = $this->d3a_albania_model->get_albania_po_invoice_pdf_html($invoice_id);

        try {
            $pdf = $this->d3a_albania_model->purchase_invoice_pdf($po_invoice);
        } catch (Exception $e) {
            echo pur_html_entity_decode($e->getMessage());
            die;
        }

        $type = 'D';
        
        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $filename = 'AL Purchase Invoice';
        $pdf->Output($filename . '.pdf', $type);
    }

    public function add_invoice_payment($invoice)
    {
        $this->d3a_require('add_al_purchase_payment');
        if ($this->input->post()) {
            $data = $this->input->post();
            $message = '';
            $success = $this->d3a_albania_model->add_invoice_payment($data, $invoice);
            if ($success) {
                $message = _l('added_successfully', _l('payment'));
            }
            set_alert('success', $message);
            redirect(admin_url('d3a_albania/albania_po_invoice/' . $invoice));

        }
    }

    public function delete_po_invoice($id, $inv)
    {
        $this->d3a_require('delete_al_purchase_payment');
        if (!$id) {
            redirect(admin_url('d3a_albania/albania_po_invoice/' . $inv));
        }
        $response = $this->d3a_albania_model->delete_po_invoice($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('payment')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('payment')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('payment')));
        }
        redirect(admin_url('d3a_albania/albania_po_invoice/' . $inv));
    }

    public function delete_po_invoices($id)
    {
        $this->d3a_require('delete_al_purchase_invoice');
        if (!$id) {
            redirect(admin_url('d3a_albania/po_invoices'));
        }
        $response = $this->d3a_albania_model->delete_po_invoices($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('invoice')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('invoice')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('invoice')));
        }
        redirect(admin_url('d3a_albania/po_invoices'));
    }

    public function delete_po_payment($id)
    {
        $this->d3a_require('delete_al_purchase_payment');
        if (!$id) {
            redirect(admin_url('d3a_albania/po_payments'));
        }

        $response = $this->d3a_albania_model->delete_po_payment($id);

        if ($response) {
            set_alert('success', _l('deleted', _l('payment')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('payment')));
        }

        redirect($_SERVER['HTTP_REFERER']);
    }

    public function payment_po_invoice($id)
    {
        $this->d3a_require('view_al_purchase_payment');
        $this->load->model('currencies_model');

        $send_mail_approve = $this->session->userdata("send_mail_approve");
        if ((isset($send_mail_approve)) && $send_mail_approve != '') {
            $data['send_mail_approve'] = $send_mail_approve;
            $this->session->unset_userdata("send_mail_approve");
        }

        $data['check_appr'] = $this->d3a_albania_model->get_approve_setting('payment_request');
        $data['get_staff_sign'] = $this->d3a_albania_model->get_staff_sign($id, 'payment_request');
        $data['check_approve_status'] = $this->d3a_albania_model->check_approval_details($id, 'payment_request');
        $data['list_approve_status'] = $this->d3a_albania_model->get_list_approval_details($id, 'payment_request');


        $data['payment_invoice'] = $this->d3a_albania_model->get_payment_pur_invoice($id);
        $data['title'] = _l('payment_for') . ' ' . get_pur_invoice_number($data['payment_invoice']->pur_invoice);

        $data['invoice'] = $this->d3a_albania_model->get_po_invoice($data['payment_invoice']->pur_invoice);

        $data['base_currency'] = $this->currencies_model->get_base_currency();
        if ($data['invoice']->currency != 0) {
            $data['base_currency'] = po_get_currency_by_id($data['invoice']->currency);
        }
        $this->load->view('d3a_albania/po_payments/po_payment_invoice', $data);
    }

    //Rifat END........................
}