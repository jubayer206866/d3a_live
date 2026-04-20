<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin font-bold">
                                    <i class="fa fa-clipboard" aria-hidden="true"></i> <?php echo _l($title); ?>
                                </h4>
                                <hr />
                            </div>
                        </div>

                        <div class="row mbot15">
                            <div class="col-md-2">
                                <?php if (is_admin() || staff_can('add_al_purchase_invoice', 'd3a_albania')) { ?>
                                <a href="<?php echo admin_url('d3a_albania/po_invoice'); ?>" class="btn btn-info">
                                    <?php echo _l('new'); ?>
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col-md-3 hide">
                            <label><?php echo _l('vendor'); ?></label>
                            <?php echo render_select(
                                'vendor_ft[]',
                                $vendors,
                                ['userid', 'company'],
                                '',
                                '',
                                [
                                    'data-width' => '100%',
                                    'data-none-selected-text' => _l('vendors'),
                                    'multiple' => true,
                                    'data-actions-box' => true
                                ]
                            ); ?>
                        </div>
                        <div class="col-md-4">
                            <label for="months-report" class="form-label font-weight-bold"><?= _l('date'); ?></label>
                            <select class="selectpicker form-control" name="months-report" id="date_filter"
                                data-width="100%" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                <option value=""><?= _l('report_sales_months_all_time'); ?></option>
                                <option value="this_month"><?= _l('this_month'); ?></option>
                                <option value="1"><?= _l('last_month'); ?></option>
                                <option value="this_year"><?= _l('this_year'); ?></option>
                                <option value="last_year"><?= _l('last_year'); ?></option>
                                <option value="3"><?= _l('report_sales_months_three_months'); ?></option>
                                <option value="6"><?= _l('report_sales_months_six_months'); ?></option>
                                <option value="12"><?= _l('report_sales_months_twelve_months'); ?></option>
                                <option value="custom"><?= _l('custom_created_date'); ?></option>
                            </select>

                            <div class="row mt-2" id="custom-date-wrapper" style="display: none;">
                                <div class="col-md-6" style="margin-bottom:10px;">
                                    <label for="report-from" class="form-label"><?= _l('report_sales_from_date'); ?></label>
                                    <div class="input-group date">
                                        <input type="text" class="form-control datepicker" id="report-from" name="report-from" />
                                        <div class="input-group-addon">
                                            <i class="fa-regular fa-calendar calendar-icon"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6" style="margin-bottom:10px;">
                                    <label for="report-to" class="form-label"><?= _l('report_sales_to_date'); ?></label>
                                    <div class="input-group date">
                                        <input type="text" class="form-control datepicker" id="report-to" name="report-to" />
                                        <div class="input-group-addon">
                                            <i class="fa-regular fa-calendar calendar-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>Currency</label>
                            <?php echo render_select(
                                'invoice_currency[]',
                                $currencies,
                                ['id', 'name'],
                                '',
                                '',
                                [
                                    'data-width' => '100%',
                                    'data-none-selected-text' => 'Currency',
                                    'multiple' => true,
                                    'data-actions-box' => true
                                ]
                            ); ?>
                        </div>
                        <div class="col-md-4">
                            <label>Status</label>
                            <select class="selectpicker form-control" id="status_filter" multiple data-width="100%" data-actions-box="true" data-none-selected-text="All">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                                <option value="partially_paid">Partially Paid</option>
                            </select>
                        </div>



                        <?php render_datatable([
                            _l('Invoice Number'),
                            _l('Name/Title'),
                            _l('Description'),
                            _l('vendor'),
                            _l('Total Invoice Amount'),
                            _l('Paid Amount'),
                            _l('Currency'),
                            _l('Status'),
                        ], 'alb-po-invoices', [], []); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
    $(function() {

        var poInvoicesTable = initDataTable(
            '.table-alb-po-invoices',
            '<?php echo admin_url('d3a_albania/po_invoices_table'); ?>',
            undefined,
            undefined, {
                invoice_currency: '[name="invoice_currency[]"]',
                date_filter: '#date_filter',
                vendor_ft: '[name="vendor_ft[]"]',
                status_filter: '#status_filter',
                report_from: '[name="report-from"]',
                report_to: '[name="report-to"]'
            }
        );

        $('#invoice_currency, #date_filter, #status_filter').on('change', function() {
            poInvoicesTable.ajax.reload();
        });

        $('[name="invoice_currency[]"]').on('change', function() {
            poInvoicesTable.ajax.reload();
        });

        $('#report-from, #report-to').on('change', function() {
            poInvoicesTable.ajax.reload();
        });

        $('[name="vendor_ft[]"]').on('change', function() {
            poInvoicesTable.ajax.reload();
        });

        $('#date_filter').on('change', function() {

            if ($(this).val() == 'custom') {
                $('#custom-date-wrapper').show();
            } else {
                $('#custom-date-wrapper').hide();
                $('#report-from').val('');
                $('#report-to').val('');
            }

            poInvoicesTable.ajax.reload();
        });

    });
</script>
</body>

</html>