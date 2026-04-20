<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2">
                    <a href="#" onclick="new_category(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('new_category'); ?>
                    </a>
                </div>

                <!-- Categories Datatable -->
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('#'),
                            _l('category'),
                            _l('use_category_floor'),
                            _l('floor_percent_of_pv'),
                            _l('floor_euro_per_cbm'),
                            _l('customs_duty'),
                            _l('vat_integre_percent'),
                            _l('excise_type'),
                            _l('excise_value'),
                        ], 'categories_table'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="category_modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title"><?= _l('add_category'); ?></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <?= form_open('admin/calculators/save_category', ['id' => 'category-form']); ?>
            <div class="modal-body">
              <?= form_hidden('id'); ?>
                <?php
                echo render_input('Category', _l('category'));

                echo render_select(
                    'Use_Category_Floor',
                    [
                        ['id' => 'Y', 'name' => _l('yes')],
                        ['id' => 'N', 'name' => _l('no')],
                    ],
                    ['id', 'name'],
                    _l('use_category_floor')
                );

                echo render_input(
                    'Floor_percent_of_pv',
                    _l('floor_percent_of_pv'),
                    '',
                    'number',
                    ['step' => '0.01']
                );

                echo render_input(
                    'Floor_euro_per_CBM',
                    _l('floor_euro_per_cbm'),
                    '',
                    'number',
                    ['step' => '0.01']
                );

                echo render_input(
                    'Customs_duty',
                    _l('customs_duty'),
                    '',
                    'number',
                    ['step' => '0.01']
                );

                echo render_input(
                    'VAT_integre_percent',
                    _l('vat_integre_percent'),
                    '',
                    'number',
                    ['step' => '0.01']
                );

                echo render_select(
                    'Excise_type',
                    [
                        ['id' => 'none', 'name' => _l('none')],
                        ['id' => 'percent_of_value', 'name' => _l('percent_of_value')],
                        ['id' => 'per_kg', 'name' => _l('per_kg')],
                    ],
                    ['id', 'name'],
                    _l('excise_type')
                );

                echo render_input(
                    'Excise_value',
                    _l('excise_value'),
                    '',
                    'number',
                    ['step' => '0.01']
                );
                ?>

            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?>
                </button>
            </div>

            <?= form_close(); ?>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function () {
    initDataTable(
        '.table-categories_table',admin_url + 'calculators/get_categories',[8], [8], 'undefined', [0, 'asc']);
});

function new_category() {
    $('#category-form')[0].reset();
    $('input[name="id"]').val('');
    $('.modal-title').text('Add Category');
    $('#category_modal').modal('show');
}

function edit_category(id) {
    $.get(admin_url + 'calculators/get_category/' + id, function (response) {
        let data = JSON.parse(response);

        $('#category_modal').modal('show');
        $('.modal-title').text('Edit Category');

        $('input[name="id"]').val(data.id);
        $('input[name="Category"]').val(data.category);
        $('select[name="Use_Category_Floor"]').val(data.use_category_floor).change();
        $('input[name="Floor_percent_of_pv"]').val(data.floor_percent_of_pv);
        $('input[name="Floor_euro_per_CBM"]').val(data.floor_euro_per_cbm);
        $('input[name="Customs_duty"]').val(data.customs_duty);
        $('input[name="VAT_integre_percent"]').val(data.vat_integre_percent);
        $('select[name="Excise_type"]').val(data.excise_type).change();
        $('input[name="Excise_value"]').val(data.excise_value);
    });
}

</script>
</body>
</html>
