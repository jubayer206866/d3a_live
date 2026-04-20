<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
    img.images_w_table {
        width: 116px;
        height: 73px;
    }

    tr.single_order td {
        background-color: #FFF2CC !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .yellow {
        background-color: yellow;

    }

    @media print {
        body * {
            visibility: hidden;
        }

        .table.table-bordered {
            visibility: visible;
            position: absolute;
            top: 0;
            left: 0;
        }

        .table.table-bordered * {
            visibility: visible;
        }

        .print-table {
            margin-top: -100px !important;
            page-break-before: always;
        }

        tr.print-header th {
            background-color: #DDEBF7 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        tr.single_order td {
            background-color: #FFF2CC !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        tr.full_order td {
            background-color: #F4B084 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        tr.shop_no td {
            background-color: #D8D8D8 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        td.yellow {
            background-color: yellow !important;
            -webkit-print-color-adjust: exact;
            /* For Chrome/Safari */
            print-color-adjust: exact;
            /* For Firefox */
        }
    }
</style>
<div class="row">
    <div class="col-md-12 tw-space-x-1">
        <a href="<?= admin_url('warehouse/save_excel_packing_list/' . $project->id); ?>" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Save as Excel
        </a>
        <button class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i>
            Save as PDF
        </button>
    </div>

</div>

<table class="table table-bordered print-table" style=" border-bottom: none !important; border-top: none !important;">

    <tr>
        <td style="border: none !important;" colspan="15">
            <div style="width: 100%; text-align: center;">
                <img width="400px" height="100px"
                    src="<?= base_url('uploads/company/' . get_option('company_logo')) ?>">
                <br>
                <div style="font-size:30px; client_infofont-weight:700; text-align: center; margin-top: 20px;">
                    <?php echo $project->name; ?>
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <td style="border: none !important;" colspan="7">
            <div style="padding-left:10px; text-align: left; font-size: 20px; font-weight:600">
                Bill of Lading Nr. : <?php echo $project->bill_of_landing_number; ?>
                <br>
                Container Nr.: <?php echo $project->create_container_number; ?>
                <br>
                Invoice Nr.: <?php echo $project_invoices; ?>
                <br>
                Date: <?php echo _d($date); ?>
            </div>

        </td>
        <td style="border: none !important;" colspan="3">


        </td>
        <td style="border: none !important;" colspan="5">
            <div style="padding-right:10px; text-align: left; font-size: 20px; font-weight:600">
                Customer : <?php echo $client_info->company; ?>
                <br>
                VAT Nr: <?php echo $client_info->vat; ?>
                <br>
                Phone Nr.: <?php echo $client_info->phonenumber; ?>
                <br>
                Address: <?php echo $client_info->address; ?>

            </div>

        </td>
    </tr>
    <tr>
        <th style="border: none !important;" colspan="4"></th>
        <td style="border: none !important; text-align:center;" colspan="4">
        </td>
        <th style="border: none !important;" colspan="7"></th>
    </tr>
    <tr>
        <td style="border: none !important;" colspan="15"></td>
    </tr>
    <tr>
        <td style="border: none !important;" colspan="15"></td>
    </tr>



    <tbody>
        <?php foreach ($vendors as $key => $vendor_items) { ?>
            <tr class="shop_no">

                <td <?php echo $span = isset($shop[$key]['span']) ? $shop[$key]['span'] : ''; ?>
                    style="<?php echo $style = isset($shop[$key]['style']) ? $shop[$key]['style'] : ''; ?>">
                    <?php echo $shop[$key]['name']; ?>
                </td>

            </tr>
            <tr class="print-header">
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Barcode</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Image</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Product Code</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Product Name</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Cartons</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Pieces/Carton</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Total/Pieces</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Price</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Price/Total</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Gross Weight</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Total Gross Weight</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Net Weight</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Total Net Weight</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">CBM</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Total CBM</th>
            </tr>
            <?php foreach ($vendor_items as $items) { ?>
                <?php
                $class = '';
                if (strpos($items[0]['name'], 'Total') !== false) {
                    $class = 'single_order';
                } ?>
                <tr class="<?php echo $class ?>">
                    <?php foreach ($items as $cell) { ?>
                        <td class="<?php echo $class = isset($cell['class']) ? $cell['class'] : ''; ?>" <?php echo $span = isset($cell['span']) ? $cell['span'] : ''; ?> style="text-align:center;">
                            <?php echo $cell['name']; ?>
                        </td>
                    <?php } ?>
                </tr>
            <?php } ?>
        <?php } ?>
        <tr class="full_order">
            <?php foreach ($project_summary as $cell) { ?>
                <td <?php echo $span = isset($cell['span']) ? $cell['span'] : ''; ?>
                    style="text-align:center; background-color:#F4B084 !important; font-weight:600; font-size: 14px;">
                    <?php echo $cell['name']; ?>
                </td>
            <?php } ?>
        </tr>
        <?php if (count($stock_items)) { ?>
            <tr>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Barcode</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Image</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Product Code</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Product Name</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Cartons</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Pieces/Carton</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Total/Pieces</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Price</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Price/Total</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Gross Weight</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Total Gross Weight</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Net Weight</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Total Net Weight</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">CBM</th>
                <th scope="col" style="text-align:center; background-color:#DDEBF7">Total CBM</th>
            </tr>
            <tr>
                <td style="text-align:center; font-size: 16px; font-weight: 700;" colspan="15">Items From Privious Project
                </td>
            </tr>
            <?php foreach ($stock_items as $items) { ?>
                <tr class="">
                    <?php foreach ($items as $cell) { ?>
                        <td style="text-align:center;">
                            <?php echo $cell['name']; ?>
                        </td>
                    <?php } ?>
                </tr>
            <?php } ?>
        <?php } ?>
        <tr>
            <td colspan="15"></td>
        </tr>
        <tr>
            <td colspan="5"><b>Shipping Company: <?php echo $shipping_company_name; ?></b></td>
            <td colspan="10"><b>Link: <a href="<?php echo $shipping_company_link; ?>" target="_blank">
                        <?php echo $shipping_company_link; ?>
                    </a></b></td>
        </tr>
    </tbody>
</table>
<script>
    function printTable() {
        window.print();
    }
</script>