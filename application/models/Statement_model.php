<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Statement_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get customer statement formatted
     * @param  mixed $customer_id customer id
     * @param  string $from        date from
     * @param  string $to          date to
     * @return array
     */
    public function get_statement($customer_id, $from, $to)
    {
        if (!class_exists('Invoices_model', false)) {
            $this->load->model('invoices_model');
        }

        $sql = 'SELECT
        ' . db_prefix() . 'invoices.id as invoice_id,
        hash,
        ' . db_prefix() . 'invoices.date as date,
        ' . db_prefix() . 'invoices.duedate,
        concat(' . db_prefix() . 'invoices.date, \' \', RIGHT(' . db_prefix() . 'invoices.datecreated,LOCATE(\' \',' . db_prefix() . 'invoices.datecreated) - 3)) as tmp_date,
        ' . db_prefix() . 'invoices.duedate as duedate,
        ' . db_prefix() . 'invoices.total as invoice_amount
        FROM ' . db_prefix() . 'invoices WHERE clientid =' . $this->db->escape_str($customer_id);

        if ($from == $to) {
            $sqlDate = 'date="' . $this->db->escape_str($from) . '"';
        } else {
            $sqlDate = '(date BETWEEN "' . $this->db->escape_str($from) . '" AND "' . $this->db->escape_str($to) . '")';
        }

        $sql .= ' AND ' . $sqlDate;

        $invoices = $this->db->query($sql . '
            AND status != ' . Invoices_model::STATUS_DRAFT . '
            AND status != ' . Invoices_model::STATUS_CANCELLED . '
            ORDER By date DESC')->result_array();

        // Credit notes
        $sql_credit_notes = 'SELECT
        ' . db_prefix() . 'creditnotes.id as credit_note_id,
        ' . db_prefix() . 'creditnotes.date as date,
        concat(' . db_prefix() . 'creditnotes.date, \' \', RIGHT(' . db_prefix() . 'creditnotes.datecreated,LOCATE(\' \',' . db_prefix() . 'creditnotes.datecreated) - 3)) as tmp_date,
        ' . db_prefix() . 'creditnotes.total as credit_note_amount
        FROM ' . db_prefix() . 'creditnotes WHERE clientid =' . $this->db->escape_str($customer_id) . ' AND status != 3';

        $sql_credit_notes .= ' AND ' . $sqlDate;

        $credit_notes = $this->db->query($sql_credit_notes)->result_array();

        // Credits applied
        $sql_credits_applied = 'SELECT
        ' . db_prefix() . 'credits.id as credit_id,
        invoice_id as credit_invoice_id,
        ' . db_prefix() . 'credits.credit_id as credit_applied_credit_note_id,
        ' . db_prefix() . 'credits.date as date,
        concat(' . db_prefix() . 'credits.date, \' \', RIGHT(' . db_prefix() . 'credits.date_applied,LOCATE(\' \',' . db_prefix() . 'credits.date_applied) - 3)) as tmp_date,
        ' . db_prefix() . 'credits.amount as credit_amount
        FROM ' . db_prefix() . 'credits
        JOIN ' . db_prefix() . 'creditnotes ON ' . db_prefix() . 'creditnotes.id = ' . db_prefix() . 'credits.credit_id
        ';

        $sql_credits_applied .= '
        WHERE clientid =' . $this->db->escape_str($customer_id);

        $sqlDateCreditsAplied = str_replace('date', db_prefix() . 'credits.date', $sqlDate);

        $sql_credits_applied .= ' AND ' . $sqlDateCreditsAplied;
        $credits_applied = $this->db->query($sql_credits_applied)->result_array();

        // Replace error ambigious column in where clause
        $sqlDatePayments = str_replace('date', db_prefix() . 'invoicepaymentrecords.date', $sqlDate);

        $sql_payments = 'SELECT
        ' . db_prefix() . 'invoicepaymentrecords.id as payment_id,
        ' . db_prefix() . 'invoicepaymentrecords.date as date,
        concat(' . db_prefix() . 'invoicepaymentrecords.date, \' \', RIGHT(' . db_prefix() . 'invoicepaymentrecords.daterecorded,LOCATE(\' \',' . db_prefix() . 'invoicepaymentrecords.daterecorded) - 3)) as tmp_date,
        ' . db_prefix() . 'invoicepaymentrecords.invoiceid as payment_invoice_id,
        ' . db_prefix() . 'invoicepaymentrecords.amount as payment_total
        FROM ' . db_prefix() . 'invoicepaymentrecords
        JOIN ' . db_prefix() . 'invoices ON ' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid
        WHERE ' . $sqlDatePayments . ' AND ' . db_prefix() . 'invoices.clientid = ' . $this->db->escape_str($customer_id) . '
        ORDER by ' . db_prefix() . 'invoicepaymentrecords.date DESC';

        $payments = $this->db->query($sql_payments)->result_array();

        $sqlCreditNoteRefunds = str_replace('date', 'refunded_on', $sqlDate);

        $sql_credit_notes_refunds = 'SELECT id as credit_note_refund_id,
        credit_note_id as refund_credit_note_id,
        amount as refund_amount,
        concat(' . db_prefix() . 'creditnote_refunds.refunded_on, \' \', RIGHT(' . db_prefix() . 'creditnote_refunds.created_at,LOCATE(\' \',' . db_prefix() . 'creditnote_refunds.created_at) - 3)) as tmp_date,
        refunded_on as date FROM ' . db_prefix() . 'creditnote_refunds
        WHERE ' . $sqlCreditNoteRefunds . ' AND credit_note_id IN (SELECT id FROM ' . db_prefix() . 'creditnotes WHERE clientid=' . $this->db->escape_str($customer_id) . ')
        ';

        $credit_notes_refunds = $this->db->query($sql_credit_notes_refunds)->result_array();

        // merge results
        $merged = array_merge($invoices, $payments, $credit_notes, $credits_applied, $credit_notes_refunds);

        // sort by date
        usort($merged, function ($a, $b) {
            // fake date select sorting
            return strtotime($a['tmp_date']) - strtotime($b['tmp_date']);
        });

        // Define final result variable
        $result = [];
        // Store in result array key
        $result['result'] = $merged;

        // Invoiced amount during the period
        $result['invoiced_amount'] = $this->db->query('SELECT
        SUM(' . db_prefix() . 'invoices.total) as invoiced_amount
        FROM ' . db_prefix() . 'invoices
        WHERE clientid = ' . $this->db->escape_str($customer_id) . '
        AND ' . $sqlDate . ' AND status != ' . Invoices_model::STATUS_DRAFT . ' AND status != ' . Invoices_model::STATUS_CANCELLED . '')
            ->row()->invoiced_amount;

        if ($result['invoiced_amount'] === null) {
            $result['invoiced_amount'] = 0;
        }

        $result['credit_notes_amount'] = $this->db->query('SELECT
        SUM(' . db_prefix() . 'creditnotes.total) as credit_notes_amount
        FROM ' . db_prefix() . 'creditnotes
        WHERE clientid = ' . $this->db->escape_str($customer_id) . '
        AND ' . $sqlDate . ' AND status != 3')
            ->row()->credit_notes_amount;

        if ($result['credit_notes_amount'] === null) {
            $result['credit_notes_amount'] = 0;
        }

        $result['refunds_amount'] = $this->db->query('SELECT
        SUM(' . db_prefix() . 'creditnote_refunds.amount) as refunds_amount
        FROM ' . db_prefix() . 'creditnote_refunds
        WHERE ' . $sqlCreditNoteRefunds . ' AND credit_note_id IN (SELECT id FROM ' . db_prefix() . 'creditnotes WHERE clientid=' . $this->db->escape_str($customer_id) . ')
        ')->row()->refunds_amount;

        if ($result['refunds_amount'] === null) {
            $result['refunds_amount'] = 0;
        }

        $result['invoiced_amount'] = $result['invoiced_amount'] - $result['credit_notes_amount'];

        // Amount paid during the period
        $result['amount_paid'] = $this->db->query('SELECT
        SUM(' . db_prefix() . 'invoicepaymentrecords.amount) as amount_paid
        FROM ' . db_prefix() . 'invoicepaymentrecords
        JOIN ' . db_prefix() . 'invoices ON ' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid
        WHERE ' . $sqlDatePayments . ' AND ' . db_prefix() . 'invoices.clientid = ' . $this->db->escape_str($customer_id))
            ->row()->amount_paid;

        if ($result['amount_paid'] === null) {
            $result['amount_paid'] = 0;
        }

        // Beginning balance is all invoices amount before the FROM date - payments received before FROM date
        $result['beginning_balance'] = $this->db->query('
            SELECT (
            COALESCE(SUM(' . db_prefix() . 'invoices.total),0) - (
            (
            SELECT COALESCE(SUM(' . db_prefix() . 'invoicepaymentrecords.amount),0)
            FROM ' . db_prefix() . 'invoicepaymentrecords
            JOIN ' . db_prefix() . 'invoices ON ' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid
            WHERE ' . db_prefix() . 'invoicepaymentrecords.date < "' . $this->db->escape_str($from) . '"
            AND ' . db_prefix() . 'invoices.clientid=' . $this->db->escape_str($customer_id) . '
            ) + (
                SELECT COALESCE(SUM(' . db_prefix() . 'creditnotes.total),0)
                FROM ' . db_prefix() . 'creditnotes
                WHERE ' . db_prefix() . 'creditnotes.date < "' . $this->db->escape_str($from) . '"
                AND ' . db_prefix() . 'creditnotes.clientid=' . $this->db->escape_str($customer_id) . '
            ) - (
                SELECT COALESCE(SUM(' . db_prefix() . 'creditnote_refunds.amount),0)
                FROM ' . db_prefix() . 'creditnote_refunds
                WHERE ' . db_prefix() . 'creditnote_refunds.refunded_on < "' . $this->db->escape_str($from) . '"
                AND ' . db_prefix() . 'creditnote_refunds.credit_note_id IN (SELECT id FROM ' . db_prefix() . 'creditnotes WHERE clientid=' . $this->db->escape_str($customer_id) . ')
            )
        )
            )
            as beginning_balance FROM ' . db_prefix() . 'invoices
            WHERE date < "' . $this->db->escape_str($from) . '"
            AND clientid = ' . $this->db->escape_str($customer_id) . '
            AND status != ' . Invoices_model::STATUS_DRAFT . '
            AND status != ' . Invoices_model::STATUS_CANCELLED)
              ->row()->beginning_balance;

        if ($result['beginning_balance'] === null) {
            $result['beginning_balance'] = 0;
        }

        $dec = get_decimal_places();

        if (function_exists('bcsub')) {
            $result['balance_due'] = bcsub($result['invoiced_amount'], $result['amount_paid'], $dec);
            $result['balance_due'] = bcadd($result['balance_due'], $result['beginning_balance'], $dec);
            $result['balance_due'] = bcadd($result['balance_due'], $result['refunds_amount'], $dec);
        } else {
            $result['balance_due'] = number_format($result['invoiced_amount'] - $result['amount_paid'], $dec, '.', '');
            $result['balance_due'] = $result['balance_due'] + number_format($result['beginning_balance'], $dec, '.', '');
            $result['balance_due'] = $result['balance_due'] + number_format($result['refunds_amount'], $dec, '.', '');
        }

        // Subtract amount paid - refund, because the refund is not actually paid amount
        $result['amount_paid'] = $result['amount_paid'] - $result['refunds_amount'];

        $result['client_id'] = $customer_id;
        $result['client']    = $this->clients_model->get($customer_id);
        $result['from']      = $from;
        $result['to']        = $to;

        $customer_currency = $this->clients_model->get_customer_default_currency($customer_id);
        $this->load->model('currencies_model');

        if ($customer_currency != 0) {
            $currency = $this->currencies_model->get($customer_currency);
        } else {
            $currency = $this->currencies_model->get_base_currency();
        }

        $result['currency'] = $currency;

        return hooks()->apply_filters('statement', $result);
    }

    /**
     * Send customer statement to email
     * @param  mixed $customer_id customer id
     * @param  array $send_to     array of contact emails to send
     * @param  string $from        date from
     * @param  string $to          date to
     * @param  string $cc          email CC
     * @return boolean
     */
    public function send_statement_to_email($customer_id, $send_to, $from, $to, $cc = '')
    {
        $sent = false;
        if (is_array($send_to) && count($send_to) > 0) {
            $statement = $this->get_statement($customer_id, to_sql_date($from), to_sql_date($to));
            set_mailing_constant();
            $pdf = statement_pdf($statement);

            $pdf_file_name = slug_it(_l('customer_statement') . '-' . $statement['client']->company);

            $attach = $pdf->Output($pdf_file_name . '.pdf', 'S');

            $i = 0;
            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {

                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_contact($contact_id);

                    $template = mail_template('customer_statement', $contact->email, $contact_id, $statement, $cc);

                    $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $pdf_file_name . '.pdf',
                            'type'       => 'application/pdf',
                        ]);

                    if ($template->send()) {
                        $sent = true;
                    }
                }
                $i++;
            }

            if ($sent) {
                return true;
            }
        }

        return false;
    }
    public function get_all_clients()
    {
        $this->db->select('userid, company');
        $this->db->from('tblclients');
        $this->db->order_by('company', 'ASC');
        return $this->db->get()->result_array();
    }
    public function get_all_projects()
    {
        $this->db->select('id, name');
        $this->db->from('tblprojects');
        $this->db->order_by('name', 'ASC');
        return $this->db->get()->result_array();
    }
    public function get_all_client_admin()
    {
        $this->db->select('staffid as customer_id, CONCAT(firstname, " ", lastname) as admin_names');
        $this->db->from(db_prefix().'staff');
        $this->db->where('active', 1);
        $this->db->order_by('admin_names', 'ASC');
        return $this->db->get()->result_array();
    }



    public function get_client_by_id($client_id)
    {
        return $this->db
            ->where('userid', (int)$client_id)
            ->get(db_prefix().'clients')
            ->row_array();
    }

    public function get_projects_by_client($client_id)
    {
        return $this->db
            ->where('clientid', (int)$client_id)
            ->get(db_prefix().'projects')
            ->result_array();
    }
    public function get_client_statement($client_id)
    {
        $sql = "
            SELECT
                p.start_date as start_date,
                p.name as project_name,

                IFNULL(gr.total_goods_value,0) as total_goods_value,
                IFNULL(e.service_fee,0) as service_fee,
                IFNULL(e.inland_transport_b,0) as inland_transport_b,
                IFNULL(e.others_expenses,0) as others_expenses,

                (
                    IFNULL(gr.total_goods_value,0)
                    + IFNULL(e.service_fee,0)
                    + IFNULL(e.inland_transport_b,0)
                    + IFNULL(e.others_expenses,0)
                ) as total,

                IFNULL(inv.invoice_amount,0) as invoice_amount,
                IFNULL(inv.invoice_total,0) as invoice_total,
                inv.currency_name as currency_name,

                (
                    IFNULL(inv.invoice_total,0)
                    - IFNULL(gr.total_goods_value,0)
                    - IFNULL(e.service_fee,0)
                    - IFNULL(e.inland_transport_b,0)
                    - IFNULL(e.others_expenses,0)
                ) as balance,

                cl.company as company,
                adm.admin_names as admin_names,
                IFNULL(e.sea_transport_b,0) as sea_transport_b

            FROM (
                SELECT MIN(id) as id, project, clients
                FROM " . db_prefix() . "pur_orders
                GROUP BY project, clients
            ) po

            LEFT JOIN " . db_prefix() . "clients as cl 
                ON cl.userid = po.clients

            LEFT JOIN " . db_prefix() . "projects as p 
                ON p.id = po.project

            LEFT JOIN (
                SELECT po.clients, po.project, SUM(gr.total_goods_money) AS total_goods_value
                FROM " . db_prefix() . "pur_orders po
                LEFT JOIN " . db_prefix() . "goods_receipt gr ON gr.pr_order_id = po.id
                GROUP BY po.clients, po.project
            ) gr ON gr.clients = po.clients AND gr.project = po.project

            LEFT JOIN (
                SELECT clientid, project_id,
                    SUM(CASE WHEN category = 2 THEN amount ELSE 0 END) as service_fee,
                    SUM(CASE WHEN category = 1 THEN amount_2 ELSE 0 END) as inland_transport_b,
                    SUM(CASE WHEN category = 3 THEN amount_2 ELSE 0 END) as sea_transport_b,
                    SUM(CASE WHEN category NOT IN (1,2,3) THEN amount ELSE 0 END) as others_expenses
                FROM " . db_prefix() . "expenses
                GROUP BY clientid, project_id
            ) e ON e.clientid = po.clients 
            AND e.project_id = po.project

            LEFT JOIN (
                SELECT 
                    i.clientid,
                    i.project_id,
                    SUM(i.invoice_amount) as invoice_amount,
                    SUM(i.total) as invoice_total,
                    MAX(c.name) as currency_name
                FROM " . db_prefix() . "invoices i
                LEFT JOIN " . db_prefix() . "currencies c ON c.id = i.currency
                GROUP BY i.clientid, i.project_id
            ) inv ON inv.clientid = po.clients AND inv.project_id = po.project

            LEFT JOIN (
                SELECT ca.customer_id,
                    GROUP_CONCAT(CONCAT(s.firstname,' ',s.lastname)) as admin_names
                FROM " . db_prefix() . "customer_admins ca
                JOIN " . db_prefix() . "staff s 
                    ON s.staffid = ca.staff_id
                GROUP BY ca.customer_id
            ) adm ON adm.customer_id = po.clients

            WHERE po.clients = ?
            ORDER BY p.start_date DESC
        ";

        return $this->db->query($sql, [$client_id])->result_array();
    }

    public function get_customers_with_negative_balance()
    {
        $aColumns = [
            'p.start_date as start_date',
            'p.name as project_name',
            'IFNULL(gr.total_goods_value,0) as total_goods_value',
            'IFNULL(e.service_fee,0) as service_fee',
            'IFNULL(e.inland_transport,0) as inland_transport',
            'IFNULL(e.inland_transport_b,0) as inland_transport_b',
            'IFNULL(e.others_expenses,0) as others_expenses',
            '(IFNULL(gr.total_goods_value,0)+IFNULL(e.service_fee,0)+IFNULL(e.inland_transport,0)+IFNULL(e.others_expenses,0)) as total',
            'IFNULL(inv.invoice_amount,0) as invoice_amount',
            'IFNULL(inv.invoice_total,0) as invoice_total',
            'inv.currency_name as currency_name',
            '(IFNULL(inv.invoice_total,0)- IFNULL(gr.total_goods_value,0)- IFNULL(e.service_fee,0)- IFNULL(e.inland_transport,0)- IFNULL(e.others_expenses,0)) as balance',
            'cl.company as company',
        ];

        $sIndexColumn = 'po.id';
        $sTable = '(SELECT MIN(id) AS id, project, clients FROM ' . db_prefix() . 'pur_orders GROUP BY project, clients) as po';

        $additionalSelect = [
            'po.id as id',
            'po.project as project_id',
            'po.clients as client_id',
        ];

        $join = [
            'LEFT JOIN ' . db_prefix() . 'clients as cl ON cl.userid = po.clients',
            'LEFT JOIN ' . db_prefix() . 'projects as p ON p.id = po.project',

            'LEFT JOIN (
                SELECT po.clients, po.project, SUM(gr.total_goods_money) AS total_goods_value
                FROM ' . db_prefix() . 'pur_orders po
                LEFT JOIN ' . db_prefix() . 'goods_receipt gr ON gr.pr_order_id = po.id
                GROUP BY po.clients, po.project
            ) gr ON gr.clients = po.clients AND gr.project = po.project',

            'LEFT JOIN (
                SELECT clientid, project_id,
                    SUM(CASE WHEN category = 2 THEN amount ELSE 0 END) AS service_fee,
                    SUM(CASE WHEN category = 1 THEN amount ELSE 0 END) AS inland_transport,
                    SUM(CASE WHEN category = 1 THEN amount_2 ELSE 0 END) AS inland_transport_b,
                    SUM(CASE WHEN category NOT IN (1,2,3) THEN amount ELSE 0 END) AS others_expenses
                FROM ' . db_prefix() . 'expenses
                GROUP BY clientid, project_id
            ) e ON e.clientid = po.clients AND e.project_id = po.project',

            'LEFT JOIN (
                SELECT 
                    i.clientid,
                    i.project_id,
                    SUM(i.invoice_amount) AS invoice_amount,
                    SUM(i.total) AS invoice_total,
                    MAX(c.name) AS currency_name
                FROM ' . db_prefix() . 'invoices i
                LEFT JOIN ' . db_prefix() . 'currencies c ON c.id = i.invoice_currency
                GROUP BY i.clientid, i.project_id
            ) inv ON inv.clientid = po.clients AND inv.project_id = po.project',
        ];

        $where = [];
        $where[] = 'AND (IFNULL(inv.invoice_total,0) - IFNULL(gr.total_goods_value,0) - IFNULL(e.service_fee,0) - IFNULL(e.inland_transport,0) - IFNULL(e.others_expenses,0)) < 0';

        $result = data_tables_init(
            $aColumns,
            $sIndexColumn,
            $sTable,
            $join,
            $where,
            $additionalSelect,
            ''
        );

        $rResult = $result['rResult'];

        $final = [];
        foreach ($rResult as $aRow) {
            $row = [];
            $row['customer_name'] = $aRow['company'];
            $row['balance_due'] = $aRow['balance'];
            $final[] = $row;
        }

        return $final;
    }

}
