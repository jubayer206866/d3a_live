<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2">
                    <a href="#" onclick="new_material(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('new_material'); ?>
                    </a>
                </div>

                <!-- Materials Datatable -->
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('#'),
                            _l('material_name'),       
                            _l('excise_type'),         
                            _l('excise_value'),        
                            _l('vat_on_excise'),       
                        ], 'materials'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Material Modal -->
<div class="modal fade" id="material_modal" tabindex="-1" role="dialog" aria-labelledby="materialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title"><?= _l('add_material'); ?></h4>
</div>


            <?= form_open('admin/calculators/save_material', ['id' => 'material-form']); ?>
            <div class="modal-body">
                <?= form_hidden('id'); ?>
                <?php
                echo render_input('Material', _l('material'));
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
                echo render_input('Excise_value', _l('excise_value'), '', 'number', ['step' => '0.01']);
                echo render_select(
                    'VAT_on_Excise',
                    [
                        ['id' => 'Y', 'name' => _l('yes')],
                        ['id' => 'N', 'name' => _l('no')],
                    ],
                    ['id', 'name'],
                    _l('vat_on_excise')
                );
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
$(function() {
    initDataTable('.table-materials', admin_url + 'calculators/get_materials', [4], [4], 'undefined', [0, 'asc']);

});

function new_material() {
    $('#material-form')[0].reset();
    $('input[name="id"]').val('');
    $('.modal-title').text('Add Material');
    $('#material_modal').modal('show');
}

function edit_material(id) {
    $.get(admin_url + 'calculators/get_material/' + id, function (response) {

        let data = JSON.parse(response);

        $('#material_modal').modal('show');
        $('.modal-title').text('Edit Material');

        $('input[name="id"]').val(data.id);
        $('input[name="Material"]').val(data.material);
        $('select[name="Excise_type"]').val(data.excise_type).change();
        $('input[name="Excise_value"]').val(data.excise_value);
        $('select[name="VAT_on_Excise"]').val(data.vat_on_excise).change();
    });
}

</script>
</body>
</html>
