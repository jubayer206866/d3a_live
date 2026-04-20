<?php defined('BASEPATH') or exit('No direct script access allowed'); 
// Calculate summary values from statements
$invoiced_amount = 0;
$amount_paid = 0;
$total_balance = 0;
$currency = '¥'; // Default currency

if (!empty($statements)) {
    foreach ($statements as $row) {
        $invoiced_amount += floatval($row['invoice_amount']);
        $amount_paid += floatval($row['invoice_total']);
        $total_balance += floatval($row['balance']);
        if (empty($currency) && !empty($row['currency_name'])) {
            $currency = $row['currency_name'];
        }
    }
}
$balance_due = $total_balance;
?>
<div class="col-md-5">
    <div class="text-right">
        <h4 class="tw-my-0 tw-font-semibold"><?php echo _l('account_summary'); ?></h4>
        <p class="text-muted"><?php echo e(_l('statement_from_to', [$from, $to])); ?></p>
        <hr />
        <table class="table statement-account-summary">
            <tbody>
                <tr>
                    <td class="text-left"><?php echo _l('statement_beginning_balance'); ?>:</td>
                    <td><?php echo e(app_format_money(0, $currency)); ?></td>
                </tr>
                <tr>
                    <td class="text-left"><?php echo _l('invoiced_amount'); ?>:</td>
                    <td><?php echo e(app_format_money($invoiced_amount, $currency)); ?></td>
                </tr>
                <tr>
                    <td class="text-left"><?php echo _l('amount_paid'); ?>:</td>
                    <td><?php echo e(app_format_money($amount_paid, $currency)); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="text-left"><b><?php echo _l('balance_due'); ?></b>:</td>
                    <td><?php echo e(app_format_money($balance_due, $currency)); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<div class="col-md-12">
    <div class="text-center bold padding-10">
        <?php echo _l('customer_statement_info', [$from, $to]); ?>
    </div>
    <div class="table-responsive">
        <table class="table dt-table table-statement-report" data-order-col="0" data-order-type="desc">
            <thead>
                <tr>
                    <th><?= _l('date'); ?></th>
                    <th><?= _l('Details'); ?></th>
                    <th><?= _l('Products Value'); ?></th>
                    <th><?= _l('Service Fee'); ?></th>
                    <th><?= _l('Other Expenses'); ?></th>
                    <th><?= _l('Total'); ?></th>
                    <th><?= _l('Invoice Amount'); ?></th>
                    <th><?= _l('RMB Received'); ?></th>
                    <th><?= _l('Balance'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statements as $row): 
                    $date_for_js = date('Y-m-d', strtotime($row['start_date']));
                ?>
                <tr class="statement-row" data-date="<?= $date_for_js; ?>">
                    <td data-order="<?= $date_for_js; ?>"><?= _d($row['start_date']); ?></td>
                    <td><?= e($row['project_name']); ?></td>
                    <td><?= app_format_money($row['total_goods_value'], '¥'); ?></td>
                    <td><?= app_format_money($row['service_fee'], '¥'); ?></td>
                    <td><?= app_format_money($row['others_expenses'], '¥'); ?></td>
                    <td><?= app_format_money($row['total'], '¥'); ?></td>
                    <td><?= app_format_money($row['invoice_amount'], $row['currency_name']); ?></td>
                    <td><?= app_format_money($row['invoice_total'], '¥'); ?></td>
                    <td><?= app_format_money($row['balance'], '¥'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>