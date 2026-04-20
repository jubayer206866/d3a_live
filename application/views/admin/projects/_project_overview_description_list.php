<dl class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 tw-gap-x-4 tw-gap-y-3">
    <!-- Left Column -->
    <div class="tw-col-span-1">
        <!-- Customer -->
        <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('project_customer'); ?></dt>
        <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2">
            <a href="<?= admin_url(); ?>clients/client/<?= e($project->clientid); ?>">
                <?= e($project->client_data->company); ?>
            </a>
        </dd>
        
        <!-- Project ID -->
        <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('project'); ?> <?= _l('the_number_sign'); ?></dt>
        <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2"><?= e($project->id); ?></dd>

        <!-- Project Status -->
        <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('project_status'); ?></dt>
        <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2"><?= e($project_status['name']); ?></dd>

        <!-- Shipping Company -->
        <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('shipping_company'); ?></dt>
        <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2"><?= e($project->shipping_company_name); ?></dd>

        <!-- Container Number -->
        <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('create_container_number'); ?></dt>
        <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2"><?= e($project->create_container_number); ?></dd>

        <!-- Project Created Date -->
        <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('project_datecreated'); ?></dt>
        <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2"><?= e(_d($project->project_created)); ?></dd>

        <!-- Project Deadline -->
        <?php if ($project->deadline) { ?>
            <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('project_deadline'); ?></dt>
            <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2"><?= e(_d($project->deadline)); ?></dd>
        <?php } ?>

        <!-- Project Completed Date -->
        <?php if ($project->date_finished) { ?>
            <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('project_completed_date'); ?></dt>
            <dd class="tw-mt-1 tw-text-sm text-success tw-mb-2"><?= e(_dt($project->date_finished)); ?></dd>
        <?php } ?>

        <!-- Custom Fields -->
        <?php $custom_fields = get_custom_fields('projects'); ?>
        <?php if (count($custom_fields) > 0) { ?>
            <?php foreach ($custom_fields as $field) { ?>
                <?php $value = get_custom_field_value($project->id, $field['id'], 'projects'); ?>
                <?php if ($value == '') continue; ?>
                <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= e(ucfirst($field['name'])); ?></dt>
                <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2"><?= $value; ?></dd>
            <?php } ?>
        <?php } ?>

        <!-- Tags -->
        <?php $tags = get_tags_in($project->id, 'project'); ?>
        <?php if (count($tags) > 0) { ?>
            <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('tags'); ?></dt>
            <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2"><?= render_tags($tags); ?></dd>
        <?php } ?>

        <!-- Project Description -->
        <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('project_description'); ?></dt>
        <dd class="tw-mt-1 tw-text-sm tw-text-neutral-500 tw-mb-2">
            <?php if (empty($project->description)) { ?>
                <p class="tw-text-neutral-400 tw-mb-0"><?= _l('no_description_project'); ?></p>
            <?php } ?>
            <?= check_for_links($project->description); ?>
        </dd>

    </div>

    <!-- Right Column -->
    <div class="tw-col-span-1">
        <!-- Total Value of Goods -->
        <?php
        $this->load->database();

        $total_goods_money = $this->db
            ->select_sum('total_goods_money')
            ->where('project', $project->id)
            ->get('tblgoods_receipt')
            ->row()
            ->total_goods_money;

        ?>
        <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('total_goods_money'); ?></dt>
        <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2">
            <?= e(app_format_money($total_goods_money ?? 0, $currency)); ?>
        </dd>


        <!-- Project Expenses (Category-wise) -->
        <?php
        $total_expense_amount = 0;

        if (!empty($project_expenses_by_category)) {
            foreach ($project_expenses_by_category as $row) {
                $total_expense_amount += (float) $row['total_amount'];
        ?>
                <dt class="tw-text-sm tw-font-normal tw-text-neutral-500">
                    <?= e($row['category_name']); ?>
                </dt>
                <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2">
                    <?= app_format_money($row['total_amount'], get_base_currency()); ?>
                </dd>
        <?php
            }
        }
        ?>

        <!-- Total Project Value -->
        <?php
        $total_project_value = $total_goods_money + $total_expense_amount;
        ?>
        <dt class="tw-text-sm tw-font-normal tw-text-neutral-500"><?= _l('total_project_value'); ?></dt>
        <dd class="tw-mt-1 tw-text-sm tw-text-neutral-700 tw-font-medium tw-mb-2">
            <?= e(app_format_money($total_project_value, $currency)); ?>
        </dd>
    </div>
</dl>