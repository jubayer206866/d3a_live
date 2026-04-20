<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>LCL Contract</title>
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

        .print-btn {
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            border: none;
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .template-content {
            margin-top: 20px;
            white-space: pre-line;
            display: inline-block;
            text-align: left;
            font-size: 18px;
        }

        .logo {
            display: block;
            margin: 60px auto 20px auto;
            max-width: 200px;
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
                page-break-after: avoid;
            }

            .print-btn {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <button class="print-btn" onclick="window.print()">Print</button>

    <img src="<?= base_url('uploads/company/' . get_option('company_logo')) ?>" alt="Company Logo" class="logo">

    <div class="template-content">
        <?= $filled_template ?>
    </div>
</body>

</html>