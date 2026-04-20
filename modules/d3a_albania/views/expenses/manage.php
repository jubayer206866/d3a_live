<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-bold tw-text-xl tw-mb-3">
                    <?= _l('expenses'); ?>
                </h4>
                <div id="stats-top" class="tw-mb-6 hide">
                    <div id="expenses_total" class="empty:tw-min-h-[61px]"></div>
                </div>
                <div class="tw-mb-2">
                    <div class="_buttons sm:tw-space-x-1 rtl:sm:tw-space-x-reverse">
                        <?php if (is_admin() || staff_can('create_al_expense', 'd3a_albania')) { ?>
                            <a href="<?= admin_url('d3a_albania/expense'); ?>"
                                class="btn btn-primary">
                                <i class="fa-regular fa-plus"></i>
                                <?= _l('new_expense'); ?>
                            </a>
                            <a href="<?= admin_url('d3a_albania/expense'); ?>"
                                class="hidden-xs btn btn-default ">
                                <i class="fa-solid fa-upload tw-mr-1"></i>
                                <?= _l('import_expenses'); ?>
                            </a>
                        <?php } ?>
                        <?php if (staff_can('view', 'bulk_pdf_exporter')) { ?>
                            <a href="<?= admin_url('utilities/bulk_pdf_exporter?feature=expenses'); ?>"
                                data-toggle="tooltip"
                                title="<?= _l('bulk_pdf_exporter'); ?>"
                                class="btn-with-tooltip btn btn-default !tw-px-3">
                                <i class="fa-regular fa-file-pdf"></i>
                            </a>
                        <?php } ?>
                        <div class="tw-inline-block tw-ml-4">
                            <label class="tw-block tw-text-xs tw-font-medium">Category Type</label>
                            <select id="filter_type" class="selectpicker" data-width="300px">
                                <option value=""></option>
                                <option value="operational">Operational</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>

                        <div class="tw-inline-block tw-ml-3">
                            <label class="tw-block tw-text-xs tw-font-medium">Expense Category</label>
                            <select id="filter_category" class="selectpicker" data-width="300px" data-live-search="true" multiple>
                                <option value=""></option>
                                <?php foreach ($categories as $category) { ?>
                                    <option value="<?= $category['id']; ?>">
                                        <?= $category['name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="tw-inline-block tw-ml-3">
                            <label class="tw-block tw-text-xs tw-font-medium">Customer</label>
                            <select id="filter_customer" class="selectpicker" data-width="300px" data-live-search="true" multiple>
                                <option value=""></option>
                                <?php foreach ($customers as $customer) { ?>
                                    <option value="<?= $customer['userid']; ?>">
                                        <?= $customer['company']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="clearfix"></div>
                        <?php
                        $hasPermission = is_admin() || staff_can('edit_al_expense', 'd3a_albania') || staff_can('delete_al_expense', 'd3a_albania');
                        ?>
                        <?php if ($hasPermission) { ?>
                            <a href="#" data-toggle="modal" data-target="#expenses_bulk_actions" class="hide bulk-actions-btn table-btn"
                                data-table=".table-alb-expenses">
                                <?= _l('bulk_actions'); ?>
                            </a>
                        <?php } ?>
                        <?php
                        $table_data = [
                            [
                                'name'     => '<span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="alb-expenses"><label></label></div>',
                                'th_attrs' => ['class' => $hasPermission ? '' : 'not_visible'],
                            ],
                            _l('the_number_sign'),
                            _l('Category Type'),
                            _l('Expense Category'),
                            _l('expense_dt_table_heading_amount'),
                            _l('expense_dt_table_heading_date'),
                            // _l('project'),
                        ];

                        $custom_fields = get_custom_fields('alb_expenses', ['show_on_table' => 1]);

                        foreach ($custom_fields as $field) {
                            array_push($table_data, [
                                'name'     => $field['name'],
                                'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
                            ]);
                        }

                        $table_data = hooks()->apply_filters('alb_expenses_table_columns', $table_data);
                        render_datatable($table_data, 'alb-expenses', [], [
                            'data-last-order-identifier' => 'alb-expenses',
                            'data-default-order'         => get_table_last_order('alb-expenses'),
                            'id'                         => 'table-alb-expenses',
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="expenses_bulk_actions" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <?= _l('bulk_actions'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="radio radio-primary">
                    <input type="radio" id="bulk_delete" name="bulk_action" value="delete" checked>
                    <label for="bulk_delete">
                        <?= _l('bulk_action_delete'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?= _l('close'); ?>
                </button>
                <button type="submit" class="btn btn-primary" id="bulk_action_confirm">
                    <?= _l('confirm'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    var hidden_columns = [4, 5, 6, 7, 8, 9];
</script>
<?php init_tail(); ?>
<script>
    Dropzone.autoDiscover = false;

    $(function() {

        $('#filter_category').selectpicker({
            liveSearch: true,
            countSelectedText: '{0} categories selected',
            multipleSeparator: ', '
        });

        $('#filter_customer').selectpicker({
            liveSearch: true,
            countSelectedText: '{0} customers selected',
            multipleSeparator: ', '
        });

        var table = initDataTable(
            '.table-alb-expenses',
            admin_url + 'd3a_albania/expenses_table',
            [0],
            [0], {
                filter_type: '#filter_type',
                filter_category: '#filter_category',
                filter_customer: '#filter_customer'
            },
            <?= hooks()->apply_filters('alb_expenses_table_default_order', json_encode([5, 'desc'])); ?>
        );

        $('#filter_type').on('change', function() {
            table.ajax.reload();
        });

        $('#filter_category, #filter_customer').on('change', function() {
            $(this).selectpicker('refresh');
            table.ajax.reload();
        });

    });
</script>
</body>

</html>