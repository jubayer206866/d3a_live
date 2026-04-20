<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-9">
                <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                    Landing Contract Template
                </h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <?= form_open($this->uri->uri_string()); ?>
                        <div class="row">
                            <div class="col-md-12">
                                <?php $template_value = isset($template[0]['template']) ? $template[0]['template'] : ''; ?>
                                <?= render_textarea(
                                    'template',
                                    '',
                                    $template_value,
                                    ['data-url-converter-callback' => 'myCustomURLConverter'],
                                    [],
                                    '',
                                    'tinymce tinymce-manual'
                                ); ?>
                                <div class="btn-bottom-toolbar text-right">
                                    <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                                </div>
                            </div>
                        </div>
                        <?= form_close(); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3 lg:tw-sticky lg:tw-top-2">
                <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                    <?= _l('available_merge_fields'); ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row available_merge_fields_container">
                            <?php foreach ($available_merge_fields as $group): ?>
                                <?php foreach ($group as $key => $fields): ?>
                                    <div class="col-md-12 merge_fields_col">
                                        <h5 class="bold tw-text-base tw-rounded-lg tw-bg-neutral-50 tw-py-2 tw-px-3">
                                            <?= ucwords(str_replace(['-', '_'], ' ', $key)); ?>
                                        </h5>
                                        <?php foreach ($fields as $_field): ?>
                                            <p>
                                                <?= $_field['name']; ?>
                                                <span class="pull-right">
                                                    <a href="#" class="add_merge_field"><?= $_field['key']; ?></a>
                                                </span>
                                            </p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="btn-bottom-pusher"></div>
    </div>
</div>
<?php init_tail(); ?>

<script>
$(function () {
    init_editor('textarea[name="template"]', { urlconverter_callback: merge_field_format_url });

    $('.add_merge_field').on('click', function (e) {
        e.preventDefault();
        tinymce.activeEditor.execCommand('mceInsertContent', false, $(this).text());
    });

    appValidateForm($('form'), {
        template: 'required',
    });
});
</script>
</body>
</html>
