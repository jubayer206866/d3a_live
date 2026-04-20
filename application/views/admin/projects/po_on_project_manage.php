<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div id="po">
    <div class="content">

        <!-- Tabs -->
        <div class="custom-tabs">
            <button class="tab-btn active" data-tab="tabA">PO Summary</button>
            <button class="tab-btn" data-tab="tabB">PO Details</button>
        </div>

        <div class="tab-content-wrapper">

            <!-- TAB A: Purchase Orders -->
            <div class="tab-panel active" id="tabA">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php
                            render_datatable([
                                'Purchase Order Number',
                                'P.O Value',
                                'Deposit',
                                'Volume',
                                'Delivery Date',
                                'Project',
                                'Vendor',
                                'Customer',
                            ], 'po_on_project_table');
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB B: Items / Stock -->
            <div class="tab-panel" id="tabB">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="mb-3 text-right">
                            <a href="<?= admin_url('projects/export_po_details_excel/' . $project_id); ?>"
                            class="btn btn-primary">
                                <i class="fas fa-file-excel"></i> Save As Excel
                            </a>
                        </div>
                        <div class="panel-table-full">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <?php if (!empty($vendors)) : ?>

                                        <?php
                                        $grand_cartons = 0;
                                        $grand_total_pieces = 0;
                                        $grand_total_price = 0;
                                        $grand_total_gross = 0;
                                        $grand_total_net = 0;
                                        $grand_total_cbm = 0;
                                        ?>

                                        <?php foreach ($vendors as $vendor) : ?>

                                            <tr>
                                                <td colspan="15" style="background:#D8D8D8;font-weight:600; text-align:center">
                                                    <?= $vendor['vendor_name']; ?>
                                                </td>
                                            </tr>

                                            <tr style="background:#DDEBF7;font-weight:600">
                                                <th>Barcode</th>
                                                <th>Image</th>
                                                <th>Product Code</th>
                                                <th>Product Name</th>
                                                <th>Cartons</th>
                                                <th>Pieces/Carton</th>
                                                <th>Total/Pieces</th>
                                                <th>Price</th>
                                                <th>Total</th>
                                                <th>Gross Wt</th>
                                                <th>Total Gross</th>
                                                <th>Net Wt</th>
                                                <th>Total Net</th>
                                                <th>CBM</th>
                                                <th>Total CBM</th>
                                            </tr>

                                            <?php
                                            $sum_cartons = 0;
                                            $sum_total_pieces = 0;
                                            $sum_total_price = 0;
                                            $sum_total_gross = 0;
                                            $sum_total_net = 0;
                                            $sum_total_cbm = 0;
                                            ?>

                                            <?php foreach ($vendor['items'] as $row) : ?>
                                                <tr>
                                                    <?php foreach ($row as $index => $cell) : ?>
                                                        <td style="text-align:center"><?= $cell ?></td>
                                                    <?php endforeach; ?>
                                                </tr>

                                                <?php
                                                $sum_cartons += (float)($row[4] ?? 0);
                                                $sum_total_pieces += (float)($row[6] ?? 0);
                                                $sum_total_price += (float)str_replace('¥', '', ($row[8] ?? 0));
                                                $sum_total_gross += (float)($row[10] ?? 0);
                                                $sum_total_net += (float)($row[12] ?? 0);
                                                $sum_total_cbm += (float)($row[14] ?? 0);
                                                ?>
                                            <?php endforeach; ?>

                                            <tr style="background:#FFF3CD; font-weight:600">
                                                <td colspan="4" style="text-align:center">Total Of The <?= $vendor['company_name']; ?></td>
                                                <td style="text-align:center"><?= clean_number($sum_cartons) ?></td>
                                                <td></td>
                                                <td style="text-align:center"><?= clean_number($sum_total_pieces) ?></td>
                                                <td></td>
                                                <td style="text-align:center">¥<?= clean_number($sum_total_price) ?></td>
                                                <td></td>
                                                <td style="text-align:center"><?= clean_number($sum_total_gross) ?></td>
                                                <td></td>
                                                <td style="text-align:center"><?= clean_number($sum_total_net) ?></td>
                                                <td></td>
                                                <td style="text-align:center"><?= clean_number($sum_total_cbm) ?></td>
                                            </tr>

                                            <?php
                                            $grand_cartons += $sum_cartons;
                                            $grand_total_pieces += $sum_total_pieces;
                                            $grand_total_price += $sum_total_price;
                                            $grand_total_gross += $sum_total_gross;
                                            $grand_total_net += $sum_total_net;
                                            $grand_total_cbm += $sum_total_cbm;
                                            ?>

                                        <?php endforeach; ?>

                                        <tr style="background:#F4B084; font-weight:700; text-align:center">
                                            <td colspan="4">Total Of Full Order</td>
                                            <td><?= clean_number($grand_cartons) ?></td>
                                            <td></td>
                                            <td><?= clean_number($grand_total_pieces) ?></td>
                                            <td></td>
                                            <td>¥<?= clean_number($grand_total_price) ?></td>
                                            <td></td>
                                            <td><?= clean_number($grand_total_gross) ?></td>
                                            <td></td>
                                            <td><?= clean_number($grand_total_net) ?></td>
                                            <td></td>
                                            <td><?= clean_number($grand_total_cbm) ?></td>
                                        </tr>

                                    <?php else : ?>
                                        <tr>
                                            <td colspan="15" style="text-align:center">No items found</td>
                                        </tr>
                                    <?php endif; ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php init_tail(); ?>

<script>
    $(function() {
        var projectId = '<?php echo $project_id; ?>';
        var poTableInitialized = false;

        function showTab(tabId) {
            $('.tab-panel').removeClass('active');
            $('.tab-btn').removeClass('active');

            $('#' + tabId).addClass('active');
            $('.tab-btn[data-tab="' + tabId + '"]').addClass('active');

            if (tabId === 'tabA' && !poTableInitialized) {
                initDataTable(
                    '.table-po_on_project_table',
                    admin_url + 'projects/po_on_project_table?project_id=' + projectId,
                    undefined, undefined, undefined, [0, 'desc']
                );
                poTableInitialized = true;
            }
        }

        $('.tab-btn').click(function() {
            showTab($(this).data('tab'));
        });

        showTab('tabA');
    });
</script>
<style>
    .custom-tabs {
        margin-bottom: 15px;
    }

    .custom-tabs .tab-btn {
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: #f5f5f5;
        cursor: pointer;
        margin-right: 5px;
    }

    .custom-tabs .tab-btn.active {
        background: #fff;
        border-bottom: 2px solid #007bff;
        font-weight: 600;
    }

    .tab-panel {
        display: none;
    }

    .tab-panel.active {
        display: block;
    }

    #tabB img {
        max-width: 100px;
        max-height: 90px;
        object-fit: contain;
    }

    #po {
        background: #f4f4f5;
        min-height: 100%;
    }

    .barcode-cell {
        text-align: center;
    }
    #tabB table th {
    text-align: center;
    vertical-align: middle;
}
</style>