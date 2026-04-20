<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2">
                    <a href="#" onclick="new_ladder_rate(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('new_ladder_rate'); ?>
                    </a>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('#'),
                            _l('min_cbm'),
                            _l('max_cbm'),
                            _l('rate'),
                        ], 'ladder_rates_table'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ladder Rate Modal -->
<div class="modal fade" id="ladder_rate_modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= _l('add_ladder_rate'); ?></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <?= form_open('admin/calculators/save_ladder_rate', ['id' => 'ladder_rate-form']); ?>
            <div class="modal-body">
                <?= form_hidden('id'); ?>
                <?php
                echo render_input('Min_cbm', _l('min_cbm'), '', 'number', ['step' => '0.01']);
                echo render_input('Max_cbm', _l('max_cbm'), '', 'number', ['step' => '0.01']);
                echo render_input('Rate', _l('rate'), '', 'number', ['step' => '0.01']);
                ?>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function () {
    initDataTable(
        '.table-ladder_rates_table',
        admin_url + 'calculators/get_ladder_rates',
        [3],
        [3],
        'undefined',
        [0, 'asc']
    );
});

function new_ladder_rate() {
    $('#ladder_rate-form')[0].reset();
    $('input[name="id"]').val('');
    $('.modal-title').text('<?= _l('add_ladder_rate'); ?>');
    $('#ladder_rate_modal').modal('show');
}

function edit_ladder_rate(id) {
    $.get(admin_url + 'calculators/get_ladder_rate/' + id, function (response) {
        let data = JSON.parse(response);
        $('#ladder_rate_modal').modal('show');
        $('.modal-title').text('<?= _l('edit'); ?> <?= _l('ladder_rate'); ?>');
        $('input[name="id"]').val(data.id);
        $('input[name="Min_cbm"]').val(data.min_cbm);
        $('input[name="Max_cbm"]').val(data.max_cbm);
        $('input[name="Rate"]').val(data.rate);
    });
}
</script>
</body>
</html>
