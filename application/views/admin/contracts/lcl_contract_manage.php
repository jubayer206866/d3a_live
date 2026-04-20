<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-3 flex flex-wrap sm:tw-space-x-2 rtl:sm:tw-space-x-reverse">
          <a href="<?= admin_url('contracts/add_lcl'); ?>" class="btn btn-primary">
            <i class="fa-regular fa-plus tw-mr-1"></i> New LCL Contract
          </a>
          <?php if (is_admin()) { ?>
            <a href="<?= admin_url('contracts/lcl_contract_template'); ?>" class="btn btn-default">
              <i class="fa-regular fa-plus tw-mr-1"></i> Edit Template
            </a>
          <?php } ?>
        </div>
      </div>
    </div>
    <div class="panel_s">
      <div class="panel-body panel-table-full">
        <?php render_datatable([
          _l('#'),
          _l('name'),
          _l('options'),
        ], 'lcl_contracts'); ?>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
  $(function() {
    initDataTable('.table-lcl_contracts', window.location.href, [2], [2], 'undefined', [1, 'asc']);
  });
</script>
</body>
</html>
