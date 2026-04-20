<?php

defined('BASEPATH') or exit('No direct script access allowed');
// This can be removed if you use __autoload() in config.php OR use Modular Extensions

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Items extends REST_Controller
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Api_model');
    }

    /**
     * @api {get} api/items/items/:id Request items information
     * @apiVersion 0.1.0
     * @apiName GetItem
     * @apiGroup Items
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiSuccess {Object} Item item information.
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *	  "itemid": "1",
     *        "rate": "100.00",
     *        "taxrate": "5.00",
     *        "taxid": "1",
     *        "taxname": "PAYPAL",
     *        "taxrate_2": "9.00",
     *        "taxid_2": "2",
     *        "taxname_2": "CGST",
     *        "description": "JBL Soundbar",
     *        "long_description": "The JBL Cinema SB110 is a hassle-free soundbar",
     *        "group_id": "0",
     *        "group_name": null,
     *        "unit": ""
     *     }
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */


    /**
     * @api {get} api/items/search/:keysearch Search invoice item information
     * @apiVersion 0.1.0
     * @apiName GetItemSearch
     * @apiGroup Items
     *
     * @apiHeader {String} Authorization Basic Access Authentication token
     *
     * @apiParam {String} keysearch Search Keywords
     *
     * @apiSuccess {Object} Item  Item Information
     *
     * @apiSuccessExample Success-Response:
     *	HTTP/1.1 200 OK
     *	{
     *	  "rate": "100.00",
     *	  "id": "1",
     *	  "name": "(100.00) JBL Soundbar",
     *	  "subtext": "The JBL Cinema SB110 is a hassle-free soundbar..."
     *	}
     *
     * @apiError {Boolean} status Request status
     * @apiError {String} message No data were found
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_search_get($key = '')
    {
        $data = $this->Api_model->search('invoice_items', $key);
        // Check if the data store contains
        if ($data) {
            $data = $this->Api_model->get_api_custom_data($data, "items");
            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code

        } else {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code

        }
    }

    public function data_post()
    {
        $this->load->model('Api_model');
        $get_data = $this->input->post();


        if ($get_data['type'] == 'received') {

            $this->db->select('pr_order_id,tblgoods_receipt_detail.id as id,goods_receipt_id,commodity_code,commodity_long_description,commodity_name,koli,checkd_koli,cope_koli,total_koli,price,price_total,net_weight,total_net_weight,gross_weight,total_gross_weight,cbm_koli,total_cbm,goods_delivery_id');
            $this->db->where(db_prefix() . 'goods_receipt_detail.id', $get_data['id']);
            $this->db->join(db_prefix() . 'goods_receipt', '' . db_prefix() . 'goods_receipt_detail.goods_receipt_id = ' . db_prefix() . 'goods_receipt.id', 'left');
            $previous_data = $this->db->get(db_prefix() . 'goods_receipt_detail')->result_array();
            $amount = 0;
            $delivery_amount = 0;
            if (count($previous_data)) {

                $set_data['checkd_koli'] = (int) $previous_data[0]['checkd_koli'] + $get_data['receving_cartons'];
                $item_data['cope_koli'] = $set_data['cope_koli'] = $get_data['cope_koli'];
                $set_data['total_koli'] = $set_data['cope_koli'] * $set_data['checkd_koli'];
                $item_data['cbm_koli'] = $set_data['cbm_koli'] = $get_data['length'] * $get_data['width'] * $get_data['height'] * 0.000001;
                $set_data['total_cbm'] = $set_data['cbm_koli'] * $set_data['checkd_koli'];
                $item_data['gross_weight'] = $set_data['gross_weight'] = $get_data['gross_weight'];
                $set_data['total_gross_weight'] = $set_data['gross_weight'] * $set_data['checkd_koli'];
                $item_data['net_weight'] = $set_data['net_weight'] = $get_data['gross_weight'] - 1.5;
                $set_data['total_net_weight'] = $set_data['net_weight'] * $set_data['checkd_koli'];
                $price_total = (float) preg_replace('/[^0-9.]/', '', $previous_data[0]['price']) * (float) $set_data['total_koli'];
                $set_data['price_total'] = '¥' . $price_total;

                $set_data['length'] = $get_data['length'];
                $set_data['width'] = $get_data['width'];
                $set_data['height'] = $get_data['height'];
                $idv_data = $pr_data = $item_data;

                //update IRV data
                $this->db->where('id', $get_data['id']);
                $this->db->update(db_prefix() . 'goods_receipt_detail', $set_data);
                $this->calulate_irv_total($previous_data[0]['goods_receipt_id']);

                //upload item data
                $this->db->where('id', $previous_data[0]['commodity_code']);
                $saved_item_data = $this->db->get(db_prefix() . 'items')->result_array();
                $item_data['total_cbm'] = $item_data['cbm_koli'] * $saved_item_data[0]['koli'];
                $item_data['total_gross_weight'] = $item_data['gross_weight'] * $saved_item_data[0]['koli'];
                $item_data['total_net_weight'] = $item_data['net_weight'] * $saved_item_data[0]['koli'];
                $item_data['total_koli'] = $item_data['cope_koli'] * $saved_item_data[0]['koli'];
                $item_data['price_total'] = $saved_item_data[0]['rate'] * $item_data['total_koli'];

                $this->db->where('id', $previous_data[0]['commodity_code']);
                $this->db->update(db_prefix() . 'items', $item_data);
                //upload item data end

                //upload pr data
                $this->db->where('pur_order', $previous_data[0]['pr_order_id']);
                $this->db->where('item_code', $previous_data[0]['commodity_code']);
                $saved_po_data = $this->db->get(db_prefix() . 'pur_order_detail')->result_array();


                $pr_data['total_cbm'] = $pr_data['cbm_koli'] * $saved_po_data[0]['koli'];
                $pr_data['total_gross_weight'] = $pr_data['gross_weight'] * $saved_po_data[0]['koli'];
                $pr_data['total_net_weight'] = $pr_data['net_weight'] * $saved_po_data[0]['koli'];
                $pr_data['total_koli'] = $pr_data['cope_koli'] * $saved_po_data[0]['koli'];
                $pr_data['price_total'] = $saved_po_data[0]['price'] * $pr_data['total_koli'];

                $this->db->where('pur_order', $previous_data[0]['pr_order_id']);
                $this->db->where('item_code', $previous_data[0]['commodity_code']);
                $this->db->update(db_prefix() . 'pur_order_detail', $pr_data);
                //upload pr data end

                //update idv data 
                $this->db->where('goods_delivery_id', $previous_data[0]['goods_delivery_id']);
                $this->db->where('commodity_code', $previous_data[0]['commodity_code']);
                $goods_delivery_detail = $this->db->get(db_prefix() . 'goods_delivery_detail')->result_array();

                if (count($goods_delivery_detail)) {
                    $idv_data['total_cbm'] = $idv_data['cbm_koli'] * $goods_delivery_detail[0]['koli'];
                    $idv_data['total_gross_weight'] = $idv_data['gross_weight'] * $goods_delivery_detail[0]['koli'];
                    $idv_data['total_net_weight'] = $idv_data['net_weight'] * $goods_delivery_detail[0]['koli'];
                    $idv_data['total_koli'] = $idv_data['cope_koli'] * $goods_delivery_detail[0]['koli'];

                    $price_total = (float) preg_replace('/[^0-9.]/', '', $goods_delivery_detail[0]['price']) * (float) $idv_data['total_koli'];
                    $idv_data['price_total'] = '¥' . $price_total;

                    $this->db->where('id', $goods_delivery_detail[0]['id']);
                    $this->db->update(db_prefix() . 'goods_delivery_detail', $idv_data);

                    //packing list update
                    $this->db->where('delivery_detail_id', $goods_delivery_detail[0]['id']);
                    $this->db->update(db_prefix() . 'wh_packing_list_details', $idv_data);

                    $this->calulate_idv_total($previous_data[0]['goods_delivery_detail']);
                }
                //update idv data  end

                //update pur_invoice start
                $this->db->select('tblpur_invoice_details.id as id,tblpur_invoices.id as pur_invoice_id');
                $this->db->where('goods_receipt_id', $previous_data[0]['id']);
                $this->db->where('item_code', $previous_data[0]['commodity_code']);
                $this->db->join(db_prefix() . 'pur_invoices', '' . db_prefix() . 'pur_invoices.id = ' . db_prefix() . 'pur_invoice_details.pur_invoice', 'left');
                $invoice_details = $this->db->get(db_prefix() . 'pur_invoice_details')->result_array();
                if (count($invoice_details)) {
                    $pur_inv_data = $set_data;
                    unset($pur_inv_data['length']);
                    unset($pur_inv_data['width']);
                    unset($pur_inv_data['height']);
                    unset($pur_inv_data['checkd_koli']);
                    $pur_inv_data['koli'] = $set_data['checkd_koli'];
                    $this->db->where('id', $invoice_details[0]['id']);
                    $this->db->update(db_prefix() . 'pur_invoice_details', $pur_inv_data);
                    $this->calulate_pur_invoice_total($invoice_details[0]['pur_invoice_id']);
                }
                //update pur invoice end
                $response = [
                    'status' => true,
                    'message' => 'Update successfully.',
                ];
            } else {
                $response = [
                    'status' => false,
                    'message' => 'Missing ID or data to update.'
                ];
            }
        } else if ($get_data['type'] == 'delivery') {

            $array = json_decode($get_data['items'], true);
            foreach ($array as $item) {
                $ids_count[$item['id']] = $item['count'];
                $ids[] = $item['id'];
            }

            $this->db->select('pr_order_id,tblgoods_receipt_detail.id as id,goods_receipt_id,commodity_code,commodity_long_description,commodity_name,koli,checkd_koli,cope_koli,total_koli,price,price_total,net_weight,total_net_weight,gross_weight,total_gross_weight,cbm_koli,total_cbm,goods_delivery_id');
            $this->db->where_in(db_prefix() . 'goods_receipt_detail.id', $ids);
            $this->db->join(db_prefix() . 'goods_receipt', '' . db_prefix() . 'goods_receipt_detail.goods_receipt_id = ' . db_prefix() . 'goods_receipt.id', 'left');
            $all_goods = $this->db->get(db_prefix() . 'goods_receipt_detail')->result();

            $goods_delivery_ids = array();
            $packing_list_ids = array();
            $total = 0;
            if (count($all_goods)) {
                foreach ($all_goods as $goods) {
                    $this->db->where('goods_delivery_id', $goods->goods_delivery_id);
                    $this->db->where('commodity_code', $goods->commodity_code);
                    $goods_delivery_detail = $this->db->get(db_prefix() . 'goods_delivery_detail')->result_array();

                    if (count($goods_delivery_detail)) {
                        if ((int) $goods->checkd_koli < (int) $goods_delivery_detail[0]['koli'] + (int) $ids_count[$goods->id]) {
                            $ids_count[$goods->id] = (int) $goods->checkd_koli - (int) $goods_delivery_detail[0]['koli'];
                        }
                        if ($ids_count[$goods->id] > 0) {
                            $total += $ids_count[$goods->id];
                            (string) $item_data['koli'] = (string) $goods_delivery_data['koli'] = (int) $goods_delivery_detail[0]['koli'] + $ids_count[$goods->id];

                            $goods_delivery_data['total_koli'] = $goods_delivery_data['koli'] * $goods_delivery_detail[0]['cope_koli'];
                            $goods_delivery_data['total_cbm'] = $goods_delivery_detail[0]['cbm_koli'] * $goods_delivery_data['koli'];
                            $goods_delivery_data['total_gross_weight'] = $goods_delivery_detail[0]['gross_weight'] * $goods_delivery_data['koli'];
                            $goods_delivery_data['total_net_weight'] = $goods_delivery_detail[0]['net_weight'] * $goods_delivery_data['koli'];
                            $goods_delivery_data['price_total'] = $goods_delivery_detail[0]['price'] * $goods_delivery_data['total_koli'];


                            $this->db->where('id', $goods_delivery_detail[0]['id']);
                            $this->db->update(db_prefix() . 'goods_delivery_detail', $goods_delivery_data);

                            $this->db->where('delivery_detail_id', $goods_delivery_detail[0]['id']);
                            $wh_packing_list_details = $this->db->get(db_prefix() . 'wh_packing_list_details')->result_array();

                            if (count($wh_packing_list_details)) {
                                $this->db->where('delivery_detail_id', $goods_delivery_detail[0]['id']);
                                $this->db->update(db_prefix() . 'wh_packing_list_details', $goods_delivery_data);
                                $packing_list_ids[] = $wh_packing_list_details[0]['packing_list_id'];
                            }


                            $this->db->where('id', $goods->commodity_code);
                            $this->db->update(db_prefix() . 'items', $item_data);
                            $goods_delivery_ids[] = $goods->goods_delivery_id;
                        }
                    }
                    $goods_delivery_ids = array_unique($goods_delivery_ids);
                    foreach ($goods_delivery_ids as $goods_delivery_id) {
                        $this->calulate_idv_total($goods_delivery_id);
                    }
                    $packing_list_ids = array_unique($packing_list_ids);
                    foreach ($packing_list_ids as $packing_list_id) {
                        $this->calulate_packing_total($packing_list_id);
                    }
                }
                if ($total == 0) {
                    $response = [
                        'status' => false,
                        'message' => 'Nothing To Update Here',
                    ];
                } else {
                    $response = [
                        'status' => true,
                        'message' => 'Update completed!. Total box deliverd: ' . $total,
                    ];
                }

            } else {
                $response = [
                    'status' => false,
                    'message' => 'No Product Found.'
                ];
            }

        } else {
            $response = [
                'status' => false,
                'message' => 'Invalid type provided.'
            ];
        }



        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response, JSON_PRETTY_PRINT));


    }
    public function data_get($id = '')
    {
        $id = $this->input->get('id');
        $type = $this->input->get('type');


        if ($type === "received") {
            $this->db->select('length,width,height,tblgoods_receipt.id as goods_receipt_id,tblpur_order_detail.koli as po_koli,pr_order_id,tblgoods_receipt_detail.id as id,goods_receipt_id,commodity_code,commodity_long_description,commodity_name,tblgoods_receipt_detail.koli as koli,checkd_koli,tblgoods_receipt_detail.cope_koli,tblgoods_receipt_detail.total_koli,tblgoods_receipt_detail.price,tblgoods_receipt_detail.price_total,tblgoods_receipt_detail.net_weight as net_weight,tblgoods_receipt_detail.total_net_weight,tblgoods_receipt_detail.gross_weight as gross_weight,tblgoods_receipt_detail.total_gross_weight,tblgoods_receipt_detail.cbm_koli,tblgoods_receipt_detail.total_cbm,goods_delivery_id');
            $this->db->where(db_prefix() . 'goods_receipt_detail.id', $id);
            $this->db->join(db_prefix() . 'goods_receipt', '' . db_prefix() . 'goods_receipt_detail.goods_receipt_id = ' . db_prefix() . 'goods_receipt.id', 'left');
            $this->db->join(db_prefix() . 'pur_order_detail', db_prefix() . 'goods_receipt_detail.commodity_code = ' . db_prefix() . 'pur_order_detail.item_code AND ' . db_prefix() . 'pur_order_detail.pur_order = ' . db_prefix() . 'goods_receipt.pr_order_id', 'left');
            $goods = $this->db->get(db_prefix() . 'goods_receipt_detail')->row();
            if (empty($goods)) {
                $response = [
                    'status' => false,
                    'message' => 'No goods receipt found with the given ID.'
                ];
            } else {
                $irv_link = base_url('admin/warehouse/edit_purchase/' . $goods->goods_receipt_id);
                $checkd_koli = (int) $goods->checkd_koli;
                $goods->checkd_koli = $checkd_koli;

                $cope_koli = (double) $goods->cope_koli;
                $goods->total_koli = $cope_koli * $checkd_koli;

                $price = (double) $goods->price;
                $goods->price_total = $price * $checkd_koli;

                $net_weight = (double) $goods->net_weight;
                $goods->total_net_weight = $net_weight * $checkd_koli;

                $gross_weight = (double) $goods->gross_weight;
                $goods->total_gross_weight = $gross_weight * $checkd_koli;

                $cbm_koli = (double) $goods->cbm_koli;
                $goods->total_cbm = $cbm_koli * $checkd_koli;

                $response = [
                    'status' => true,
                    'type' => 'received',
                    'message' => 'Goods receipt data fetched successfully.',
                    'data' => $goods,
                    'irv_link' => $irv_link
                ];

            }
        } else if ($type === "delivery") {

            $array = explode(",", $id);
            $ids_count = array_count_values($array);
            $ids = array_values(array_unique($array));

            $this->db->select('pr_order_id,tblgoods_receipt_detail.id as id,goods_receipt_id,commodity_code,commodity_long_description,commodity_name,koli,checkd_koli,cope_koli,total_koli,price,price_total,net_weight,total_net_weight,gross_weight,total_gross_weight,cbm_koli,total_cbm,goods_delivery_id');
            $this->db->where_in(db_prefix() . 'goods_receipt_detail.id', $ids);
            $this->db->join(db_prefix() . 'goods_receipt', '' . db_prefix() . 'goods_receipt_detail.goods_receipt_id = ' . db_prefix() . 'goods_receipt.id', 'left');
            $all_goods = $this->db->get(db_prefix() . 'goods_receipt_detail')->result();

            $total = 0;
            if (count($all_goods)) {
                foreach ($all_goods as $goods) {
                    $this->db->where('goods_delivery_id', $goods->goods_delivery_id);
                    $this->db->where('commodity_code', $goods->commodity_code);
                    $goods_delivery_detail = $this->db->get(db_prefix() . 'goods_delivery_detail')->result_array();
                    if (count($goods_delivery_detail)) {
                        if ((int) $goods->checkd_koli < (int) $goods_delivery_detail[0]['koli'] + (int) $ids_count[$goods->id]) {
                            $ids_count[$goods->id] = (int) $goods->checkd_koli - (int) $goods_delivery_detail[0]['koli'];
                        }
                        if ($ids_count[$goods->id] > 0) {
                            $total += $ids_count[$goods->id];
                            (string) $item_data['koli'] = (string) $goods_delivery_data['koli'] = (int) $goods_delivery_detail[0]['koli'] + $ids_count[$goods->id];

                            $this->db->where('id', $goods_delivery_detail[0]['id']);
                            $this->db->update(db_prefix() . 'goods_delivery_detail', $goods_delivery_data);

                            $this->db->where('id', $goods->commodity_code);
                            $this->db->update(db_prefix() . 'items', $item_data);
                        }
                    }
                }
                if ($total == 0) {
                    $response = [
                        'status' => false,
                        'message' => 'Nothing To Update Here',
                    ];
                } else {
                    $response = [
                        'status' => true,
                        'message' => 'Update completed!. Total box deliverd: ' . $total,
                    ];
                }

            } else {
                $response = [
                    'status' => false,
                    'message' => 'No Product Found.'
                ];
            }

        } else {
            $response = [
                'status' => false,
                'message' => 'Invalid type provided.'
            ];
        }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function calulate_irv_total($id)
    {
        $this->db->where('goods_receipt_id', $id);
        $goods_receipt_details = $this->db->get(db_prefix() . 'goods_receipt_detail')->result_array();
        $total = 0;
        foreach ($goods_receipt_details as $goods_receipt_detail) {
            $total += (float) $goods_receipt_detail['total_koli'] * (float) $goods_receipt_detail['price'];
        }
        $data['total_goods_money'] = $total;
        $data['value_of_inventory'] = $total;
        $data['total_money'] = $total;
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'goods_receipt', $data);

    }
    public function calulate_idv_total($id)
    {
        $this->db->where('goods_delivery_id', $id);
        $goods_delivery_details = $this->db->get(db_prefix() . 'goods_delivery_detail')->result_array();
        $total = 0;
        foreach ($goods_delivery_details as $goods_delivery_detail) {
            $total += (float) $goods_delivery_detail['total_koli'] * (float) $goods_delivery_detail['price'];
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'goods_delivery', [
            'total_money' => $total,
            'sub_total' => $total,
        ]);

    }
    public function calulate_pur_invoice_total($id)
    {
        $this->db->where('pur_invoice', $id);
        $pur_invoice_details = $this->db->get(db_prefix() . 'pur_invoice_details')->result_array();
        $total = 0;
        foreach ($pur_invoice_details as $pur_invoice_detail) {
            $total += (float) $pur_invoice_detail['total_koli'] * (float) $pur_invoice_detail['price'];
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'pur_invoices', [
            'subtotal' => $total,
        ]);
    }
    public function calulate_packing_total($id)
    {
        $this->db->where('packing_list_id', $id);
        $wh_packing_list_details = $this->db->get(db_prefix() . 'wh_packing_list_details')->result_array();
        $total = 0;
        foreach ($wh_packing_list_details as $wh_packing_list_detail) {
            $total += (float) $wh_packing_list_detail['total_koli'] * (float) $wh_packing_list_detail['price'];
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'wh_packing_lists', [
            'subtotal' => $total,
            'total_amount' => $total,
            'total_after_discount' => $total,
        ]);
    }

}