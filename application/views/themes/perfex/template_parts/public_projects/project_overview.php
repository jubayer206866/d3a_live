<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row mtop15">
    <div class="col-md-6">
        <h4 class="tw-font-semibold tw-text-base tw-mt-0 tw-mb-4">
            <?= _l('project_overview'); ?>
        </h4>
        <div class="tw-flex tw-space-x-4">
            <p class="bold">
                <?= _l('project'); ?>
            </p>
            <p><?= _l('the_number_sign'); ?><?= e($project->id); ?>
            </p>
        </div>
        <?php if ($project->settings->view_finance_overview == 1) { ?>
        <div class="project-billing-type tw-flex tw-space-x-4">
            <p class="bold">
                <?= _l('project_billing_type'); ?>
            </p>
            <p>
                <?php
                if ($project->billing_type == 1) {
                    $type_name = 'project_billing_type_fixed_cost';
                } elseif ($project->billing_type == 2) {
                    $type_name = 'project_billing_type_project_hours';
                } else {
                    $type_name = 'project_billing_type_project_task_hours';
                }
            echo e(_l($type_name));
            ?>
            </p>
        </div>
        <?php } ?>
        <div class="tw-flex tw-space-x-4">
            <p class="bold">
                <?= _l('project_status'); ?>
            </p>
            <p><?= e($project_status['name']); ?>
            </p>
        </div>
        <div class="tw-flex tw-space-x-4">
            <p class="bold">
                <?= _l('project_start_date'); ?>
            </p>
            <p><?= e(_d($project->start_date)); ?></p>
        </div>
        <?php if ($project->deadline) { ?>
        <div class="tw-flex tw-space-x-4">
            <p class="bold">
                <?= _l('project_deadline'); ?>
            </p>
            <p><?= e(_d($project->deadline)); ?></p>
        </div>
        <?php } ?>
        <?php if ($project->date_finished) { ?>
        <div class="text-success tw-flex tw-space-x-4">
            <p class="bold">
                <?= _l('project_completed_date'); ?>
            </p>
            <p><?= e(_dt($project->date_finished)); ?></p>
        </div>
        <?php } ?>
        <?php $custom_fields = get_custom_fields('projects', ['show_on_client_portal' => 1]); ?>
        <?php foreach ($custom_fields as $field) { ?>
        <?php $value = get_custom_field_value($project->id, $field['id'], 'projects');
            if ($value == '') {
                continue;
            } ?>
        <div class="tw-flex tw-space-x-4">
            <p class="bold">
                <?= e(ucfirst($field['name'])); ?>
            </p>
            <p><?= $value; ?></p>
        </div>
        <?php } ?>
    </div>
    <div class="col-md-6">
        <div
            class="tw-rounded-md tw-border tw-border-solid tw-border-neutral-100 tw-bg-neutral-50 tw-py-2 tw-px-3 tw-mb-3">
            <div class="row">
                <div class="col-md-9">
                    <p class="project-info tw-mb-2 tw-font-medium tw-tracking-tight">
                        <?= _l('project_progress_text'); ?>
                        <span
                            class="tw-text-neutral-500"><?= e($progress); ?>%</span>
                    </p>
                </div>
                <div class="col-md-3 text-right">
                    <i class="fa-solid fa-bars-progress text-muted" aria-hidden="true"></i>
                </div>
            </div>
            <div class="progress tw-my-0 progress-bar-mini">
                <div class="progress-bar progress-bar-success no-percent-text not-dynamic" role="progressbar"
                    aria-valuenow="<?= e($progress); ?>"
                    aria-valuemin="0" aria-valuemax="100" style="width: 0%"
                    data-percent="<?= e($progress); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="col-md-12">
        <hr />
    </div>
    <div class="clearfix"></div>
    <div class="col-md-12">
        <h4 class="tw-font-semibold tw-text-base tw-mt-0 tw-mb-4">
            <?= _l('project_description'); ?>
        </h4>
        <div class="tc-content project-description tw-text-neutral-600">
            <?php if (empty($project->description)) { ?>
            <p class="text-center tw-mb-0">
                <?= _l('no_description_project'); ?>
            </p>
            <?php } ?>
            <?= check_for_links($project->description); ?>
        </div>
    </div>
</div>