<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12 tw-mb-1">
				<div class="md:tw-flex md:tw-items-center">
					<div class="tw-grow">
						<h4 class="tw-my-0 tw-font-bold tw-text-xl">
						<?= $title; ?>
						</h4>
					</div>
				</div>
			</div>
			<div class="col-md-12">
                <div class="row">
                    <div class="col-md-12" id="small-table">
                        <?= form_hidden('invoiceid', isset($invoiceid) ? $invoiceid : ''); ?>
                        <div class="row tw-mb-3">
                           <div class="col-md-2">
                                <label></label><br>
                                <?php if (is_admin() || staff_can('create_al_invoice', 'd3a_albania')) { ?>
                                <a href="<?= admin_url('d3a_albania/invoice'); ?>" class="btn btn-info">
                                    <?= _l('new'); ?>
                                </a>
                                <?php } ?>
                            </div>
                      </div>

						<div class="row tw-mb-3 filter-row">
							<div class="col-md-3">
								<label>Currency</label>
								<?= render_select(
									'invoice_currency',
									$currencies,
									['id', 'name', 'symbol'],
									''
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
									<div class="col-md-6">
										<label for="report-from" class="form-label"><?= _l('report_sales_from_date'); ?></label>
										<div class="input-group date">
											<input type="text" class="form-control datepicker" id="report-from" name="report-from" />
											<div class="input-group-addon">
												<i class="fa-regular fa-calendar calendar-icon"></i>
											</div>
										</div>
									</div>
									<div class="col-md-6">
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

							<div class="col-md-3">
								<label>Customer</label>
								<select id="filter_customer" class="selectpicker form-control" data-live-search="true" multiple>
									<option value=""></option>
									<?php foreach ($customers as $customer) { ?>
										<option value="<?= $customer['userid']; ?>"><?= $customer['company']; ?></option>
									<?php } ?>
								</select>
							</div>

							<div class="col-md-2">
								<label>Status</label>
								<select class="form-control selectpicker" id="status_filter" data-live-search="true" multiple>
									<option value=""></option>
									<option value="1">Unpaid</option>
									<option value="2">Paid</option>
									<option value="3">Partially Paid</option>
								</select>
							</div>
						</div>
            
                        <div class="panel_s">
                            <div class="panel-body">

                                <?php render_datatable([
                                    _l('invoice_dt_table_heading_number'),
                                    _l('invoice_amount'),
                                    _l('invoice_dt_table_heading_date'),
                                    _l('clients'),
                                    _l('invoice_dt_table_heading_status')
                                ], 'alb_invoices'); ?>

                            </div>
                        </div>

                    </div>
					<div class="col-md-7 small-table-right-col">
						<div id="alb_invoice" class="hide"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div id="modal-wrapper"></div>
<?php init_tail(); ?>
<script src="<?php echo module_dir_url(D3A_ALBANIA_MODULE_NAME, 'assets/js/alb_invoice_preview.js'); ?>"></script>
<script>
var alb_can_view_al_invoice = <?php echo json_encode(is_admin() || staff_can('view_al_invoice', 'd3a_albania')); ?>;

var hidden_columns = [];

$(function () {

$('#filter_customer').selectpicker({
    liveSearch: true,
    maxOptionsText: ['{{count}} more selected', '{{count}} more selected'],
    countSelectedText: '{0} customers selected',
    multipleSeparator: ', '
});

$('#status_filter').selectpicker({
    liveSearch: true,
    countSelectedText: '{0} statuses selected',
    multipleSeparator: ', '
});

var table = initDataTable(
    '.table-alb_invoices',
    admin_url + 'd3a_albania/alb_invoices',
    undefined,
    undefined,
    {
        invoice_currency: '[name="invoice_currency"]',
        date_filter: '#date_filter',
        filter_customer: '#filter_customer',
        status_filter: '#status_filter',
        report_from: '[name="report-from"]',
        report_to: '[name="report-to"]'
    },
    [0,'desc']
);

$('#invoice_currency, #date_filter').on('change', function(){
    table.ajax.reload();
});

$('#filter_customer, #status_filter').on('change', function(){
    $(this).selectpicker('refresh');
    table.ajax.reload();
});

$('#report-from, #report-to').on('change', function(){
    table.ajax.reload();
});

if (alb_can_view_al_invoice) {
    init_alb_invoice();
}

});

function init_alb_invoice(id)
{
    if (typeof alb_can_view_al_invoice !== 'undefined' && !alb_can_view_al_invoice) {
        return;
    }
    if (typeof load_small_table_item === 'function') {
        load_small_table_item(
            id,
            "#alb_invoice",
            "invoiceid",
            "d3a_albania/get_alb_invoice_data_ajax",
            ".table-alb_invoices"
        );
    }
}
	$('#date_filter').on('change', function(){

	if($(this).val() == 'custom'){
		$('#custom-date-wrapper').show();
	}else{
		$('#custom-date-wrapper').hide();
	}

	});

</script>
<style>
.filter-row > div{
    margin-bottom:15px;
}
</style>
</body>
</html>