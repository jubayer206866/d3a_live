<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$client_logged_in = $this->session->userdata('client_user_id') ?? null;

$show_project_financials = true;
if ($client_logged_in) {
    if ($project->clientid != $client_logged_in) {
        $show_project_financials = false;
    }
}
?>

<div class="row mtop15">
    <div class="col-md-3">
        <h4 class="tw-font-semibold tw-text-base tw-mt-0 tw-mb-4">
            <?= _l('project_overview'); ?>
        </h4>
        <!-- Project ID -->
        <div class="tw-flex tw-flex-col">
            <p><?= _l('project'); ?> <?= _l('the_number_sign'); ?></p>
            <p class="tw-font-bold"><?= e($project->id); ?></p>
        </div>
        <!-- Project Status -->
        <div class="tw-flex tw-flex-col">
            <p><?= _l('project_status'); ?></p>
            <p class="bold"><?= e($project_status['name']); ?></p>
        </div>
        <!-- Shipping Company -->
        <div class="tw-flex tw-flex-col">
            <p><?= _l('shipping_company'); ?></p>
            <p class="bold"><?= e($project->shipping_company_name); ?></p>
        </div>
        <!-- Container Number -->
        <div class="tw-flex tw-flex-col">
            <p><?= _l('create_container_number'); ?></p>
            <p class="bold"><?= e($project->create_container_number); ?></p>
        </div>
        <!-- Project Created Date -->
        <div class="tw-flex tw-flex-col">
            <p><?= _l('project_datecreated'); ?></p>
            <p class="bold"><?= e($project->project_created); ?></p>
        </div>
         <!-- Project Description -->
        <div class="tw-flex tw-flex-col">
            <p><?= _l('project_description'); ?></p>
            <p class="bold"><?php if (empty($project->description)) { ?>
                <span><?= _l('no_description_project'); ?></span>
                <?php } else { ?>
                    <?= check_for_links($project->description); ?>
                <?php } ?>
            </p>
        </div>
    </div>    
        <div class="tw-flex-1 tw-pl-4">
            <!-- Total Value of Goods -->
            <dt class="tw-text-sm tw-font-normal tw-text-neutral-500">
                <?= _l('total_goods_money'); ?>
            </dt>
            <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2">
                <?= e(app_format_money($total_goods_money, $currency)); ?>
            </dd>

            <!-- Project Expenses (Category-wise) -->
            <?php if (!empty($project_expenses_by_category)) { ?>
                <?php foreach ($project_expenses_by_category as $row) { ?>
                    <dt class="tw-text-sm tw-font-normal tw-text-neutral-500">
                        <?= e($row['category_name']); ?>
                    </dt>
                    <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2">
                        <?= e(app_format_money($row['total_amount'], $currency)); ?>
                    </dd>
                <?php } ?>
            <?php } ?>

            <!-- Total Project Value -->
            <dt class="tw-text-sm tw-font-normal tw-text-neutral-500">
                <?= _l('total_project_value'); ?>
            </dt>
            <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2">
                <?= e(app_format_money($total_project_value, $currency)); ?>
            </dd>
        </div>
        <div style="height:100%;">&nbsp;</div>
    <div class="col-md-6">
        <div
            class="tw-rounded-md tw-border tw-border-solid tw-border-neutral-100 tw-bg-neutral-50 tw-py-2 tw-px-3 tw-mb-3 hide">
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
        <?php if ($project->settings->view_tasks == 1) { ?>
        <div class="project-progress-bars tw-mb-3">
            <div class="tw-rounded-md tw-border tw-border-solid tw-border-neutral-100 tw-bg-neutral-50 tw-py-2 tw-px-3">
                <div class="row">
                    <div class="col-md-9">
                        <p class="bold text-dark font-medium tw-mb-0">
                            <span
                                dir="ltr"><?= e($tasks_not_completed); ?>
                                /
                                <?= e($total_tasks); ?></span>
                            <?= _l('project_open_tasks'); ?>
                        </p>
                        <p class="tw-text-neutral-600 tw-font-medium">
                            <?= e($tasks_not_completed_progress); ?>%
                        </p>
                    </div>
                    <div class="col-md-3 text-right">
                        <i class="fa-regular fa-check-circle <?= $tasks_not_completed_progress >= 100 ? 'text-success' : 'text-muted'; ?>"
                            aria-hidden="true"></i>
                    </div>
                    <div class="col-md-12">
                        <div class="progress tw-my-0 progress-bar-mini">
                            <div class="progress-bar progress-bar-success no-percent-text not-dynamic"
                                role="progressbar"
                                aria-valuenow="<?= e($tasks_not_completed_progress); ?>"
                                aria-valuemin="0" aria-valuemax="100" style="width: 0%"
                                data-percent="<?= e($tasks_not_completed_progress); ?>">
                            </div>
                        </div>
                    </div>
                </div>  
            </div>
        </div>
        <?php } ?>
        <?php if ($project->deadline) { ?>
        <div class="project-progress-bars">
            <div class="tw-rounded-md tw-border tw-border-solid tw-border-neutral-100 tw-bg-neutral-50 tw-py-2 tw-px-3">
                <div class="row">
                    <div class="col-md-9">
                        <p class="bold text-dark font-medium tw-mb-0">
                            <span
                                dir="ltr"><?= e($project_days_left); ?>
                                /
                                <?= e($project_total_days); ?></span>
                            <?= _l('project_days_left'); ?>
                        </p>
                        <p class="tw-text-neutral-600 tw-font-medium">
                            <?= e($project_time_left_percent); ?>%
                        </p>
                    </div>
                    <div class="col-md-3 text-right">
                        <i class="fa-regular fa-calendar-check <?= $project_time_left_percent >= 100 ? 'text-success' : 'text-muted'; ?>"
                            aria-hidden="true"></i>
                    </div>
                    <div class="col-md-12">
                        <div class="progress tw-my-0 progress-bar-mini">
                            <div class="progress-bar<?= $project_time_left_percent == 0 ? ' progress-bar-warning ' : ' progress-bar-success '; ?>no-percent-text not-dynamic"
                                role="progressbar"
                                aria-valuenow="<?= e($project_time_left_percent); ?>"
                                aria-valuemin="0" aria-valuemax="100" style="width: 0%"
                                data-percent="<?= e($project_time_left_percent); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</div>

    <div class="clearfix"></div>
    <?php if ($project->settings->view_finance_overview == 1) { ?>
    <div class="col-md-6 project-overview-column hide">
        <div class="row">
            <div class="col-md-12">
                <hr />
                <?php
                if ($project->billing_type == 3 || $project->billing_type == 2) { ?>
                <div class="row">
                    <div class="col-md-3">
                        <?php $data = $this->projects_model->total_logged_time_by_billing_type($project->id); ?>
                        <p class="tw-mb-0 text-muted">
                            <?= _l('project_overview_logged_hours'); ?>
                            <span
                                class="bold"><?= e($data['logged_time']); ?></span>
                        </p>
                        <p class="tw-font-medium tw-text-neutral-600 tw-mb-0">
                            <?= e(app_format_money($data['total_money'], $currency)); ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <?php $data = $this->projects_model->data_billable_time($project->id); ?>
                        <p class="text-info tw-mb-0">
                            <?= _l('project_overview_billable_hours'); ?>
                            <span
                                class="bold"><?= e($data['logged_time']) ?></span>
                        </p>
                        <p class="tw-font-medium tw-text-neutral-600 tw-mb-0">
                            <?= e(app_format_money($data['total_money'], $currency)); ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <?php $data = $this->projects_model->data_billed_time($project->id); ?>
                        <p class="text-success tw-mb-0">
                            <?= _l('project_overview_billed_hours'); ?>
                            <span
                                class="bold"><?= e($data['logged_time']); ?></span>
                        </p>
                        <p class="tw-font-medium tw-text-neutral-600 tw-mb-0">
                            <?= e(app_format_money($data['total_money'], $currency)); ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <?php $data = $this->projects_model->data_unbilled_time($project->id); ?>
                        <p class="text-danger tw-mb-0">
                            <?= _l('project_overview_unbilled_hours'); ?>
                            <span
                                class="bold"><?= e($data['logged_time']); ?></span>
                        </p>
                        <p class="tw-font-medium tw-text-neutral-600 tw-mb-0">
                            <?= e(app_format_money($data['total_money'], $currency)); ?>
                        </p>
                    </div>
                </div>
                <hr />
                <?php } ?>
            </div>
        </div>
        <?php if ($project->settings->available_features['project_expenses'] == 1) { ?>
        <div class="row hide">
            <div class="col-md-3">
                <p class="text-muted tw-mb-0">
                    <?= _l('project_overview_expenses'); ?></span>
                </p>
                <p class="tw-font-medium tw-font-neutral-500 tw-mb-0">
                    <?= e(app_format_money(sum_from_table(db_prefix() . 'expenses', ['where' => ['project_id' => $project->id, 'clientid' => $client_logged_in], 'field' => 'amount']), $currency)); ?>
                </p>
            </div>
            <div class="col-md-3">
                <p class="text-info tw-mb-0">
                    <?= _l('project_overview_expenses_billable'); ?></span>
                </p>
                <p class="tw-font-medium tw-font-neutral-500 tw-mb-0">
                    <?= e(app_format_money(sum_from_table(db_prefix() . 'expenses', ['where' => ['project_id' => $project->id, 'billable' => 1, 'clientid' => $client_logged_in], 'field' => 'amount']), $currency)); ?>
                </p>
            </div>
            <div class="col-md-3">
                <p class="text-success tw-mb-0">
                    <?= _l('project_overview_expenses_billed'); ?></span>
                </p>
                <p class="tw-font-medium tw-font-neutral-500 tw-mb-0">
                    <?= e(app_format_money(sum_from_table(db_prefix() . 'expenses', ['where' => ['project_id' => $project->id, 'invoiceid !=' => 'NULL', 'billable' => 1, 'clientid' => $client_logged_in], 'field' => 'amount']), $currency)); ?>
                </p>
            </div>
            <div class="col-md-3">
                <p class="text-danger tw-mb-0">
                    <?= _l('project_overview_expenses_unbilled'); ?></span>
                </p>
                <p class="tw-font-medium tw-font-neutral-500 tw-mb-0">
                    <?= e(app_format_money(sum_from_table(db_prefix() . 'expenses', ['where' => ['project_id' => $project->id, 'invoiceid IS NULL', 'billable' => 1, 'clientid' => $client_logged_in], 'field' => 'amount']), $currency)); ?>
                </p>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>