<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">

			<?php echo form_open_multipart(admin_url('purchase/pur_invoice_form'), array('id' => 'pur_invoice-form', 'class' => '_pur_invoice_form _transaction_form')); ?>
			<?php
			if (isset($pur_order)) {
				echo form_hidden('isedit');
			}
			?>
			<div class="col-md-12">
				<div class="panel_s accounting-template estimate">
					<div class="panel-body">

						<div class="row">
							<div class="col-md-12">
								<h4 class="no-margin font-bold"><i class="fa <?php if (isset($pur_invoice)) {
									echo 'fa-pencil-square';
								} else {
									echo 'fa-plus';
								} ?>" aria-hidden="true"></i> <?php echo _l($title); ?>
									<?php if (isset($pur_invoice)) {
										echo ' ' . pur_html_entity_decode($pur_invoice->invoice_number);
									} ?>
								</h4>
								<hr />
							</div>
						</div>

						<div class="row">
							<?php $additional_discount = 0; ?>
							<input type="hidden" name="additional_discount"
								value="<?php echo pur_html_entity_decode($additional_discount); ?>">
							<div class="col-md-6">
								<?php echo form_hidden('id', (isset($pur_invoice) ? $pur_invoice->id : '')); ?>
								<div class="col-md-6 pad_left_0 hide">
									<label for="invoice_number"><span class="text-danger">*
										</span><?php echo _l('invoice_code'); ?></label>
									<?php
									$prefix = get_purchase_option('pur_inv_prefix');
									$next_number = get_purchase_option('next_inv_number');
									$number = (isset($pur_invoice) ? $pur_invoice->number : $next_number);
									echo form_hidden('number', $number); ?>

									<?php $invoice_number = (isset($pur_invoice) ? $pur_invoice->invoice_number : $prefix . str_pad($next_number, 5, '0', STR_PAD_LEFT));
									echo render_input('invoice_number', '', $invoice_number, 'text', array('readonly' => '', 'required' => 'true')); ?>
								</div>

								<div class="col-md-6 pad_right_0">
									<?php $vendor_invoice_number = ((isset($pur_invoice) && $pur_invoice->vendor_invoice_number != '') ? $pur_invoice->vendor_invoice_number : $invoice_number);
									echo render_input('vendor_invoice_number', 'invoice_number', $vendor_invoice_number, 'text', array()); ?>
								</div>

								<div class="col-md-6 pad_left_0 form-group">
									<label for="vendor"><span class="text-danger">*
										</span><?php echo _l('pur_vendor'); ?></label>
									<select name="vendor" id="vendor" class="selectpicker"
										onchange="pur_vendor_change(this); return false;" required="true"
										data-live-search="true" data-width="100%"
										data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php foreach ($vendors as $ven) { ?>
											<option value="<?php echo pur_html_entity_decode($ven['userid']); ?>" <?php if (isset($pur_invoice) && $pur_invoice->vendor == $ven['userid']) {
												   echo 'selected';
											   } ?>>
												<?php echo pur_html_entity_decode($ven['vendor_code'] . ' ' . $ven['company']); ?>
											</option>
										<?php } ?>
									</select>
								</div>

								<div class="col-md-6 form-group pad_right_0 hide">
									<label for="contract"><?php echo _l('contract'); ?></label>
									<select name="contract" id="contract" class="selectpicker"
										onchange="contract_change(this); return false;" data-live-search="true"
										data-width="100%"
										data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php foreach ($contracts as $ct) { ?>
											<option value="<?php echo pur_html_entity_decode($ct['id']); ?>" <?php if (isset($pur_invoice) && $pur_invoice->contract == $ct['id']) {
												   echo 'selected';
											   } ?>>
												<?php echo pur_html_entity_decode($ct['contract_number']); ?>
											</option>
										<?php } ?>
									</select>
								</div>
								<div class="col-md-6 form-group pad_left_0">
									<label for="pur_order"><?php echo _l('delivery_receive'); ?></label>
									<select name="goods_receipt_id" <?php if (isset($pur_invoice)) {
												   echo 'disabled';
											   } ?> id="goods_receipt_id" class="selectpicker"
										onchange="goods_receipt_change(this); return false;" data-live-search="true"
										data-width="100%"
										data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php foreach ($inventory_receives as $inventory_receive) { ?>
											<option value="<?php echo pur_html_entity_decode($inventory_receive['id']); ?>"
												<?php if (isset($pur_invoice) && $pur_invoice->goods_receipt_id == $inventory_receive['id']) {
													echo 'selected';
												} ?>>
												<?php echo pur_html_entity_decode($inventory_receive['goods_receipt_code']); ?>
											</option>
										<?php } ?>
									</select>
								</div>
					

								<div class="col-md-6 pad_right_0">
									<label for="invoice_date"><span class="text-danger">*
										</span><?php echo _l('invoice_date'); ?></label>
									<?php $invoice_date = (isset($pur_invoice) ? _d($pur_invoice->invoice_date) : _d(date('Y-m-d')));
									echo render_date_input('invoice_date', '', $invoice_date, array('required' => 'true')); ?>
								</div>

								<div class="col-md-6 pad_left_0">
									<label for="invoice_date"><?php echo _l('pur_due_date'); ?></label>
									<?php $duedate = (isset($pur_invoice) ? _d($pur_invoice->duedate) : _d(date('Y-m-d')));
									echo render_date_input('duedate', '', $duedate); ?>
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
													} ?> name="cycles" id="cycles"
														value="<?php echo $value; ?>" <?php if (isset($pur_invoice) && $pur_invoice->total_cycles > 0) {
															   echo 'min="' . ($pur_invoice->total_cycles) . '"';
														   } ?>>
													<div class="input-group-addon">
														<div class="checkbox">
															<input type="checkbox" <?php if ($value == 0) {
																echo ' checked';
															} ?> id="unlimited_cycles">
															<label
																for="unlimited_cycles"><?php echo _l('cycles_infinity'); ?></label>
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
									<label for="currency"><span class="text-danger">*
										</span><?php echo _l('invoice_add_edit_currency') ?></label>
									<?php echo render_select('currency', $currencies, array('id', 'name', 'symbol'), '', $selected, $currency_attr); ?>
								</div>
								<div class="col-md-6 pad_left_0 hide">
									<?php $transactionid = (isset($pur_invoice) ? $pur_invoice->transactionid : '');
									echo render_input('transactionid', 'transaction_id', $transactionid); ?>
								</div>
								<div class="col-md-6 pad_right_0">
									<?php $transaction_date = (isset($pur_invoice) ? $pur_invoice->transaction_date : '');
									echo render_date_input('transaction_date', 'transaction_date', $transaction_date); ?>
								</div>





								<div class="col-md-12 pad_left_0 pad_right_0">
									<div class="attachments">
										<div class="attachment">
											<div class="mbot15">
												<div class="form-group">
													<label for="attachment"
														class="control-label"><?php echo _l('ticket_add_attachments'); ?></label>
													<div class="input-group">
														<input type="file"
															extension="<?php echo str_replace('.', '', get_option('ticket_attachments_file_extensions')); ?>"
															filesize="<?php echo file_upload_max_size(); ?>"
															class="form-control" name="attachments[0]"
															accept="<?php echo get_ticket_form_accepted_mimes(); ?>">
														<span class="input-group-btn">
															<button class="btn btn-success add_more_attachments p8-half"
																data-max="10" type="button"><i
																	class="fa fa-plus"></i></button>
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
							<div class="col-md-8 <?php if ($po_currency->id == $base_currency->id) {
								echo 'hide';
							} ?>" id="currency_rate_div">
								<div class="col-md-10 text-right">

									<p class="mtop10"><?php echo _l('currency_rate'); ?><span
											id="convert_str"><?php echo ' (' . $base_currency->name . ' => ' . $po_currency->name . '): '; ?></span>
									</p>
								</div>
								<div class="col-md-2 pull-right">
									<?php $currency_rate = 1;
									if (isset($pur_invoice) && $pur_invoice->currency != 0) {
										$currency_rate = pur_get_currency_rate($base_currency->name, $po_currency->name);
									}
									echo render_input('currency_rate', '', $currency_rate, 'number', [], [], '', 'text-right');
									?>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<div class="table-responsive s_table ">
									<table
										class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
										<thead>
											<tr>
												<th></th>
												<th width="9%" align="center"><i class="fa fa-exclamation-circle"
														aria-hidden="true" data-toggle="tooltip"
														data-title="Product Code"></i><?php echo _l('product_code'); ?>
												</th>
												<th width="7%" align="center"><?php echo _l('product_name'); ?></th>
												<th width="7%" align="center"><?php echo _l('cartons'); ?></th>
												<th width="7%" align="center" class="qty">
													<?php echo _l('pieces_carton'); ?>
												</th>
												<th width="7%" align="center"><?php echo _l('total_pieces'); ?></th>
												<th width="7%" align="center"><?php echo _l('price'); ?></th>
												<th width="7%" align="center"><?php echo _l('price_total'); ?></th>
												<th width="7%" align="center"><i class="fa fa-exclamation-circle"
														aria-hidden="true" data-toggle="tooltip"
														data-title="<?php echo _l('gross_weight'); ?>"></i>G.W</th>
												<th width="7%" align="center"><i class="fa fa-exclamation-circle"
														aria-hidden="true" data-toggle="tooltip"
														data-title="<?php echo _l('total_gross_weight'); ?>"></i>T.G.W
												</th>
												<th width="7%" align="center"><i class="fa fa-exclamation-circle"
														aria-hidden="true" data-toggle="tooltip"
														data-title="<?php echo _l('net_weight'); ?>"></i>N.W</th>
												<th width="7%" align="center"><i class="fa fa-exclamation-circle"
														aria-hidden="true" data-toggle="tooltip"
														data-title="<?php echo _l('total_net_weight'); ?>"></i>T.N.W
												</th>
												<th width="7%" align="center"><?php echo _l('cbm'); ?></th>
												<th width="7%" align="center"><?php echo _l('total_cbm'); ?></th>
												<th width="7%" align="center"><i class="fa fa-cog"></i></th>
											</tr>
										</thead>
										<tbody>
											<?php echo $pur_order_row_template; ?>
										</tbody>
									</table>
								</div>
							</div>
							<div class="col-md-8 col-md-offset-4">
								<table class="table text-right">
									<tbody>
									<tbody>
										<tr id="cartons">
											<td><?php echo _l('cartons'); ?></span></td>
											<td class="sum_cartons"><?php echo $sum_cartons; ?></td>
										</tr>
										<tr id="total_pieces">
											<td><?php echo _l('Total Pieces'); ?></span></td>
											<td class="sum_total_prices"><?php echo $sum_total_prices; ?></td>
										</tr>
										<tr id="price_total">
											<td><?php echo _l('price_total'); ?></span></td>
											<td class="sum_price_total"><?php echo $sum_price_total; ?></td>
										</tr>
										<tr id="total_gross_weight">
											<td><?php echo _l('total_gross_weight'); ?></span></td>
											<td class="sum_total_gross_weight"><?php echo clean_number($sum_total_gross_weight); ?></td>
										</tr>
										<tr id="total_net_weight">
											<td><?php echo _l('total_net_weight'); ?></span></td>
											<td class="sum_total_net_weight"><?php echo clean_number($sum_total_net_weight); ?></td>
										</tr>
										<tr id="total_cbml">
											<td><?php echo _l('total_cbm'); ?></span></td>
											<td class="sum_total_cbm"><?php echo clean_number($sum_total_cbm); ?></td>
										</tr>
									</tbody>

									</tbody>
								</table>
							</div>

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

								<div class="btn-bottom-toolbar text-right">

									<button type="button"
										class="btn-tr save_detail btn btn-info mleft10 transaction-submit">
										<?php echo _l('submit'); ?>
									</button>
								</div>

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
	</body>

	</html>
	<?php require 'modules/purchase/assets/js/pur_invoice_js.php'; ?>