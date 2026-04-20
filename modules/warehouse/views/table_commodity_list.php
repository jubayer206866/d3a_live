<?php

defined('BASEPATH') or exit('No direct script access allowed');
$arr_inventory_min_data = $this->ci->warehouse_model->arr_inventory_min(false);
$filter_arr_inventory_min_max = $this->ci->warehouse_model->filter_arr_inventory_min_max();
$arr_inventory_min_id = $filter_arr_inventory_min_max['inventory_min'];
$arr_inventory_max_id = $filter_arr_inventory_min_max['inventory_max'];

$aColumns = [
	db_prefix() . 'items.id',
	'commodity_code',
	'long_description',
	'koli',
	'cope_koli',
	'total_koli',
	'rate',
	'price_total',
	'gross_weight',
	'total_gross_weight',
	'net_weight',
	'total_net_weight',
	'cbm_koli',
	'total_cbm',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'items';


$where = [];

$where[] = 'AND ' . db_prefix() . 'items.active = 1';
$join = [
	'LEFT JOIN ' . db_prefix() . 'taxes t1 ON t1.id = ' . db_prefix() . 'items.tax',
	'LEFT JOIN ' . db_prefix() . 'taxes t2 ON t2.id = ' . db_prefix() . 'items.tax2',
	'LEFT JOIN ' . db_prefix() . 'items_groups ON ' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id',
	'LEFT JOIN ' . db_prefix() . 'ware_style_type ON ' . db_prefix() . 'ware_style_type.style_type_id = ' . db_prefix() . 'items.style_id',
	'LEFT JOIN ' . db_prefix() . 'ware_body_type ON ' . db_prefix() . 'ware_body_type.body_type_id = ' . db_prefix() . 'items.model_id',
	'LEFT JOIN ' . db_prefix() . 'ware_size_type ON ' . db_prefix() . 'ware_size_type.size_type_id = ' . db_prefix() . 'items.size_id',
	'LEFT JOIN ' . db_prefix() . 'ware_color ON ' . db_prefix() . 'ware_color.color_id = ' . db_prefix() . 'items.color',
	'LEFT JOIN ' . db_prefix() . 'pur_vendor_items ON ' . db_prefix() . 'pur_vendor_items.id = ' . db_prefix() . 'items.id',
	'LEFT JOIN ' . db_prefix() . 'pur_vendor ON ' . db_prefix() . 'pur_vendor.userid = ' . db_prefix() . 'pur_vendor_items.vendor',
	
];
if ($this->ci->input->post('vendor')) {
    $vendor_id = $this->ci->input->post('vendor');
    array_push($where, 'AND ' . db_prefix() . 'pur_vendor_items.vendor = ' . $this->ci->db->escape($vendor_id));
}

if ($this->ci->input->post('category')) {
    $category  = $this->ci->input->post('category');
  
    $_category = [];
    if (is_array($category)) {
        foreach ($category as $cat) {
            if ($cat != '') {
                array_push($_category, $this->ci->db->escape_str($cat));
              
            }
        }
    }
 
    if (count($_category) > 0) {
        array_push($where, 'AND category IN (' . implode(', ', $_category) . ')');
    }
}
array_push($where, 'AND ' . db_prefix() . 'items.group_item LIKE "%' . 0 . '%"');	
if ($this->ci->input->post('product_code')) {
	array_push($where, 'AND ' . db_prefix() . 'items.commodity_code LIKE "%' . $this->ci->db->escape_like_str($this->ci->input->post('product_code')) . '%"');
}
if ($this->ci->input->post('vendor_type')) {
    $vendor_type  = $this->ci->input->post('vendor_type');
   
    $_type = [];
    if (is_array($vendor_type)) {
        foreach ($vendor_type as $type) {
            if ($type != '') {
                array_push($_type, $this->ci->db->escape_str($type));
              
            }
        }
    }
    if (count($_type) > 0) {
        // Example: $_type = ['retail', 'wholesale'];
$_type = array_map(fn($val) => "'" . addslashes($val) . "'", $_type);

array_push($where, 'AND vendor_type IN (' . implode(', ', $_type) . ')'); 
        
    }
}
if ($this->ci->input->post('city')) {
    array_push($where, 'AND ' . db_prefix() . 'pur_vendor.city LIKE "%' . $this->ci->db->escape_like_str($this->ci->input->post('city')) . '%"');
}


if (get_status_modules_wh('purchase')) {
	$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'items.id', db_prefix() . 'items.description', db_prefix() . 'items.unit_id', db_prefix() . 'items.commodity_code', db_prefix() . 'items.commodity_barcode', db_prefix() . 'items.commodity_type', db_prefix() . 'items.warehouse_id', db_prefix() . 'items.origin', db_prefix() . 'items.color_id', db_prefix() . 'items.style_id', db_prefix() . 'items.model_id', db_prefix() . 'items.size_id', db_prefix() . 'items.rate', db_prefix() . 'items.tax', db_prefix() . 'items.group_id', db_prefix() . 'items.long_description', db_prefix() . 'items.sku_code', db_prefix() . 'items.sku_name', db_prefix() . 'items.sub_group', db_prefix() . 'items.color', db_prefix() . 'items.guarantee', db_prefix() . 'items.profif_ratio', db_prefix() . 'items.without_checking_warehouse', db_prefix() . 'items.parent_id', db_prefix() . 'items.tax2', db_prefix() . 'items.can_be_sold', db_prefix() . 'items.can_be_purchased', db_prefix() . 'items.can_be_manufacturing', db_prefix() . 'items.can_be_inventory', db_prefix() . 'ware_style_type.style_code', db_prefix() . 'ware_style_type.style_barcode', db_prefix() . 'ware_style_type.style_name', db_prefix() . 'ware_style_type.note', db_prefix() . 'ware_body_type.body_code', db_prefix() . 'ware_body_type.body_name', db_prefix() . 'ware_body_type.note', db_prefix() . 'ware_size_type.size_code', db_prefix() . 'ware_size_type.size_name', db_prefix() . 'ware_size_type.size_symbol', db_prefix() . 'ware_size_type.note', db_prefix() . 'ware_color.color_code', db_prefix() . 'ware_color.color_name', db_prefix() . 'ware_color.color_hex', db_prefix() . 'ware_color.note', db_prefix() . 'items.from_vendor_item']);
} else {

	$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'items.id', db_prefix() . 'items.description', db_prefix() . 'items.unit_id', db_prefix() . 'items.commodity_code', db_prefix() . 'items.commodity_barcode', db_prefix() . 'items.commodity_type', db_prefix() . 'items.warehouse_id', db_prefix() . 'items.origin', db_prefix() . 'items.color_id', db_prefix() . 'items.style_id', db_prefix() . 'items.model_id', db_prefix() . 'items.size_id', db_prefix() . 'items.rate', db_prefix() . 'items.tax', db_prefix() . 'items.group_id', db_prefix() . 'items.long_description', db_prefix() . 'items.sku_code', db_prefix() . 'items.sku_name', db_prefix() . 'items.sub_group', db_prefix() . 'items.color', db_prefix() . 'items.guarantee', db_prefix() . 'items.profif_ratio', db_prefix() . 'items.without_checking_warehouse', db_prefix() . 'items.parent_id', db_prefix() . 'items.tax2', db_prefix() . 'items.can_be_sold', db_prefix() . 'items.can_be_purchased', db_prefix() . 'items.can_be_manufacturing', db_prefix() . 'items.can_be_inventory', db_prefix() . 'ware_style_type.style_code', db_prefix() . 'ware_style_type.style_barcode', db_prefix() . 'ware_style_type.style_name', db_prefix() . 'ware_style_type.note', db_prefix() . 'ware_body_type.body_code', db_prefix() . 'ware_body_type.body_name', db_prefix() . 'ware_body_type.note', db_prefix() . 'ware_size_type.size_code', db_prefix() . 'ware_size_type.size_name', db_prefix() . 'ware_size_type.size_symbol', db_prefix() . 'ware_size_type.note', db_prefix() . 'ware_color.color_code', db_prefix() . 'ware_color.color_name', db_prefix() . 'ware_color.color_hex', db_prefix() . 'ware_color.note', 'koli', 'cope_koli', 'total_koli', 'price_total', 'gross_weight', 'total_gross_weight', 'net_weight', 'total_net_weight', 'cbm_koli', 'total_cbm']);
}

