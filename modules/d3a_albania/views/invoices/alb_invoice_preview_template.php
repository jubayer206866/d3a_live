<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$alb_invoice = isset($alb_invoice) ? $alb_invoice : null;
$invoice = $alb_invoice;
$payment = isset($payment) ? $payment : [];
$payment_modes = isset($payment_modes) ? $payment_modes : [];
$members = isset($members) ? $members : [];
$activity = isset($activity) ? $activity : [];
$totalNotes = isset($totalNotes) ? $totalNotes : 0;
$total_left_to_pay = function_exists('alb_invoice_left_to_pay') && $alb_invoice ? alb_invoice_left_to_pay($alb_invoice->id) : 0;
$_tooltip = _l('invoice_sent_to_email_tooltip');
$_tooltip_already_send = '';
if (isset($alb_invoice->sent) && $alb_invoice->sent == 1 && !empty($alb_invoice->datesend)) {
    $_tooltip_already_send = _l('invoice_already_send_to_client_tooltip', time_ago($alb_invoice->datesend));
}
?>
<div class="col-md-12 no-padding">
    <div class="panel_s">
        <div class="panel-body">
            <div class="horizontal-scrollable-tabs preview-tabs-top panel-full-width-tabs">
                <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                <div class="horizontal-tabs">
                    <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#tab_alb_invoice" aria-controls="tab_alb_invoice" role="tab" data-toggle="tab">
                                <?= _l('invoice'); ?>
                            </a>
                        </li>
                        <?php if (count($payment) > 0) { ?>
                        <li role="presentation">
                            <a href="#invoice_payments_received" aria-controls="invoice_payments_received" role="tab" data-toggle="tab">
                                <?= _l('payments'); ?>
                                <span class="badge"><?= count($payment); ?></span>
                            </a>
                        </li>
                        <?php } ?>
                        <li role="presentation">
                            <a href="#tab_activity" aria-controls="tab_activity" role="tab" data-toggle="tab">
                                <?= _l('invoice_view_activity_tooltip'); ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#tab_reminders" onclick="initDataTable('#table-reminders-alb', admin_url + 'misc/get_reminders/' + <?= (int)$alb_invoice->id; ?> + '/' + 'alb_invoice', undefined, undefined, undefined, [1,'asc']); return false;" aria-controls="tab_reminders" role="tab" data-toggle="tab">
                                <?= _l('estimate_reminders'); ?>
                                <?php
                                $total_reminders = total_rows(db_prefix() . 'reminders', ['isnotified' => 0, 'staff' => get_staff_user_id(), 'rel_type' => 'alb_invoice', 'rel_id' => $alb_invoice->id]);
                                if ($total_reminders > 0) {
                                    echo '<span class="badge">' . $total_reminders . '</span>';
                                }
                                ?>
                            </a>
                        </li>
                        <li role="presentation" class="tab-separator">
                            <a href="#tab_notes" onclick="get_sales_notes(<?= (int)$alb_invoice->id; ?>,'d3a_albania'); return false" aria-controls="tab_notes" role="tab" data-toggle="tab">
                                <?= _l('estimate_notes'); ?>
                                <span class="notes-total"><?php if ($totalNotes > 0) { ?><span class="badge"><?= $totalNotes; ?></span><?php } ?></span>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip" data-title="<?= _l('toggle_full_view'); ?>" class="tab-separator toggle_view">
                            <a href="#" onclick="small_table_full_view(); return false;"><i class="fa fa-expand"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row mtop20">
                <div class="col-md-3">
                    <?= format_invoice_status($alb_invoice->status, 'mtop5 inline-block'); ?>
                </div>
                <div class="col-md-9 _buttons">
                    <div class="visible-xs"><div class="mtop10"></div></div>
                    <div class="pull-right">
                        <?php if (is_admin() || staff_can('edit_al_invoice', 'd3a_albania')) { ?>
                        <a href="<?= admin_url('d3a_albania/invoice/' . $alb_invoice->id); ?>" data-toggle="tooltip" title="<?= _l('edit_invoice_tooltip'); ?>" class="btn btn-default btn-with-tooltip sm:!tw-px-3" data-placement="bottom"><i class="fa-regular fa-pen-to-square"></i></a>
                        <?php } ?>
                        <div class="btn-group">
                            <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa-regular fa-file-pdf"></i><?php if (is_mobile()) { echo ' PDF'; } ?> <span class="caret"></span></a>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li class="hidden-xs"><a href="<?= admin_url('d3a_albania/alb_invoice_pdf/' . $alb_invoice->id . '?output_type=I'); ?>"><?= _l('view_pdf'); ?></a></li>
                                <li class="hidden-xs"><a href="<?= admin_url('d3a_albania/alb_invoice_pdf/' . $alb_invoice->id . '?output_type=I'); ?>" target="_blank"><?= _l('view_pdf_in_new_window'); ?></a></li>
                                <li><a href="<?= admin_url('d3a_albania/alb_invoice_pdf/' . $alb_invoice->id); ?>"><?= _l('download'); ?></a></li>
                                <li><a href="<?= admin_url('d3a_albania/alb_invoice_pdf/' . $alb_invoice->id . '?print=true'); ?>" target="_blank"><?= _l('print'); ?></a></li>
                            </ul>
                        </div>
                        <?php if (!empty($alb_invoice->clientid)) { ?>
                        <span<?php if ($alb_invoice->status == 5) { ?> data-toggle="tooltip" data-title="<?= _l('invoice_cancelled_email_disabled'); ?>"<?php } ?>>
                            <a href="#" class="alb-invoice-send-to-client btn-with-tooltip sm:!tw-px-3 btn btn-default<?php if ($alb_invoice->status == 5) { echo ' disabled'; } ?>" data-toggle="tooltip" title="<?= e($_tooltip); ?>" data-placement="bottom"><span data-toggle="tooltip" data-title="<?= e($_tooltip_already_send); ?>"><i class="fa-regular fa-envelope"></i></span></a>
                        </span>
                        <?php } ?>
                        <?php if ($total_left_to_pay > 0 && (is_admin() || staff_can('add_al_payment', 'd3a_albania')) && !empty($payment_modes) && $alb_invoice->status != 5) { ?>
                        <a href="#" onclick="add_alb_payment(<?= (int)$alb_invoice->id; ?>); return false;" class="mleft10 btn btn-success"><i class="fa fa-plus-square"></i> <?= _l('payment'); ?></a>
                        <?php } ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default pull-left dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?= _l('more'); ?> <span class="caret"></span></button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <?php if (!empty($alb_invoice->hash) && (is_admin() || staff_can('preview_al_invoice', 'd3a_albania'))) { ?>
                                <li><a href="<?= site_url('alb_invoice/' . $alb_invoice->id . '/' . $alb_invoice->hash); ?>" target="_blank"><?= _l('view_invoice_as_customer_tooltip'); ?></a></li>
                                <?php } ?>
                                <?php if (is_admin() || staff_can('delete_al_invoice', 'd3a_albania')) { ?>
                                <li class="divider"></li>
                                <li>
                                    <a href="<?= admin_url('d3a_albania/delete_alb_invoice/' . $alb_invoice->id); ?>" class="text-danger delete-text _delete" data-toggle="tooltip" data-title="<?= _l('delete_invoice_tooltip'); ?>"><?= _l('delete_invoice'); ?></a>
                                </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <hr class="hr-panel-separator" />
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="tab_alb_invoice">
                    <?php $this->load->view('d3a_albania/invoices/alb_invoice_preview_html', isset($data) ? $data : []); ?>
                </div>
                <?php if (count($payment) > 0) { ?>
                <div class="tab-pane" role="tabpanel" id="invoice_payments_received">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?= _l('payments_table_amount_heading'); ?></th>
                                <th><?= _l('payments_table_mode_heading'); ?></th>
                                <!-- <th><?= _l('payment_transaction_id'); ?></th> -->
                                <th><?= _l('payments_table_date_heading'); ?></th>
                                <th><?= _l('options'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment as $pay) {
                                $pay = (object)$pay;
                            ?>
                            <tr>
                                <td><?= app_format_money($pay->amount ?? 0, $base_currency); ?></td>
                                <td><?= get_payment_mode_by_id($pay->paymentmode ?? 0); ?></td>
                                <!-- <td><?= e($pay->transactionid ?? ''); ?></td> -->
                                <td><?= _d($pay->date ?? ''); ?></td>
                                <td>
                                    <?php if ((is_admin() || staff_can('delete_al_payment', 'd3a_albania')) && $pay->id ?? 0) { ?>
                                    <a href="<?= admin_url('d3a_albania/delete_alb_invoice_payment/' . ($pay->id ?? '') . '/' . $alb_invoice->id); ?>" class="btn btn-danger btn-icon btn-sm _delete" data-toggle="tooltip" title="<?= _l('delete'); ?>"><i class="fa fa-remove"></i></a>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php } ?>
                <div role="tabpanel" class="tab-pane" id="tab_reminders">
                    <a href="#" class="btn btn-primary" data-toggle="modal" data-target=".reminder-modal-alb_invoice-<?= (int)$alb_invoice->id; ?>"><i class="fa-regular fa-bell"></i> <?= _l('invoice_set_reminder_title'); ?></a>
                    <hr />
                    <?php render_datatable([_l('reminder_description'), _l('reminder_date'), _l('reminder_staff'), _l('reminder_is_notified')], 'reminders', [], ['id' => 'table-reminders-alb']); ?>
                    <?php $this->load->view('admin/includes/modals/reminder', ['id' => $alb_invoice->id, 'name' => 'alb_invoice', 'members' => $members, 'reminder_title' => _l('invoice_set_reminder_title')]); ?>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab_notes">
                    <?php if (is_admin() || staff_can('edit_al_invoice', 'd3a_albania')) { ?>
                    <?= form_open(admin_url('d3a_albania/add_note_alb_invoice/' . $alb_invoice->id), ['id' => 'sales-notes', 'class' => 'alb-invoice-notes-form']); ?>
                    <?= render_textarea('description'); ?>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary mtop15 mbot15"><?= _l('estimate_add_note'); ?></button>
                    </div>
                    <?= form_close(); ?>
                    <?php } ?>
                    <hr />
                    <div class="mtop20" id="sales_notes_area"></div>
                </div>
                <div role="tabpanel" class="tab-pane ptop10" id="tab_activity">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="activity-feed">
                                <?php foreach ($activity as $act) { ?>
                                <div class="feed-item">
                                    <div class="date"><span class="text-has-action" data-toggle="tooltip" data-title="<?= e(_dt($act['date'] ?? '')); ?>"><?= e(time_ago($act['date'] ?? '')); ?></span></div>
                                    <div class="text">
                                        <?php if (!empty($act['staffid']) && $act['staffid'] != 0) { ?>
                                        <a href="<?= admin_url('profile/' . $act['staffid']); ?>"><?= staff_profile_image($act['staffid'], ['staff-profile-xs-image pull-left mright5']); ?></a>
                                        <?php } ?>
                                        <?= _l($act['description'] ?? '', isset($act['additional_data']) ? unserialize($act['additional_data']) : []); ?>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($alb_invoice->clientid) && isset($template)) { ?>
<?php $this->load->view('d3a_albania/invoices/alb_invoice_send_to_client', [
    'alb_invoice' => $alb_invoice,
    'template' => $template,
    'template_name' => isset($template_name) ? $template_name : 'invoice-send-to-client',
    'template_disabled' => isset($template_disabled) ? $template_disabled : false,
    'template_id' => isset($template_id) ? $template_id : 0,
    'template_system_name' => isset($template_system_name) ? $template_system_name : ''
]); ?>
<?php } ?>
<?php if ($total_left_to_pay > 0 && (is_admin() || staff_can('add_al_payment', 'd3a_albania')) && !empty($payment_modes)) { ?>
<div class="modal fade" id="payment_record_alb" tabindex="-1" role="dialog">
    <div class="modal-dialog dialog_30">
        <?= form_open(admin_url('d3a_albania/add_alb_invoice_payment/' . $alb_invoice->id), ['id' => 'albinvoice-add_payment-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?= _l('new_payment'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?= render_input('amount', 'amount', $total_left_to_pay, 'number', ['max' => $total_left_to_pay]); ?>
                        <?= render_date_input('date', 'payment_edit_date'); ?>
                        <?= render_select('paymentmode', $payment_modes, ['id', 'name'], 'payment_mode'); ?>
                        <!-- <?= render_input('transactionid', 'payment_transaction_id'); ?> -->
                        <?= render_textarea('note', 'note', '', ['rows' => 7]); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" class="btn btn-info"><?= _l('submit'); ?></button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>
<?php } ?>
<script>
    init_btn_with_tooltips();
    init_datepicker();
    init_selectpicker();
    init_tabs_scrollable();
    init_form_reminder();
</script>
