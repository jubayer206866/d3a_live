<?php

defined('BASEPATH') or exit('No direct script access allowed');

class D3a_albania_model extends App_Model
{
    public const STATUS_UNPAID = 1;
    public const STATUS_PAID = 2;
    public const STATUS_PARTIALLY = 3;
    public const STATUS_OVERDUE = 4;
    public const STATUS_CANCELLED = 5;
    public const STATUS_DRAFT = 6;
    public const STATUS_DRAFT_NUMBER = 1000000000;

    private $shipping_fields = [
        'shipping_street',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
    ];
    public function __construct()
    {
        parent::__construct();
    }


    public function get_invoice($id = '', $where = [])
    {
        $this->db->select('*, ' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'alb_invoices.id as id, ' . db_prefix() . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . 'alb_invoices');
        // Support both invoice_currency (used by form/table) and currency (install default)
        $currency_col = $this->db->field_exists('invoice_currency', db_prefix() . 'alb_invoices') ? 'invoice_currency' : 'currency';
        $this->db->join(db_prefix() . 'currencies', '' . db_prefix() . 'currencies.id = ' . db_prefix() . 'alb_invoices.' . $currency_col, 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'alb_invoices.id', $id);
            $invoice = $this->db->get()->row();
            if ($invoice) {
                $total_paid = $this->get_invoice_total_paid($id);
                $invoice->total_left_to_pay = (float) $invoice->total - (float) $total_paid;
                $invoice->total_left_to_pay_invoice_amount = $invoice->total_left_to_pay;

                $invoice->items = get_items_by_type('alb_invoice', $id);
                if (empty($invoice->items)) {
                    $invoice->items = get_items_by_type('alb_invoices', $id);
                } else {
                    // Merge with alb_invoices items (new items added during edit use alb_invoices)
                    $alb_invoices_items = get_items_by_type('alb_invoices', $id);
                    if (!empty($alb_invoices_items)) {
                        $invoice->items = array_merge($invoice->items, $alb_invoices_items);
                        usort($invoice->items, function ($a, $b) {
                            $orderA = isset($a['item_order']) ? (int) $a['item_order'] : 0;
                            $orderB = isset($b['item_order']) ? (int) $b['item_order'] : 0;
                            return $orderA - $orderB;
                        });
                    }
                }
                $invoice->attachments = $this->get_attachments($id);

                if ($invoice->project_id) {
                    $this->load->model('projects_model');
                    $invoice->project_data = $this->projects_model->get($invoice->project_id);
                }

                $invoice->visible_attachments_to_customer_found = false;
                foreach ($invoice->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $invoice->visible_attachments_to_customer_found = true;

                        break;
                    }
                }
                $client = $this->clients_model->get($invoice->clientid);
                $invoice->client = $client;
                if (!$invoice->client) {
                    $invoice->client = new stdClass();
                    $invoice->client->company = $invoice->deleted_customer_name;
                }

                $invoice->payments = $this->get_invoice_payments($id);

                $this->load->model('email_schedule_model');
                $invoice->scheduled_email = $this->email_schedule_model->get($id, 'alb_invoices');
            }

