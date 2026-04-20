<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="open_mark_tasks_finished_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= _l('additional_action_required'); ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= _l('close'); ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="checkbox checkbox-primary">
                    <input type="checkbox" name="notify_project_members_status_change"
                           id="open_notify_project_members_status_change" checked>
                    <label for="open_notify_project_members_status_change">
                        <?= _l('notify_project_members_status_change'); ?>
                    </label>
                </div>

                <div class="checkbox checkbox-primary hide">
                    <input type="checkbox" name="mark_all_tasks_as_completed"
                           id="open_mark_all_tasks_as_completed">
                    <label for="open_mark_all_tasks_as_completed">
                        <?= _l('project_mark_all_tasks_as_completed'); ?>
                    </label>
                </div>

                <div class="form-group project_marked_as_finished hide no-mbot">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox" name="project_marked_as_finished_email_to_contacts"
                               id="project_marked_as_finished_email_to_contacts" checked>
                        <label for="project_marked_as_finished_email_to_contacts">
                            <?= _l('project_marked_as_finished_to_contacts'); ?>
                        </label>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" id="open_project_mark_status_confirm">
                    <?= _l('project_mark_tasks_finished_confirm'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
