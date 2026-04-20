<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?= form_open($this->uri->uri_string(), ['id' => 'invoice-form', 'class' => '_transaction_form invoice-form alb-invoice-form']); ?>
            <?php if (isset($invoice)) {
                echo form_hidden('isedit');
            } ?>
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    <span>
                        <?= e(isset($invoice) ? format_alb_invoice_number_custom($invoice) : _l('create_new_invoice')); ?>
                    </span>
                    <?= isset($invoice) ? format_invoice_status($invoice->status) : ''; ?>
                </h4>
                <?php $this->load->view('d3a_albania/invoices/invoice_template'); ?>
            </div>
            <?= form_close(); ?>
            <?php $this->load->view('admin/invoice_items/item'); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script src="<?= module_dir_url(D3A_ALBANIA_MODULE_NAME, 'assets/js/alb_invoice_edit.js'); ?>"></script>
<script>
    $(function() {
        // Custom validation for ALB invoice form
        validate_alb_invoice_form();
        // Init accountacy currency symbol
        init_invoice_currency();
        // Project ajax search
        init_ajax_project_search_by_customer_id();
        // Maybe items ajax search
        init_ajax_search('items', '#item_select.ajax-search', undefined, admin_url + 'items/search');
        // Initialize item sorting
        init_items_sortable();
        // Initialize calculations
        calculate_total();
        
        // Debug form submission
        $('#invoice-form').on('submit', function(e) {
            console.log('Form is being submitted');
            console.log('Items in table:', $('.invoice-items-table tbody tr.item').length);
            console.log('New items:', $('input[name^="newitems"]').length);
            console.log('Existing items:', $('input[name^="items"]').length);
        });
        
        // Debug item addition
        $('body').on('click', 'button[onclick*="add_item_to_table"]', function(e) {
            console.log('Add item button clicked');
            console.log('Preview values:', get_item_preview_values());
        });
    });
    
    function validate_alb_invoice_form() {
        appValidateForm($('#invoice-form'), {
            clientid: {
                required: {
                    depends: function () {
                        var customerRemoved = $("select#clientid").hasClass("customer-removed");
                        return !customerRemoved;
                    },
                },
            },
            date: "required",
            invoice_currency: "required",
            number: {
                required: true,
            },
        });
        
        $("body")
            .find('input[name="number"]')
            .rules("add", {
                remote: {
                    url: admin_url + "d3a_albania/validate_invoice_number",
                    type: "post",
                    data: {
                        number: function () {
                            return $('input[name="number"]').val();
                        },
                        isedit: function () {
                            return $('input[name="number"]').data("isedit");
                        },
                        original_number: function () {
                            return $('input[name="number"]').data("original-number");
                        },
                        date: function () {
                            return $('input[name="date"]').val();
                        },
                        invoice_id: function () {
                            return $('input[name="id"]').val();
                        },
                    },
                },
                messages: {
                    remote: app.lang.invoice_number_exists,
                },
            });
    }
</script>
</body>

</html>