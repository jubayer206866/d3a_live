<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12" id="small-table">
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin font-bold">
                                    <i class="fa fa-clone menu-icon" aria-hidden="true"></i>
                                    <?php echo _l($title); ?>
                                </h4>
                                <hr>
                            </div>
                        </div>

                        <div class="row row-margin">
                            <div class="col-md-12">
                                <div class="panel panel-info panel-padding">
                                    <div class="panel-body">

                                        <div class="col-md-12 text-center">
                                            <h2 class="bold"><?php echo mb_strtoupper(_l('store_input_slip')); ?></h2>
                                        </div>

                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <td class="bold"><?php echo _l('supplier_name'); ?></td>
                                                    <td>
                                                        <?php
                                                        if (get_status_modules_wh('purchase') && $goods_receipt->supplier_code) {
                                                            echo new_html_entity_decode(wh_get_vendor_company_name($goods_receipt->supplier_code));
                                                        } else {
                                                            echo new_html_entity_decode($goods_receipt->supplier_name);
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('person_in_charge'); ?></td>
                                                    <td><?php echo get_staff_full_name($goods_receipt->buyer_id); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('customer'); ?></td>
                                                    <td><?php echo get_customer_name_with_project($goods_receipt->project); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('stock_received_docket_number'); ?></td>
                                                    <td><?php echo new_html_entity_decode($goods_receipt->goods_receipt_code); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('invoice_no'); ?></td>
                                                    <td><?php echo new_html_entity_decode($goods_receipt->invoice_no); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('note_'); ?></td>
                                                    <td><?php echo new_html_entity_decode($goods_receipt->description); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th><?php echo _l('product_code'); ?></th>
                                                        <th><?php echo _l('product_name'); ?></th>
                                                        <th><?php echo _l('cartons'); ?></th>
                                                        <th><?php echo _l('pieces_carton'); ?></th>
                                                        <th><?php echo _l('total_pieces'); ?></th>
                                                        <th><?php echo _l('price'); ?></th>
                                                        <th><?php echo _l('price_total'); ?></th>
                                                        <th>G.W</th>
                                                        <th>T.G.W</th>
                                                        <th>N.W</th>
                                                        <th>T.N.W</th>
                                                        <th>CBM</th>
                                                        <th>Total CBM</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $cartons = $totalPieces = $Pricetotal = $Total_net_weight = $Total_gross_weight = $Total_cbm = 0;
                                                    foreach (json_decode($goods_receipt_detail) as $key => $item) {
                                                        if ($item->checkd_koli != 0) {
                                                            $cartons += (float)$item->checkd_koli;
                                                            $totalPieces += (float)$item->total_koli;
                                                            $Pricetotal += preg_replace('/[^0-9.]/', '', $item->price_total);
                                                            $Total_net_weight += (float)$item->total_net_weight;
                                                            $Total_gross_weight += (float)$item->total_gross_weight;
                                                            $Total_cbm += (float)$item->total_cbm;
                                                    ?>
                                                            <tr>
                                                                <td><?php echo $key + 1; ?></td>
                                                                <td><?php echo $item->commodity_name; ?></td>
                                                                <td><?php echo $item->commodity_long_description; ?></td>
                                                                <td class="text-right"><?php echo $item->checkd_koli; ?></td>
                                                                <td class="text-right"><?php echo $item->cope_koli; ?></td>
                                                                <td class="text-right"><?php echo $item->total_koli; ?></td>
                                                                <td class="text-right"><?php echo app_format_money((float)$item->price, ''); ?></td>
                                                                <td class="text-right"><?php echo app_format_money($item->price_total, ''); ?></td>
                                                                <td class="text-right"><?php echo clean_number($item->gross_weight); ?></td>
                                                                <td class="text-right"><?php echo clean_number($item->total_gross_weight); ?></td>
                                                                <td class="text-right"><?php echo clean_number($item->net_weight); ?></td>
                                                                <td class="text-right"><?php echo clean_number($item->total_net_weight); ?></td>
                                                                <td class="text-right"><?php echo clean_number($item->cbm_koli); ?></td>
                                                                <td class="text-right"><?php echo clean_number($item->total_cbm); ?></td>
                                                            </tr>
                                                    <?php }
                                                    } ?>
                                                </tbody>

                                            </table>
                                        </div>

                                        <div class="col-md-5 col-md-offset-7">
                                            <table class="table text-right">
                                                <tr>
                                                    <td class="bold"><?php echo _l('cartons'); ?></td>
                                                    <td><?php echo $cartons; ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('total_pieces'); ?></td>
                                                    <td><?php echo $totalPieces; ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('price_total'); ?></td>
                                                    <td><?php echo '¥' . $Pricetotal; ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('total_gross_weight'); ?></td>
                                                    <td><?php echo clean_number($Total_gross_weight); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('total_net_weight'); ?></td>
                                                    <td><?php echo clean_number($Total_net_weight); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bold"><?php echo _l('total_cbm'); ?></td>
                                                    <td><?php echo clean_number($Total_cbm); ?></td>
                                                </tr>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>

</html>