            // Use ALB-specific hook to avoid conflict with standard invoices module
            return hooks()->apply_filters('get_alb_invoice', $invoice);
        }

        $this->db->order_by('number,YEAR(date)', 'desc');

        return $this->db->get()->result_array();
    }

    public function get_all_invoices()
    {
        return $this->db->get(db_prefix() . 'alb_invoices')->result();
    }

    public function get_unpaid_invoices()
    {
        $this->db->where_in('status', [1, 3]); // Unpaid and Partially paid
        $invoices = $this->db->get(db_prefix() . 'alb_invoices')->result();

        foreach ($invoices as $invoice) {
            $invoice->total_paid = $this->get_invoice_total_paid($invoice->id);
            $invoice->total_left = $invoice->total - $invoice->total_paid;
        }

        return $invoices;
    }

    public function get_invoice_payments($invoice_id)
    {
        $this->db->where('invoiceid', $invoice_id);
        $this->db->order_by('date', 'desc');
        return $this->db->get(db_prefix() . 'alb_payments')->result();
    }
    public function get_invoice_total_paid($invoice_id)
    {
        $this->db->select_sum('amount');
        $this->db->where('invoiceid', $invoice_id);
        $result = $this->db->get(db_prefix() . 'alb_payments')->row();
        return $result->amount ? $result->amount : 0;
    }
    // ALB Payment Methods
    public function get_payment($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'alb_payments')->row();
    }
    public function add_payment($data)
    {
        $invoice = $this->get_invoice($data['invoiceid']);
        $invoice_total = 0;
        if ($invoice) {
            $invoice_total = (float) (isset($invoice->invoice_amount) && $invoice->invoice_amount > 0 ? $invoice->invoice_amount : ($invoice->total ?? 0));
        }
        $payment_date = isset($data['date']) && $data['date'] !== '' ? to_sql_date($data['date']) : null;
        if (empty($payment_date)) {
            $payment_date = date('Y-m-d');
        }
        $payment_data = [
            'invoiceid' => $data['invoiceid'],
            'amount' => $data['amount'],
            'invoice_amount' => $invoice_total,
            'paymentmode' => isset($data['paymentmode']) ? $data['paymentmode'] : null,
            'paymentmethod' => isset($data['paymentmethod']) ? $data['paymentmethod'] : null,
            'date' => $payment_date,
            'daterecorded' => date('Y-m-d H:i:s'),
            'note' => isset($data['note']) ? $data['note'] : null,
            'transactionid' => isset($data['transactionid']) ? $data['transactionid'] : null,
        ];

        $this->db->insert(db_prefix() . 'alb_payments', $payment_data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update invoice status if fully paid
            $this->update_invoice_payment_status($data['invoiceid']);
            log_activity('ALB Payment Recorded [ID: ' . $insert_id . ']');
            return $insert_id;
        }
        return false;
    }
    public function update_payment($data, $id)
    {
        $invoice = $this->get_invoice($data['invoiceid']);
        $invoice_total = 0;
        if ($invoice) {
            $invoice_total = (float) (isset($invoice->invoice_amount) && $invoice->invoice_amount > 0 ? $invoice->invoice_amount : ($invoice->total ?? 0));
        }
        $payment_date = isset($data['date']) && $data['date'] !== '' ? to_sql_date($data['date']) : null;
        if (empty($payment_date)) {
            $payment_date = date('Y-m-d');
        }
        $payment_data = [
            'invoiceid' => $data['invoiceid'],
            'amount' => $data['amount'],
            'invoice_amount' => $invoice_total,
            'paymentmode' => isset($data['paymentmode']) ? $data['paymentmode'] : null,
            'paymentmethod' => isset($data['paymentmethod']) ? $data['paymentmethod'] : null,
            'date' => $payment_date,
            'note' => isset($data['note']) ? $data['note'] : null,
            'transactionid' => isset($data['transactionid']) ? $data['transactionid'] : null,
        ];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'alb_payments', $payment_data);

        // Update invoice status
        $payment = $this->get_payment($id);
        if ($payment) {
            $this->update_invoice_payment_status($payment->invoiceid);
        }

        log_activity('ALB Payment Updated [ID: ' . $id . ']');
        return true;
    }

    public function delete_payment($payment_id)
    {
        $payment = $this->get_payment($payment_id);
        if (!$payment) {
            return false;
        }
        $this->db->where('id', $payment_id);
        $this->db->delete(db_prefix() . 'alb_payments');
        if ($this->db->affected_rows() > 0) {
            $this->update_invoice_payment_status($payment->invoiceid);
            return true;
        }
        return false;
    }

    private function update_invoice_payment_status($invoice_id)
    {
        $invoice = $this->get_invoice($invoice_id);
        if ($invoice) {
            $total_paid = $this->get_invoice_total_paid($invoice_id);
            $invoice_total = (float) (isset($invoice->invoice_amount) && $invoice->invoice_amount > 0 ? $invoice->invoice_amount : ($invoice->total ?? 0));
            $status = 1; // Unpaid
            if ($total_paid >= $invoice_total) {
                $status = 2; // Paid
            } elseif ($total_paid > 0) {
                $status = 3; // Partially paid
            }

            $this->db->where('id', $invoice_id);
            $this->db->update(db_prefix() . 'alb_invoices', ['status' => $status]);
        }
    }

    // ALB Expense Methods
    public function get_expense($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'alb_expenses')->row();
    }

    public function get($id)
    {
        return $this->get_expense($id);
    }

    public function get_all_expenses()
    {
        return $this->db->get(db_prefix() . 'alb_expenses')->result_array();
    }

    public function add_expense($data)
    {
        $data['date'] = to_sql_date($data['date']);
        $data['note'] = nl2br($data['note']);
        if (isset($data['billable'])) {
            $data['billable'] = 1;
        } else {
            $data['billable'] = 0;
        }

        if (isset($data['create_invoice_billable'])) {
            $data['create_invoice_billable'] = 1;
        } else {
            $data['create_invoice_billable'] = 0;
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        if (isset($data['send_invoice_to_customer'])) {
            $data['send_invoice_to_customer'] = 1;
        } else {
            $data['send_invoice_to_customer'] = 0;
        }

        if (isset($data['repeat_every']) && $data['repeat_every'] != '') {
            $data['recurring'] = 1;
            if ($data['repeat_every'] == 'custom') {
                $data['repeat_every'] = $data['repeat_every_custom'];
                $data['recurring_type'] = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
            } else {
                $_temp = explode('-', $data['repeat_every']);
                $data['recurring_type'] = $_temp[1];
                $data['repeat_every'] = $_temp[0];
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['recurring'] = 0;
        }

        unset($data['repeat_type_custom']);
        unset($data['repeat_every_custom']);

        if ((isset($data['project_id']) && $data['project_id'] == '') || !isset($data['project_id'])) {
            $data['project_id'] = 0;
        }

        $data['addedfrom'] = get_staff_user_id();
        $data['dateadded'] = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_expense_added', $data);

        $this->db->insert(db_prefix() . 'alb_expenses', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            if (isset($data['project_id']) && !empty($data['project_id'])) {
                $this->load->model('projects_model');
                $project_settings = $this->projects_model->get_project_settings($data['project_id']);
                $visible_activity = 0;
                foreach ($project_settings as $s) {
                    if ($s['name'] == 'view_finance_overview') {
                        if ($s['value'] == 1) {
                            $visible_activity = 1;

                            break;
                        }
                    }
                }

                $expense = $this->get($insert_id);
                $activity_additional_data = $expense->name;
                $this->projects_model->log_activity($data['project_id'], 'project_activity_recorded_expense', $activity_additional_data, $visible_activity);
            }

            hooks()->do_action('after_expense_added', $insert_id);

            log_activity('New Expense Added [' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    public function update_expense($data, $id)
    {
        $expense_data = [
            'category' => $data['category'],
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'tax' => isset($data['tax']) ? $data['tax'] : null,
            'reference_no' => isset($data['reference_no']) ? $data['reference_no'] : null,
            'note' => isset($data['note']) ? nl2br($data['note']) : null,
            'clientid' => isset($data['clientid']) ? $data['clientid'] : 0,
            'paymentmode' => isset($data['paymentmode']) ? $data['paymentmode'] : null,
            'date' => to_sql_date($data['date']),
        ];
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'alb_expenses', $expense_data);

        log_activity('ALB Expense Updated [ID: ' . $id . ']');
        return true;
    }

    public function delete_expense($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'alb_expenses');

        if ($this->db->affected_rows() > 0) {
            log_activity('ALB Expense Deleted [ID: ' . $id . ']');
            return true;
        }

        return false;
    }

    // ALB PO Invoice Methods
    public function get_po_invoice($id)
    {
        if ($id != '') {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'alb_po_invoices')->row();
        } else {
            return $this->db->get(db_prefix() . 'alb_po_invoices')->result_array();
        }
    }

    public function get_all_po_invoices()
    {
        return $this->db->get(db_prefix() . 'alb_po_invoices')->result();
    }

    public function get_last_po_invoice_number()
    {
        $this->db->select('invoice_number');
        $this->db->from(db_prefix() . 'alb_po_invoices');
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $row = $this->db->get()->row();

        if (!$row || empty($row->invoice_number)) {
            return 0;
        }

        $number_part = preg_replace('/[^0-9]/', '', $row->invoice_number);
        return (int) $number_part;
    }

    public function get_unpaid_po_invoices()
    {
        $this->db->where('payment_status', 'unpaid');
        $po_invoices = $this->db->get(db_prefix() . 'alb_po_invoices')->result();

        foreach ($po_invoices as $po_invoice) {
            $po_invoice->total_paid = $this->get_po_invoice_total_paid($po_invoice->id);
            $po_invoice->total_left = $po_invoice->total - $po_invoice->total_paid;
        }

        return $po_invoices;
    }

    public function get_po_invoice_total_paid($po_invoice_id)
    {
        $this->db->select_sum('amount');
        $this->db->where('po_invoice_id', $po_invoice_id);
        $result = $this->db->get(db_prefix() . 'alb_po_payments')->row();
        return $result->amount ? $result->amount : 0;
    }

    public function add_po_invoice($data)
    {
        unset($data['grand_total']);
        unset($data['item_select']);
        unset($data['tax_select']);
        unset($data['item_name']);
        unset($data['description']);
        unset($data['total']);
        unset($data['quantity']);
        unset($data['unit_price']);
        unset($data['unit_name']);
        unset($data['item_code']);
        unset($data['unit_id']);
        unset($data['discount']);
        unset($data['into_money']);
        unset($data['tax_rate']);
        unset($data['tax_name']);
        unset($data['discount_money']);
        unset($data['total_money']);
        unset($data['additional_discount']);
        unset($data['tax_value']);

        $order_detail = [];
        if (isset($data['newitems'])) {
            $order_detail = $data['newitems'];
            unset($data['newitems']);
        }

        $data['to_currency'] = $data['currency'];

        if (isset($data['add_from'])) {
            $data['add_from'] = $data['add_from'];
        } else {
            $data['add_from'] = get_staff_user_id();
            $data['add_from_type'] = 'admin';
        }

        $data['date_add'] = date('Y-m-d');
        $data['payment_status'] = 'unpaid';
        $prefix = get_purchase_option('alb_po_inv_prefix');

        $this->db->where('invoice_number', $data['invoice_number']);
        $check_exist_number = $this->db->get(db_prefix() . 'alb_po_invoices')->row();

        while ($check_exist_number) {
            $data['number'] = $data['number'] + 1;
            $data['invoice_number'] = $prefix . str_pad($data['number'], 6, '0', STR_PAD_LEFT);
            $this->db->where('invoice_number', $data['invoice_number']);
            $check_exist_number = $this->db->get(db_prefix() . 'alb_po_invoices')->row();
        }

        $data['invoice_date'] = to_sql_date($data['invoice_date']);
        if (isset($data['duedate']) && $data['duedate'] != '') {
            $data['duedate'] = to_sql_date($data['duedate']);
        }

        $data['transaction_date'] = to_sql_date($data['transaction_date']);

        if (isset($data['order_discount'])) {
            $order_discount = $data['order_discount'];
            if ($data['add_discount_type'] == 'percent') {
                $data['discount_percent'] = $order_discount;
            }

            unset($data['order_discount']);
        }

        unset($data['add_discount_type']);

        if (isset($data['dc_total'])) {
            $data['discount_total'] = $data['dc_total'];
            unset($data['dc_total']);
        }

        // if (isset($data['total_mn'])) {
        //     $data['subtotal'] = $data['total_mn'];
        //     unset($data['total_mn']);
        // }

        if (isset($data['total_mn'])) {
            $data['subtotal'] = $data['total_mn'];
            $data['total'] = $data['total_mn'];
            unset($data['total_mn']);
        }

        // if (isset($data['grand_total'])) {
        //     $data['total'] = $data['grand_total'];
        //     unset($data['grand_total']);
        // }

        $tags = '';
        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }
        $this->db->insert(db_prefix() . 'alb_po_invoices', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $next_number = $data['number'] + 1;
            $this->db->where('option_name', 'next_inv_number');
            $this->db->update(db_prefix() . 'purchase_option', ['option_val' => $next_number,]);

            handle_tags_save($tags, $insert_id, 'pur_invoice');

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            $total = [];
            $total['tax'] = 0;

            if (count($order_detail) > 0) {
                foreach ($order_detail as $key => $rqd) {
                    $dt_data = [];
                    $dt_data['pur_invoice'] = $insert_id;
                    $dt_data['item_code'] = $rqd['item_code'];
                    $dt_data['unit_id'] = isset($rqd['unit_id']) ? $rqd['unit_id'] : null;
                    $dt_data['unit_price'] = $rqd['unit_price'];
                    $dt_data['into_money'] = $rqd['into_money'];
                    $dt_data['total'] = $rqd['total'];
                    $dt_data['tax_value'] = $rqd['tax_value'];
                    $dt_data['item_name'] = $rqd['item_name'];
                    $dt_data['description'] = $rqd['item_description'];
                    $dt_data['total_money'] = $rqd['total_money'];
                    $dt_data['discount_money'] = $rqd['discount_money'];
                    $dt_data['discount_percent'] = $rqd['discount'];

                    $tax_money = 0;
                    $tax_rate_value = 0;
                    $tax_rate = null;
                    $tax_id = null;
                    $tax_name = null;

                    if (isset($rqd['tax_select'])) {
                        $tax_rate_data = $this->pur_get_tax_rate($rqd['tax_select']);
                        $tax_rate_value = $tax_rate_data['tax_rate'];
                        $tax_rate = $tax_rate_data['tax_rate_str'];
                        $tax_id = $tax_rate_data['tax_id_str'];
                        $tax_name = $tax_rate_data['tax_name_str'];
                    }

                    $dt_data['tax'] = $tax_id;
                    $dt_data['tax_rate'] = $tax_rate;
                    $dt_data['tax_name'] = $tax_name;

                    $dt_data['quantity'] = ($rqd['quantity'] != '' && $rqd['quantity'] != null) ? $rqd['quantity'] : 0;

                    $this->db->insert(db_prefix() . 'alb_po_invoice_items', $dt_data);

                    $total['tax'] += $rqd['tax_value'];
                }
            }

            $this->db->where('id', $insert_id);
            $this->db->update(db_prefix() . 'alb_po_invoices', $total);

            return $insert_id;
        }

        return false;
    }

    public function update_po_invoice($id, $data)
    {
        $data['invoice_date'] = to_sql_date($data['invoice_date']);
        $data['transaction_date'] = to_sql_date($data['transaction_date']);

        $affectedRows = 0;
        unset($data['grand_total']);
        unset($data['item_select']);
        unset($data['tax_select']);
        unset($data['item_name']);
        unset($data['description']);
        unset($data['total']);
        unset($data['quantity']);
        unset($data['unit_price']);
        unset($data['unit_name']);
        unset($data['item_code']);
        unset($data['unit_id']);
        unset($data['discount']);
        unset($data['into_money']);
        unset($data['tax_rate']);
        unset($data['tax_name']);
        unset($data['discount_money']);
        unset($data['total_money']);
        unset($data['additional_discount']);
        unset($data['tax_value']);

        unset($data['isedit']);

        if (isset($data['dc_total'])) {
            $data['discount_total'] = $data['dc_total'];
            unset($data['dc_total']);
        }

        $data['to_currency'] = $data['currency'];

        // if (isset($data['total_mn'])) {
        //     $data['subtotal'] = $data['total_mn'];
        //     unset($data['total_mn']);
        // }

        if (isset($data['total_mn'])) {
            $data['subtotal'] = $data['total_mn'];
            $data['total'] = $data['total_mn'];
            unset($data['total_mn']);
        }
        // if (isset($data['grand_total'])) {
        //     $data['total'] = $data['grand_total'];
        //     unset($data['grand_total']);
        // }

        $new_order = [];
        if (isset($data['newitems'])) {
            $new_order = $data['newitems'];
            unset($data['newitems']);
        }

        $update_order = [];
        if (isset($data['items'])) {
            $update_order = $data['items'];
            unset($data['items']);
        }

        $remove_order = [];
        if (isset($data['removed_items'])) {
            $remove_order = $data['removed_items'];
            unset($data['removed_items']);
        }

        if (isset($data['duedate']) && $data['duedate'] != '') {
            $data['duedate'] = to_sql_date($data['duedate']);
        }

        if (isset($data['order_discount'])) {
            $order_discount = $data['order_discount'];
            if ($data['add_discount_type'] == 'percent') {
                $data['discount_percent'] = $order_discount;
            }

            unset($data['order_discount']);
        }

        unset($data['add_discount_type']);

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'pur_invoice')) {
                $affectedRows++;
            }
            unset($data['tags']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        if (count($new_order) > 0) {
            foreach ($new_order as $key => $rqd) {

                $dt_data = [];
                $dt_data['pur_invoice'] = $id;
                $dt_data['item_code'] = $rqd['item_code'];
                $dt_data['unit_id'] = isset($rqd['unit_id']) ? $rqd['unit_id'] : null;
                $dt_data['unit_price'] = $rqd['unit_price'];
                $dt_data['into_money'] = $rqd['into_money'];
                $dt_data['total'] = $rqd['total'];
                $dt_data['tax_value'] = $rqd['tax_value'];
                $dt_data['item_name'] = $rqd['item_name'];
                $dt_data['total_money'] = $rqd['total_money'];
                $dt_data['discount_money'] = $rqd['discount_money'];
                $dt_data['discount_percent'] = $rqd['discount'];
                $dt_data['description'] = $rqd['item_description'];

                $tax_money = 0;
                $tax_rate_value = 0;
                $tax_rate = null;
                $tax_id = null;
                $tax_name = null;

                if (isset($rqd['tax_select'])) {
                    $tax_rate_data = $this->pur_get_tax_rate($rqd['tax_select']);
                    $tax_rate_value = $tax_rate_data['tax_rate'];
                    $tax_rate = $tax_rate_data['tax_rate_str'];
                    $tax_id = $tax_rate_data['tax_id_str'];
                    $tax_name = $tax_rate_data['tax_name_str'];
                }

                $dt_data['tax'] = $tax_id;
                $dt_data['tax_rate'] = $tax_rate;
                $dt_data['tax_name'] = $tax_name;

                $dt_data['quantity'] = ($rqd['quantity'] != '' && $rqd['quantity'] != null) ? $rqd['quantity'] : 0;

                $this->db->insert(db_prefix() . 'alb_po_invoice_items', $dt_data);
                $new_quote_insert_id = $this->db->insert_id();
                if ($new_quote_insert_id) {
                    $affectedRows++;
                }
            }
        }

        if (count($update_order) > 0) {
            foreach ($update_order as $_key => $rqd) {
                $dt_data = [];
                $dt_data['pur_invoice'] = $id;
                $dt_data['item_code'] = $rqd['item_code'];
                $dt_data['unit_id'] = isset($rqd['unit_id']) ? $rqd['unit_id'] : null;
                $dt_data['unit_price'] = $rqd['unit_price'];
                $dt_data['into_money'] = $rqd['into_money'];
                $dt_data['total'] = $rqd['total'];
                $dt_data['tax_value'] = $rqd['tax_value'];
                $dt_data['item_name'] = $rqd['item_name'];
                $dt_data['total_money'] = $rqd['total_money'];
                $dt_data['discount_money'] = $rqd['discount_money'];
                $dt_data['discount_percent'] = $rqd['discount'];
                $dt_data['description'] = $rqd['item_description'];

                $tax_money = 0;
                $tax_rate_value = 0;
                $tax_rate = null;
                $tax_id = null;
                $tax_name = null;

                if (isset($rqd['tax_select'])) {
                    $tax_rate_data = $this->pur_get_tax_rate($rqd['tax_select']);
                    $tax_rate_value = $tax_rate_data['tax_rate'];
                    $tax_rate = $tax_rate_data['tax_rate_str'];
                    $tax_id = $tax_rate_data['tax_id_str'];
                    $tax_name = $tax_rate_data['tax_name_str'];
                }

                $dt_data['tax'] = $tax_id;
                $dt_data['tax_rate'] = $tax_rate;
                $dt_data['tax_name'] = $tax_name;

                $dt_data['quantity'] = ($rqd['quantity'] != '' && $rqd['quantity'] != null) ? $rqd['quantity'] : 0;

                $this->db->where('id', $rqd['id']);
                $this->db->update(db_prefix() . 'alb_po_invoice_items', $dt_data);
                if ($this->db->affected_rows() > 0) {
                    $affectedRows++;
                }
            }
        }

        if (count($remove_order) > 0) {
            foreach ($remove_order as $remove_id) {
                $this->db->where('id', $remove_id);
                if ($this->db->delete(db_prefix() . 'alb_po_invoice_items')) {
                    $affectedRows++;
                }
            }
        }

        $order_detail_after_update = $this->get_pur_invoice_detail($id);
        $total = [];
        $data['tax'] = 0;
        if (count($order_detail_after_update) > 0) {
            foreach ($order_detail_after_update as $dt) {
                $data['tax'] += $dt['tax_value'];
            }
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'alb_po_invoices', $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    // ALB PO Payment Methods
    public function get_po_payment($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'alb_po_invoice_payment')->row();
    }

    public function add_po_payment($data)
    {
        $payment_data = [
            'po_invoice_id' => $data['po_invoice_id'],
            'amount' => $data['amount'],
            'paymentmode' => isset($data['paymentmode']) ? $data['paymentmode'] : null,
            'date' => to_sql_date($data['date']),
            'daterecorded' => date('Y-m-d H:i:s'),
            'note' => isset($data['note']) ? $data['note'] : null,
            'transactionid' => isset($data['transactionid']) ? $data['transactionid'] : null,
            'addedfrom' => get_staff_user_id(),
        ];

        $this->db->insert(db_prefix() . 'alb_po_payments', $payment_data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update PO invoice payment status
            $this->update_po_invoice_payment_status($data['po_invoice_id']);
            log_activity('ALB PO Payment Recorded [ID: ' . $insert_id . ']');
            return $insert_id;
        }

        return false;
    }

    public function update_po_payment($data, $id)
    {
        $payment_data = [
            'po_invoice_id' => $data['po_invoice_id'],
            'amount' => $data['amount'],
            'paymentmode' => isset($data['paymentmode']) ? $data['paymentmode'] : null,
            'date' => to_sql_date($data['date']),
            'note' => isset($data['note']) ? $data['note'] : null,
            'transactionid' => isset($data['transactionid']) ? $data['transactionid'] : null,
        ];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'alb_po_payments', $payment_data);

        // Update PO invoice status
        $payment = $this->get_po_payment($id);
        if ($payment) {
            $this->update_po_invoice_payment_status($payment->po_invoice_id);
        }

        log_activity('ALB PO Payment Updated [ID: ' . $id . ']');
        return true;
    }

    private function update_po_invoice_payment_status($po_invoice_id)
    {
        $po_invoice = $this->get_po_invoice($po_invoice_id);
        if ($po_invoice) {
            $total_paid = $this->get_po_invoice_total_paid($po_invoice_id);
            $status = 'unpaid';
            if ($total_paid >= $po_invoice->total) {
                $status = 'paid';
            } elseif ($total_paid > 0) {
                $status = 'partially_paid';
            }

            $this->db->where('id', $po_invoice_id);
            $this->db->update(db_prefix() . 'alb_po_invoices', ['payment_status' => $status]);
        }
    }

    public function create_albania_invoice_row_template($name = '', $item_name = '', $item_description = '', $quantity = '', $unit_name = '', $unit_price = '', $taxname = '', $item_code = '', $unit_id = '', $tax_rate = '', $total_money = '', $discount = '', $discount_money = '', $total = '', $into_money = '', $tax_id = '', $tax_value = '', $item_key = '', $is_edit = false, $currency_rate = 1, $to_currency = '')
    {

        $this->load->model('invoice_items_model');
        $row = '';

        $name_item_code = 'item_code';
        $name_item_name = 'item_name';
        $name_item_description = 'description';
        $name_unit_id = 'unit_id';
        $name_unit_name = 'unit_name';
        $name_quantity = 'quantity';
        $name_unit_price = 'unit_price';
        $name_tax_id_select = 'tax_select';
        $name_tax_id = 'tax_id';
        $name_total = 'total';
        $name_tax_rate = 'tax_rate';
        $name_tax_name = 'tax_name';
        $name_tax_value = 'tax_value';
        $array_attr = [];
        $array_attr_payment = ['data-payment' => 'alb_invoices'];
        $name_into_money = 'into_money';
        $name_discount = 'discount';
        $name_discount_money = 'discount_money';
        $name_total_money = 'total_money';

        $array_available_quantity_attr = ['min' => '0.0', 'step' => 'any', 'readonly' => true];
        $array_qty_attr = ['min' => '0.0', 'step' => 'any'];
        $array_rate_attr = ['min' => '0.0', 'step' => 'any'];
        $array_discount_attr = ['min' => '0.0', 'step' => 'any'];
        $array_discount_money_attr = ['min' => '0.0', 'step' => 'any'];
        $str_rate_attr = 'min="0.0" step="any"';

        $array_subtotal_attr = ['readonly' => true];
        $text_right_class = 'text-right';

        if ($name == '') {
            $row .= '<tr class="main">
                  <td></td>';
            $vehicles = [];
            $array_attr = ['placeholder' => _l('unit_price')];

            $manual = true;
            $invoice_item_taxes = '';
            $amount = '';
            $sub_total = 0;

        } else {
            $row .= '<tr class="sortable item">
                    <td class="dragger"><input type="hidden" class="order" name="' . $name . '[order]"><input type="hidden" class="ids" name="' . $name . '[id]" value="' . $item_key . '"></td>';
            $name_item_code = $name . '[item_code]';
            $name_item_name = $name . '[item_name]';
            $name_item_description = $name . '[item_description]';
            $name_unit_id = $name . '[unit_id]';
            $name_unit_name = '[unit_name]';
            $name_quantity = $name . '[quantity]';
            $name_unit_price = $name . '[unit_price]';
            $name_tax_id_select = $name . '[tax_select][]';
            $name_tax_id = $name . '[tax_id]';
            $name_total = $name . '[total]';
            $name_tax_rate = $name . '[tax_rate]';
            $name_tax_name = $name . '[tax_name]';
            $name_into_money = $name . '[into_money]';
            $name_discount = $name . '[discount]';
            $name_discount_money = $name . '[discount_money]';
            $name_total_money = $name . '[total_money]';
            $name_tax_value = $name . '[tax_value]';


            $array_qty_attr = ['onblur' => 'pur_calculate_total();', 'onchange' => 'pur_calculate_total();', 'min' => '0.0', 'step' => 'any', 'data-quantity' => (float) $quantity];


            $array_rate_attr = ['onblur' => 'pur_calculate_total();', 'onchange' => 'pur_calculate_total();', 'min' => '0.0', 'step' => 'any', 'data-amount' => 'alb_invoices', 'placeholder' => _l('rate')];
            $array_discount_attr = ['onblur' => 'pur_calculate_total();', 'onchange' => 'pur_calculate_total();', 'min' => '0.0', 'step' => 'any', 'data-amount' => 'alb_invoices', 'placeholder' => _l('discount')];

            $array_discount_money_attr = ['onblur' => 'pur_calculate_total(1);', 'onchange' => 'pur_calculate_total(1);', 'min' => '0.0', 'step' => 'any', 'data-amount' => 'alb_invoices', 'placeholder' => _l('discount')];

            $manual = false;

            $tax_money = 0;
            $tax_rate_value = 0;

            if ($is_edit) {
                $invoice_item_taxes = pur_convert_item_taxes($tax_id, $tax_rate, $taxname);
                $arr_tax_rate = explode('|', $tax_rate);
                foreach ($arr_tax_rate as $key => $value) {
                    $tax_rate_value += (float) $value;
                }
            } else {
                $invoice_item_taxes = $taxname;
                $tax_rate_data = $this->pur_get_tax_rate($taxname);
                $tax_rate_value = $tax_rate_data['tax_rate'];
            }

            if ((float) $tax_rate_value != 0) {
                $tax_money = (float) $unit_price * (float) $tax_rate_value / 100;
                $goods_money = (float) $unit_price + (float) $tax_money;
                $amount = (float) $unit_price + (float) $tax_money;
            } else {
                $goods_money = (float) $unit_price;
                $amount = (float) $unit_price;
            }

            $sub_total = (float) $unit_price;
            
            // Calculate total_money if not provided (for create/edit modes)
            if ($total_money === '' || $total_money === null) {
                $total_money = (float) $unit_price;
            }
            
            $amount = app_format_number($amount);
        }


        $row .= '<td class="">' . render_textarea($name_item_name, '', $item_name, ['rows' => 2, 'placeholder' => _l('Name/Title')]) . '</td>';

        $row .= '<td class="">' . render_textarea($name_item_description, '', $item_description, ['rows' => 2, 'placeholder' => _l('description')]) . '</td>';

        $row .= '<td class="quantities">' .
            render_input($name_quantity, '', $quantity, 'number', $array_qty_attr, [], 'no-margin', $text_right_class) .
            //render_input($name_unit_name, '', $unit_name, 'text', ['readonly' => true], [], 'no-margin', 'input-transparent text-right pur_input_none') .
            '</td>';

        $row .= '<td class="rate" align="right">' . render_input($name_unit_price, '', $unit_price, 'number', $array_rate_attr, [], 'no-margin', $text_right_class) . '</td>';

        //$row .= '<td class="_total" align="right">' . $total . '</td>';

        $row .= '<td class="hide commodity_code">' . render_input($name_item_code, '', $item_code, 'text', ['placeholder' => _l('commodity_code')]) . '</td>';
        $row .= '<td class="hide unit_id">' . render_input($name_unit_id, '', $unit_id, 'text', ['placeholder' => _l('unit_id')]) . '</td>';

        //$row .= '<td class="hide _total_after_tax">' . render_input($name_total, '', $total, 'number', []) . '</td>';

        //$row .= '<td class="hide discount_money">' . render_input($name_discount_money, '', $discount_money, 'number', []) . '</td>';
        $row .= '<td class="hide total_after_discount">' . render_input($name_total_money, '', $total_money, 'number', []) . '</td>';
        $row .= '<td class="hide _into_money">' . render_input($name_into_money, '', $into_money, 'number', []) . '</td>';

        if ($name == '') {
            $row .= '<td class="hide preview_item_name">' . render_textarea('item_name', '', '', ['placeholder' => _l('item_name')]) . '</td>';
            $row .= '<td class="hide preview_description">' . render_textarea('description', '', '', ['placeholder' => _l('description')]) . '</td>';
            $row .= '<td class="hide preview_quantity">' . render_input('quantity', '', '', 'number', ['placeholder' => _l('quantity')]) . '</td>';
            $row .= '<td class="hide preview_unit_name">' . render_input('unit_name', '', '', 'text', ['placeholder' => _l('unit')]) . '</td>';
            $row .= '<td class="hide preview_unit_price">' . render_input('unit_price', '', '', 'number', ['placeholder' => _l('unit_price')]) . '</td>';
            $row .= '<td class="hide preview_taxname">' . render_select('tax_select', [], ['id', 'name'], '', '', ['data-none-selected-text' => _l('no_tax')], [], 'tax') . '</td>';
            $row .= '<td class="hide preview_item_code">' . render_input('item_code', '', '', 'text', ['placeholder' => _l('item_code')]) . '</td>';
            $row .= '<td class="hide preview_unit_id">' . render_input('unit_id', '', '', 'text', ['placeholder' => _l('unit_id')]) . '</td>';
            $row .= '<td class="hide preview_tax_rate">' . render_input('tax_rate', '', '', 'number', ['placeholder' => _l('tax_rate')]) . '</td>';
            $row .= '<td class="hide preview_discount">' . render_input('discount', '', '', 'number', ['placeholder' => _l('discount')]) . '</td>';
            $row .= '<td><button type="button" onclick="albania_add_item_to_table(undefined,undefined); return false;" class="btn pull-right btn-info"><i class="fa fa-check"></i></button></td>';
        } else {
            $row .= '<td><a href="#" class="btn btn-danger pull-right" onclick="delete_item(this,' . $item_key . '); return false;"><i class="fa fa-trash"></i></a></td>';
        }

        $row .= '</tr>';
        return $row;
    }

    public function get_vendor($id = '', $where = [])
    {
        $this->db->select(implode(',', prefixed_table_fields_array(db_prefix() . 'pur_vendor')) . ',' . get_sql_select_vendor_company());

        if (is_numeric($id)) {

            $this->db->join(db_prefix() . 'countries', db_prefix() . 'countries.country_id = ' . db_prefix() . 'pur_vendor.country', 'left');
            $this->db->join(db_prefix() . 'pur_contacts', db_prefix() . 'pur_contacts.userid = ' . db_prefix() . 'pur_vendor.userid AND is_primary = 1', 'left');

            if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
                $this->db->where($where);
            }

            $this->db->where(db_prefix() . 'pur_vendor.userid', $id);

            $vendor = $this->db->get(db_prefix() . 'pur_vendor')->row();

            if ($vendor && get_option('company_requires_vat_number_field') == 0) {
                $vendor->vat = null;
            }

            return $vendor;

        } else {

            $this->db->join(db_prefix() . 'countries', db_prefix() . 'countries.country_id = ' . db_prefix() . 'pur_vendor.country', 'left');
            $this->db->join(db_prefix() . 'pur_contacts', db_prefix() . 'pur_contacts.userid = ' . db_prefix() . 'pur_vendor.userid AND is_primary = 1', 'left');

            if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
                $this->db->where($where);
            }

            if (!has_permission('purchase_vendors', '', 'view') && is_staff_logged_in()) {
                $this->db->where(
                    db_prefix() . 'pur_vendor.userid IN (SELECT vendor_id FROM '
                    . db_prefix() . 'pur_vendor_admin WHERE staff_id=' . get_staff_user_id() . ')'
                );
            }

            $this->db->order_by('company', 'asc');

            return $this->db->get(db_prefix() . 'pur_vendor')->result_array();
        }
    }

    public function get_taxes_dropdown_template($name, $taxname, $type = '', $item_key = '', $is_edit = false, $manual = false)
    {
        if ($taxname != '' && !is_array($taxname)) {
            $taxname = explode(',', $taxname);
        }

        if ($manual == true) {
            if (is_array($taxname) || strpos($taxname, '+') !== false) {
                if (!is_array($taxname)) {
                    $__tax = explode('+', $taxname);
                } else {
                    $__tax = $taxname;
                }
                $taxname = [];
                foreach ($__tax as $t) {
                    $tax_array = explode('|', $t);
                    if (isset($tax_array[0]) && isset($tax_array[1])) {
                        array_push($taxname, $tax_array[0] . '|' . $tax_array[1]);
                    }
                }
            } else {
                $tax_array = explode('|', $taxname);
                if (isset($tax_array[0]) && isset($tax_array[1])) {
                    $tax = get_tax_by_name($tax_array[0]);
                    if ($tax) {
                        $taxname = $tax->name . '|' . $tax->taxrate;
                    }
                }
            }
        }

        $this->load->model('taxes_model');
        $taxes = $this->taxes_model->get();
        $i = 0;
        foreach ($taxes as $tax) {
            unset($taxes[$i]['id']);
            $taxes[$i]['name'] = $tax['name'] . '|' . $tax['taxrate'];
            $i++;
        }

        if ($is_edit == true) {

            $func_taxes = 'get_' . $type . '_item_taxes';
            if (function_exists($func_taxes)) {
                $item_taxes = call_user_func($func_taxes, $item_key);
            }
            foreach ($item_taxes as $item_tax) {
                $new_tax = [];
                $new_tax['name'] = $item_tax['taxname'];
                $new_tax['taxrate'] = $item_tax['taxrate'];
                $taxes[] = $new_tax;
            }
        }

        if (is_array($taxname)) {
            foreach ($taxname as $tax) {
                if ((!is_array($tax) && $tax == '') || is_array($tax) && $tax['taxname'] == '') {
                    continue;
                }
                if (!value_exists_in_array_by_key($taxes, 'name', $tax)) {
                    if (!is_array($tax)) {
                        $tmp_taxname = $tax;
                        $tax_array = explode('|', $tax);
                    } else {
                        $tax_array = explode('|', $tax['taxname']);
                        $tmp_taxname = $tax['taxname'];
                        if ($tmp_taxname == '') {
                            continue;
                        }
                    }
                    $taxes[] = ['name' => $tmp_taxname, 'taxrate' => $tax_array[1]];
                }
            }
        }

        $taxes = $this->pur_uniqueByKey($taxes, 'name');

        $select = '<select class="selectpicker display-block taxes" data-width="100%" name="' . $name . '" multiple data-none-selected-text="' . _l('no_tax') . '">';

        foreach ($taxes as $tax) {
            $selected = '';
            if (is_array($taxname)) {
                foreach ($taxname as $_tax) {
                    if (is_array($_tax)) {
                        if ($_tax['taxname'] == $tax['name']) {
                            $selected = 'selected';
                        }
                    } else {
                        if ($_tax == $tax['name']) {
                            $selected = 'selected';
                        }
                    }
                }
            } else {
                if ($taxname == $tax['name']) {
                    $selected = 'selected';
                }
            }

            $select .= '<option value="' . $tax['name'] . '" ' . $selected . ' data-taxrate="' . $tax['taxrate'] . '" data-taxname="' . $tax['name'] . '" data-subtext="' . $tax['name'] . '">' . $tax['taxrate'] . '%</option>';
        }
        $select .= '</select>';

        return $select;
    }

    public function pur_get_tax_rate($taxname)
    {
        $tax_rate = 0;
        $tax_rate_str = '';
        $tax_id_str = '';
        $tax_name_str = '';
        if (is_array($taxname)) {
            foreach ($taxname as $key => $value) {
                $_tax = explode("|", $value);
                if (isset($_tax[1])) {
                    $tax_rate += (float) $_tax[1];
                    if (strlen($tax_rate_str) > 0) {
                        $tax_rate_str .= '|' . $_tax[1];
                    } else {
                        $tax_rate_str .= $_tax[1];
                    }

                    $this->db->where('name', $_tax[0]);
                    $taxes = $this->db->get(db_prefix() . 'taxes')->row();
                    if ($taxes) {
                        if (strlen($tax_id_str) > 0) {
                            $tax_id_str .= '|' . $taxes->id;
                        } else {
                            $tax_id_str .= $taxes->id;
                        }
                    }

                    if (strlen($tax_name_str) > 0) {
                        $tax_name_str .= '|' . $_tax[0];
                    } else {
                        $tax_name_str .= $_tax[0];
                    }
                }
            }
        }
        return ['tax_rate' => $tax_rate, 'tax_rate_str' => $tax_rate_str, 'tax_id_str' => $tax_id_str, 'tax_name_str' => $tax_name_str];
    }

    public function pur_uniqueByKey($array, $key)
    {
        $temp_array = [];
        $i = 0;
        $key_array = [];

        foreach ($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }

        return $temp_array;
    }

    public function get_pur_invoice_detail($pur_request)
    {
        $this->db->where('pur_invoice', $pur_request);
        $pur_invoice_details = $this->db->get(db_prefix() . 'alb_po_invoice_items')->result_array();

        foreach ($pur_invoice_details as $key => $detail) {
            $pur_invoice_details[$key]['discount_money'] = (float) $detail['discount_money'];
            $pur_invoice_details[$key]['into_money'] = (float) $detail['into_money'];
            $pur_invoice_details[$key]['total'] = (float) $detail['total'];
            $pur_invoice_details[$key]['total_money'] = (float) $detail['total_money'];
            $pur_invoice_details[$key]['unit_price'] = (float) $detail['unit_price'];
            $pur_invoice_details[$key]['tax_value'] = (float) $detail['tax_value'];
        }

        return $pur_invoice_details;
    }

    public function get_pur_order_approved_for_inv()
    {
        $this->db->where('approve_status', 2);
        $list_po = $this->db->get(db_prefix() . 'pur_orders')->result_array();
        $data_rs = [];
        if (count($list_po) > 0) {
            foreach ($list_po as $po) {
                $this->db->where('pur_order', $po['id']);
                $list_inv = $this->db->get(db_prefix() . 'alb_po_invoices')->result_array();
                $total_inv_value = 0;
                foreach ($list_inv as $inv) {
                    $total_inv_value += $inv['total'];
                }

                if ($total_inv_value < $po['total']) {
                    $data_rs[] = $po;
                }
            }
        }
        return $data_rs;
    }

    public function get_pur_invoice($id = '')
    {
        if ($id != '') {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'pur_invoices')->row();
        } else {
            return $this->db->get(db_prefix() . 'pur_invoices')->result_array();
        }
    }
    public function get_albania_approved()
    {
        $this->db->where('approve_status', 2);
        return $this->db->get(db_prefix() . 'pur_orders')->result_array();

    }
    public function pur_get_grouped($can_be = '', $search_all = false, $vendor = '')
    {
        $items = [];
        $this->db->order_by('name', 'asc');
        $groups = $this->db->get(db_prefix() . 'items_groups')->result_array();

        array_unshift($groups, [
            'id' => 0,
            'name' => '',
        ]);

        foreach ($groups as $group) {
            $this->db->select('*,' . db_prefix() . 'items_groups.name as group_name,' . db_prefix() . 'items.id as id');
            if (strlen($can_be) > 0) {
                $this->db->where($can_be, $can_be);
            }
            if (!$search_all) {
                $this->db->where(db_prefix() . 'items.id not in ( SELECT distinct parent_id from ' . db_prefix() . 'items WHERE parent_id is not null AND parent_id != "0" )');
            }
            if ($vendor != '') {
                $this->db->where('items.id in (SELECT items from ' . db_prefix() . 'pur_vendor_items WHERE vendor = ' . $vendor . ')');
            }

            $this->db->where('group_id', $group['id']);
            $this->db->where(db_prefix() . 'items.active', 1);
            $this->db->join(db_prefix() . 'items_groups', '' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id', 'left');
            $this->db->order_by('description', 'asc');

            $_items = $this->db->get(db_prefix() . 'items')->result_array();

            if (count($_items) > 0) {
                $items[$group['id']] = [];
                foreach ($_items as $i) {
                    array_push($items[$group['id']], $i);
                }
            }
        }

        return $items;
    }

    public function get_contract($id = '')
    {
        if ($id == '') {
            return $this->db->get(db_prefix() . 'pur_contracts')->result_array();
        } else {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'pur_contracts')->row();
        }
    }

    public function get_base_currency()
    {
        $this->db->where('isdefault', 1);

        return $this->db->get(db_prefix() . 'currencies')->row();
    }

    public function get_currencies($id = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            $currency = $this->db->get(db_prefix() . 'currencies')->row();
            $this->app_object_cache->set('currency-' . $currency->name, $currency);

            return $currency;
        } else {
            $currencies = $this->app_object_cache->get('currencies-data');

            if (!$currencies && !is_array($currencies)) {
                $currencies = $this->db->get(db_prefix() . 'currencies')->result_array();
                $this->app_object_cache->add('currencies-data', $currencies);
            }

            return $currencies;
        }
    }

    public function get_items_groups()
    {
        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'items_groups')->result_array();
    }

    public function get_items_grouped()
    {
        $items = [];
        $this->db->order_by('name', 'asc');
        $groups = $this->db->get(db_prefix() . 'items_groups')->result_array();

        array_unshift($groups, [
            'id' => 0,
            'name' => '',
        ]);

        foreach ($groups as $group) {
            $this->db->select('*,' . db_prefix() . 'items_groups.name as group_name,' . db_prefix() . 'items.id as id');
            $this->db->where('group_id', $group['id']);
            $this->db->where('group_item', 1);
            $this->db->join(db_prefix() . 'items_groups', '' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id', 'left');
            $this->db->order_by('description', 'asc');
            $_items = $this->db->get(db_prefix() . 'items')->result_array();
            if (count($_items) > 0) {
                $items[$group['id']] = [];
                foreach ($_items as $i) {
                    array_push($items[$group['id']], $i);
                }
            }
        }
        return $items;
    }
    public function get_taxes($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'taxes')->row();
        }
        $this->db->order_by('taxrate', 'ASC');
        return $this->db->get(db_prefix() . 'taxes')->result_array();
    }
    public function get_payment_modes($id = '', $where = [], $include_inactive = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'payment_modes')->row();
        }
        if (!empty($where)) {
            foreach ($where as $key => $value) {
                $this->db->where($key, $value);
            }
        }
        if ($include_inactive !== true) {
            $this->db->where('active', 1);
        }
        return $this->db->get(db_prefix() . 'payment_modes')->result_array();

    }
    public function update_invoice($data, $id)
    {
        $original_invoice = $this->get_invoice($id);
        $updated = false;

        if (isset($data['number'])) {
            $data['number'] = trim($data['number']);
        }

        $original_number_formatted = format_alb_invoice_number_custom($id);
        $original_number = $original_invoice->number;
        $save_and_send = isset($data['save_and_send']);
        $cancel_merged_invoices = isset($data['cancel_merged_invoices']);
        $invoices_to_merge = isset($data['invoices_to_merge']) ? $data['invoices_to_merge'] : [];
        $billed_tasks = isset($data['billed_tasks']) ? $data['billed_tasks'] : [];
        $billed_expenses = isset($data['billed_expenses'])
            ? array_map('unserialize', array_unique(array_map('serialize', $data['billed_expenses'])))
            : [];

        $data['cancel_overdue_reminders'] = isset($data['cancel_overdue_reminders']) ? 1 : 0;
        $data['cycles'] = !isset($data['cycles']) ? 0 : $data['cycles'];
        $data['allowed_payment_modes'] = isset($data['allowed_payment_modes'])
            ? serialize($data['allowed_payment_modes'])
            : serialize([]);


        if (isset($data['recurring'])) {
            if ($data['recurring'] == 'custom') {
                $data['recurring_type'] = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
                $data['recurring'] = $data['repeat_every_custom'];
            } else {
                $data['recurring_type'] = null;
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['custom_recurring'] = 0;
            $data['recurring'] = 0;
            $data['recurring_type'] = null;
        }

        if ($original_invoice->recurring != 0 && $data['recurring'] == 0) {
            $data['cycles'] = 0;
            $data['total_cycles'] = 0;
            $data['last_recurring_date'] = null;
        }


        $data = $this->map_shipping_columns($data);

        $data['billing_street'] = nl2br(trim($data['billing_street']));
        if (!empty($data['shipping_street'])) {
            $data['shipping_street'] = nl2br(trim($data['shipping_street']));
        }

        $data['duedate'] = (isset($data['duedate']) && empty($data['duedate'])) ? null : $data['duedate'];

        // Map form fields to table columns (form sends grand_total, total_mn, or subtotal, invoice_amount)
        if (isset($data['total_mn'])) {
            $data['subtotal'] = $data['total_mn'];
            unset($data['total_mn']);
        }
        if (isset($data['grand_total'])) {
            $data['total'] = $data['grand_total'];
            if ($this->db->field_exists('invoice_amount', db_prefix() . 'alb_invoices')) {
                $data['invoice_amount'] = $data['grand_total'];
            }
            unset($data['grand_total']);
        } elseif (isset($data['invoice_amount'])) {
            $data['total'] = $data['invoice_amount'];
            if ($this->db->field_exists('invoice_amount', db_prefix() . 'alb_invoices')) {
                $data['invoice_amount'] = $data['total'];
            }
        }
        if (isset($data['invoice_currency'])) {
            $data['currency'] = $data['invoice_currency'];
            if (!$this->db->field_exists('invoice_currency', db_prefix() . 'alb_invoices')) {
                unset($data['invoice_currency']);
            }
        }

        $items = $data['items'] ?? [];
        $newitems = $data['newitems'] ?? [];


        if (handle_custom_fields_post($id, $custom_fields = $data['custom_fields'] ?? [])) {
            $updated = true;
        }

        if (array_key_exists('tags', $data)) {
            if (handle_tags_save($tags = $data['tags'], $id, 'alb_invoices')) {
                $updated = true;
            }
        }

        unset($data['items'], $data['newitems'], $data['custom_fields'], $data['tags']);


        $hookData = [
            'id' => $id,
            'data' => $data,
            'items' => $items,
            'new_items' => $newitems,
            'removed_items' => $data['removed_items'] ?? [],
            'custom_fields' => $custom_fields ?? [],
            'billed_tasks' => $billed_tasks,
            'invoices_to_merge' => $invoices_to_merge,
            'cancel_merged_invoices' => $cancel_merged_invoices,
            'tags' => $tags ?? null,
            'billed_expenses' => $billed_expenses,
        ];

        $hook = hooks()->apply_filters('before_update_invoice', $hookData, $id);

        $data = $hook['data'];
        $items = $hook['items'];
        $newitems = $hook['new_items'];
        $removed_items = $hook['removed_items'];
        $custom_fields = $hook['custom_fields'];
        $billed_tasks = $hook['billed_tasks'];
        $invoices_to_merge = $hook['invoices_to_merge'];
        $tags = $hook['tags'];
        $billed_expenses = $hook['billed_expenses'];


        foreach ($billed_tasks as $tasks) {
            foreach ($tasks as $taskId) {

                $this->db->select('status')->where('id', $taskId);
                $taskStatus = $this->db->get('tasks')->row()->status;

                $taskUpdateData = ['billed' => 1, 'invoice_id' => $id];

                if ($taskStatus != Tasks_model::STATUS_COMPLETE) {
                    $taskUpdateData['status'] = Tasks_model::STATUS_COMPLETE;
                    $taskUpdateData['datefinished'] = date('Y-m-d H:i:s');
                }

                $this->db->where('id', $taskId)->update('tasks', $taskUpdateData);
            }
        }


        foreach ($billed_expenses as $val) {
            foreach ($val as $expenseId) {
                $this->db->where('id', $expenseId)->update('expenses', [
                    'invoiceid' => $id,
                ]);
            }
        }

        if ($this->remove_items($removed_items, $id)) {
            $updated = true;
        }

        unset($data['removed_items']);


        $this->db->where('id', $id)->update(db_prefix() . 'alb_invoices', $data);

        $this->save_formatted_numbers($id);

        if ($this->db->affected_rows() > 0) {
            $updated = true;
        }

        if ($this->save_items($items, $id)) {
            $updated = true;
        }

        if ($this->add_new_items($newitems, $billed_tasks, $billed_expenses, $id)) {
            $updated = true;
        }

        if ($this->merge_invoices($invoices_to_merge, $id, $cancel_merged_invoices)) {
            $updated = true;
        }

        if ($updated) {
            update_sales_total_tax_column($id, 'alb_invoices', db_prefix() . 'alb_invoices');
            update_invoice_status($id);
        }

        if ($save_and_send === true) {
            $this->send_invoice_to_client($id, '', true, '', true);
        }

        hooks()->do_action('invoice_updated', array_merge($hookData, ['updated' => &$updated]));

        return $updated;
    }

    public function add_invoice($data)
    {

        // Get ALB-specific prefix and next number
        $this->db->where('option_name', 'alb_inv_prefix');
        $prefix_row = $this->db->get(db_prefix() . 'purchase_option')->row();
        $data['prefix'] = $prefix_row ? $prefix_row->option_val : 'ALB-INV-';

        $data['number_format'] = 1; // ALB uses simple format
        $data['formatted_number '] = $data['prefix'] . $data['number'];
        $data['datecreated'] = date('Y-m-d H:i:s');

        $save_and_send = isset($data['save_and_send']);

        $data['addedfrom'] = !DEFINED('CRON') ? get_staff_user_id() : 0;

        $data['cancel_overdue_reminders'] = isset($data['cancel_overdue_reminders']) ? 1 : 0;

        $data['allowed_payment_modes'] = isset($data['allowed_payment_modes']) ? serialize($data['allowed_payment_modes']) : serialize([]);

        $billed_tasks = isset($data['billed_tasks']) ? array_map('unserialize', array_unique(array_map('serialize', $data['billed_tasks']))) : [];

        $billed_expenses = isset($data['billed_expenses']) ? array_map('unserialize', array_unique(array_map('serialize', $data['billed_expenses']))) : [];

        $invoices_to_merge = isset($data['invoices_to_merge']) ? $data['invoices_to_merge'] : [];

        $cancel_merged_invoices = isset($data['cancel_merged_invoices']);

        $tags = isset($data['tags']) ? $data['tags'] : '';

        if (isset($data['save_as_draft'])) {
            $data['status'] = self::STATUS_DRAFT;
            unset($data['save_as_draft']);
        } elseif (isset($data['save_and_send_later'])) {
            $data['status'] = self::STATUS_DRAFT;
            unset($data['save_and_send_later']);
        }

        if (isset($data['recurring'])) {
            if ($data['recurring'] == 'custom') {
                $data['recurring_type'] = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
                $data['recurring'] = $data['repeat_every_custom'];
            }
        } else {
            $data['custom_recurring'] = 0;
            $data['recurring'] = 0;
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['hash'] = app_generate_hash();

        $items = [];

        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        $data = $this->map_shipping_columns($data, $expense);

        if (isset($data['shipping_street'])) {
            $data['shipping_street'] = trim($data['shipping_street']);
            $data['shipping_street'] = nl2br($data['shipping_street']);
        }

        if (isset($data['billing_street'])) {
            $data['billing_street'] = trim($data['billing_street']);
            $data['billing_street'] = nl2br($data['billing_street']);
        }
        $is_new_invoice = !isset($data['id']) || empty($data['id']);

        if (isset($data['status']) && $data['status'] == self::STATUS_DRAFT) {
            // For draft invoices, get the next draft number and increment it
            $this->db->where('option_name', 'alb_next_draft_number');
            $number_row = $this->db->get(db_prefix() . 'purchase_option')->row();
            $next_draft_number = $number_row ? (int) $number_row->option_val : 1;
            $data['number'] = $next_draft_number;

            // Increment the next draft number
            $this->increment_next_draft_number();
        } else {
            if ($is_new_invoice) {
                $this->db->where('option_name', 'alb_next_inv_number');
                $number_row = $this->db->get(db_prefix() . 'purchase_option')->row();
                $next_number = $number_row ? (int) $number_row->option_val : 1;
                $data['number'] = $next_number;

                $this->increment_next_number();
            }
        }

        $data['duedate'] = isset($data['duedate']) && empty($data['duedate']) ? null : $data['duedate'];

        // Map form fields to table columns (form sends grand_total, total_mn, or subtotal, invoice_amount)
        if (isset($data['total_mn'])) {
            $data['subtotal'] = $data['total_mn'];
            unset($data['total_mn']);
        }
        if (isset($data['grand_total'])) {
            $data['total'] = $data['grand_total'];
            if ($this->db->field_exists('invoice_amount', db_prefix() . 'alb_invoices')) {
                $data['invoice_amount'] = $data['grand_total'];
            }
            unset($data['grand_total']);
        } elseif (isset($data['invoice_amount'])) {
            $data['total'] = $data['invoice_amount'];
            if ($this->db->field_exists('invoice_amount', db_prefix() . 'alb_invoices')) {
                $data['invoice_amount'] = $data['total'];
            }
        }
        if (isset($data['invoice_currency'])) {
            $data['currency'] = $data['invoice_currency'];
            if (!$this->db->field_exists('invoice_currency', db_prefix() . 'alb_invoices')) {
                unset($data['invoice_currency']);
            }
        }

        unset($data['save_and_send'], $data['save_and_send_later'], $data['save_as_draft'], $data['save_and_record_payment']);

        $hook = hooks()->apply_filters('before_invoice_added', [
            'data' => $data,
            'items' => $items,
        ]);

        $data = $hook['data'];
        $items = $hook['items'];
        $this->db->insert(db_prefix() . 'alb_invoices', $data);
        
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
          
            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

  
        
            foreach ($items as $key => $item) {
                if ($itemid = add_new_sales_item_post($item, $insert_id, 'alb_invoice')) {
                    if (isset($billed_tasks[$key])) {
                        foreach ($billed_tasks[$key] as $_task_id) {
                            $this->db->insert(db_prefix() . 'related_items', [
                                'item_id' => $itemid,
                                'rel_id' => $_task_id,
                                'rel_type' => 'task',
                            ]);
                        }
                    } elseif (isset($billed_expenses[$key])) {
                        foreach ($billed_expenses[$key] as $_expense_id) {
                            $this->db->insert(db_prefix() . 'related_items', [
                                'item_id' => $itemid,
                                'rel_id' => $_expense_id,
                                'rel_type' => 'expense',
                            ]);
                        }
                    }
                    _maybe_insert_post_item_tax($itemid, $item, $insert_id, 'alb_invoices');
                }
            }

            update_sales_total_tax_column($insert_id, 'alb_invoices', db_prefix() . 'alb_invoices');

            if ($save_and_send === true) {
                $this->send_invoice_to_client($insert_id, '', true, '', true);
            }

            return $insert_id;
        }

        return false;
    }

    /**
     * Send ALB invoice to client via email (Save & Send).
     * Uses public link alb_invoice/{id}/{hash}.
     *
     * @param int     $id        ALB invoice ID
     * @param string  $template  template name (unused, kept for signature)
     * @param bool    $attachpdf attach PDF
     * @param string  $cc        CC emails
     * @param bool    $manually  manual send (Save & Send)
     * @return bool
     */
    public function send_invoice_to_client($id, $template = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $invoice = $this->get_invoice($id);
        if (!$invoice || empty($invoice->hash)) {
            return false;
        }

        $template_name = 'Alb_invoice_send_to_customer';

        $send_to = [];
        $this->load->model('clients_model');
        // When sent from modal (not manually Save & Send), use sent_to[] from form
        if (!$manually && $this->input->post('sent_to') && is_array($this->input->post('sent_to'))) {
            $send_to = array_filter($this->input->post('sent_to'));
        } else {
            $contacts = $this->clients_model->get_contacts($invoice->clientid, ['active' => 1, 'invoice_emails' => 1]);
            foreach ($contacts as $contact) {
                $send_to[] = $contact['id'];
            }
        }

        // Allow attach_pdf from form when sent from modal
        if (!$manually && $this->input->post('attach_pdf') !== null) {
            $attachpdf = (bool) $this->input->post('attach_pdf');
        }
        if (!$manually && $this->input->post('cc') !== null) {
            $cc = $this->input->post('cc');
        }

        if (empty($send_to)) {
            return false;
        }

        $invoice_number = function_exists('format_alb_invoice_number_custom') ? format_alb_invoice_number_custom($invoice) : ('#' . $invoice->id);
        $attach          = null;
        if ($attachpdf) {
            set_mailing_constant();
            try {
                $pdf   = app_pdf('alb_invoice', module_dir_path(D3A_ALBANIA_MODULE_NAME, 'libraries/pdf/Alb_invoice_pdf'), $invoice);
                $attach = $pdf->Output($invoice_number . '.pdf', 'S');
            } catch (Exception $e) {
                return false;
            }
        }

        $emails_sent = [];
        foreach ($send_to as $contact_id) {
            if (empty($contact_id)) {
                continue;
            }
            $contact = $this->clients_model->get_contact($contact_id);
            if (!$contact || empty($contact->email)) {
                continue;
            }

            try {
                $instance = mail_template($template_name, D3A_ALBANIA_MODULE_NAME, $invoice, $contact, $cc);
                if (!$instance) {
                    continue;
                }
                if ($attachpdf && $attach) {
                    $instance->add_attachment([
                        'attachment' => $attach,
                        'filename'  => str_replace('/', '-', $invoice_number . '.pdf'),
                        'type'      => 'application/pdf',
                    ]);
                }
                if ($instance->send()) {
                    $emails_sent[] = $contact->email;
                }
            } catch (Exception $e) {
                // Continue to next contact
            }
        }

        if (count($emails_sent) > 0) {
            $this->set_alb_invoice_sent($id, $emails_sent);
            return true;
        }
        return false;
    }

    /**
     * Mark ALB invoice as sent
     */
    protected function set_alb_invoice_sent($id, $emails_sent = [])
    {
        if (!$this->db->field_exists('sent', db_prefix() . 'alb_invoices')) {
            return false;
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'alb_invoices', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affected_rows() > 0;
    }

    public function get_attachments($invoiceid, $id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $invoiceid);
        }

        $this->db->where('rel_type', 'alb_invoices');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     * Get currency object for an ALB invoice (for PDF/formatting).
     *
     * @param object $invoice ALB invoice object
     * @return object
     */
    public function get_invoice_currency($invoice)
    {
        $this->load->model('currencies_model');
        $currency_id = isset($invoice->invoice_currency) ? $invoice->invoice_currency : (isset($invoice->currency) ? $invoice->currency : 0);
        $base_currency = $this->currencies_model->get_base_currency();
        if ($currency_id != 0 && $currency_id != '') {
            $curr = $this->currencies_model->get($currency_id);
            if ($curr) {
                $base_currency = $curr;
            }
        }
        return $base_currency;
    }

    /**
     * Get alb invoice items formatted for preview table (same structure as pur_invoice_detail)
     *
     * @param int $id ALB invoice ID
     * @param object $base_currency Currency object for formatting
     * @return array
     */
    public function get_alb_invoice_items_for_preview($id, $base_currency)
    {
        $items = get_items_by_type('alb_invoice', $id);
        $alb_invoices_items = get_items_by_type('alb_invoices', $id);
        if (!empty($alb_invoices_items)) {
            $items = array_merge($items ?: [], $alb_invoices_items);
            usort($items, function ($a, $b) {
                $orderA = isset($a['item_order']) ? (int) $a['item_order'] : 0;
                $orderB = isset($b['item_order']) ? (int) $b['item_order'] : 0;
                return $orderA - $orderB;
            });
        }
        if (empty($items)) {
            $items = [];
        }
        $currency_symbol = $base_currency ? $base_currency->symbol : get_base_currency()->symbol;
        $detail = [];

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 1);
            $rate = (float) ($item['rate'] ?? 0);
            $amount = $rate;

            $detail[] = [
                'item_group' => $item['description'] ?? ($item['long_description'] ?? ''),
                'qty' => $qty,
                'unit' => $item['unit'] ?? '-',
                'price' => $rate,
                'amount' => $amount,
            ];
        }

        return $detail;
    }

    /**
     * Get ALB invoice HTML for PDF generation
     *
     * @param int $id ALB invoice ID
     * @return string HTML for PDF
     */
    public function get_alb_invoice_pdf_html($id)
    {
        $invoice = $this->get_invoice($id);
        if (!$invoice) {
            return '';
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

        $invoice_detail = $this->get_alb_invoice_items_for_preview($id, $base_currency);
        $invoice_number = format_alb_invoice_number_custom($invoice);
        $invoice_total = isset($invoice->invoice_amount) ? (float) $invoice->invoice_amount : (isset($invoice->total) ? (float) $invoice->total : 0);

        $client_name = '';
        $bill_to = '-';
        if (!empty($invoice->client)) {
            $client_name = $invoice->client->company ?? ($invoice->deleted_customer_name ?? '');
            $bill_to = format_customer_info($invoice, 'invoice', 'billing');
        }

        $currency_symbol = $base_currency ? ($base_currency->symbol ?? $base_currency->name) : '';
        $organization_info = '<div style="color:#424242;">' . (function_exists('format_alb_organization_info') ? format_alb_organization_info() : format_organization_info()) . '</div>';

        $right_info = 'Invoice No: <b style="color:#4e4e4e;"># ' . $invoice_number . '</b><br>';
        $right_info .= '<b>' . _l('invoice_bill_to') . ':</b><div style="color:#424242;">' . $bill_to . '</div>';
        $right_info .= _l('invoice_data_date') . ' ' . _d($invoice->date) . '<br>';
        if (!empty($invoice->duedate)) {
            $right_info .= _l('invoice_data_duedate') . ' ' . _d($invoice->duedate) . '<br>';
        }

        $alb_company_name = get_option('alb_invoice_company_name') ?: 'D3A TRADING COMPANY LIMITED';
        $html = '<table class="table" style="width:100%; border-collapse:collapse;"><tbody>';
        $html .= '<tr><td align="center" colspan="2" style="padding-bottom:8px;">' . alb_pdf_logo_url() . '</td></tr>';
        $html .= '<tr><td colspan="2" align="center"><b style="font-size:20px; font-weight:600;">' . htmlspecialchars($alb_company_name) . '</b><br><b style="font-size:20px;">' . _l('invoice_pdf_heading') . '</b></td></tr>';
        $html .= '<tr><td style="width:50%; vertical-align:top; padding-right:15px;">' . $organization_info . '</td>';
        $html .= '<td style="width:50%; text-align:right; vertical-align:top;">' . $right_info . '</td></tr>';
        $html .= '</tbody></table><br><br>';

        $html .= '<table class="table" style="width:100%; border-collapse:collapse;">
<thead><tr style="background:#424242; color:#fff;">
<th style="border:1px solid #333; padding:6px; font-size:10px; text-align:center;">' . _l('marks_and_no') . '</th>
<th style="border:1px solid #333; padding:6px; font-size:10px; text-align:left;">' . _l('invoice_table_item_description') . '</th>
<th style="border:1px solid #333; padding:6px; font-size:10px; text-align:center;">' . _l('alb_invoice_table_quantity') . '</th>
<th style="border:1px solid #333; padding:6px; font-size:10px; text-align:center;">' . _l('unit') . '</th>
<th style="border:1px solid #333; padding:6px; font-size:10px; text-align:center;">' . _l('currency') . '</th>
<th style="border:1px solid #333; padding:6px; font-size:10px; text-align:right;">' . _l('invoice_table_amount_heading') . '</th>
</tr></thead><tbody>';

        $sum_amount = 0;
        $sum_qty = 0;
        foreach ($invoice_detail as $es) {
            $amount = (float) ($es['amount'] ?? 0);
            $qty = (float) ($es['qty'] ?? 0);
            $sum_amount += $amount;
            $sum_qty += $qty;
            $marks_and_no = $client_name ?: '-';
            $html .= '<tr nobr="true">';
            $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:center;">' . htmlspecialchars($marks_and_no) . '</td>';
            $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:left;">' . htmlspecialchars($es['item_group'] ?? '') . '</td>';
            $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:center;">' . htmlspecialchars((string) ($es['qty'] ?? '')) . '</td>';
            $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:center;">' . htmlspecialchars($es['unit'] ?? '-') . '</td>';
            $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:center;">' . $currency_symbol . '</td>';
            $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:right;">' . app_format_money($amount, $base_currency) . '</td>';
            $html .= '</tr>';
        }
        $html .= '<tr style="font-weight:bold;">';
        $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:center;"></td>';
        $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:left;">Total</td>';
        $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:center;">' . $sum_qty . '</td>';
        $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:center;"></td>';
        $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:center;"></td>';
        $html .= '<td style="border:1px solid #ddd; padding:6px; text-align:right;">' . app_format_money($sum_amount, $base_currency) . '</td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        $html .= '<br>';

        $bank_info = get_option('alb_bank_information');
        if (empty($bank_info)) {
            $bank_info = $invoice->terms ?? '';
        }
        if (!empty($bank_info)) {
            $bank_info = clear_textarea_breaks($bank_info, "\n");
            $bank_info = _info_format_replace('company_name', get_option('alb_invoice_company_name') ?: '', $bank_info);
            $bank_info_html = nl2br(htmlspecialchars($bank_info, ENT_QUOTES, 'UTF-8'));
            $html .= '<br><div><strong>' . _l('alb_bank_information') . ':</strong><br>' . $bank_info_html . '</div>';
        }

        return $html;
    }

    public function increment_next_number()
    {
        $this->db->where('option_name', 'alb_next_inv_number');
        $number_row = $this->db->get(db_prefix() . 'purchase_option')->row();
        $current_number = $number_row ? (int) $number_row->option_val : 1;
        $next_number = $current_number + 1;

        $this->db->where('option_name', 'alb_next_inv_number');
        $result = $this->db->update(db_prefix() . 'purchase_option', ['option_val' => $next_number]);

        if ($result) {
            log_activity('ALB Invoice Next Number Incremented to ' . $next_number);
        }

        return $next_number;
    }

    public function increment_next_draft_number()
    {
        $this->db->where('option_name', 'alb_next_draft_number');
        $number_row = $this->db->get(db_prefix() . 'purchase_option')->row();
        $current_number = $number_row ? (int) $number_row->option_val : 1;
        $next_number = $current_number + 1;

        $this->db->where('option_name', 'alb_next_draft_number');
        $result = $this->db->update(db_prefix() . 'purchase_option', ['option_val' => $next_number]);

        if ($result) {
            log_activity('ALB Draft Next Number Incremented to ' . $next_number);
        }

        return $next_number;
    }


    public function get_invoice_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'itemable')->row();
    }

    protected function remove_items($removed_items, $id)
    {
        $updated = false;
        foreach ($removed_items as $itemId) {
            $original_item = $this->get_invoice_item($itemId);

            if (handle_removed_sales_item_post($itemId, 'alb_invoices')) {
                $updated = true;

                $this->log_invoice_activity($id, 'invoice_estimate_activity_removed_item', false, serialize([
                    $original_item->description,
                ]));

                $this->db->where('item_id', $original_item->id);
                $related_items = $this->db->get('related_items')->result_array();

                foreach ($related_items as $rel_item) {
                    if ($rel_item['rel_type'] == 'task') {
                        $this->db->where('id', $rel_item['rel_id'])->update('tasks', [
                            'invoice_id' => null,
                            'billed' => 0,
                        ]);
                    } elseif ($rel_item['rel_type'] == 'expense') {
                        $this->db->where('id', $rel_item['rel_id'])->update('expenses', [
                            'invoiceid' => null,
                        ]);
                    }
                }
                $this->db->where('item_id', $original_item->id)->delete('related_items');
            }
        }

        return $updated;
    }

    public function save_formatted_numbers($id)
    {
        $formattedNumber = format_alb_invoice_number_custom($id);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'alb_invoices', ['formatted_number' => $formattedNumber]);
    }

    public function log_invoice_activity($id, $description = '', $client = false, $additional_data = '')
    {
        if (DEFINED('CRON')) {
            $staffid = '[CRON]';
            $full_name = '[CRON]';
        } elseif (defined('STRIPE_SUBSCRIPTION_INVOICE')) {
            $staffid = null;
            $full_name = '[Stripe]';
        } elseif ($client == true) {
            $staffid = null;
            $full_name = '';
        } else {
            $staffid = get_staff_user_id();
            $full_name = get_staff_full_name(get_staff_user_id());
        }
        $this->db->insert(db_prefix() . 'sales_activity', [
            'description' => $description,
            'date' => date('Y-m-d H:i:s'),
            'rel_id' => $id,
            'rel_type' => 'alb_invoices',
            'staffid' => $staffid,
            'full_name' => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    protected function save_items($items, $id)
    {
        $updated = false;

        foreach ($items as $key => $item) {
            if (update_sales_item_post($item['itemid'], $item)) {
                $updated = true;
            }

            if (isset($item['custom_fields'])) {
                if (handle_custom_fields_post($item['itemid'], $item['custom_fields'])) {
                    $updated = true;
                }
            }

            if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                if (delete_taxes_from_item($item['itemid'], 'alb_invoices')) {
                    $updated = true;
                }
            } else {
                $item_taxes = get_invoice_item_taxes($item['itemid']);
                $_item_taxes_names = [];
                foreach ($item_taxes as $_item_tax) {
                    array_push($_item_taxes_names, $_item_tax['taxname']);
                }
                $i = 0;
                foreach ($_item_taxes_names as $_item_tax) {
                    if (!in_array($_item_tax, $item['taxname'])) {
                        $this->db->where('id', $item_taxes[$i]['id'])
                            ->delete(db_prefix() . 'item_tax');
                        if ($this->db->affected_rows() > 0) {
                            $updated = true;
                        }
                    }
                    $i++;
                }
                if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'alb_invoices')) {
                    $updated = true;
                }
            }
        }

        return $updated;
    }

    /**
     * Add new items to invoice
     * @param array $newitems
     * @param array $billed_tasks
     * @param array $billed_expenses
     * @param int $id
     * @return bool
     */
    protected function add_new_items($newitems, $billed_tasks, $billed_expenses, $id)
    {
        $updated = false;

        foreach ($newitems as $key => $item) {
            if ($new_item_added = add_new_sales_item_post($item, $id, 'alb_invoices')) {
                if (isset($billed_tasks[$key])) {
                    foreach ($billed_tasks[$key] as $_task_id) {
                        $this->db->insert(db_prefix() . 'related_items', [
                            'item_id' => $new_item_added,
                            'rel_id' => $_task_id,
                            'rel_type' => 'task',
                        ]);
                    }
                } elseif (isset($billed_expenses[$key])) {
                    foreach ($billed_expenses[$key] as $_expense_id) {
                        $this->db->insert(db_prefix() . 'related_items', [
                            'item_id' => $new_item_added,
                            'rel_id' => $_expense_id,
                            'rel_type' => 'expense',
                        ]);
                    }
                }
                _maybe_insert_post_item_tax($new_item_added, $item, $id, 'alb_invoices');
                $updated = true;
            }
        }

        return $updated;
    }

    protected function merge_invoices($invoices_to_merge, $id, $cancel_merged_invoices)
    {
        $updated = false;

        foreach ($invoices_to_merge as $m) {
            $merged = false;
            $or_merge = $this->get_invoice($m);
            if ($cancel_merged_invoices == false) {
                if ($this->delete($m, true)) {
                    $merged = true;
                }
            } else {
                if ($this->mark_as_cancelled($m)) {
                    $merged = true;
                    $admin_note = $or_merge->adminnote;
                    $note = 'Merged into invoice ' . format_alb_invoice_number_custom($id);
                    if ($admin_note != '') {
                        $admin_note .= "\n\r" . $note;
                    } else {
                        $admin_note = $note;
                    }
                    $this->db->where('id', $m);
                    $this->db->update(db_prefix() . 'alb_invoices', [
                        'adminnote' => $admin_note,
                    ]);
                    // Delete the old items related from the merged invoice
                    foreach ($or_merge->items as $or_merge_item) {
                        $this->db->where('item_id', $or_merge_item['id']);
                        $this->db->delete(db_prefix() . 'related_items');
                    }
                }
            }

            if ($merged) {
                $this->db->where('invoiceid', $or_merge->id);
                $is_expense_invoice = $this->db->get(db_prefix() . 'expenses')->row();
                if ($is_expense_invoice) {
                    $this->db->where('id', $is_expense_invoice->id);
                    $this->db->update(db_prefix() . 'expenses', [
                        'invoiceid' => $id,
                    ]);
                }
                if (
                    total_rows(db_prefix() . 'estimates', [
                        'invoiceid' => $or_merge->id,
                    ]) > 0
                ) {
                    $this->db->where('invoiceid', $or_merge->id);
                    $estimate = $this->db->get(db_prefix() . 'estimates')->row();
                    $this->db->where('id', $estimate->id);
                    $this->db->update(db_prefix() . 'estimates', [
                        'invoiceid' => $id,
                    ]);
                } elseif (
                    total_rows(db_prefix() . 'proposals', [
                        'invoice_id' => $or_merge->id,
                    ]) > 0
                ) {
                    $this->db->where('invoice_id', $or_merge->id);
                    $proposal = $this->db->get(db_prefix() . 'proposals')->row();
                    $this->db->where('id', $proposal->id);
                    $this->db->update(db_prefix() . 'proposals', [
                        'invoice_id' => $id,
                    ]);
                }
                $updated = true;
            }
        }

        return $updated;
    }

    private function map_shipping_columns($data, $expense = false)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_invoice'] = 1;
            $data['include_shipping'] = 0;
        } else {
            // We dont need to overwrite to 1 unless its coming from the main function add
            if (!DEFINED('CRON') && $expense == false) {
                $data['include_shipping'] = 1;
                // set by default for the next time to be checked
                if (isset($data['show_shipping_on_invoice']) && ($data['show_shipping_on_invoice'] == 1 || $data['show_shipping_on_invoice'] == 'on')) {
                    $data['show_shipping_on_invoice'] = 1;
                } else {
                    $data['show_shipping_on_invoice'] = 0;
                }
            }
            // else its just like they are passed
        }

        return $data;
    }

    public function mark_as_cancelled($id)
    {
        $this->db->where('id', $id);
        $result = $this->db->update(db_prefix() . 'alb_invoices', [
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);

        if ($result) {
            log_activity('ALB Invoice Marked as Cancelled [ID: ' . $id . ']');
            return true;
        }

        return false;
    }

    public function is_draft($id)
    {
        $this->db->where('id', $id);
        $invoice = $this->db->get(db_prefix() . 'alb_invoices')->row();
        return $invoice && $invoice->status == self::STATUS_DRAFT;
    }

    public function delete($id, $force = false)
    {
        if (!$force && $this->is_draft($id)) {
            return $this->mark_as_cancelled($id);
        }
        $invoice = $this->get_invoice($id);
        if (!$invoice) {
            return false;
        }

        // Delete items (alb_invoice_items table)
        $this->db->where('invoice_id', $id);
        $this->db->delete(db_prefix() . 'alb_invoice_items');

        // Delete items from itemable (rel_type alb_invoices)
        $items = get_items_by_type('alb_invoices', $id);
        if (!empty($items)) {
            foreach ($items as $item) {
                if (!empty($item['id'])) {
                    if (function_exists('delete_taxes_from_item')) {
                        delete_taxes_from_item($item['id'], 'alb_invoices');
                    }
                }
            }
        }
        $this->db->where('relid IN (SELECT id FROM ' . db_prefix() . 'itemable WHERE rel_type="alb_invoices" AND rel_id=' . (int) $id . ')');
        $this->db->where('fieldto', 'items');
        $this->db->delete(db_prefix() . 'customfieldsvalues');
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'alb_invoices');
        $this->db->delete(db_prefix() . 'itemable');

        // Delete payments
        $this->db->where('invoiceid', $id);
        $this->db->delete(db_prefix() . 'alb_payments');

        // Delete attachments (files)
        $attachments = $this->get_attachments($id);
        foreach ($attachments as $attachment) {
            $attachment = (object) $attachment;
            if (empty($attachment->external) && function_exists('get_upload_path_by_type')) {
                $path = get_upload_path_by_type('alb_invoices') . $attachment->rel_id . '/' . $attachment->file_name;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'alb_invoices');
        $this->db->delete(db_prefix() . 'files');
        if (is_dir(get_upload_path_by_type('alb_invoices') . $id)) {
            $other = list_files(get_upload_path_by_type('alb_invoices') . $id);
            if (empty($other) || (count($other) == 1 && strpos($other[0], 'index.html') !== false)) {
                delete_dir(get_upload_path_by_type('alb_invoices') . $id);
            }
        }

        // Delete notes, reminders, activity
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'alb_invoice');
        $this->db->delete(db_prefix() . 'notes');

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'alb_invoice');
        $this->db->delete(db_prefix() . 'reminders');

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'alb_invoices');
        $this->db->delete(db_prefix() . 'sales_activity');

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'alb_invoices');
        $this->db->delete(db_prefix() . 'taggables');

        if ($this->db->table_exists(db_prefix() . 'scheduled_emails')) {
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'alb_invoices');
            $this->db->delete(db_prefix() . 'scheduled_emails');
        }

        // Delete custom field values (invoice-level)
        $this->db->where('relid', $id);
        $this->db->where('fieldto', 'invoice');
        $this->db->delete(db_prefix() . 'customfieldsvalues');

        // Delete invoice
        $this->db->where('id', $id);
        $result = $this->db->delete(db_prefix() . 'alb_invoices');

        if ($result) {
            log_activity('ALB Invoice Deleted [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    public function get_invoice_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'alb_invoices');
        $this->db->order_by('date', 'asc');

        return $this->db->get(db_prefix() . 'sales_activity')->result_array();
    }

    public function check_for_merge_invoice($client_id, $current_invoice = '')
    {
        if ($current_invoice != '') {
            $this->db->select('status');
            $this->db->where('id', $current_invoice);
            $row = $this->db->get(db_prefix() . 'alb_invoices')->row();
            if ($row->status == 2 || $row->status == 3 || $row->status == 4) {
                return [];
            }
        }

        $statuses = [
            1,
            5,
            6
        ];
        $noPermissionsQuery = get_invoices_where_sql_for_staff(get_staff_user_id());
        $has_permission_view = staff_can('view', 'invoices');
        $this->db->select('id');
        $this->db->where('clientid', $client_id);
        $this->db->where('status IN (' . implode(', ', $statuses) . ')');
        if (!$has_permission_view) {
            $whereUser = $noPermissionsQuery;
            $this->db->where('(' . $whereUser . ')');
        }
        if ($current_invoice != '') {
            $this->db->where('id !=', $current_invoice);
        }

        $invoices = $this->db->get(db_prefix() . 'alb_invoices')->result_array();
        $invoices = hooks()->apply_filters('alb_invoices_ids_available_for_merging', $invoices);

        $_invoices = [];

        foreach ($invoices as $invoice) {
            $inv = $this->get_invoice($invoice['id']);
            if ($inv) {
                $_invoices[] = $inv;
            }
        }

        return $_invoices;
    }

    public function get_invoice_recurring_invoices($id)
    {
        $this->db->select('id');
        $this->db->where('is_recurring_from', $id);
        $invoices = $this->db->get(db_prefix() . 'alb_invoices')->result_array();
        $recurring_invoices = [];

        foreach ($invoices as $invoice) {
            $recurring_invoices[] = $this->get_invoice($invoice['id']);
        }

        return $recurring_invoices;
    }

    public function get_invoices_years()
    {
        $this->db->select('YEAR(date) as year', false);
        $this->db->from(db_prefix() . 'alb_invoices');
        $this->db->group_by('YEAR(date)');
        $this->db->order_by('YEAR(date)', 'DESC');
        $years = $this->db->get()->result_array();
        $i = 0;
        foreach ($years as $year) {
            $years[$i]['year'] = $year['year'];
            $i++;
        }
        return $years;
    }

    public function get_sale_agents()
    {
        $this->db->select('DISTINCT(sale_agent), tblstaff.firstname, tblstaff.lastname');
        $this->db->from(db_prefix() . 'alb_invoices');
        $this->db->join(db_prefix() . 'staff', 'tblstaff.staffid = ' . db_prefix() . 'alb_invoices.sale_agent', 'left');
        $this->db->where('sale_agent !=', 0);
        $this->db->order_by('firstname', 'ASC');
        $this->db->order_by('lastname', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_statuses()
    {
        return [
            [
                'id' => self::STATUS_UNPAID,
                'name' => _l('invoice_status_unpaid'),
                'color' => '#808080',
            ],
            [
                'id' => self::STATUS_PAID,
                'name' => _l('invoice_status_paid'),
                'color' => '#78CC78',
            ],
            [
                'id' => self::STATUS_PARTIALLY,
                'name' => _l('invoice_status_partially_paid'),
                'color' => '#ffd43b',
            ],
            [
                'id' => self::STATUS_OVERDUE,
                'name' => _l('invoice_status_overdue'),
                'color' => '#f14c4c',
            ],
            [
                'id' => self::STATUS_CANCELLED,
                'name' => _l('invoice_status_cancelled'),
                'color' => '#b7b7b7',
            ],
            [
                'id' => self::STATUS_DRAFT,
                'name' => _l('invoice_status_draft'),
                'color' => '#7e7e7e',
            ],
        ];
    }
    // Added by Arju
    public function get_applied_invoice_debits($invoice_id)
    {
        $this->db->order_by('date', 'desc');
        $this->db->where('invoice_id', $invoice_id);

        return $this->db->get(db_prefix() . 'pur_debits')->result_array();
    }
    public function total_remaining_debits_by_vendor($vendor_id, $currency)
    {
        $base_currency = get_base_currency_pur();
        if ($currency == 0) {
            $currency = $base_currency->id;
        }

        $this->db->select('total,id');
        $this->db->where('vendorid', $vendor_id);
        $this->db->where('currency', $currency);
        $this->db->where('status', 1);

        $debits = $this->db->get(db_prefix() . 'pur_debit_notes')->result_array();

        $total = $this->calc_remaining_debits($debits);

        return $total;
    }
    public function get_open_debits($vendor_id)
    {

        $this->db->where('status', 1);
        $this->db->where('vendorid', $vendor_id);

        $debits = $this->db->get(db_prefix() . 'pur_debit_notes')->result_array();

        foreach ($debits as $key => $debit) {
            $debits[$key]['available_debits'] = $this->calculate_available_debits($debit['id'], $debit['total']);
        }

        return $debits;
    }
    private function calc_remaining_debits($debits)
    {
        $total = 0;
        $credits_ids = [];

        $bcadd = function_exists('bcadd');
        foreach ($debits as $debit) {
            if ($bcadd) {
                $total = bcadd($total, $debit['total'], get_decimal_places());
            } else {
                $total += $debit['total'];
            }
            array_push($credits_ids, $debit['id']);
        }

        if (count($credits_ids) > 0) {
            $this->db->where('debit_id IN (' . implode(', ', $credits_ids) . ')');
            $applied_credits = $this->db->get(db_prefix() . 'pur_debits')->result_array();
            $bcsub = function_exists('bcsub');
            foreach ($applied_credits as $debit) {
                if ($bcsub) {
                    $total = bcsub($total, $debit['amount'], get_decimal_places());
                } else {
                    $total -= $debit['amount'];
                }
            }

            foreach ($credits_ids as $credit_note_id) {
                $total_refunds_by_debit_note = $this->total_refunds_by_debit_note($credit_note_id);
                if ($bcsub) {
                    $total = bcsub($total, $total_refunds_by_debit_note ?? '', get_decimal_places());
                } else {
                    $total -= $total_refunds_by_debit_note;
                }
            }
        }

        return $total;
    }
    public function get_invoice_detail($pur_request)
    {
        $this->db->where('pur_invoice', $pur_request);
        $pur_invoice_details = $this->db->get(db_prefix() . 'pur_invoice_details')->result_array();

        foreach ($pur_invoice_details as $key => $detail) {
            $pur_invoice_details[$key]['discount_money'] = (float) $detail['discount_money'];
            $pur_invoice_details[$key]['into_money'] = (float) $detail['into_money'];
            $pur_invoice_details[$key]['total'] = (float) $detail['total'];
            $pur_invoice_details[$key]['total_money'] = (float) $detail['total_money'];
            $pur_invoice_details[$key]['unit_price'] = (float) $detail['unit_price'];
            $pur_invoice_details[$key]['tax_value'] = (float) $detail['tax_value'];
        }

        return $pur_invoice_details;
    }
    public function get_html_tax_pur_invoice($id)
    {
        $html = '';
        $preview_html = '';
        $pdf_html = '';
        $taxes = [];
        $t_rate = [];
        $tax_val = [];
        $tax_val_rs = [];
        $tax_name = [];
        $rs = [];

        $invoice = $this->get_pur_invoice($id);

        $this->load->model('currencies_model');
        $base_currency = $this->currencies_model->get_base_currency();

        if ($invoice->currency != 0 && $invoice->currency != null) {
            $base_currency = pur_get_currency_by_id($invoice->currency);
        }


        $this->db->where('pur_invoice', $id);
        $details = $this->db->get(db_prefix() . 'pur_invoice_details')->result_array();

        $item_discount = 0;
        foreach ($details as $row) {
            if ($row['tax'] != '') {
                $tax_arr = explode('|', $row['tax']);

                $tax_rate_arr = [];
                if ($row['tax_rate'] != '') {
                    $tax_rate_arr = explode('|', $row['tax_rate']);
                }

                foreach ($tax_arr as $k => $tax_it) {
                    if (!isset($tax_rate_arr[$k])) {
                        $tax_rate_arr[$k] = $this->tax_rate_by_id($tax_it);
                    }

                    if (!in_array($tax_it, $taxes)) {
                        $taxes[$tax_it] = $tax_it;
                        $t_rate[$tax_it] = $tax_rate_arr[$k];
                        $tax_name[$tax_it] = $this->get_tax_name($tax_it) . ' (' . $tax_rate_arr[$k] . '%)';
                    }
                }
            }

            $item_discount += $row['discount_money'];
        }

        if (count($tax_name) > 0) {
            $discount_total = $item_discount + $invoice->discount_total;
            foreach ($tax_name as $key => $tn) {
                $tax_val[$key] = 0;
                foreach ($details as $row_dt) {
                    if (!(strpos($row_dt['tax'] ?? '', $taxes[$key]) === false)) {
                        $total = ($row_dt['into_money'] * $t_rate[$key] / 100);
                        if ($invoice->discount_type == 'before_tax') {
                            $t = ($discount_total / $invoice->subtotal) * 100;
                            $tax_val[$key] += ($total - $total * $t / 100);
                        } else {
                            $tax_val[$key] += $total;
                        }
                    }
                }
                $pdf_html .= '<tr id="subtotal"><td width="33%"></td><td>' . $tn . '</td><td>' . app_format_money($tax_val[$key], '') . '</td></tr>';
                $preview_html .= '<tr id="subtotal"><td>' . $tn . '</td><td>' . app_format_money($tax_val[$key], $base_currency->name) . '</td><tr>';
                $html .= '<tr class="tax-area_pr"><td>' . $tn . '</td><td width="65%">' . app_format_money($tax_val[$key], '') . ' ' . ($base_currency->name) . '</td></tr>';
                $tax_val_rs[] = $tax_val[$key];
            }
        }

        $rs['pdf_html'] = $pdf_html;
        $rs['preview_html'] = $preview_html;
        $rs['html'] = $html;
        $rs['taxes'] = $taxes;
        $rs['taxes_val'] = $tax_val_rs;
        return $rs;
    }
    public function get_payment_invoice($invoice)
    {
        $this->db->where('pur_invoice', $invoice);
        return $this->db->get(db_prefix() . 'pur_invoice_payment')->result_array();
    }
    public function get_purchase_invoice_attachments($id)
    {

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'pur_invoice');
        return $this->db->get(db_prefix() . 'files')->result_array();
    }
    //     public function get_alb_invoice($id = '')
// {
//     if ($id != '') {
//         $this->db->where('id', $id);
//         return $this->db->get(db_prefix() . 'alb_invoices')->row();
//     } else {
//         return $this->db->get(db_prefix() . 'alb_invoices')->result_array();
//     }
// }

//Rifat Start............................
    public function get_po_applied_invoice_debits($invoice_id)
    {
        $this->db->order_by('date', 'desc');
        $this->db->where('invoice_id', $invoice_id);

        return $this->db->get(db_prefix() . 'po_invoice_debits')->result_array();
    }

    public function total_po_remaining_debits_by_vendor($vendor_id, $currency)
    {
        $base_currency = get_base_currency_pur();
        if ($currency == 0) {
            $currency = $base_currency->id;
        }

        $this->db->select('total,id');
        $this->db->where('vendorid', $vendor_id);
        $this->db->where('currency', $currency);
        $this->db->where('status', 1);

        $debits = $this->db->get(db_prefix() . 'po_invoice_debit_notes')->result_array();

        $total = $this->calc_po_remaining_debits($debits);

        return $total;
    }

    private function calc_po_remaining_debits($debits)
    {
        $total = 0;
        $credits_ids = [];

        $bcadd = function_exists('bcadd');
        foreach ($debits as $debit) {
            if ($bcadd) {
                $total = bcadd($total, $debit['total'], get_decimal_places());
            } else {
                $total += $debit['total'];
            }
            array_push($credits_ids, $debit['id']);
        }

        if (count($credits_ids) > 0) {
            $this->db->where('debit_id IN (' . implode(', ', $credits_ids) . ')');
            $applied_credits = $this->db->get(db_prefix() . 'po_invoice_debits')->result_array();
            $bcsub = function_exists('bcsub');
            foreach ($applied_credits as $debit) {
                if ($bcsub) {
                    $total = bcsub($total, $debit['amount'], get_decimal_places());
                } else {
                    $total -= $debit['amount'];
                }
            }

            foreach ($credits_ids as $credit_note_id) {
                $total_refunds_by_debit_note = $this->total_refunds_by_debit_note($credit_note_id);
                if ($bcsub) {
                    $total = bcsub($total, $total_refunds_by_debit_note ?? '', get_decimal_places());
                } else {
                    $total -= $total_refunds_by_debit_note;
                }
            }
        }

        return $total;
    }

    private function total_refunds_by_debit_note($id)
    {
        return sum_from_table(db_prefix() . 'po_invoice_debit_note_refunds', [
            'field' => 'amount',
            'where' => ['debit_note_id' => $id],
        ]);
    }

    public function get_po_open_debits($vendor_id)
    {

        $this->db->where('status', 1);
        $this->db->where('vendorid', $vendor_id);

        $debits = $this->db->get(db_prefix() . 'po_invoice_debit_notes')->result_array();

        foreach ($debits as $key => $debit) {
            $debits[$key]['available_debits'] = $this->calculate_available_debits($debit['id'], $debit['total']);
        }

        return $debits;
    }

    private function calculate_available_debits($debit_id, $debit_amount = false)
    {
        if ($debit_amount === false) {
            $this->db->select('total')
                ->from(db_prefix() . 'po_invoice_debit_notes')
                ->where('id', $debit_id);

            $debit_amount = $this->db->get()->row()->total;
        }

        $available_total = $debit_amount;

        $bcsub = function_exists('bcsub');
        $applied_debits = $this->get_applied_debits($debit_id);


        foreach ($applied_debits as $debit) {
            if ($bcsub) {
                $available_total = bcsub($available_total, $debit['amount'], get_decimal_places());
            } else {
                $available_total -= $debit['amount'];
            }
        }

        $total_refunds = $this->total_refunds_by_debit_note($debit_id);

        if ($total_refunds) {
            if ($bcsub) {
                $available_total = bcsub($available_total, $total_refunds, get_decimal_places());
            } else {
                $available_total -= $total_refunds;
            }
        }

        return $available_total;
    }

    public function get_applied_debits($debit_id)
    {
        $this->db->where('debit_id', $debit_id);
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'po_invoice_debits')->result_array();
    }

    public function get_po_html_tax_pur_invoice($id)
    {
        $html = '';
        $preview_html = '';
        $pdf_html = '';
        $taxes = [];
        $t_rate = [];
        $tax_val = [];
        $tax_val_rs = [];
        $tax_name = [];
        $rs = [];

        $invoice = $this->get_pur_invoice($id);

        $this->load->model('currencies_model');
        $base_currency = $this->currencies_model->get_base_currency();

        if ($invoice->currency != 0 && $invoice->currency != null) {
            $base_currency = pur_get_currency_by_id($invoice->currency);
        }


        $this->db->where('pur_invoice', $id);
        $details = $this->db->get(db_prefix() . 'alb_po_invoice_items')->result_array();

        $item_discount = 0;
        foreach ($details as $row) {
            if ($row['tax'] != '') {
                $tax_arr = explode('|', $row['tax']);

                $tax_rate_arr = [];
                if ($row['tax_rate'] != '') {
                    $tax_rate_arr = explode('|', $row['tax_rate']);
                }

                foreach ($tax_arr as $k => $tax_it) {
                    if (!isset($tax_rate_arr[$k])) {
                        $tax_rate_arr[$k] = $this->tax_rate_by_id($tax_it);
                    }

                    if (!in_array($tax_it, $taxes)) {
                        $taxes[$tax_it] = $tax_it;
                        $t_rate[$tax_it] = $tax_rate_arr[$k];
                        $tax_name[$tax_it] = $this->get_tax_name($tax_it) . ' (' . $tax_rate_arr[$k] . '%)';
                    }
                }
            }

            $item_discount += $row['discount_money'];
        }

        if (count($tax_name) > 0) {
            $discount_total = $item_discount + $invoice->discount_total;
            foreach ($tax_name as $key => $tn) {
                $tax_val[$key] = 0;
                foreach ($details as $row_dt) {
                    if (!(strpos($row_dt['tax'] ?? '', $taxes[$key]) === false)) {
                        $total = ($row_dt['into_money'] * $t_rate[$key] / 100);
                        if ($invoice->discount_type == 'before_tax') {
                            $t = ($discount_total / $invoice->subtotal) * 100;
                            $tax_val[$key] += ($total - $total * $t / 100);
                        } else {
                            $tax_val[$key] += $total;
                        }
                    }
                }
                $pdf_html .= '<tr id="subtotal"><td width="33%"></td><td>' . $tn . '</td><td>' . app_format_money($tax_val[$key], '') . '</td></tr>';
                $preview_html .= '<tr id="subtotal"><td>' . $tn . '</td><td>' . app_format_money($tax_val[$key], $base_currency->name) . '</td><tr>';
                $html .= '<tr class="tax-area_pr"><td>' . $tn . '</td><td width="65%">' . app_format_money($tax_val[$key], '') . ' ' . ($base_currency->name) . '</td></tr>';
                $tax_val_rs[] = $tax_val[$key];
            }
        }

        $rs['pdf_html'] = $pdf_html;
        $rs['preview_html'] = $preview_html;
        $rs['html'] = $html;
        $rs['taxes'] = $taxes;
        $rs['taxes_val'] = $tax_val_rs;
        return $rs;
    }

    public function tax_rate_by_id($tax_id)
    {
        $this->db->where('id', $tax_id);
        $tax = $this->db->get(db_prefix() . 'taxes')->row();
        if ($tax) {
            return $tax->taxrate;
        }
        return 0;
    }

    public function get_tax_name($tax)
    {
        $this->db->where('id', $tax);
        $tax_if = $this->db->get(db_prefix() . 'taxes')->row();
        if ($tax_if) {
            return $tax_if->name;
        }
        return '';
    }

    public function get_po_payment_invoice($invoice)
    {
        $this->db->where('pur_invoice', $invoice);
        return $this->db->get(db_prefix() . 'alb_po_invoice_payment')->result_array();
    }


    public function get_albania_po_invoice_pdf_html($invoice_id)
    {
        $invoice = $this->get_po_invoice($invoice_id);
        if (!$invoice) {
            return '';
        }

        $invoice_detail = $this->get_pur_invoice_detail($invoice_id);

        $base_currency = get_base_currency_pur();
        if ($invoice->currency != 0) {
            $base_currency = pur_get_currency_by_id($invoice->currency);
        }

        $vendor = $this->get_vendor($invoice->vendor);
        $vendor_name = '';
        $address     = '';
        if ($vendor) {
            $vendor_name = $vendor->company;
            $countryName = '';
            if ($country = get_country($vendor->country)) {
                $countryName = $country->short_name;
            }
            $address = $vendor->address . ($countryName ? ', ' . $countryName : '');
        }
        $bill_to = $vendor_name;
        if ($address !== '') {
            $bill_to .= '<br>' . $address;
        }

        $invoice_number = $invoice->invoice_number;

        $alb_company_name = get_option('alb_invoice_company_name') ?: 'D3A TRADING COMPANY LIMITED';

        $html  = '<table class="table" style="width:100%; border-collapse:collapse;"><tbody>';
        $html .= '<tr><td align="center" colspan="2" style="padding-bottom:8px;">' . alb_pdf_logo_url() . '</td></tr>';
        $html .= '<tr><td colspan="2" align="center"><b style="font-size:20px; font-weight:600;">' . htmlspecialchars($alb_company_name) . '</b><br><b style="font-size:20px;">' .'AL Purchase Invoice' . '</b></td></tr>';
        $html .= '<tr><td style="width:50%; vertical-align:top; padding-right:15px;">' . '<div style="color:#424242;">' . format_alb_organization_info() . '</div>' . '</td>';
        $right_info  = '<b style="font-size:18px;">AL Purchase Invoice</b><br>';
        $right_info .= '<b>#'. $invoice_number . '</b><br><br>';
        $right_info .= '<b>' . 'Vendor' . '</b><div style="color:#424242;">' . $bill_to . '</div><br>';
        $right_info .= 'Invoice Date: ' . _d($invoice->invoice_date) . '<br>';
        if (!empty($invoice->duedate)) {
            $right_info .= 'Due Date: ' . _d($invoice->duedate) . '<br>';
        }
        $html .= '<td style="width:50%; text-align:right; vertical-align:top;">' . $right_info . '</td></tr>';
        $html .= '</tbody></table><br><br>';

        $currency_symbol = $base_currency ? ($base_currency->symbol ?? $base_currency->name) : '';
        $sum_amount = 0;
        $sum_qty = 0;

        $tblhtml = '<table cellpadding="4" style="width:100%; border-collapse:collapse; border:1px solid #333;">';
        $tblhtml .= '<tr style="background-color:#e8e8e8;">';
        $tblhtml .= '<td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:left;">Name/Title</td>';
        $tblhtml .= '<td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:center;">Description</td>';
        $tblhtml .= '<td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:center;">Unit/Quantity</td>';
        $tblhtml .= '<td style="border:1px solid #333; padding:6px; font-weight:bold; color:#000; text-align:center;">Price/Value</td>';
        $tblhtml .= '</tr>';

        foreach ($invoice_detail as $row) {
            $product_code = isset($row['item_name']) ? $row['item_name'] : '';
            $product_name = isset($row['description']) ? $row['description'] : '';

            $quantity = floatval($row['quantity'] ?? 0);
            $unit_price = floatval($row['unit_price'] ?? 0);
            
            $sum_qty += $quantity;
            $sum_amount += $unit_price;
            
            $tblhtml .= '<tr nobr="true">';
            $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:left;">' . htmlspecialchars($product_code) . '</td>';
            $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . htmlspecialchars($product_name) . '</td>';
            $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . htmlspecialchars((string)$quantity) . '</td>';
            $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . htmlspecialchars((string)$unit_price) . '</td>';
            $tblhtml .= '</tr>';
        }
        
        // Total row
        $tblhtml .= '<tr style="font-weight:bold;">';
        $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:left;"></td>';
        $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">Total</td>';
        $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . $sum_qty . '</td>';
        $tblhtml .= '<td style="border:1px solid #333; padding:6px; text-align:center;">' . app_format_money($sum_amount, $base_currency, true) . '</td>';
        $tblhtml .= '</tr>';
        $tblhtml .= '</table>';

        $html .= $tblhtml;
        $html .= '<br>';

        // Bank Information
        $bank_info = get_option('alb_bank_information');
        if (empty($bank_info)) {
            $bank_info = !empty($invoice->clientnote) ? $invoice->clientnote : (!empty($invoice->terms) ? $invoice->terms : '');
        }
        if (!empty($bank_info)) {
            $bank_info = clear_textarea_breaks($bank_info, "\n");
            $bank_info = _info_format_replace('company_name', get_option('alb_invoice_company_name') ?: '', $bank_info);
            $bank_info_html = nl2br(htmlspecialchars($bank_info, ENT_QUOTES, 'UTF-8'));
            $html .= '<div style="margin-top:15px;">';
            $html .= '<b>' . _l('alb_bank_information') . ':</b><br>';
            $html .= $bank_info_html;
            $html .= '</div>';
        }

        return $html;
    }

    public function get_pur_order($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'pur_orders')->row();
    }
    public function purchase_invoice_pdf($pur_invoice)
    {
        return app_pdf('pur_invoice', module_dir_path(D3A_ALBANIA_MODULE_NAME, 'libraries/pdf/Po_invoice_pdf'), $pur_invoice);
    }

    public function add_invoice_payment($data, $invoice)
    {
        $data['date'] = to_sql_date($data['date']);
        $data['daterecorded'] = date('Y-m-d H:i:s');

        $data['pur_invoice'] = $invoice;
        $data['approval_status'] = 1;
        $data['requester'] = get_staff_user_id();
        $check_appr = $this->get_approve_setting('payment_request');
        if ($check_appr && $check_appr != false) {
            $data['approval_status'] = 1;
        } else {
            $data['approval_status'] = 2;
        }

        $this->db->insert(db_prefix() . 'alb_po_invoice_payment', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {

            if ($data['approval_status'] == 2) {
                $pur_invoice = $this->get_po_invoice($invoice);
                if ($pur_invoice) {
                    $left_to_pay = po_invoice_left_to_pay($invoice);
                    $status_inv = 'unpaid';
                    
                    if ($left_to_pay <= 0) {
                        $status_inv = 'paid';
                    } elseif ($left_to_pay < $pur_invoice->total) {
                        $status_inv = 'partially_paid';
                    }
                    
                    $this->db->where('id', $invoice);
                    $this->db->update(db_prefix() . 'alb_po_invoices', ['payment_status' => $status_inv,]);
                }
            }

            hooks()->do_action('after_payment_pur_invoice_added', $insert_id);

            return $insert_id;
        }
        return false;
    }
    public function get_approve_setting($type, $status = '')
    {
        $this->db->select('*');
        $this->db->where('related', $type);
        $approval_setting = $this->db->get('tblpur_approval_setting')->row();
        if ($approval_setting) {
            return json_decode($approval_setting->setting);
        } else {
            return false;
        }
    }

    public function delete_po_invoices($id)
    {
        if (!$id) {
            return false;
        }

        $this->db->where('pur_invoice', $id);
        $this->db->delete(db_prefix() . 'alb_po_invoice_items');

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'alb_po_invoices');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function delete_po_invoice($id)
    {
        $payment = $this->get_payment_po_invoice($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'alb_po_invoice_payment');
        if ($this->db->affected_rows() > 0) {
            $pur_invoice = $this->get_po_invoice($payment->pur_invoice);

            if ($pur_invoice) {
                $left_to_pay = po_invoice_left_to_pay($payment->pur_invoice);
                $status_inv = 'unpaid';
                
                if ($left_to_pay <= 0) {
                    $status_inv = 'paid';
                } elseif ($left_to_pay < $pur_invoice->total) {
                    $status_inv = 'partially_paid';
                }

                $this->db->where('id', $payment->pur_invoice);
                $this->db->update(db_prefix() . 'alb_po_invoices', ['payment_status' => $status_inv]);
            }

            hooks()->do_action('after_payment_pur_invoice_deleted', $id);

            return true;
        }
        return false;
    }

    public function delete_po_payment($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'alb_po_invoice_payment');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }
    
    public function get_payment_po_invoice($id = '')
    {
        if ($id != '') {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'alb_po_invoice_payment')->row();
        } else {
            return $this->db->get(db_prefix() . 'alb_po_invoice_payment')->result_array();
        }
    }

    public function get_staff_sign($rel_id, $rel_type)
    {
        $this->db->select('*');

        $this->db->where('rel_id', $rel_id);
        $this->db->where('rel_type', $rel_type);
        $this->db->where('action', 'sign');
        $approve_status = $this->db->get(db_prefix() . 'po_approval_details')->result_array();
        if (isset($approve_status)) {
            $array_return = [];
            foreach ($approve_status as $key => $value) {
                array_push($array_return, $value['staffid']);
            }
            return $array_return;
        }
        return [];
    }

    public function check_approval_details($rel_id, $rel_type)
    {
        $this->db->where('rel_id', $rel_id);
        $this->db->where('rel_type', $rel_type);
        $approve_status = $this->db->get(db_prefix() . 'po_approval_details')->result_array();
        if (count($approve_status) > 0) {
            foreach ($approve_status as $value) {
                if ($value['approve'] == -1) {
                    return 'reject';
                }
                if ($value['approve'] == 0) {
                    $value['staffid'] = explode(', ', $value['staffid']);
                    return $value;
                }
            }
            return true;
        }
        return false;
    }

    public function get_list_approval_details($rel_id, $rel_type)
    {
        $this->db->select('*');
        $this->db->where('rel_id', $rel_id);
        $this->db->where('rel_type', $rel_type);
        return $this->db->get(db_prefix() . 'po_approval_details')->result_array();
    }

    public function get_payment_pur_invoice($id = '')
    {
        if ($id != '') {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'alb_po_invoice_payment')->row();
        } else {
            return $this->db->get(db_prefix() . 'alb_po_invoice_payment')->result_array();
        }
    }
//Rifat END............................

}
