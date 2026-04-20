/**
 * ALB Invoice: amount = price (no qty multiplication)
 * Overrides calculate_total for .alb-invoice-form to use amount = rate
 */
(function() {
  if (typeof calculate_total !== 'function') return;

  var _original_calculate_total = calculate_total;
  calculate_total = function() {
    if ($('#invoice-form.alb-invoice-form').length === 0) {
      return _original_calculate_total.apply(this, arguments);
    }

    if ($("body").hasClass("no-calculate-total")) {
      return false;
    }

    var calculated_tax, taxrate, item_taxes, row, _amount, _tax_name,
      taxes = {}, taxes_rows = [], subtotal = 0, total = 0,
      total_discount_calculated = 0,
      rows = $(".table.has-calculations tbody tr.item"),
      discount_area = $("#discount_area"),
      adjustment = $('input[name="adjustment"]').val(),
      discount_percent = $('input[name="discount_percent"]').val(),
      discount_fixed = $('input[name="discount_total"]').val(),
      discount_total_type = $(".discount-total-type.selected"),
      discount_type = $('select[name="discount_type"]').val();

    $(".tax-area").remove();

    $.each(rows, function() {
      _amount = accounting.toFixed(
        parseFloat($(this).find("td.rate input").val()) || 0,
        app.options.decimal_places
      );
      _amount = parseFloat(_amount);

      $(this).find("td.amount").html(format_money(_amount, true));
      subtotal += _amount;
      row = $(this);
      item_taxes = $(this).find("select.tax").selectpicker("val");

      if (item_taxes) {
        $.each(item_taxes, function(i, taxname) {
          taxrate = row.find('select.tax [value="' + taxname + '"]').data("taxrate");
          calculated_tax = (_amount / 100) * taxrate;
          if (!taxes.hasOwnProperty(taxname)) {
            if (taxrate != 0) {
              _tax_name = taxname.split("|");
              var tax_row = '<tr class="tax-area"><td>' + _tax_name[0] + "(" + taxrate + '%)</td><td id="tax_id_' + slugify(taxname) + '"></td></tr>';
              $(discount_area).after(tax_row);
              taxes[taxname] = calculated_tax;
            }
          } else {
            taxes[taxname] = taxes[taxname] += calculated_tax;
          }
        });
      }
    });

    if (discount_percent !== "" && discount_percent != 0 && discount_type == "before_tax" && discount_total_type.hasClass("discount-type-percent")) {
      total_discount_calculated = (subtotal * discount_percent) / 100;
    } else if (discount_fixed !== "" && discount_fixed != 0 && discount_type == "before_tax" && discount_total_type.hasClass("discount-type-fixed")) {
      total_discount_calculated = discount_fixed;
    }

    $.each(taxes, function(taxname, total_tax) {
      if (discount_percent !== "" && discount_percent != 0 && discount_type == "before_tax" && discount_total_type.hasClass("discount-type-percent")) {
        var total_tax_calculated = (total_tax * discount_percent) / 100;
        total_tax = total_tax - total_tax_calculated;
      } else if (discount_fixed !== "" && discount_fixed != 0 && discount_type == "before_tax" && discount_total_type.hasClass("discount-type-fixed")) {
        var t = (discount_fixed / subtotal) * 100;
        total_tax = total_tax - (total_tax * t) / 100;
      }
      total += total_tax;
      total_tax = format_money(total_tax);
      $("#tax_id_" + slugify(taxname)).html(total_tax);
    });

    total = total + subtotal;

    if (discount_percent !== "" && discount_percent != 0 && discount_type == "after_tax" && discount_total_type.hasClass("discount-type-percent")) {
      total_discount_calculated = (total * discount_percent) / 100;
    } else if (discount_fixed !== "" && discount_fixed != 0 && discount_type == "after_tax" && discount_total_type.hasClass("discount-type-fixed")) {
      total_discount_calculated = discount_fixed;
    }

    total = total - total_discount_calculated;
    adjustment = parseFloat(adjustment);
    if (!isNaN(adjustment)) {
      total = total + adjustment;
    }

    var discount_html = "-" + format_money(total_discount_calculated);
    $('input[name="discount_total"]').val(accounting.toFixed(total_discount_calculated, app.options.decimal_places));
    $(".discount-total").html(discount_html);
    $(".adjustment").html(format_money(adjustment));
    $(".subtotal").html(format_money(subtotal) + hidden_input("subtotal", accounting.toFixed(subtotal, app.options.decimal_places)));
    $(".invoice_amount").html(format_money(total) + hidden_input("invoice_amount", accounting.toFixed(total, app.options.decimal_places)));
    $(document).trigger("sales-total-calculated");
  };
})();
