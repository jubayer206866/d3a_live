<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?= form_open($this->uri->uri_string(), ['id' => 'project_form']); ?>

        <div class="tw-max-w-6xl tw-mx-auto">
            <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                Add Stock To Project
            </h4>
            <div class="panel_s">
                <table class="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">Item Name</th>
                            <th scope="col">Item Code</th>
                            <th scope="col"> Receipt</th>
                            <th scope="col"> Deliverd</th>
                            <th scope="col">Add Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items) { ?>
                            <?php foreach ($items as $item) { ?>
                                <?php if ($item['quantity'] - $item['other'] > 0) { ?>
                                    <tr>
                                        <td> <?= $item['commodity_name'] ?></td>
                                        <td> <?= $item['commodity_long_description'] ?></td>
                                        <td> <?= $item['receipt'] ?></td>
                                        <td> <?= $item['delivery'] ?></td>
                                        <td> <input type="number"
                                                name="quantity[<?= $item['commodity_code'] ?>][<?= $item['goods_receipt_details_id'] ?>]"
                                                class="form-control form-control-sm" value="<?= $item['value'] ?>"
                                                max="<?= $item['quantity'] - $item['other'] ?>">
                                        </td>
                                        <input type="hidden" name="id[<?= $item['commodity_code'] ?>]" value="<?= $item['id'] ?>">
                                    </tr>
                                <?php }
                            } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="5" class="text-center"> No item found.</td>

                            </tr>
                        <?php }
                        ?>
                    </tbody>
                </table>
                <div class="panel-footer text-right">
                    <button type="submit" data-form="" class="btn btn-primary" autocomplete="off"
                        data-loading-text="<?= _l('wait_text'); ?>">
                        <?= _l('submit'); ?>
                    </button>
                </div>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
    <?php init_tail(); ?>

    </body>

    </html>