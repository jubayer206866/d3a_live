<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">

			<?php echo form_open_multipart(admin_url('d3a_albania/create_invoice_form'), array('id' => 'pur_invoice-form', 'class' => '_pur_invoice_form _transaction_form')); ?>
			<?php
			if (isset($pur_invoice)) {
				echo form_hidden('isedit', 1);
			}
			?>
			<div class="col-md-12">
				<div class="panel_s accounting-template estimate">
					<div class="panel-body">

						<div class="row">
							<div class="col-md-12">
								<h4 class="no-margin font-bold">
									<?php echo _l($title); ?>
								</h4>
								<hr />
							</div>
						</div>

						<div class="row">
							<?php $additional_discount = 0; ?>
							<input type="hidden" name="additional_discount" value="<?php echo html_entity_decode($additional_discount); ?>">
							<div class="col-md-6">
								<?php echo form_hidden('id', (isset($pur_invoice) ? $pur_invoice->id : '')); ?>
								<div class="col-md-6 pad_left_0">
									<label for="invoice_number"><span class="text-danger">* </span><?php echo _l('invoice_number'); ?></label>
									<?php
									$prefix = get_purchase_option('alb_po_inv_prefix');
					// allow controller to supply dynamic next_number based on last saved invoice
					$next_number = isset($next_number) ? $next_number : get_purchase_option('alb_po_next_inv_number');
									if (isset($pur_invoice) && isset($pur_invoice->invoice_number)) {
										$invoice_number = $pur_invoice->invoice_number;
										$number_part = preg_replace('/[^0-9]/', '', $invoice_number);
										if ($number_part === '') {
											$number_part = $next_number;
										}
										$numeric_part = str_pad($number_part, 6, '0', STR_PAD_LEFT);
										echo form_hidden('number', $number_part);
									} else {
										$invoice_number = $prefix . str_pad($next_number, 6, '0', STR_PAD_LEFT);
										$numeric_part = str_pad($next_number, 6, '0', STR_PAD_LEFT);
										echo form_hidden('number', $next_number);
									}

									echo render_input('invoice_number', '', $invoice_number, 'text', ['readonly' => '', 'required' => 'true']); ?>
								</div>

								<div class="col-md-6 pad_right_0 form-group">
									<label for="vendor"><span class="text-danger">* </span><?php echo _l('pur_vendor'); ?></label>
									<select name="vendor" id="vendor" class="selectpicker" onchange="pur_vendor_change(this); return false;" required="true" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php foreach ($vendors as $ven) { ?>
											<option value="<?php echo html_entity_decode($ven['userid']); ?>" <?php if (isset($pur_invoice) && $pur_invoice->vendor == $ven['userid']) {
																													echo 'selected';
																												} ?>><?php echo html_entity_decode($ven['vendor_code'] . ' ' . $ven['company']); ?></option>
										<?php } ?>
									</select>
								</div>
								

								<div class="col-md-6 pad_left_0">
									<label for="invoice_date"><span class="text-danger">* </span><?php echo _l('PO Invoice Date'); ?></label>
									<?php $invoice_date = (isset($pur_invoice) ? _d($pur_invoice->invoice_date) : _d(date('Y-m-d')));
									echo render_date_input('invoice_date', '', $invoice_date, array('required' => 'true')); ?>
								</div>
								<div class="col-md-6 pad_left_0">
									<?php $transactionid = (isset($pur_invoice) ? $pur_invoice->transactionid : '');
									echo render_input('transactionid', 'Sales Invoice Number', $transactionid); ?>
								</div>
								

								<div id="recurring_div" class="<?php if (isset($pur_invoice) && $pur_invoice->pur_order != null) {
																	echo 'hide';
																} ?>">

									

									<div id="cycles_wrapper" class="<?php if (!isset($pur_invoice) || (isset($pur_invoice) && $pur_invoice->recurring == 0)) {
																		echo ' hide';
																	} ?>">
										<div class="col-md-12 pad_left_0 pad_right_0">
											<?php $value = (isset($pur_invoice) ? $pur_invoice->cycles : 0); ?>
											<div class="form-group recurring-cycles">
												<label for="cycles"><?php echo _l('recurring_total_cycles'); ?>
													<?php if (isset($pur_invoice) && $pur_invoice->total_cycles > 0) {
														echo '<small>' . _l('cycles_passed', $pur_invoice->total_cycles) . '</small>';
													}
													?>
												</label>
												<div class="input-group">
													<input type="number" class="form-control" <?php if ($value == 0) {
																									echo ' disabled';
																								} ?> name="cycles" id="cycles" value="<?php echo $value; ?>" <?php if (isset($pur_invoice) && $pur_invoice->total_cycles > 0) {
																																																		echo 'min="' . ($pur_invoice->total_cycles) . '"';
																																																	} ?>>
													<div class="input-group-addon">
														<div class="checkbox">
															<input type="checkbox" <?php if ($value == 0) {
																						echo ' checked';
																					} ?> id="unlimited_cycles">
															<label for="unlimited_cycles"><?php echo _l('cycles_infinity'); ?></label>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>

								</div>


							</div>

							<div class="col-md-6">
								<div class="col-md-6 pad_left_0">
									<?php
									$currency_attr = array('data-show-subtext' => true, 'required' => true);

									$selected = '';
									foreach ($currencies as $currency) {
										if (isset($pur_invoice) && $pur_invoice->currency != 0) {
											if ($currency['id'] == $pur_invoice->currency) {
												$selected = $currency['id'];
											}
										} else {
											if ($currency['isdefault'] == 1) {
												$selected = $currency['id'];
											}
										}
									}

									?>
									<label for="currency"><span class="text-danger">* </span><?php echo _l('invoice_add_edit_currency') ?></label>
									<?php echo render_select('currency', $currencies, array('id', 'name', 'symbol'), '', $selected, $currency_attr); ?>
								</div>
								
								<div class="col-md-6 pad_right_0">
									<?php $transaction_date = (isset($pur_invoice) ? $pur_invoice->transaction_date : '');
									echo render_date_input('transaction_date', 'Sales Invoice Date', $transaction_date); ?>
								</div>

								<div class="col-md-12 pad_left_0 pad_right_0">
									<div class="attachments">
										<div class="attachment">
											<div class="mbot15">
												<div class="form-group">
													<label for="attachment" class="control-label"><?php echo _l('ticket_add_attachments'); ?></label>
													<div class="input-group">
														<input type="file" extension="<?php echo str_replace('.', '', get_option('ticket_attachments_file_extensions')); ?>" filesize="<?php echo file_upload_max_size(); ?>" class="form-control" name="attachments[0]" accept="<?php echo get_ticket_form_accepted_mimes(); ?>">
														<span class="input-group-btn">
															<button class="btn btn-success add_more_attachments p8-half" data-max="10" type="button"><i class="fa fa-plus"></i></button>
														</span>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

						</div>

						<?php $rel_id = (isset($pur_invoice) ? $pur_invoice->id : false); ?>
						<?php echo render_custom_fields('pur_invoice', $rel_id); ?>

					</div>



					<div class="panel-body mtop10 invoice-item">

						<div class="row">

							<?php
							$base_currency = get_base_currency();

							$po_currency = $base_currency;
							if (isset($pur_invoice) && $pur_invoice->currency != 0) {
								$po_currency = pur_get_currency_by_id($pur_invoice->currency);
							}

							$from_currency = (isset($pur_invoice) && $pur_invoice->from_currency != null) ? $pur_invoice->from_currency : $base_currency->id;
							echo form_hidden('from_currency', $from_currency);

							?>
							
						</div>
						<div class="row">
							<div class="col-md-12">
								<div class="table-responsive s_table ">
									<table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
										<thead>
											<tr>
												<th></th>
												<th width="30%" align="left"><i class="fa fa-exclamation-circle" aria-hidden="true" data-toggle="tooltip" data-title="<?php echo _l('item_description_new_lines_notice'); ?>"></i> <?php echo _l('Name/Title'); ?></th>
												<th width="30%" align="left"><?php echo _l('description'); ?></th>
												<th width="18%" align="right"><?php echo _l('Unit/Quantity '); ?></th>
												<th width="18%" align="right" class="qty"><?php echo _l('Price/Value'); ?></th>
												<th align="center"><i class="fa fa-cog"></i></th>
											</tr>
										</thead>
										<tbody>
											<?php echo $pur_invoice_row_template; ?>
										</tbody>
									</table>
								</div>
							</div>
							<div class="col-md-8 col-md-offset-4">
								<table class="table text-right">
									<tbody>
										<tr id="subtotal">
											<td><span class="bold"><?php echo _l('subtotal'); ?> :</span>
												<?php echo form_hidden('total_mn', ''); ?>
											</td>
											<td class="wh-subtotal">
											</td>
										</tr>

										<tr id="order_discount_percent">
											<td>
												<div class="row">
													<div class="col-md-7">
														<span class="bold"><?php echo _l('pur_discount'); ?> <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="top" title="<?php echo _l('discount_percent_note'); ?>"></i></span>
													</div>
													<div class="col-md-3">
														<?php $discount_total = isset($pur_invoice) ? $pur_invoice->discount_total : '';
														echo render_input('order_discount', '', $discount_total, 'number', ['onchange' => 'pur_calculate_total()', 'onblur' => 'pur_calculate_total()']); ?>
													</div>
													<div class="col-md-2">
														<select name="add_discount_type" id="add_discount_type" class="selectpicker" onchange="pur_calculate_total(); return false;" data-width="100%" data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
															<option value="percent">%</option>
															<option value="amount" selected><?php echo _l('amount'); ?></option>
														</select>
													</div>
												</div>
											</td>
											<td class="order_discount_value">

											</td>
										</tr>

										<tr id="total_discount">
											<td><span class="bold"><?php echo _l('total_discount'); ?> :</span>
												<?php echo form_hidden('dc_total', ''); ?>
											</td>
											<td class="wh-total_discount">
											</td>
										</tr>

										<tr id="totalmoney">
											<td><span class="bold"><?php echo _l('grand_total'); ?> :</span>
												<?php echo form_hidden('grand_total', ''); ?>
											</td>
											<td class="wh-total">
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div id="removed-items"></div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12 mtop15">
							<div class="panel-body bottom-transaction">
								<div class="col-md-12 pad_left_0 pad_right_0">
									<?php $adminnote = (isset($pur_invoice) ? $pur_invoice->adminnote : '');
									echo render_textarea('adminnote', 'adminnote', $adminnote, array('rows' => 7)) ?>
								</div>

								<div class="col-md-12 pad_left_0 pad_right_0">
									<?php $vendor_note = (isset($pur_invoice) ? $pur_invoice->vendor_note : '');
									echo render_textarea('vendor_note', 'vendor_note', $vendor_note, array('rows' => 7)) ?>
								</div>
								<div class="col-md-12 pad_left_0 pad_right_0">
									<?php $terms = (isset($pur_invoice) ? $pur_invoice->terms : '');
									echo render_textarea('terms', 'terms', $terms, array('rows' => 7)) ?>
								</div>

								<?php if (is_admin() || (isset($pur_invoice) && $pur_invoice ? staff_can('edit_al_purchase_invoice', 'd3a_albania') : staff_can('add_al_purchase_invoice', 'd3a_albania'))) { ?>
								<div class="btn-bottom-toolbar text-right">

									<button type="button" class="btn-tr save_detail btn btn-info mleft10 transaction-submit">
										<?php echo _l('submit'); ?>
									</button>
								</div>
								<?php } ?>

							</div>
							<div class="btn-bottom-pusher"></div>
						</div>
					</div>
				</div>

				<?php echo form_close(); ?>
			</div>
		</div>
	</div>

	<?php init_tail(); ?>
	<script>
		$(document).ready(function() {
			if ($('.table.has-calculations tbody tr.item').length > 0) {
				albania_calculate_total();
			}

			$('#pur_invoice-form').on('submit', function(e) {
				albania_calculate_total();
			});
		});

		function pur_vendor_change(selectElement) {
			var vendorId = $(selectElement).val();

			if (vendorId !== '') {
				$.ajax({
					url: admin_url + 'd3a_albania/get_vendor_currency/' + vendorId,
					type: 'GET',
					dataType: 'json',
					success: function(response) {
						if (response.success && response.currency_id) {
							$('select[name="currency"]').selectpicker('val', response.currency_id);
							$('select[name="currency"]').selectpicker('refresh');

							$('select[name="currency"]').trigger('change');
						}
					},
					error: function() {
						console.log('Error fetching vendor currency');
					}
				});
			}
		}
	</script>
	</body>

	</html>
	<?php require 'modules/d3a_albania/assets/js/albania_invoice_js.php'; ?>