<?php

use app\services\projects\Gantt;
use app\services\projects\AllProjectsGantt;
use app\services\projects\HoursOverviewChart;

// PhpSpreadsheet classes used for IRV Excel export
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

defined('BASEPATH') or exit('No direct script access allowed');

class Projects extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('currencies_model');
        $this->load->helper('date');
    }

    public function index()
    {
        close_setup_menu();
        $data['statuses'] = $this->projects_model->get_project_statuses();
        $data['title'] = _l('projects');
        $data['table'] = App_table::find('projects');
        $this->load->view('admin/projects/manage', $data);
    }

    public function table($clientid = '')
    {
        App_table::find('projects')->output([
            'clientid' => $clientid
        ]);
    }

    public function staff_projects()
    {
        $this->app->get_table_data('staff_projects');
    }

    public function expenses($id)
    {
        $this->load->model('expenses_model');
        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [], true);

        App_table::find('project_expenses')->output([
            'project_id' => $id,
            'data' => $data,
        ]);
    }

    public function add_expense()
    {
        if ($this->input->post()) {
            $this->load->model('expenses_model');
            $id = $this->expenses_model->add($this->input->post());
            if ($id) {
                set_alert('success', _l('added_successfully', _l('expense')));
                echo json_encode([
                    'url' => admin_url('projects/view/' . $this->input->post('project_id') . '/?group=project_expenses'),
                    'expenseid' => $id,
                ]);
                die;
            }
            echo json_encode([
                'url' => admin_url('projects/view/' . $this->input->post('project_id') . '/?group=project_expenses'),
            ]);
            die;
        }
    }

    public function project($id = '')
    {
        if (staff_cant('edit', 'projects') && staff_cant('create', 'projects')) {
            access_denied('Projects');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            $data['send_created_email'] = 'on';
            $data['description'] = html_purify($this->input->post('description', false));
            if ($id == '') {
                if (staff_cant('create', 'projects')) {
                    access_denied('Projects');
                }
                $id = $this->projects_model->add($data);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('project')));
                    redirect(admin_url('projects/view/' . $id));
                }
            } else {
                if (staff_cant('edit', 'projects')) {
                    access_denied('Projects');
                }
                $success = $this->projects_model->update($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('project')));
                }
                redirect(admin_url('projects/view/' . $id));
            }
        }
        if ($id == '') {
            $title = _l('add_new', _l('project'));
            $data['auto_select_billing_type'] = $this->projects_model->get_most_used_billing_type();

            if ($this->input->get('via_estimate_id')) {
                $this->load->model('estimates_model');
                $data['estimate'] = $this->estimates_model->get($this->input->get('via_estimate_id'));
            }
        } else {
            $data['project'] = $this->projects_model->get($id);
            $data['project']->settings->available_features = unserialize($data['project']->settings->available_features);

            $data['project_members'] = $this->projects_model->get_project_members($id);
            $title = _l('edit', _l('project'));
        }

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        $data['last_project_settings'] = $this->projects_model->get_last_project_settings();

        if (count($data['last_project_settings'])) {
            $key = array_search('available_features', array_column($data['last_project_settings'], 'name'));
            $data['last_project_settings'][$key]['value'] = unserialize($data['last_project_settings'][$key]['value']);
        }

        $data['settings'] = $this->projects_model->get_settings();
        $data['statuses'] = $this->projects_model->get_project_statuses();
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);

        //Add By Rifat.............(shipping company)
        $this->db->select('id, name');
        $this->db->from(db_prefix() . 'shipping_company');
        $data['shipping_companies'] = $this->db->get()->result_array();
        //Add By Rifat.............
        $data['create_container_number'] = $data['project']->create_container_number ?? '';
        $data['shipping_company'] = $data['project']->shipping_company ?? '';

        $data['title'] = $title;
        $this->load->view('admin/projects/project', $data);
    }

    public function gantt()
    {
        $data['title'] = _l('project_gant');

        $selected_statuses = [];
        $selectedMember = null;
        $data['statuses'] = $this->projects_model->get_project_statuses();

        $appliedStatuses = $this->input->get('status');
        $appliedMember = $this->input->get('member');

        $allStatusesIds = [];
        foreach ($data['statuses'] as $status) {
            if (
                !isset($status['filter_default'])
                || (isset($status['filter_default']) && $status['filter_default'])
                && !$appliedStatuses
            ) {
                $selected_statuses[] = $status['id'];
            } elseif ($appliedStatuses) {
                if (in_array($status['id'], $appliedStatuses)) {
                    $selected_statuses[] = $status['id'];
                }
            } else {
                // All statuses
                $allStatusesIds[] = $status['id'];
            }
        }

        if (count($selected_statuses) == 0) {
            $selected_statuses = $allStatusesIds;
        }


        $data['selected_statuses'] = $selected_statuses;

        if (staff_can('view', 'projects')) {
            $selectedMember = $appliedMember;
            $data['selectedMember'] = $selectedMember;
            $data['project_members'] = $this->projects_model->get_distinct_projects_members();
        }

        $data['gantt_data'] = (new AllProjectsGantt([
            'status' => $selected_statuses,
            'member' => $selectedMember,
        ]))->get();

        $this->load->view('admin/projects/gantt', $data);
    }

    public function view($id)
    {
        if (staff_can('view', 'projects') || $this->projects_model->is_member($id)) {
            close_setup_menu();
            $project = $this->projects_model->get($id);
            if (!$project) {
                blank_page(_l('project_not_found'));
            }

            $project->settings->available_features = unserialize($project->settings->available_features);
            $data['statuses'] = $this->projects_model->get_project_statuses();

            $group = !$this->input->get('group') ? 'project_overview' : $this->input->get('group');

            // Unable to load the requested file: admin/projects/project_tasks#.php - FIX
            if (strpos($group, '#') !== false) {
                $group = str_replace('#', '', $group);
            }

            $tabs = get_project_tabs_admin();
            $data['tabs'] = $tabs;
            $data['tab'] = $this->app_tabs->filter_tab($data['tabs'], $group);

            if (!$data['tab']) {
                show_404();
            }

            $this->load->model('payment_modes_model');
            $data['payment_modes'] = $this->payment_modes_model->get('', [], true);

            $data['project'] = $project;
            $data['create_container_number'] = $data['project']->create_container_number ?? '';// Add By Rifat............ 
            $data['bill_of_landing_number'] = $data['project']->bill_of_landing_number ?? '';// Add By Rifat............ 

            $data['currency'] = $this->projects_model->get_currency($id);

            $data['project_total_logged_time'] = $this->projects_model->total_logged_time($id);

            $data['staff'] = $this->staff_model->get('', ['active' => 1]);
            $percent = $this->projects_model->calc_progress($id);
            $data['members'] = $this->projects_model->get_project_members($id);
            foreach ($data['members'] as $key => $member) {
                $data['members'][$key]['total_logged_time'] = 0;
                $member_timesheets = $this->tasks_model->get_unique_member_logged_task_ids($member['staff_id'], ' AND task_id IN (SELECT id FROM ' . db_prefix() . 'tasks WHERE rel_type="project" AND rel_id="' . $this->db->escape_str($id) . '")');

                foreach ($member_timesheets as $member_task) {
                    $data['members'][$key]['total_logged_time'] += $this->tasks_model->calc_task_total_time($member_task->task_id, ' AND staff_id=' . $member['staff_id']);
                }
            }
            $data['bodyclass'] = '';

            $this->app_scripts->add(
                'projects-js',
                base_url($this->app_scripts->core_file('assets/js', 'projects.js')) . '?v=' . $this->app_scripts->core_version(),
                'admin',
                ['app-js', 'jquery-comments-js', 'frappe-gantt-js', 'circle-progress-js']
            );

            if ($group == 'project_overview') {
                $data['project_expenses_by_category'] = $this->projects_model->get_project_expenses_grouped_by_category($id);
                $data['project_total_days'] = round((human_to_unix($data['project']->deadline . ' 00:00') - human_to_unix($data['project']->start_date . ' 00:00')) / 3600 / 24);
                $data['project_days_left'] = $data['project_total_days'];
                $data['project_time_left_percent'] = 100;
                if ($data['project']->deadline) {
                    if (human_to_unix($data['project']->start_date . ' 00:00') < time() && human_to_unix($data['project']->deadline . ' 00:00') > time()) {
                        $data['project_days_left'] = round((human_to_unix($data['project']->deadline . ' 00:00') - time()) / 3600 / 24);
                        $data['project_time_left_percent'] = $data['project_days_left'] / $data['project_total_days'] * 100;
                        $data['project_time_left_percent'] = round($data['project_time_left_percent'], 2);
                    }
                    if (human_to_unix($data['project']->deadline . ' 00:00') < time()) {
                        $data['project_days_left'] = 0;
                        $data['project_time_left_percent'] = 0;
                    }
                }

                $__total_where_tasks = 'rel_type = "project" AND rel_id=' . $this->db->escape_str($id);
                if (staff_cant('view', 'tasks')) {
                    $__total_where_tasks .= ' AND ' . db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . get_staff_user_id() . ')';

                    if (get_option('show_all_tasks_for_project_member') == 1) {
                        $__total_where_tasks .= ' AND (rel_type="project" AND rel_id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . '))';
                    }
                }

                $__total_where_tasks = hooks()->apply_filters('admin_total_project_tasks_where', $__total_where_tasks, $id);

                $where = ($__total_where_tasks == '' ? '' : $__total_where_tasks . ' AND ') . 'status != ' . Tasks_model::STATUS_COMPLETE;

                $data['tasks_not_completed'] = total_rows(db_prefix() . 'tasks', $where);
                $total_tasks = total_rows(db_prefix() . 'tasks', $__total_where_tasks);
                $data['total_tasks'] = $total_tasks;

                $where = ($__total_where_tasks == '' ? '' : $__total_where_tasks . ' AND ') . 'status = ' . Tasks_model::STATUS_COMPLETE . ' AND rel_type="project" AND rel_id="' . $id . '"';

                $data['tasks_completed'] = total_rows(db_prefix() . 'tasks', $where);

                $data['tasks_not_completed_progress'] = ($total_tasks > 0 ? number_format(($data['tasks_completed'] * 100) / $total_tasks, 2) : 0);
                $data['tasks_not_completed_progress'] = round($data['tasks_not_completed_progress'], 2);

                @$percent_circle = $percent / 100;
                $data['percent_circle'] = $percent_circle;

                $data['project_overview_chart'] = (new HoursOverviewChart(
                    $id,
                    ($this->input->get('overview_chart') ? $this->input->get('overview_chart') : 'this_week')
                ))->get();
            } elseif ($group == 'project_invoices') {
                $this->load->model('invoices_model');

                $data['invoiceid'] = '';
                $data['status'] = '';
                $data['custom_view'] = '';

                $data['invoices_years'] = $this->invoices_model->get_invoices_years();
                $data['invoices_sale_agents'] = $this->invoices_model->get_sale_agents();
                $data['invoices_statuses'] = $this->invoices_model->get_statuses();
                $data['invoices_table'] = App_table::find('project_invoices');
            } elseif ($group == 'inventory_receiving_voucher') {
                $this->load->model('purchase/Purchase_model');
                $data['irv_items'] = $this->Purchase_model->get_irv_items($id);
                $data['irvs'] = $this->projects_model->get_project_irv_orders_items($id);
                $data['project_id'] = $id;
            } elseif ($group == 'project_gantt') {
                $gantt_type = (!$this->input->get('gantt_type') ? 'milestones' : $this->input->get('gantt_type'));
                $taskStatus = (!$this->input->get('gantt_task_status') ? null : $this->input->get('gantt_task_status'));
                $data['gantt_data'] = (new Gantt($id, $gantt_type))->forTaskStatus($taskStatus)->get();
            } elseif ($group == 'project_milestones') {
                $data['bodyclass'] .= 'project-milestones ';
                $data['milestones_exclude_completed_tasks'] = $this->input->get('exclude_completed') && $this->input->get('exclude_completed') == 'yes' || !$this->input->get('exclude_completed');

                $data['total_milestones'] = total_rows(db_prefix() . 'milestones', ['project_id' => $id]);
                $data['milestones_found'] = $data['total_milestones'] > 0 || (!$data['total_milestones'] && total_rows(db_prefix() . 'tasks', ['rel_id' => $id, 'rel_type' => 'project', 'milestone' => 0]) > 0);
            } elseif ($group == 'project_files') {
                $data['files'] = $this->projects_model->get_files($id);
            } elseif ($group == 'project_expenses') {
                $this->load->model('taxes_model');
                $this->load->model('expenses_model');
                $data['taxes'] = $this->taxes_model->get();
                $data['expense_categories'] = $this->expenses_model->get_category();
                $data['currencies'] = $this->currencies_model->get();
                $data['expenses_table'] = App_table::find('project_expenses');
            } elseif ($group == 'project_activity') {
                $data['activity'] = $this->projects_model->get_activity($id);
            } elseif ($group == 'project_notes') {
                $data['staff_notes'] = $this->projects_model->get_staff_notes($id);
            } elseif ($group == 'project_contracts') {
                $this->load->model('contracts_model');
                $data['contract_types'] = $this->contracts_model->get_contract_types();
                $data['years'] = $this->contracts_model->get_contracts_years();
                $data['contracts_table'] = App_table::find('project_contracts');
            } elseif ($group == 'project_estimates') {
                $this->load->model('estimates_model');
                $data['estimates_years'] = $this->estimates_model->get_estimates_years();
                $data['estimates_sale_agents'] = $this->estimates_model->get_sale_agents();
                $data['estimate_statuses'] = $this->estimates_model->get_statuses();
                $data['estimates_table'] = App_table::find('project_estimates');
                $data['estimateid'] = '';
                $data['switch_pipeline'] = '';
            } elseif ($group == 'project_proposals') {
                $this->load->model('proposals_model');
                $data['proposal_statuses'] = $this->proposals_model->get_statuses();
                $data['proposals_sale_agents'] = $this->proposals_model->get_sale_agents();
                $data['years'] = $this->proposals_model->get_proposals_years();
                $data['proposals_table'] = App_table::find('project_proposals');
                $data['proposal_id'] = '';
                $data['switch_pipeline'] = '';
            } elseif ($group == 'project_tickets') {
                $data['chosen_ticket_status'] = '';
                $this->load->model('tickets_model');
                $data['ticket_assignees'] = $this->tickets_model->get_tickets_assignes_disctinct();

                $this->load->model('departments_model');
                $data['staff_deparments_ids'] = $this->departments_model->get_staff_departments(get_staff_user_id(), true);
                $data['default_tickets_list_statuses'] = hooks()->apply_filters('default_tickets_list_statuses', [1, 2, 4]);
            } elseif ($group == 'project_timesheets') {
                // Tasks are used in the timesheet dropdown
                // Completed tasks are excluded from this list because you can't add timesheet on completed task.
                $data['tasks'] = $this->projects_model->get_tasks($id, 'status != ' . Tasks_model::STATUS_COMPLETE . ' AND billed=0');
                $data['timesheets_staff_ids'] = $this->projects_model->get_distinct_tasks_timesheets_staff($id);
            } elseif ($group == 'project_packing_list') {
                $this->db->from(db_prefix() . 'shipping_company');
                $this->db->where('id', $project->shipping_company);
                $shipping_company = $this->db->get()->result_array();
                $data['shipping_company_name'] = count($shipping_company) ? $shipping_company[0]['name'] : '';
                $data['shipping_company_link'] = count($shipping_company) ? $shipping_company[0]['link'] : '';

                $return = $this->projects_model->get_project_po_items($id);
                $data['vendors'] = $return['return'];

                $data['project_summary'] = $return['summary'];
                $data['shop'] = $return['shop'];
                $data['date'] = $return['date'];
                $data['project_invoices'] = $this->projects_model->get_project_invoices($id);
                $data['client_info'] = $this->clients_model->get($project->clientid);
                $data['stock_items']=$this->projects_model->get_stock_items($id);
 
            } elseif ($group == 'purchase_order') {
                $data['project_id'] = $id;
                $data['vendors'] = $this->projects_model->get_project_po_orders_items($id);
            }

            // Discussions
            if ($this->input->get('discussion_id')) {
                $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
                $data['discussion'] = $this->projects_model->get_discussion($this->input->get('discussion_id'), $id);
                $data['current_user_is_admin'] = is_admin();
            }

            $data['percent'] = $percent;

            $this->app_scripts->add('circle-progress-js', 'assets/plugins/jquery-circle-progress/circle-progress.min.js');

            $other_projects = [];
            $other_projects_where = 'id != ' . $id;

            $statuses = $this->projects_model->get_project_statuses();

            $other_projects_where .= ' AND (';
            foreach ($statuses as $status) {
                if (isset($status['filter_default']) && $status['filter_default']) {
                    $other_projects_where .= 'status = ' . $status['id'] . ' OR ';
                }
            }

            $other_projects_where = rtrim($other_projects_where, ' OR ');

            $other_projects_where .= ')';

            if (staff_cant('view', 'projects')) {
                $other_projects_where .= ' AND ' . db_prefix() . 'projects.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . ')';
            }

            $data['other_projects'] = $this->projects_model->get('', $other_projects_where);
            $data['title'] = $data['project']->name;
            $data['bodyclass'] .= 'project estimates-total-manual';
            $data['project_status'] = get_project_status_by_id($project->status);
            $data['project_id'] = $id;
            $this->load->view('admin/projects/view', $data);
        } else {
            access_denied('Project View');
        }
    }

    public function mark_as()
    {
        $success = false;
        $message = '';
        if ($this->input->is_ajax_request()) {
            if (staff_can('create', 'projects') || staff_can('edit', 'projects')) {
                $status = get_project_status_by_id($this->input->post('status_id'));

                $message = _l('project_marked_as_failed', $status['name']);
                $success = $this->projects_model->mark_as($this->input->post());

                if ($success) {
                    $message = _l('project_marked_as_success', $status['name']);
                }
            }
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    public function file($id, $project_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin'] = is_admin();

        $data['file'] = $this->projects_model->get_file($id, $project_id);

        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }

        $this->load->view('admin/projects/_file', $data);
    }

    public function update_file_data()
    {
        if ($this->input->post()) {
            $this->projects_model->update_file_data($this->input->post());
        }
    }

    public function add_external_file()
    {
        if ($this->input->post()) {
            $data = [];
            $data['project_id'] = $this->input->post('project_id');
            $data['files'] = $this->input->post('files');
            $data['external'] = $this->input->post('external');
            $data['visible_to_customer'] = ($this->input->post('visible_to_customer') == 'true' ? 1 : 0);
            $data['staffid'] = get_staff_user_id();
            $this->projects_model->add_external_file($data);
        }
    }

    public function download_all_files($id)
    {
        if ($this->projects_model->is_member($id) || staff_can('view', 'projects')) {
            $files = $this->projects_model->get_files($id);
            if (count($files) == 0) {
                set_alert('warning', _l('no_files_found'));
                redirect(admin_url('projects/view/' . $id . '?group=project_files'));
            }
            $path = get_upload_path_by_type('project') . $id;
            $this->load->library('zip');
            foreach ($files as $file) {
                if ($file['original_file_name'] != '') {
                    $this->zip->read_file($path . '/' . $file['file_name'], $file['original_file_name']);
                } else {
                    $this->zip->read_file($path . '/' . $file['file_name']);
                }
            }
            $this->zip->download(slug_it(get_project_name_by_id($id)) . '-files.zip');
            $this->zip->clear_data();
        }
    }

    public function export_project_data($id)
    {
        if (staff_can('create', 'projects')) {
            app_pdf('project-data', LIBSPATH . 'pdf/Project_data_pdf', $id);
        }
    }

    public function update_task_milestone()
    {
        if ($this->input->post()) {
            $this->projects_model->update_task_milestone($this->input->post());
        }
    }

    public function update_milestones_order()
    {
        if ($post_data = $this->input->post()) {
            $this->projects_model->update_milestones_order($post_data);
        }
    }

    public function pin_action($project_id)
    {
        $this->projects_model->pin_action($project_id);
        redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
    }

    public function add_edit_members($project_id)
    {
        if (staff_can('edit', 'projects')) {
            $this->projects_model->add_edit_members($this->input->post(), $project_id);
            redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
        }
    }

    public function discussions($project_id)
    {
        if ($this->projects_model->is_member($project_id) || staff_can('view', 'projects')) {
            if ($this->input->is_ajax_request()) {
                $this->app->get_table_data('project_discussions', [
                    'project_id' => $project_id,
                ]);
            }
        }
    }

    public function discussion($id = '')
    {
        if ($this->input->post()) {
            $message = '';
            $success = false;
            if (!$this->input->post('id')) {
                $id = $this->projects_model->add_discussion($this->input->post());
                if ($id) {
                    $success = true;
                    $message = _l('added_successfully', _l('project_discussion'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                ]);
            } else {
                $data = $this->input->post();
                $id = $data['id'];
                unset($data['id']);
                $success = $this->projects_model->edit_discussion($data, $id);
                if ($success) {
                    $message = _l('updated_successfully', _l('project_discussion'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                ]);
            }
            die;
        }
    }

    public function get_discussion_comments($id, $type)
    {
        echo json_encode($this->projects_model->get_discussion_comments($id, $type));
    }

    public function add_discussion_comment($discussion_id, $type)
    {
        echo json_encode($this->projects_model->add_discussion_comment(
            $this->input->post(null, false),
            $discussion_id,
            $type
        ));
    }

    public function update_discussion_comment()
    {
        echo json_encode($this->projects_model->update_discussion_comment($this->input->post(null, false)));
    }

    public function delete_discussion_comment($id)
    {
        echo json_encode($this->projects_model->delete_discussion_comment($id));
    }

    public function delete_discussion($id)
    {
        $success = false;
        if (staff_can('delete', 'projects')) {
            $success = $this->projects_model->delete_discussion($id);
        }
        $alert_type = 'warning';
        $message = _l('project_discussion_failed_to_delete');
        if ($success) {
            $alert_type = 'success';
            $message = _l('project_discussion_deleted');
        }
        echo json_encode([
            'alert_type' => $alert_type,
            'message' => $message,
        ]);
    }

    public function change_milestone_color()
    {
        if ($this->input->post()) {
            $this->projects_model->update_milestone_color($this->input->post());
        }
    }

    public function upload_file($project_id)
    {
        handle_project_file_uploads($project_id);
    }

    public function change_file_visibility($id, $visible)
    {
        if ($this->input->is_ajax_request()) {
            $this->projects_model->change_file_visibility($id, $visible);
        }
    }

    public function change_activity_visibility($id, $visible)
    {
        if (staff_can('create', 'projects')) {
            if ($this->input->is_ajax_request()) {
                $this->projects_model->change_activity_visibility($id, $visible);
            }
        }
    }

    public function remove_file($project_id, $id)
    {
        $this->projects_model->remove_file($id);
        redirect(admin_url('projects/view/' . $project_id . '?group=project_files'));
    }

    public function milestones_kanban()
    {
        $data['milestones_exclude_completed_tasks'] = $this->input->get('exclude_completed_tasks') && $this->input->get('exclude_completed_tasks') == 'yes';

        $data['project_id'] = $this->input->get('project_id');
        $data['milestones'] = [];

        $data['milestones'][] = [
            'name' => _l('milestones_uncategorized'),
            'id' => 0,
            'total_logged_time' => $this->projects_model->calc_milestone_logged_time($data['project_id'], 0),
            'color' => null,
        ];

        $_milestones = $this->projects_model->get_milestones($data['project_id']);

        foreach ($_milestones as $m) {
            $data['milestones'][] = $m;
        }

        echo $this->load->view('admin/projects/milestones_kan_ban', $data, true);
    }

    public function milestones_kanban_load_more()
    {
        $milestones_exclude_completed_tasks = $this->input->get('exclude_completed_tasks') && $this->input->get('exclude_completed_tasks') == 'yes';

        $status = $this->input->get('status');
        $page = $this->input->get('page');
        $project_id = $this->input->get('project_id');
        $where = [];
        if ($milestones_exclude_completed_tasks) {
            $where['status !='] = Tasks_model::STATUS_COMPLETE;
        }
        $tasks = $this->projects_model->do_milestones_kanban_query($status, $project_id, $page, $where);
        foreach ($tasks as $task) {
            $this->load->view('admin/projects/_milestone_kanban_card', ['task' => $task, 'milestone' => $status]);
        }
    }

    public function milestones($project_id)
    {
        if ($this->projects_model->is_member($project_id) || staff_can('view', 'projects')) {
            if ($this->input->is_ajax_request()) {
                $this->app->get_table_data('milestones', [
                    'project_id' => $project_id,
                ]);
            }
        }
    }

    public function milestone($id = '')
    {
        if ($this->input->post()) {
            $message = '';
            $success = false;
            if (!$this->input->post('id')) {
                if (staff_cant('create_milestones', 'projects')) {
                    access_denied();
                }

                $id = $this->projects_model->add_milestone($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('project_milestone')));
                }
            } else {
                if (staff_cant('edit_milestones', 'projects')) {
                    access_denied();
                }

                $data = $this->input->post();
                $id = $data['id'];
                unset($data['id']);
                $success = $this->projects_model->update_milestone($data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('project_milestone')));
                }
            }
        }

        redirect(admin_url('projects/view/' . $this->input->post('project_id') . '?group=project_milestones'));
    }

    public function delete_milestone($project_id, $id)
    {
        if (staff_can('delete_milestones', 'projects')) {
            if ($this->projects_model->delete_milestone($id)) {
                set_alert('deleted', 'project_milestone');
            }
        }
        redirect(admin_url('projects/view/' . $project_id . '?group=project_milestones'));
    }

    public function bulk_action_files()
    {
        hooks()->do_action('before_do_bulk_action_for_project_files');
        $total_deleted = 0;
        $hasPermissionDelete = staff_can('delete', 'projects');
        // bulk action for projects currently only have delete button
        if ($this->input->post()) {
            $fVisibility = $this->input->post('visible_to_customer') == 'true' ? 1 : 0;
            $ids = $this->input->post('ids');
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    if ($hasPermissionDelete && $this->input->post('mass_delete') && $this->projects_model->remove_file($id)) {
                        $total_deleted++;
                    } else {
                        $this->projects_model->change_file_visibility($id, $fVisibility);
                    }
                }
            }
        }
        if ($this->input->post('mass_delete')) {
            set_alert('success', _l('total_files_deleted', $total_deleted));
        }
    }

    public function timesheets($project_id)
    {
        if ($this->projects_model->is_member($project_id) || staff_can('view', 'projects')) {
            if ($this->input->is_ajax_request()) {
                $this->app->get_table_data('timesheets', [
                    'project_id' => $project_id,
                ]);
            }
        }
    }

    public function timesheet()
    {
        if ($this->input->post()) {
            if (
                $this->input->post('timer_id') &&
                !(staff_can('edit_timesheet', 'tasks') || (staff_can('edit_own_timesheet', 'tasks') && total_rows(db_prefix() . 'taskstimers', ['staff_id' => get_staff_user_id(), 'id' => $this->input->post('timer_id')]) > 0))
            ) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('access_denied'),
                ]);
                die;
            }
            $message = '';
            $success = false;
            $success = $this->tasks_model->timesheet($this->input->post());
            if ($success === true) {
                $langKey = $this->input->post('timer_id') ? 'updated_successfully' : 'added_successfully';
                $message = _l($langKey, _l('project_timesheet'));
            } elseif (is_array($success) && isset($success['end_time_smaller'])) {
                $message = _l('failed_to_add_project_timesheet_end_time_smaller');
            } else {
                $message = _l('project_timesheet_not_updated');
            }
            echo json_encode([
                'success' => $success,
                'message' => $message,
            ]);
            die;
        }
    }

    public function timesheet_task_assignees($task_id, $project_id, $staff_id = 'undefined')
    {
        $assignees = $this->tasks_model->get_task_assignees($task_id);
        $data = '';
        $has_permission_edit = staff_can('edit', 'projects');
        $has_permission_create = staff_can('edit', 'projects');
        // The second condition if staff member edit their own timesheet
        if ($staff_id == 'undefined' || $staff_id != 'undefined' && (!$has_permission_edit || !$has_permission_create)) {
            $staff_id = get_staff_user_id();
            $current_user = true;
        }
        foreach ($assignees as $staff) {
            $selected = '';
            // maybe is admin and not project member
            if ($staff['assigneeid'] == $staff_id && $this->projects_model->is_member($project_id, $staff_id)) {
                $selected = ' selected';
            }
            if ((!$has_permission_edit || !$has_permission_create) && isset($current_user)) {
                if ($staff['assigneeid'] != $staff_id) {
                    continue;
                }
            }
            $data .= '<option value="' . $staff['assigneeid'] . '"' . $selected . '>' . e(get_staff_full_name($staff['assigneeid'])) . '</option>';
        }
        echo $data;
    }

    public function remove_team_member($project_id, $staff_id)
    {
        if (staff_can('edit', 'projects')) {
            if ($this->projects_model->remove_team_member($project_id, $staff_id)) {
                set_alert('success', _l('project_member_removed'));
            }
        }

        redirect(admin_url('projects/view/' . $project_id));
    }

    public function save_note($project_id)
    {
        if ($this->input->post()) {
            $success = $this->projects_model->save_note($this->input->post(null, false), $project_id);
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('project_note')));
            }
            redirect(admin_url('projects/view/' . $project_id . '?group=project_notes'));
        }
    }

    public function delete($project_id)
    {
        if (staff_can('delete', 'projects')) {
            $project = $this->projects_model->get($project_id);
            $success = $this->projects_model->delete($project_id);
            if ($success) {
                set_alert('success', _l('deleted', _l('project')));
                redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
            } else {
                set_alert('warning', _l('problem_deleting', _l('project_lowercase')));
                redirect(admin_url('projects/view/' . $project_id));
            }
        }
    }

    public function copy($project_id)
    {
        if (staff_can('create', 'projects')) {
            $id = $this->projects_model->copy($project_id, $this->input->post());
            if ($id) {
                set_alert('success', _l('project_copied_successfully'));
                redirect(admin_url('projects/view/' . $id));
            } else {
                set_alert('danger', _l('failed_to_copy_project'));
                redirect(admin_url('projects/view/' . $project_id));
            }
        }
    }

    public function mass_stop_timers($project_id, $billable = 'false')
    {
        if (staff_can('create', 'invoices')) {
            $where = [
                'billed' => 0,
                'startdate <=' => date('Y-m-d'),
            ];
            if ($billable == 'true') {
                $where['billable'] = true;
            }
            $tasks = $this->projects_model->get_tasks($project_id, $where);
            $total_timers_stopped = 0;
            foreach ($tasks as $task) {
                $this->db->where('task_id', $task['id']);
                $this->db->where('end_time IS NULL');
                $this->db->update(db_prefix() . 'taskstimers', [
                    'end_time' => time(),
                ]);
                $total_timers_stopped += $this->db->affected_rows();
            }
            $message = _l('project_tasks_total_timers_stopped', $total_timers_stopped);
            $type = 'success';
            if ($total_timers_stopped == 0) {
                $type = 'warning';
            }
            echo json_encode([
                'type' => $type,
                'message' => $message,
            ]);
        }
    }

    public function get_pre_invoice_project_info($project_id)
    {
        if (staff_can('create', 'invoices')) {
            $data['billable_tasks'] = $this->projects_model->get_tasks($project_id, [
                'billable' => 1,
                'billed' => 0,
                'startdate <=' => date('Y-m-d'),
            ]);

            $data['not_billable_tasks'] = $this->projects_model->get_tasks($project_id, [
                'billable' => 1,
                'billed' => 0,
                'startdate >' => date('Y-m-d'),
            ]);

            $data['project_id'] = $project_id;
            $data['billing_type'] = get_project_billing_type($project_id);

            $this->load->model('expenses_model');
            $this->db->where('invoiceid IS NULL');
            $data['expenses'] = $this->expenses_model->get('', [
                'project_id' => $project_id,
                'billable' => 1,
            ]);

            $this->load->view('admin/projects/project_pre_invoice_settings', $data);
        }
    }

    public function get_invoice_project_data()
    {
        if (staff_can('create', 'invoices')) {
            $type = $this->input->post('type');
            $project_id = $this->input->post('project_id');
            // Check for all cases
            if ($type == '') {
                $type == 'single_line';
            }
            $this->load->model('payment_modes_model');
            $data['payment_modes'] = $this->payment_modes_model->get('', [
                'expenses_only !=' => 1,
            ]);
            $this->load->model('taxes_model');
            $data['taxes'] = $this->taxes_model->get();
            $data['currencies'] = $this->currencies_model->get();
            $data['base_currency'] = $this->currencies_model->get_base_currency();
            $this->load->model('invoice_items_model');

            $data['ajaxItems'] = false;
            if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
                $data['items'] = $this->invoice_items_model->get_grouped();
            } else {
                $data['items'] = [];
                $data['ajaxItems'] = true;
            }

            $data['items_groups'] = $this->invoice_items_model->get_groups();
            $data['staff'] = $this->staff_model->get('', ['active' => 1]);
            $project = $this->projects_model->get($project_id);
            $data['project'] = $project;
            $items = [];

            $project = $this->projects_model->get($project_id);
            $item['id'] = 0;

            $default_tax = unserialize(get_option('default_tax'));
            $item['taxname'] = $default_tax;

            $tasks = $this->input->post('tasks');
            if ($tasks) {
                $item['long_description'] = '';
                $item['qty'] = 0;
                $item['task_id'] = [];
                if ($type == 'single_line') {
                    $item['description'] = $project->name;
                    foreach ($tasks as $task_id) {
                        $task = $this->tasks_model->get($task_id);
                        $sec = $this->tasks_model->calc_task_total_time($task_id);
                        $item['long_description'] .= $task->name . ' - ' . seconds_to_time_format(task_timer_round($sec)) . ' ' . _l('hours') . "\r\n";
                        $item['task_id'][] = $task_id;
                        if ($project->billing_type == 2) {
                            if ($sec < 60) {
                                $sec = 0;
                            }
                            $item['qty'] += sec2qty(task_timer_round($sec));
                        }
                    }
                    if ($project->billing_type == 1) {
                        $item['qty'] = 1;
                        $item['rate'] = $project->project_cost;
                    } elseif ($project->billing_type == 2) {
                        $item['rate'] = $project->project_rate_per_hour;
                    }
                    $item['unit'] = '';
                    $items[] = $item;
                } elseif ($type == 'task_per_item') {
                    foreach ($tasks as $task_id) {
                        $task = $this->tasks_model->get($task_id);
                        $sec = $this->tasks_model->calc_task_total_time($task_id);
                        $item['description'] = $project->name . ' - ' . $task->name;
                        $item['qty'] = floatVal(sec2qty(task_timer_round($sec)));
                        $item['long_description'] = seconds_to_time_format(task_timer_round($sec)) . ' ' . _l('hours');
                        if ($project->billing_type == 2) {
                            $item['rate'] = $project->project_rate_per_hour;
                        } elseif ($project->billing_type == 3) {
                            $item['rate'] = $task->hourly_rate;
                        }
                        $item['task_id'] = $task_id;
                        $item['unit'] = '';
                        $items[] = $item;
                    }
                } elseif ($type == 'timesheets_individualy') {
                    $timesheets = $this->projects_model->get_timesheets($project_id, $tasks);
                    $added_task_ids = [];
                    foreach ($timesheets as $timesheet) {
                        if ($timesheet['task_data']->billed == 0 && $timesheet['task_data']->billable == 1) {
                            $item['description'] = $project->name . ' - ' . $timesheet['task_data']->name;
                            if (!in_array($timesheet['task_id'], $added_task_ids)) {
                                $item['task_id'] = $timesheet['task_id'];
                            }

                            array_push($added_task_ids, $timesheet['task_id']);

                            $item['qty'] = floatVal(sec2qty(task_timer_round($timesheet['total_spent'])));
                            $item['long_description'] = _l('project_invoice_timesheet_start_time', _dt($timesheet['start_time'], true)) . "\r\n" . _l('project_invoice_timesheet_end_time', _dt($timesheet['end_time'], true)) . "\r\n" . _l('project_invoice_timesheet_total_logged_time', seconds_to_time_format(task_timer_round($timesheet['total_spent']))) . ' ' . _l('hours');

                            if ($this->input->post('timesheets_include_notes') && $timesheet['note']) {
                                $item['long_description'] .= "\r\n\r\n" . _l('note') . ': ' . $timesheet['note'];
                            }

                            if ($project->billing_type == 2) {
                                $item['rate'] = $project->project_rate_per_hour;
                            } elseif ($project->billing_type == 3) {
                                $item['rate'] = $timesheet['task_data']->hourly_rate;
                            }
                            $item['unit'] = '';
                            $items[] = $item;
                        }
                    }
                }
            }
            if ($project->billing_type != 1) {
                $data['hours_quantity'] = true;
            }
            if ($this->input->post('expenses')) {
                if (isset($data['hours_quantity'])) {
                    unset($data['hours_quantity']);
                }
                if (count($tasks) > 0) {
                    $data['qty_hrs_quantity'] = true;
                }
                $expenses = $this->input->post('expenses');
                $addExpenseNote = $this->input->post('expenses_add_note');
                $addExpenseName = $this->input->post('expenses_add_name');

                if (!$addExpenseNote) {
                    $addExpenseNote = [];
                }

                if (!$addExpenseName) {
                    $addExpenseName = [];
                }

                $this->load->model('expenses_model');
                foreach ($expenses as $expense_id) {
                    // reset item array
                    $item = [];
                    $item['id'] = 0;
                    $expense = $this->expenses_model->get($expense_id);
                    $item['expense_id'] = $expense->expenseid;
                    $item['description'] = _l('item_as_expense') . ' ' . $expense->name;
                    $item['long_description'] = $expense->description;

                    if (in_array($expense_id, $addExpenseNote) && !empty($expense->note)) {
                        $item['long_description'] .= PHP_EOL . $expense->note;
                    }

                    if (in_array($expense_id, $addExpenseName) && !empty($expense->expense_name)) {
                        $item['long_description'] .= PHP_EOL . $expense->expense_name;
                    }

                    $item['qty'] = 1;

                    $item['taxname'] = [];
                    if ($expense->tax != 0) {
                        array_push($item['taxname'], $expense->tax_name . '|' . $expense->taxrate);
                    }
                    if ($expense->tax2 != 0) {
                        array_push($item['taxname'], $expense->tax_name2 . '|' . $expense->taxrate2);
                    }
                    $item['rate'] = $expense->amount;
                    $item['order'] = 1;
                    $item['unit'] = '';
                    $items[] = $item;
                }
            }
            $data['customer_id'] = $project->clientid;
            $data['invoice_from_project'] = true;
            $data['add_items'] = $items;
            $this->load->view('admin/projects/invoice_project', $data);
        }
    }

    public function get_rel_project_data($id, $task_id = '')
    {
        if ($this->input->is_ajax_request()) {
            $selected_milestone = '';
            $assigned = '';
            if ($task_id != '' && $task_id != 'undefined') {
                $task = $this->tasks_model->get($task_id);
                $selected_milestone = $task->milestone;
                $assigned = array_map(function ($member) {
                    return $member['assigneeid'];
                }, $this->tasks_model->get_task_assignees($task_id));
            }

            $allow_to_view_tasks = 0;
            $this->db->where('project_id', $id);
            $this->db->where('name', 'view_tasks');
            $project_settings = $this->db->get(db_prefix() . 'project_settings')->row();
            if ($project_settings) {
                $allow_to_view_tasks = $project_settings->value;
            }

            $deadline = get_project_deadline($id);

            echo json_encode([
                'deadline' => $deadline,
                'deadline_formatted' => $deadline ? _d($deadline) : null,
                'allow_to_view_tasks' => $allow_to_view_tasks,
                'billing_type' => get_project_billing_type($id),
                'milestones' => render_select('milestone', $this->projects_model->get_milestones($id), [
                    'id',
                    'name',
                ], 'task_milestone', $selected_milestone),
                'assignees' => render_select('assignees[]', $this->projects_model->get_project_members($id, true), [
                    'staff_id',
                    ['firstname', 'lastname'],
                ], 'task_single_assignees', $assigned, ['multiple' => true], [], '', '', false),
            ]);
        }
    }

    public function invoice_project($project_id)
    {
        if (staff_can('create', 'invoices')) {
            $this->load->model('invoices_model');
            $data = $this->input->post();
            $data['project_id'] = $project_id;
            $invoice_id = $this->invoices_model->add($data);
            if ($invoice_id) {
                $this->projects_model->log_activity($project_id, 'project_activity_invoiced_project', format_invoice_number($invoice_id));
                set_alert('success', _l('project_invoiced_successfully'));
            }
            redirect(admin_url('projects/view/' . $project_id . '?group=project_invoices'));
        }
    }

    public function view_project_as_client($id, $clientid)
    {
        if (is_admin()) {
            login_as_client($clientid);
            redirect(site_url('clients/project/' . $id));
        }
    }

    public function get_staff_names_for_mentions($projectId)
    {
        if ($this->input->is_ajax_request()) {
            $projectId = $this->db->escape_str($projectId);

            $members = $this->projects_model->get_project_members($projectId);
            $members = array_map(function ($member) {
                $staff = $this->staff_model->get($member['staff_id']);

                $_member['id'] = $member['staff_id'];
                $_member['name'] = $staff->firstname . ' ' . $staff->lastname;

                return $_member;
            }, $members);

            echo json_encode($members);
        }
    }
    public function save_excel_packing_list($project_id)
    {

        $this->db->where(db_prefix() . 'projects.id', $project_id);
        $project = $this->db->get(db_prefix() . 'projects')->row();
        $project_invoices = $this->projects_model->get_project_invoices($project_id);
        $this->db->where(db_prefix() . 'clients.userid', $project->clientid);
        $client = $this->db->get(db_prefix() . 'clients')->row();
        $this->db->select('tblpur_vendor.store_number as vendor_code,tblpur_vendor.company as company,tblpur_vendor.company as company,tblwh_packing_lists.id as wh_packing_lists_id,tblgoods_delivery.date_add as date');
        $this->db->where(db_prefix() . 'pur_orders.project', $project_id);
        $this->db->from(db_prefix() . 'pur_orders');
        $this->db->join(db_prefix() . 'pur_vendor', db_prefix() . 'pur_vendor.userid=' . db_prefix() . 'pur_orders.vendor');
        $this->db->join(db_prefix() . 'goods_receipt', db_prefix() . 'goods_receipt.pr_order_id = ' . db_prefix() . 'pur_orders.id');
        $this->db->join(db_prefix() . 'goods_delivery', db_prefix() . 'goods_delivery.goods_receipt_id = ' . db_prefix() . 'goods_receipt.id');
        $this->db->join(db_prefix() . 'wh_packing_lists', db_prefix() . 'wh_packing_lists.delivery_note_id = ' . db_prefix() . 'goods_delivery.id');

        $wh_packing_lists = $this->db->get()->result_array();


        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultRowDimension()->setRowHeight(50);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
        foreach (range('A', 'O') as $col) {
            if ($col == 'B') {
                $sheet->getColumnDimension($col)->setWidth(20);
            } else {
                $sheet->getColumnDimension($col)->setWidth(16);
            }

        }


        $sheet->mergeCells('A1:O1');
        $sheet->getRowDimension(1)->setRowHeight(110);
        $imagePath = FCPATH . 'uploads/company/' . get_option('company_logo');
        if (file_exists($imagePath) && get_option('company_logo') != '') {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Company Logo');
            $drawing->setDescription('Company Logo');
            $drawing->setPath($imagePath);
            $drawing->setHeight(40);
            $drawing->setWidth(220);
            $drawing->setCoordinates('G1');
            $drawing->setOffsetX(45);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        }



        $row = 2;
        $sheet->mergeCells("A{$row}:O{$row}");
        $sheet->setCellValue("A{$row}", $project->name);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getFont()->setSize(16);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);


        $Left = "Bill of Lading Nr. :" . $project->bill_of_landing_number .
            "\nContainer Nr.:" . $project->create_container_number .
            "\nInvoice Nr. :" . $project_invoices .
            "\nDate:" . _d($wh_packing_lists[0]['date']);
        $row = 3;
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->getRowDimension($row)->setRowHeight(100);
        $sheet->setCellValue("A{$row}", $Left);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getFont()->setSize(12);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);

        $sheet->mergeCells('D' . 3 . ':L' . 3);
        $sheet->setCellValue('D' . 3, '');


        $right = "Customer :" . $client->company .
            "\nVAT Nr:" . $client->vat .
            "\nPhone Nr.:" . $client->phonenumber .
            "\nAddress : " . $client->address;


        $sheet->mergeCells("M{$row}:O{$row}");
        $sheet->setCellValue("M{$row}", $right);
        $sheet->getStyle("M{$row}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("M{$row}")->getFont()->setBold(true);
        $sheet->getStyle("M{$row}")->getFont()->setSize(12);
        $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("M{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);


        $arr_images = $this->warehouse_model->item_attachments();

        $i = 4;
        $summary[0]['name'] = 'Cartons';
        $summary[0]['data'] = 0;
        $summary[1]['name'] = 'Total/Pieces';
        $summary[1]['data'] = 0;
        $summary[2]['name'] = 'Price/Total';
        $summary[2]['data'] = 0;
        $summary[3]['name'] = 'Total Net Weight';
        $summary[3]['data'] = 0;
        $summary[4]['name'] = 'Total Gross Weight';
        $summary[4]['data'] = 0;
        $summary[5]['name'] = 'Total CBM';
        $summary[5]['data'] = 0;
        foreach ($wh_packing_lists as $wh_packing_list) {
            $this->db->select(db_prefix() . 'wh_packing_list_details.*, ' . db_prefix() . 'items.commodity_barcode,items.rate');
            $this->db->from(db_prefix() . 'wh_packing_list_details');
            $this->db->join(
                db_prefix() . 'items',
                db_prefix() . 'items.id = ' . db_prefix() . 'wh_packing_list_details.commodity_code'
            );
            $this->db->where('packing_list_id', $wh_packing_list['wh_packing_lists_id']);
            $this->db->where('wh_packing_list_details.koli >', 0);
            $list_items = $this->db->get()->result_array();

            if (count($list_items)) {
                $vendor = array();
                $sheet->mergeCells('A' . $i . ':O' . $i);
                $sheet->setCellValue('A' . $i, "Shop Name:" . $wh_packing_list['company'] . '  Shop Nr.:' . $wh_packing_list['vendor_code']);
                $sheet->getStyle('A' . $i)->getFont()->setBold(true);
                $sheet->getStyle('A' . $i)->getFont()->setSize(16);
                $sheet->getStyle('A' . $i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A' . $i)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $styleArray = [
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'D8D8D8',
                        ],
                    ],
                ];
                $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
                $i++;


                $sheet->setCellValue('A' . $i, 'Barcode');
                $sheet->setCellValue('B' . $i, 'Image');
                $sheet->setCellValue('C' . $i, 'Product Code');
                $sheet->setCellValue('D' . $i, 'Product Name');
                $sheet->setCellValue('E' . $i, 'Cartons');
                $sheet->setCellValue('F' . $i, 'Pieces/Carton');
                $sheet->setCellValue('G' . $i, 'Total/Pieces');
                $sheet->setCellValue('H' . $i, 'Price');
                $sheet->setCellValue('I' . $i, 'Price/Total');
                $sheet->setCellValue('J' . $i, 'Gross Weight');
                $sheet->setCellValue('K' . $i, 'Total Gross Weight');
                $sheet->setCellValue('L' . $i, 'Net Weight');
                $sheet->setCellValue('M' . $i, 'Total Net Weight');
                $sheet->setCellValue('N' . $i, 'CBM');
                $sheet->setCellValue('O' . $i, 'Total CBM');


                $styleArray = [
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'name' => 'Calibri', // optional, if you want to enforce font here too
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'DDEBF7',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ];

                $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
                $i++;



                $vendor_koli = 0;
                $vendor_total_peaces = 0;
                $vendor_total_prices = 0;
                $vendor_total_net_weight = 0;
                $vendor_total_gross_weight = 0;
                $vendor_total_cbm = 0;
                foreach ($list_items as $list_item) {
                    $barcode = (string) $list_item['commodity_barcode'];
                    $sheet->setCellValueExplicit('A' . $i, $barcode, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);


                    if (isset($arr_images[$list_item['commodity_code']]) && isset($arr_images[$list_item['commodity_code']][0])) {

                        $imagePath = FCPATH . 'modules/purchase/uploads/item_img/' . $arr_images[$list_item['commodity_code']][0]['rel_id'] . '/' . $arr_images[$list_item['commodity_code']][0]['file_name'];

                        if (file_exists($imagePath)) {
                            $drawing = new Drawing();
                            $drawing->setName($arr_images[$list_item['id']][0]['file_name']);
                            $drawing->setDescription($arr_images[$list_item['id']][0]['file_name']);
                            $drawing->setPath($imagePath);
                            $drawing->setHeight(50);
                            $cellColumn = 'B';
                            $cellRow = $i;
                            $columnWidth = $sheet->getColumnDimension($cellColumn)->getWidth();
                            $pixelWidth = $columnWidth * 7;
                            $offsetX = max(0, ($pixelWidth - $drawing->getWidth()) / 2);
                            $drawing->setOffsetX($offsetX);
                            $sheet->getRowDimension($cellRow)->setRowHeight(60);
                            $drawing->setOffsetY(5);
                            $drawing->setCoordinates($cellColumn . $cellRow);
                            $drawing->setWorksheet($sheet);

                        } else {
                            $sheet->setCellValue('B' . $i, '');
                        }
                    } else {
                        $sheet->setCellValue('B' . $i, '');
                    }
                    $pricetotal = (float) $list_item['rate'] * (float) $list_item['total_koli'];
                    $sheet->setCellValue('C' . $i, $list_item['commodity_name']);
                    $sheet->setCellValue('D' . $i, $list_item['commodity_long_description']);
                    $sheet->setCellValue('E' . $i, $list_item['koli']);
                    $sheet->setCellValue('F' . $i, $list_item['cope_koli']);
                    $sheet->setCellValue('G' . $i, $list_item['total_koli']);
                    $cell = 'H' . $i;
                    $sheet->setCellValue($cell, $list_item['rate']);
                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => [
                            'color' => ['rgb' => 'FF0000'], // Red
                        ],
                    ]);
                    $cell = 'I' . $i;
                    $sheet->setCellValue($cell, '¥' . $pricetotal);
                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FF0000'], // Red
                        ],
                    ]);
                    $sheet->setCellValue('J' . $i, clean_number($list_item['gross_weight']));
                    $sheet->setCellValue('K' . $i, clean_number($list_item['total_gross_weight']));
                    $sheet->setCellValue('L' . $i, clean_number($list_item['net_weight']));
                    $sheet->setCellValue('M' . $i, clean_number($list_item['total_net_weight']));
                    $sheet->setCellValue('N' . $i, clean_number($list_item['cbm_koli']));
                    $sheet->setCellValue('O' . $i, clean_number($list_item['total_cbm']));
                    $styleArray = [
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                    ];

                    $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);

                    $i++;
                    $vendor_koli += (float) $list_item['koli'];
                    $vendor_total_peaces += (float) $list_item['total_koli'];
                    $vendor_total_prices += (float) $pricetotal;
                    $vendor_total_net_weight += (float) $list_item['total_net_weight'];
                    $vendor_total_gross_weight += (float) $list_item['total_gross_weight'];
                    $vendor_total_cbm += (float) $list_item['total_cbm'];
                }

                $sheet->mergeCells('A' . $i . ':D' . $i);
                $sheet->setCellValue('A' . $i, 'Total Of the ' . $wh_packing_list['company']);
                $sheet->setCellValue('E' . $i, $vendor_koli);
                $sheet->setCellValue('F' . $i, '');
                $sheet->setCellValue('G' . $i, $vendor_total_peaces);
                $sheet->setCellValue('H' . $i, '');
                $sheet->setCellValue('I' . $i, '¥' . $vendor_total_prices);
                $sheet->setCellValue('J' . $i, '');
                $sheet->setCellValue('K' . $i, clean_number($vendor_total_net_weight));
                $sheet->setCellValue('L' . $i, '');
                $sheet->setCellValue('M' . $i, clean_number($vendor_total_gross_weight));
                $sheet->setCellValue('N' . $i, '');
                $sheet->setCellValue('O' . $i, clean_number($vendor_total_cbm));


                $styleArray = [
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FFF2CC',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ];
                $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
                $i++;
                $summary[0]['data'] += $vendor_koli;
                $summary[1]['data'] += $vendor_total_peaces;
                $summary[2]['data'] += $vendor_total_prices;
                $summary[3]['data'] += $vendor_total_net_weight;
                $summary[4]['data'] += $vendor_total_gross_weight;
                $summary[5]['data'] += $vendor_total_cbm;

            }
        }
        $summary[2]['data'] = '¥' . $summary[2]['data'];
        $sheet->mergeCells('A' . $i . ':D' . $i);
        $sheet->setCellValue('A' . $i, 'Total Of Full Order');
        $sheet->setCellValue('E' . $i, $summary[0]['data']);
        $sheet->setCellValue('F' . $i, '');
        $sheet->setCellValue('G' . $i, $summary[1]['data']);
        $sheet->setCellValue('H' . $i, '');
        $sheet->setCellValue('I' . $i, $summary[2]['data']);
        $sheet->setCellValue('J' . $i, '');
        $sheet->setCellValue('K' . $i, clean_number($summary[3]['data']));
        $sheet->setCellValue('L' . $i, '');
        $sheet->setCellValue('M' . $i, clean_number($summary[4]['data']));
        $sheet->setCellValue('N' . $i, '');
        $sheet->setCellValue('O' . $i, clean_number($summary[5]['data']));

        $styleArray = [
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'F4B084',
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
        $i++;
        //two empty row
        $sheet->mergeCells('A' . $i . ':O' . $i);
        $sheet->setCellValue('A' . $i, '');
        $i++;
        $sheet->mergeCells('A' . $i . ':O' . $i);
        $sheet->setCellValue('A' . $i, '');
        $i++;

        $writer = new Xlsx($spreadsheet);
        $fileName = $project->name . "_" . time() . '.xlsx';
        $filePath = FCPATH . 'uploads/' . $fileName;

        $writer->save($filePath);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        readfile($filePath);
        unlink($filePath);
        exit;
    }
    public function add_stock($id)
    {
        $project = $this->projects_model->get($id);
        if ($this->input->post()) {
            $data = $this->input->post();
            $insert_data = array();
            $update_data = array();
            $delete_ids = array();
            foreach ($data['quantity'] as $item_id => $quantity) {
                $key = array_keys($quantity);
                if ($data['id'][$item_id]) {
                    if ($quantity[$key[0]]) {
                        $update['id'] = $data['id'][$item_id];
                        $update['quantity'] = $quantity[$key[0]];
                        $update_data[] = $update;
                    } else {
                        $delete_ids[] = $data['id'][$item_id];
                    }
                } else {
                    if ($quantity[$key[0]]) {
                        $single['item_id'] = $item_id;
                        $single['project_id'] = $id;
                        $single['client_id'] = $project->clientid;
                        $single['goods_receipt_details_id'] = $key[0];
                        $single['quantity'] = $quantity[$key[0]];
                        $insert_data[] = $single;
                    }
                }
            }
            if (count($insert_data)) {
                $this->db->insert_batch('tblproject_stocks', $insert_data);
            }
            if (count($update_data)) {
                $this->db->update_batch('tblproject_stocks', $update_data, 'id');
            }
            if (count($delete_ids)) {
                $this->db->where_in('id', $delete_ids);
                $this->db->delete('tblproject_stocks');
            }
            set_alert('success', _l(line: 'Extra Item has been added to the project'));
            redirect(admin_url('projects/view/' . $id . '?group=project_packing_list'));

        }

        if ($project->status == 4) {
            set_alert('warning', _l(line: 'Project is finished. You can not add stock'));
            redirect(admin_url('projects/'));
        }


        $this->db->from('tblproject_stocks');
        $this->db->where('project_id', $id);
        $project_stocks = $this->db->get()->result_array();
        $filter_project_stocks = array();
        foreach ($project_stocks as $stocks) {
            $filter_project_stocks[$stocks['item_id']] = $stocks;
        }
        $this->load->model('warehouse/warehouse_model');

        $this->db->select('tblgoods_delivery.id as goods_delivery_id, tblgoods_receipt.id as goods_receipt_id,tblgoods_receipt.project');
        $this->db->from('tblgoods_delivery');
        $this->db->join('tblgoods_receipt', 'tblgoods_receipt.goods_delivery_id = tblgoods_delivery.id', 'left');
        $this->db->join('tblprojects', 'tblprojects.id = tblgoods_receipt.project', 'left');
        $this->db->where('goods_delivery.customer_code', $project->clientid);
        $this->db->where_in('tblprojects.status', [4, 5]);

        $result = $this->db->get()->result_array();
        $goods_receipt_details_ids = array();
        foreach ($result as $row) {
            $goods_receipt_id = $row['goods_receipt_id'];
            $goods_delivery_id = $row['goods_delivery_id'];
            $goods_receipt_details = $this->warehouse_model->get_goods_receipt_detail($goods_receipt_id);
            $goods_delivery_details = $this->warehouse_model->get_goods_delivery_detail($goods_delivery_id);
            $filter_goods_recepits = array();
            foreach ($goods_receipt_details as $goods_receipt_detail) {
                $filter_goods_recepits[$goods_receipt_detail['commodity_code']] = $goods_receipt_detail;
            }
            foreach ($goods_delivery_details as $goods_delivery_detail) {
                if (isset($filter_goods_recepits[$goods_delivery_detail['commodity_code']]) && $filter_goods_recepits[$goods_delivery_detail['commodity_code']]['koli'] > $goods_delivery_detail['koli']) {
                    $single['commodity_code'] = $goods_delivery_detail['commodity_code'];
                    $single['goods_receipt_details_id'] = (int) $filter_goods_recepits[$goods_delivery_detail['commodity_code']]['id'];
                    $single['commodity_long_description'] = $goods_delivery_detail['commodity_long_description'];
                    $single['commodity_name'] = $goods_delivery_detail['commodity_name'];
                    $single['quantity'] = (int) $filter_goods_recepits[$goods_delivery_detail['commodity_code']]['checkd_koli'] - (int) $goods_delivery_detail['koli'];
                    $single['delivery'] = (int) $goods_delivery_detail['koli'];
                    $single['receipt'] = (int) $filter_goods_recepits[$goods_delivery_detail['commodity_code']]['koli'];
                    $single['other'] = 0;
                    if (isset($filter_project_stocks[$goods_delivery_detail['commodity_code']])) {
                        $single['id'] = $filter_project_stocks[$goods_delivery_detail['commodity_code']]['id'];
                        $single['value'] = $filter_project_stocks[$goods_delivery_detail['commodity_code']]['quantity'];
                        $single['delivery'] = +$filter_project_stocks[$goods_delivery_detail['commodity_code']]['quantity'];
                    } else {
                        $single['id'] = '';
                        $single['value'] = '';
                    }
                    $item_array[$single['goods_receipt_details_id']] = $single;
                    $goods_receipt_details_ids[] = $single['goods_receipt_details_id'];
                }
            }
        }
        if (count($goods_receipt_details_ids)) {
            $this->db->select('goods_receipt_details_id, sum(quantity) as quantity');
            $this->db->from('tblproject_stocks');
            $this->db->where('project_id !=', $id);
            $this->db->group_by('goods_receipt_details_id');
            $this->db->where_in('goods_receipt_details_id', $goods_receipt_details_ids);
            $other_stocks = $this->db->get()->result_array();
            foreach ($other_stocks as $other_stock) {
                $item_array[$other_stock['goods_receipt_details_id']]['quantity'] = $item_array[$other_stock['goods_receipt_details_id']]['quantity'] - $other_stock['quantity'];
                $item_array[$other_stock['goods_receipt_details_id']]['delivery'] = $item_array[$other_stock['goods_receipt_details_id']]['delivery'] + $other_stock['quantity'];
                $item_array[$other_stock['goods_receipt_details_id']]['other'] = $other_stock['quantity'];
            }
        }
        $data['id'] = $id;
        $data['items'] = $item_array;
        $this->load->view('admin/projects/add_stock', $data);
    }

    public function generate_project_name()
    {
        if ($this->input->is_ajax_request()) {
            $client_id = $this->input->post('client_id');
            
            if (!$client_id) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Customer ID is required'
                    ]));
                return;
            }

            $this->load->model('clients_model');
            $client = $this->clients_model->get($client_id);
            
            if (!$client) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Customer not found'
                    ]));
                return;
            }

            // Get customer name (company name)
            $customer_name = get_company_name($client_id);
            if (empty($customer_name)) {
                $customer_name = trim($client->firstname . ' ' . $client->lastname);
            }

            // Get current month and year
            $month = date('F'); // Full month name (e.g., December)
            $year = date('Y'); // Full year (e.g., 2025)

            // Generate base project name
            $base_name = $customer_name . ' ' . $month . ' ' . $year;

            // Check for existing projects with the same name pattern
            $this->db->like('name', $base_name, 'after');
            $existing_projects = $this->db->get(db_prefix() . 'projects')->result();

            $max_suffix = 0;
            $pattern = '/^' . preg_quote($base_name, '/') . ' - (\d+)$/';

            foreach ($existing_projects as $project) {
                if (preg_match($pattern, $project->name, $matches)) {
                    $suffix = (int)$matches[1];
                    if ($suffix > $max_suffix) {
                        $max_suffix = $suffix;
                    }
                }
            }

            // Always increment from 1
            $counter = $max_suffix + 1;
            $project_name = $base_name . ' - ' . $counter;

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'project_name' => $project_name,
                    'customer_name' => $customer_name
                ]));
        }
    }

    public function po_on_project_table($project_id = null)
    {
        $this->load->view('admin/tables/po_on_project_table', [
            'project_id' => $project_id
        ]);
    }

    public function manage_purchase_table($project_id = null)
    {
        $this->load->view('admin/tables/manage_purchase_table', [
            'project_id' => $project_id
        ]);
    }

    public function export_irv_details_excel($project_id)
    {
        if (!staff_can('view', 'projects')) {
            access_denied('projects');
        }

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            require_once(FCPATH . 'vendor/autoload.php');
        }

        $this->load->model('projects_model');
        $irvs = $this->projects_model->get_project_irv_orders_items($project_id);

        if (empty($irvs)) {
            set_alert('warning', 'No IRV data found');
            redirect(admin_url('projects/view/' . $project_id));
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        $headers = [
            'Barcode', 'Image', 'Product Code', 'Product Name',
            'Cartons', 'Pieces/Carton', 'Total Pieces',
            'Price', 'Total',
            'Gross Wt', 'Total Gross',
            'Net Wt', 'Total Net',
            'CBM', 'Total CBM'
        ];

        $grand_cartons = $grand_pieces = $grand_price = 0;
        $grand_gross = $grand_net = $grand_cbm = 0;

        foreach ($irvs as $irv) {

            $sheet->mergeCells("A{$row}:O{$row}");
            $sheet->setCellValue("A{$row}", 'IRV: ' . $irv['irv_code']);
            $sheet->getStyle("A{$row}:O{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:O{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D8D8D8');
            $sheet->getStyle("A{$row}:O{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $row++;

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col.$row, $header);
                $sheet->getStyle($col.$row)->getFont()->setBold(true);
                $sheet->getStyle($col.$row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');
                $sheet->getStyle($col.$row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $col++;
            }
            $row++;

            $sum_cartons = $sum_pieces = $sum_price = 0;
            $sum_gross = $sum_net = $sum_cbm = 0;

            foreach ($irv['items'] as $item) {

                $col = 'A';
                foreach ($item as $index => $cell) {

                    if ($index == 0) {
                        $barcode_text = strip_tags($cell);
                        $barcode_text = trim($barcode_text);
                        if ($barcode_text === 'No barcode') {
                            $barcode_text = '';
                        }
                        $sheet->setCellValueExplicit(
                            $col.$row,
                            $barcode_text,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                    }
                    elseif ($index == 1) {
                        $img_path = '';
                        if (!empty($cell) && preg_match('/src=["\']([^"\']+)["\']/', $cell, $matches)) {
                            $img_url = $matches[1];
                            $img_path = str_replace(site_url(), FCPATH, $img_url);
                            $img_path = str_replace(base_url(), FCPATH, $img_path);
                        }

                        if (!empty($img_path) && file_exists($img_path)) {
                            $drawing = new Drawing();
                            $drawing->setPath($img_path);
                            $drawing->setHeight(60);
                            $drawing->setCoordinates($col.$row);
                            $drawing->setWorksheet($sheet);
                            $sheet->getRowDimension($row)->setRowHeight(50);
                        }
                    } else {
                        $cell_value = strip_tags($cell);
                        if (strpos($cell_value, '@') === 0) {
                            $cell_value = "'" . $cell_value;
                        }
                        $sheet->setCellValue($col.$row, $cell_value);
                    }

                    $sheet->getStyle($col.$row)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    $col++;
                }

                $sum_cartons += (float)($item[4] ?? 0);
                $sum_pieces  += (float)($item[6] ?? 0);
                $sum_price   += (float)str_replace('¥', '', ($item[8] ?? 0));
                $sum_gross   += (float)($item[10] ?? 0);
                $sum_net     += (float)($item[12] ?? 0);
                $sum_cbm     += (float)($item[14] ?? 0);

                $row++;
            }

            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", 'IRV Total');
            $sheet->setCellValue("E{$row}", $sum_cartons);
            $sheet->setCellValue("G{$row}", $sum_pieces);
            $sheet->setCellValue("I{$row}", $sum_price);
            $sheet->setCellValue("K{$row}", $sum_gross);
            $sheet->setCellValue("M{$row}", $sum_net);
            $sheet->setCellValue("O{$row}", $sum_cbm);

            $sheet->getStyle("A{$row}:O{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:O{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3CD');
            $sheet->getStyle("A{$row}:O{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $grand_cartons += $sum_cartons;
            $grand_pieces  += $sum_pieces;
            $grand_price   += $sum_price;
            $grand_gross   += $sum_gross;
            $grand_net     += $sum_net;
            $grand_cbm     += $sum_cbm;

            $row++;
        }

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'Total Of Full Order');
        $sheet->setCellValue("E{$row}", $grand_cartons);
        $sheet->setCellValue("G{$row}", $grand_pieces);
        $sheet->setCellValue("I{$row}", $grand_price);
        $sheet->setCellValue("K{$row}", $grand_gross);
        $sheet->setCellValue("M{$row}", $grand_net);
        $sheet->setCellValue("O{$row}", $grand_cbm);

        $sheet->getStyle("A{$row}:O{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:O{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4B084');
        $sheet->getStyle("A{$row}:O{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        foreach (range('A','O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'IRV_Details_'.date('Ymd_His').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    public function export_po_details_excel($project_id)
    {
        if (!staff_can('view', 'projects')) {
            access_denied('projects');
        }

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            require_once(FCPATH . 'vendor/autoload.php');
        }

        $this->load->model('projects_model');
        $vendors = $this->projects_model->get_project_po_orders_items($project_id);

        if (empty($vendors)) {
            set_alert('warning', 'No PO items found');
            redirect(admin_url('projects/view/' . $project_id));
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        $headers = [
            'Barcode', 'Image', 'Product Code', 'Product Name',
            'Cartons', 'Pieces/Carton', 'Total Pieces',
            'Price', 'Total',
            'Gross Wt', 'Total Gross',
            'Net Wt', 'Total Net',
            'CBM', 'Total CBM'
        ];

        $grand_cartons = $grand_pieces = $grand_price = 0;
        $grand_gross = $grand_net = $grand_cbm = 0;

        foreach ($vendors as $vendor) {

            $sheet->mergeCells("A{$row}:O{$row}");
            $sheet->setCellValue("A{$row}", $vendor['vendor_name']);
            $sheet->getStyle("A{$row}:O{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:O{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D8D8D8');
            $sheet->getStyle("A{$row}:O{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $row++;

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col.$row, $header);
                $sheet->getStyle($col.$row)->getFont()->setBold(true);
                $sheet->getStyle($col.$row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DDEBF7');
                $sheet->getStyle($col.$row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $col++;
            }
            $row++;

            $sum_cartons = $sum_pieces = $sum_price = 0;
            $sum_gross = $sum_net = $sum_cbm = 0;

            foreach ($vendor['items'] as $item) {

                $col = 'A';
                foreach ($item as $index => $cell) {

                    if ($index == 0) {
                        $barcode_text = strip_tags($cell);
                        $barcode_text = trim($barcode_text);
                        if ($barcode_text === 'No barcode') {
                            $barcode_text = '';
                        }
                        $sheet->setCellValueExplicit(
                            $col.$row,
                            $barcode_text,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                    }
                    elseif ($index == 1) {
                        $img_path = '';
                        if (!empty($cell) && preg_match('/src=["\']([^"\']+)["\']/', $cell, $matches)) {
                            $img_url = $matches[1];
                            $img_path = str_replace(site_url(), FCPATH, $img_url);
                            $img_path = str_replace(base_url(), FCPATH, $img_path);
                        }
                        
                        if (!empty($img_path) && file_exists($img_path)) {
                            $drawing = new Drawing();
                            $drawing->setPath($img_path);
                            $drawing->setHeight(60);
                            $drawing->setCoordinates($col.$row);
                            $drawing->setWorksheet($sheet);
                            $sheet->getRowDimension($row)->setRowHeight(50);
                        }
                    } else {
                        $cell_value = strip_tags($cell);
                        if (strpos($cell_value, '@') === 0) {
                            $cell_value = "'" . $cell_value;
                        }
                        $sheet->setCellValue($col.$row, $cell_value);
                    }

                    $sheet->getStyle($col.$row)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    $col++;
                }

                $sum_cartons += (float)($item[4] ?? 0);
                $sum_pieces  += (float)($item[6] ?? 0);
                $sum_price   += (float)str_replace('¥', '', ($item[8] ?? 0));
                $sum_gross   += (float)($item[10] ?? 0);
                $sum_net     += (float)($item[12] ?? 0);
                $sum_cbm     += (float)($item[14] ?? 0);

                $row++;
            }

            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", 'Total Of '.$vendor['company_name']);
            $sheet->setCellValue("E{$row}", $sum_cartons);
            $sheet->setCellValue("G{$row}", $sum_pieces);
            $sheet->setCellValue("I{$row}", $sum_price);
            $sheet->setCellValue("K{$row}", $sum_gross);
            $sheet->setCellValue("M{$row}", $sum_net);
            $sheet->setCellValue("O{$row}", $sum_cbm);

            $sheet->getStyle("A{$row}:O{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:O{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3CD');
            $sheet->getStyle("A{$row}:O{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $grand_cartons += $sum_cartons;
            $grand_pieces  += $sum_pieces;
            $grand_price   += $sum_price;
            $grand_gross   += $sum_gross;
            $grand_net     += $sum_net;
            $grand_cbm     += $sum_cbm;

            $row++;
        }

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'Total Of Full Order');
        $sheet->setCellValue("E{$row}", $grand_cartons);
        $sheet->setCellValue("G{$row}", $grand_pieces);
        $sheet->setCellValue("I{$row}", $grand_price);
        $sheet->setCellValue("K{$row}", $grand_gross);
        $sheet->setCellValue("M{$row}", $grand_net);
        $sheet->setCellValue("O{$row}", $grand_cbm);

        $sheet->getStyle("A{$row}:O{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:O{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4B084');
        $sheet->getStyle("A{$row}:O{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        foreach (range('A','O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'PO_Details_'.date('Ymd_His').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    public function get_status_counts()
    {
        $client_filter = $this->input->get('client_filter');
        $worker_filter = $this->input->get('worker');
        $date_range    = $this->input->get('date_range');
        $date_from     = $this->input->get('date_from');
        $date_to       = $this->input->get('date_to');

        $ci = &get_instance();
        $statuses = $ci->projects_model->get_project_statuses();

        $counts = [];
        foreach($statuses as $status){
            $ci->db->from(db_prefix().'projects');
            $ci->db->where('status',$status['id']);

            if(!empty($client_filter)){
                $ci->db->where_in('clientid',$client_filter);
            }
            if(!empty($worker_filter)){
                $ci->db->where('id IN (SELECT project_id FROM '.db_prefix().'project_members WHERE staff_id IN ('.implode(',',$worker_filter).'))');
            }
            if($date_range && $date_range != 'custom'){
                $range = get_dates_from_select_range($date_range);
                $ci->db->where('DATE(start_date) >=',$range['from']);
                $ci->db->where('DATE(start_date) <=',$range['to']);
            } elseif($date_range == 'custom' && !empty($date_from) && !empty($date_to)){
                $ci->db->where('DATE(start_date) >=',$date_from);
                $ci->db->where('DATE(start_date) <=',$date_to);
            }

            $counts[$status['id']] = $ci->db->count_all_results();
        }

        echo json_encode($counts);
    }
}
