<script>
    function albania_add_item_to_table(data, itemid) {
    "use strict";

    data = typeof (data) == 'undefined' || data == 'undefined' ? albania_get_item_preview_values() : data;

    if (data.quantity == "") {
      return;
    }
    var currency_rate = $('input[name="currency_rate"]').val();
    var to_currency = $('select[name="currency"]').val();
    var table_row = '';
    var item_key = lastAddedItemKey ? lastAddedItemKey += 1 : $("body").find('.invoice-items-table tbody .item').length + 1;
    lastAddedItemKey = item_key;
    $("body").append('<div class="dt-loader"></div>');
    albania_get_item_row_template('newitems[' + item_key + ']', data.item_name, data.description, data.quantity, data.unit_name, data.unit_price, data.taxname, data.item_code, data.unit_id, data.tax_rate, data.discount, itemid, currency_rate, to_currency).done(function (output) {
      table_row += output;

      $('.invoice-item table.invoice-items-table.items tbody').append(table_row);

      setTimeout(function () {
        albania_calculate_total();
      }, 15);
      init_selectpicker();
      albania_reorder_items('.invoice-item');
      albania_clear_item_preview_values('.invoice-item');
      $('body').find('#items-warning').remove();
      $("body").find('.dt-loader').remove();
      $('#item_select').selectpicker('val', '');

      return true;
    });
    return false;
  }

    function albania_delete_item(row, itemid, parent) {
    "use strict";

    $(row).parents('tr').addClass('animated fadeOut', function () {
      setTimeout(function () {
        $(row).parents('tr').remove();
        albania_calculate_total();
      }, 50);
    });
    if (itemid && $('input[name="isedit"]').length > 0) {
      $(parent + ' #removed-items').append(hidden_input('removed_items[]', itemid));
    }
  }
  
  // Alias for delete_item to work with invoice template
  function delete_item(row, itemid, parent) {
    "use strict";
    parent = typeof parent === 'undefined' ? '.invoice-item' : parent;
    albania_delete_item(row, itemid, parent);
  }
  
    function albania_get_item_preview_values() {
    "use strict";

    var response = {};
    // Look for preview fields in the main row
    response.item_name = $('.invoice-item .main .preview_item_name textarea[name="item_name"]').val() || $('.invoice-item .main textarea[name="item_name"]').val() || '';
    response.description = $('.invoice-item .main .preview_description textarea[name="description"]').val() || $('.invoice-item .main textarea[name="description"]').val() || '';
    response.quantity = $('.invoice-item .main .preview_quantity input[name="quantity"]').val() || $('.invoice-item .main input[name="quantity"]').val() || '';
    response.unit_name = $('.invoice-item .main .preview_unit_name input[name="unit_name"]').val() || $('.invoice-item .main input[name="unit_name"]').val() || '';
    response.unit_price = $('.invoice-item .main .preview_unit_price input[name="unit_price"]').val() || $('.invoice-item .main input[name="unit_price"]').val() || '';
    response.taxname = $('.invoice-item .main .preview_taxname select[name="tax_select"]').selectpicker('val') || $('.main select.taxes').selectpicker('val') || '';
    response.item_code = $('.invoice-item .main .preview_item_code input[name="item_code"]').val() || $('.invoice-item .main input[name="item_code"]').val() || '';
    response.unit_id = $('.invoice-item .main .preview_unit_id input[name="unit_id"]').val() || $('.invoice-item .main input[name="unit_id"]').val() || '';
    response.tax_rate = $('.invoice-item .main .preview_tax_rate input[name="tax_rate"]').val() || $('.invoice-item .main input[name="tax_rate"]').val() || '';
    response.discount = $('.invoice-item .main .preview_discount input[name="discount"]').val() || $('.invoice-item .main input[name="discount"]').val() || '';


    return response;
  }
   function albania_get_item_row_template(name, item_name, description, quantity, unit_name, unit_price, taxname, item_code, unit_id, tax_rate, discount, item_key, currency_rate, to_currency) {
    "use strict";

    jQuery.ajaxSetup({
      async: false
    });

    var postData = {
      name: (name !== undefined && name !== null) ? String(name) : '',
      item_name: (item_name !== undefined && item_name !== null) ? String(item_name) : '',
      item_description: (description !== undefined && description !== null) ? String(description) : '',
      quantity: (quantity !== undefined && quantity !== null) ? String(quantity) : '',
      unit_name: (unit_name !== undefined && unit_name !== null) ? String(unit_name) : '',
      unit_price: (unit_price !== undefined && unit_price !== null) ? String(unit_price) : '',
      taxname: (taxname !== undefined && taxname !== null) ? (Array.isArray(taxname) ? taxname.join(',') : String(taxname)) : '',
      item_code: (item_code !== undefined && item_code !== null) ? String(item_code) : '',
      unit_id: (unit_id !== undefined && unit_id !== null) ? String(unit_id) : '',
      tax_rate: (tax_rate !== undefined && tax_rate !== null) ? String(tax_rate) : '',
      discount: (discount !== undefined && discount !== null) ? String(discount) : '',
      item_key: (item_key !== undefined && item_key !== null) ? String(item_key) : '',
      currency_rate: (currency_rate !== undefined && currency_rate !== null) ? String(currency_rate) : '',
      to_currency: (to_currency !== undefined && to_currency !== null) ? String(to_currency) : ''
    };
    

    
    var d = $.post(admin_url + 'd3a_albania/get_albania_invoice_row_template', postData);
    jQuery.ajaxSetup({
      async: true
    });
    return d;
  }
  function albania_calculate_total(from_discount_money) {
    "use strict";
    if ($('body').hasClass('no-calculate-total')) {
      return false;
    }

    var calculated_tax,
      taxrate,
      item_taxes,
      row,
      _amount,
      _tax_name,
      taxes = {},
      taxes_rows = [],
      subtotal = 0,
      total = 0,
      total_money = 0,
      total_tax_money = 0,
      quantity = 1,
      total_discount_calculated = 0,
      item_total_payment,
      rows = $('.table.has-calculations tbody tr.item'),
      subtotal_area = $('#subtotal'),
      discount_area = $('#discount_area'),
      adjustment = $('input[name="adjustment"]').val(),
      // discount_percent = $('input[name="discount_percent"]').val(),
      discount_percent = 'before_tax',
      discount_fixed = $('input[name="discount_total"]').val(),
      discount_total_type = $('.discount-total-type.selected'),
      discount_type = $('select[name="discount_type"]').val(),
      additional_discount = $('input[name="additional_discount"]').val(),
      add_discount_type = $('select[name="add_discount_type"]').val();

    var shipping_fee = $('input[name="shipping_fee"]').val();
    if (shipping_fee == '') {
      shipping_fee = 0;
      $('input[name="shipping_fee"]').val(0);
    }

    $('.wh-tax-area').remove();

    $.each(rows, function () {
      var item_discount = 0;
      var item_discount_money = 0;
      var item_discount_from_percent = 0;
      var item_discount_percent = 0;
      var item_tax = 0,
        item_amount = 0;

      quantity = $(this).find('[data-quantity]').val();
      if (quantity === '') {
        quantity = 1;
        $(this).find('[data-quantity]').val(1);
      }
      item_discount_percent = $(this).find('td.discount input').val();
      item_discount_money = $(this).find('td.discount_money input').val();

      if (isNaN(item_discount_percent) || item_discount_percent == '') {
        item_discount_percent = 0;
      }

      if (isNaN(item_discount_money) || item_discount_money == '') {
        item_discount_money = 0;
      }

      if (from_discount_money == 1 && item_discount_money > 0) {
        $(this).find('td.discount input').val('');
      }

      _amount = accounting.toFixed($(this).find('td.rate input').val(), app.options.decimal_places);
      item_amount = _amount;
      _amount = parseFloat(_amount);

      $(this).find('td.into_money').html(format_money(_amount));
      $(this).find('td._into_money input').val(_amount);

      subtotal += _amount;
      row = $(this);
      item_taxes = $(this).find('select.taxes').val();

      if (discount_type == 'after_tax') {
        if (item_taxes) {
          $.each(item_taxes, function (i, taxname) {
            taxrate = row.find('select.taxes [value="' + taxname + '"]').data('taxrate');
            calculated_tax = (_amount / 100 * taxrate);
            item_tax += calculated_tax;
            if (!taxes.hasOwnProperty(taxname)) {
              if (taxrate != 0) {
                _tax_name = taxname.split('|');
                var tax_row = '<tr class="wh-tax-area"><td>' + _tax_name[0] + '(' + taxrate + '%)</td><td id="tax_id_' + slugify(taxname) + '"></td></tr>';
                $(subtotal_area).after(tax_row);
                taxes[taxname] = calculated_tax;
              }
            } else {
              // Increment total from this tax
              taxes[taxname] = taxes[taxname] += calculated_tax;
            }
          });
        }
      }

      //Discount of item
      if (item_discount_percent > 0 && from_discount_money != 1) {


        if (discount_type == 'after_tax') {
          item_discount_from_percent = (parseFloat(item_amount) + parseFloat(item_tax)) * parseFloat(item_discount_percent) / 100;
        } else if (discount_type == 'before_tax') {
          item_discount_from_percent = parseFloat(item_amount) * parseFloat(item_discount_percent) / 100;
        }

        if (item_discount_from_percent != item_discount_money) {
          item_discount_money = item_discount_from_percent;
        }
      }

      if (item_discount_money > 0) {
        item_discount = parseFloat(item_discount_money);
      }

      item_total_payment = parseFloat(item_amount) + parseFloat(item_tax) - parseFloat(item_discount);
      // Append value to item
      total_discount_calculated += parseFloat(item_discount);
      $(this).find('td.discount_money input').val(item_discount);


      if (discount_type == 'before_tax') {
        if (item_taxes) {
          var after_dc_amount = _amount - parseFloat(item_discount);
          $.each(item_taxes, function (i, taxname) {
            taxrate = row.find('select.taxes [value="' + taxname + '"]').data('taxrate');
            calculated_tax = (after_dc_amount / 100 * taxrate);
            item_tax += calculated_tax;
            if (!taxes.hasOwnProperty(taxname)) {
              if (taxrate != 0) {
                _tax_name = taxname.split('|');
                var tax_row = '<tr class="wh-tax-area"><td>' + _tax_name[0] + '(' + taxrate + '%)</td><td id="tax_id_' + slugify(taxname) + '"></td></tr>';
                $(subtotal_area).after(tax_row);
                taxes[taxname] = calculated_tax;
              }
            } else {
              // Increment total from this tax
              taxes[taxname] = taxes[taxname] += calculated_tax;
            }
          });
        }
      }

      var after_tax = _amount + item_tax;
      var before_tax = _amount;

      item_total_payment = parseFloat(item_amount) + parseFloat(item_tax) - parseFloat(item_discount);

      $(this).find('td.total_after_discount input').val(item_total_payment);

      $(this).find('td.label_total_after_discount').html(format_money(item_total_payment));

      $(this).find('td._total').html(format_money(after_tax));
      $(this).find('td._total_after_tax input').val(after_tax);

      $(this).find('td.tax_value input').val(item_tax);

    });

    var order_discount_percent = $('input[name="order_discount"]').val();
    var order_discount_percent_val = 0;

    // Discount by percent
    if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'before_tax' && add_discount_type == 'percent') {
      total_discount_calculated += parseFloat((subtotal * order_discount_percent) / 100);
      order_discount_percent_val = (subtotal * order_discount_percent) / 100;
    } else if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'before_tax' && add_discount_type == 'amount') {
      total_discount_calculated += parseFloat(order_discount_percent);
      order_discount_percent_val = order_discount_percent;
    }

    $.each(taxes, function (taxname, total_tax) {
      if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'before_tax' && add_discount_type == 'percent') {
        var total_tax_calculated = (total_tax * order_discount_percent) / 100;
        total_tax = (total_tax - total_tax_calculated);
      } else if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'before_tax' && add_discount_type == 'amount') {
        var t = (order_discount_percent / subtotal) * 100;
        total_tax = (total_tax - (total_tax * t) / 100);
      }

      total += total_tax;
      total_tax_money += total_tax;
      total_tax = format_money(total_tax);
      $('#tax_id_' + slugify(taxname)).html(total_tax);
    });


    total = (total + subtotal);
    total_money = total;
    // Discount by percent
    if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'after_tax' && add_discount_type == 'percent') {
      total_discount_calculated += parseFloat((total * order_discount_percent) / 100);
      order_discount_percent_val = (total * order_discount_percent) / 100;
    } else if ((order_discount_percent !== '' && order_discount_percent != 0) && discount_type == 'after_tax' && add_discount_type == 'amount') {
      total_discount_calculated += parseFloat(order_discount_percent);
      order_discount_percent_val = order_discount_percent;
    }





    total = parseFloat(total) - parseFloat(total_discount_calculated) - parseFloat(additional_discount);
    adjustment = parseFloat(adjustment);

    // Check if adjustment not empty
    if (!isNaN(adjustment)) {
      total = total + adjustment;
    }

    total += parseFloat(shipping_fee);

    var discount_html = '-' + format_money(parseFloat(total_discount_calculated) + parseFloat(additional_discount));
    $('input[name="discount_total"]').val(accounting.toFixed(total_discount_calculated, app.options.decimal_places));

    // Append, format to html and display
    $('.shiping_fee').html(format_money(shipping_fee));
    $('.order_discount_value').html(format_money(order_discount_percent_val));
    $('.wh-total_discount').html(discount_html + hidden_input('dc_total', accounting.toFixed(order_discount_percent_val, app.options.decimal_places)));
    $('.adjustment').html(format_money(adjustment));
    $('.wh-subtotal').html(format_money(subtotal) + hidden_input('total_mn', accounting.toFixed(subtotal, app.options.decimal_places)));
    $('.wh-total').html(format_money(total) + hidden_input('grand_total', accounting.toFixed(total, app.options.decimal_places)));

    $(document).trigger('purchase-quotation-total-calculated');

  }
  function albania_reorder_items(parent) {
    "use strict";

    var rows = $(parent + ' .table.has-calculations tbody tr.item');
    var i = 1;
    $.each(rows, function () {
      $(this).find('input.order').val(i);
      i++;
    });
  }
  
  function albania_clear_item_preview_values(parent) {
    "use strict";

    var previewArea = $(parent + ' .main');
    previewArea.find('input').val('');
    previewArea.find('textarea').val('');
    previewArea.find('select').val('').selectpicker('refresh');
    // Clear preview fields specifically
    previewArea.find('.preview_item_name textarea').val('');
    previewArea.find('.preview_description textarea').val('');
    previewArea.find('.preview_quantity input').val('');
    previewArea.find('.preview_unit_name input').val('');
    previewArea.find('.preview_unit_price input').val('');
    previewArea.find('.preview_taxname select').val('').selectpicker('refresh');
    previewArea.find('.preview_item_code input').val('');
    previewArea.find('.preview_unit_id input').val('');
    previewArea.find('.preview_tax_rate input').val('');
    previewArea.find('.preview_discount input').val('');
  }
  
    function list_albania_invoices(id) {
        load_small_table_item(
            id,
            "#invoice",
            "invoiceid",
            "d3a_albania/get_albania_invoice_data_ajax",
            ".table-invoices"
        );
    }

    function load_small_table_item(id, selector, input_name, url, table) {
        var _tmpID = $('input[name="' + input_name + '"]').val();
        // Check if id passed from url, hash is prioritized becuase is last
        if (_tmpID !== "" && !window.location.hash) {
            id = _tmpID;
            // Clear the current id value in case user click on the left sidebar credit_note_ids
            $('input[name="' + input_name + '"]').val("");
        } else {
            // check first if hash exists and not id is passed, becuase id is prioritized
            if (window.location.hash && !id) {
                id = window.location.hash.substring(1); //Puts hash in variable, and removes the # character
            }
        }
        if (typeof id == "undefined" || id === "") {
            return;
        }
        destroy_dynamic_scripts_in_element($(selector));
        if (!$("body").hasClass("small-table")) {
            toggle_small_view(table, selector);
        }
        $('input[name="' + input_name + '"]').val(id);
        do_hash_helper(id);
        $(selector).load(admin_url + url + "/" + id)

        $("html, body").animate({
                scrollTop: $(selector).offset().top + (is_mobile() ? 150 : 0),
            },
            600
        );
    }

    function toggle_small_view(table, main_data) {
        if (
            !is_mobile() &&
            $("#small-table").hasClass("hide") &&
            $(".small-table-right-col").hasClass("col-md-12")
        ) {
            $("#small-table").toggleClass("hide");
            $(".small-table-right-col").toggleClass("col-md-12 col-md-7");
            $(window).trigger("resize");
            return;
        }
        $("body").toggleClass("small-table");
        var tablewrap = $("#small-table");
        if (tablewrap.length === 0) {
            return;
        }
        var _visible = false;
        if (tablewrap.hasClass("col-md-5")) {
            tablewrap.removeClass("col-md-5").addClass("col-md-12");
            _visible = true;
            $(".toggle-small-view")
                .find("i")
                .removeClass("fa fa-angle-double-right")
                .addClass("fa fa-angle-double-left");
        } else {
            tablewrap.addClass("col-md-5").removeClass("col-md-12");
            $(".toggle-small-view")
                .find("i")
                .removeClass("fa fa-angle-double-left")
                .addClass("fa fa-angle-double-right");
        }
        var _table = $(table).DataTable();
        // Show hide hidden columns
        _table.columns(hidden_columns).visible(_visible, false);
        _table.columns.adjust();
        $(main_data).toggleClass("hide");
        $(window).trigger("resize");
    }

    function do_hash_helper(hash) {
        if (typeof history.pushState != "undefined") {
            var url = window.location.href;
            var obj = {
                Url: url,
            };
            history.pushState(obj, "", obj.Url);
            window.location.hash = hash;
        }
    }
</script>