$output = $result['output'];
$rResult = $result['rResult'];
if ($this->ci->input->post('price')) {
   if($this->ci->input->post('price')=="low_to_high"){
	usort($rResult, function ($a, $b) {
		return [$a ['rate']] <=>
		[$b ['rate']];
	});
   }else{
	usort($rResult, function ($a, $b) {
		return [$b ['rate']] <=>
		[$a ['rate']];
	});
   }

}

$arr_images = $this->ci->warehouse_model->item_attachments();
$arr_inventory_min = $arr_inventory_min_data;
$arr_warehouse_by_item = $this->ci->warehouse_model->arr_warehouse_by_item();
$arr_warehouse_id = $this->ci->warehouse_model->arr_warehouse_id();
$arr_unit_id = [];
$get_unit_type = $this->ci->warehouse_model->get_unit_type();
foreach ($get_unit_type as $key => $value) {
	$arr_unit_id[$value['unit_type_id']] = $value;
}
$inventory_min = $this->ci->warehouse_model->arr_inventory_min(true);
$arr_inventory_number = $this->ci->warehouse_model->arr_inventory_number_by_item();
$arr_tax_rate = [];
$get_tax_rate = get_tax_rate();
foreach ($get_tax_rate as $key => $value) {
	$arr_tax_rate[$value['id']] = $value;
}
$item_have_variation = $this->ci->warehouse_model->arr_item_have_variation();
foreach ($rResult as $aRow) {
	$product_inventory_quantity = 0;
	$row = [];
	for ($i = 0; $i < count($aColumns); $i++) {

		if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
			$_data = $aRow[strafter($aColumns[$i], 'as ')];
		} else {
			$_data = $aRow[$aColumns[$i]];
		}


		/*get commodity file*/
		if ($aColumns[$i] == db_prefix() . 'items.id') {
			if (isset($arr_images[$aRow['id']]) && isset($arr_images[$aRow['id']][0])) {

				if (file_exists('modules/purchase/uploads/item_img/' . $arr_images[$aRow['id']][0]['rel_id'] . '/' . $arr_images[$aRow['id']][0]['file_name'])) {
					$_data = '<img class="images_w_table" src="' . site_url('modules/purchase/uploads/item_img/' . $arr_images[$aRow['id']][0]['rel_id'] . '/' . $arr_images[$aRow['id']][0]['file_name']) . '" alt="' . $arr_images[$aRow['id']][0]['file_name'] . '" >';
				} else {
					$_data = '<img class="images_w_table" src="' . site_url('modules/warehouse/uploads/nul_image.jpg') . '" alt="nul_image.jpg">';
				}

			} else {
				if (get_status_modules_wh('purchase')) {
					$this->ci->load->model('purchase/purchase_model');
					if (is_numeric($aRow['from_vendor_item'])) {
						$vendor_image = $this->ci->purchase_model->get_vendor_item_file($aRow['from_vendor_item']);
						if (count($vendor_image) > 0) {
							if (file_exists(PURCHASE_PATH . 'vendor_items/' . $aRow['from_vendor_item'] . '/' . $vendor_image[0]['file_name'])) {
								$_data = '<img class="images_w_table" src="' . site_url('modules/purchase/uploads/vendor_items/' . $vendor_image[0]['rel_id'] . '/' . $vendor_image[0]['file_name']) . '" alt="' . $vendor_image[0]['file_name'] . '" >';
							} else {
								$_data = '<img class="images_w_table" src="' . site_url('modules/warehouse/uploads/nul_image.jpg') . '" alt="nul_image.jpg">';
							}
						}
					} else {
						$_data = '<img class="images_w_table" src="' . site_url('modules/warehouse/uploads/nul_image.jpg') . '" alt="nul_image.jpg">';
					}
				} else {
					$_data = '<img class="images_w_table" src="' . site_url('modules/warehouse/uploads/nul_image.jpg') . '" alt="nul_image.jpg">';
				}
			}
		}

		if ($aColumns[$i] == 'commodity_code') {
			$code = '<a href="' . admin_url('warehouse/view_commodity_detail/' . $aRow['id']) . '">' . $aRow['commodity_code'] . '</a>';
			$code .= '<div class="row-options">';

			$code .= '<a href="' . admin_url('warehouse/view_commodity_detail/' . $aRow['id']) . '" >' . _l('view') . '</a>';

			if (has_permission('warehouse_item', '', 'edit') || is_admin()) {
				$code .= ' | <a href="#" onclick="edit_commodity_item(this); return false;"  data-commodity_id="' . $aRow['id'] . '" data-description="' . $aRow['description'] . '" data-unit_id="' . $aRow['unit_id'] . '" data-commodity_code="' . $aRow['commodity_code'] . '" data-commodity_barcode="' . $aRow['commodity_barcode'] . '" data-commodity_type="' . $aRow['commodity_type'] . '" data-origin="' . $aRow['origin'] . '" data-color_id="' . $aRow['color_id'] . '" data-style_id="' . $aRow['style_id'] . '" data-model_id="' . $aRow['model_id'] . '" data-size_id="' . $aRow['size_id'] . '"  data-rate="' . $aRow['rate'] . '" data-koli="' . $aRow['koli'] . '" data-cope_koli="' . $aRow['cope_koli'] . '"  data-total_koli="' . $aRow['total_koli'] . '" data-price_total="' . $aRow['price_total'] . '" data-gross_weight="' . $aRow['gross_weight'] . '" data-total_gross_weight="' . $aRow['total_gross_weight'] . '" data-purchase_price="' . $aRow['purchase_price'] . '" data-net_weight="' . $aRow['net_weight'] . '" data-total_net_weight="' . $aRow['total_net_weight'] . '" data-cbm_koli="' . $aRow['cbm_koli'] . '" data-total_cbm="' . $aRow['total_cbm'] . '" data-long_description="' . $aRow['long_description'] . '" data-tax2="' . $aRow['tax2'] . '" data-can_be_sold="' . $aRow['can_be_sold'] . '" data-can_be_purchased="' . $aRow['can_be_purchased'] . '" data-can_be_manufacturing="' . $aRow['can_be_manufacturing'] . '" data-can_be_inventory="' . $aRow['can_be_inventory'] . '"  >' . _l('edit') . '</a>';
			}

			if ((has_permission('warehouse_item', '', 'edit') || has_permission('warehouse_item', '', 'create')) && ($aRow['without_checking_warehouse'] == 0)) {
				$code .= ' | <a href="#" onclick="add_opening_stock_modal(' . $aRow['id'] . ', ' . $aRow['parent_id'] . '); return false;">' . _l('add_opening_stock') . '</a>';
			}

			if (has_permission('warehouse_item', '', 'delete') || is_admin()) {
				$code .= ' | <a href="' . admin_url('warehouse/delete_commodity/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
			}
			$code .= ' | <a href="' . admin_url('warehouse/change_barcode/' . $aRow['id']) . '" class="text-warning">Change Barcode</a>';
			$code .= '</div>';

			$_data = $code;

		} elseif ($aColumns[$i] == 'rate') {
			$_data = app_format_money((float) $aRow['rate'], '');
		} elseif ($aColumns[$i] == 'price_total') {
			$_data = app_format_money((float) $aRow['price_total'], '');
		}


		$row[] = $_data;

	}
	$output['aaData'][] = $row;
}

