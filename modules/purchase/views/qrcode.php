<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head_custom(); ?>
<style>
  .row {
    display: flex;
    flex-wrap: wrap;
  }

  .column {
    padding-top: 5px;
    width: 650px;
    margin: 5px;
    margin-bottom: 60px;
  }

  #base {
    margin: 0 0 0 100px;
    padding: 0;
    background: #f4f4f5;
    position: relative;
    min-height: 100%;
  }

  .label-container {
    border: 3px solid #ddd;
  }

  .logo-content {
    display: flex;
    justify-content: center;
  }

  .logo-center {
    text-align: center;
    width: 30%;
  }

  .center-text {
    text-align: center;
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 0px !important;
    padding: 10px;
    height: 40px;
  }

  .table {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
    margin-top: 0px !important;
    margin-bottom: 0px !important;
  }

  .table td {
    font-size: 18px;
    font-weight: 600;
  }

  .barcode-cell {
    height: 60px;
    border-right: 3px solid;
    border-bottom: 3px solid;
    border-top: 3px solid;
  }

  .qty-cell {
    height: 60px;
    border: 3px solid;
    vertical-align: middle;
    border-top: 3px solid;
  }





  .ce-logo img {
    margin-top: -10px;
    width: 20%;
  }

  .import-text {
    flex: 1;
    text-align: center;
    font-size: 14px;
    font-weight: bold;
  }

  .design-container {
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: Arial, sans-serif;
    font-size: 18px;
    color: black;
    padding: 10px;
    font-weight: 600;
  }


  .d-print-none {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
  }

  @media print {
    .d-print-none {
      display: none !important;
    }

    .page-break {
      page-break-after: always;
    }
  }
  .centerid {
    height: 90px;
    padding: 0px;
  }

</style>

<div id="base">
  <div>
    <button class="btn btn-primary btn-lg d-print-none" onclick="window.print()"
      style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
      <i class="fas fa-print"></i>
      <?php echo _l('print'); ?>
    </button>
  </div>

  <div class="row">
    <?php foreach ($items as $key => $item) { ?>
      <?php
      $parts = preg_split('/[\s]+/', $item['item_name']);
      ?>
      <div class="column">
        <div class="label-container" style="border: 3px solid #000;">
          <div class="logo mb-2" style="border-bottom: 3px solid #000;">
            <p class="center-text centerid mb-3" style="font-size:60px; font-weight:800;">
              <?php echo $details[0]['shipping_mark']; ?>
            </p>
          </div>
          <div class="logo mb-2" style="border-bottom: 3px solid #000;">
            <p class="center-text mb-3">
            <?php 
if (isset($item['long_description']) && !empty($item['long_description'])) {
    $string = $item['long_description'];
    $length = strlen($string);

    if ($length > 45) {
        
        echo substr($string, 0, 46); 
    } else {
      
        echo $string;
    }
}
?>


           

            </p>
          </div>

          <div class="design-container">
            <span style="margin-right: auto; margin-left: 10px;">CE</span>
            <span> <?php echo $item['commodity_code']; ?></span>

            <span style="margin-left: auto; margin-right: 10px;">
              <i class="fa-solid fa-recycle"></i>
            </span>
          </div>

          <table class="table">
            <tr style="border-top: 3px solid black;">
              <td class="barcode-cell" style="width: 240px;">
                <?php
                $name = $item['item_code'] . '.png';
                $Path = FCPATH . 'modules/warehouse/uploads/print_item/' . md5($item['commodity_barcode'] ?? '') . '.svg';
                ?>

<img
                  src="<?= base_url('modules/warehouse/uploads/print_item/' . md5(trim($item['commodity_barcode'])) . '.svg'); ?>"
                  alt="SVG Image" class="img-responsive"
                  style="width: 100%; max-width: 400px; margin-top: 65px; padding: 10px;">
                <b><?php echo $item['commodity_barcode']; ?></b>
                <div style="border-bottom: 2px solid #000; width: 100%; margin: 6px 0;"></div>
                <b><?php echo _l('QTY'); ?>: <?php echo $item['cope_koli']; ?> PCS</b>
              </td>
              <td class="barcode-cell" style="width: 180px;  height: 200px; overflow: hidden;">
                <?php

                if (file_exists('modules/purchase/uploads/item_img/' . $arr_images[$item['item_code']][0]['rel_id'] . '/' . $arr_images[$item['item_code']][0]['file_name'])) {
                  $Path = site_url('modules/purchase/uploads/item_img/' . $arr_images[$item['item_code']][0]['rel_id'] . '/' . $arr_images[$item['item_code']][0]['file_name']);

                } else {
                  $Path = site_url('modules/warehouse/uploads/nul_image.jpg');
                }
                $name = $item['item_code'] . '.png'; ?>

                <a class="" style="width: 100%; display: block;">
                  <img src="<?= e($Path); ?>" class="img-responsive" alt="<?= e($parts[0]); ?>"
                    style="width: auto; height: 200px; object-fit: cover; display: inline-block; " />
                </a>
              </td>
              <td class="barcode-cell" style="width: 180px; height: 200px; overflow: hidden; border-right: 0px;">
                <?php
                $name = $item['item_code'] . '.png';
                $Path = base_url('modules/purchase/uploads/qr_code/' . $name); ?>
                <a class="" style="width: 100%; display: block;">
                  <img src="<?= e($Path); ?>" class="img-responsive" alt="<?= e($parts[0]); ?>"
                    style="width: auto; height: 200px; object-fit: cover;" />
                </a>
              </td>
            </tr>
          </table>
        </div>
      </div>
      <?php if (($key + 1) % 2 == 0) { ?>
        <div class="page-break"></div>
      <?php } ?>
    <?php } ?>
  </div>





</div>

<?php init_tail(); ?>
</body>

</html>