<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>
        <?php
            $type_titles = [
                'landing_contract' => 'Landing Contract',
                'lcl_contract' => 'LCL Contract',
                'ddp_contract' => 'DDP Contract',
            ];
            $title = isset($type_titles[$contract->type]) ? $type_titles[$contract->type] : 'Contract';
            echo $title;
        ?>
    </title>

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.6;
            font-size: 18px;
            text-align: center;
        }

        p {
            margin: 0 0 10px 0;
            font-size: 18px;
        }

        .header-section {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .logo {
            display: block;
            max-width: 200px;
            height: auto;
        }

        .print-btn {
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            border: none;
        }

        .template-content {
            margin-top: 20px; 
            white-space: pre-line;
            display: inline-block;
            text-align: left;
            font-size: 18px;
        }

        .sub-title {
            text-align: center;
            font-weight: bold;
            margin-top: 0;
            line-height: 1.4;
            font-size: 20px;
        }

        @media print {
            @page {
                margin: 0;
                margin-bottom: 90px;
            }

            body {
                margin: 0;
                padding: 60px 60px 150px 60px;
                font-size: 16px;
            }

            .template-content {
                display: block !important;
                white-space: normal !important;
                page-break-before: auto !important;
                page-break-after: auto !important;
                page-break-inside: auto !important;
            }

            img.logo {
                max-width: 200px !important;
                height: auto !important;
                page-break-after: avoid;
            }

            .print-btn,
            .customer-top-submenu {
                display: none !important;
            }
        }
    </style>
</head>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>
        <?php
            $type_titles = [
                'landing_contract' => 'Landing Contract',
                'lcl_contract' => 'LCL Contract',
                'ddp_contract' => 'DDP Contract',
            ];
            $title = isset($type_titles[$contract->type]) ? $type_titles[$contract->type] : 'Contract';
            echo $title;
        ?>
    </title>

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.6;
            font-size: 18px;
            text-align: center;
            position: relative;
        }

        p {
            margin: 0 0 10px 0;
            font-size: 18px;
        }

        .header-section {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            display: block;
            max-width: 200px;
            height: auto;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 30px;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            border: 1px solid #0003;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.2s ease-in-out;
        }

        .print-btn:hover {
            background-color: #218838;
            transform: scale(1.05);
        }

        .template-content {
            margin-top: 20px; 
            white-space: pre-line;
            display: inline-block;
            text-align: left;
            font-size: 18px;
        }

        .sub-title {
            text-align: center;
            font-weight: bold;
            margin-top: 0;
            line-height: 1.4;
            font-size: 20px;
        }

        @media print {
            @page {
                margin: 0;
                margin-bottom: 90px;
            }

            body {
                margin: 0;
                padding: 60px 60px 150px 60px;
                font-size: 16px;
            }

            .template-content {
                display: block !important;
                white-space: normal !important;
                page-break-before: auto !important;
                page-break-after: auto !important;
                page-break-inside: auto !important;
            }

            img.logo {
                max-width: 200px !important;
                height: auto !important;
                page-break-after: avoid;
            }

            .print-btn,
            .customer-top-submenu {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <button class="print-btn" onclick="window.print()">Print</button>

    <div class="header-section">
        <img src="<?= base_url('uploads/company/' . get_option('company_logo')) ?>" alt="Company Logo" class="logo">
    </div>

    <div class="template-content">
        <?= $filled_template ?>
    </div>
</body>
</html>

</html>
