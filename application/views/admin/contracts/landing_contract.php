<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="container">
            <h4 class="bold page-title">Landing Contracts</h4>
            <?= form_open(
                isset($landing) ? admin_url('contracts/edit_landing/' . $landing->id) : admin_url('contracts/save_landing'),
                ['id' => 'landing_contract_form']
            ); ?>
            <div class="panel_s">
                <div class="panel-body">
                    <div class="row justify-content-center">
                        <div class="col-md-12">
                            <?php
                            $fields = isset($landing) ? json_decode($landing->fields_value, true) : [];
                            ?>
                            <?= render_input('template_name', 'contract_title', isset($landing) ? $landing->name : ''); ?>
                            <?= render_date_input('date', 'date', isset($fields['date']) ? _d($fields['date']) : ''); ?>

                            <div class="form-group">
                                <label for="client"><?= _l('Select Trading Company'); ?></label>
                                <select name="trading_company" id="trading_company" class="form-control">
                                    <option value=""><?= _l('dropdown_non_selected_tex'); ?></option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['userid']; ?>"
                                            data-client-name="<?= htmlspecialchars($client['company']); ?>"
                                            <?= (isset($fields['client']) && $fields['trading_company'] == $client['userid']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($client['company']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?= render_input('trading_company_name', 'trading_company_name', isset($fields['trading_company_name']) ? $fields['trading_company_name'] : ''); ?>
                            <?= render_input('trading_company_address', 'trading_company_address', isset($fields['trading_company_address']) ? $fields['trading_company_address'] : ''); ?>
                            <?= render_input('trading_company_vat', 'trading_company_vat', isset($fields['trading_company_vat']) ? $fields['trading_company_vat'] : ''); ?>
                            <?= render_input('trading_company_city', 'trading_company_city', isset($fields['trading_company_city']) ? $fields['trading_company_city'] : ''); ?>
                            <?= render_input('trading_company_country', 'trading_company_country', isset($fields['trading_company_country']) ? $fields['trading_company_country'] : ''); ?>


                            <div class="form-group">
                                <label for="client"><?= _l('Select Lending Company'); ?></label>
                                <select name="lending_company" id="lending_company" class="form-control">
                                    <option value=""><?= _l('dropdown_non_selected_tex'); ?></option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['userid']; ?>"
                                            data-client-name="<?= htmlspecialchars($client['company']); ?>"
                                            <?= (isset($fields['client']) && $fields['lending_company'] == $client['userid']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($client['company']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?= render_input('lending_company_name', 'lending_company_name', isset($fields['lending_company_name']) ? $fields['lending_company_name'] : ''); ?>
                            <?= render_input('lending_company_address', 'lending_company_address', isset($fields['lending_company_address']) ? $fields['lending_company_address'] : ''); ?>
                            <?= render_input('lending_company_vat', 'lending_company_vat', isset($fields['lending_company_vat']) ? $fields['lending_company_vat'] : ''); ?>
                            <?= render_input('lending_company_city', 'lending_company_city', isset($fields['lending_company_city']) ? $fields['lending_company_city'] : ''); ?>
                            <?= render_input('lending_company_country', 'lending_company_country', isset($fields['lending_company_country']) ? $fields['lending_company_country'] : ''); ?>



                            <div class="form-group">
                                <label for="client"><?= _l('Select Borrowing Company'); ?></label>
                                <select name="borrowing_company" id="borrowing_company" class="form-control">
                                    <option value=""><?= _l('dropdown_non_selected_tex'); ?></option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['userid']; ?>"
                                            data-client-name="<?= htmlspecialchars($client['company']); ?>"
                                            <?= (isset($fields['client']) && $fields['borrowing_company'] == $client['userid']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($client['company']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?= render_input('borrowing_company_name', 'borrowing_company_name', isset($fields['borrowing_company_name']) ? $fields['borrowing_company_name'] : ''); ?>
                            <?= render_input('borrowing_company_address', 'borrowing_company_address', isset($fields['borrowing_company_address']) ? $fields['borrowing_company_address'] : ''); ?>
                            <?= render_input('borrowing_company_vat', 'borrowing_company_vat', isset($fields['borrowing_company_vat']) ? $fields['borrowing_company_vat'] : ''); ?>
                            <?= render_input('borrowing_company_city', 'borrowing_company_city', isset($fields['borrowing_company_city']) ? $fields['borrowing_company_city'] : ''); ?>
                            <?= render_input('borrowing_company_country', 'borrowing_company_country', isset($fields['borrowing_company_country']) ? $fields['borrowing_company_country'] : ''); ?>


                            <?= render_input('lending_amount', 'Lending Amount', isset($fields['lending_amount']) ? $fields['lending_amount'] : ''); ?>
                            <?= render_input('interest', 'Interest(%)', isset($fields['interest']) ? $fields['interest'] : ''); ?>
                            <?= render_input('days', 'Days (Landing Time)', isset($fields['days']) ? _d($fields['days']) : ''); ?>
                            <?= render_input('initial_paid_amount', 'Initial Paid Amount', isset($fields['initial_paid_amount']) ? $fields['initial_paid_amount'] : ''); ?>
                            <?= render_input('late_payment_amount_per_day', 'Late Payment Amount Per Day', isset($fields['late_payment_amount_per_day']) ? $fields['late_payment_amount_per_day'] : ''); ?>

                            <?= render_date_input('repayment_date', 'Repayment Date', isset($fields['repayment_date']) ? _d($fields['repayment_date']) : ''); ?>
                            <?= render_input('late_payment_days', 'Late Payment Days', isset($fields['late_payment_days']) ? $fields['late_payment_days'] : ''); ?>
                            <?= render_input('repayment_mode', 'Repayment Mode', isset($fields['repayment_mode']) ? $fields['repayment_mode'] : ''); ?>
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
<style>
    .bold {
        font-weight: 600;
        font-size: 18px;
        margin-bottom: 20px;
    }
</style>
</body>

</html>
<script>
    $(document).ready(function () {
        $('#borrowing_company').on('change', function () {
            var userid = $(this).val();
            if (userid) {
                $.ajax({
                    url: '<?= admin_url('contracts/get_client_details'); ?>',
                    type: 'POST',
                    data: { userid: userid },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            $('#borrowing_company_name').val(response.client.company || '');
                            $('#borrowing_company_address').val(response.client.address || '');
                            $('#borrowing_company_vat').val(response.client.vat || '');
                            $('#borrowing_company_city').val(response.client.city || '');
                            $('#borrowing_company_country').val(response.client.short_name || '');
                        } else {
                            $('#borrowing_company_name').val('');
                            $('#borrowing_company_address').val('');
                            $('#borrowing_company_vat').val('');
                            $('#borrowing_company_city').val('');
                            $('#borrowing_company_country').val('');
                            alert('Client details not found.');
                        }
                    },
                    error: function () {
                        $('#borrowing_company_name').val('');
                        $('#borrowing_company_address').val('');
                        $('#borrowing_company_vat').val('');
                        $('#borrowing_company_city').val('');
                        $('#borrowing_company_country').val('');
                        alert('Error fetching client details.');
                    }
                });
            } else {
                $('#borrowing_company_name').val('');
                $('#borrowing_company_address').val('');
                $('#borrowing_company_vat').val('');
                $('#borrowing_company_city').val('');
                $('#borrowing_company_country').val('');
            }
        });



        $('#lending_company').on('change', function () {
            var userid = $(this).val();
            if (userid) {
                $.ajax({
                    url: '<?= admin_url('contracts/get_client_details'); ?>',
                    type: 'POST',
                    data: { userid: userid },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            $('#lending_company_name').val(response.client.company || '');
                            $('#lending_company_address').val(response.client.address || '');
                            $('#lending_company_vat').val(response.client.vat || '');
                            $('#lending_company_city').val(response.client.city || '');
                            $('#lending_company_country').val(response.client.short_name || '');
                        } else {
                            $('#lending_company_name').val('');
                            $('#lending_company_address').val('');
                            $('#lending_company_vat').val('');
                            $('#lending_company_city').val('');
                            $('#lending_company_country').val('');
                            alert('Client details not found.');
                        }
                    },
                    error: function () {
                        $('#lending_company_name').val('');
                        $('#lending_company_address').val('');
                        $('#lending_company_vat').val('');
                        $('#lending_company_city').val('');
                        $('#lending_company_country').val('');
                        alert('Error fetching client details.');
                    }
                });
            } else {
                $('#lending_company_name').val('');
                $('#lending_company_address').val('');
                $('#lending_company_vat').val('');
                $('#lending_company_city').val('');
                $('#lending_company_country').val('');
            }
        });

        $('#trading_company').on('change', function () {
            var userid = $(this).val();
            if (userid) {
                $.ajax({
                    url: '<?= admin_url('contracts/get_client_details'); ?>',
                    type: 'POST',
                    data: { userid: userid },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            $('#trading_company_name').val(response.client.company || '');
                            $('#trading_company_address').val(response.client.address || '');
                            $('#trading_company_vat').val(response.client.vat || '');
                            $('#trading_company_city').val(response.client.city || '');
                            $('#trading_company_country').val(response.client.short_name || '');
                        } else {
                            $('#trading_company_name').val('');
                            $('#trading_company_address').val('');
                            $('#trading_company_vat').val('');
                            $('#trading_company_city').val('');
                            $('#trading_company_country').val('');
                            alert('Client details not found.');
                        }
                    },
                    error: function () {
                        $('#trading_company_name').val('');
                        $('#trading_company_address').val('');
                        $('#trading_company_vat').val('');
                        $('#trading_company_city').val('');
                        $('#trading_company_country').val('');
                        alert('Error fetching client details.');
                    }
                });
            } else {
                $('#trading_company_name').val('');
                $('#trading_company_address').val('');
                $('#trading_company_vat').val('');
                $('#trading_company_city').val('');
                $('#trading_company_country').val('');
            }
        });

    });
</script>