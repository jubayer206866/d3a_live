<?php

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\projects\Gantt;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class Projects extends ClientsController
{
    /**
     * @since  2.3.3
     */


    public function __construct()
    {
        parent::__construct();
        $this->load->model('warehouse/warehouse_model');
        $this->load->model('purchase/purchase_model');

    }


    public function index($id, $hash)
    {

        $project = $this->projects_model->get($id);
        if ($hash != md5($id)) {
            show_404();
        }

        if (!$project) {
            show_404();
        }

        $data['project'] = $project;
        $data['project']->settings->available_features = unserialize($data['project']->settings->available_features);
        $data['title'] = $data['project']->name;

        if (!$this->input->get('group')) {
            $group = 'project_overview';
        } else {
            $group = $this->input->get('group');
        }
        $data['project_status'] = get_project_status_by_id($data['project']->status);

        if ($group == 'project_overview') {
            $percent = $this->projects_model->calc_progress($id);
            @$data['percent'] = $percent / 100; // old
            $data['progress'] = $percent;
            $this->load->helper('date');
            $data['project_total_days'] = round((human_to_unix($data['project']->deadline . ' 00:00') - human_to_unix($data['project']->start_date . ' 00:00')) / 3600 / 24);
            $data['project_days_left'] = $data['project_total_days'];
            $data['project_time_left_percent'] = 100;
            if ($data['project']->deadline) {
                if (human_to_unix($data['project']->start_date . ' 00:00') < time() && human_to_unix($data['project']->deadline . ' 00:00') > time()) {
                    $data['project_days_left'] = round((human_to_unix($data['project']->deadline . ' 00:00') - time()) / 3600 / 24);
                    $data['project_time_left_percent'] = $data['project_days_left'] / $data['project_total_days'] * 100;
                    $data['project_time_left_percent'] = round($data['project_time_left_percent'], 2);
                }
                if (human_to_unix($data['project']->deadline . ' 00:00') < time()) {
                    $data['project_days_left'] = 0;
                    $data['project_time_left_percent'] = 0;
                }
            }
            $total_tasks = $this->projects_model->get_tasks($id, [
                db_prefix() . 'milestones.hide_from_customer' => 0,
            ], false, true);

            $total_tasks = hooks()->apply_filters('client_project_total_tasks', $total_tasks, $id);

            $data['tasks_not_completed'] = $this->projects_model->get_tasks($id, [
                'status !=' => 5,
                db_prefix() . 'milestones.hide_from_customer' => 0,
            ], false, true);

            $data['tasks_not_completed'] = hooks()->apply_filters('client_project_tasks_not_completed', $data['tasks_not_completed'], $id);

            $data['tasks_completed'] = $this->projects_model->get_tasks($id, [
                'status' => 5,
                db_prefix() . 'milestones.hide_from_customer' => 0,
            ], false, true);

            $data['tasks_completed'] = hooks()->apply_filters('client_project_tasks_completed', $data['tasks_completed'], $id);

            $data['total_tasks'] = $total_tasks;
            $data['tasks_not_completed_progress'] = ($total_tasks > 0 ? number_format(($data['tasks_completed'] * 100) / $total_tasks, 2) : 0);
            $data['tasks_not_completed_progress'] = round($data['tasks_not_completed_progress'], 2);
        } elseif ($group == 'project_files') {
            $data['files'] = $this->projects_model->get_files($id);
        } elseif ($group == 'project_packing_list') {

            $this->db->from(db_prefix() . 'shipping_company');
            $this->db->where('id', $project->shipping_company);
            $shipping_company = $this->db->get()->result_array();
            $data['shipping_company_name'] = count($shipping_company) ? $shipping_company[0]['name'] : '';
            $data['shipping_company_link'] = count($shipping_company) ? $shipping_company[0]['link'] : '';
            $return = $this->projects_model->get_project_po_items($id);

            $data['vendors'] = $return['return'];

            $data['project_summary'] = $return['summary'];
            $data['shop'] = $return['shop'];
            $data['date'] = $return['date'];
            $data['project_invoices'] = $this->projects_model->get_project_invoices($id);
            $data['client_info'] = $this->clients_model->get($project->clientid);
            $data['stock_items'] = $this->projects_model->get_stock_items($id);
        }



        $data['group'] = $group;
        $data['currency'] = $this->projects_model->get_currency($id);
        $data['members'] = $this->projects_model->get_project_members($id);



        $this->data($data);
        $this->view('public_project');
        $this->layout();
    }
    public function save_excel_packing_list($project_id)
    {

        $this->db->where(db_prefix() . 'projects.id', $project_id);
        $project = $this->db->get(db_prefix() . 'projects')->row();
        $project_invoices = $this->projects_model->get_project_invoices($project_id);
        $this->db->where(db_prefix() . 'clients.userid', $project->clientid);
        $client = $this->db->get(db_prefix() . 'clients')->row();
        $this->db->select('tblpur_vendor.store_number as vendor_code,tblpur_vendor.company as company,tblpur_vendor.company as company,tblwh_packing_lists.id as wh_packing_lists_id,tblgoods_delivery.date_add as date');
        $this->db->where(db_prefix() . 'pur_orders.project', $project_id);
        $this->db->from(db_prefix() . 'pur_orders');
        $this->db->join(db_prefix() . 'pur_vendor', db_prefix() . 'pur_vendor.userid=' . db_prefix() . 'pur_orders.vendor');
        $this->db->join(db_prefix() . 'goods_receipt', db_prefix() . 'goods_receipt.pr_order_id = ' . db_prefix() . 'pur_orders.id');
        $this->db->join(db_prefix() . 'goods_delivery', db_prefix() . 'goods_delivery.goods_receipt_id = ' . db_prefix() . 'goods_receipt.id');
        $this->db->join(db_prefix() . 'wh_packing_lists', db_prefix() . 'wh_packing_lists.delivery_note_id = ' . db_prefix() . 'goods_delivery.id');

        $wh_packing_lists = $this->db->get()->result_array();


        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getDefaultRowDimension()->setRowHeight(50);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri');
        foreach (range('A', 'O') as $col) {
            if ($col == 'B') {
                $sheet->getColumnDimension($col)->setWidth(20);
            } else {
                $sheet->getColumnDimension($col)->setWidth(16);
            }

        }


        $sheet->mergeCells('A1:O1');
        $sheet->getRowDimension(1)->setRowHeight(110);
        $imagePath = FCPATH . 'uploads/company/' . get_option('company_logo');
        if (file_exists($imagePath) && get_option('company_logo') != '') {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Company Logo');
            $drawing->setDescription('Company Logo');
            $drawing->setPath($imagePath);
            $drawing->setHeight(40);
            $drawing->setWidth(220);
            $drawing->setCoordinates('G1');
            $drawing->setOffsetX(45);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($sheet);
        }



        $row = 2;
        $sheet->mergeCells("A{$row}:O{$row}");
        $sheet->setCellValue("A{$row}", $project->name);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getFont()->setSize(16);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);



        $wh_date = count($wh_packing_lists) ? $wh_packing_lists[0]['date'] : date('y-m-d');
        $Left = "Bill of Lading Nr. :" . $project->bill_of_landing_number .
            "\nContainer Nr.:" . $project->create_container_number .
            "\nInvoice Nr. :" . $project_invoices .
            "\nDate:" . _d($wh_date);
        $row = 3;
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->getRowDimension($row)->setRowHeight(100);
        $sheet->setCellValue("A{$row}", $Left);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getFont()->setSize(12);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);

        $sheet->mergeCells('D' . 3 . ':L' . 3);
        $sheet->setCellValue('D' . 3, '');


        $right = "Customer :" . $client->company .
            "\nVAT Nr:" . $client->vat .
            "\nPhone Nr.:" . $client->phonenumber .
            "\nAddress : " . $client->address;


        $sheet->mergeCells("M{$row}:O{$row}");
        $sheet->setCellValue("M{$row}", $right);
        $sheet->getStyle("M{$row}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("M{$row}")->getFont()->setBold(true);
        $sheet->getStyle("M{$row}")->getFont()->setSize(12);
        $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("M{$row}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);


        $arr_images = $this->warehouse_model->item_attachments();


        $i = 4;
        $summary[0]['name'] = 'Cartons';
        $summary[0]['data'] = 0;
        $summary[1]['name'] = 'Total/Pieces';
        $summary[1]['data'] = 0;
        $summary[2]['name'] = 'Price/Total';
        $summary[2]['data'] = 0;
        $summary[3]['name'] = 'Total Net Weight';
        $summary[3]['data'] = 0;
        $summary[4]['name'] = 'Total Gross Weight';
        $summary[4]['data'] = 0;
        $summary[5]['name'] = 'Total CBM';
        $summary[5]['data'] = 0;
        foreach ($wh_packing_lists as $wh_packing_list) {
            $this->db->select(db_prefix() . 'wh_packing_list_details.*, ' . db_prefix() . 'items.commodity_barcode,items.rate');
            $this->db->from(db_prefix() . 'wh_packing_list_details');
            $this->db->join(
                db_prefix() . 'items',
                db_prefix() . 'items.id = ' . db_prefix() . 'wh_packing_list_details.commodity_code'
            );
            $this->db->where('packing_list_id', $wh_packing_list['wh_packing_lists_id']);
            $this->db->where('wh_packing_list_details.koli >', 0);
            $list_items = $this->db->get()->result_array();

            if (count($list_items)) {
                $vendor = array();
                $sheet->mergeCells('A' . $i . ':O' . $i);
                $sheet->setCellValue('A' . $i, "Shop Name:" . $wh_packing_list['company'] . '  Shop Nr.:' . $wh_packing_list['vendor_code']);
                $sheet->getStyle('A' . $i)->getFont()->setBold(true);
                $sheet->getStyle('A' . $i)->getFont()->setSize(16);
                $sheet->getStyle('A' . $i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A' . $i)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $styleArray = [
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'D8D8D8',
                        ],
                    ],
                ];
                $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
                $i++;


                $sheet->setCellValue('A' . $i, 'Barcode');
                $sheet->setCellValue('B' . $i, 'Image');
                $sheet->setCellValue('C' . $i, 'Product Code');
                $sheet->setCellValue('D' . $i, 'Product Name');
                $sheet->setCellValue('E' . $i, 'Cartons');
                $sheet->setCellValue('F' . $i, 'Pieces/Carton');
                $sheet->setCellValue('G' . $i, 'Total/Pieces');
                $sheet->setCellValue('H' . $i, 'Price');
                $sheet->setCellValue('I' . $i, 'Price/Total');
                $sheet->setCellValue('J' . $i, 'Gross Weight');
                $sheet->setCellValue('K' . $i, 'Total Gross Weight');
                $sheet->setCellValue('L' . $i, 'Net Weight');
                $sheet->setCellValue('M' . $i, 'Total Net Weight');
                $sheet->setCellValue('N' . $i, 'CBM');
                $sheet->setCellValue('O' . $i, 'Total CBM');


                $styleArray = [
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'name' => 'Calibri', // optional, if you want to enforce font here too
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'DDEBF7',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ];

                $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
                $i++;



                $vendor_koli = 0;
                $vendor_total_peaces = 0;
                $vendor_total_prices = 0;
                $vendor_total_net_weight = 0;
                $vendor_total_gross_weight = 0;
                $vendor_total_cbm = 0;
                foreach ($list_items as $list_item) {
                    $barcode = (string) $list_item['commodity_barcode'];
                    $sheet->setCellValueExplicit('A' . $i, $barcode, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);


                    if (isset($arr_images[$list_item['commodity_code']]) && isset($arr_images[$list_item['commodity_code']][0])) {

                        $imagePath = FCPATH . 'modules/purchase/uploads/item_img/' . $arr_images[$list_item['commodity_code']][0]['rel_id'] . '/' . $arr_images[$list_item['commodity_code']][0]['file_name'];

                        if (file_exists($imagePath)) {
                            $file_name = $arr_images[$list_item['id']][0]['file_name'] ? $arr_images[$list_item['id']][0]['file_name'] : "image.jpg";
                            $drawing = new Drawing();
                            $drawing->setName($file_name);
                            $drawing->setDescription($file_name);
                            $drawing->setPath($imagePath);
                            $drawing->setHeight(50);
                            $cellColumn = 'B';
                            $cellRow = $i;
                            $columnWidth = $sheet->getColumnDimension($cellColumn)->getWidth();
                            $pixelWidth = $columnWidth * 7;
                            $offsetX = max(0, ($pixelWidth - $drawing->getWidth()) / 2);
                            $drawing->setOffsetX($offsetX);
                            $sheet->getRowDimension($cellRow)->setRowHeight(60);
                            $drawing->setOffsetY(5);
                            $drawing->setCoordinates($cellColumn . $cellRow);
                            $drawing->setWorksheet($sheet);

                        } else {
                            $sheet->setCellValue('B' . $i, '');
                        }
                    } else {
                        $sheet->setCellValue('B' . $i, '');
                    }
                    $pricetotal = (float) $list_item['rate'] * (float) $list_item['total_koli'];
                    $sheet->setCellValue('C' . $i, $list_item['commodity_name']);
                    $sheet->setCellValue('D' . $i, $list_item['commodity_long_description']);
                    $sheet->setCellValue('E' . $i, $list_item['koli']);
                    $sheet->setCellValue('F' . $i, $list_item['cope_koli']);
                    $sheet->setCellValue('G' . $i, $list_item['total_koli']);
                    $cell = 'H' . $i;
                    $sheet->setCellValue($cell, $list_item['rate']);
                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => [
                            'color' => ['rgb' => 'FF0000'], // Red
                        ],
                    ]);
                    $cell = 'I' . $i;
                    $sheet->setCellValue($cell, '¥' . $pricetotal);
                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FF0000'], // Red
                        ],
                    ]);
                    $sheet->setCellValue('J' . $i, clean_number($list_item['gross_weight']));
                    $sheet->setCellValue('K' . $i, clean_number($list_item['total_gross_weight']));
                    $sheet->setCellValue('L' . $i, clean_number($list_item['net_weight']));
                    $sheet->setCellValue('M' . $i, clean_number($list_item['total_net_weight']));
                    $sheet->setCellValue('N' . $i, clean_number($list_item['cbm_koli']));
                    $sheet->setCellValue('O' . $i, clean_number($list_item['total_cbm']));
                    $styleArray = [
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                    ];

                    $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);

                    $i++;
                    $vendor_koli += (float) $list_item['koli'];
                    $vendor_total_peaces += (float) $list_item['total_koli'];
                    $vendor_total_prices += (float) $pricetotal;
                    $vendor_total_net_weight += (float) $list_item['total_net_weight'];
                    $vendor_total_gross_weight += (float) $list_item['total_gross_weight'];
                    $vendor_total_cbm += (float) $list_item['total_cbm'];
                }

                $sheet->mergeCells('A' . $i . ':D' . $i);
                $sheet->setCellValue('A' . $i, 'Total Of the ' . $wh_packing_list['company']);
                $sheet->setCellValue('E' . $i, $vendor_koli);
                $sheet->setCellValue('F' . $i, '');
                $sheet->setCellValue('G' . $i, $vendor_total_peaces);
                $sheet->setCellValue('H' . $i, '');
                $sheet->setCellValue('I' . $i, '¥' . $vendor_total_prices);
                $sheet->setCellValue('J' . $i, '');
                $sheet->setCellValue('K' . $i, clean_number($vendor_total_net_weight));
                $sheet->setCellValue('L' . $i, '');
                $sheet->setCellValue('M' . $i, clean_number($vendor_total_gross_weight));
                $sheet->setCellValue('N' . $i, '');
                $sheet->setCellValue('O' . $i, clean_number($vendor_total_cbm));


                $styleArray = [
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FFF2CC',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ];
                $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
                $i++;
                $summary[0]['data'] += $vendor_koli;
                $summary[1]['data'] += $vendor_total_peaces;
                $summary[2]['data'] += $vendor_total_prices;
                $summary[3]['data'] += $vendor_total_net_weight;
                $summary[4]['data'] += $vendor_total_gross_weight;
                $summary[5]['data'] += $vendor_total_cbm;

            }
        }
        $summary[2]['data'] = '¥' . $summary[2]['data'];
        $sheet->mergeCells('A' . $i . ':D' . $i);
        $sheet->setCellValue('A' . $i, 'Total Of Full Order');
        $sheet->setCellValue('E' . $i, $summary[0]['data']);
        $sheet->setCellValue('F' . $i, '');
        $sheet->setCellValue('G' . $i, $summary[1]['data']);
        $sheet->setCellValue('H' . $i, '');
        $sheet->setCellValue('I' . $i, $summary[2]['data']);
        $sheet->setCellValue('J' . $i, '');
        $sheet->setCellValue('K' . $i, clean_number($summary[3]['data']));
        $sheet->setCellValue('L' . $i, '');
        $sheet->setCellValue('M' . $i, clean_number($summary[4]['data']));
        $sheet->setCellValue('N' . $i, '');
        $sheet->setCellValue('O' . $i, clean_number($summary[5]['data']));

        $styleArray = [
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'F4B084',
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
        $i++;


        $this->db->select('
    tblitems.commodity_barcode,
    tblgoods_receipt_detail.commodity_code,
    tblgoods_receipt_detail.commodity_long_description,
    tblgoods_receipt_detail.commodity_name,
    tblgoods_receipt_detail.cope_koli,
    tblgoods_receipt_detail.price,
    tblgoods_receipt_detail.net_weight,
    tblgoods_receipt_detail.gross_weight,
    tblgoods_receipt_detail.cbm_koli,
    goods_receipt_details_id,
    SUM(quantity) as quantity
');
        $this->db->from('tblproject_stocks');
        $this->db->join('tblitems', 'tblitems.id = tblproject_stocks.item_id');
        $this->db->join('tblgoods_receipt_detail', 'tblgoods_receipt_detail.id = tblproject_stocks.goods_receipt_details_id');
        $this->db->where('project_id', $project_id);
        $this->db->group_by([
            'goods_receipt_details_id',
            'tblitems.commodity_barcode',
            'tblgoods_receipt_detail.commodity_code',
            'tblgoods_receipt_detail.commodity_long_description',
            'tblgoods_receipt_detail.commodity_name',
            'tblgoods_receipt_detail.cope_koli',
            'tblgoods_receipt_detail.price',
            'tblgoods_receipt_detail.net_weight',
            'tblgoods_receipt_detail.gross_weight',
            'tblgoods_receipt_detail.cbm_koli'
        ]);
        $project_stocks = $this->db->get()->result_array();


        if (count($project_stocks)) {
            $sheet->mergeCells('A' . $i . ':O' . $i);
            $sheet->setCellValue('A' . $i, "Items From Privious Project");
            $sheet->getStyle('A' . $i)->getFont()->setBold(true);
            $sheet->getStyle('A' . $i)->getFont()->setSize(16);
            $sheet->getStyle('A' . $i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $i)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $styleArray = [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'D8D8D8',
                    ],
                ],
            ];
            $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
            $i++;


            $sheet->setCellValue('A' . $i, 'Barcode');
            $sheet->setCellValue('B' . $i, 'Image');
            $sheet->setCellValue('C' . $i, 'Product Code');
            $sheet->setCellValue('D' . $i, 'Product Name');
            $sheet->setCellValue('E' . $i, 'Cartons');
            $sheet->setCellValue('F' . $i, 'Pieces/Carton');
            $sheet->setCellValue('G' . $i, 'Total/Pieces');
            $sheet->setCellValue('H' . $i, 'Price');
            $sheet->setCellValue('I' . $i, 'Price/Total');
            $sheet->setCellValue('J' . $i, 'Gross Weight');
            $sheet->setCellValue('K' . $i, 'Total Gross Weight');
            $sheet->setCellValue('L' . $i, 'Net Weight');
            $sheet->setCellValue('M' . $i, 'Total Net Weight');
            $sheet->setCellValue('N' . $i, 'CBM');
            $sheet->setCellValue('O' . $i, 'Total CBM');


            $styleArray = [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Calibri', // optional, if you want to enforce font here too
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'DDEBF7',
                    ],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ];

            $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
            $i++;
            foreach ($project_stocks as $list_item) {
                $barcode = (string) $list_item['commodity_barcode'];
                $sheet->setCellValueExplicit('A' . $i, $barcode, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);


                if (isset($arr_images[$list_item['commodity_code']]) && isset($arr_images[$list_item['commodity_code']][0])) {

                    $imagePath = FCPATH . 'modules/purchase/uploads/item_img/' . $arr_images[$list_item['commodity_code']][0]['rel_id'] . '/' . $arr_images[$list_item['commodity_code']][0]['file_name'];

                    if (file_exists($imagePath)) {
                        $file_name = $arr_images[$list_item['id']][0]['file_name'] ? $arr_images[$list_item['id']][0]['file_name'] : "image.jpg";
                        $drawing = new Drawing();
                        $drawing->setName($file_name);
                        $drawing->setDescription($file_name);
                        $drawing->setPath($imagePath);
                        $drawing->setHeight(50);
                        $cellColumn = 'B';
                        $cellRow = $i;
                        $columnWidth = $sheet->getColumnDimension($cellColumn)->getWidth();
                        $pixelWidth = $columnWidth * 7;
                        $offsetX = max(0, ($pixelWidth - $drawing->getWidth()) / 2);
                        $drawing->setOffsetX($offsetX);
                        $sheet->getRowDimension($cellRow)->setRowHeight(60);
                        $drawing->setOffsetY(5);
                        $drawing->setCoordinates($cellColumn . $cellRow);
                        $drawing->setWorksheet($sheet);

                    } else {
                        $sheet->setCellValue('B' . $i, '');
                    }
                } else {
                    $sheet->setCellValue('B' . $i, '');
                }

                $sheet->setCellValue('C' . $i, $list_item['commodity_name']);
                $sheet->setCellValue('D' . $i, $list_item['commodity_long_description']);
                $sheet->getStyle('C' . $i)->getAlignment()->setWrapText(true);
                $sheet->getStyle('D' . $i)->getAlignment()->setWrapText(true);
                $sheet->getStyle('C' . $i)->getAlignment()->setIndent(1);
                $sheet->getStyle('D' . $i)->getAlignment()->setIndent(1);
                $sheet->getStyle('C' . $i)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $sheet->getStyle('D' . $i)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
                $sheet->getColumnDimension('D')->setWidth(20);

                $sheet->setCellValue('E' . $i, $list_item['quantity']);
                $sheet->setCellValue('F' . $i, $list_item['cope_koli']);
                $total_koli = (float) $list_item['quantity'] * (float) $list_item['cope_koli'];
                $sheet->setCellValue('G' . $i, $total_koli);
                $cell = 'H' . $i;
                $sheet->setCellValue($cell, $list_item['price']);
                $sheet->getStyle($cell)->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => 'FF0000'], // Red
                    ],
                ]);
                $cell = 'I' . $i;
                $pricetotal = clean_number($total_koli * (float) $list_item['price']);
                $sheet->setCellValue($cell, '¥' . $pricetotal);
                $sheet->getStyle($cell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FF0000'], // Red
                    ],
                ]);
                $sheet->setCellValue('J' . $i, clean_number($list_item['gross_weight']));
                $total_gross_weight = $list_item['gross_weight'] * $list_item['quantity'];
                $sheet->setCellValue('K' . $i, clean_number($total_gross_weight));
                $sheet->setCellValue('L' . $i, clean_number($list_item['net_weight']));
                $total_net_weight = $list_item['net_weight'] * $list_item['quantity'];
                $sheet->setCellValue('M' . $i, clean_number($total_net_weight));
                $sheet->setCellValue('N' . $i, clean_number($list_item['cbm_koli']));
                $total_cbm = $list_item['cbm_koli'] * $list_item['quantity'];
                $sheet->setCellValue('O' . $i, clean_number($total_cbm));
                $styleArray = [
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ];

                $sheet->getStyle("A{$i}:O{$i}")->applyFromArray($styleArray);
                $i++;
            }
        }


        //two empty row
        $sheet->mergeCells('A' . $i . ':O' . $i);
        $sheet->setCellValue('A' . $i, '');
        $i++;
        $sheet->mergeCells('A' . $i . ':O' . $i);
        $sheet->setCellValue('A' . $i, '');
        $i++;

        $writer = new Xlsx($spreadsheet);
        $fileName = $project->name . "_" . time() . '.xlsx';
        $filePath = FCPATH . 'uploads/' . $fileName;

        $writer->save($filePath);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        readfile($filePath);
        unlink($filePath);
        exit;
    }

}
