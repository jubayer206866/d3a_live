(function() {
  "use strict";

  function add_alb_payment(invoiceId) {
    appValidateForm($('#albinvoice-add_payment-form'), {
      amount: 'required',
      date: 'required'
    });
    $('#payment_record_alb').modal('show');
  }

  window.add_alb_payment = add_alb_payment;


  function add_payment(){
    "use strict"; 
    appValidateForm($('#purinvoice-add_payment-form'),{amount:'required', date:'required'});
    $('#payment_record_pur').modal('show');
    $('.edit-title').addClass('hide');
    $('.add-title').removeClass('hide');
    $('#additional').html('');
  }

  window.add_payment = add_payment;
  $(function() {
    $("body").on("click", ".alb-invoice-send-to-client", function(e) {
      e.preventDefault();
      if ($(this).hasClass("disabled")) return false;
      $("#alb_invoice_send_to_client_modal").modal("show");
      return false;
    });

    $("body").on("submit", "#sales-notes.alb-invoice-notes-form", function() {
      var form = $(this);
      if (form.find('textarea[name="description"]').val() === "") {
        return false;
      }
      $.post(form.attr("action"), form.serialize()).done(function(rel_id) {
        form.find('textarea[name="description"]').val("");
        requestGet(admin_url + "d3a_albania/get_notes/" + rel_id).done(function(response) {
          $("#sales_notes_area").html(response);
          var totalNotesNow = $("#sales-notes-wrapper").attr("data-total");
          if (totalNotesNow > 0) {
            $(".notes-total").html('<span class="badge">' + totalNotesNow + "</span>").removeClass("hide");
          }
        });
      });
      return false;
    });
  });
})();
