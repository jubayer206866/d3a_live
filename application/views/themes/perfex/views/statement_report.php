<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
    <?= _l('Statement'); ?>
</h4>
<div class="row mtop15 mbot15">
    <div class="col-md-4">
        <label class="control-label"><?php echo _l('Date'); ?></label>
        <select id="filter_date_range" class="form-control">
            <option value="all"><?php echo _l('All'); ?></option>
            <option value="this_month"><?php echo _l('This Month'); ?></option>
            <option value="1"><?php echo _l('Last Month'); ?></option>
            <option value="3"><?php echo _l('Last 3 Months'); ?></option>
            <option value="6"><?php echo _l('Last 6 Months'); ?></option>
            <option value="12"><?php echo _l('Last 12 Months'); ?></option>
            <option value="this_year"><?php echo _l('This Year'); ?></option>
            <option value="last_year"><?php echo _l('Last Year'); ?></option>
            <option value="custom"><?php echo _l('Custom Range'); ?></option>
        </select>
    </div>
</div>

<div id="custom_date_row" class="row mtop10" style="display:none;">
    <div class="col-md-2">
        <label class="control-label"><?php echo _l('From'); ?></label>
        <input type="date" id="custom_from" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="control-label"><?php echo _l('To'); ?></label>
        <input type="date" id="custom_to" class="form-control">
    </div>
</div><br>
<div class="panel_s">
    <div class="panel-body">

        <table class="table dt-table table-statement-report"
            data-order-col="0"
            data-order-type="desc">
            <thead>
                <tr>
                    <th><?= _l('date'); ?></th>
                    <th><?= _l('Details'); ?></th>
                    <th><?= _l('Products Value'); ?></th>
                    <th><?= _l('Service Fee'); ?></th>
                    <th><?= _l('Other Expenses'); ?></th>
                    <th><?= _l('Total'); ?></th>
                    <th><?= _l('Invoice Amount'); ?></th>
                    <th><?= _l('RMB Received'); ?></th>
                    <th><?= _l('Balance'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sum_products = 0;
                $sum_service = 0;
                $sum_other = 0;
                $sum_total = 0;
                $sum_invoice = 0;
                $sum_rmb = 0;
                $sum_balance = 0;

                foreach ($statements as $row) { 
                    $date_for_js = date('Y-m-d', strtotime($row['start_date']));

                    $sum_products += $row['total_goods_value'];
                    $sum_service += $row['service_fee'];
                    $sum_other += $row['others_expenses'];
                    $sum_total += $row['total'];
                    $sum_invoice += $row['invoice_amount'];
                    $sum_rmb += $row['invoice_total'];
                    $sum_balance += $row['balance'];
                ?>
                    <tr class="statement-row" data-date="<?= $date_for_js; ?>">
                        <td data-order="<?= $date_for_js; ?>"><?= _d($row['start_date']); ?></td>
                        <td><?= e($row['project_name']); ?></td>
                        <td><?= app_format_money($row['total_goods_value'], '¥'); ?></td>
                        <td><?= app_format_money($row['service_fee'], '¥'); ?></td>
                        <td><?= app_format_money($row['others_expenses'], '¥'); ?></td>
                        <td><?= app_format_money($row['total'], '¥'); ?></td>
                        <td><?= app_format_money($row['invoice_amount'], $row['currency_name']); ?></td>
                        <td><?= app_format_money($row['invoice_total'], '¥'); ?></td>
                        <td><?= app_format_money($row['balance'], '¥'); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr class="tw-font-bold tw-bg-gray-100">
                    <th colspan="2" class="tw-text-center"><?= _l('Total'); ?></th>
                    <th><?= app_format_money($sum_products, '¥'); ?></th>
                    <th><?= app_format_money($sum_service, '¥'); ?></th>
                    <th><?= app_format_money($sum_other, '¥'); ?></th>
                    <th><?= app_format_money($sum_total, '¥'); ?></th>
                    <th><?= app_format_money($sum_invoice, '¥'); ?></th>
                    <th><?= app_format_money($sum_rmb, '¥'); ?></th>
                    <th><?= app_format_money($sum_balance, '¥'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var filterSelect = document.getElementById('filter_date_range');
        var customDateRow = document.getElementById('custom_date_row');
        var customFrom = document.getElementById('custom_from');
        var customTo = document.getElementById('custom_to');

        function shouldShowRow(dateStr, filterType) {
            if (!dateStr) return false;

            var rowDate = new Date(dateStr);
            var now = new Date();

            switch (filterType) {
                case 'all':
                    return true;

                case 'this_month':
                    return rowDate.getMonth() === now.getMonth() &&
                        rowDate.getFullYear() === now.getFullYear();

                case 'this_year':
                    return rowDate.getFullYear() === now.getFullYear();

                case 'last_year':
                    return rowDate.getFullYear() === now.getFullYear() - 1;

                case '1':
                    var oneMonthAgo = new Date();
                    oneMonthAgo.setMonth(now.getMonth() - 1);
                    return rowDate >= oneMonthAgo;

                case '3':
                    var threeMonthsAgo = new Date();
                    threeMonthsAgo.setMonth(now.getMonth() - 3);
                    return rowDate >= threeMonthsAgo;

                case '6':
                    var sixMonthsAgo = new Date();
                    sixMonthsAgo.setMonth(now.getMonth() - 6);
                    return rowDate >= sixMonthsAgo;

                case '12':
                    var yearAgo = new Date();
                    yearAgo.setFullYear(now.getFullYear() - 1);
                    return rowDate >= yearAgo;

                case 'custom':
                    var from = customFrom.value;
                    var to = customTo.value;

                    if (from && to) {
                        var fromDate = new Date(from);
                        var toDate = new Date(to);
                        return rowDate >= fromDate && rowDate <= toDate;
                    }
                    return true;

                default:
                    return true;
            }
        }

        function filterRows(filterType) {
            var rows = document.querySelectorAll('.statement-row');

            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var dateStr = row.getAttribute('data-date');
                var showRow = shouldShowRow(dateStr, filterType);

                if (showRow) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        filterSelect.addEventListener('change', function() {
            var value = this.value;

            if (value === 'custom') {
                customDateRow.style.display = 'flex';
            } else {
                customDateRow.style.display = 'none';
                filterRows(value);
            }
        });

        customFrom.addEventListener('change', function() {
            if (filterSelect.value === 'custom') {
                filterRows('custom');
            }
        });

        customTo.addEventListener('change', function() {
            if (filterSelect.value === 'custom') {
                filterRows('custom');
            }
        });
    });
</script>