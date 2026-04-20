<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12 tw-mb-3 md:tw-mb-6">
                <div class="md:tw-flex md:tw-items-center">
                    <div class="tw-grow">
                        <h4 class="tw-my-0 tw-font-bold tw-text-xl">
                            <?= _l('alb_company_information'); ?>
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open_multipart(admin_url('d3a_albania/company_information')); ?>

                        <!-- ALB Company Logo (separate from main company logo) -->
                        <h5 class="tw-font-bold tw-mb-3"><?= _l('company_logo_light'); ?></h5>
                        <?php $alb_company_logo = get_option('alb_company_logo'); ?>
                        <?php if (!empty($alb_company_logo)) { ?>
                        <div class="row tw-mb-3">
                            <div class="col-md-9">
                                <img src="<?= base_url('uploads/alb_company/' . $alb_company_logo); ?>" class="img img-responsive" style="max-height: 80px;">
                            </div>
                            <?php if ((is_admin() || staff_can('view_al_settings', 'd3a_albania'))) { ?>
                            <div class="col-md-3 text-right">
                                <a href="<?= admin_url('d3a_albania/remove_alb_company_logo'); ?>" data-toggle="tooltip"
                                    title="<?= _l('settings_general_company_remove_logo_tooltip'); ?>"
                                    class="_delete text-danger"><i class="fa fa-remove"></i></a>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } elseif ((is_admin() || staff_can('view_al_settings', 'd3a_albania'))) { ?>
                        <div class="form-group">
                            <input type="file" name="alb_company_logo" class="form-control" accept=".jpg,.jpeg,.png,.gif,.svg">
                        </div>
                        <?php } ?>
                        <hr />

                        <!-- ALB Company Logo Dark -->
                        <h5 class="tw-font-bold tw-mb-3"><?= _l('company_logo_dark'); ?></h5>
                        <?php $alb_company_logo_dark = get_option('alb_company_logo_dark'); ?>
                        <?php if (!empty($alb_company_logo_dark)) { ?>
                        <div class="row tw-mb-3">
                            <div class="col-md-9">
                                <img src="<?= base_url('uploads/alb_company/' . $alb_company_logo_dark); ?>" class="img img-responsive" style="max-height: 80px;">
                            </div>
                            <?php if ((is_admin() || staff_can('view_al_settings', 'd3a_albania'))) { ?>
                            <div class="col-md-3 text-right">
                                <a href="<?= admin_url('d3a_albania/remove_alb_company_logo/dark'); ?>" data-toggle="tooltip"
                                    title="<?= _l('settings_general_company_remove_logo_tooltip'); ?>"
                                    class="_delete text-danger"><i class="fa fa-remove"></i></a>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } elseif ((is_admin() || staff_can('view_al_settings', 'd3a_albania'))) { ?>
                        <div class="form-group">
                            <input type="file" name="alb_company_logo_dark" class="form-control" accept=".jpg,.jpeg,.png,.gif,.svg">
                        </div>
                        <?php } ?>
                        <hr />

                        <!-- ALB Company Stamp / Signature -->
                        <h5 class="tw-font-bold tw-mb-3"><?= _l('signature_image'); ?></h5>
                        <?php $alb_signature_image = get_option('alb_signature_image'); ?>
                        <?php if (!empty($alb_signature_image)) { ?>
                        <div class="row tw-mb-3">
                            <div class="col-md-9">
                                <img src="<?= base_url('uploads/alb_company/' . $alb_signature_image); ?>" class="img img-responsive" style="max-height: 80px;">
                            </div>
                            <?php if ((is_admin() || staff_can('view_al_settings', 'd3a_albania'))) { ?>
                            <div class="col-md-3 text-right">
                                <a href="<?= admin_url('d3a_albania/remove_alb_signature_image'); ?>" class="_delete text-danger"><i class="fa fa-remove"></i> <?= _l('remove'); ?></a>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } elseif ((is_admin() || staff_can('view_al_settings', 'd3a_albania'))) { ?>
                        <div class="form-group">
                            <input type="file" name="alb_signature_image" class="form-control" accept=".jpg,.jpeg,.png">
                        </div>
                        <?php } ?>
                        <hr />

                        <!-- ALB Company Information (separate from main Settings > Company) -->
                        <h5 class="tw-font-bold tw-mb-3"><?= _l('company_information'); ?></h5>
                        <?php
                        $input_attrs = (is_admin() || staff_can('view_al_settings', 'd3a_albania')) ? [] : ['readonly' => true];
                        echo render_input('settings[invoice_company_name]', 'settings_sales_company_name', get_option('alb_invoice_company_name'), 'text', $input_attrs); ?>
                        <?php echo render_input('settings[invoice_company_address]', 'settings_sales_address', get_option('alb_invoice_company_address'), 'text', $input_attrs); ?>
                        <?php echo render_input('settings[invoice_company_city]', 'settings_sales_city', get_option('alb_invoice_company_city'), 'text', $input_attrs); ?>
                        <?php echo render_input('settings[company_state]', 'billing_state', get_option('alb_company_state'), 'text', $input_attrs); ?>
                        <?php echo render_input('settings[invoice_company_country_code]', 'settings_sales_country_code', get_option('alb_invoice_company_country_code'), 'text', $input_attrs); ?>
                        <?php echo render_input('settings[invoice_company_postal_code]', 'settings_sales_postal_code', get_option('alb_invoice_company_postal_code'), 'text', $input_attrs); ?>
                        <?php echo render_input('settings[invoice_company_phonenumber]', 'settings_sales_phonenumber', get_option('alb_invoice_company_phonenumber'), 'text', $input_attrs); ?>
                        <?php echo render_input('settings[company_vat]', 'company_vat_number', get_option('alb_company_vat'), 'text', $input_attrs); ?>
                        <hr />
                        <?php
                        $textarea_attrs = ['rows' => 6, 'style' => 'line-height:20px;'];
                        if (!(is_admin() || staff_can('view_al_settings', 'd3a_albania'))) {
                            $textarea_attrs['readonly'] = true;
                        }
                        echo render_textarea('settings[company_info_format]', 'company_info_format', clear_textarea_breaks(get_option('alb_company_info_format')), $textarea_attrs); ?>
                        <p class="text-muted">
                            <?= _l('available_merge_fields'); ?>:
                            <a href="#" class="settings-textarea-merge-field" data-to="company_info_format">{company_name}</a>,
                            <a href="#" class="settings-textarea-merge-field" data-to="company_info_format">{address}</a>,
                            <a href="#" class="settings-textarea-merge-field" data-to="company_info_format">{city}</a>,
                            <a href="#" class="settings-textarea-merge-field" data-to="company_info_format">{state}</a>,
                            <a href="#" class="settings-textarea-merge-field" data-to="company_info_format">{zip_code}</a>,
                            <a href="#" class="settings-textarea-merge-field" data-to="company_info_format">{country_code}</a>,
                            <a href="#" class="settings-textarea-merge-field" data-to="company_info_format">{phone}</a>,
                            <a href="#" class="settings-textarea-merge-field" data-to="company_info_format">{vat_number}</a>
                        </p>
                        <hr />

                        <!-- Bank Information (for ALB Invoice PDF) -->
                        <h5 class="tw-font-bold tw-mb-3"><?= _l('alb_bank_information'); ?></h5>
                        <?php
                        $bank_attrs = ['rows' => 10, 'style' => 'line-height:20px;'];
                        if (!(is_admin() || staff_can('view_al_settings', 'd3a_albania'))) {
                            $bank_attrs['readonly'] = true;
                        }
                        echo render_textarea('settings[bank_information]', 'alb_bank_information', clear_textarea_breaks(get_option('alb_bank_information')), $bank_attrs); ?>
                        <p class="text-muted">
                            <?= _l('alb_bank_information_help'); ?>
                            <br><a href="#" class="settings-textarea-merge-field" data-to="bank_information">{company_name}</a>
                        </p>
                        <hr />

                        <?php if ((is_admin() || staff_can('view_al_settings', 'd3a_albania'))) { ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-regular fa-check"></i> <?= _l('settings_save'); ?>
                        </button>
                        <?php } ?>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    $('a.settings-textarea-merge-field').on('click', function(e) {
        e.preventDefault();
        var to = $(this).data('to');
        var text = $(this).text();
        var textarea = $('textarea[name="settings[' + to + ']"]');
        if (textarea.length) {
            var cursorPos = textarea[0].selectionStart;
            var value = textarea.val();
            textarea.val(value.substring(0, cursorPos) + text + value.substring(cursorPos));
        }
    });
});
</script>
</body>
</html>
