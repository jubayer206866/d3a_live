<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">

                <div class="row mb-3 align-items-end">
                    <div class="col-md-2">
                        <label><?php echo _l('Clients'); ?></label>
                        <select id="filter_client" name="client" class="form-control selectpicker" data-live-search="true">
                            <option value=""><?php echo _l('all'); ?></option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= (int) $c['userid']; ?>"><?= e($c['company']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label><?php echo _l('project'); ?></label>
                        <select id="filter_project" name="project" class="form-control selectpicker" data-live-search="true">
                            <option value=""><?php echo _l('all'); ?></option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= (int) $p['id']; ?>"><?= e($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label><?php echo _l('Staff'); ?></label>
                        <select id="filter_staff" name="staff" class="form-control selectpicker" data-live-search="true">
                            <option value=""><?php echo _l('all'); ?></option>
                            <?php foreach ($client_admin as $s): ?>
                                <option value="<?= e($s['admin_names']); ?>"><?= e($s['admin_names']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label><?php echo _l('Balance'); ?></label>
                        <select id="filter_balance" name="balance" class="form-control selectpicker">
                            <option value="all"><?php echo _l('All'); ?></option>
                            <option value="positive"><?php echo _l('Positive Balance'); ?></option>
                            <option value="negative"><?php echo _l('Negative Balance'); ?></option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label><?php echo _l('Date'); ?></label>
                        <select id="filter_date_range" name="date_range" class="form-control">
                            <option value="all"><?php echo _l('All'); ?></option>
                            <option value="this_month"><?php echo _l('This Month'); ?></option>
                            <option value="1"><?php echo _l('Last Month'); ?></option>
                            <option value="this_year"><?php echo _l('This Year'); ?></option>
                            <option value="last_year"><?php echo _l('Last Year'); ?></option>
                            <option value="3"><?php echo _l('Last 3 Months'); ?></option>
                            <option value="6"><?php echo _l('Last 6 Months'); ?></option>
                            <option value="12"><?php echo _l('Last 12 Months'); ?></option>
                            <option value="custom"><?php echo _l('Custom'); ?></option>
                        </select>

                        <div id="custom_date_row" class="row mtop10" style="display:none;">
                            <div class="col-md-6">
                                <input type="date" id="custom_from" name="custom_from" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <input type="date" id="custom_to" name="custom_to" class="form-control">
                            </div>
                        </div>
                    </div>
                </div><br>

                <div class="panel-table-full">
                    <?php render_datatable([
                        _l('date'),
                        _l('Details'),
                        _l('Products Value'),
                        _l('Service Fee'),
                        _l('Inland Freight (B)'),
                        _l('Inland Freight (S)'),
                        _l('Other Expenses'),
                        _l('Total'),
                        _l('Invoice Amount'),
                        _l('RMB Received'),
                        _l('Balance'),
                        _l('Clients'),
                        _l('Clients Admin'),
                        _l('Sea Transport (B)'),
                        _l('Sea Transport (S)'),
                    ], 'statement_report'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
  $(function() {
    var fnServerParams = {
        "client": '[name="client"]',
        "project": '[name="project"]',
        "staff": '[name="staff"]',
        "balance": '[name="balance"]',
        "date_range": '[name="date_range"]',
        "custom_from": '[name="custom_from"]',
        "custom_to": '[name="custom_to"]',
    };

    var reportTable = initDataTable(
        '.table-statement_report',
        admin_url + 'statement_report/table',
        undefined,
        undefined,
        fnServerParams,
        [0, 'desc']
    );

    $('#filter_client,#filter_project,#filter_staff,#filter_balance,#filter_date_range').on('change', function() {
        reportTable && reportTable.ajax.reload();
    });

    function reloadIfCustomDatesReady() {
        if ($('#filter_date_range').val() !== 'custom') return;
        var from = $('#custom_from').val();
        var to = $('#custom_to').val();
        if (from && to) reportTable && reportTable.ajax.reload();
    }

    $('#custom_from,#custom_to').on('change', reloadIfCustomDatesReady);

    $('#filter_date_range').on('change', function() {
        var isCustom = $(this).val() === 'custom';
        $('#custom_date_row').toggle(isCustom);
        if (!isCustom) {
            $('#custom_from,#custom_to').val('');
            reportTable && reportTable.ajax.reload();
        }
    });
});
</script>
</body>
</html>


