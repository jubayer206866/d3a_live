<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h4><?= _l('expense'); ?> #<?= $expense->id; ?></h4>
    </div>
    <div class="panel-body">

        <p><strong><?= _l('expense_category'); ?>:</strong>
            <?= htmlspecialchars($category ? $category->name : '-'); ?>
        </p>

        <p><strong><?= _l('invoice'); ?>:</strong>
            <?php if ($expense->invoiceid) { ?>
                <?= format_invoice_number($expense->invoiceid); ?>
            <?php } else { ?>
                <span class="text-danger"><?= _l('expense_list_unbilled'); ?></span>
            <?php } ?>
        </p>

        <p><strong><?= _l('expense_name'); ?>:</strong>
            <?= htmlspecialchars($expense->expense_name); ?>
        </p>

        <p><strong><?= _l('created_by'); ?>:</strong>
            <?= get_staff_full_name($expense->addedfrom); ?>
        </p>

        <p><strong><?= _l('amount'); ?>:</strong>
            <?= app_format_money($expense->amount, get_currency($expense->currency)); ?>
        </p>

        <p><strong><?= _l('date'); ?>:</strong> <?= _d($expense->date); ?></p>

        <p><strong><?= _l('customer'); ?>:</strong>
            <?php if (!empty($expense->clientid)) { ?>
                <?= get_company_name($expense->clientid); ?>
            <?php } else { ?>
                -
            <?php } ?>
        </p>

        <p><strong><?= _l('project'); ?>:</strong>
            <?php if (!empty($expense->project_id)) { ?>
                <?= get_project_name_by_id($expense->project_id); ?>
            <?php } else { ?>
                -
            <?php } ?>
        </p>

        <p><strong><?= _l('note'); ?>:</strong><br>
            <?= nl2br(htmlspecialchars($expense->note)); ?>
        </p>

        <?php if (!empty($expense->file_name)) { ?>
            <p><strong><?= _l('expense_receipt'); ?>:</strong><br>
                <a href="<?= site_url('download/file/expense/' . $expense->id); ?>" target="_blank">
                    <?= htmlspecialchars($expense->file_name); ?>
                </a>
            </p>
        <?php } ?>

    </div>
</div>
