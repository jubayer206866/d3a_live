<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (isset($client)) { ?>
    <h4 class="customer-profile-group-heading">
        <?= _l('client_stock_tab'); ?>
    </h4>

    <div class="row">
        <div class="table-responsive">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th>#</th>
                        <th><?php echo _l('commodity_code') ?></th>
                        <th><?php echo _l('commodity_name') ?></th>
                        <th><?php echo _l('cartons') ?></th>
                    </tr>

                    <?php 
                    foreach ($single_product as $single) {
                    ?>
                    <tr>
                        <td><?php echo ($single['commodity_code']) ?></td>
                        <td><?php echo ($single['commodity_name']) ?></td>
                        <td><?php echo ($single['commodity_long_description']) ?></td>
                        <td class="text-right"><?php echo ($single['quantity']) ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>
