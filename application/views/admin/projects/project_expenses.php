<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="expenses_total" class="tw-mb-4 hide"></div>
<?php if (staff_can('create', 'expenses')) { ?>
<a href="#" data-toggle="modal" data-target="#new_project_expense" class="btn btn-primary tw-mb-4">
    <i class="fa-regular fa-plus tw-mr-1"></i>
    <?= _l('new_expense'); ?>
</a>
<?php } ?>

<div id="vueApp" class="tw-inline pull-right tw-ml-0 sm:tw-ml-1.5">
    <app-filters id="<?= $expenses_table->id(); ?>"
        view="<?= $expenses_table->viewName(); ?>"
        :saved-filters="<?= $expenses_table->filtersJs(); ?>"
        :available-rules="<?= $expenses_table->rulesJs(); ?>">
    </app-filters>
</div>

<div class="clearfix"></div>
<div class="panel_s panel-table-full">
    <div class="panel-body">
        <?= form_hidden('custom_view');
$this->load->view('admin/expenses/table_html', [
    'class'           => 'project-expenses',
    'withBulkActions' => false,
    'table_id'        => 'project_expenses',
]);
?>
    </div>
</div>
<div class="modal fade" id="new_project_expense" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <?= form_open(admin_url('projects/add_expense'), ['id' => 'project-expense-form', 'class' => 'dropzone dropzone-manual']); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <?= e(_l('add_new', _l('expense_lowercase'))); ?>
                </h4>
            </div>
            <div class="modal-body">
                <!-- <?= render_input('expense_name', 'expense_name'); ?> -->
                <?php
                    $this->db->where('category_type', 'customer');
                    $expense_categories = $this->db
                        ->order_by('description', 'ASC')
                        ->get(db_prefix() . 'expenses_categories')
                        ->result_array();

                    $selected = isset($expense) ? $expense->category : '';

                    echo render_select(
                        'category',
                        $expense_categories,
                        ['id', 'name'],
                        'expense_category',
                        $selected,
                        ['data-none-selected-text' => _l('dropdown_non_selected_tex')]
                    );
                    ?>
                <?= render_date_input('date', 'expense_add_edit_date', _d(date('Y-m-d'))); ?>
                <?= render_input('amount', 'expense_add_edit_amount', '', 'number'); ?>
                <?= render_input('amount_2', 'Amount 2', '', 'number'); ?>
                <div class="row mbot15 hide">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label"
                                for="tax"><?= _l('tax_1'); ?></label>
                            <select class="selectpicker display-block" data-width="100%" name="tax"
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                <option value="">
                                    <?= _l('no_tax'); ?>
                                </option>
                                <?php foreach ($taxes as $tax) { ?>
                                <option
                                    value="<?= e($tax['id']); ?>"
                                    data-subtext="<?= e($tax['name']); ?>">
                                    <?= e($tax['taxrate']); ?>%
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label"
                                for="tax2"><?= _l('tax_2'); ?></label>
                            <select class="selectpicker display-block" data-width="100%" name="tax2"
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                disabled>
                                <option value="">
                                    <?= _l('no_tax'); ?>
                                </option>
                                <?php foreach ($taxes as $tax) { ?>
                                <option
                                    value="<?= e($tax['id']); ?>"
                                    data-subtext="<?= e($tax['name']); ?>">
                                    <?= e($tax['taxrate']); ?>%
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="hide">
                    <?= render_select('currency', $currencies, ['id', 'name', 'symbol'], 'expense_currency', $currency->id); ?>
                </div>
                <?php
                $project_id = $project->id;

                $this->db->distinct();
                $this->db->select('clients');
                $this->db->where('project', $project_id);
                $pur_orders_clients = $this->db->get('tblpur_orders')->result_array();

                $customer_expense_data = [];
                foreach ($pur_orders_clients as $client) {
                    $this->db->where('userid', $client['clients']);
                    $client_data = $this->db->get(db_prefix() . 'clients')->row();
                    if ($client_data) {
                        $customer_expense_data[] = [
                            'userid' => $client_data->userid,
                            'company' => $client_data->company,
                        ];
                    }
                }
$selected_client = isset($expense) ? $expense->clientid : '';
echo render_select('clientid', $customer_expense_data, ['userid', 'company'], 'expense_add_edit_customer', $selected_client, ['data-live-search' => 'true']);?>
                <div class="checkbox checkbox-primary hide">
                    <input type="checkbox" id="billable" name="billable" checked>
                    <label
                        for="billable"><?= _l('expense_add_edit_billable'); ?></label>
                </div>
                <!-- <?= render_input('reference_no', 'expense_add_edit_reference_no'); ?> -->
                <?php $selected = (isset($expense) ? $expense->paymentmode : ''); ?>
                <?php
// Fix becuase payment modes are used for invoice filtering and there needs to be shown all
// in case there is payment made with payment mode that was active and now is inactive
$expenses_modes = [];

foreach ($payment_modes as $m) {
    if (isset($m['invoices_only']) && $m['invoices_only'] == 1) {
        continue;
    }
    if ($m['active'] == 1) {
        $expenses_modes[] = $m;
    }
}
?>
                <?= render_select('paymentmode', $expenses_modes, ['id', 'name'], 'payment_mode', $selected); ?>
                <?= render_textarea('note', 'expense_add_edit_note', '', ['rows' => 4], []); ?>
                <div id="dropzoneDragArea" class="dz-default dz-message">
                    <span><?= _l('expense_add_edit_attach_receipt'); ?></span>
                </div>
                <div class="dropzone-previews"></div>
                <div class="clearfix mbot15"></div>
                <?= render_custom_fields('expenses'); ?>
                <?= form_hidden('project_id', $project->id); ?>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default"
                    data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit"
                    class="btn btn-primary"><?= _l('submit'); ?></button>
            </div>
            <?= form_close(); ?>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->