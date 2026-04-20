<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div id="irv_po_tabs">

    <div class="custom-tabs">
        <button class="tab-btn active" data-tab="tabA">IRV Summary</button>
        <button class="tab-btn" data-tab="tabB">IRV Details</button>
    </div>

    <div class="tab-content-wrapper">

        <div class="tab-panel active" id="tabA">
            <div class="panel_s">
                <div class="panel-body">
                    <?php
                    render_datatable([
                        'Delivery Docket Code',
                        'Project Name',
                        'Supplier Name',
                        'Purchase Order Number',
                        'Total Value of Goods',
                        'Volume',
                        'Status',
                        'Person in Charge',
                    ], 'manage_purchase_table');
                    ?>
                </div>
            </div>
        </div>

        <div class="tab-panel" id="tabB">
            <div class="panel_s">
                <div class="panel-body">
                    <div class="mb-3 text-right">
                        <a href="<?= admin_url('projects/export_irv_details_excel/' . $project_id); ?>" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save As Excel
                        </a>
                    </div>
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <?php
                            $grand_cartons = $grand_total_pieces = 0;
                            $grand_total_price = $grand_total_gross = 0;
                            $grand_total_net = $grand_total_cbm = 0;
                            ?>

                            <?php if (!empty($irvs)) : ?>
                                <?php foreach ($irvs as $irv) : ?>

                                    <tr>
                                        <td colspan="15" style="background:#D8D8D8;font-weight:600;text-align:center">
                                            IRV: <?= $irv['irv_code']; ?>
                                        </td>
                                    </tr>

                                    <tr style="background:#DDEBF7;font-weight:600;">
                                        <th>Barcode</th>
                                        <th>Image</th>
                                        <th>Product Code</th>
                                        <th>Product Name</th>
                                        <th>Cartons</th>
                                        <th>Pieces/Carton</th>
                                        <th>Total Pieces</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                        <th>Gross</th>
                                        <th>Total Gross</th>
                                        <th>Net</th>
                                        <th>Total Net</th>
                                        <th>CBM</th>
                                        <th>Total CBM</th>
                                    </tr>

                                    <?php
                                    $sum_cartons = $sum_pieces = $sum_price = 0;
                                    $sum_gross = $sum_net = $sum_cbm = 0;
                                    ?>

                                    <?php foreach ($irv['items'] as $row) : ?>
                                        <tr>
                                            <?php foreach ($row as $cell) : ?>
                                                <td class="text-center"><?= $cell ?></td>
                                            <?php endforeach; ?>
                                        </tr>

                                        <?php
                                        $sum_cartons += (float)$row[4];
                                        $sum_pieces  += (float)$row[6];
                                        $sum_price   += (float)str_replace('¥', '', $row[8]);
                                        $sum_gross   += (float)$row[10];
                                        $sum_net     += (float)$row[12];
                                        $sum_cbm     += (float)$row[14];
                                        ?>
                                    <?php endforeach; ?>

                                    <tr style="background:#FFF3CD;font-weight:600">
                                        <td colspan="4" class="text-center">IRV Total</td>
                                        <td><?= clean_number($sum_cartons) ?></td>
                                        <td></td>
                                        <td><?= clean_number($sum_pieces) ?></td>
                                        <td></td>
                                        <td>¥<?= clean_number($sum_price) ?></td>
                                        <td></td>
                                        <td><?= clean_number($sum_gross) ?></td>
                                        <td></td>
                                        <td><?= clean_number($sum_net) ?></td>
                                        <td></td>
                                        <td><?= clean_number($sum_cbm) ?></td>
                                    </tr>

                                    <?php
                                    $grand_cartons      += $sum_cartons;
                                    $grand_total_pieces += $sum_pieces;
                                    $grand_total_price  += $sum_price;
                                    $grand_total_gross  += $sum_gross;
                                    $grand_total_net    += $sum_net;
                                    $grand_total_cbm    += $sum_cbm;
                                    ?>

                                <?php endforeach; ?>

                                <tr style="background:#F4B084;font-weight:700;text-align:center">
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
                                    <td colspan="15" class="text-center">No IRV items found</td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php init_tail(); ?>

<script>
$(function () {

    var projectId = '<?= $project_id; ?>';
    var tableInitialized = false;

    function showTab(tabId) {
        $('.tab-panel').removeClass('active');
        $('.tab-btn').removeClass('active');

        $('#' + tabId).addClass('active');
        $('.tab-btn[data-tab="' + tabId + '"]').addClass('active');

        if (tabId === 'tabA' && !tableInitialized) {

            initDataTable(
                '.table-manage_purchase_table',
                admin_url + 'projects/manage_purchase_table?project_id=' + projectId,
                undefined,
                undefined,
                undefined,
                [0, 'desc']
            );

            setTimeout(function () {
                var table = $('.table-manage_purchase_table').DataTable();

                function highlightLastRow() {
                    $('.table-manage_purchase_table tbody tr')
                        .removeClass('last-row-highlight');

                    $('.table-manage_purchase_table tbody tr:last')
                        .addClass('last-row-highlight');
                }

                table.on('draw', highlightLastRow);
                table.on('page.dt', function () {
                    setTimeout(highlightLastRow, 50);
                });

                highlightLastRow();
            }, 300);

            tableInitialized = true;
        }
    }

    $('.tab-btn').on('click', function () {
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
.table-irv_summary_table tbody tr.last-row-highlight {
    background-color: #f4b084 !important;
}
#tabB img {
    max-width: 100px;
    max-height: 90px;
    object-fit: contain;
}
#tabB table th {
    text-align: center;
    vertical-align: middle;
}
</style>
