<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Invoice_items extends AdminController
{
    private $not_importable_fields = ['id'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('invoice_items_model');
    }
    public function data_get($id = '')
    {
        $id = $this->input->get('id');
        $type = $this->input->get('type');

        if ($type === "received") {
            $this->db->select('pr_order_id,tblgoods_receipt_detail.id as id,goods_receipt_id,commodity_code,commodity_long_description,commodity_name,koli,checkd_koli,cope_koli,total_koli,price,price_total,net_weight,total_net_weight,gross_weight,total_gross_weight,cbm_koli,total_cbm,goods_delivery_id');
            $this->db->where(db_prefix() . 'goods_receipt_detail.id', $id);
            $this->db->join(db_prefix() . 'goods_receipt', '' . db_prefix() . 'goods_receipt_detail.goods_receipt_id = ' . db_prefix() . 'goods_receipt.id', 'left');
            $goods = $this->db->get(db_prefix() . 'goods_receipt_detail')->row();
            if (empty($goods)) {
                $response = [
                    'status' => false,
                    'message' => 'No goods receipt found with the given ID.'
                ];
            } else {
                $this->db->where('pur_order', $goods->pr_order_id);
                $this->db->where('item_code', $goods->commodity_code);
                $pur_order_detail = $this->db->get(db_prefix() . 'pur_order_detail')->result_array();
                if ($pur_order_detail[0]['koli'] <= $goods->checkd_koli) {
                    $response = [
                        'status' => false,
                        'message' => 'You cannot scan more boxes. Maximum limit reached!',
                    ];
                } else {
                    $checkd_koli = (int) $goods->checkd_koli;
                    $goods->checkd_koli = $checkd_koli += 1;

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
                        'data' => $goods
                    ];
                }
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
                        'message' => 'Nothing To Update Update',
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

    /* List all available items */
    public function index()
    {
        if (staff_cant('view', 'items')) {
            access_denied('Invoice Items');
        }

        $this->load->model('taxes_model');
        $data['taxes'] = $this->taxes_model->get();
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $data['title'] = _l('invoice_items');
        $this->load->view('admin/invoice_items/manage', $data);
    }

    public function table()
    {
        if (staff_cant('view', 'items')) {
            ajax_access_denied();
        }
        $this->app->get_table_data('invoice_items');
    }

    /* Edit or update items / ajax request /*/
    public function manage()
    {
        if (staff_can('view', 'items')) {
            if ($this->input->post()) {
                $data = $this->input->post();
                if ($data['itemid'] == '') {
                    if (staff_cant('create', 'items')) {
                        header('HTTP/1.0 400 Bad error');
                        echo _l('access_denied');
                        die;
                    }
                    $id = $this->invoice_items_model->add($data);
                    $success = false;
                    $message = '';
                    if ($id) {
                        $success = true;
                        $message = _l('added_successfully', _l('sales_item'));
                    }
                    echo json_encode([
                        'success' => $success,
                        'message' => $message,
                        'item' => $this->invoice_items_model->get($id),
                    ]);
                } else {
                    if (staff_cant('edit', 'items')) {
                        header('HTTP/1.0 400 Bad error');
                        echo _l('access_denied');
                        die;
                    }
                    $success = $this->invoice_items_model->edit($data);
                    $message = '';
                    if ($success) {
                        $message = _l('updated_successfully', _l('sales_item'));
                    }
                    echo json_encode([
                        'success' => $success,
                        'message' => $message,
                    ]);
                }
            }
        }
    }

    public function import()
    {
        if (staff_cant('create', 'items')) {
            access_denied('Items Import');
        }

        $this->load->library('import/import_items', [], 'import');

        $this->import->setDatabaseFields($this->db->list_fields(db_prefix() . 'items'))
            ->setCustomFields(get_custom_fields('items'));

        if ($this->input->post('download_sample') === 'true') {
            $this->import->downloadSample();
        }

        if (
            $this->input->post()
            && isset($_FILES['file_csv']['name']) && $_FILES['file_csv']['name'] != ''
        ) {
            $this->import->setSimulation($this->input->post('simulate'))
                ->setTemporaryFileLocation($_FILES['file_csv']['tmp_name'])
                ->setFilename($_FILES['file_csv']['name'])
                ->perform();

            $data['total_rows_post'] = $this->import->totalRows();

            if (!$this->import->isSimulation()) {
                set_alert('success', _l('import_total_imported', $this->import->totalImported()));
            }
        }

        $data['title'] = _l('import');
        $this->load->view('admin/invoice_items/import', $data);
    }

    public function add_group()
    {
        if ($this->input->post() && staff_can('create', 'items')) {
            $this->invoice_items_model->add_group($this->input->post());
            set_alert('success', _l('added_successfully', _l('item_group')));
        }
    }

    public function update_group($id)
    {
        if ($this->input->post() && staff_can('edit', 'items')) {
            $this->invoice_items_model->edit_group($this->input->post(), $id);
            set_alert('success', _l('updated_successfully', _l('item_group')));
        }
    }

    public function delete_group($id)
    {
        if (staff_can('delete', 'items')) {
            if ($this->invoice_items_model->delete_group($id)) {
                set_alert('success', _l('deleted', _l('item_group')));
            }
        }
        redirect(admin_url('invoice_items?groups_modal=true'));
    }

    /* Delete item*/
    public function delete($id)
    {
        if (staff_cant('delete', 'items')) {
            access_denied('Invoice Items');
        }

        if (!$id) {
            redirect(admin_url('invoice_items'));
        }

        $response = $this->invoice_items_model->delete($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('invoice_item_lowercase')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('invoice_item')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('invoice_item_lowercase')));
        }
        redirect(admin_url('invoice_items'));
    }

    public function bulk_action()
    {
        hooks()->do_action('before_do_bulk_action_for_items');
        $total_deleted = 0;
        if ($this->input->post()) {
            $ids = $this->input->post('ids');
            $has_permission_delete = staff_can('delete', 'items');
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    if ($this->input->post('mass_delete')) {
                        if ($has_permission_delete) {
                            if ($this->invoice_items_model->delete($id)) {
                                $total_deleted++;
                            }
                        }
                    }
                }
            }
        }

        if ($this->input->post('mass_delete')) {
            set_alert('success', _l('total_items_deleted', $total_deleted));
        }
    }

    public function search()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            echo json_encode($this->invoice_items_model->search($this->input->post('q')));
        }
    }

    /* Get item by id / ajax */
    public function get_item_by_id($id)
    {
        if ($this->input->is_ajax_request()) {
            $item = $this->invoice_items_model->get($id);
            $item->long_description = nl2br($item->long_description);
            $item->custom_fields_html = render_custom_fields('items', $id, [], ['items_pr' => true]);
            $item->custom_fields = [];

            $cf = get_custom_fields('items');

            foreach ($cf as $custom_field) {
                $val = get_custom_field_value($id, $custom_field['id'], 'items_pr');
                if ($custom_field['type'] == 'textarea') {
                    $val = clear_textarea_breaks($val);
                }
                $custom_field['value'] = $val;
                $item->custom_fields[] = $custom_field;
            }

            echo json_encode($item);
        }
    }

    /* Copy Item */
    public function copy($id)
    {
        if (staff_cant('create', 'items')) {
            access_denied('Create Item');
        }

        $data = (array) $this->invoice_items_model->get($id);

        $id = $this->invoice_items_model->copy($data);

        if ($id) {
            set_alert('success', _l('item_copy_success'));
            return redirect(admin_url('invoice_items?id=' . $id));
        }

        set_alert('warning', _l('item_copy_fail'));
        return redirect(admin_url('invoice_items'));
    }
}
