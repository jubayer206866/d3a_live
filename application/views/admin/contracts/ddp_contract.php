<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="container">
            <h4 class="bold page-title">DDP Contracts</h4>
            <?= form_open(
                isset($ddp) ? admin_url('contracts/edit_ddp/' . $ddp->id) : admin_url('contracts/save_ddp'),
                ['id' => 'ddp_contract_form']
            ); ?>
            <div class="panel_s">
                <div class="panel-body">
                    <div class="row justify-content-center">
                        <div class="col-md-12">
                            <?php
                            $fields = isset($ddp) ? json_decode($ddp->fields_value, true) : [];
                            ?>
                            <?= render_input('template_name', 'contract_title', isset($ddp) ? $ddp->name : ''); ?>
                            <?= render_date_input('date', 'date', isset($fields['date']) ? _d($fields['date']) : ''); ?>
                            <div class="form-group">
                                <label for="client"><?= _l('Select Company'); ?></label>
                                <select name="company" id="company" class="form-control">
                                    <option value=""><?= _l('dropdown_non_selected_tex'); ?></option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['userid']; ?>"
                                            data-client-name="<?= htmlspecialchars($client['company']); ?>"
                                            <?= (isset($fields['client']) && $fields['company'] == $client['userid']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($client['company']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?= render_input('company_name', 'company_name', isset($fields['company_name']) ? $fields['company_name'] : ''); ?>
                            <?= render_input('company_address', 'company_address', isset($fields['company_address']) ? $fields['company_address'] : ''); ?>
                            <?= render_input('company_city', 'company_city', isset($fields['company_city']) ? $fields['company_city'] : ''); ?>
                            <?= render_input('company_country', 'company_country', isset($fields['company_country']) ? $fields['company_country'] : ''); ?>
                            <?= render_input('company_vat_number', 'company_vat_number', isset($fields['company_vat_number']) ? $fields['company_vat_number'] : ''); ?>

                            <div class="form-group">
                                <label for="client"><?= _l('Select Client'); ?></label>
                                <select name="client" id="client" class="form-control">
                                    <option value=""><?= _l('dropdown_non_selected_tex'); ?></option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['userid']; ?>"
                                            data-client-name="<?= htmlspecialchars($client['company']); ?>"
                                            <?= (isset($fields['client']) && $fields['client'] == $client['userid']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($client['company']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?= render_input('client_name', 'client_name', isset($fields['client_name']) ? $fields['client_name'] : ''); ?>
                            <?= render_input('client_city', 'client_city', isset($fields['client_city']) ? $fields['client_city'] : ''); ?>
                            <?= render_input('client_country', 'client_country', isset($fields['client_country']) ? $fields['client_country'] : ''); ?>
                            <?= render_input('warehouse_location', 'warehouse_location', isset($fields['warehouse_location']) ? $fields['warehouse_location'] : ''); ?>
                            <?= render_input('service_charge', 'service_charge', isset($fields['service_charge']) ? $fields['service_charge'] : ''); ?>
                            <?= render_input('deposit_mode', 'deposit_mode', isset($fields['deposit_mode']) ? $fields['deposit_mode'] : ''); ?>
                            <?= render_input('deposit_value', 'deposit_value', isset($fields['deposit_value']) ? $fields['deposit_value'] : ''); ?>
                            <?= render_input('payment_mode', 'payment_mode', isset($fields['payment_mode']) ? $fields['payment_mode'] : ''); ?>
                            <?= render_input('account_currency', 'account_currency', isset($fields['account_currency']) ? $fields['account_currency'] : ''); ?>
                        </div>
                    </div>
                </div>
                <div class="panel-footer text-right">
                    <button type="submit" class="btn btn-primary" data-loading-text="<?= _l('wait_text'); ?>">
                        <?= _l('submit'); ?>
                    </button>
                </div>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
    $(document).ready(function () {
        $('#client').on('change', function () {
            var userid = $(this).val();
            if (userid) {
                $.ajax({
                    url: '<?= admin_url('contracts/get_client_details'); ?>',
                    type: 'POST',
                    data: { userid: userid },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            $('#client_name').val(response.client.company || '');
                            $('#client_city').val(response.client.city || '');
                            $('#client_country').val(response.client.short_name || '');
                        } else {
                            $('#client_city').val('');
                            $('#client_city').val('');
                            $('#client_country').val('');
                            alert('Client details not found.');
                        }
                    },
                    error: function () {
                        $('#client_city').val('');
                        $('#client_city').val('');
                        $('#client_country').val('');
                        alert('Error fetching client details.');
                    }
                });
            } else {
                $('#client_city').val('');
                $('#client_name').val('');
                $('#client_country').val('');
            }
        });

        $('#company').on('change', function () {
            var userid = $(this).val();
            var client = $(this).find('option:selected').data('client-name');
            if (userid) {
                $.ajax({
                    url: '<?= admin_url('contracts/get_client_details'); ?>',
                    type: 'POST',
                    data: { userid: userid },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            $('#company_name').val(response.client.company || '');
                            $('#company_address').val(response.client.address || '');
                            $('#company_vat_number').val(response.client.vat || '');
                            $('#company_city').val(response.client.city || '');
                            $('#company_country').val(response.client.short_name || '');
                        } else {
                            $('#company_name').val('');
                            $('#company_address').val('');
                            $('#company_vat_number').val('');
                            $('#company_city').val('');
                            $('#company_country').val('');
                            alert('Client details not found.');
                        }
                    },
                    error: function () {
                        $('#company_name').val('');
                        $('#company_address').val('');
                        $('#company_vat_number').val('');
                        $('#company_city').val('');
                        $('#company_country').val('');
                        alert('Error fetching client details.');
                    }
                });
            } else {
                $('#company_name').val('');
                $('#company_address').val('');
                $('#company_city').val('');
                $('#company_country').val('');
                $('#company_vat_number').val('');
            }
        });
    });
</script>

<style>
    .bold {
        font-weight: 600;
        font-size: 18px;
        margin-bottom: 20px;
    }
</style>
</body>

</html>