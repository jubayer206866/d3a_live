<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel-body">
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Name</th>
                    <th>Receipt</th>
                    <th>Date</th>
                    <th>Invoice</th>
                    <th>Reference #</th>
                    <th>Payment Mode</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($expenses) > 0) { ?>
                    <?php foreach ($expenses as $exp) { ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($exp['category_name']); ?>
                                <br>
                                <a href="<?= site_url('Clients/view_expenses/' . $exp['id']); ?>" class="btn btn-xs" style="margin-top:5px;">
                                    <?= _l('view'); ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $total     = $exp['amount'];
                                $tmp_total = $total;
                                if ($exp['tax'] != 0) {
                                    $_tax = get_tax_by_id($exp['tax']);
                                    $total += ($total / 100 * $_tax->taxrate);
                                }
                                if ($exp['tax2'] != 0) {
                                    $_tax = get_tax_by_id($exp['tax2']);
                                    $total += ($tmp_total / 100 * $_tax->taxrate);
                                }
                                echo app_format_money($total, get_currency($exp['currency']));
                                ?>
                            </td>
                            <td><?= htmlspecialchars($exp['expense_name']); ?></td>
                            <td>
                                <?php if (!empty($exp['file_name'])) { ?>
                                    <a href="<?= site_url('download/file/expense/' . $exp['id']); ?>" target="_blank">
                                        <?= htmlspecialchars($exp['file_name']); ?>
                                    </a>
                                <?php } else {
                                    echo '-';
                                } ?>
                            </td>
                            <td><?= _d($exp['date']); ?></td>
                            <td>
                                <?php if ($exp['invoiceid']) { ?>
                                    <?= format_invoice_number($exp['invoiceid']); ?>
                                <?php } else {
                                    echo '-';
                                } ?>
                            </td>
                            <td><?= htmlspecialchars($exp['reference_no']); ?></td>
                            <td>
                                <?php
                                if ($exp['paymentmode'] != '0' && !empty($exp['paymentmode'])) {
                                    $pm = $this->payment_modes_model->get($exp['paymentmode']);
                                    echo e($pm ? $pm->name : '-');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8" class="text-center"><?= _l('no_data_found'); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
