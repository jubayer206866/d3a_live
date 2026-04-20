<?php init_head(); ?>


<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12" id="small-table">
        <div class="panel_s">
          <div class="panel-body">
            <?php echo form_hidden('proposal_id', $proposal_id); ?>
            <div class="row">
              <div class="col-md-12">
                <h4 class="no-margin font-bold"><i class="fa fa-clone menu-icon menu-icon" aria-hidden="true"></i>
                  <?php echo _l($title); ?></h4>
                <br>

              </div>
            </div>

            <div class="row row-margin-bottom">
              <div class="col-md-12 ">
                <?php if (has_permission('warehouse_item', '', 'create') || is_admin() || has_permission('warehouse_item', '', 'edit')) { ?>


                  <a href="#" onclick="new_commodity_item(); return false;"
                    class="btn btn-info pull-left display-block mr-4 button-margin-r-b" data-toggle="sidebar-right"
                    data-target=".commodity_list-add-edit-modal">
                    <?php echo _l('add'); ?>
                  </a>

                  <a href="<?php echo admin_url('warehouse/import_xlsx_commodity'); ?>"
                    class="btn btn-success pull-left display-block  mr-4 button-margin-r-b"
                    title="<?php echo _l('import_items') ?> ">
                    <?php echo _l('import_items'); ?>
                  </a>
                <?php } ?>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2">
                <div class="form-group">
                  <?php
                  // Define static vendor types
                  $static_price = [
                    ['id' => 'high_to_low', 'name' => 'High to Low'],
                    ['id' => 'low_to_high', 'name' => 'Low to High'],
                  ];

                  // Merge static and dynamic vendor types
                  $vendor_price = array_merge($static_price);

                  ?>
                  <label for="price"><?php echo _l('report_Filter_by_price'); ?></label>
                  <select id="price" name="price" class="selectpicker" data-width="100%"
                    title="<?php echo _l('invoice_status_report_all'); ?>">
                    <?php foreach ($vendor_price as $price) { ?>
                      <option value="<?php echo $price['id']; ?>"><?php echo $price['name']; ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label for="product_code"><?php echo _l('report_product_code'); ?></label>
                  <input type="text" id="product_code" class="form-control" placeholder="Enter product code"
                    name="product_code">
                </div>
              </div>

              <div class="col-md-2">
                <div class="form-group">
                  <label for="vendor"><?php echo _l('vendor'); ?></label>
                  <select id="vendor" class="form-control" name="vendor">
                    <option value=""><?php echo _l('select_vendor'); ?></option>
                    <?php foreach ($vendors as $vendor) { ?>
                      <option value="<?php echo $vendor->userid; ?>">
                        <?php echo htmlspecialchars($vendor->company); ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
              </div>


              <div class="col-md-2">
                <div class="form-group">
                  <label for="city"><?php echo _l('vendor_city'); ?></label>
                  <input type="text" id="city" class="form-control" placeholder="Enter city" name="city">
                </div>
              </div>

              <div class="col-md-2">
                <div class="form-group">
                  <label for="category"><?php echo _l('vendor_category'); ?></label>
                  <select id="category" name="category" class="selectpicker" multiple data-width="100%"
                    data-none-selected-text="<?php echo _l('invoice_status_report_all'); ?>">
                    <?php foreach ($vendor_categorys as $category) { ?>
                      <option value="<?php echo $category['id']; ?>"><?php echo $category['category_name']; ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <?php
                  // Define static vendor types
                  $static_vendor_types = [
                    ['id' => 'Individuals', 'name' => 'Individuals'],
                    ['id' => 'Trading Company', 'name' => 'Trading Company'],
                    ['id' => 'Wholesaler', 'name' => 'Wholesaler'],
                    ['id' => 'Factory Direct', 'name' => 'Factory Direct']
                  ];

                  // Merge static and dynamic vendor types
                  $vendor_type = array_merge($static_vendor_types);

                  ?>
                  <label for="vendor_type"><?php echo _l('report_vendor_type'); ?></label>
                  <select id="vendor_type" name="vendor_type" class="selectpicker" multiple data-width="100%"
                    data-none-selected-text="<?php echo _l('invoice_status_report_all'); ?>">
                    <?php foreach ($vendor_type as $type) { ?>
                      <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>



            <div class="row">
              <!-- view/manage -->
              <div class="modal bulk_actions" id="table_commodity_list_bulk_actions" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                          aria-hidden="true">&times;</span></button>
                      <h4 class="modal-title"><?php echo _l('bulk_actions'); ?></h4>
                    </div>
                    <div class="modal-body">
                      <div class="checkbox checkbox-danger">
                        <div class="row">

                          <?php if (has_permission('warehouse_item', '', 'delete') || is_admin()) { ?>
                            <div class="col-md-4">
                              <div class="form-group">
                                <input type="checkbox" name="mass_delete" id="mass_delete">
                                <label for="mass_delete"><?php echo _l('mass_delete'); ?></label>
                              </div>
                            </div>
                          <?php } ?>


                        </div>

                        <div class="row">
                          <?php if (has_permission('warehouse_item', '', 'create') || is_admin()) { ?>
                            <div class="col-md-4">
                              <div class="form-group">
                                <input type="checkbox" name="clone_items" id="clone_items">
                                <label for="clone_items"><?php echo _l('clone_this_items'); ?></label>
                              </div>
                            </div>
                          <?php } ?>

                        </div>

                        <?php if (has_permission('warehouse_item', '', 'edit') || is_admin()) { ?>
                          <div class="row">
                            <div class="col-md-5">
                              <div class="form-group">

                                <input type="checkbox" name="change_item_selling_price" id="change_item_selling_price">
                                <label
                                  for="change_item_selling_price"><?php echo _l('change_item_selling_price'); ?></label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">

                                <div class="input-group" id="discount-total">
                                  <input type="number" class="form-control text-right" min="0" max="100"
                                    name="selling_price" value="">
                                  <div class="input-group-addon">
                                    <div class="dropdown">
                                      <span class="discount-type-selected">
                                        %
                                      </span>
                                    </div>
                                  </div>
                                </div>
                              </div>

                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-5">
                              <div class="form-group">

                                <input type="checkbox" name="change_item_purchase_price" id="change_item_purchase_price">
                                <label
                                  for="change_item_purchase_price"><?php echo _l('change_item_purchase_price'); ?></label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">

                                <div class="input-group" id="discount-total">
                                  <input type="number" class="form-control text-right" min="0" max="100"
                                    name="b_purchase_price" value="">
                                  <div class="input-group-addon">
                                    <div class="dropdown">
                                      <span class="discount-type-selected">
                                        %
                                      </span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        <?php } ?>

                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-default"
                        data-dismiss="modal"><?php echo _l('close'); ?></button>

                      <?php if (has_permission('warehouse_item', '', 'delete') || is_admin()) { ?>
                        <a href="#" class="btn btn-info"
                          onclick="warehouse_delete_bulk_action(this); return false;"><?php echo _l('confirm'); ?></a>
                      <?php } ?>
                    </div>
                  </div>

                </div>

              </div>

              <!-- update multiple item -->

              <div class="modal export_item" id="table_commodity_list_export_item" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h4 class="modal-title"><?php echo _l('export_item'); ?></h4>
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                          aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                      <?php if (has_permission('warehouse_item', '', 'create') || is_admin()) { ?>
                        <div class="checkbox checkbox-danger">
                          <input type="checkbox" name="mass_delete" id="mass_delete">
                          <label for="mass_delete"><?php echo _l('mass_delete'); ?></label>
                        </div>

                      <?php } ?>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-default"
                        data-dismiss="modal"><?php echo _l('close'); ?></button>

                      <?php if (has_permission('warehouse_item', '', 'create') || is_admin()) { ?>
                        <a href="#" class="btn btn-info"
                          onclick="warehouse_delete_bulk_action(this); return false;"><?php echo _l('confirm'); ?></a>
                      <?php } ?>
                    </div>
                  </div>

                </div>

              </div>

              <!-- print barcode -->
              <div class="col-md-12">
                <?php
                $table_data = array(
                  _l('_images'),
                  _l('product_code'),
                  _l('product_name'),
                  _l('cartons'),
                  _l('pieces_carton'),
                  _l('total_pieces'),
                  _l('price'),
                  _l('price_total'),
                  _l('net_weight'),
                  _l('total_net_weight'),
                  _l('gross_weight'),
                  _l('total_gross_weight'),
                  _l('cbm'),
                  _l('total_cbm'),
                );

                $cf = get_custom_fields('items', array('show_on_table' => 1));
                foreach ($cf as $custom_field) {
                  array_push($table_data, $custom_field['name']);
                }

                render_datatable(
                  $table_data,
                  'table_commodity_list',
                  array('customizable-table'),
                  array(
                    'proposal_sm' => 'proposal_sm',
                    'id' => 'table-table_commodity_list',
                    'data-last-order-identifier' => 'table_commodity_list',
                    'data-default-order' => get_table_last_order('table_commodity_list'),
                  )
                ); ?>

              </div>
            </div>


          </div>
        </div>
      </div>
      <div class="col-md-7 small-table-right-col">
        <div id="proposal_sm_view" class="hide">
        </div>
      </div>
    </div>
  </div>

</div>


<div class="modal" id="warehouse_type" tabindex="-1" role="dialog">
  <div class="modal-dialog ht-dialog-width">

    <?php echo form_open_multipart(admin_url('warehouse/add_commodity_list'), array('id' => 'add_warehouse_type')); ?>
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>

        <h4 class="modal-title">
          <span class="add-title"><?php echo _l('add'); ?></span>
        </h4>

      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12">
            <div id="warehouse_type_id">
            </div>
            <div class="form">
              <div class="col-md-12" id="add_handsontable">
              </div>
              <?php echo form_hidden('hot_warehouse_type'); ?>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button id="latch_assessor" type="button" class="btn btn-info intext-btn"
          onclick="add_warehouse_type(this); return false;"><?php echo _l('submit'); ?></button>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>


<!-- add one commodity list sibar start-->

<div class="modal" id="commodity_list-add-edit" tabindex="-1" role="dialog">
  <div class="modal-dialog ht-dialog-width">

    <?php echo form_open_multipart(admin_url('warehouse/commodity_list_add_edit'), array('class' => 'commodity_list-add-edit', 'autocomplete' => 'off')); ?>

    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
        <h4 class="modal-title">
          <span class="edit-commodity-title"><?php echo _l('edit_item'); ?></span>
          <span class="add-commodity-title"><?php echo _l('add_item'); ?></span>
        </h4>
      </div>

      <div class="modal-body">
        <div id="commodity_item_id"></div>




        <div class="tab-content">

          <!-- interview process start -->
          <div role="tabpanel" class="tab-pane active" id="interview_infor">


            <div class="row">
              <div class="col-md-6">
                <?php echo render_input('commodity_code', 'commodity_code'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('long_description', 'commodity_name'); ?>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <?php echo render_input('koli', 'koli'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('cope_koli', 'cope_koli'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('total_koli', 'total_koli'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('price_total', 'price_total'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('gross_weight', 'gross_weight'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('total_gross_weight', 'total_gross_weight'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('net_weight', 'net_weight'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('total_net_weight', 'total_net_weight'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('cbm_koli', 'cbm_koli'); ?>
              </div>
              <div class="col-md-6">
                <?php echo render_input('total_cbm', 'total_cbm'); ?>
              </div>
            </div>








            <div class="row">
              <div class="col-md-6">

                <?php
                $attr = array();
                //$attr = ['data-type' => 'currency'];
                echo render_input('purchase_price', 'purchase_price', '', 'number', $attr); ?>

              </div>
              <div class="col-md-6">

                <?php $premium_rates = isset($premium_rates) ? $premium_rates : '' ?>
                <?php
                $attr = array();
                //$attr = ['data-type' => 'currency'];
                echo render_input('rate', 'rate', '', 'number', $attr); ?>


              </div>
            </div>

            <?php if (!isset($expense) || (isset($expense) && $expense->attachment == '')) { ?>
              <div id="dropzoneDragArea" class="dz-default dz-message">
                <span><?php echo _l('attach_images'); ?></span>
              </div>
              <div class="dropzone-previews"></div>
            <?php } ?>

            <div id="images_old_preview">

            </div>


          </div>




        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close') ?></button>
        <button type="submit" class="btn btn-info submit_btn"><?php echo _l('save'); ?></button>
      </div>
    </div>

  </div>
</div>
<?php echo form_close(); ?>

<!-- add one commodity list sibar end -->
<div class="modal fade" id="show_detail" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">
          <span class="add-title"></span>
        </h4>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="horizontal-scrollable-tabs preview-tabs-top col-md-12">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
              <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                <li role="presentation" class="active">
                  <a href="#out_of_stock" aria-controls="out_of_stock" role="tab" id="tab_out_of_stock"
                    data-toggle="tab">
                    <?php echo _l('out_of_stock') ?>
                  </a>
                </li>
                <li role="presentation">
                  <a href="#expired" aria-controls="expired" role="tab" id="tab_expired" data-toggle="tab">
                    <?php echo _l('expired') ?>
                  </a>
                </li>
              </ul>
            </div>
          </div>

          <div class="tab-content col-md-12">
            <div role="tabpanel" class="tab-pane active row" id="out_of_stock">
              <div class="col-md-12">
                <?php render_datatable(array(
                  _l('id'),
                  _l('commodity_name'),
                  _l('expiry_date'),
                  _l('lot_number'),
                  _l('quantity'),


                ), 'table_out_of_stock'); ?>
              </div>
            </div>

            <div role="tabpanel" class="tab-pane row" id="expired">
              <div class="col-md-12">
                <?php render_datatable(array(
                  _l('id'),
                  _l('commodity_name'),
                  _l('expiry_date'),
                  _l('lot_number'),
                  _l('quantity'),

                ), 'table_expired'); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
      </div>
    </div>
  </div>
</div>
<?php echo form_hidden('warehouse_id'); ?>
<?php echo form_hidden('commodity_id'); ?>
<?php echo form_hidden('expiry_date'); ?>
<?php echo form_hidden('parent_item_filter', 'true'); ?>
<?php echo form_hidden('filter_all_simple_variation_value'); ?>


<div id="modal_wrapper"></div>

<?php init_tail(); ?>
<?php require 'modules/warehouse/assets/js/commodity_list_js.php'; ?>
</body>

</html>