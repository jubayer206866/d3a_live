<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Statement_report extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('statement_model');
    }

    public function index()
    {
        $data['clients']  = $this->statement_model->get_all_clients();
        $data['projects'] = $this->statement_model->get_all_projects();
        $data['client_admin'] = $this->statement_model->get_all_client_admin();
        $data['title'] = _l('Statements');
        $this->load->view('admin/statement_report/manage', $data);
    }

    public function table()
    {
        $this->app->get_table_data('statement_report');
    }
}


