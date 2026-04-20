<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?php echo $title; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .custom-table th,
        .custom-table td {
            border: 1px solid #ccc;
            padding: 4px;
            text-align: center;
            font-size: 13px;
            word-wrap: break-word;
        }

        .custom-table th {
            background-color: #d9edf7;
            font-size: 14px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .custom-table tr:nth-child(even) {
            background-color: #f9f9f9;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .custom-table tfoot td {
            background-color: #fff6c9;
            font-weight: bold;
            font-size: 14px;
            padding: 8px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        tfoot {
            page-break-inside: avoid !important;
            page-break-after: auto;
        }

        tfoot tr:last-child td {
            padding-bottom: 10px;
        }

        table,
        tr,
        td,
        th {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        thead {
            display: table-header-group !important;
        }

        tfoot {
            display: table-footer-group !important;
        }

        .img-cell img {
            width: 40px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 1px;
        }

        .header-table td {
            vertical-align: top;
            font-size: 14px;
            padding: 6px;
        }

        .company-logo {
            text-align: center;
            padding-bottom: 10px;
        }

        .company-logo img {
            max-width: 250px;
            height: auto;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
        }

        .doc-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }

        .btn-pdf {
            display: inline-block;
            margin-bottom: 15px;
            padding: 6px 12px;
            font-size: 14px;
            background: #863f3b;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }

        .btn-pdf:hover {
            background: #e76565;
        }

        #invoice {
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
            margin-left: 0;
            padding-left: 10px;
            padding-right: 10px;
        }

        .custom-table {
            table-layout: fixed;
            width: 100%;
        }

        @media print {

            table,
            tr,
            td,
            th,
            thead,
            tbody,
            tfoot {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto;
            }
        }

        div.download-wrapper {
            margin: 10px 0;
            text-align: left;
            padding-left: 20px;
        }

    
        .summary-table {
            border-collapse: collapse;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .summary-table td {
            padding: 10px 12px;
            font-size: 15px;
        }

        .summary-table .label {
            text-align: right;
            font-weight: bold;
            background-color: #eef6ff;
            border-right: 1px solid #ccc;
            width: 60%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .summary-table .value {
            text-align: right;
            background-color: #fff;
            font-weight: 500;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .summary-table .highlight {
            background-color: #fff6c9;
            font-weight: bold;
            color: #444;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @media print {
            .summary-table {
                page-break-inside: avoid;
            }
        }

    </style>
</head>

<body>

    <div class="download-wrapper">
        <button onclick="downloadPDF()" class="btn-pdf">📄 Download PDF</button>
    </div>

    <div id="invoice">
        <div class="company-logo">
            <img src="<?= base_url('uploads/company/' . get_option('company_logo')) ?>">
            <div class="company-name"><?php echo $company_name; ?></div>
            <div class="doc-title">Inventory Receiving</div>
        </div>

        <table class="header-table" width="100%" style="margin-bottom: 20px;">
            <tr>
                <td width="35%">
                    <strong><?php echo $company_name; ?></strong><br>
                    <?php echo nl2br($address); ?><br>
                    Yiwu Shi China<br>
                    +8613263491105
                </td>
                <td width="30%" style="text-align:center;"></td>
                <td width="35%" style="text-align:right;">
                    <b>#<?php echo $goods_receipt->goods_receipt_code; ?></b><br>
                    <b>Invoice Date:</b> <?php echo _d($goods_receipt->date_add); ?><br>
                    <b>Supplier Name:</b>
                    <?php
                    $vendorName = $goods_receipt->supplier_name;
                    if (isset($goods_receipt->supplier_code) && $goods_receipt->supplier_code != 0) {
                        $this->load->model('purchase/purchase_model');
                        $supplier = $this->purchase_model->get_vendor($goods_receipt->supplier_code);
                        if ($supplier) {
                            $vendorName = $supplier->company;
                            echo '<strong>' . $supplier->company . '</strong>, ' . $supplier->city;
                        } else {
                            echo $goods_receipt->supplier_name;
                        }
                    } else {
                        echo $goods_receipt->supplier_name;
                    }
                    ?><br>
                    <b>Customer:</b>
                    <?php echo get_customer_name_with_project($goods_receipt->project); ?>
                </td>
            </tr>
        </table>

    <table class="custom-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Product Code</th>
                <th>Cartons</th>
                <th>Pieces/Carton</th>
                <th>Total Pieces</th>
                <th>Price</th>
                <th>Price Total</th>
                <th>Gross Weight</th>
                <th>Total Gross Weight</th>
                <th>Net Weight</th>
                <th>Total Net Weight</th>
                <th>CBM</th>
                <th>Total CBM</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $koli = 0;
            $total_koli = 0;
            $price_total = 0;
            $total_net_weight = 0;
            $total_gross_weight = 0;
            $total_cbm = 0;

            foreach ($goods_receipt_detail as $row): 
                $koli += $row['checkd_koli'];
                $total_koli += $row['total_koli'];
                $price_total += (float) preg_replace('/[^0-9.]/', '', $row['price_total']);
                $total_net_weight += $row['total_net_weight'];
                $total_gross_weight += $row['total_gross_weight'];
                $total_cbm += $row['total_cbm'];
            ?>
            <tr>
                <td class="img-cell">
                    <?php 
                    $commodity_id = $row['commodity_code'];
                    if (isset($item_images[$commodity_id])) {
                        $img = $item_images[$commodity_id][0];
                        $path = 'modules/purchase/uploads/item_img/'.$commodity_id.'/'.$img['file_name'];
                        echo '<img src="'.base_url($path).'" alt="'.$row['commodity_name'].'" />';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
                <td><?php echo $row['commodity_name']; ?></td>
                <td><?php echo $row['checkd_koli']; ?></td>
                <td><?php echo $row['cope_koli']; ?></td>
                <td><?php echo $row['total_koli']; ?></td>
                <td><?php echo clean_number((float)$row['price']); ?></td>
                <td>¥<?php echo clean_number((float) preg_replace('/[^0-9.]/', '', $row['price_total'])); ?></td>
                <td><?php echo clean_number($row['gross_weight']); ?></td>
                <td><?php echo clean_number($row['total_gross_weight']); ?></td>
                <td><?php echo clean_number($row['net_weight']); ?></td>
                <td><?php echo clean_number($row['total_net_weight']); ?></td>
                <td><?php echo clean_number($row['cbm_koli']); ?></td>
                <td><?php echo clean_number($row['total_cbm']); ?></td>

            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right;">Total Of the Xinye maoyi</td>
                <td><?php echo $koli; ?></td>
                <td></td>
                <td><?php echo $total_koli; ?></td>
                <td></td>
                <td>¥<?php echo clean_number($price_total); ?></td>
                <td></td>
                <td><?php echo clean_number($total_gross_weight); ?></td>
                <td></td>
                <td><?php echo clean_number($total_net_weight); ?></td>
                <td></td>
                <td><?php echo clean_number($total_cbm); ?></td>
            </tr>
        </tfoot>
    </table>
        <br><br>
        <table class="summary-table" style="width: 35%; float: right; margin-top: 25px;">
            <tbody>
                <tr>
                    <td class="label">Total Cost</td>
                    <td class="value">¥<?php echo number_format($price_total, 2); ?></td>
                </tr>
                <tr>
                    <td class="label">Deposit</td>
                    <td class="value">¥<?php echo number_format($deposit ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td class="label">Remaining Balance</td>
                    <td class="value highlight">¥<?php echo number_format(($price_total - ($deposit ?? 0)), 2); ?></td>
                </tr>
            </tbody>
        </table>
        <div style="clear: both;"></div>
    </div>

    <script>
        function downloadPDF() {
            if (!confirm("Are you sure you want to download this PDF?")) {
                return;
            }
            const vendorName = "<?php echo $vendorName; ?>";
            const today = new Date();
            const dateStr = today.toLocaleDateString('en-GB').replace(/\//g, '-');

            const filename = `Received ${vendorName} ${dateStr}.pdf`;

            const element = document.getElementById('invoice');
            const options = {
                margin: [0.2, 0.2, 0.8, 0.2],
                filename: filename,
                image: { type: 'jpeg', quality: 0.95 },
                html2canvas: {
                    scale: 2,
                    logging: false,
                    useCORS: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'a4',
                    orientation: 'portrait',
                    putOnlyUsedFonts: true,
                    floatPrecision: 16
                },
                pagebreak: { mode: ['css', 'legacy'] }
            };
            html2pdf().set(options).from(element).save();
        }
    </script>

</body>

</html>