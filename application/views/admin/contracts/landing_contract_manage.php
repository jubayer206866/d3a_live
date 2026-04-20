<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="tw-mb-3 flex flex-wrap sm:tw-space-x-2 rtl:sm:tw-space-x-reverse">
          <a href="<?= admin_url('contracts/add_landing'); ?>" class="btn btn-primary">
            <i class="fa-regular fa-plus tw-mr-1"></i> New Lending Contract
          </a>
          <?php if (is_admin()) { ?>
            <a href="<?= admin_url('contracts/landing_contract_template'); ?>" class="btn btn-default">
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
        ], 'landing_contracts'); ?>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
  $(function() {
    initDataTable('.table-landing_contracts', window.location.href, [2], [2], 'undefined', [1, 'asc']);
  });
</script>
</body>
</html>
