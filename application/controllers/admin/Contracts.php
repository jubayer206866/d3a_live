<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Contracts extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contracts_model');
        $this->load->model('emails_model');
    }

    /* List all contracts */
    public function index()
    {
        close_setup_menu();

        if (staff_cant('view', 'contracts') && staff_cant('view_own', 'contracts')) {
            access_denied('contracts');
        }

        $data['expiring'] = $this->contracts_model->get_contracts_about_to_expire(get_staff_user_id());
        $data['count_active'] = count_active_contracts();
        $data['count_expired'] = count_expired_contracts();
        $data['count_recently_created'] = count_recently_created_contracts();
        $data['count_trash'] = count_trash_contracts();
        $data['chart_types'] = json_encode($this->contracts_model->get_contracts_types_chart_data());
        $data['chart_types_values'] = json_encode($this->contracts_model->get_contracts_types_values_chart_data());
        $data['contract_types'] = $this->contracts_model->get_contract_types();
        $data['years'] = $this->contracts_model->get_contracts_years();
        $this->load->model('currencies_model');
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $data['title'] = _l('contracts');
        $data['table'] = App_table::find('contracts');
        $this->load->view('admin/contracts/manage', $data);
    }

    public function table($clientid = '')
    {
        if (staff_cant('view', 'contracts') && staff_cant('view_own', 'contracts')) {
            ajax_access_denied();
        }

        App_table::find('contracts')->output([
            'clientid' => $clientid,
        ]);
    }

    /* Edit contract or add new contract */
    public function contract($id = '')
    {
        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'contracts')) {
                    access_denied('contracts');
                }
                $id = $this->contracts_model->add($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('contract')));
                    redirect(admin_url('contracts/contract/' . $id));
                }
            } else {
                if (staff_cant('edit', 'contracts')) {
                    access_denied('contracts');
                }
                $contract = $this->contracts_model->get($id);
                $data = $this->input->post();

                if ($contract->signed == 1) {
                    unset($data['contract_value'], $data['clientid'], $data['datestart'], $data['dateend']);
                }

                $success = $this->contracts_model->update($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('contract')));
                }
                redirect(admin_url('contracts/contract/' . $id));
            }
        }
        if ($id == '') {
            $title = _l('add_new', _l('contract'));
        } else {
            $data['contract'] = $this->contracts_model->get($id, [], true);
            $data['contract_renewal_history'] = $this->contracts_model->get_contract_renewal_history($id);
            $data['totalNotes'] = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'contract']);
            if (!$data['contract'] || (staff_cant('view', 'contracts') && $data['contract']->addedfrom != get_staff_user_id())) {
                blank_page(_l('contract_not_found'));
            }

            $data['contract_merge_fields'] = $this->app_merge_fields->get_flat('contract', ['other', 'client'], '{email_signature}');

            $title = $data['contract']->subject;

            $data = array_merge($data, prepare_mail_preview_data('contract_send_to_customer', $data['contract']->client));
        }

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        $this->load->model('currencies_model');
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $data['types'] = $this->contracts_model->get_contract_types();
        $data['title'] = $title;
        $data['bodyclass'] = 'contract';
        $this->load->view('admin/contracts/contract', $data);
    }

    public function get_template()
    {
        $name = $this->input->get('name');
        echo $this->load->view('admin/contracts/templates/' . $name, [], true);
    }

    public function mark_as_signed($id)
    {
        if (staff_cant('edit', 'contracts')) {
            access_denied('mark contract as signed');
        }

        $this->contracts_model->mark_as_signed($id);

        redirect(admin_url('contracts/contract/' . $id));
    }

    public function unmark_as_signed($id)
    {
        if (staff_cant('edit', 'contracts')) {
            access_denied('mark contract as signed');
        }

        $this->contracts_model->unmark_as_signed($id);

        redirect(admin_url('contracts/contract/' . $id));
    }

    public function pdf($id)
    {
        if (staff_cant('view', 'contracts') && staff_cant('view_own', 'contracts')) {
            access_denied('contracts');
        }

        if (!$id) {
            redirect(admin_url('contracts'));
        }

        $contract = $this->contracts_model->get($id);

        try {
            $pdf = contract_pdf($contract);
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $pdf->Output(slug_it($contract->subject) . '.pdf', $type);
    }

    public function send_to_email($id)
    {
        if (staff_cant('view', 'contracts') && staff_cant('view_own', 'contracts')) {
            access_denied('contracts');
        }
        $success = $this->contracts_model->send_contract_to_client($id, $this->input->post('attach_pdf'), $this->input->post('cc'));
        if ($success) {
            set_alert('success', _l('contract_sent_to_client_success'));
        } else {
            set_alert('danger', _l('contract_sent_to_client_fail'));
        }
        redirect(admin_url('contracts/contract/' . $id));
    }

    public function add_note($rel_id)
    {
        if ($this->input->post() && (staff_can('view', 'contracts') || staff_can('view_own', 'contracts'))) {
            $this->misc_model->add_note($this->input->post(), 'contract', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if ((staff_can('view', 'contracts') || staff_can('view_own', 'contracts'))) {
            $data['notes'] = $this->misc_model->get_notes($id, 'contract');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }

    public function clear_signature($id)
    {
        if (staff_can('delete', 'contracts')) {
            $this->contracts_model->clear_signature($id);
        }

        redirect(admin_url('contracts/contract/' . $id));
    }

    public function save_contract_data()
    {
        if (staff_cant('edit', 'contracts')) {
            header('HTTP/1.0 400 Bad error');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied'),
            ]);
            die;
        }

        $success = false;
        $message = '';

        $this->db->where('id', $this->input->post('contract_id'));
        $this->db->update(db_prefix() . 'contracts', [
            'content' => html_purify($this->input->post('content', false)),
        ]);

        $success = $this->db->affected_rows() > 0;
        $message = _l('updated_successfully', _l('contract'));

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    public function add_comment()
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->contracts_model->add_comment($this->input->post()),
            ]);
        }
    }

    public function edit_comment($id)
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->contracts_model->edit_comment($this->input->post(), $id),
                'message' => _l('comment_updated_successfully'),
            ]);
        }
    }

    public function get_comments($id)
    {
        $data['comments'] = $this->contracts_model->get_comments($id);
        $this->load->view('admin/contracts/comments_template', $data);
    }

    public function remove_comment($id)
    {
        $this->db->where('id', $id);
        $comment = $this->db->get(db_prefix() . 'contract_comments')->row();
        if ($comment) {
            if ($comment->staffid != get_staff_user_id() && !is_admin()) {
                echo json_encode([
                    'success' => false,
                ]);
                die;
            }
            echo json_encode([
                'success' => $this->contracts_model->remove_comment($id),
            ]);
        } else {
            echo json_encode([
                'success' => false,
            ]);
        }
    }

    public function renew()
    {
        if (staff_cant('edit', 'contracts')) {
            access_denied('contracts');
        }
        if ($this->input->post()) {
            $data = $this->input->post();
            $success = $this->contracts_model->renew($data);
            if ($success) {
                set_alert('success', _l('contract_renewed_successfully'));
            } else {
                set_alert('warning', _l('contract_renewed_fail'));
            }
            redirect(admin_url('contracts/contract/' . $data['contractid'] . '?tab=renewals'));
        }
    }

    public function delete_renewal($renewal_id, $contractid)
    {
        $success = $this->contracts_model->delete_renewal($renewal_id, $contractid);
        if ($success) {
            set_alert('success', _l('contract_renewal_deleted'));
        } else {
            set_alert('warning', _l('contract_renewal_delete_fail'));
        }
        redirect(admin_url('contracts/contract/' . $contractid . '?tab=renewals'));
    }

    public function copy($id)
    {
        if (staff_cant('create', 'contracts')) {
            access_denied('contracts');
        }
        if (!$id) {
            redirect(admin_url('contracts'));
        }
        $newId = $this->contracts_model->copy($id);
        if ($newId) {
            set_alert('success', _l('contract_copied_successfully'));
        } else {
            set_alert('warning', _l('contract_copied_fail'));
        }
        redirect(admin_url('contracts/contract/' . $newId));
    }

    /* Delete contract from database */
    public function delete($id)
    {
        if (staff_cant('delete', 'contracts')) {
            access_denied('contracts');
        }
        if (!$id) {
            redirect(admin_url('contracts'));
        }
        $response = $this->contracts_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('deleted', _l('contract')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('contract_lowercase')));
        }

        redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
    }

    /* Manage contract types Since Version 1.0.3 */
    public function type($id = '')
    {
        if (!is_admin() && get_option('staff_members_create_inline_contract_types') == '0') {
            access_denied('contracts');
        }
        if ($this->input->post()) {
            if (!$this->input->post('id')) {
                $id = $this->contracts_model->add_contract_type($this->input->post());
                if ($id) {
                    $success = true;
                    $message = _l('added_successfully', _l('contract_type'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'id' => $id,
                    'name' => $this->input->post('name'),
                ]);
            } else {
                $data = $this->input->post();
                $id = $data['id'];
                unset($data['id']);
                $success = $this->contracts_model->update_contract_type($data, $id);
                $message = '';
                if ($success) {
                    $message = _l('updated_successfully', _l('contract_type'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                ]);
            }
        }
    }

    public function types()
    {
        if (!is_admin()) {
            access_denied('contracts');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('contract_types');
        }
        $data['title'] = _l('contract_types');
        $this->load->view('admin/contracts/manage_types', $data);
    }

    /* Delete announcement from database */
    public function delete_contract_type($id)
    {
        if (!$id) {
            redirect(admin_url('contracts/types'));
        }
        if (!is_admin()) {
            access_denied('contracts');
        }
        $response = $this->contracts_model->delete_contract_type($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('contract_type_lowercase')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('contract_type')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('contract_type_lowercase')));
        }
        redirect(admin_url('contracts/types'));
    }

    public function add_contract_attachment($id)
    {
        handle_contract_attachment($id);
    }

    public function add_external_attachment()
    {
        if ($this->input->post()) {
            $this->misc_model->add_attachment_to_database(
                $this->input->post('contract_id'),
                'contract',
                $this->input->post('files'),
                $this->input->post('external')
            );
        }
    }

    public function delete_contract_attachment($attachment_id)
    {
        $file = $this->misc_model->get_file($attachment_id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo json_encode([
                'success' => $this->contracts_model->delete_contract_attachment($attachment_id),
            ]);
        }
    }

    //DDP Contract
    public function ddp_contract_template()
    {
        if ($this->input->post()) {
            $template = $this->input->post('template', false);

            $check = $this->db->get_where(db_prefix() . 'contact_templates', ['type' => 'ddp_contract'])->row();
            if ($check) {
                $this->db->where('type', 'ddp_contract');
                $this->db->update(db_prefix() . 'contact_templates', ['template' => $template]);
            } else {
                $this->db->insert(db_prefix() . 'contact_templates', [
                    'type' => 'ddp_contract',
                    'template' => $template,
                ]);
            }

            set_alert('success', _l('updated_successfully', _l('DDP Contract Template')));
            redirect(admin_url('contracts/ddp_contract_template'));
        }

        $merge_fields = [
            ['name' => 'Date', 'key' => '{date}'],
            ['name' => 'Company Name', 'key' => '{company_name}'],
            ['name' => 'Company Address', 'key' => '{company_address}'],
            ['name' => 'Company City', 'key' => '{company_city}'],
            ['name' => 'Company Country', 'key' => '{company_country}'],
            ['name' => 'Company VAT Number', 'key' => '{company_vat_number}'],
            ['name' => 'Client Name', 'key' => '{client_name}'],
            ['name' => 'Client City', 'key' => '{client_city}'],
            ['name' => 'Client Country', 'key' => '{client_country}'],
            ['name' => 'Warehouse Location', 'key' => '{warehouse_location}'],
            ['name' => 'Service Charge', 'key' => '{service_charge}'],
            ['name' => 'Deposit Mode', 'key' => '{deposit_mode}'],
            ['name' => 'Deposit Value', 'key' => '{deposit_value}'],
            ['name' => 'Payment Mode', 'key' => '{payment_mode}'],
            ['name' => 'Account Currency', 'key' => '{account_currency}'],
        ];

        $data['available_merge_fields'][0]['DDP Merge Field'] = $merge_fields;

        $this->db->where('type', 'ddp_contract');
        $template_row = $this->db->get_where(db_prefix() . 'contact_templates', ['type' => 'ddp_contract'])->row_array();
        $data['template'] = $template_row ? [$template_row] : [['template' => '']];
        $this->load->view('admin/contracts/ddp_contract_template', $data);
    }


    public function ddp_contract()
    {
        if (!is_admin()) {
            access_denied('DDP Contracts');
        }

        if ($this->input->is_ajax_request()) {
            $aColumns = ['id', 'name'];
            $sIndexColumn = 'id';
            $sTable = db_prefix() . 'contact_fields_value';
            $where = ["AND type = 'ddp_contract'"];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where, ['id']);
            $output = $result['output'];
            $rResult = $result['rResult'];
            $count = 1;
            foreach ($rResult as $aRow) {
                $row = [];
                $row[] = $count++;

                foreach ($aColumns as $col) {
                    if ($col == 'id') {
                        continue;
                    }
                    $_data = $aRow[$col];
                    if ($col == 'name') {
                        $_data = '<a href="' . admin_url('contracts/edit_ddp/' . $aRow['id']) . '" class="tw-font-medium">' . $aRow['name'] . '</a>';
                    }
                    $row[] = $_data;
                }

                $options = '<a href="' . admin_url('contracts/edit_ddp/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 tw-mr-2">
                    <i class="fa-regular fa-pen-to-square fa-lg"></i>
                </a>';

                $options .= '<a href="' . admin_url('contracts/delete_ddp/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete tw-ml-2">
                    <i class="fa-regular fa-trash-can fa-lg"></i>
                </a>';
                $options .= '<a href="' . admin_url('contracts/print_ddp/' . $aRow['id']) . '" target="_blank" class="btn btn-sm btn-success tw-ml-4">
                    <i class="fa fa-print"></i> Print
                </a>';

                $row[] = $options;
                $output['aaData'][] = $row;
            }

            echo json_encode($output);
            exit();
        }

        $data['title'] = _l('DDP Contracts');
        $data['ddp_list'] = $this->db->get_where(db_prefix() . 'contact_fields_value', ['type' => 'ddp_contract'])->result_array();
        $this->load->view('admin/contracts/ddp_contract_manage', $data);
    }

    public function add_ddp()
    {
        $this->db->select('userid, company, address');
        $query = $this->db->get('clients');
        $data['clients'] = $query->result_array();

        $this->load->view('admin/contracts/ddp_contract', $data);
    }

    public function get_client_details()
    {
        $userid = $this->input->post('userid');

        $this->db->select('company, vat, city, tblcountries.short_name,address');
        $this->db->from('clients');
        $this->db->join(
            db_prefix() . 'countries',
            db_prefix() . 'countries.country_id = ' . db_prefix() . 'clients.country',
            'left'
        );
        $this->db->where('clients.userid', $userid);

        $query = $this->db->get();
        $client = $query->row_array();


        $response = $client
            ? ['success' => true, 'client' => $client]
            : ['success' => false, 'message' => 'Client not found'];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function save_ddp()
    {
        if ($this->input->post()) {
            $data = $this->input->post();

            $save_data['name'] = $data['template_name'];
            unset($data['template_name']);
            $save_data['fields_value'] = json_encode($data);
            $save_data['type'] = 'ddp_contract';

            $this->db->insert('contact_fields_value', $save_data);
            $insert_id = $this->db->insert_id();

            if ($insert_id) {
                set_alert('success', 'DDP contract saved successfully.');
            } else {
                set_alert('error', 'Failed to save DDP contract.');
            }
            redirect(admin_url('contracts/ddp_contract'));
        }

        $this->ddp_contract();
    }

    public function edit_ddp($id = '')
    {
        if (!is_admin()) {
            access_denied('DDP Contracts');
        }

        if ($this->input->post()) {
            $data = $this->input->post();

            $update_data['name'] = $data['template_name'];
            unset($data['template_name']);

            $update_data['fields_value'] = json_encode($data);
            $update_data['type'] = 'ddp_contract';

            $this->db->where('id', $id);
            $this->db->where('type', 'ddp_contract');
            $success = $this->db->update(db_prefix() . 'contact_fields_value', $update_data);

            if ($success) {
                set_alert('success', _l('updated_successfully', _l('DDP Contract')));
            } else {
                set_alert('warning', _l('problem_updating', _l('DDP Contract')));
            }

            redirect(admin_url('contracts/ddp_contract'));
        }

        $ddp = $this->db->get_where(db_prefix() . 'contact_fields_value', [
            'id' => $id,
            'type' => 'ddp_contract'
        ])->row();

        if ($ddp) {
            $ddp->fields = json_decode($ddp->fields_value, true);
        }

        $data['ddp'] = $ddp;
        $data['title'] = _l('edit') . ' ' . _l('DDP Contract');
        $data['clients'] = $this->db->get(db_prefix() . 'clients')->result_array();

        $this->load->view('admin/contracts/ddp_contract', $data);
    }


    public function delete_ddp($id)
    {
        if (!is_admin()) {
            access_denied('DDP Contracts');
        }

        if (!$id) {
            redirect(admin_url('contracts/ddp_contract'));
        }

        $this->db->where('id', $id);
        $this->db->where('type', 'ddp_contract');
        $success = $this->db->delete(db_prefix() . 'contact_fields_value');

        if ($success) {
            set_alert('success', _l('deleted', _l('DDP Contract')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('DDP Contract')));
        }

        redirect(admin_url('contracts/ddp_contract'));
    }

    public function print_ddp($id)
    {
        if (!is_admin()) {
            access_denied('DDP Contracts');
        }
        $ddp = $this->db->get_where(db_prefix() . 'contact_fields_value', [
            'id' => $id,
            'type' => 'ddp_contract'
        ])->row();

        if (!$ddp) {
            show_404();
        }

        $ddp->fields = json_decode($ddp->fields_value, true);
        $template_row = $this->db->get_where(db_prefix() . 'contact_templates', [
            'type' => 'ddp_contract'
        ])->row_array();

        $template = $template_row ? $template_row['template'] : '';
        $merge_fields = [
            '{date}' => isset($ddp->fields['date']) ? $ddp->fields['date'] : '',
            '{company_name}' => isset($ddp->fields['company_name']) ? $ddp->fields['company_name'] : '',
            '{company_address}' => isset($ddp->fields['company_address']) ? $ddp->fields['company_address'] : '',
            '{company_vat_number}' => isset($ddp->fields['company_vat_number']) ? $ddp->fields['company_vat_number'] : '',
            '{company_city}' => isset($ddp->fields['company_city']) ? $ddp->fields['company_city'] : '',
            '{company_country}' => isset($ddp->fields['company_country']) ? $ddp->fields['company_country'] : '',
            '{client_name}' => isset($ddp->fields['client_name']) ? $ddp->fields['client_name'] : '',
            '{client_city}' => isset($ddp->fields['client_city']) ? $ddp->fields['client_city'] : '',
            '{client_country}' => isset($ddp->fields['client_country']) ? $ddp->fields['client_country'] : '',
            '{warehouse_location}' => isset($ddp->fields['warehouse_location']) ? $ddp->fields['warehouse_location'] : '',
            '{service_charge}' => isset($ddp->fields['service_charge']) ? $ddp->fields['service_charge'] : '',
            '{deposit_mode}' => isset($ddp->fields['deposit_mode']) ? $ddp->fields['deposit_mode'] : '',
            '{deposit_value}' => isset($ddp->fields['deposit_value']) ? $ddp->fields['deposit_value'] : '',
            '{payment_mode}' => isset($ddp->fields['payment_mode']) ? $ddp->fields['payment_mode'] : '',
            '{account_currency}' => isset($ddp->fields['account_currency']) ? $ddp->fields['account_currency'] : '',
        ];
        $filled_template = str_replace(array_keys($merge_fields), array_values($merge_fields), $template);

        $data['contract'] = $ddp;
        $data['filled_template'] = $filled_template;
        //echo'<pre>';print_r($data);exit;

        $this->load->view('admin/contracts/print_ddp', $data);
    }


    //LCL Contract
    public function lcl_contract_template()
    {
        if ($this->input->post()) {
            $template = $this->input->post('template', false);

            $check = $this->db->get_where(db_prefix() . 'contact_templates', ['type' => 'lcl_contract'])->row();
            if ($check) {
                $this->db->where('type', 'lcl_contract');
                $this->db->update(db_prefix() . 'contact_templates', ['template' => $template]);
            } else {
                $this->db->insert(db_prefix() . 'contact_templates', [
                    'type' => 'lcl_contract',
                    'template' => $template,
                ]);
            }

            set_alert('success', _l('updated_successfully', _l('LCL Contract Template')));
            redirect(admin_url('contracts/lcl_contract_template'));
        }

        $merge_fields = [
            ['name' => 'Date', 'key' => '{date}'],
            ['name' => 'Company Name', 'key' => '{company_name}'],
            ['name' => 'Company Address', 'key' => '{company_address}'],
            ['name' => 'Company City', 'key' => '{company_city}'],
            ['name' => 'Company Country', 'key' => '{company_country}'],
            ['name' => 'Company VAT Number', 'key' => '{company_vat_number}'],
            ['name' => 'Client Name', 'key' => '{client_name}'],
            ['name' => 'Client City', 'key' => '{client_city}'],
            ['name' => 'Client Country', 'key' => '{client_country}'],
            ['name' => 'Warehouse Location', 'key' => '{warehouse_location}'],
            ['name' => 'Service Charge', 'key' => '{service_charge}'],
            ['name' => 'Account Currency', 'key' => '{account_currency}'],
            ['name' => 'Deposit Value', 'key' => '{deposit_value}'],
            ['name' => 'Payment Mode', 'key' => '{payment_mode}'],
        ];

        $data['available_merge_fields'][0]['LCL Merge Field'] = $merge_fields;

        $this->db->where('type', 'lcl_contract');
        $template_row = $this->db->get_where(db_prefix() . 'contact_templates', ['type' => 'lcl_contract'])->row_array();
        $data['template'] = $template_row ? [$template_row] : [['template' => '']];

        $this->load->view('admin/contracts/lcl_contract_template', $data);
    }

    public function lcl_contract()
    {
        if (!is_admin()) {
            access_denied('LCL Contracts');
        }

        if ($this->input->is_ajax_request()) {
            $aColumns = ['id', 'name'];
            $sIndexColumn = 'id';
            $sTable = db_prefix() . 'contact_fields_value';
            $where = ["AND type = 'lcl_contract'"];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where, ['id']);
            $output = $result['output'];
            $rResult = $result['rResult'];
            $count = 1;
            foreach ($rResult as $aRow) {
                $row = [];
                $row[] = $count++;
                foreach ($aColumns as $col) {
                    if ($col == 'id') {
                        continue;
                    }
                    $_data = $aRow[$col];
                    if ($col == 'name') {
                        $_data = '<a href="' . admin_url('contracts/edit_lcl/' . $aRow['id']) . '" class="tw-font-medium">' . $aRow['name'] . '</a>';
                    }
                    $row[] = $_data;
                }

                $options = '<a href="' . admin_url('contracts/edit_lcl/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 tw-mr-2">
                    <i class="fa-regular fa-pen-to-square fa-lg"></i>
                </a>';

                $options .= '<a href="' . admin_url('contracts/delete_lcl/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete tw-ml-2">
                    <i class="fa-regular fa-trash-can fa-lg"></i>
                </a>';
                $options .= '<a href="' . admin_url('contracts/print_lcl/' . $aRow['id']) . '" target="_blank" class="btn btn-sm btn-success tw-ml-4">
                    <i class="fa fa-print"></i> Print
                </a>';

                $row[] = $options;
                $output['aaData'][] = $row;
            }

            echo json_encode($output);
            exit();
        }

        $data['title'] = _l('LCL Contracts');
        $data['lcl_list'] = $this->db->get_where(db_prefix() . 'contact_fields_value', ['type' => 'lcl_contract'])->result_array();
        $this->load->view('admin/contracts/lcl_contract_manage', $data);
    }

    public function add_lcl()
    {
        $this->db->select('userid, company, address');
        $query = $this->db->get('clients');
        $data['clients'] = $query->result_array();

        $this->load->view('admin/contracts/lcl_contract', $data);
    }


    public function save_lcl()
    {
        if ($this->input->post()) {
            $data = $this->input->post();

            $save_data['name'] = $data['template_name'];
            unset($data['template_name']);
            $save_data['fields_value'] = json_encode($data);
            $save_data['type'] = 'lcl_contract';

            $this->db->insert('contact_fields_value', $save_data);
            $insert_id = $this->db->insert_id();

            if ($insert_id) {
                set_alert('success', 'LCL contract saved successfully.');
            } else {
                set_alert('error', 'Failed to save LCL contract.');
            }
            redirect(admin_url('contracts/lcl_contract'));
        }

        $this->lcl_contract();
    }

    public function edit_lcl($id = '')
    {
        if (!is_admin()) {
            access_denied('LCL Contracts');
        }

        if ($this->input->post()) {
            $data = $this->input->post();

            $update_data['name'] = $data['template_name'];
            unset($data['template_name']);

            $update_data['fields_value'] = json_encode($data);
            $update_data['type'] = 'lcl_contract';

            $this->db->where('id', $id);
            $this->db->where('type', 'lcl_contract');
            $success = $this->db->update(db_prefix() . 'contact_fields_value', $update_data);

            if ($success) {
                set_alert('success', _l('updated_successfully', _l('LCL Contract')));
            } else {
                set_alert('warning', _l('problem_updating', _l('LCL Contract')));
            }

            redirect(admin_url('contracts/lcl_contract'));
        }

        $lcl = $this->db->get_where(db_prefix() . 'contact_fields_value', [
            'id' => $id,
            'type' => 'lcl_contract'
        ])->row();

        if ($lcl) {
            $lcl->fields = json_decode($lcl->fields_value, true);
        }

        $data['lcl'] = $lcl;
        $data['title'] = _l('edit') . ' ' . _l('LCL Contract');
        $data['clients'] = $this->db->get(db_prefix() . 'clients')->result_array();

        $this->load->view('admin/contracts/lcl_contract', $data);
    }


    public function delete_lcl($id)
    {
        if (!is_admin()) {
            access_denied('LCL Contracts');
        }

        if (!$id) {
            redirect(admin_url('contracts/lcl_contract'));
        }

        $this->db->where('id', $id);
        $this->db->where('type', 'lcl_contract');
        $success = $this->db->delete(db_prefix() . 'contact_fields_value');

        if ($success) {
            set_alert('success', _l('deleted', _l('lcl Contract')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('lcl Contract')));
        }

        redirect(admin_url('contracts/lcl_contract'));
    }

    public function print_lcl($id)
    {
        if (!is_admin()) {
            access_denied('LCL Contracts');
        }
        $lcl = $this->db->get_where(db_prefix() . 'contact_fields_value', [
            'id' => $id,
            'type' => 'lcl_contract'
        ])->row();

        if (!$lcl) {
            show_404();
        }

        $lcl->fields = json_decode($lcl->fields_value, true);
        $template_row = $this->db->get_where(db_prefix() . 'contact_templates', [
            'type' => 'lcl_contract'
        ])->row_array();

        $template = $template_row ? $template_row['template'] : '';
        $merge_fields = [
            '{date}' => isset($lcl->fields['date']) ? $lcl->fields['date'] : '',
            '{company_name}' => isset($lcl->fields['company_name']) ? $lcl->fields['company_name'] : '',
            '{company_address}' => isset($lcl->fields['company_address']) ? $lcl->fields['company_address'] : '',
            '{company_vat_number}' => isset($lcl->fields['company_vat_number']) ? $lcl->fields['company_vat_number'] : '',
            '{company_city}' => isset($lcl->fields['company_city']) ? $lcl->fields['company_city'] : '',
            '{company_country}' => isset($lcl->fields['company_country']) ? $lcl->fields['company_country'] : '',
            '{client_name}' => isset($lcl->fields['client_name']) ? $lcl->fields['client_name'] : '',
            '{client_city}' => isset($lcl->fields['client_city']) ? $lcl->fields['client_city'] : '',
            '{client_country}' => isset($lcl->fields['client_country']) ? $lcl->fields['client_country'] : '',
            '{warehouse_location}' => isset($lcl->fields['warehouse_location']) ? $lcl->fields['warehouse_location'] : '',
            '{service_charge}' => isset($lcl->fields['service_charge']) ? $lcl->fields['service_charge'] : '',
            '{account_currency}' => isset($lcl->fields['account_currency']) ? $lcl->fields['account_currency'] : '',
            '{deposit_value}' => isset($lcl->fields['deposit_value']) ? $lcl->fields['deposit_value'] : '',
            '{payment_mode}' => isset($lcl->fields['payment_mode']) ? $lcl->fields['payment_mode'] : '',
        ];

        $filled_template = str_replace(array_keys($merge_fields), array_values($merge_fields), $template);

        $data['contract'] = $lcl;
        $data['filled_template'] = $filled_template;

        $this->load->view('admin/contracts/print_lcl', $data);
    }


    //Landing Contract
    public function landing_contract_template()
    {
        if ($this->input->post()) {
            $template = $this->input->post('template', false);
            $check = $this->db->get_where(db_prefix() . 'contact_templates', ['type' => 'landing_contract'])->row();
            if ($check) {
                $this->db->where('type', 'landing_contract');
                $this->db->update(db_prefix() . 'contact_templates', ['template' => html_purify($template)]);
            } else {
                $this->db->insert(db_prefix() . 'contact_templates', [
                    'type' => 'landing_contract',
                    'template' => html_purify($template),
                ]);
            }

            set_alert('success', _l('updated_successfully', _l('Landing Contract Template')));
            redirect(admin_url('contracts/landing_contract_template'));
        }
        $merge_fields = [
            ['name' => 'Date', 'key' => '{date}'],
            ['name' => 'Trading Company Name', 'key' => '{trading_company_name}'],
            ['name' => 'Trading Company Address', 'key' => '{trading_company_address}'],
            ['name' => 'Trading Company Vat', 'key' => '{trading_company_vat}'],
            ['name' => 'Trading Company City', 'key' => '{trading_company_city}'],
            ['name' => 'Trading Company Country', 'key' => '{trading_company_country}'],


            ['name' => 'Lending Company Name', 'key' => '{lending_company_name}'],
            ['name' => 'Lending Company Address', 'key' => '{lending_company_address}'],
            ['name' => 'Lending Company Vat', 'key' => '{lending_company_vat}'],
            ['name' => 'Lending Company City', 'key' => '{lending_company_city}'],
            ['name' => 'Lending Company Country', 'key' => '{lending_company_country}'],


            ['name' => 'Borrowing Company Name', 'key' => '{borrowing_company_name}'],
            ['name' => 'Borrowing Company Address', 'key' => '{borrowing_company_address}'],
            ['name' => 'Borrowing Company Vat', 'key' => '{borrowing_company_vat}'],
            ['name' => 'Borrowing Company City', 'key' => '{borrowing_company_city}'],
            ['name' => 'Borrowing Company Country', 'key' => '{borrowing_company_country}'],


            ['name' => 'Lending Amount', 'key' => '{lending_amount}'],
            ['name' => 'Interest', 'key' => '{interest}'],
            ['name' => 'Days (Landing Time)', 'key' => '{days}'],
            ['name' => 'Initial Paid Amount', 'key' => '{initial_paid_amount}'],
            ['name' => 'Late Payment Amount Per Day', 'key' => '{late_payment_amount_per_day}'],
            ['name' => 'Repayment Date', 'key' => '{repayment_date}'],
            ['name' => 'Late Payment Days', 'key' => '{late_payment_days}'],
            ['name' => 'Repayment Mode', 'key' => '{repayment_mode}'],
        ];

        $data['available_merge_fields'][0]['Landing Merge Field'] = $merge_fields;

        $this->db->where('type', 'landing_contract');
        $template_row = $this->db->get_where(db_prefix() . 'contact_templates', ['type' => 'landing_contract'])->row_array();
        $data['template'] = $template_row ? [$template_row] : [['template' => '']];

        $this->load->view('admin/contracts/landing_contract_template', $data);
    }

    public function landing_contract()
    {
        if (!is_admin()) {
            access_denied('Landing Contracts');
        }

        if ($this->input->is_ajax_request()) {
            $aColumns = ['id', 'name'];
            $sIndexColumn = 'id';
            $sTable = db_prefix() . 'contact_fields_value';
            $where = ["AND type = 'landing_contract'"];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where, ['id']);
            $output = $result['output'];
            $rResult = $result['rResult'];
            $count = 1;
            foreach ($rResult as $aRow) {
                $row = [];
                $row[] = $count++;
                foreach ($aColumns as $col) {
                    if ($col == 'id') {
                        continue;
                    }
                    $_data = $aRow[$col];
                    if ($col == 'name') {
                        $_data = '<a href="' . admin_url('contracts/edit_landing/' . $aRow['id']) . '" class="tw-font-medium">' . $aRow['name'] . '</a>';
                    }
                    $row[] = $_data;
                }

                $options = '<a href="' . admin_url('contracts/edit_landing/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 tw-mr-2">
                    <i class="fa-regular fa-pen-to-square fa-lg"></i>
                </a>';

                $options .= '<a href="' . admin_url('contracts/delete_landing/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete tw-ml-2">
                    <i class="fa-regular fa-trash-can fa-lg"></i>
                </a>';
                $options .= '<a href="' . admin_url('contracts/print_landing/' . $aRow['id']) . '" target="_blank" class="btn btn-sm btn-success tw-ml-4">
                    <i class="fa fa-print"></i> Print
                </a>';

                $row[] = $options;
                $output['aaData'][] = $row;
            }

            echo json_encode($output);
            exit();
        }

        $data['title'] = _l('Landing Contracts');
        $data['landing_list'] = $this->db->get_where(db_prefix() . 'contact_fields_value', ['type' => 'landing_contract'])->result_array();
        $this->load->view('admin/contracts/landing_contract_manage', $data);
    }

    public function add_landing()
    {
        $this->db->select('userid, company, address');
        $query = $this->db->get('clients');
        $data['clients'] = $query->result_array();

        $this->load->view('admin/contracts/landing_contract', $data);
    }



    public function save_landing()
    {
        if ($this->input->post()) {
            $data = $this->input->post();

            $save_data['name'] = $data['template_name'];
            unset($data['template_name']);
            $save_data['fields_value'] = json_encode($data);
            $save_data['type'] = 'landing_contract';

            $this->db->insert('contact_fields_value', $save_data);
            $insert_id = $this->db->insert_id();

            if ($insert_id) {
                set_alert('success', 'Landing contract saved successfully.');
            } else {
                set_alert('error', 'Failed to save Landing contract.');
            }
            redirect(admin_url('contracts/landing_contract'));
        }

        $this->landing_contract();
    }

    public function edit_landing($id = '')
    {
        if (!is_admin()) {
            access_denied('Landing Contracts');
        }

        if ($this->input->post()) {
            $data = $this->input->post();

            $update_data['name'] = $data['template_name'];
            unset($data['template_name']);

            $update_data['fields_value'] = json_encode($data);
            $update_data['type'] = 'landing_contract';

            $this->db->where('id', $id);
            $this->db->where('type', 'landing_contract');
            $success = $this->db->update(db_prefix() . 'contact_fields_value', $update_data);

            if ($success) {
                set_alert('success', _l('updated_successfully', _l('Landing Contract')));
            } else {
                set_alert('warning', _l('problem_updating', _l('Landing Contract')));
            }

            redirect(admin_url('contracts/landing_contract'));
        }

        $landing = $this->db->get_where(db_prefix() . 'contact_fields_value', [
            'id' => $id,
            'type' => 'landing_contract'
        ])->row();

        if ($landing) {
            $data['fields'] = json_decode($landing->fields_value, true);
        }

        $data['landing'] = $landing;
        $data['title'] = _l('edit') . ' ' . _l('Landing Contract');
        $data['clients'] = $this->db->get(db_prefix() . 'clients')->result_array();
        $this->load->view('admin/contracts/landing_contract', $data);
    }


    public function delete_landing($id)
    {
        if (!is_admin()) {
            access_denied('Landing Contracts');
        }

        if (!$id) {
            redirect(admin_url('contracts/landing_contract'));
        }

        $this->db->where('id', $id);
        $this->db->where('type', 'landing_contract');
        $success = $this->db->delete(db_prefix() . 'contact_fields_value');

        if ($success) {
            set_alert('success', _l('deleted', _l('Landing Contract')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('landing Contract')));
        }

        redirect(admin_url('contracts/landing_contract'));
    }

    public function print_landing($id)
    {
        if (!is_admin()) {
            access_denied('Landing Contracts');
        }
        $landing = $this->db->get_where(db_prefix() . 'contact_fields_value', [
            'id' => $id,
            'type' => 'landing_contract'
        ])->row();

        if (!$landing) {
            show_404();
        }

        $landing->fields = json_decode($landing->fields_value, true);
        $template_row = $this->db->get_where(db_prefix() . 'contact_templates', [
            'type' => 'landing_contract'
        ])->row_array();

        $template = $template_row ? $template_row['template'] : '';
        $merge_fields = [
            '{template_name}' => $landing->name ?? '',
            '{date}' => $landing->fields['date'] ?? '',
            '{trading_company_name}' => $landing->fields['trading_company_name'] ?? '',
            '{trading_company_address}' => $landing->fields['trading_company_address'] ?? '',
            '{trading_company_vat}' => $landing->fields['trading_company_vat'] ?? '',
            '{trading_company_city}' => $landing->fields['trading_company_city'] ?? '',
            '{trading_company_country}' => $landing->fields['trading_company_country'] ?? '',

            '{lending_company_name}' => $landing->fields['lending_company_name'] ?? '',
            '{lending_company_address}' => $landing->fields['lending_company_address'] ?? '',
            '{lending_company_vat}' => $landing->fields['lending_company_vat'] ?? '',
            '{lending_company_city}' => $landing->fields['lending_company_city'] ?? '',
            '{lending_company_country}' => $landing->fields['lending_company_country'] ?? '',

            '{borrowing_company_name}' => $landing->fields['borrowing_company_name'] ?? '',
            '{borrowing_company_address}' => $landing->fields['borrowing_company_address'] ?? '',
            '{borrowing_company_vat}' => $landing->fields['borrowing_company_vat'] ?? '',
            '{borrowing_company_city}' => $landing->fields['borrowing_company_city'] ?? '',
            '{borrowing_company_country}' => $landing->fields['borrowing_company_country'] ?? '',

            '{lending_amount}' => $landing->fields['lending_amount'] ?? '',
            '{interest}' => $landing->fields['interest'] ?? '',
            '{days}' => $landing->fields['days'] ?? '',
            '{initial_paid_amount}' => $landing->fields['initial_paid_amount'] ?? '',
            '{repayment_date}' => $landing->fields['repayment_date'] ?? '',
            '{late_payment_amount_per_day}' => $landing->fields['late_payment_amount_per_day'] ?? '',
            '{late_payment_days}' => $landing->fields['late_payment_days'] ?? '',
            '{repayment_mode}' => $landing->fields['repayment_mode'] ?? '',
        ];
        $filled_template = str_replace(array_keys($merge_fields), array_values($merge_fields), $template);

        $data['contract'] = $landing;
        $data['filled_template'] = $filled_template;

        $this->load->view('admin/contracts/print_landing', $data);
    }


}
