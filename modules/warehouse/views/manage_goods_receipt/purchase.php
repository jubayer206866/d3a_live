<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12" id="small-table">
				<div class="panel_s">
					<?php echo form_open_multipart(admin_url('warehouse/manage_goods_receipt'), array('id' => 'add_goods_receipt')); ?>
					<div class="panel-body">
						<div class="row">
							<div class="col-md-12">
								<h4 class="no-margin font-bold"><i class="fa fa-clone menu-icon menu-icon"
										aria-hidden="true"></i> <?php echo _l($title); ?></h4>
								<hr>
							</div>
						</div>

						<?php
						$id = '';
						if (isset($goods_receipt)) {
							$id = $goods_receipt->id;
							echo form_hidden('isedit');
						}
						?>

						<input type="hidden" name="id" value="<?php echo new_html_entity_decode($id); ?>">
						<input type="hidden" name="save_and_send_request" value="false">

						<!-- start-->
						<div class="row">
							<div class="col-md-6">
								<?php $goods_receipt_code = isset($goods_receipt) ? $goods_receipt->goods_receipt_code : (isset($goods_code) ? $goods_code : ''); ?>
								<?php echo render_input('goods_receipt_code', 'stock_received_docket_number', $goods_receipt_code, '', array('disabled' => 'true')) ?>
							</div>
							<div class="col-md-3">
								<?php $date_c = isset($goods_receipt) ? $goods_receipt->date_c : $current_day ?>
								<?php echo render_date_input('date_c', 'accounting_date', _d($date_c)) ?>
							</div>
							<div class="col-md-3">
								<?php $date_add = isset($goods_receipt) ? $goods_receipt->date_add : $current_day ?>
								<?php echo render_date_input('date_add', 'day_vouchers', _d($date_add)) ?>
							</div>



							<div class="col-md-6 <?php if ($pr_orders_status == false) {
								echo 'hide';
							}
							; ?>">
								<div class="form-group">
									<label for="supplier_code"><?php echo _l('supplier_name'); ?></label>
									<select name="supplier_code" id="supplier_code" class="selectpicker"
										data-live-search="true" data-width="100%"
										data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>

										<?php if (isset($vendors)) { ?>
											<?php foreach ($vendors as $s) { ?>
												<option value="<?php echo new_html_entity_decode($s['userid']); ?>" <?php if (isset($goods_receipt) && $goods_receipt->supplier_code == $s['userid']) {
													   echo 'selected';
												   } ?>><?php echo new_html_entity_decode($s['company']); ?>
												</option>
											<?php } ?>
										<?php } ?>

									</select>
								</div>
							</div>

							<div class="col-md-6 <?php if ($pr_orders_status == true) {
								echo 'hide';
							}
							; ?>">

								<?php $supplier_name = isset($goods_receipt) ? $goods_receipt->supplier_name : '' ?>
								<?php
								echo render_input('supplier_name', 'supplier_name', $supplier_name) ?>
							</div>

							<div class=" col-md-3">
								<div class="form-group">
									<label for="buyer_id" class="control-label">Person in charge</label>
									<select name="buyer_id" class="selectpicker" id="buyer_id" data-width="100%"
										data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
										<option value=""></option>
										<?php foreach ($staff as $s) { ?>
											<option value="<?php echo new_html_entity_decode($s['staffid']); ?>" <?php if (isset($goods_receipt) && ($goods_receipt->buyer_id == $s['staffid'])) {
												   echo 'selected';
											   } ?>>
												<?php echo new_html_entity_decode($s['firstname'] . '' . $s['lastname']); ?>
											</option>
										<?php } ?>
									</select>
								</div>
							</div>

							<?php if (ACTIVE_PROPOSAL == true) { ?>
								<div class="col-md-3 form-group <?php if ($pr_orders_status == false) {
									echo 'hide';
								}
								; ?>">
									<label for="project"><?php echo _l('project'); ?></label>
									<select name="project" id="project" class="selectpicker" data-live-search="true"
										data-width="100%"
										data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>

										<?php if (isset($projects)) { ?>
											<?php foreach ($projects as $s) { ?>
												<option value="<?php echo new_html_entity_decode($s['id']); ?>" <?php if (isset($goods_receipt) && $s['id'] == $goods_receipt->project) {
													   echo 'selected';
												   } ?>><?php echo new_html_entity_decode($s['name']); ?></option>
											<?php } ?>
										<?php } ?>
									</select>
								</div>

								<div class="col-md-3 form-group hide">
									<label for="type"><?php echo _l('type_label'); ?></label>
									<select name="type" id="type" class="selectpicker" data-live-search="true"
										data-width="100%"
										data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<option value="capex" <?php if (isset($goods_receipt) && $goods_receipt->type == 'capex') {
											echo 'selected';
										} ?>><?php echo _l('capex'); ?>
										</option>
										<option value="opex" <?php if (isset($goods_receipt) && $goods_receipt->type == 'opex') {
											echo 'selected';
										} ?>><?php echo _l('opex'); ?>
										</option>
									</select>
								</div>

								<div class="col-md-3 form-group hide">
									<label for="department"><?php echo _l('department'); ?></label>
									<select name="department" id="department" class="selectpicker" data-live-search="true"
										data-width="100%"
										data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php if (isset($departments)) { ?>
											<?php foreach ($departments as $s) { ?>
												<option value="<?php echo new_html_entity_decode($s['departmentid']); ?>" <?php if (isset($goods_receipt) && $s['departmentid'] == $goods_receipt->department) {
													   echo 'selected';
												   } ?>><?php echo new_html_entity_decode($s['name']); ?></option>
											<?php } ?>

										<?php } ?>

									</select>
								</div>

								<div class="col-md-3 form-group hide">
									<label for="requester"><?php echo _l('requester'); ?></label>
									<select name="requester" id="requester" class="selectpicker" data-live-search="true"
										data-width="100%"
										data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
										<option value=""></option>
										<?php if (isset($staffs)) { ?>
											<?php foreach ($staffs as $s) { ?>
												<option value="<?php echo new_html_entity_decode($s['staffid']); ?>" <?php if (isset($goods_receipt) && $s['staffid'] == $goods_receipt->requester) {
													   echo 'selected';
												   } ?>>
													<?php echo new_html_entity_decode($s['lastname'] . ' ' . $s['firstname']); ?>
												</option>
											<?php } ?>
										<?php } ?>
									</select>
								</div>

							<?php } ?>

							<div class=" col-md-3 hide">
								<?php $deliver_name = (isset($goods_receipt) ? $goods_receipt->deliver_name : '');
								echo render_input('deliver_name', 'deliver_name', $deliver_name) ?>
							</div>

							<div class="col-md-3 ">
								<?php $warehouse_id_value = (isset($goods_receipt) ? $goods_receipt->warehouse_id : ''); ?>
								<a href="#" class="pull-right display-block input_method"><i
										class="fa fa-question-circle skucode-tooltip" data-toggle="tooltip" title=""
										data-original-title="<?php echo _l('goods_receipt_warehouse_tooltip'); ?>"></i></a>
								<?php echo render_select('warehouse_id_m', $warehouses, array('warehouse_id', 'warehouse_name'), 'warehouse_name', $warehouse_id_value); ?>
							</div>

							<?php if (ACTIVE_PROPOSAL == true) { ?>
								<div class="col-md-3 <?php if ($pr_orders_status == false) {
									echo 'hide';
								}
								; ?>">
									<?php $expiry_date = isset($goods_receipt) ? $goods_receipt->expiry_date : $current_day ?>
									<?php echo render_date_input('expiry_date_m', 'expiry_date', _d($expiry_date)) ?>
								</div>
							<?php } ?>
							<div class="col-md-3 form-group hide">
								<?php $invoice_no = (isset($goods_receipt) ? $goods_receipt->invoice_no : '');
								echo render_input('invoice_no', 'invoice_no', $invoice_no) ?>
							</div>
						</div>
					</div>
					<div class="panel-body mtop10 invoice-item">
						<!--
						<div class="row">
							<div class="col-md-4">
								<?php //$this->load->view('warehouse/item_include/main_item_select'); 
								?>
							</div>
							<div class="col-md-8 text-right">
								<label class="bold mtop10 text-right" data-toggle="tooltip" title=""
									data-original-title="<?php echo _l('support_barcode_scanner_tooltip'); ?>"><?php echo _l('support_barcode_scanner'); ?>
									<i class="fa fa-question-circle i_tooltip"></i></label>
							</div>
						</div>
							-->
						<div class="table-responsive s_table ">
							<table
								class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
								<thead>
									<tr>
										<th></th>

										<th width="15%" align="left"><i class="fa fa-exclamation-circle"
												aria-hidden="true" data-toggle="tooltip"
												data-title="Product Code"></i><?php echo _l('product_code'); ?></th>
										<th width="15%" align="left"><?php echo _l('product_name'); ?></th>

										<th width="10%" align="right">Received Cartons</th>

										<th width="10%" align="right">Delivered Cartons</th>
										<th width="10%" align="right" class="qty"><?php echo _l('pieces_carton'); ?>
										</th>

										<th width="10%" align="right"><?php echo _l('price'); ?></th>

										<th width="10%" align="right"><i class="fa fa-exclamation-circle"
												aria-hidden="true" data-toggle="tooltip"
												data-title="<?php echo _l('gross_weight'); ?>"></i>G.W</th>


										<th width="10%" align="right"><i class="fa fa-exclamation-circle"
												aria-hidden="true" data-toggle="tooltip"
												data-title="<?php echo _l('net_weight'); ?>"></i>N.W</th>

										<th width="10%" align="right"><?php echo _l('cbm'); ?></th>

									</tr>
								</thead>
								<tbody>
									<?php echo new_html_entity_decode($goods_receipt_row_template);
									?>
								</tbody>
							</table>
						</div>

						<div class="row pull-right mbot10">
							<div class="col-md-12 ">
								<table class="table ">
									<tbody>
									<tbody>
										<tr id="cartons">
											<td><span class="bold"><?php echo _l('cartons'); ?></span>
											</td>
											<td class="cartons">
												<?php echo ($cartons); ?>
											</td>
										</tr>
										<tr id="total_pieces">
											<td><span class="bold"><?php echo _l('total_pieces'); ?></span>
											</td>
											<td class="total_pieces">
												<?php echo ($totalPieces); ?>
											</td>
										</tr>

										<tr id="price_total">
											<td><span class="bold"><?php echo _l('price_total'); ?></span></td>
											<td class="price_total">
												<?php echo ($get_base_currency->symbol . '¥' . number_format(clean_number($Pricetotal), 2)); ?>
												<input type="hidden" name="total_goods_money" id="price_total_input"
													value="<?php echo ($Pricetotal); ?>">
											</td>
										</tr>


										<tr id="total_net_weight">
											<td><span class="bold"><?php echo _l('total_net_weight'); ?></span>
											</td>
											<td class="total_net_weight">
												<?php echo clean_number($Total_net_weight); ?>
											</td>
										</tr>
										<tr id="total_gross_weight">
											<td><span class="bold"><?php echo _l('total_gross_weight'); ?></span>
											</td>
											<td class="total_gross_weight">
												<?php echo clean_number($Total_gross_weight); ?>
											</td>
										</tr>
										<tr id="total_cbm">
											<td><span class="bold"><?php echo _l('total_cbm'); ?></span>
											</td>
											<td class="total_cbm">
												<?php echo clean_number($Total_cbm); ?>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div id="removed-items"></div>
					</div>

					<div class="row">
						<div class="col-md-12 mtop15">
							<div class="panel-body ">
								<?php $description = (isset($goods_receipt) ? $goods_receipt->description : ''); ?>
								<?php echo render_textarea('description', 'note', $description, array(), array(), 'mtop15'); ?>

								<div class="btn-bottom-toolbar text-right">
									<a href="<?php echo admin_url('warehouse/manage_purchase'); ?>"
										class="btn btn-default text-right mright5"><?php echo _l('close'); ?></a>

									<?php if (wh_check_approval_setting('1') != false) { ?>
										<?php if (isset($goods_receipt) && $goods_receipt->approval != 1) { ?>
											<a href="javascript:void(0)"
												class="btn btn-info pull-right mright5 add_goods_receipt_send"><?php echo _l('save_send_request'); ?></a>
										<?php } elseif (!isset($goods_receipt)) { ?>
											<a href="javascript:void(0)"
												class="btn btn-info pull-right mright5 add_goods_receipt_send"><?php echo _l('save_send_request'); ?></a>
										<?php } ?>
									<?php } ?>

									<?php if (is_admin() || has_permission('wh_stock_import', '', 'edit') || has_permission('wh_stock_import', '', 'create')) { ?>
										<?php if (isset($goods_receipt)) { ?>
											<a href="javascript:void(0)"
												class="btn btn-info pull-right mright5 add_goods_receipt"><?php echo _l('submit'); ?></a>
										<?php } elseif (!isset($goods_receipt)) { ?>
											<a href="javascript:void(0)"
												class="btn btn-info pull-right mright5 add_goods_receipt"><?php echo _l('submit'); ?></a>
										<?php } ?>
									<?php } ?>
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
</div>
</div>
</div>
<div id="modal_wrapper"></div>


<?php init_tail(); ?>
<?php require 'modules/warehouse/assets/js/purchase_js.php'; ?>
</body>

</html>