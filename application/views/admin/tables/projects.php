<?php

defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('projects')
    ->outputUsing(function ($params) {
        extract($params);

        $hasPermissionEdit = staff_can('edit', 'projects');
        $hasPermissionDelete = staff_can('delete', 'projects');
        $hasPermissionCreate = staff_can('create', 'projects');

        $aColumns = [
            db_prefix() . 'projects.id as id',
            'name',
            get_sql_select_client_company(),
            'start_date',
            '(SELECT GROUP_CONCAT(CONCAT(firstname, \' \', lastname) SEPARATOR ",") FROM ' . db_prefix() . 'project_members JOIN ' . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'project_members.staff_id WHERE project_id=' . db_prefix() . 'projects.id ORDER BY staff_id) as members',
            'status',
        ];


        $sIndexColumn = 'id';
        $sTable = db_prefix() . 'projects';

        $join = [
            'JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid',
        ];

        $where = [];
        if ($filtersWhere = $this->getWhereFromRules()) {
            $where[] = $filtersWhere;
        }

        array_push($where, 'AND tblprojects.id >= 1');

        $ci =& get_instance();
        $clientid = $ci->input->post('client_filter');
        $worker_filter = $ci->input->post('worker');
        $date_range = $ci->input->post('date_range');
        $date_from = $ci->input->post('date_from');
        $date_to = $ci->input->post('date_to');


        if (!empty($clientid) && is_array($clientid)) {
            $client_ids = implode(',', array_map('intval', $clientid));
            $where[] = "AND clientid IN ($client_ids)";
        }


        if (!empty($worker_filter) && is_array($worker_filter)) {
            $worker_ids = implode(',', array_map('intval', $worker_filter));
            $where[] = 'AND ' . db_prefix() . "projects.id IN (
                SELECT project_id FROM " . db_prefix() . "project_members WHERE staff_id IN ($worker_ids)
            )";
        }


        if ($date_range && $date_range != 'custom') {
            $range = get_dates_from_select_range($date_range);
            $where[] = 'AND DATE(' . db_prefix() . 'projects.start_date) BETWEEN "' . $range['from'] . '" AND "' . $range['to'] . '"';
        } elseif ($date_range == 'custom' && !empty($date_from) && !empty($date_to)) {
            $where[] = 'AND DATE(' . db_prefix() . 'projects.start_date) BETWEEN "' . $ci->db->escape_str($date_from) . '" AND "' . $ci->db->escape_str($date_to) . '"';
        }

        if (staff_cant('view', 'projects')) {
            $where[] = 'AND ' . db_prefix() . 'projects.id IN (
                SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . '
            )';
        }

        $custom_fields = get_table_custom_fields('projects');
        $customFieldsColumns = [];

        foreach ($custom_fields as $key => $field) {
            $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
            array_push($customFieldsColumns, $selectAs);
            array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
            array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'projects.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
        }

        $aColumns = hooks()->apply_filters('projects_table_sql_columns', $aColumns);

        // Fix for big queries. Some hosting have max_join_limit
        if (count($custom_fields) > 4) {
            @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            'clientid',
            '(SELECT GROUP_CONCAT(staff_id SEPARATOR ",") FROM ' . db_prefix() . 'project_members WHERE project_id=' . db_prefix() . 'projects.id ORDER BY staff_id) as members_ids',
        ]);

        $output = $result['output'];
        $rResult = $result['rResult'];
        $statuses = $this->ci->projects_model->get_project_statuses();
        foreach ($rResult as $aRow) {
            $row = [];

            $link = admin_url('projects/view/' . $aRow['id']);

            $row[] = '<a href="' . $link . '" class="tw-font-medium">' . $aRow['id'] . '</a>';

            $name = '<a href="' . $link . '" class="tw-font-medium">' . e($aRow['name']) . '</a>';

            $name .= '<div class="row-options">';

            $name .= '<a href="' . $link . '">' . _l('view') . '</a>';

            if ($hasPermissionCreate && !$clientid) {
                $name .= ' | <a href="#" data-name="' . e($aRow['name']) . '" onclick="copy_project(' . $aRow['id'] . ', this);return false;">' . _l('copy_project') . '</a>';
            }

            if ($hasPermissionEdit) {
                $name .= ' | <a href="' . admin_url('projects/project/' . $aRow['id']) . '">' . _l('edit') . '</a>';
            }

            if ($hasPermissionDelete) {
                $name .= ' | <a href="' . admin_url('projects/delete/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
            }
            if (!in_array($aRow['status'], [4, 5])) {
                $name .= ' | <a href="' . admin_url('projects/add_stock/' . $aRow['id']) . '" class="">' . _l('Add Stock') . '</a>';
            }


            $name .= '</div>';

            $row[] = $name;

            $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '">' . e($aRow['company']) . '</a>';


            $row[] = e(_d($aRow['start_date']));

            $members = explode(',', $aRow['members']);
            $members_ids = explode(',', $aRow['members_ids']);
            $membersOutput = '<div class="tw-flex -tw-space-x-1">';
            foreach ($members as $key => $member) {
                if ($member != '') {
                    $member_id = $members_ids[$key];
                    $membersOutput .= '<a href="' . admin_url('profile/' . $member_id) . '" class="tw-flex tw-items-center tw-space-x-2">'
                        . staff_profile_image($member_id, [
                            'tw-inline-block tw-h-7 tw-w-7 tw-rounded-full tw-ring-2 tw-ring-white',
                        ], 'small', [
                            'data-toggle' => 'tooltip',
                            'data-title' => $member,
                        ])
                        . '<span>' . $member . '</span>'
                        . '</a>';
                }
            }
            $membersOutput .= '</div>';
            $row[] = $membersOutput;

            $currentStatus = get_project_status_by_id($aRow['status']);
            $statusDropdown = '<select class="form-control project-status-select"
            data-project-id="' . $aRow['id'] . '"
            data-project-name="' . e($aRow['name']) . '"
            style="color:' . $currentStatus['color'] . ';
            border:1px solid ' . adjust_hex_brightness($currentStatus['color'], 0.4) . ';
            background:' . adjust_hex_brightness($currentStatus['color'], 0.04) . ';">';

            foreach ($statuses as $statusOption) {
                $selected = ($statusOption['id'] == $aRow['status']) ? 'selected' : '';
                $statusDropdown .= '<option value="' . $statusOption['id'] . '" ' . $selected . '>' . e($statusOption['name']) . '</option>';
            }
            $statusDropdown .= '</select>';
            $row[] = $statusDropdown;

            // Custom fields add values
            foreach ($customFieldsColumns as $customFieldColumn) {
                $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
            }

            $row['DT_RowClass'] = 'has-row-options';

            $row = hooks()->apply_filters('projects_table_row_data', $row, $aRow);

            $output['aaData'][] = $row;
        }
        return $output;
    })->setRules([
            App_table_filter::new('name', 'TextRule')->label(_l('project_name')),
            App_table_filter::new('start_date', 'DateRule')->label(_l('project_start_date')),
            App_table_filter::new('deadline', 'DateRule')->label(_l('project_deadline')),
            App_table_filter::new('billing_type', 'SelectRule')->label(_l('project_billing_type'))->options(function ($ci) {
                return [
                    ['value' => 1, 'label' => _l('project_billing_type_fixed_cost')],
                    ['value' => 2, 'label' => _l('project_billing_type_project_hours')],
                    ['value' => 3, 'label' => _l('project_billing_type_project_task_hours_hourly_rate')],
                ];
            }),
            App_table_filter::new('status', 'MultiSelectRule')->label(_l('project_status'))->options(function ($ci) {
                return collect($ci->projects_model->get_project_statuses())->map(fn($data) => [
                    'value' => $data['id'],
                    'label' => $data['name'],
                ])->all();
            }),

            App_table_filter::new('members', 'MultiSelectRule')->label(_l('project_members'))
                ->isVisible(fn() => staff_can('view', 'projects'))
                ->options(function ($ci) {
                    return collect($ci->projects_model->get_distinct_projects_members())->map(function ($staff) {
                        return [
                            'value' => $staff['staff_id'],
                            'label' => get_staff_full_name($staff['staff_id'])
                        ];
                    })->all();
                })->raw(function ($value, $operator, $sqlOperator) {
                    $dbPrefix = db_prefix();
                    $sqlOperator = $sqlOperator['operator'];
                    return "({$dbPrefix}projects.id IN (SELECT project_id FROM {$dbPrefix}project_members WHERE staff_id $sqlOperator ('" . implode("','", $value) . "')))";
                })
        ]);
