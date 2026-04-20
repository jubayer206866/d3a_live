<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="horizontal-scrollable-tabs preview-tabs-top panel-full-width-tabs">
    <div class="scroller arrow-left tw-mt-px"><i class="fa fa-angle-left"></i></div>
    <div class="scroller arrow-right tw-mt-px"><i class="fa fa-angle-right"></i></div>
    <div class="horizontal-tabs">
        <ul class="nav nav-tabs nav-tabs-horizontal no-margin" role="tablist">
            <li role="presentation" class=" <?php if ($group == 'project_overview') {
                echo 'active ';
            } ?>project_tab_overview">
                <a data-group="project_overview"
                    href="<?= site_url('projects/index/' . $project->id . '/' . md5($project->id) . '?group=project_overview'); ?>"
                    role="tab">
                    <i class="fa fa-th menu-icon" aria-hidden="true"></i>
                    <?= _l('project_overview'); ?></a>
            </li>
            <li role="presentation" class=" <?php if ($group == 'project_packing_list') {
                echo 'active ';
            } ?>project_tab_overview">
                <a data-group="project_packing_list"
                    href="<?= site_url('projects/index/' . $project->id . '/' . md5($project->id) . '?group=project_packing_list'); ?>"
                    role="tab">
                    <i class="fa fa-th menu-icon" aria-hidden="true"></i>
                    Packing List</a>
            </li>
            <?php if ($project->settings->available_features['project_files'] == 1) { ?>
                <li role="presentation" class=" <?php if ($group == 'project_files') {
                    echo 'active ';
                } ?>project_tab_files">
                    <a data-group="project_files"
                        href="<?= site_url('projects/index/' . $project->id . '/' . md5($project->id) . '?group=project_files'); ?>"
                        role="tab">
                        <i class="fa-solid fa-file menu-icon" aria-hidden="true"></i>
                        <?= _l('project_files'); ?></a>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>