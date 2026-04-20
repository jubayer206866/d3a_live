<?php
defined('BASEPATH') or exit('No direct script access allowed');

class project_status_received_notification extends App_mail_template
{
    protected $for = 'customer';

    protected $project_id;
    protected $contact_id;
    protected $contact;

    public $slug = 'project_status_received_notification';
    public $rel_type = 'project';

    public function __construct($project_id, $contact_id, $contact)
    {
        parent::__construct();
        $this->project_id = $project_id;
        $this->contact_id = $contact_id;
        $this->contact    = $contact;
    }

    public function build()
    {
        $this->to($this->contact['email'])
            ->set_rel_id($this->project_id)
            ->set_merge_fields(
                'client_merge_fields',
                $this->contact['userid'],
                $this->contact_id
            )
            ->set_merge_fields(
                'projects_merge_fields',
                $this->project_id,
                ['customer_template' => true]
            );
    }
}
