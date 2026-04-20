(function($) {
  "use strict";

  	var _project_id = $('input[name="_project_id"]').val();
	initDataTable('.table-table_pur_order', admin_url+'purchase/table_project_pur_order/'+_project_id);
	
	// Style the last row dynamically
	setTimeout(function() {
		var table = $('.table-table_pur_order').DataTable();
		if(table) {
			// Function to style the last row
			function styleLastRow() {
				// Remove previous last-row styling
				$('.table-table_pur_order tbody tr').removeClass('last-row-highlight');
				// Get the last visible row and add styling
				var lastRow = $('.table-table_pur_order tbody tr:last');
				if(lastRow.length > 0) {
					lastRow.addClass('last-row-highlight');
				}
			}
			
			// Apply styling on draw event (when table is redrawn due to filtering, pagination, etc.)
			table.on('draw', function() {
				styleLastRow();
			});
			
			// Apply styling on page change
			table.on('page.dt', function() {
				setTimeout(styleLastRow, 50);
			});
			
			// Apply styling on initial load
			setTimeout(styleLastRow, 200);
		}
	}, 300);

})(jQuery);
