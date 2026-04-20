<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel-body">
    <h4><?php echo _l('purchase_orders'); ?></h4>

    <?php if (!empty($purchase_orders)) { ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th><?php echo _l('purchase_order'); ?></th>
                        <th><?php echo _l('vendor'); ?></th>
                        <th><?php echo _l('order_date'); ?></th>
                        <th><?php echo _l('payment_due_date'); ?></th>
                        <th><?php echo _l('project'); ?></th>
                        <th><?php echo _l('po_description'); ?></th>
                        <th><?php echo _l('total'); ?></th>
                        <th><?php echo _l('approval_status'); ?></th>
                        <th><?php echo _l('delivery_date'); ?></th>
                        <th><?php echo _l('delivery_status'); ?></th>
                        <th><?php echo _l('payment_status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchase_orders as $order) { ?>
                        <tr>
                            <td>
                                <?php echo $order['pur_order_number']; ?>
                                <div class="row-options">
                                    <a href="<?php echo site_url('clients/purchase_order/' . $order['id']); ?>" 
                                        class="btn btn-xs">
                                        <?php echo _l('view'); ?>
                                    </a>
                                </div>
                            </td>
                            <td><?php echo $order['vendor_name'] ?? $order['vendor']; ?></td>
                            <td><?php echo _d($order['order_date']); ?></td>
                            <td><?php echo _d($order['payment_due_date']); ?></td>
                            <td><?php echo $order['project_name'] ?? '-'; ?></td>
                            <td><?php echo $order['po_description'] ?? '-'; ?></td>
                            <td>
                                <?php 
                                echo isset($order['total']) && isset($currency)
                                    ? app_format_money($order['total'], $currency->name)
                                    : $order['total'];
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (isset($order['approve_status'])) {
                                    if ($order['approve_status'] == 1) {
                                        echo '<span class="label label-warning">' . _l('purchase_not_yet_approve') . '</span>';
                                    } elseif ($order['approve_status'] == 2) {
                                        echo '<span class="label label-success">' . _l('purchase_approved') . '</span>';
                                    } elseif ($order['approve_status'] == 3) {
                                        echo '<span class="label label-danger">' . _l('purchase_reject') . '</span>';
                                    } elseif ($order['approve_status'] == 4) {
                                        echo '<span class="label label-default">' . _l('cancelled') . '</span>';
                                    } else {
                                        echo '-';
                                    }
                                }
                                ?>
                            </td>
                            <td><?php echo !empty($order['delivery_date']) ? _d($order['delivery_date']) : '-'; ?></td>
                            <td>
                                <?php
                                if ($order['delivery_status'] == 0) {
                                    echo '<span class="label label-danger">'._l('undelivered').'</span>';
                                } elseif ($order['delivery_status'] == 1) {
                                    echo '<span class="label label-success">'._l('completely_delivered').'</span>';
                                } elseif ($order['delivery_status'] == 2) {
                                    echo '<span class="label label-info">'._l('pending_delivered').'</span>';
                                } else {
                                    echo '<span class="label label-warning">'._l('partially_delivered').'</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $order['payment_status'] ?? '-'; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <p class="text-muted"><?php echo _l('no_purchase_orders_found'); ?></p>
    <?php } ?>
</div>
