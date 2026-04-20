<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table" data-order-col="4" data-order-type="desc">
  <thead>
    <tr>
      <th>
        <?= _l('project_file_filename'); ?>
      </th>
      <th>
        <?= _l('project_file__filetype'); ?>
      </th>
      <th>
        <?= _l('project_discussion_last_activity'); ?>
      </th>
      <th>
        <?= _l('project_discussion_total_comments'); ?>
      </th>
      <th>
        <?= _l('project_file_dateadded'); ?>
      </th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($files as $file) {
        $path = get_upload_path_by_type('project') . $project->id . '/' . $file['file_name']; ?>
    <tr>
      <td
        data-order="<?= e($file['file_name']); ?>">
          <?php if (is_image(PROJECT_ATTACHMENTS_FOLDER . $project->id . '/' . $file['file_name']) || (! empty($file['external']) && ! empty($file['thumbnail_link']))) {
              echo '<img class="project-file-image img-table-loading"  src="' . project_file_url($file, true) . '" width="100">';
              echo '</div>';
          }
 ?>
      </td>
      <td
        data-order="<?= e($file['filetype']); ?>">
        <?= e($file['filetype']); ?>
      </td>
      <td
        data-order="<?= e($file['last_activity']); ?>">
        <?= e(! is_null($file['last_activity']) ? time_ago($file['last_activity']) : _l('project_discussion_no_activity')); ?>
      </td>
      <?php $total_file_comments = total_rows(db_prefix() . 'projectdiscussioncomments', ['discussion_id' => $file['id'], 'discussion_type' => 'file']); ?>
      <td data-order="<?= e($total_file_comments); ?>">
        <?= e($total_file_comments); ?>
      </td>
      <td
        data-order="<?= e($file['dateadded']); ?>">
        <?= e(_dt($file['dateadded'])); ?>
      </td>
    </tr>
    <?php } ?>
  </tbody>
</table>
<div id="project_file_data"></div>