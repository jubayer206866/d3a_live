<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div id="vueApp">
            <div class="row">
                <div class="col-md-12">
                    <div class="tw-block md:tw-hidden">
                        <?php $this->load->view('admin/projects/stats'); ?>
                    </div>
                    <div class="_buttons">
                        <div class="md:tw-flex md:tw-items-center">
                            <?php if (staff_can('create', 'projects')) { ?>
                            <a href="<?= admin_url('projects/project'); ?>"
                                class="btn btn-primary pull-left display-block mright5">
                                <i class="fa-regular fa-plus tw-mr-1"></i>
                                <?= _l('new_project'); ?>
                            </a>
                            <?php } ?>
                            <a href="<?= admin_url('projects/gantt'); ?>"
                                data-toggle="tooltip"
                                data-title="<?= _l('project_gant'); ?>"
                                class="btn btn-default btn-with-tooltip sm:!tw-px-3">
                                <i class="fa fa-align-left" aria-hidden="true"></i>
                            </a>

                            <!-- Status Buttons -->
                            <div class="tw-hidden md:tw-block md:tw-ml-6 rtl:md:tw-mr-6">
                                <?php
                                $_where = '';
                                if (staff_cant('view', 'projects')) {
                                    $_where = 'id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . ')';
                                }
                                foreach ($statuses as $status) {
                                    $where_sql = $_where ? $_where . ' AND ' : '';
                                    $where_sql .= 'status=' . $status['id'];
                                ?>
                                <a href="#"
                                    class="status-button tw-bg-transparent tw-border tw-border-solid tw-border-neutral-300 tw-shadow-sm tw-py-1 tw-px-2 tw-rounded-lg tw-text-sm hover:tw-bg-neutral-200/60 tw-text-neutral-600"
                                    data-status-id="<?= $status['id']; ?>"
                                    @click.prevent="extra.projectsRules = <?= app\services\utilities\Js::from($table->findRule('status')->setValue([(int) $status['id']])); ?>">
                                    <span class="status-count tw-font-semibold tw-mr-1 rtl:tw-ml-1">
                                        <?= total_rows(db_prefix() . 'projects', $where_sql); ?>
                                    </span>
                                    <span style="color: <?= e($status['color']); ?>"><?= e($status['name']); ?></span>
                                </a>
                                <?php } ?>
                            </div>

                            <div class="ltr:tw-ml-auto rtl:tw-mr-auto">
                                <app-filters
                                    id="<?= $table->id(); ?>"
                                    view="<?= $table->viewName(); ?>"
                                    :rules="extra.projectsRules || <?= app\services\utilities\Js::from($this->input->get('status') ? $table->findRule('status')->setValue([(int) $this->input->get('status')]) : []); ?>"
                                    :saved-filters="<?= $table->filtersJs(); ?>"
                                    :available-rules="<?= $table->rulesJs(); ?>">
                                </app-filters>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </div>

                    <!-- Filters -->
                    <div class="row tw-mt-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="client_filter"><?= _l('customer'); ?></label>
                                <select 
                                    id="client_filter" 
                                    name="client_filter[]" 
                                    class="selectpicker" 
                                    multiple 
                                    data-width="100%" 
                                    data-none-selected-text="<?= _l('all'); ?>"
                                    data-live-search="true">
                                    <?php
                                    $this->db->select('DISTINCT(' . db_prefix() . 'projects.clientid), ' . db_prefix() . 'clients.company');
                                    $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid');
                                    $clients = $this->db->get(db_prefix() . 'projects')->result_array();
                                    foreach ($clients as $client): ?>
                                        <option value="<?= $client['clientid'] ?>"><?= e($client['company']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <?php
                        $this->db->select('DISTINCT(' . db_prefix() . 'staff.staffid), CONCAT(firstname, " ", lastname) as full_name');
                        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'project_members.staff_id');
                        $members = $this->db->get(db_prefix() . 'project_members')->result_array();
                        ?>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="worker"><?= _l('worker'); ?></label>
                                <select id="worker" name="worker[]" class="selectpicker" multiple data-width="100%"
                                    data-none-selected-text="<?= _l('all'); ?>" data-live-search="true">
                                    <?php foreach ($members as $member): ?>
                                        <option value="<?= $member['staffid'] ?>"><?= e($member['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="months-report" class="form-label font-weight-bold"><?= _l('date'); ?></label>
                            <select class="selectpicker form-control" name="months-report" id="months-report"
                                data-width="100%" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                <option value=""><?= _l('report_sales_months_all_time'); ?></option>
                                <option value="this_month"><?= _l('this_month'); ?></option>
                                <option value="1"><?= _l('last_month'); ?></option>
                                <option value="this_year"><?= _l('this_year'); ?></option>
                                <option value="last_year"><?= _l('last_year'); ?></option>
                                <option value="3"><?= _l('report_sales_months_three_months'); ?></option>
                                <option value="6"><?= _l('report_sales_months_six_months'); ?></option>
                                <option value="12"><?= _l('report_sales_months_twelve_months'); ?></option>
                                <option value="custom"><?= _l('custom_created_date'); ?></option>
                            </select>
                            <div class="row mt-2" id="custom-date-wrapper" style="display: none;">
                                <div class="col-md-6">
                                    <label for="report-from" class="form-label"><?= _l('report_sales_from_date'); ?></label>
                                    <div class="input-group date">
                                        <input type="text" class="form-control datepicker" id="report-from" name="report-from" />
                                        <div class="input-group-addon">
                                            <i class="fa-regular fa-calendar calendar-icon"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="report-to" class="form-label"><?= _l('report_sales_to_date'); ?></label>
                                    <div class="input-group date">
                                        <input type="text" class="form-control datepicker" id="report-to" name="report-to" />
                                        <div class="input-group-addon">
                                            <i class="fa-regular fa-calendar calendar-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="panel_s tw-mt-2">
                        <div class="panel-body">
                            <div class="panel-table-full">
                                <?= form_hidden('custom_view'); ?>
                                <?php $this->load->view('admin/projects/table_html'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('admin/projects/copy_settings'); ?>
<?php $this->load->view('admin/projects/open_mark_tasks_finished'); ?>
<?php init_tail(); ?>

<script>
var fnServerParams;

(function($) {
    "use strict";

    fnServerParams = {
        "client_filter[]": '[name="client_filter[]"]',
        "worker[]": '[name="worker[]"]',
        "date_range": '#months-report',
        "date_from": '#report-from',
        "date_to": '#report-to',
    };

    var ProjectsTable = initDataTable('.table-projects', admin_url + 'projects/table', [0], [0], fnServerParams, [0, 'desc']);

    // Reload table + update status counts
    function updateStatusCounts() {
        var data = {
            'client_filter[]': $('select[name="client_filter[]"]').val(),
            'worker[]': $('select[name="worker[]"]').val(),
            'date_range': $('#months-report').val(),
            'date_from': $('#report-from').val(),
            'date_to': $('#report-to').val()
        };
        $.get(admin_url + 'projects/get_status_counts', data, function(response){
            $.each(response, function(status_id, count){
                $('.status-button[data-status-id="'+status_id+'"] .status-count').text(count);
            });
        }, 'json');
    }

    $('select[name="client_filter[]"], select[name="worker[]"], #months-report, #report-from, #report-to').on('change', function() {
        ProjectsTable.ajax.reload(updateStatusCounts);
    });

})(jQuery);

$(function () {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
    });

    $('#months-report').on('change', function () {
        if ($(this).val() === 'custom') {
            $('#custom-date-wrapper').slideDown();
        } else {
            $('#custom-date-wrapper').slideUp();
            $('#report-from, #report-to').val('');
        }
    });
});

// Status modal + change (existing code)
$(document).on('change', '.project-status-select', function () {
    var status_id  = $(this).val();
    var project_id = $(this).data('project-id');
    openProjectStatusModal(status_id, project_id, this);
});

function openProjectStatusModal(status_id, project_id, target) {
    $("#open_mark_tasks_finished_modal").modal("show");

    var $checkboxWrapper = $(".project_marked_as_finished");
    var $checkbox = $("#project_marked_as_finished_email_to_contacts");

    if ([1,3,4,5].includes(parseInt(status_id))) {
        $checkboxWrapper.removeClass("hide");

        var label = '';
        switch(parseInt(status_id)) {
            case 1: label = 'Send Project Marked as Orders Confirmed email to customer contacts'; break;
            case 3: label = 'Send Project Marked as Goods Received email to customer contacts'; break;
            case 4: label = 'Send Project Marked as Finished email to customer contacts'; break;
            case 5: label = 'Send Project Marked as Container Loaded email to customer contacts'; break;
        }
        $checkboxWrapper.find('label[for="project_marked_as_finished_email_to_contacts"]').text(label);
    } else {
        $checkbox.prop("checked", false);
        $checkboxWrapper.addClass("hide");
    }

    var noticeWrapper = $(".recurring-tasks-notice");
    if ([2,3,4,5].includes(parseInt(status_id))) {
        if (noticeWrapper.length && target) {
            var notice = noticeWrapper.data("notice-text");
            notice = notice.replace("{0}", $(target).data("name"));
            noticeWrapper.html(notice).append('<input type="hidden" name="cancel_recurring_tasks" value="true">').removeClass("hide");
        }
        $("#open_mark_all_tasks_as_completed").prop("checked", true);
    } else {
        noticeWrapper.html("").addClass("hide");
        $("#open_mark_all_tasks_as_completed").prop("checked", false);
    }

    $("#open_project_mark_status_confirm").attr("data-status-id", status_id).attr("data-project-id", project_id);
}

function open_project_status_change(e) {
    var data = {};
    $(e).attr("disabled", true);

    data.project_id = $(e).data("project-id");
    data.status_id = $(e).data("status-id");

    if ([1,3,4,5].includes(parseInt(data.status_id))) {
        var $finishedInput = $("#project_marked_as_finished_email_to_contacts");
        if ($finishedInput.length) {
            data.project_marked_as_finished_email_to_contacts = $finishedInput.prop("checked") ? 1 : 0;
        }
    }

    data.open_mark_all_tasks_as_completed = $("#open_mark_all_tasks_as_completed").prop("checked") ? 1 : 0;
    data.cancel_recurring_tasks = $('input[name="cancel_recurring_tasks"]').val() ? true : false;
    data.open_notify_project_members_status_change = $("#open_notify_project_members_status_change").prop("checked") ? 1 : 0;

    $.post(admin_url + "projects/mark_as", data)
        .done(function (response) {
            response = JSON.parse(response);
            alert_float(response.success === true ? "success" : "warning", response.message);
            setTimeout(function () {
                window.location.reload();
            }, 700);
        })
        .fail(function () {
            window.location.reload();
        });
}

$(document).on('click', '#open_project_mark_status_confirm', function() {
    open_project_status_change(this);
});
</script>

</body>
</html>