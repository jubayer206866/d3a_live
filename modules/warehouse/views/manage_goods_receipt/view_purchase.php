<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_hidden('_attachment_sale_id', $goods_receipt->id); ?>
<?php echo form_hidden('_attachment_sale_type', 'estimate'); ?>
<div class="col-md-12 no-padding">
   <div class="panel_s">
      <div class="panel-body">
         <?php if ($goods_receipt->approval == 0) { ?>
            <div class="ribbon info"><span><?php echo _l('not_yet_approve'); ?></span></div>
         <?php } elseif ($goods_receipt->approval == 1) { ?>
            <div class="ribbon success"><span><?php echo _l('approved'); ?></span></div>
         <?php } elseif ($goods_receipt->approval == -1) { ?>
            <div class="ribbon danger"><span><?php echo _l('reject'); ?></span></div>
         <?php } ?>
         <div class="horizontal-scrollable-tabs preview-tabs-top">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
               <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                  <li role="presentation" class="active">
                     <a href="#tab_estimate" aria-controls="tab_estimate" role="tab" data-toggle="tab">
                        <?php echo _l('stock_import'); ?>
                     </a>
                  </li>

                  <li role="presentation">
                     <a href="#tab_tasks"
                        onclick="init_rel_tasks_table(<?php echo new_html_entity_decode($goods_receipt->id); ?>,'stock_import'); return false;"
                        aria-controls="tab_tasks" role="tab" data-toggle="tab">
                        <?php echo _l('tasks'); ?>
                     </a>
                  </li>

                  <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>"
                     class="tab-separator toggle_view">
                     <a href="#" onclick="small_table_full_view(); return false;">
                        <i class="fa fa-expand"></i></a>
                  </li>
               </ul>
            </div>
         </div>

         <div class="clearfix"></div>
         <div class="tab-content">
            <div role="tabpanel" class="tab-pane ptop10 active" id="tab_estimate">
               <div class="row">
                  <div class="col-md-4">

                  </div>
                  <div class="col-md-8">
                     <div class="pull-right _buttons">
                        <?php if (has_permission('wh_stock_import', '', 'edit')) { ?>
                           <a href="<?php echo admin_url('warehouse/edit_purchase/' . $goods_receipt->id); ?>"
                              class="btn btn-default btn-with-tooltip" data-toggle="tooltip"
                              title="<?php echo _l('view'); ?>" data-placement="bottom"><i class="fa fa-eye"></i></a>
                        <?php } ?>

                     </div>

                  </div>
               </div>

               <div id="estimate-preview">

                  <div class="col-md-12 panel-padding">
                     <table class="table border table-striped table-margintop">
                        <tbody>

                           <tr class="project-overview">
                              <td class="bold" width="30%"><?php echo _l('supplier_name'); ?></td>

                              <?php
                              if (get_status_modules_wh('purchase') && ($goods_receipt->supplier_code != '') && ($goods_receipt->supplier_code != 0)) { ?>
                                 <td>
                                    <?php echo new_html_entity_decode(wh_get_vendor_company_name($goods_receipt->supplier_code)); ?>
                                 </td>
                              <?php } else { ?>
                                 <td><?php echo new_html_entity_decode($goods_receipt->supplier_name); ?></td>
                              <?php }

                              ?>

                           </tr>
                           <tr class="project-overview">
                              <td class="bold" width="30%"><?php echo _l('deliver_name'); ?></td>
                              <td><?php echo new_html_entity_decode($goods_receipt->deliver_name); ?></td>
                           </tr>
                           <tr class="project-overview">
                              <td class="bold"><?php echo _l('Person In Charge'); ?></td>
                              <td><?php echo get_staff_full_name($goods_receipt->buyer_id); ?></td>
                           </tr>
                           <tr class="project-overview">
                              <td class="bold"><?php echo _l('customer'); ?></td>
                              <td><?php echo !empty($client_name) ? $client_name : ''; ?></td>
                           </tr>

                           <tr class="project-overview">
                              <td class="bold"><?php echo _l('stock_received_docket_code'); ?></td>
                              <td><?php echo new_html_entity_decode($goods_receipt->goods_receipt_code); ?></td>
                           </tr>

                           <tr class="project-overview">
                              <td class="bold"><?php echo _l('deposit'); ?></td>
                              <td>¥<?php echo number_format($deposit, 2); ?></td>
                           </tr>

                           <tr class="project-overview">
                              <td class="bold"><?php echo _l('note_'); ?></td>
                              <td><?php echo new_html_entity_decode($goods_receipt->description); ?></td>
                           </tr>

                           <?php
                           if (get_status_modules_wh('purchase')) {
                              if (($goods_receipt->pr_order_id != '') && ($goods_receipt->pr_order_id != 0)) { ?>

                                 <tr class="project-overview">
                                    <td class="bold"><?php echo _l('reference_purchase_order'); ?></td>
                                    <td>
                                       <a
                                          href="<?php echo admin_url('purchase/purchase_order/' . $goods_receipt->pr_order_id) ?>"><?php echo get_pur_order_name($goods_receipt->pr_order_id) ?></a>

                                    </td>
                                 </tr>

                              <?php }
                           }
                           ?>

                           <td class="bold"><?php echo _l('print'); ?></td>
                           <td>
                              <div class="btn-group">



                                 <a href="<?php echo admin_url('warehouse/stock_export_pdf/' . $goods_receipt->id); ?>"
                                    target="_blank">
                                    Download PDF
                                 </a>




                           </td>
                           </tr>
                           <tr class="project-overview">
                              <td class="bold"><?php echo _l('invoice_no'); ?></td>
                              <td><?php echo new_html_entity_decode($goods_receipt->invoice_no); ?></td>
                           </tr>


                        </tbody>
                     </table>
                  </div>
                  <div class="row">
                     <div class="col-md-12">
                        <div class="table-responsive">
                           <table class="table items items-preview estimate-items-preview" data-type="estimate">
                              <thead>
                                 <tr>
                                    <th colspan="1">#</th>
                                    <th colspan="1"><?php echo _l('commodity_code') ?></th>
                                    <th colspan="1"><?php echo _l('commodity_name') ?></th>
                                    <th colspan="1"><?php echo _l('cartons') ?></th>
                                    <th colspan="1" class="text-center"><?php echo _l('pieces_carton') ?></th>
                                    <th align="right" colspan="1"><?php echo _l('total_pieces') ?></th>
                                    <th align="right" colspan="1"><?php echo _l('price') ?></th>
                                    <th align="right" colspan="1"><?php echo _l('price_total') ?></th>
                                    <th align="right" colspan="1"><?php echo _l('gross_weight') ?></th>
                                    <th align="right" colspan="1"><?php echo _l('total_gross_weight') ?></th>
                                    <th align="right" colspan="1"><?php echo _l('net_weight') ?></th>
                                    <th align="right" colspan="1"><?php echo _l('total_net_weight') ?></th>
                                    <th align="right" colspan="1"><?php echo _l('cbm') ?></th>
                                    <th align="right" colspan="1"><?php echo _l('total_cbm') ?></th>
                                 </tr>
                              </thead>
                              <tbody class="ui-sortable">

                                 <?php
                                 $subtotal = 0;
                                 $cartons = 0;
                                 $totalPieces = 0;
                                 $Pricetotal = 0;
                                 $Total_net_weight = 0;
                                 $Total_gross_weight = 0;
                                 $Total_cbm = 0;
                                 foreach ($goods_receipt_detail as $receipt_key => $receipt_value) {
                                    $receipt_key++;
                                    if ($receipt_value['checkd_koli'] != 0) {

                                       $checkd_koli = (isset($receipt_value) ? $receipt_value['checkd_koli'] : '');
                                       $total_koli = (isset($receipt_value) ? $receipt_value['total_koli'] : '');
                                       $cope_koli = (isset($receipt_value) ? $receipt_value['cope_koli'] : '');
                                       $price = (isset($receipt_value) ? $receipt_value['price'] : '');
                                       $price_total = (isset($receipt_value) ? $receipt_value['price_total'] : '');
                                       $price_total = (strpos($price_total, '¥') !== false) ? $price_total : '¥' . $price_total;
                                       $net_weight = (isset($receipt_value) ? $receipt_value['net_weight'] : '');
                                       $total_net_weight = (isset($receipt_value) ? $receipt_value['total_net_weight'] : '');
                                       $gross_weight = (isset($receipt_value) ? $receipt_value['gross_weight'] : '');
                                       $total_gross_weight = (isset($receipt_value) ? $receipt_value['total_gross_weight'] : '');
                                       $cbm = (isset($receipt_value) ? $receipt_value['cbm_koli'] : '');
                                       $total_cbm = (isset($receipt_value) ? $receipt_value['total_cbm'] : '');
                                       $cartons += (float) $checkd_koli;
                                       $totalPieces += (float) $total_koli;
                                       $Pricetotal += (float) preg_replace('/[^0-9.]/', '', $price_total);
                                       $Total_net_weight += (float) $total_net_weight;
                                       $Total_gross_weight += (float) $total_gross_weight;
                                       $Total_cbm += (float) $total_cbm;

                                       $commodity_code = get_commodity_name($receipt_value['commodity_code']) != null ? get_commodity_name($receipt_value['commodity_code'])->long_description : '';
                                       $commodity_name = get_commodity_name($receipt_value['commodity_code']) != null ? get_commodity_name($receipt_value['commodity_code'])->description : '';
                                       ?>

                                       <tr data-toggle="tooltip"
                                          data-original-title="<?php echo new_html_entity_decode($name_serial_number_tooltip); ?>">
                                          <td><?php echo new_html_entity_decode($receipt_key) ?></td>
                                          <td><?php echo new_html_entity_decode($commodity_name) ?>
                                          </td>
                                          <td><?php echo new_html_entity_decode($commodity_code) ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo new_html_entity_decode($checkd_koli) ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo new_html_entity_decode($cope_koli) ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo new_html_entity_decode($total_koli) ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo app_format_money((float) $price, '') ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo app_format_money($price_total, '') ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo ($gross_weight) ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo app_format_money($total_gross_weight, '') ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo new_html_entity_decode($net_weight) ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo app_format_money($total_net_weight, '') ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo ($cbm) ?>
                                          </td>
                                          <td class="text-right">
                                             <?php echo ($total_cbm) ?>
                                          </td>
                                       </tr>
                                    <?php }
                                 } ?>
                              </tbody>
                           </table>
                        </div>
                     </div>

                     <div class="col-md-6 col-md-offset-6">
                        <table class="table text-right table-margintop hidden">
                           <tbody>
                              <tr class="project-overview" id="subtotal">
                                 <td class="td_style"><span class="bold"><?php echo _l('total_goods_money'); ?></span>
                                 </td>
                                 <?php $total_goods_money = (isset($goods_receipt) ? $goods_receipt->total_goods_money : ''); ?>
                                 <td><?php echo app_format_money((float) $total_goods_money, $base_currency); ?></td>

                              </tr>

                              <tr class="project-overview">
                                 <td class="td_style"><span class="bold"><?php echo _l('value_of_inventory'); ?></span>
                                 </td>
                                 <?php $value_of_inventory = (isset($goods_receipt) ? $goods_receipt->value_of_inventory : ''); ?>
                                 <td><?php echo app_format_money((float) $value_of_inventory, $base_currency); ?></td>
                              </tr>

                              <?php if (isset($goods_receipt) && $tax_data['html_currency'] != '') {
                                 echo new_html_entity_decode($tax_data['html_currency']);
                              } ?>

                              <tr class="project-overview">
                                 <td class="td_style"><span class="bold"><?php echo _l('total_tax_money'); ?></span>
                                 </td>
                                 <?php $total_tax_money = (isset($goods_receipt) ? $goods_receipt->total_tax_money : ''); ?>
                                 <td><?php echo app_format_money((float) $total_tax_money, $base_currency); ?></td>
                              </tr>

                              <tr class="project-overview">
                                 <td class="td_style"><span class="bold"><?php echo _l('total_money'); ?></span>
                                 </td>
                                 <?php $total_money = (isset($goods_receipt) ? $goods_receipt->total_money : ''); ?>
                                 <td><?php echo app_format_money((float) $total_money, $base_currency); ?></td>

                              </tr>
                           </tbody>
                        </table>
                     </div>
                     <div class="col-md-5 col-md-offset-7">
                        <table class="table text-right">
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
                                 <td><span class="bold"><?php echo _l('price_total'); ?></span>
                                 </td>
                                 <td class="price_total">
                                    <?php echo ('¥' . $Pricetotal); ?>
                                 </td>
                              </tr>
                              <tr id="price_total">
                                 <td><span class="bold"><?php echo _l('total_gross_weight'); ?></span>
                                 </td>
                                 <td class="price_total">
                                    <?php echo clean_number($Total_gross_weight); ?>
                                 </td>
                              </tr>
                              <tr id="price_total">
                                 <td><span class="bold"><?php echo _l('total_net_weight'); ?></span>
                                 </td>
                                 <td class="price_total">
                                    <?php echo clean_number($Total_net_weight); ?>
                                 </td>
                              </tr>
                              <tr id="price_total">
                                 <td><span class="bold"><?php echo _l('total_cbm'); ?></span>
                                 </td>
                                 <td class="price_total">
                                    <?php echo clean_number($Total_cbm); ?>
                                 </td>
                              </tr>
                           </tbody>
                        </table>
                     </div>

                     <div class="col-md-12">
                        <div class="project-overview-right">
                           <?php if (count($list_approve_status) > 0) { ?>

                              <div class="row">
                                 <div class="col-md-12 project-overview-expenses-finance">
                                    <div class="col-md-4 text-center">
                                    </div>
                                    <?php
                                    $this->load->model('staff_model');
                                    $enter_charge_code = 0;
                                    foreach ($list_approve_status as $value) {
                                       $value['staffid'] = new_explode(', ', $value['staffid']);
                                       if ($value['action'] == 'sign') {
                                          ?>
                                          <div class="col-md-3 text-center">
                                             <p class="text-uppercase text-muted no-mtop bold">
                                                <?php
                                                $staff_name = '';
                                                $st = _l('status_0');
                                                $color = 'warning';
                                                foreach ($value['staffid'] as $key => $val) {
                                                   if ($staff_name != '') {
                                                      $staff_name .= ' or ';
                                                   }
                                                   if ($this->staff_model->get($val)) {
                                                      $staff_name .= $this->staff_model->get($val)->full_name;
                                                   }
                                                }
                                                echo new_html_entity_decode($staff_name);
                                                ?>
                                             </p>
                                             <?php if ($value['approve'] == 1) {
                                                ?>
                                                <?php if (file_exists(WAREHOUSE_STOCK_IMPORT_MODULE_UPLOAD_FOLDER . $goods_receipt->id . '/signature_' . $value['id'] . '.png')) { ?>

                                                   <img
                                                      src="<?php echo site_url('modules/warehouse/uploads/stock_import/' . $goods_receipt->id . '/signature_' . $value['id'] . '.png'); ?>"
                                                      class="img-width-height">

                                                <?php } else { ?>
                                                   <img
                                                      src="<?php echo site_url('modules/warehouse/uploads/image_not_available.jpg'); ?>"
                                                      class="img-width-height">
                                                <?php } ?>


                                             <?php }
                                             ?>
                                          </div>
                                       <?php } else { ?>
                                          <div class="col-md-3 text-center">
                                             <p class="text-uppercase text-muted no-mtop bold">
                                                <?php
                                                $staff_name = '';
                                                foreach ($value['staffid'] as $key => $val) {
                                                   if ($staff_name != '') {
                                                      $staff_name .= ' or ';
                                                   }
                                                   if ($this->staff_model->get($val)) {
                                                      $staff_name .= $this->staff_model->get($val)->full_name;
                                                   }
                                                }
                                                echo new_html_entity_decode($staff_name);
                                                ?>
                                             </p>
                                             <?php if ($value['approve'] == 1) {
                                                ?>
                                                <img
                                                   src="<?php echo site_url('modules/warehouse/uploads/approval/approved.png'); ?>"
                                                   class="img-width-height">
                                             <?php } elseif ($value['approve'] == -1) { ?>
                                                <img
                                                   src="<?php echo site_url('modules/warehouse/uploads/approval/rejected.png'); ?>"
                                                   class="img-width-height">
                                             <?php }
                                             ?>
                                             <p class="text-muted no-mtop bold">
                                                <?php echo new_html_entity_decode($value['note']) ?>
                                             </p>
                                          </div>
                                       <?php }
                                    } ?>
                                 </div>
                              </div>

                           <?php } ?>
                        </div>

                        <div class="pull-right">

                           <?php
                           if ($goods_receipt->approval != 1 && ($check_approve_status == false)) { ?>
                              <?php if ($check_appr && $check_appr != false) { ?>

                                 <a data-toggle="tooltip" data-loading-text="<?php echo _l('wait_text'); ?>"
                                    class="btn btn-success lead-top-btn lead-view" data-placement="top" href="#"
                                    onclick="send_request_approve(<?php echo new_html_entity_decode($goods_receipt->id); ?>); return false;"><?php echo _l('send_request_approve'); ?></a>
                              <?php } ?>

                           <?php }
                           if (isset($check_approve_status['staffid'])) {
                              ?>
                              <?php
                              if (in_array(get_staff_user_id(), $check_approve_status['staffid']) && !in_array(get_staff_user_id(), $get_staff_sign)) { ?>
                                 <div class="btn-group">
                                    <a href="#" class="btn btn-success dropdown-toggle" data-toggle="dropdown"
                                       aria-haspopup="true" aria-expanded="false"><?php echo _l('approve'); ?><span
                                          class="caret"></span></a>
                                    <ul class="dropdown-menu dropdown-menu-right menu-width-height">
                                       <li>
                                          <div class="col-md-12">
                                             <?php echo render_textarea('reason', 'reason'); ?>
                                          </div>
                                       </li>
                                       <li>
                                          <div class="row text-right col-md-12">
                                             <a href="#" data-loading-text="<?php echo _l('wait_text'); ?>"
                                                onclick="approve_request(<?php echo new_html_entity_decode($goods_receipt->id); ?>); return false;"
                                                class="btn btn-success button-margin"><?php echo _l('approve'); ?></a>
                                             <a href="#" data-loading-text="<?php echo _l('wait_text'); ?>"
                                                onclick="deny_request(<?php echo new_html_entity_decode($goods_receipt->id); ?>); return false;"
                                                class="btn btn-warning"><?php echo _l('deny'); ?></a>
                                          </div>
                                       </li>
                                    </ul>
                                 </div>
                              <?php }
                              ?>

                              <?php
                              if (in_array(get_staff_user_id(), $check_approve_status['staffid']) && in_array(get_staff_user_id(), $get_staff_sign)) { ?>
                                 <button onclick="accept_action();"
                                    class="btn btn-success pull-right action-button"><?php echo _l('e_signature_sign'); ?></button>
                              <?php }
                              ?>
                              <?php
                           }
                           ?>
                        </div>

                     </div>

                  </div>
               </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="tab_tasks">
               <?php init_relation_tasks_table(array('data-new-rel-id' => $goods_receipt->id, 'data-new-rel-type' => 'stock_import')); ?>
            </div>

         </div>

         <div class="modal fade" id="add_action" tabindex="-1" role="dialog">
            <div class="modal-dialog">
               <div class="modal-content">

                  <div class="modal-body">
                     <p class="bold" id="signatureLabel"><?php echo _l('signature'); ?></p>
                     <div class="signature-pad--body">
                        <canvas id="signature" height="130" width="550"></canvas>
                     </div>
                     <input type="text" class="sig-input-style" tabindex="-1" name="signature" id="signatureInput">
                     <div class="dispay-block">
                        <button type="button" class="btn btn-default btn-xs clear" tabindex="-1"
                           onclick="signature_clear();"><?php echo _l('clear'); ?></button>
                     </div>

                  </div>
                  <div class="modal-footer">
                     <button type="button" class="btn btn-default"
                        data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                     <button onclick="sign_request(<?php echo new_html_entity_decode($goods_receipt->id); ?>);"
                        autocomplete="off"
                        class="btn btn-success sign_request_class"><?php echo _l('e_signature_sign'); ?></button>
                  </div>


               </div>
            </div>
         </div>

      </div>
   </div>
</div>

<?php require 'modules/warehouse/assets/js/view_purchase_js.php'; ?>
</body>

</html>