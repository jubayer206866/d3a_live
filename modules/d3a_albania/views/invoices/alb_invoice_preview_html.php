<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$invoice = $alb_invoice;
$base_currency = isset($base_currency) ? $base_currency : null;
$invoice_number = format_alb_invoice_number_custom($invoice);
$invoice_total = isset($invoice->invoice_amount) ? (float) $invoice->invoice_amount : (float) ($invoice->total ?? 0);
$total_left_to_pay = function_exists('alb_invoice_left_to_pay') && $invoice ? alb_invoice_left_to_pay($invoice->id) : $invoice_total;
$items = get_items_table_data($invoice, 'alb_invoice', 'html', true);
?>
<div id="invoice-preview">
    <div class="row">
        <div class="col-md-6 col-sm-6">
            <h4 class="bold">
                <a href="<?= admin_url('d3a_albania/invoice/' . $invoice->id); ?>">
                    <span id="invoice-number"><?= e($invoice_number); ?></span>
                </a>
            </h4>
            <address>
                <?= function_exists('format_alb_organization_info') ? format_alb_organization_info() : format_organization_info(); ?>
            </address>
        </div>
        <div class="col-sm-6 text-right">
            <span class="bold"><?= _l('invoice_bill_to'); ?></span>
            <address class="tw-text-neutral-500">
                <?= format_customer_info($invoice, 'invoice', 'billing', true); ?>
            </address>
            <p class="no-mbot">
                <span class="bold"><?= _l('invoice_data_date'); ?></span>
                <?= e(_d($invoice->date)); ?>
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <?= $items->table(); ?>
            </div>
        </div>
        <div class="col-md-5 col-md-offset-7">
            <table class="table text-right">
                <tbody>
                    <tr id="subtotal">
                        <td>
                            <span class="tw-font-medium tw-text-neutral-700"><?= _l('total'); ?></span>
                        </td>
                        <td class="subtotal"><?= e(app_format_money($invoice_total, $base_currency)); ?></td>
                    </tr>
                    <?php if (get_option('show_amount_due_on_invoice') == 1 && $invoice->status != 5) { ?>
                    <tr>
                        <td>
                            <span class="tw-font-medium<?= $total_left_to_pay > 0 ? ' text-danger' : ''; ?>">
                                <?= _l('invoice_amount_due'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="tw-font-medium<?= $total_left_to_pay > 0 ? ' text-danger' : ''; ?>">
                                <?= e(app_format_money($total_left_to_pay, $base_currency)); ?>
                            </span>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <hr />
    <?php
    $bank_info = get_option('alb_bank_information');
    if (empty($bank_info)) {
        $bank_info = !empty($invoice->terms) ? $invoice->terms : '';
    }
    if (!empty($bank_info)) {
        $bank_info = clear_textarea_breaks($bank_info, "\n");
        $bank_info = _info_format_replace('company_name', get_option('alb_invoice_company_name') ?: '', $bank_info);
    ?>
    <div class="col-md-12 row mtop15">
        <p class="tw-text-neutral-700 tw-font-medium"><?= _l('alb_bank_information'); ?></p>
        <div class="tw-text-neutral-500 tw-leading-relaxed" style="white-space: pre-line;"><?= e($bank_info); ?></div>
    </div>
    <?php } ?>
</div>
