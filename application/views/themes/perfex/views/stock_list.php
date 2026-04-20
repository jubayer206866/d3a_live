<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h4 class="customer-profile-group-heading">
    <strong><?= _l('client_stock_tab'); ?></strong>
</h4>

<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th><strong>#</strong></th>
                        <th><strong><?php echo _l('commodity_code'); ?></strong></th>
                        <th><strong><?php echo _l('commodity_name'); ?></strong></th>
                        <th><strong><?php echo _l('cartons'); ?></strong></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stock_data)) { ?>
                        <?php $i = 1; foreach ($stock_data as $item) { ?>
                            <tr>
                                <td><?= e($item['commodity_code']); ?></td>
                                <td><?= e($item['commodity_name']); ?></td>
                                <td><?= e($item['commodity_long_description']); ?></td>
                                <td class="text-right"><?= e($item['quantity']); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                <?= _l('no_data_found'); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
