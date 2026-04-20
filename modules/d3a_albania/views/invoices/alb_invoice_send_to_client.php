<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$invoice = isset($alb_invoice) ? $alb_invoice : null;
if (!$invoice || empty($invoice->clientid)) {
    return;
}
$template_disabled = isset($template_disabled) ? $template_disabled : false;
$template_id = isset($template_id) ? $template_id : 0;
$template_system_name = isset($template_system_name) ? $template_system_name : '';
$template = isset($template) ? $template : null;
$template_name = isset($template_name) ? $template_name : 'invoice-send-to-client';
?>
<div class="modal fade email-template"
    data-editor-id=".<?= 'tinymce-alb-' . $invoice->id; ?>"
    id="alb_invoice_send_to_client_modal" tabindex="-1" role="dialog" aria-labelledby="albInvoiceSendModalLabel">
    <div class="modal-dialog" role="document">
        <?= form_open(admin_url('d3a_albania/send_alb_invoice_to_email/' . $invoice->id)); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="albInvoiceSendModalLabel">
                    <?= _l('invoice_send_to_client_modal_heading'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <?php
                            $selected = [];
                            $contacts = $this->clients_model->get_contacts($invoice->clientid, ['active' => 1, 'invoice_emails' => 1]);
                            foreach ($contacts as $contact) {
                                $selected[] = $contact['id'];
                            }
                            if (count($selected) == 0) {
                                echo '<p class="text-danger">' . _l('sending_email_contact_permissions_warning', _l('customer_permission_invoice')) . '</p><hr />';
                            }
                            echo render_select('sent_to[]', $contacts, ['id', 'email', 'firstname,lastname'], 'invoice_estimate_sent_to_email', $selected, ['multiple' => true], [], '', '', false);
                            ?>
                        </div>
                        <?= render_input('cc', 'CC'); ?>
                        <hr />
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="attach_pdf" id="alb_attach_pdf" checked>
                            <label for="alb_attach_pdf"><?= _l('invoice_send_to_client_attach_pdf'); ?></label>
                        </div>
                        <hr />
                        <h5 class="bold"><?= _l('invoice_send_to_client_preview_template'); ?></h5>
                        <hr />
                        <?php
                        if ($template_disabled) {
                            echo '<div class="alert alert-danger">';
                            echo 'The email template <b><a href="' . admin_url('emails/email_template/' . $template_id) . '" target="_blank" class="alert-link">' . e($template_system_name) . '</a></b> is disabled. Click <a href="' . admin_url('emails/email_template/' . $template_id) . '" class="alert-link" target="_blank">here</a> to enable the email template in order to be sent successfully.';
                            echo '</div>';
                        }
                        $template_message = $template && isset($template->message) ? $template->message : '';
                        echo render_textarea('email_template_custom', '', $template_message, [], [], '', 'tinymce-alb-' . $invoice->id);
                        echo form_hidden('template_name', $template_name);
                        ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" autocomplete="off" data-loading-text="<?= _l('wait_text'); ?>" class="btn btn-primary"><?= _l('send'); ?></button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>
