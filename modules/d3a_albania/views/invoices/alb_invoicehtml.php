<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$base_currency    = isset($base_currency) ? $base_currency : null;
$invoice_number   = format_alb_invoice_number_custom($invoice);
$invoice_total    = isset($invoice->invoice_amount) ? (float) $invoice->invoice_amount : (isset($invoice->total) ? (float) $invoice->total : 0);
$total_left_to_pay = function_exists('alb_invoice_left_to_pay') && $invoice ? alb_invoice_left_to_pay($invoice->id) : $invoice_total;
$items            = get_items_table_data($invoice, 'alb_invoice', 'html', false);
?>
<div class="mtop15 preview-top-wrapper">
    <div class="row">
        <div class="col-md-3">
            <div class="mbot30">
                <div class="invoice-html-logo">
                    <?php
                    $alb_path = get_upload_path_by_type('alb_company');
                    $alb_logo = get_option('alb_company_logo_dark') ?: get_option('alb_company_logo');
                    if ($alb_logo && file_exists($alb_path . $alb_logo)) {
                        echo '<img src="' . base_url('uploads/alb_company/' . $alb_logo) . '" alt="" style="max-height:60px;">';
                    } else {
                        echo get_dark_company_logo();
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="top" data-sticky data-sticky-class="preview-sticky-header">
        <div class="container preview-sticky-container">
            <div class="sm:tw-flex tw-justify-between -tw-mx-4">
                <div class="sm:tw-self-end">
                    <h3 class="bold tw-my-0 invoice-html-number">
                        <span class="sticky-visible hide tw-mb-2"><?= e($invoice_number); ?></span>
                    </h3>
                    <span class="invoice-html-status">
                        <?= format_invoice_status($invoice->status, '', true); ?>
                    </span>
                </div>
                <div class="tw-flex tw-items-end tw-space-x-2 tw-mt-3 sm:tw-mt-0">
                    <?php if (is_client_logged_in() && function_exists('has_contact_permission') && has_contact_permission('invoices')) { ?>
                    <a href="<?= site_url('clients/invoices/'); ?>" class="btn btn-default action-button go-to-portal">
                        <?= _l('client_go_to_dashboard'); ?>
                    </a>
                    <?php } ?>
                    <?= form_open($this->uri->uri_string()); ?>
                    <button type="submit" name="invoicepdf" value="invoicepdf" class="btn btn-default action-button">
                        <i class='fa-regular fa-file-pdf'></i>
                        <?= _l('clients_invoice_html_btn_download'); ?>
                    </button>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="clearfix"></div>

<div class="panel_s tw-mt-6">
    <div class="panel-body">
        <?php if (!empty($invoice->duedate) && strtotime($invoice->duedate) < time() && $invoice->status != 2 && $invoice->status != 5) {
            $days_overdue = floor((time() - strtotime($invoice->duedate)) / 86400);
        ?>
        <div class="col-md-10 col-md-offset-1 tw-mb-5">
            <div class="alert alert-danger text-center">
                <p class="tw-font-medium"><?= e(_l('overdue_by_days', $days_overdue)); ?></p>
            </div>
        </div>
        <?php } ?>
        <div class="col-md-10 col-md-offset-1">
            <div class="row mtop20">
                <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
                    <h4 class="tw-font-semibold tw-text-neutral-700 invoice-html-number"><?= e($invoice_number); ?></h4>
                    <address class="invoice-html-company-info tw-text-neutral-500 tw-text-normal">
                        <?= function_exists('format_alb_organization_info') ? format_alb_organization_info() : format_organization_info(); ?>
                    </address>
                </div>
                <div class="col-sm-6 text-right transaction-html-info-col-right">
                    <span class="tw-font-medium tw-text-neutral-700 invoice-html-bill-to"><?= _l('invoice_bill_to'); ?></span>
                    <address class="invoice-html-customer-billing-info tw-text-neutral-500 tw-text-normal">
                        <?= format_customer_info($invoice, 'invoice', 'billing'); ?>
                    </address>
                    <p class="invoice-html-date tw-mb-0 tw-text-normal">
                        <span class="tw-font-medium tw-text-neutral-700"><?= _l('invoice_data_date'); ?></span>
                        <?= e(_d($invoice->date)); ?>
                    </p>
                    <?php if (!empty($invoice->duedate)) { ?>
                    <p class="invoice-html-duedate tw-mb-0 tw-text-normal">
                        <span class="tw-font-medium tw-text-neutral-700"><?= _l('invoice_data_duedate'); ?></span>
                        <?= e(_d($invoice->duedate)); ?>
                    </p>
                    <?php } ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <?= $items->table(); ?>
                    </div>
                </div>
                <div class="col-md-6 col-md-offset-6">
                    <table class="table text-right tw-text-normal">
                        <tbody>
                            <tr>
                                <td><span class="bold tw-text-neutral-700"><?= _l('invoice_total'); ?></span></td>
                                <td class="total"><?= e(app_format_money($invoice_total, $base_currency)); ?></td>
                            </tr>
                            <?php if (get_option('show_amount_due_on_invoice') == 1 && $invoice->status != 5) { ?>
                            <tr>
                                <td>
                                    <span class="<?= $total_left_to_pay > 0 ? 'text-danger ' : ''; ?>bold">
                                        <?= _l('invoice_amount_due'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?= $total_left_to_pay > 0 ? 'text-danger' : ''; ?>">
                                        <?= e(app_format_money($total_left_to_pay, $base_currency)); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php if (get_option('total_to_words_enabled') == 1 && class_exists('app_number_to_word')) {
                    $this->load->library('app_number_to_word', ['clientid' => $invoice->clientid], 'numberword');
                ?>
                <div class="col-md-12 text-center invoice-html-total-to-words">
                    <p class="tw-font-medium"><?= _l('num_word'); ?>: <span class="tw-text-neutral-500"><?= $this->numberword->convert($invoice_total, $base_currency ? $base_currency->name : ''); ?></span></p>
                </div>
                <?php } ?>
                <?php
                $bank_info = get_option('alb_bank_information');
                if (empty($bank_info)) {
                    $bank_info = !empty($invoice->terms) ? $invoice->terms : (!empty($invoice->clientnote) ? $invoice->clientnote : '');
                }
                if (!empty($bank_info)) {
                    $bank_info = clear_textarea_breaks($bank_info, "\n");
                    $bank_info = _info_format_replace('company_name', get_option('alb_invoice_company_name') ?: '', $bank_info);
                ?>
                <div class="col-md-12 invoice-html-terms-and-conditions">
                    <hr />
                    <p><b><?= _l('alb_bank_information'); ?></b></p>
                    <div class="tw-text-neutral-500 tw-mt-2.5" style="white-space: pre-line;"><?= e($bank_info); ?></div>
                </div>
                <?php } ?>
                <?php if (count($invoice->payments ?? []) > 0) { ?>
                <div class="col-md-12 invoice-html-payments">
                    <hr />
                    <p><b><?= _l('invoice_received_payments'); ?></b></p>
                    <table class="table table-hover invoice-payments-table tw-mt-2.5">
                        <thead>
                            <tr>
                                <th><?= _l('invoice_payments_table_number_heading'); ?></th>
                                <th><?= _l('invoice_payments_table_mode_heading'); ?></th>
                                <th><?= _l('invoice_payments_table_date_heading'); ?></th>
                                <th><?= _l('invoice_payments_table_amount_heading'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoice->payments as $payment) { ?>
                            <tr>
                                <td><?= e($payment->id ?? ''); ?></td>
                                <td><?= e(get_payment_mode_by_id($payment->paymentmode ?? 0)); ?></td>
                                <td><?= e(_d($payment->date ?? '')); ?></td>
                                <td><?= e(app_format_money($payment->amount ?? 0, $base_currency)); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php if (file_exists(FCPATH . 'assets/plugins/sticky/sticky.js')) { ?>
<script src="<?= site_url('assets/plugins/sticky/sticky.js'); ?>"></script>
<?php } ?>
<script>
    $(function() {
        if (typeof Sticky !== 'undefined') {
            new Sticky('[data-sticky]');
        }
    });
</script>
