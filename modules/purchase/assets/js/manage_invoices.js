(function($) {
	"use strict"; 
	var table_invoice = $('.table-table_pur_invoices');
	var Params = {
		"from_date": 'input[name="from_date"]',
        "to_date": 'input[name="to_date"]',
        "clients": "[name='clients[]']",
        "pur_orders": "[name='pur_orders[]']",
        "vendors": "[name='vendor_ft[]']",
        "payment_status": "[name='payment_status[]']"
    };

	var vendorInvoiceColIndex = 0;
    table_invoice.find('thead th').each(function(index) {
        var headerText = $(this).text().toLowerCase().trim();
        if (headerText.indexOf('invoice') !== -1 && headerText.indexOf('number') !== -1) {
            vendorInvoiceColIndex = index;
            return false; 
        }
    });
	
	// Only set default order if column exists
	if (table_invoice.find('thead th').length > vendorInvoiceColIndex) {
		initDataTable(table_invoice, admin_url+'purchase/table_pur_invoices',[], [], Params, [vendorInvoiceColIndex, "desc"]);
	} else {
		initDataTable(table_invoice, admin_url+'purchase/table_pur_invoices',[], [], Params);
	}
	$.each(Params, function(i, obj) {
        $('select' + obj).on('change', function() {  
            table_invoice.DataTable().ajax.reload()
                .columns.adjust()
                .responsive.recalc();
        });
    });

    $('input[name="from_date"]').on('change', function() {
        table_invoice.DataTable().ajax.reload()
                .columns.adjust()
                .responsive.recalc();
    });
    $('input[name="to_date"]').on('change', function() {
        table_invoice.DataTable().ajax.reload()
                .columns.adjust()
                .responsive.recalc();
    });
})(jQuery);