<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12" id="small-table">
                <div class="panel_s">
                    <?php echo form_open_multipart(admin_url('warehouse/change_goods_receipt_detail/' . $goods_receipt_detail[0]['id']), array('id' => 'add_goods_delivery')); ?>
                    <div class="panel-body">

                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin font-bold">
                                    <i class="fa fa-object-ungroup menu-icon" aria-hidden="true"></i>
                                    Update Inventory Receipt Product Details of
                                    <?php echo $goods_receipt_detail[0]['commodity_name']; ?>
                                </h4>
                                <hr>
                            </div>
                        </div>
                        <form id="Product-form" class="Product-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="radio" id="receive" name="type" value="receive"
                                        onclick="toggleForm(true)">
                                    <label for="receive">Receive</label><br>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" id="delivery" name="type" value="delivery"
                                        onclick="toggleForm(false)">
                                    <label for="delivery">Delivery</label><br>
                                </div>
                            </div>
                            <br>
                            <br>
                            <div class="row" id="formContent" style="display: none;">
                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['koli']; ?>
                                    <?php echo render_input('koli', 'koli', $value, '') ?>
                                </div>
                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['checkd_koli']; ?>
                                    <?php echo render_input('checkd_koli', 'checkd_koli', $value, '', ['readonly' => true]); ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['cope_koli']; ?>
                                    <?php echo render_input('cope_koli', 'cope_koli', $value, '') ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['total_koli']; ?>
                                    <?php echo render_input('total_koli', 'total_koli', $value, '', ['readonly' => true]) ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['price']; ?>
                                    <?php echo render_input('price', 'price', $value, '') ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['price_total']; ?>
                                    <?php echo render_input('price_total', 'price_total', $value, '', ['readonly' => true]) ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['net_weight']; ?>
                                    <?php echo render_input('net_weight', 'gross_weight', $value, '') ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['total_net_weight']; ?>
                                    <?php echo render_input('total_net_weight', 'total_gross_weight', $value, '', ['readonly' => true]) ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['gross_weight']; ?>
                                    <?php echo render_input('gross_weight', 'net_weight', $value, '', ) ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['total_gross_weight']; ?>
                                    <?php echo render_input('total_gross_weight', 'total_net_weight', $value, '', ['readonly' => true]) ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['cbm_koli']; ?>
                                    <?php echo render_input('cbm_koli', 'cbm_koli', $value, '') ?>
                                </div>

                                <div class="col-md-6">
                                    <?php $value = $goods_receipt_detail[0]['total_cbm']; ?>
                                    <?php echo render_input('total_cbm', 'total_cbm', $value, '', ['readonly' => true]) ?>
                                </div>
                            </div>
                        </form>
                        <button type="submit" class="btn btn-info pull-right mright5" id="SubmitBtn">
                            <?php echo _l('submit'); ?>
                        </button>
                        <script>
                            function toggleForm(show) {
                                document.getElementById('formContent').style.display = show ? 'block' : 'none';
                                document.querySelectorAll('input[name="type"]').forEach(radio => {
                                    radio.addEventListener('change', function () {
                                        document.getElementById('SubmitBtn').disabled = !(document.getElementById('receive').checked);
                                        document.getElementById('SubmitBtn').disabled = false;
                                    })
                                }
                                );
                            }
                        </script>
                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                document.getElementById("cope_koli").addEventListener("change", function () {
                                    let cope_koli = parseFloat(document.querySelector('input[name="cope_koli"]').value);
                                    let checkd_koli = parseFloat(document.querySelector('input[name="checkd_koli"]').value);
                                    let total_koli = cope_koli * checkd_koli;
                                    document.querySelector('input[name="total_koli"]').value = total_koli;
                                });
                                document.getElementById("price").addEventListener("change", function () {
                                    let price = parseFloat(document.querySelector('input[name="price"]').value);
                                    let checkd_koli = parseFloat(document.querySelector('input[name="checkd_koli"]').value);
                                    let price_total = price * checkd_koli;
                                    document.querySelector('input[name="price_total"]').value = price_total;
                                });
                                document.getElementById("gross_weight").addEventListener("change", function () {
                                    let gross_weight = parseFloat(document.querySelector('input[name="gross_weight"]').value);
                                    let checkd_koli = parseFloat(document.querySelector('input[name="checkd_koli"]').value);
                                    let total_gross_weight = gross_weight * checkd_koli;
                                    document.querySelector('input[name="total_gross_weight"]').value = total_gross_weight;
                                });
                                document.getElementById("net_weight").addEventListener("change", function () {
                                    let net_weight = parseFloat(document.querySelector('input[name="net_weight"]').value);
                                    let checkd_koli = parseFloat(document.querySelector('input[name="checkd_koli"]').value);
                                    let total_net_weight = net_weight * checkd_koli;
                                    document.querySelector('input[name="total_net_weight"]').value = total_net_weight;
                                });
                                document.getElementById("cbm_koli").addEventListener("change", function () {
                                    let cbm_koli = parseFloat(document.querySelector('input[name="cbm_koli"]').value);
                                    let checkd_koli = parseFloat(document.querySelector('input[name="checkd_koli"]').value);
                                    let total_cbm = cbm_koli * checkd_koli;
                                    document.querySelector('input[name="total_cbm"]').value = total_cbm;
                                });
                            });

                        </script>

                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>


<?php init_tail(); ?>