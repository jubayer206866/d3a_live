<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2">
                    <a href="javascript:void(0);" onclick="new_shipping_company(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('new_shipping_company') ?>
                    </a>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php render_datatable([
                                _l('id'),
                                _l('name'),
                                _l('link'),
                                _l('options')
                            ], 'shipping_company'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<!-- Add by Rifat..... -->
<div class="modal fade" id="shippingCompanyModal" tabindex="-1" role="dialog" aria-labelledby="shippingCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <?= form_open(admin_url('Settings/shipping_company_add'), ['id' => 'shipping-company-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="shippingCompanyModalLabel"><?= _l('add_shipping_company'); ?></h4>
            </div>
            <div class="modal-body">
                <?= render_input('name', 'name'); ?>
                <?= render_input('link', 'link'); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>

<!-- Add by Rifat....... -->
<script>
    initDataTable('.table-shipping_company', admin_url + 'Settings/table', [0], [0]);
    $(function() {
        appValidateForm($('#shipping-company-form'), {
            name: 'required',
            link: 'required'
        }, manage_shipping_company);
    });
    //Add by Rifat.......
    function edit_shipping_company(id) {
        $.get(admin_url + 'Settings/get_shipping_company/' + id, function(response) {
            response = JSON.parse(response);
            if (response.success) {
                 $('#shippingCompanyModalLabel').text('<?= _l('edit_shipping_company'); ?>');
                $('#shippingCompanyModal input[name="name"]').val(response.data.name);
                $('#shippingCompanyModal input[name="link"]').val(response.data.link);
                $('#shipping-company-form').attr('action', admin_url + 'Settings/shipping_company_edit/' + id);
                $('#shippingCompanyModal').modal('show');
            } else {
                alert_float('danger', response.message);
            }
        });
    }
    //Add by Rifat.......
    function manage_shipping_company(form) {
        var data = $(form).serialize();
        var url = $(form).attr('action');

        $.post(url, data)
            .done(function(response) {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.success) {
                    alert_float('success', response.message);
                    $('#shippingCompanyModal').modal('hide');
                    $('.table-shipping_company').DataTable().ajax.reload();

                    $('#shipping-company-form')[0].reset();
                    $('#shipping-company-form').attr('action', admin_url + 'Settings/shipping_company_add');
                } else {
                    alert_float('danger', response.message);
                }
            })
            .fail(function() {
                alert_float('danger', 'An error occurred');
            });

        return false;
    }

    function new_shipping_company() {
    $('#shippingCompanyModalLabel').text('<?= _l('add_shipping_company'); ?>')
    $('#shipping-company-form').attr('action', admin_url + 'Settings/shipping_company_add');
    $('#shipping-company-form')[0].reset();
    $('#shippingCompanyModal').modal('show');
}
</script>

</body>

</html>