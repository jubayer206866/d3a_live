<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<?php if ((debits_can_be_applied_to_invoice($pur_invoice->payment_status) && $debits_available > 0)) { ?>
						<div class="alert alert-warning mbot5">
							<?php echo app_format_money($debits_available, $vendor_currency->name) . ' ' . _l('x_debits_available'); ?>
							<br />
							<a href="#" data-toggle="modal" data-target="#apply_debits"><?php echo _l('apply_debits'); ?></a>
						</div>
					<?php } ?>

					<div class="panel-body">
						<div class="row">
							<div class="col-md-12">
								<h4 class="no-margin font-bold"><?php echo html_entity_decode($title); ?> </h4>
								<br>
							</div>
						</div>
						<?php
						$base_currency = get_base_currency_pur();
						if ($pur_invoice->currency != 0) {
							$base_currency = pur_get_currency_by_id($pur_invoice->currency);
						}
						?>
						<?php echo form_hidden('invoice_id', $pur_invoice->id) ?>
						<div class="horizontal-scrollable-tabs preview-tabs-top">
							<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
							<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
							<div class="horizontal-tabs">
								<ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
									<li role="presentation" class="active">
										<a href="#tab_pur_invoice" aria-controls="tab_pur_invoice" role="tab" data-toggle="tab">
											<?php echo _l('pur_invoice'); ?>
										</a>
									</li>
									<li role="presentation">
										<a href="#payment_record" aria-controls="payment_record" role="tab" data-toggle="tab">
											<?php echo _l('payment_record'); ?>
										</a>
									</li>

								</ul>
							</div>
						</div>

						<div class="row">
							<div class="col-md-3">
								<?php $class = '';
								if ($pur_invoice->payment_status == 'unpaid') {
									$class = 'danger';
								} elseif ($pur_invoice->payment_status == 'paid') {
									$class = 'success';
								} elseif ($pur_invoice->payment_status == 'partially_paid') {
									$class = 'warning';
								} ?>
								<span class="label label-<?php echo pur_html_entity_decode($class); ?> mtop5 s-status invoice-status-3"><?php echo _l($pur_invoice->payment_status, $pur_invoice->payment_status); ?></span>
							</div>
							<div class="col-md-9 _buttons">
								<div class="visible-xs">
									<div class="mtop10"></div>
								</div>
								<div class="pull-right">

									<div class="btn-group">
										<a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
											aria-haspopup="true" aria-expanded="false"><i class="fa-regular fa-file-pdf"></i><?php if (is_mobile()) {
																																	echo ' PDF';
																																} ?> <span class="caret"></span></a>
										<ul class="dropdown-menu dropdown-menu-right">
											<li class="hidden-xs"><a
													href="<?php echo admin_url('d3a_albania/albania_po_invoice_pdf/' . $pur_invoice->id . '?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a>
											</li>
											<li class="hidden-xs"><a
													href="<?php echo admin_url('d3a_albania/albania_po_invoice_pdf/' . $pur_invoice->id . '?output_type=I'); ?>"
													target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
											<li><a
													href="<?php echo admin_url('d3a_albania/albania_po_invoice_pdf/' . $pur_invoice->id); ?>"><?php echo _l('download'); ?></a>
											</li>
											<li>
												<a href="<?php echo admin_url('d3a_albania/albania_po_invoice_pdf/' . $pur_invoice->id . '?print=true'); ?>"
													target="_blank">
													<?php echo _l('print'); ?>
												</a>
											</li>
										</ul>
									</div>

									<?php if (is_admin() || staff_can('edit_al_purchase_invoice', 'd3a_albania')) { ?>
									<a href="<?php echo admin_url('d3a_albania/po_invoice/' . $pur_invoice->id); ?>" data-toggle="tooltip" title="<?php echo _l('edit_invoice'); ?>" class="btn btn-default btn-with-tooltip mright5" data-placement="bottom"><i class="fa fa-pencil-square"></i></a>
									<?php } ?>

									<?php if (po_invoice_left_to_pay($pur_invoice->id) > 0 && (is_admin() || staff_can('add_al_purchase_payment', 'd3a_albania'))) { ?>
										<a href="#" onclick="add_payment(<?php echo pur_html_entity_decode($pur_invoice->id); ?>); return false;" class="btn btn-success pull-right"><i class="fa fa-plus-square"></i>&nbsp;<?php echo ' ' . _l('payment'); ?></a>
									<?php } ?>
								</div>

							</div>
						</div>

						<div class="clearfix"></div>
						<hr class="hr-panel-heading" />

						<div class="tab-content">
							<div role="tabpanel" class="tab-pane active" id="tab_pur_invoice">
								<div class="row mbot10">
									<div class="col-md-6">
										<table class="table table-condensed table-borderless mbot0" style="font-size: 13px;">
											<tr>
												<td width="140"><?php echo _l('invoice_code'); ?></td>
												<td class="bold"><?php echo pur_html_entity_decode($pur_invoice->invoice_number); ?></td>
											</tr>
											<tr>
												<td><?php echo _l('invoice_date'); ?></td>
												<td class="bold"><?php echo _d($pur_invoice->invoice_date); ?></td>
											</tr>
											<tr>
												<td><?php echo _l('pur_due_date'); ?></td>
												<td class="bold"><?php echo _d($pur_invoice->duedate); ?></td>
											</tr>
											<tr>
												<td><?php echo _l('invoice_amount'); ?></td>
												<td class="bold"><?php echo app_format_money($pur_invoice->total, $base_currency->symbol); ?></td>
											</tr>
										</table>
									</div>
									<div class="col-md-6">
										<table class="table table-condensed table-borderless mbot0" style="font-size: 13px;">
											<tr>
												<td width="100"><?php echo _l('add_from'); ?></td>
												<td><a href="<?php echo admin_url('staff/profile/' . $pur_invoice->add_from); ?>"><?php echo get_staff_full_name($pur_invoice->add_from); ?></a></td>
											</tr>
											<tr>
												<td><?php echo _l('date_add'); ?></td>
												<td><?php echo _d($pur_invoice->date_add); ?></td>
											</tr>
										</table>
									</div>
								</div>

								<div class="table-responsive">
									<table class="table table-condensed items items-preview mbot10" data-type="estimate">
										<thead>
											<tr>
												<th><?php echo _l('Name/Title'); ?></th>
												<th><?php echo _l('Description'); ?></th>
												<th><?php echo _l('Unit/Quantity'); ?></th>
												<th><?php echo _l('Price/Value'); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php
											$sum_amount = 0;

											if (count($invoice_detail) > 0) {
												foreach ($invoice_detail as $es) {

													$line_total = isset($es['total_money']) 
																	? (float)$es['total_money'] 
																	: 0;

													$sum_amount += $line_total;
											?>
													<tr>
														<td><?php echo e($es['item_name'] ?? ''); ?></td>
														<td><?php echo e($es['description'] ?? ''); ?></td>
														<td><?php echo e($es['quantity'] ?? ''); ?></td>
														<td><?php echo app_format_money($es['unit_price'] ?? 0, $base_currency); ?></td>
													</tr>
											<?php
												}
											}
											?>
											<tr class="bold">
												<td colspan="3" class="text-right"><?php echo _l('invoice_total'); ?></td>
												<td><?php echo app_format_money($sum_amount, $base_currency); ?></td>
											</tr>
										</tbody>
									</table>
								</div>

								<?php if (!empty($pur_invoice->adminnote)) { ?>
									<div class="mtop10" style="font-size: 13px;"><strong><?php echo _l('adminnote'); ?>:</strong> <?php echo pur_html_entity_decode($pur_invoice->adminnote); ?></div>
								<?php } ?>
								<?php if (!empty($pur_invoice->terms)) { ?>
									<div class="mtop5" style="font-size: 13px;"><strong><?php echo _l('terms'); ?>:</strong> <?php echo pur_html_entity_decode($pur_invoice->terms); ?></div>
								<?php } ?>
							</div>

							<div role="tabpanel" class="tab-pane" id="payment_record">
								<div class="col-md-6 pad_left_0">
									<h4 class="font-medium mbot15 bold text-success"><?php echo 'Payment Purchase Invoice' . ' ' . $pur_invoice->invoice_number; ?></h4>
								</div>

								<div class="clearfix"></div>
								<table class="table dt-table">
									<thead>
										<th><?php echo _l('payments_table_amount_heading'); ?></th>
										<th><?php echo _l('payments_table_mode_heading'); ?></th>
										<!-- <th><?php echo _l('payment_transaction_id'); ?></th> -->
										<th><?php echo _l('payments_table_date_heading'); ?></th>
										<th><?php echo _l('approval_status'); ?></th>
										<th><?php echo _l('options'); ?></th>
									</thead>
									<tbody>
										<?php foreach ($payment as $pay) { ?>
											<tr>
												<td><?php echo app_format_money($pay['amount'], $base_currency->symbol); ?></td>
												<td><?php echo get_payment_mode_by_id($pay['paymentmode']); ?></td>
												<!-- <td><?php echo pur_html_entity_decode($pay['transactionid']); ?></td> -->
												<td><?php echo _d($pay['date']); ?></td>
												<td><?php echo get_status_approve($pay['approval_status']); ?></td>
												<td>
														<a href="<?php echo admin_url('d3a_albania/payment_po_invoice/' . $pay['id']); ?>" class="btn btn-default btn-icon" data-toggle="tooltip" data-placement="top" title="<?php echo _l('view'); ?>"><i class="fa fa-eye "></i></a>

														<a href="<?php echo admin_url('d3a_albania/delete_po_invoice/' . $pay['id'] . '/' . $pur_invoice->id); ?>" class="btn btn-danger btn-icon _delete" data-toggle="tooltip" data-placement="top" title="<?php echo _l('delete'); ?>"><i class="fa fa-remove"></i></a>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>

						</div>

					</div>
				</div>
			</div>
		</div>
	</div>

	<?php if (po_invoice_left_to_pay($pur_invoice->id) > 0 && (is_admin() || staff_can('add_al_purchase_payment', 'd3a_albania'))) { ?>
	<div class="modal fade" id="payment_record_pur" tabindex="-1" role="dialog">
		<div class="modal-dialog dialog_30">
			<?php echo form_open(admin_url('d3a_albania/add_invoice_payment/' . $pur_invoice->id), array('id' => 'purinvoice-add_payment-form')); ?>
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">
						<span class="edit-title"><?php echo _l('edit_payment'); ?></span>
						<span class="add-title"><?php echo _l('new_payment'); ?></span>
					</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<div id="additional"></div>
							<?php echo render_input('amount', 'amount', po_invoice_left_to_pay($pur_invoice->id), 'number', array('max' => po_invoice_left_to_pay($pur_invoice->id))); ?>
							<?php echo render_date_input('date', 'payment_edit_date'); ?>
							<?php echo render_select('paymentmode', $payment_modes, array('id', 'name'), 'payment_mode'); ?>

							<!-- <?php echo render_input('transactionid', 'payment_transaction_id'); ?> -->
							<?php echo render_textarea('note', 'note', '', array('rows' => 7)); ?>

						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
					<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
				</div>
			</div><!-- /.modal-content -->
			<?php echo form_close(); ?>
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->

<?php } ?>

	<?php init_tail(); ?>

	<?php if (po_invoice_left_to_pay($pur_invoice->id) > 0 && (is_admin() || staff_can('add_al_purchase_payment', 'd3a_albania'))) { ?>
	<script src="<?php echo module_dir_url('d3a_albania', 'assets/js/alb_invoice_preview.js'); ?>"></script>
	<?php } ?>

	<?php $this->load->view('debit_notes/apply_invoice_debits'); ?>

	</body>

	</html>