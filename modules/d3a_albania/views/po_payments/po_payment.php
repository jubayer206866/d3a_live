<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <?php $this->load->view('admin/includes/aside'); ?>
    <div id="content">
        <?php $this->load->view('admin/includes/header'); ?>
        <div class="content-area">
            <?php echo form_open(admin_url('d3a_albania/po_payment/' . ($po_payment ? $po_payment->id : '')), ['id' => 'alb-po-payment-form']); ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="no-margin"><?php echo $title; ?></h4>
                            <hr class="hr-panel-heading" />
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="po_invoice_id"><?php echo _l('po_invoice'); ?></label>
                                        <select name="po_invoice_id" id="po_invoice_id" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" required>
                                            <option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>
                                            <?php foreach($po_invoices as $po_invoice) { ?>
                                                <option value="<?php echo $po_invoice->id; ?>" <?php echo ($po_payment && $po_payment->po_invoice_id == $po_invoice->id) ? 'selected' : ''; ?>>
                                                    <?php echo $po_invoice->invoice_number . ' - ' . app_format_money($po_invoice->total_left, get_base_currency()); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="amount"><?php echo _l('payment_amount'); ?></label>
                                        <input type="number" name="amount" id="amount" class="form-control" value="<?php echo $po_payment ? $po_payment->amount : ''; ?>" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="date"><?php echo _l('payment_date'); ?></label>
                                        <input type="text" name="date" id="date" class="form-control datepicker" value="<?php echo $po_payment ? _d($po_payment->date) : _d(date('Y-m-d')); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="paymentmode"><?php echo _l('payment_mode'); ?></label>
                                        <select name="paymentmode" id="paymentmode" class="selectpicker" data-width="100%">
                                            <option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>
                                            <?php foreach($payment_modes as $mode) { ?>
                                                <option value="<?php echo $mode->id; ?>" <?php echo ($po_payment && $po_payment->paymentmode == $mode->id) ? 'selected' : ''; ?>>
                                                    <?php echo $mode->name; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="transactionid"><?php echo _l('transaction_id'); ?></label>
                                        <input type="text" name="transactionid" id="transactionid" class="form-control" value="<?php echo $po_payment ? $po_payment->transactionid : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="note"><?php echo _l('note'); ?></label>
                                        <textarea name="note" id="note" class="form-control" rows="3"><?php echo $po_payment ? $po_payment->note : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <?php if (is_admin() || staff_can('add_al_purchase_payment', 'd3a_albania')) { ?>
                            <div class="btn-bottom-toolbar text-right">
                                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>

