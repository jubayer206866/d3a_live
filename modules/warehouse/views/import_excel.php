<?php defined('BASEPATH') or exit('No direct script access allowed');
?>
<?php
$file_header = array();
$file_header[] = "Barcode 条形码";
$file_header[] = "Image 产品图片";
$file_header[] = "Product Code 产品编号";
$file_header[] = "Product Name 产品名称";
$file_header[] = "Cartons 箱数";
$file_header[] = "Pieces/Carton 装箱量";
$file_header[] = "Total/Pieces 总量";
$file_header[] = "Price 单价";
$file_header[] = "Price/Total 总价";
$file_header[] = "Gross Weight 毛重";
$file_header[] = "Total Gross Weight 总重";
$file_header[] = "Net Weight 净重";
$file_header[] = "Total Net Weight 总净重";
$file_header[] = "CBM 体积";
$file_header[] = "Total CBM 总体积";
?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
						<h4><?php echo _l('Import_product') ?></h4>
						<?php if (!isset($simulate)) { ?>
							<ul>
								<li class="text-danger">1. * indicates required fields , Column "Product Code", "Image", "Product Name", "Cartons", "Pieces/Carton", "Total/Pieces", "Price"</li>
							</ul>
							<div class="table-responsive no-dt">
								<table class="table table-hover table-bordered">
									<thead>
										<tr>
											<?php
											$total_fields = 0;
											for ($i = 0; $i < count($file_header); $i++) { ?>
												<?php if ($i ==1 || $i == 2 || $i == 3 || $i== 7 || $i==13) { ?>
													<th class="bold"><span class="text-danger">*</span>
														<?php echo new_html_entity_decode($file_header[$i]) ?> </th>
												<?php } else{?>
													<th class="bold">
													<?php echo new_html_entity_decode($file_header[$i]) ?> </th>
												<?php } ?>
												<?php

												$total_fields++;
											} ?>

										</tr>
									</thead>
									<tbody>
										<?php for ($i = 0; $i < 1; $i++) {
											echo '<tr>';
											for ($x = 0; $x < count($file_header); $x++) {
												echo '<td>- </td>';
											}
											echo '</tr>';
										}
										?>
									</tbody>
								</table>
							</div>
							<hr>

						<?php } ?>

						<div class="row">
							<div class="col-md-4">
								<?php echo form_open_multipart(admin_url('hrm/import_job_p_excel'), array('id' => 'import_form')); ?>
								<?php echo form_hidden('leads_import', 'true'); ?>
								<?php echo render_input('file_csv', 'choose_excel_file', '', 'file'); ?>
								<div class="form-group">
									<label for="vendor"><?php echo _l('vendor'); ?></label>
									<select name="vendor" id="vendor" class="selectpicker" data-live-search="true"
										data-width="100%">
										<option value=""></option>
										<?php foreach ($vendors as $vendor) { ?>
											<option value="<?php echo new_html_entity_decode($vendor['userid']); ?>">
												<?php echo new_html_entity_decode($vendor['company']); ?>
											</option>
										<?php } ?>
									</select>
								</div>

								<div class="form-group">
									<button id="uploadfile" type="button" class="btn btn-info import"
										onclick="return uploadfilecsv(this);"><?php echo _l('import'); ?></button>
								</div>
								<?php echo form_close(); ?>
							</div>
							<div class="col-md-8">
								<div class="form-group" id="file_upload_response">

								</div>

							</div>
						</div>

					</div>
				</div>
			</div>
			<!-- box loading -->
			<div id="box-loading">
			</div>
		</div>
		<?php
		$file_header = array();
		$file_header[] = _l('parent_id');
		$file_header[] = _l('attributes');
		?>

	

	</div>
</div>
<?php init_tail(); ?>

<?php require 'modules/warehouse/assets/js/import_excel_js.php'; ?>
</body>

</html>