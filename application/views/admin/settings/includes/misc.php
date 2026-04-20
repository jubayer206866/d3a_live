<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="horizontal-scrollable-tabs panel-full-width-tabs">
    <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
    <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
            <li role="presentation" class="active">
                <a href="#misc" aria-controls="misc" role="tab" data-toggle="tab">
                    <i class="fa fa-cog"></i> <?php echo _l('settings_group_misc'); ?></a>
            </li>
            <li role="presentation">
                <a href="#settings_tables" aria-controls="settings_tables" role="tab" data-toggle="tab">
                    <i class="fa fa-table"></i> <?php echo _l('tables'); ?></a>
            </li>
            <li role="presentation">
                <a href="#inline_create" aria-controls="inline_create" role="tab" data-toggle="tab">
                    <i class="fa fa-plus"></i> <?php echo _l('inline_create'); ?>
                </a>
            </li>
        </ul>
    </div>

</div>
<div class="tab-content mtop15">
    <div role="tabpanel" class="tab-pane active" id="misc">
        <?php echo render_yes_no_option('view_contract_only_logged_in', 'settings_require_client_logged_in_to_view_contract'); ?>
        <hr />
        <?php echo render_input('settings[dropbox_app_key]', 'dropbox_app_key', get_option('dropbox_app_key')); ?>
        <hr />
        <?php echo render_input('settings[media_max_file_size_upload]', 'settings_media_max_file_size_upload', get_option('media_max_file_size_upload'), 'number'); ?>
        <hr />
        <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
            data-title="<?php echo _l('settings_group_newsfeed'); ?>"></i>
        <?php echo render_input('settings[newsfeed_maximum_files_upload]', 'settings_newsfeed_max_file_upload_post', get_option('newsfeed_maximum_files_upload'), 'number'); ?>
        <hr />

        <?php echo render_input('settings[limit_top_search_bar_results_to]', 'settings_limit_top_search_bar_results', get_option('limit_top_search_bar_results_to'), 'number'); ?>
        <hr />
        <?php echo render_select('settings[default_staff_role]', $roles, ['roleid', 'name'], 'settings_general_default_staff_role', get_option('default_staff_role'), [], ['data-toggle' => 'tooltip', 'title' => 'settings_general_default_staff_role_tooltip']); ?>
        <hr />
        <?php echo render_input('settings[delete_activity_log_older_then]', 'delete_activity_log_older_then', get_option('delete_activity_log_older_then'), 'number'); ?>
        <hr />
        <?php echo render_yes_no_option('show_setup_menu_item_only_on_hover', 'show_setup_menu_item_only_on_hover'); ?>


        <hr />
        <?php echo render_yes_no_option('show_help_on_setup_menu', 'show_help_on_setup_menu'); ?>
        <hr />
        <?php render_yes_no_option('use_minified_files', 'use_minified_files'); ?>
        <?php hooks()->do_action('after_misc_settings'); ?>
        <hr />
        <h4 class="tw-font-semibold tw-mb-3"><?php echo _l('settings_ddp_calculator_heading'); ?></h4>
        <?php echo render_input('settings[dpp_paymode_b_surcharge]', 'settings_dpp_paymode_b_surcharge', get_option('dpp_paymode_b_surcharge'), 'number', ['step' => '0.01']); ?>
        <hr />
        <?php echo render_input('settings[dpp_discount_value_density_threshold]', 'settings_dpp_discount_value_density_threshold', get_option('dpp_discount_value_density_threshold'), 'number'); ?>
        <hr />
        <?php echo render_input('settings[dpp_discount_fee_per_cbm_threshold]', 'settings_dpp_discount_fee_per_cbm_threshold', get_option('dpp_discount_fee_per_cbm_threshold'), 'number'); ?>
        <hr />
        <?php echo render_input('settings[dpp_discount_rate]', 'settings_dpp_discount_rate', get_option('dpp_discount_rate'), 'number', ['step' => '0.01']); ?>
        <hr />
        <?php echo render_input('settings[dpp_kg_per_cbm]', 'settings_dpp_kg_per_cbm', get_option('dpp_kg_per_cbm'), 'number'); ?>
        <hr />
        <?php echo render_input('settings[dpp_others_tiering_threshold]', 'settings_dpp_others_tiering_threshold', get_option('dpp_others_tiering_threshold'), 'number'); ?>
        <hr />
        <?php echo render_input('settings[dpp_floor_percent_product]', 'settings_dpp_floor_percent_product', get_option('dpp_floor_percent_product'), 'number', ['data-toggle' => 'tooltip', 'title' => _l('settings_dpp_floor_percent_product_tooltip')]); ?>
        <hr />

        <h4 class="tw-font-semibold tw-mb-3"><?php echo _l('settings_lcl_calculator_heading'); ?></h4>
        <?php echo render_input('settings[lcl_kg_per_cbm]', 'settings_lcl_kg_per_cbm', get_option('lcl_kg_per_cbm'), 'number', ['data-toggle' => 'tooltip', 'title' => _l('settings_lcl_kg_per_cbm_tooltip')]); ?>
        <hr />
        <?php echo render_input('settings[lcl_crate_fee_per_unit]', 'settings_lcl_crate_fee_per_unit', get_option('lcl_crate_fee_per_unit'), 'number'); ?>
        <hr />
        <?php echo render_input('settings[lcl_fixed_service_fee]', 'settings_lcl_fixed_service_fee', get_option('lcl_fixed_service_fee'), 'number'); ?>
        <hr />
        <?php echo render_input('settings[lcl_fixed_operational_cost]', 'settings_lcl_fixed_operational_cost', get_option('lcl_fixed_operational_cost'), 'number'); ?>
        <hr />
        <?php echo render_input('settings[lcl_container_capacity]', 'settings_lcl_container_capacity', get_option('lcl_container_capacity'), 'number'); ?>
        <hr />

    </div>

    <div role="tabpanel" class="tab-pane" id="settings_tables">
        <div class="form-group">
            <label for="save_last_order_for_tables" class="control-label clearfix">
                <i class="fa-regular fa-circle-question pointer" data-toggle="popover" data-html="true"
                    data-content="Currently supported tables: Customers, Leads, Tickets, Tasks, Projects, Payments, Subscriptions, Expenses, Proposals, Knowledge Base, Contracts <br /><br /> Note: Changing this option will delete all saved table orders!"
                    data-position="top"></i> <?php echo _l('save_last_order_for_tables'); ?>
            </label>
            <div class="radio radio-primary radio-inline">
                <input type="radio" id="y_opt_1_save_last_order_for_tables" name="settings[save_last_order_for_tables]"
                    value="1" <?php if (get_option('save_last_order_for_tables') == '1') {
    echo ' checked';
} ?>>
                <label for="y_opt_1_save_last_order_for_tables">
                    <?php echo _l('settings_yes'); ?>
                </label>
            </div>
            <div class="radio radio-primary radio-inline">
                <input type="radio" id="y_opt_2_save_last_order_for_tables" name="settings[save_last_order_for_tables]"
                    value="0" <?php if (get_option('save_last_order_for_tables') == '0') {
    echo ' checked';
} ?>>
                <label for="y_opt_2_save_last_order_for_tables">
                    <?php echo _l('settings_no'); ?>
                </label>
            </div>
        </div>
        <hr />

        <div class="form-group">
            <label><?php echo _l('show_table_export_button'); ?></label><br />
            <div class="radio radio-primary">
                <input type="radio" id="stbxb_all" name="settings[show_table_export_button]" value="to_all" <?php if (get_option('show_table_export_button') == 'to_all') {
    echo ' checked';
} ?>>
                <label for="stbxb_all"><?php echo _l('show_table_export_all'); ?></label>
            </div>

            <div class="radio radio-primary">
                <input type="radio" id="stbxb_admins" name="settings[show_table_export_button]" value="only_admins" <?php if (get_option('show_table_export_button') == 'only_admins') {
    echo ' checked';
} ?>>
                <label for="stbxb_admins"><?php echo _l('show_table_export_admins'); ?></label>

            </div>
            <div class="radio radio-primary">
                <input type="radio" id="stbxb_hide" name="settings[show_table_export_button]" value="hide" <?php if (get_option('show_table_export_button') == 'hide') {
    echo ' checked';
} ?>>
                <label for="stbxb_hide"><?php echo _l('show_table_export_hide'); ?></label>
            </div>
        </div>
        <hr />
        <?php echo render_input('settings[tables_pagination_limit]', 'settings_general_tables_limit', get_option('tables_pagination_limit'), 'number'); ?>
        <hr />
    </div>
    <div role="tabpanel" class="tab-pane" id="inline_create">
        <?php echo render_yes_no_option('staff_members_create_inline_lead_status', _l('inline_create_option', [
        '<b>' . _l('lead_status') . '</b>',
        '<b>' . _l('lead') . '</b>',
      ])); ?>
        <hr />
        <?php echo render_yes_no_option('staff_members_create_inline_lead_source', _l('inline_create_option', [
        '<b>' . _l('lead_source') . '</b>',
        '<b>' . _l('lead') . '</b>',
      ])); ?>
        <hr />
        <?php echo render_yes_no_option('staff_members_create_inline_customer_groups', _l('inline_create_option', [
        '<b>' . _l('customer_group') . '</b>',
        '<b>' . _l('client') . '</b>',
      ])); ?>
        <hr />
        <?php if (get_option('services') == 1) { ?>
        <?php echo render_yes_no_option('staff_members_create_inline_ticket_services', _l('inline_create_option', [
        '<b>' . _l('service') . '</b>',
        '<b>' . _l('ticket') . '</b>',
      ])); ?>
        <hr />
        <?php } ?>
        <?php echo render_yes_no_option('staff_members_save_tickets_predefined_replies', _l('inline_create_option_predefined_replies')); ?>
        <hr />
        <?php echo render_yes_no_option('staff_members_create_inline_contract_types', _l('inline_create_option', [
      '<b>' . _l('contract_type') . '</b>',
      '<b>' . _l('contract') . '</b>',
    ])); ?>
        <hr />
        <?php echo render_yes_no_option('staff_members_create_inline_expense_categories', _l('inline_create_option', [
      '<b>' . _l('expense_category') . '</b>',
      '<b>' . _l('expense') . '</b>',
    ])); ?>
    </div>
