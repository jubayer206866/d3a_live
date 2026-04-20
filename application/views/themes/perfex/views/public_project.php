<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?= form_hidden('project_id', $project->id); ?>
<div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
    <div class="tw-flex tw-items-center">
        <h4 class="tw-my-0 tw-font-bold tw-text-lg tw-text-neutral-700 section-heading section-heading-project">
            <?= e($project->name); ?>
        </h4>
        <?= '<span class="label project-status-' . $project_status['id'] . ' tw-ml-3" style="color:' . $project_status['color'] . ';border:1px solid ' . adjust_hex_brightness($project_status['color'], 0.4) . ';background: ' . adjust_hex_brightness($project_status['color'], 0.04) . ';">' . e($project_status['name']) . '</span>';
        ?>
    </div>
</div>
<div class="panel_s">
    <div class="panel-body">
        <?php get_template_part('public_projects/public_project_tabs'); ?>
        <div class="clearfix mtop15"></div>
        <?php get_template_part('public_projects/' . $group); ?>
    </div>
</div>