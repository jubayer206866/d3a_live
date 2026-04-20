<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$invoice_detail = isset($invoice_detail) ? $invoice_detail : [];
$payment = isset($payment) ? $payment : [];
$base_currency = isset($base_currency) ? $base_currency : null;
$invoice_number = format_alb_invoice_number_custom($alb_invoice);
$invoice_total = isset($alb_invoice->invoice_amount) ? (float)$alb_invoice->invoice_amount : (isset($alb_invoice->total) ? (float)$alb_invoice->total : 0);
$total_left_to_pay = function_exists('alb_invoice_left_to_pay') ? alb_invoice_left_to_pay($alb_invoice->id) : $invoice_total;
$currency_symbol = $base_currency ? $base_currency->symbol : '';
?>
<div class="panel_s">
	<div class="panel-body" style="padding: 15px 20px;">
		<div class="row mbot0">
			<div class="col-sm-6 col-xs-12">
				<h4 class="no-margin font-bold" style="display:inline-block;"><?php echo e($title); ?></h4>
				<?php
				$status_class = 'danger';
				if (isset($alb_invoice->status)) {
					if ($alb_invoice->status == 2) $status_class = 'success';
					elseif ($alb_invoice->status == 3) $status_class = 'warning';
				}
				?>
				<span class="label label-<?php echo $status_class; ?> mleft5"><?php echo format_invoice_status($alb_invoice->status); ?></span>
			</div>
			<div class="col-sm-6 col-xs-12 text-right">
				<?php if ($total_left_to_pay > 0 && (is_admin() || staff_can('add_al_payment', 'd3a_albania'))) { ?>
					<a href="#" onclick="add_alb_payment(<?php echo (int)$alb_invoice->id; ?>); return false;" class="btn btn-success btn-sm"><i class="fa fa-plus-square"></i> <?php echo _l('payment'); ?></a>
				<?php } ?>
				<?php if (!empty($alb_invoice->hash) && (is_admin() || staff_can('preview_al_invoice', 'd3a_albania'))) { ?>
				<a href="<?php echo site_url('alb_invoice/' . $alb_invoice->id . '/' . $alb_invoice->hash); ?>" class="btn btn-default btn-sm" target="_blank"><i class="fa fa-user"></i> <?php echo _l('view_as_customer'); ?></a>
				<?php } ?>
				<div class="btn-group">
					<a href="#" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fa-regular fa-file-pdf"></i> <?php if (is_mobile()) { echo ' PDF'; } ?> <span class="caret"></span></a>
					<ul class="dropdown-menu dropdown-menu-right">
						<li><a href="<?php echo admin_url('d3a_albania/alb_invoice_pdf/' . $alb_invoice->id . '?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
						<li><a href="<?php echo admin_url('d3a_albania/alb_invoice_pdf/' . $alb_invoice->id . '?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
						<li><a href="<?php echo admin_url('d3a_albania/alb_invoice_pdf/' . $alb_invoice->id); ?>"><?php echo _l('download'); ?></a></li>
						<li><a href="<?php echo admin_url('d3a_albania/alb_invoice_pdf/' . $alb_invoice->id . '?print=true'); ?>" target="_blank"><?php echo _l('print'); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php echo form_hidden('invoice_id', $alb_invoice->id) ?>
		<ul class="nav nav-tabs mtop10 mbot10" role="tablist">
			<li role="presentation" class="active"><a href="#tab_alb_invoice" role="tab" data-toggle="tab"><?php echo _l('alb_invoice'); ?></a></li>
			<li role="presentation"><a href="#payment_record" role="tab" data-toggle="tab"><?php echo _l('payment_record'); ?></a></li>
		</ul>

		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="tab_alb_invoice">
				<div class="row mbot10">
					<div class="col-md-6">
						<table class="table table-condensed table-borderless mbot0" style="font-size: 13px;">
							<tr><td width="140"><?php echo _l('invoice_code'); ?></td><td class="bold"><?php echo e($invoice_number); ?></td></tr>
							<tr><td><?php echo _l('invoice_date'); ?></td><td class="bold"><?php echo _d($alb_invoice->date); ?></td></tr>
							<tr><td><?php echo _l('invoice_amount'); ?></td><td class="bold"><?php echo app_format_money($invoice_total, $base_currency); ?></td></tr>
						</table>
					</div>
					<div class="col-md-6">
						<table class="table table-condensed table-borderless mbot0" style="font-size: 13px;">
							<tr><td width="100"><?php echo _l('add_from'); ?></td><td><a href="<?php echo admin_url('staff/profile/' . $alb_invoice->addedfrom); ?>"><?php echo get_staff_full_name($alb_invoice->addedfrom); ?></a></td></tr>
							<tr><td><?php echo _l('date_add'); ?></td><td><?php echo _d($alb_invoice->datecreated); ?></td></tr>
						</table>
					</div>
				</div>

				<div class="table-responsive">
					<table class="table table-condensed items items-preview mbot10">
						<thead>
							<tr>
								<th><?php echo _l('invoice_table_item_heading'); ?></th>
								<th><?php echo _l('invoice_table_quantity_heading'); ?></th>
								<th><?php echo _l('unit'); ?></th>
								<th><?php echo _l('invoice_table_rate_heading'); ?></th>
								<th><?php echo _l('invoice_table_amount_heading'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$sum_amount = 0;
							if (count($invoice_detail) > 0) {
								foreach ($invoice_detail as $es) {
									$sum_amount += (float)($es['amount'] ?? 0);
							?>
								<tr>
									<td><?php echo e($es['item_group'] ?? ''); ?></td>
									<td><?php echo e($es['qty'] ?? ''); ?></td>
									<td><?php echo e($es['unit'] ?? '-'); ?></td>
									<td><?php echo app_format_money($es['price'] ?? 0, $base_currency); ?></td>
									<td><?php echo app_format_money($es['amount'] ?? 0, $base_currency); ?></td>
								</tr>
							<?php
									}
							}
							?>
							<tr class="bold">
								<td colspan="4" class="text-right"><?php echo _l('invoice_total'); ?></td>
								<td><?php echo app_format_money($sum_amount, $base_currency); ?></td>
							</tr>
						</tbody>
					</table>
				</div>

				<?php if (!empty($alb_invoice->adminnote)) { ?>
				<div class="mtop10" style="font-size: 13px;"><strong><?php echo _l('adminnote'); ?>:</strong> <?php echo e($alb_invoice->adminnote); ?></div>
				<?php } ?>
				<?php if (!empty($alb_invoice->terms)) { ?>
				<div class="mtop5" style="font-size: 13px;"><strong><?php echo _l('terms'); ?>:</strong> <?php echo e($alb_invoice->terms); ?></div>
				<?php } ?>
			</div>

			<div role="tabpanel" class="tab-pane" id="payment_record">
				<h5 class="bold mbot10 text-success"><?php echo _l('Payment For ALB Invoice'); ?> <?php echo e($invoice_number); ?></h5>
				<table class="table table-condensed dt-table">
					<thead>
						<tr>
							<th><?php echo _l('payments_table_amount_heading'); ?></th>
							<th><?php echo _l('payments_table_mode_heading'); ?></th>
							<th><?php echo _l('payment_transaction_id'); ?></th>
							<th><?php echo _l('payments_table_date_heading'); ?></th>
							<th><?php echo _l('options'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ((array)$payment as $pay) {
							$pay = (object)$pay;
						?>
							<tr>
								<td><?php echo app_format_money($pay->amount ?? 0, $base_currency); ?></td>
								<td><?php echo get_payment_mode_by_id($pay->paymentmode ?? 0); ?></td>
								<td><?php echo e($pay->transactionid ?? ''); ?></td>
								<td><?php echo _d($pay->date ?? ''); ?></td>
								<td>
									<?php if (is_admin() || staff_can('delete_al_payment', 'd3a_albania')) { ?>
										<a href="<?php echo admin_url('d3a_albania/delete_alb_invoice_payment/' . ($pay->id ?? '') . '/' . $alb_invoice->id); ?>" class="btn btn-danger btn-icon btn-sm _delete" data-toggle="tooltip" title="<?php echo _l('delete'); ?>"><i class="fa fa-remove"></i></a>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php if ($total_left_to_pay > 0 && (is_admin() || staff_can('add_al_payment', 'd3a_albania')) && isset($payment_modes)) { ?>
<div class="modal fade" id="payment_record_alb" tabindex="-1" role="dialog">
	<div class="modal-dialog dialog_30">
		<?php echo form_open(admin_url('d3a_albania/add_alb_invoice_payment/' . $alb_invoice->id), array('id' => 'albinvoice-add_payment-form')); ?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo _l('new_payment'); ?></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<?php $left_to_pay = alb_invoice_left_to_pay($alb_invoice->id); ?>
						<?php echo render_input('amount', 'amount', $left_to_pay, 'number', array('max' => $left_to_pay)); ?>
						<?php echo render_date_input('date', 'payment_edit_date'); ?>
						<?php echo render_select('paymentmode', $payment_modes, array('id', 'name'), 'payment_mode'); ?>
						<?php echo render_input('transactionid', 'payment_transaction_id'); ?>
						<?php echo render_textarea('note', 'note', '', array('rows' => 7)); ?>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
			</div>
		</div>
		<?php echo form_close(); ?>
	</div>
</div>
<?php } ?>
