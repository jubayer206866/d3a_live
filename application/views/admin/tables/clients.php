<?php

defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('clients')
    ->outputUsing(function ($params) {
        extract($params);

        $hasPermissionDelete = staff_can('delete', 'customers');

        $custom_fields = get_table_custom_fields('customers');
        $this->ci->db->query("SET sql_mode = ''");

        $aColumns = [
            '1',
            db_prefix() . 'clients.userid as userid',
            'company',
            'shipping_mark',
            'tier',
            'service_fee',
            'CONCAT(firstname, " ", lastname) as fullname',
            'email',
            db_prefix() . 'clients.phonenumber as phonenumber',
            db_prefix() . 'clients.active',
            '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'customer_groups JOIN ' . db_prefix() . 'customers_groups ON ' . db_prefix() . 'customer_groups.groupid = ' . db_prefix() . 'customers_groups.id WHERE customer_id = ' . db_prefix() . 'clients.userid ORDER by name ASC) as customerGroups',
            db_prefix() . 'clients.datecreated as datecreated',
        ];

        $sIndexColumn = 'userid';
        $sTable       = db_prefix() . 'clients';
        $where        = [];

        if ($filtersWhere = $this->getWhereFromRules()) {
            $where[] = $filtersWhere;
        }

        $shipping_mark = $this->ci->input->post('shipping_mark_filter');
        $tier          = $this->ci->input->post('tier_filter');

        if (!empty($shipping_mark) && is_array($shipping_mark)) {
            if (ctype_digit(implode('', $shipping_mark))) {
                $shipping_mark = array_map('intval', $shipping_mark);
                $where[] = 'AND ' . db_prefix() . 'clients.shipping_mark IN (' . implode(',', $shipping_mark) . ')';
            } else {
                $shipping_mark = array_map('trim', $shipping_mark);
                $shipping_mark = array_map(function($v){ return $this->ci->db->escape_str($v); }, $shipping_mark);
                $where[] = "AND " . db_prefix() . "clients.shipping_mark IN ('" . implode("','", $shipping_mark) . "')";
            }
        }

        if (!empty($tier) && is_array($tier)) {
            if (ctype_digit(implode('', $tier))) {
                $tier = array_map('intval', $tier);
                $where[] = 'AND ' . db_prefix() . 'clients.tier IN (' . implode(',', $tier) . ')';
            } else {
                $tier = array_map('trim', $tier);
                $tier = array_map(function($v){ return $this->ci->db->escape_str($v); }, $tier);
                $where[] = "AND " . db_prefix() . "clients.tier IN ('" . implode("','", $tier) . "')";
            }
        }

        $join = [
            'LEFT JOIN ' . db_prefix() . 'contacts ON ' . db_prefix() . 'contacts.userid=' . db_prefix() . 'clients.userid AND ' . db_prefix() . 'contacts.is_primary=1',
        ];

        foreach ($custom_fields as $key => $field) {
            $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
            array_push($customFieldsColumns, $selectAs);
            array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
            array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'clients.userid = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
        }

        $join = hooks()->apply_filters('customers_table_sql_join', $join);

        if (staff_cant('view', 'customers')) {
            array_push($where, 'AND ' . db_prefix() . 'clients.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')');
        }

        $aColumns = hooks()->apply_filters('customers_table_sql_columns', $aColumns);

        // Fix for big queries. Some hosting have max_join_limit
        if (count($custom_fields) > 4) {
            @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'contacts.id as contact_id',
            'lastname',
            db_prefix() . 'clients.zip as zip',
            'registration_confirmed',
            'vat',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            // Bulk actions
            $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['userid'] . '"><label></label></div>';
            // User id
            $row[] = $aRow['userid'];

            // Company
            $company  = e($aRow['company']);
            $isPerson = false;

            if ($company == '') {
                $company  = _l('no_company_view_profile');
                $isPerson = true;
            }

            $url = admin_url('clients/client/' . $aRow['userid']);

            if ($isPerson && $aRow['contact_id']) {
                $url .= '?contactid=' . $aRow['contact_id'];
            }

            $company = '<a href="' . $url . '" class="tw-font-medium">' . $company . '</a>';
            $canView   = staff_can('view', 'customers');
            $canEdit   = staff_can('edit', 'customers');
            $canDelete = staff_can('delete', 'customers');

            $company = e($aRow['company']);
            $isPerson = false;

            if ($company == '') {
                $company  = _l('no_company_view_profile');
                $isPerson = true;
            }

            if ($canEdit || $canDelete) {
                $companyUrl = admin_url('clients/client/' . $aRow['userid']);
                if ($isPerson && $aRow['contact_id']) {
                    $companyUrl .= '?contactid=' . $aRow['contact_id'];
                }

                $company = '<a href="' . $companyUrl . '" class="tw-font-medium">' . $company . '</a>';

                $company .= '<div class="row-options">';
                if ($canEdit) {
                    $company .= '<a href="' . admin_url('clients/client/' . $aRow['userid'] . '?group=contacts') . '">' . _l('customer_contacts') . '</a>';
                    $company .= ' | <a href="' . admin_url('clients/copy_info/' . $aRow['userid']) . '" class="copy-client-info">' . _l('Copy info') . '</a>';
                }
                if ($canDelete) {
                    $company .= ' | <a href="' . admin_url('clients/delete/' . $aRow['userid']) . '" class="_delete">' . _l('delete') . '</a>';
                }
                $company .= '</div>';
            } else if ($canView) {
                $company = e($aRow['company'] ? $aRow['company'] : _l('no_company_view_profile'));
            }

            $row[] = $company;
            $row[] = e($aRow['shipping_mark']);
            $row[] = e($aRow['tier']);
            $row[] = e($aRow['service_fee']);

            // Primary contact
            $fullname = e(trim($aRow['fullname']));
            $fullname = hide_non_admin_info($fullname); // Mask staff jonno
            $row[] = ($aRow['contact_id'] ? '<a href="' . admin_url('clients/client/' . $aRow['userid'] . '?contactid=' . $aRow['contact_id']) . '" target="_blank" class="tw-font-medium">' . $fullname . '</a>' : '');

            // Primary contact email
            $email = e($aRow['email']);
            $email = hide_non_admin_info($email);
            $row[] = ($aRow['email'] ? '<a href="mailto:' . $email . '">' . $email . '</a>' : '');

            // Primary contact phone
            $phone = e($aRow['phonenumber']);
            $phone = hide_non_admin_info($phone); // Mask staff jonno
            $row[] = ($aRow['phonenumber'] ? '<a href="tel:' . $phone . '">' . $phone . '</a>' : '');

            // Toggle active/inactive customer
    //         $toggleActive = '<div class="onoffswitch" data-toggle="tooltip" data-title="' . _l('customer_active_inactive_help') . '">
    // <input type="checkbox"' . ($aRow['registration_confirmed'] == 0 ? ' disabled' : '') . ' data-switch-url="' . admin_url() . 'clients/change_client_status" name="onoffswitch" class="onoffswitch-checkbox" id="' . $aRow['userid'] . '" data-id="' . $aRow['userid'] . '" ' . ($aRow[db_prefix() . 'clients.active'] == 1 ? 'checked' : '') . '>
    // <label class="onoffswitch-label" for="' . $aRow['userid'] . '"></label>
    // </div>';

    //         // For exporting
    //         $toggleActive .= '<span class="hide">' . ($aRow[db_prefix() . 'clients.active'] == 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';

    //         $row[] = $toggleActive;

            // Customer Admins
            $adminsData = $this->ci->clients_model->get_admins($aRow['userid']);
            $admins = '';
            if (!empty($adminsData)) {
                $names = [];
                foreach ($adminsData as $admin) {
                    $names[] = '<a href="' . admin_url('profile/' . $admin['staff_id']) . '" target="_blank">' . get_staff_full_name($admin['staff_id']) . '</a>';
                }
                $admins = implode(', ', $names);
            }
            $row[] = $admins;

            // Customer groups parsing
            $groupsRow = '';
            if ($aRow['customerGroups']) {
                $groups = explode(',', $aRow['customerGroups']);

                foreach ($groups as $group) {
                    $groupsRow .= '<span class="label label-default mleft5 customer-group-list pointer">' . e($group) . '</span>';
                }
            }

            $row[] = $groupsRow;

            $row[] = e(_dt($aRow['datecreated']));

            // Custom fields add values
            foreach ($customFieldsColumns as $customFieldColumn) {
                $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
            }

            $row['DT_RowClass'] = 'has-row-options has-border-left';

            if ($aRow['registration_confirmed'] == 0) {
                $row['DT_RowClass'] .= ' row-border-warning requires-confirmation';
                $row['Data_Title']  = _l('customer_requires_registration_confirmation');
                $row['Data_Toggle'] = 'tooltip';
            }

            if ($aRow[db_prefix() . 'clients.active'] == 0) {
                $row['DT_RowClass'] .= ' secondary';
            }

            $row = hooks()->apply_filters('customers_table_row_data', $row, $aRow);

            $output['aaData'][] = $row;
        }

        return $output;
    })->setRules([
        App_table_filter::new('phonenumber', 'TextRule')->label(_l('clients_phone')),
        App_table_filter::new('active', 'BooleanRule')->label(_l('customer_active')),
        App_table_filter::new('invoice_statuses', 'MultiSelectRule')->label(_l('invoices'))
            ->options(function ($ci) {
                $ci->load->model('invoices_model');

                return collect($ci->invoices_model->get_statuses())->map(fn ($status) => [
                    'value' => $status,
                    'label' => _l('customer_have_invoices_by', format_invoice_status($status, '', false)),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT clientid FROM ' . db_prefix() . 'invoices WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),

        App_table_filter::new('estimate_statuses', 'MultiSelectRule')->label(_l('estimates'))
            ->options(function ($ci) {
                $ci->load->model('estimates_model');

                return collect($ci->estimates_model->get_statuses())->map(fn ($status) => [
                    'value' => $status,
                    'label' => _l('customer_have_estimates_by', format_estimate_status($status, '', false)),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT clientid FROM ' . db_prefix() . 'estimates WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),

        App_table_filter::new('proposal_statuses', 'MultiSelectRule')->label(_l('proposals'))
            ->options(function ($ci) {
                $ci->load->model('proposals_model');

                return collect($ci->proposals_model->get_statuses())->map(fn ($status) => [
                    'value' => $status,
                    'label' => _l('customer_have_proposals_by', format_proposal_status($status, '', false)),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT rel_id FROM ' . db_prefix() . 'proposals WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . ') AND rel_type="customer")';
            }),

        App_table_filter::new('project_statuses', 'MultiSelectRule')->label(_l('projects'))
            ->options(function ($ci) {
                $ci->load->model('projects_model');

                return collect($ci->projects_model->get_project_statuses())->map(fn ($data) => [
                    'value' => $data['id'],
                    'label' => _l('customer_have_projects_by', $data['name']),
                ]);
            })->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT clientid FROM ' . db_prefix() . 'projects WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),

        App_table_filter::new('contracts_types', 'MultiSelectRule')->label(_l('contract_types'))
            ->options(function ($ci) {
                $ci->load->model('contracts_model');

                return collect($ci->contracts_model->get_contract_types())->map(fn ($data) => [
                    'value' => $data['id'],
                    'label' => _l('customer_have_contracts_by_type', $data['name']),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT client FROM ' . db_prefix() . 'contracts WHERE contract_type ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),
        App_table_filter::new('city', 'TextRule')->label(_l('clients_city')),
        App_table_filter::new('zip', 'TextRule')->label(_l('clients_zip')),
        App_table_filter::new('state', 'TextRule')->label(_l('clients_state')),
        App_table_filter::new('country', 'SelectRule')->label(_l('clients_country'))
            ->options(function ($ci) {
                return collect($ci->clients_model->get_clients_distinct_countries())->map(fn ($data) => [
                    'value' => $data['country_id'],
                    'label' => $data['short_name'],
                ]);
            }),
        App_table_filter::new('customer_admins', 'MultiSelectRule')->label(_l('responsible_admin'))
            ->isVisible(fn () => staff_can('create', 'customers') || staff_can('edit', 'customers'))
            ->options(function ($ci) {
                return collect($ci->clients_model->get_customers_admin_unique_ids())->map(fn ($data) => [
                    'value' => $data['staff_id'],
                    'label' => get_staff_full_name($data['staff_id']),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),
        App_table_filter::new('groups', 'MultiSelectRule')->label(_l('customer_groups'))
            ->options(function ($ci) {
                return collect($ci->clients_model->get_groups())->map(fn ($group) => [
                    'value' => $group['id'],
                    'label' => $group['name'],
                ]);
            })->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_groups WHERE groupid ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),
        App_table_filter::new('my_customers', 'BooleanRule')->label(_l('customers_assigned_to_me'))
            ->raw(function ($value) {
                return db_prefix() . 'clients.userid ' . ($value == '1' ? 'IN' : 'NOT IN') . ' (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')';
            }),
        App_table_filter::new('requires_confirmation', 'BooleanRule')
            ->label(_l('customer_requires_registration_confirmation'))
            ->raw(function ($value) {
                return db_prefix() . 'clients.registration_confirmed=' . ($value == '1' ? '0' : '1');
            }),
    ]);
