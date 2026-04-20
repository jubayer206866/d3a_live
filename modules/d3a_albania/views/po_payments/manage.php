<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div id="content">
        <div class="content-area">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <?php if (is_admin() || staff_can('add_al_purchase_payment', 'd3a_albania')) { ?>
                            <div class="tw-mb-3">
                                <a href="<?php echo admin_url('d3a_albania/po_payment'); ?>" class="btn btn-info"><?php echo _l('new'); ?></a>
                            </div>
                            <?php } ?>
                            <?php render_datatable([
                                _l('payments_table_number_heading'),
                                _l('payments_table_invoicenumber_heading'),
                                _l('payments_table_mode_heading'),
                                // _l('payment_transaction_id'),
                                _l('vendor'),
                                _l('payments_table_amount_heading'),
                                _l('invoice_amount_lebel'),
                                _l('payments_table_date_heading'),
                            ], 'alb-po-payments', [], []); ?>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function () {
        initDataTable('.table-alb-po-payments', '<?php echo admin_url('d3a_albania/po_payments_table'); ?>', [5], [5]);
    });
</script>
</body>

</html>