<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>


<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>Name</th>
      <th>Type</th>
      <th>Options</th>
    </tr>
  </thead>
  <tbody>
    <?php $count = 1; ?>
    <?php foreach ($contracts as $contract): ?>
      <tr>
        <td><?= $count++; ?></td>
        <td style="font-size:0.9em;"><?= $contract['name']; ?></td>
        <td><?= $contract['type_display']; ?></td>
        <td>
          <a href="<?= site_url('clients/print/' . $contract['id']); ?>" class="btn btn-sm btn-success" target="_blank">
            View
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($contracts)): ?>
      <tr>
        <td colspan="4" class="text-center">No contracts found</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>