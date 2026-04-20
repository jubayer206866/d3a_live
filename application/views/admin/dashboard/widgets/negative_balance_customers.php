<div id="widget-negative_balance_customers" class="widget" data-name="<?= _l('customers_with_negative_balance'); ?>">
    <div class="panel_s panel-body-small">
        <div class="panel-heading">
            <span class="widget-dragger"><i class="fa fa-arrows-alt"></i></span>
            <h4><?= _l('customers_with_negative_balance'); ?></h4>
        </div>
        <div class="panel-body">
            <table class="table table-bordered table-striped table-condensed">
                <thead>
                    <tr>
                        <th><?= _l('customer_name'); ?></th>
                        <th><?= _l('balance_due'); ?></th>
                    </tr>
                </thead>
                <?php
                $grouped = [];

                foreach ($negative_balance_customers as $row) {
                    $name = $row['customer_name'];

                    if (!isset($grouped[$name])) {
                        $grouped[$name] = 0;
                    }

                    $grouped[$name] += $row['balance_due'];
                }
                ?>

                <tbody>
                <?php if (!empty($grouped)) { ?>
                    <?php foreach ($grouped as $name => $balance) { ?>
                        <tr>
                            <td><?= $name; ?></td>
                            <td style="color:red;">¥<?= number_format($balance, 2); ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="2" class="text-center"><?= _l('no_data_found'); ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
