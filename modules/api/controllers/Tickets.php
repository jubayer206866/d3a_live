<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require __DIR__.'/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Tickets extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }

    /**
     * @api {get} api/tickets/:id Request Ticket information
     * @apiName GetTicket
     * @apiGroup Ticket
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id Ticket unique ID.
     *
     * @apiSuccess {Object} Ticket information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "ticketid": "7",
     *         "adminreplying": "0",
     *         "userid": "0",
     *         "contactid": "0",
     *         "email": null,
     *         "name": "Trung bình",
     *         "department": "1",
     *         "priority": "2",
     *         "status": "1",
     *         "service": "1",
     *         "ticketkey": "8ef33d61bb0f26cd158d56cc18b71c02",
     *         "subject": "Ticket ER",
     *         "message": "Ticket ER",
     *         "admin": "5",
     *         "date": "2019-04-10 03:08:21",
     *         "project_id": "5",
     *         "lastreply": null,
     *         "clientread": "0",
     *         "adminread": "1",
     *         "assigned": "5",
     *         "line_manager": "8",
     *         "milestone": "27",
     *         ...
     *     }
     * @apiError {Boolean} status Request status.
     * @apiError {String} message The id of the Ticket was not found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_get($id = '')
    {
        // If the id parameter doesn't exist return all the
        $data = $this->Api_model->get_table('tickets', $id);

        // Check if the data store contains
        if ($data)
        {
            $data = $this->Api_model->get_api_custom_data($data,"tickets", $id);

            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * @api {get} api/tickets/search/:keysearch Search Ticket Information
     * @apiName GetTicketSearch
     * @apiGroup Ticket
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} keysearch Search keywords.
     *
     * @apiSuccess {Object} Ticket information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "ticketid": "7",
     *         "adminreplying": "0",
     *         "userid": "0",
     *         "contactid": "0",
     *         "email": null,
     *         "name": "Trung bình",
     *         "department": "1",
     *         "priority": "2",
     *         "status": "1",
     *         "service": "1",
     *         "ticketkey": "8ef33d61bb0f26cd158d56cc18b71c02",
     *         "subject": "Ticket ER",
     *         "message": "Ticket ER",
     *         "admin": "5",
     *         "date": "2019-04-10 03:08:21",
     *         "project_id": "5",
     *         "lastreply": null,
     *         "clientread": "0",
     *         "adminread": "1",
     *         "assigned": "5",
     *         "line_manager": "8",
     *         "milestone": "27",
     *         ...
     *     }
     * @apiError {Boolean} status Request status.
     * @apiError {String} message The id of the Ticket was not found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_search_get($key = '')
    {
        $this->load->model('surveys/surveys_model');
        $key=$this->input->get('searchkey');

        $data = $this->Api_model->search('ticket', $key);

        foreach($data as $key=>$d){
            $surveyid = $d['surveyid'];
            $ticketid = $d['ticketid'];
            $purchase_id = $d['purchase_id'];
            
            if(!empty($surveyid)){
                $data[$key]['question'] = $this->surveys_model->get($surveyid,$d['ticketid']);
            }else{
                $data[$key]['question']=null;
            }

            if(!empty($ticketid)){
                $data[$key]['attachments']=$this->surveys_model->get_attachments($ticketid);
            }else{
                $data[$key]['attachments']=null;
            }

            if(!empty($ticketid)){
                $data[$key]['purchaseidtems']=$this->surveys_model->get_purchaseidtems($ticketid);
            }else{
                $data[$key]['purchaseidtems']=null;
            }
            // if(!empty($ticketid)){
            //     $this->db->select('*');
            //     $this->db->from('tblticket_attachments');
            //     $this->db->where('ticketid',  $ticketid);
            //     $ticket_attachments = $this->db->get();
            //     $data[$key]['attachments']=$ticket_attachments->result();
            // }else{
            //     $data[$key]['attachments']=null;
            // }

            // if(!empty($purchase_id)){
            //     $this->db->select('tblpur_order_detail.*');
            //     $this->db->from('tblpur_orders');
            //     $this->db->join('tblpur_order_detail', 'tblpur_order_detail.pur_order = tblpur_orders.id', 'left');
            //     $this->db->where('tblpur_orders.id',  $purchase_id);
            //     $purchaseidtems = $this->db->get();
            //     $data[$key]['purchaseidtems']=$purchaseidtems->result_array();
            // }else{
            //     $data[$key]['purchaseidtems']=null;
            // }
            // $data['attachments_purchaseitem']={$data[$key]['attachments']};

        }

        // Check if the data store contains
        if ($data)
        {
            $data = $this->Api_model->get_api_custom_data($data,"tickets");

            // Set the response and exit
            $this->response(['status' => TRUE,"result" => ['data' =>$data], REST_Controller::HTTP_OK]); // OK (200) being the HTTP response code
        }
        else
        {
            // Set the response and exit
            $this->response([
                'status' => false,
                "result" => ['data' =>[]],
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }
    /**
     * @api {post} api/tickets Add New Ticket
     * @apiName PostTicket
     * @apiGroup Ticket
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} subject                       Mandatory Ticket name .
     * @apiParam {String} department                    Mandatory Ticket Department.
     * @apiParam {String} contactid                     Mandatory Ticket Contact.
     * @apiParam {String} userid                        Mandatory Ticket user.
     * @apiParam {String} [project_id]                  Optional Ticket Project.
     * @apiParam {String} [message]                     Optional Ticket message.
     * @apiParam {String} [service]                     Optional Ticket Service.
     * @apiParam {String} [assigned]                    Optional Assign ticket.
     * @apiParam {String} [cc]                          Optional Ticket CC.
     * @apiParam {String} [priority]                    Optional Priority.
     * @apiParam {String} [tags]                        Optional ticket tags.
     *
     * @apiParamExample {Multipart Form} Request-Example:
     *    array (size=11)
     *     'subject' => string 'ticket name' (length=11)
     *     'contactid' => string '4' (length=1)
     *     'userid' => string '5' (length=1)
     *     'department' => string '2' (length=1)
     *     'cc' => string '' (length=0)
     *     'tags' => string '' (length=0)
     *     'assigned' => string '8' (length=1)
     *     'priority' => string '2' (length=1)
     *     'service' => string '2' (length=1)
     *     'project_id' => string '' (length=0)
     *     'message' => string '' (length=0)
     *
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Ticket add successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Ticket add successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Ticket add fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Ticket add fail."
     *     }
     * 
     */
    public function data_post()
    {
        // form validation
        $this->form_validation->set_rules('subject', 'Ticket Name', 'trim|required', array('is_unique' => 'This %s already exists please enter another Ticket Name'));
        $this->form_validation->set_rules('department', 'Department', 'trim|required', array('is_unique' => 'This %s already exists please enter another Ticket Department'));
        $this->form_validation->set_rules('contactid', 'Contact', 'trim|required', array('is_unique' => 'This %s already exists please enter another Ticket Contact'));
        if ($this->form_validation->run() == FALSE)
        {
            // form validation error
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors() 
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            $insert_data = [
                'subject' => $this->input->post('subject', TRUE),
                'department' => $this->input->post('department', TRUE),
                'contactid' => $this->input->post('contactid', TRUE),
                'userid' => $this->input->post('userid', TRUE),

                'cc' => $this->Api_model->value($this->input->post('cc', TRUE)),
                'tags' => $this->Api_model->value($this->input->post('tags', TRUE)),
                'assigned' => $this->Api_model->value($this->input->post('assigned', TRUE)),
                'priority' => $this->Api_model->value($this->input->post('priority', TRUE)),
                'service' => $this->Api_model->value($this->input->post('service', TRUE)),
                'project_id' => $this->Api_model->value($this->input->post('project_id', TRUE)),
                'message' => $this->Api_model->value($this->input->post('message', TRUE))
             ];
            if (!empty($this->input->post('custom_fields', TRUE))) {
                $insert_data['custom_fields'] = $this->Api_model->value($this->input->post('custom_fields', TRUE));
            }
               
            // insert data
            $this->load->model('tickets_model');
            $output = $this->tickets_model->add($insert_data);
            if($output > 0 && !empty($output)){
                // success
                $message = array(
                'status' => TRUE,
                'message' => 'Ticket add successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            }else{
                // error
                $message = array(
                'status' => FALSE,
                'message' => 'Ticket add fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {delete} api/delete/tickets/:id Delete a Ticket
     * @apiName DeleteTicket
     * @apiGroup Ticket
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id Ticket unique ID.
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Ticket Delete Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Ticket Delete Successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Ticket Delete Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Ticket Delete Fail."
     *     }
     */
    public function data_delete($id = '')
    {
        $id = $this->security->xss_clean($id);
        if(empty($id) && !is_numeric($id))
        {
            $message = array(
            'status' => FALSE,
            'message' => 'Invalid Ticket ID'
        );
        $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            // delete data
            $this->load->model('tickets_model');
            $output = $this->tickets_model->delete($id);
            if($output === TRUE){
                // success
                $message = array(
                'status' => TRUE,
                'message' => 'Ticket Delete Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            }else{
                // error
                $message = array(
                'status' => FALSE,
                'message' => 'Ticket Delete Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {put} api/tickets/:id Update a ticket
     * @apiName PutTicket
     * @apiGroup Ticket
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} subject                       Mandatory Ticket name .
     * @apiParam {String} department                    Mandatory Ticket Department.
     * @apiParam {String} contactid                     Mandatory Ticket Contact.
     * @apiParam {String} userid                        Mandatory Ticket user.
     * @apiParam {String} priority                      Mandatory Priority.
     * @apiParam {String} [project_id]                  Optional Ticket Project.
     * @apiParam {String} [message]                     Optional Ticket message.
     * @apiParam {String} [service]                     Optional Ticket Service.
     * @apiParam {String} [assigned]                    Optional Assign ticket.
     * @apiParam {String} [tags]                        Optional ticket tags.
     *
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *       "subject": "Ticket ER",
     *       "department": "1",
     *       "contactid": "0",
     *       "ticketid": "7",
     *       "userid": "0",
     *       "project_id": "5",
     *       "message": "Ticket ER",
     *       "service": "1",
     *       "assigned": "5",
     *       "priority": "2",
     *       "tags": ""
     *   }
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Ticket Update Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Ticket Update Successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Ticket Update Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Ticket Update Fail."
     *     }
     */
    public function data_put($id = '')
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if(empty($_POST ) || !isset($_POST ))
        {
            $message = array(
            'status' => FALSE,
            'message' => 'Data Not Acceptable OR Not Provided'
            );
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
        $this->form_validation->set_data($_POST);
        
        if(empty($id) && !is_numeric($id))
        {
            $message = array(
            'status' => FALSE,
            'message' => 'Invalid Ticket ID'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {

            $update_data = $this->input->post();
            // update data
            $this->load->model('tickets_model');
            $update_data['ticketid'] = $id;
            $output = $this->tickets_model->update_single_ticket_settings($update_data);
            if($output > 0 && !empty($output)){
                // success
                $message = array(
                'status' => TRUE,
                'message' => 'Ticket Update Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            }else{
                // error
                $message = array(
                'status' => FALSE,
                'message' => 'Ticket Update Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function acceptance_post()
    {
        $data['ticketid']=$this->input->post('ticketid', TRUE);
        $data['note']=$this->input->post('note', TRUE);
        $is_ticket_closed=$this->input->post('is_ticket_closed', TRUE);
        $note=$this->input->post('note', TRUE);
        $ticketid=$data['ticketid'];
        $date['dateadded']=date("Y-m-d");
        $dateadded=$date['dateadded'];
        $data['latitude']=$this->input->post('latitude', TRUE);
        $data['longitude']=$this->input->post('longitude', TRUE);
        $data['location_name']=$this->input->post('location_name', TRUE);
        $data['person_name']=$this->input->post('person_name', TRUE);
    
        $this->db->where('ticketid',$ticketid);
        $result = $this->db->get('tblticket_acceptances')->num_rows();
        
        $sql="Select assigned from tbltickets where ticketid=$ticketid";   
        $query = $this->db->query($sql);
        $data['assigned_member'] = $query->result_array();
        $assignedm=$data['assigned_member'];
        $data['assigned']=$assignedm['0']['assigned'];

        if(!empty($_FILES['file_name'])){
        // insert data
        $this->load->model('tickets_model');
        if($result>0){
            $this->db->where('ticketid', $ticketid);
            $acceptanceid=$this->db->update(db_prefix() . 'ticket_acceptances', [
                        'note' => $note,
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'location_name' => $data['location_name'],
                        'person_name' => $data['person_name'],
            ]);
        }else{
            $this->db->insert(db_prefix() . 'ticket_acceptances', [
                        'ticketid' => $ticketid,
                        'note' => $note,
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'location_name' => $data['location_name'],
                        'person_name' => $data['person_name'],
                        'staffid' => $data['assigned'],
            ]);
            $acceptanceid = $this->db->insert_id();
        }
        
        if($is_ticket_closed!='no'){
            $this->db->where('ticketid', $ticketid);
            $this->db->update(db_prefix() . 'tickets', [
                        'status' => 5,
            ]);
        }    
        
        // handle_acceptance_image_upload($acceptanceid,$ticketid);
        if (isset($_FILES['file_name']['name']) && $_FILES['file_name']['name'] != '') {
            hooks()->do_action('before_upload_staff_profile_image');
            $path = get_upload_path_by_type('ticket') . 'acceptance';
            // Get the temp file path
            $tmpFilePath = $_FILES['file_name']['tmp_name'];
            // Make sure we have a filepath

            if (!empty($tmpFilePath) && $tmpFilePath != '') {
            
                // Getting file extension
                $extension          = strtolower(pathinfo($_FILES['file_name']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = [
                    'jpg',
                    'jpeg',
                    'png',
                ];
    
                $allowed_extensions = hooks()->apply_filters('staff_profile_image_upload_allowed_extensions', $allowed_extensions);
    
                if (!in_array($extension, $allowed_extensions)) {
                    set_alert('warning', _l('file_php_extension_blocked'));
    
                    return false;
                }
                _maybe_create_upload_path($path);
                $filename    = unique_filename($path, $_FILES['file_name']['name']);
                $newFilePath = $path . '/' . $filename;
                
                // Upload the file into the company uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $CI                       = & get_instance();
                    $config                   = [];
                    $config['image_library']  = 'gd2';
                    $config['source_image']   = $newFilePath;
                    $config['new_image']      = 'thumb_' . $filename;
                    $config['maintain_ratio'] = true;
                    $config['width']          = hooks()->apply_filters('staff_profile_image_thumb_width', 320);
                    $config['height']         = hooks()->apply_filters('staff_profile_image_thumb_height', 320);
                    $CI->image_lib->initialize($config);
                    $CI->image_lib->resize();
                    $CI->image_lib->clear();
                    $config['image_library']  = 'gd2';
                    $config['source_image']   = $newFilePath;
                    $config['new_image']      = 'small_' . $filename;
                    $config['maintain_ratio'] = true;
                    $config['width']          = hooks()->apply_filters('staff_profile_image_small_width', 96);
                    $config['height']         = hooks()->apply_filters('staff_profile_image_small_height', 96);
                    $CI->image_lib->initialize($config);
                    $CI->image_lib->resize();
                    $CI->db->where('ticketid', $ticketid);
                    $CI->db->update(db_prefix() . 'ticket_acceptances', [
                        'file_name' => $filename,
                        'dateadded' => $dateadded,

                    ]);
                    // Remove original image
                    // unlink($newFilePath);
    
                    // return true;
                }
            }
        }
        }

        if (isset($_FILES['technical_signature']['name']) && $_FILES['technical_signature']['name'] != '') {
        hooks()->do_action('before_upload_staff_profile_image');
        $path = get_upload_path_by_type('ticket') . 'acceptance';
        // Get the temp file path
        $tmpFilePath = $_FILES['technical_signature']['tmp_name'];
        // Make sure we have a filepath

        if (!empty($tmpFilePath) && $tmpFilePath != '') {
        
            // Getting file extension
            $extension          = strtolower(pathinfo($_FILES['technical_signature']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
            ];

            $allowed_extensions = hooks()->apply_filters('staff_profile_image_upload_allowed_extensions', $allowed_extensions);

            if (!in_array($extension, $allowed_extensions)) {
                set_alert('warning', _l('file_php_extension_blocked'));

                return false;
            }
            _maybe_create_upload_path($path);
            $filename    = unique_filename($path, $_FILES['technical_signature']['name']);
            $newFilePath = $path . '/' . $filename;
            
            // Upload the file into the company uploads dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $filename;
                $config['maintain_ratio'] = true;
                $config['width']          = hooks()->apply_filters('staff_profile_image_thumb_width', 320);
                $config['height']         = hooks()->apply_filters('staff_profile_image_thumb_height', 320);
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $filename;
                $config['maintain_ratio'] = true;
                $config['width']          = hooks()->apply_filters('staff_profile_image_small_width', 96);
                $config['height']         = hooks()->apply_filters('staff_profile_image_small_height', 96);
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->db->where('ticketid', $ticketid);
                $CI->db->update(db_prefix() . 'ticket_acceptances', [
                    'technical_signature' => $filename,
                    'dateadded' => $dateadded,

                ]);
                // Remove original image
                // unlink($newFilePath);

                // return true;
            }
        }
        }

        if($acceptanceid > 0 && !empty($acceptanceid)){
            
                // success
                $message = array(
                'status' => TRUE,
                'message' => 'Acceptance add successful.'
                );
                // $this->response($message, REST_Controller::HTTP_OK);
                $this->response($message, REST_Controller::HTTP_OK);
        }else{
                // error
                $message = array(
                'status' => FALSE,
                'message' => 'Acceptance add fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        
    }
    public function evidance_post()
    {
        $data['ticketid']=$this->input->post('ticketid', TRUE);
        $data['filetype']=$this->input->post('filetype', TRUE);
        $filetype=$data['filetype'];
        $ticketid=$data['ticketid'];
        
        if (isset($_FILES['file_name']['name']) && ($_FILES['file_name']['name'] != '' || is_array($_FILES['file_name']['name']) && count($_FILES['file_name']['name']) > 0)) {
        hooks()->do_action('before_upload_staff_profile_image');

            if (!is_array($_FILES['file_name']['name'])) {
                $_FILES['file_name']['name']     = [$_FILES['file_name']['name']];
                $_FILES['file_name']['type']     = [$_FILES['file_name']['type']];
                $_FILES['file_name']['tmp_name'] = [$_FILES['file_name']['tmp_name']];
                $_FILES['file_name']['error']    = [$_FILES['file_name']['error']];
                $_FILES['file_name']['size']     = [$_FILES['file_name']['size']];
            }
            $path = get_upload_path_by_type('ticket') . 'evidanceimage';

            for ($i = 0; $i < count($_FILES['file_name']['name']); $i++) {
                if (_perfex_upload_error($_FILES['file_name']['error'][$i])) {
                    $errors[$_FILES['file_name']['name'][$i]] = _perfex_upload_error($_FILES['file_name']['error'][$i]);

                    continue;
                }

                // Get the temp file path
                $tmpFilePath = $_FILES['file_name']['tmp_name'][$i];
                // Make sure we have a filepath
                if (!empty($tmpFilePath) && $tmpFilePath != '') {
                    _maybe_create_upload_path($path);
                    $originalFilename = unique_filename($path, $_FILES['file_name']['name'][$i]);
                    $filename = app_generate_hash() . '.' . get_file_extension($originalFilename);

                    // In case client side validation is bypassed
                    // if (!_upload_extension_allowed($filename)) {
                    //     continue;
                    // }

                    $newFilePath = $path . '/' . $filename;
                    // Upload the file into the company uploads dir
                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                        $CI = & get_instance();
                        $data = [
                                'ticketid' => $ticketid,
                                'file_name'  => $filename,
                                'filetype'   => $filetype,
                                'dateadded'  => date('Y-m-d'),
                            ];
                        
                        $CI->db->insert(db_prefix() . 'ticket_evidanceimage', $data);

                        $insert_id = $CI->db->insert_id();
                        // echo $insert_id;exit;
                        if ($insert_id) {
                            if (is_image($newFilePath)) {
                                create_img_thumb($path, $filename);
                            }
                        } else {
                            unlink($newFilePath);

                            return false;
                        }
                    }
                }
            }
        }
            if($insert_id > 0 && !empty($insert_id)){
                // success
                $message = array(
                'status' => TRUE,
                'message' => 'Evidance add successful.'
                );
                // $this->response($message, REST_Controller::HTTP_OK);
                $this->response($message, REST_Controller::HTTP_OK);
            }else{
                // error
                $message = array(
                'status' => FALSE,
                'message' => 'Evidance add fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        
    }
    public function data_evidance_get(){
        
        $ticketid=$this->input->get('ticketid');
        $filetype=$this->input->get('filetype');
        
            $this->db->select();
            $this->db->from(db_prefix().'ticket_evidanceimage');
            $this->db->where('ticketid',$ticketid);
            $this->db->where('filetype',$filetype);
            $data = $this->db->get()->result_array();
        
        // Check if the data store contains
        if ($data){
            // Set the response and exit
            $this->response([
                'status' => TRUE,
                'data' =>$data
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            , REST_Controller::HTTP_NOT_FOUND]); // NOT_FOUND (404) being the HTTP response code
        }
    }
    public function data_survey_question_get(){
        $this->load->model('surveys/surveys_model');
        $ticketid=$this->input->get('ticketid');
        
        $this->db->select('*');
        $this->db->from('tbltickets');
        $this->db->where('ticketid',  $ticketid);
        $query = $this->db->get();
        $data['serveys']=$query->result();
        $surveyid = $data['serveys'][0]->surveyid;
        $data = $this->surveys_model->get($surveyid);
        // echo '<pre>';print_r($data);exit;
        
        // Check if the data store contains
        if ($data){
            // Set the response and exit
            $this->response([
                'status' => TRUE,
                'data' =>$data
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            , REST_Controller::HTTP_NOT_FOUND]); // NOT_FOUND (404) being the HTTP response code
        }
    }
    public function geolocation_post(){
        // API Configuration
        $data['latitude']=$this->input->post('latitude', TRUE);
        $data['longitude']=$this->input->post('longitude', TRUE);
        $data['location_name']=$this->input->post('location_name', TRUE);
        $data['ticketid']=$this->input->post('ticketid', TRUE);
        $ticketid=$data['ticketid'];
        $date['dateadded']=date("Y-m-d");

        $this->db->where('ticketid',$ticketid);
        $result = $this->db->get('tblticket_geolocation')->num_rows();
        
        // insert data
        $this->load->model('tickets_model');
        if($result>0){
            $this->db->where('ticketid', $ticketid);
            $geolocationId=$this->db->update(db_prefix() . 'ticket_geolocation', [
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'location_name' => $data['location_name'],
            ]);
        }else{
            $this->db->insert(db_prefix() . 'ticket_geolocation', [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'location_name' => $data['location_name'],
                'ticketid' => $data['ticketid'],
                'dateadded' => $data['dateadded'],
            ]);
            $geolocationId = $this->db->insert_id();
        }

        if($geolocationId > 0 && !empty($geolocationId)){
            
                // success
                $message = array(
                'status' => TRUE,
                'message' => 'GeoLocation add successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
        }else{
                // error
                $message = array(
                'status' => FALSE,
                'message' => 'GeoLocation add fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function survey_question_post()
    {
        $_POST = json_decode(file_get_contents("php://input"),true);
        $ticketid=$this->input->post('ticketid', TRUE);
        $rel_id=$this->input->post('rel_id', TRUE); //survey_id
        $this->load->model('surveys/surveys_model');
    
        $dataanswer=$this->input->post('answer');
        $this->db->insert(db_prefix().'surveyresultsets', [
            'date'      => date('Y-m-d H:i:s'),
            'surveyid'  => $rel_id,
            'ip'        => $this->input->ip_address(),
            'useragent' => substr($this->input->user_agent(), 0, 149),
        ]);
        $resultsetid = $this->db->insert_id();
        // print_r($dataanswer['checkbox']);exit;
        if ($resultsetid) {
            if(!empty($dataanswer['checkbox'])){
                foreach($dataanswer['checkbox'] as $key=>$value){
                    foreach($value as $v){
                        // echo '<pre>'; echo $key .''.$v;exit;
                        if(!empty($v)){
                        $datacheckboxInsert=$this->db->insert(db_prefix().'form_results', [
                            'boxid'            => $key,
                            'rel_id'           => $rel_id,
                            'rel_type'         => 'survey',
                            'questionid'       => $key,
                            'resultsetid'      => $resultsetid,
                            'answer'           => $v,
                            'ticketid'         => $ticketid,
                        ]);
                        }
                    }
                }
                // print_r($datacheckboxInsert);exit;
            }
            if(!empty($dataanswer['radio'])){
                foreach($dataanswer['radio'] as $key=>$value){
                    // echo '<pre>'; echo $key .''.$value;exit;
                    $datacheckboxInsert=$this->db->insert(db_prefix().'form_results', [
                        'boxid'            => $key,
                        'rel_id'           => $rel_id,
                        'rel_type'         => 'survey',
                        'questionid'       => $key,
                        'resultsetid'      => $resultsetid,
                        'answer'           => $value,
                        'ticketid'         => $ticketid,
                    ]);
                }
                // print_r($datacheckboxInsert);exit;
            }
            if(!empty($dataanswer['input'])){
                foreach($dataanswer['input'] as $key=>$value){
                    // echo '<pre>'; echo $key .''.$value;exit;
                    $datacheckboxInsert=$this->db->insert(db_prefix().'form_results', [
                        'boxid'            => $key,
                        'rel_id'           => $rel_id,
                        'rel_type'         => 'survey',
                        'questionid'       => $key,
                        'resultsetid'      => $resultsetid,
                        'answer'           => $value,
                        'ticketid'         => $ticketid,
                    ]);
                }
                // print_r($datacheckboxInsert);exit;
            }
            if(!empty($dataanswer['textarea'])){
                foreach($dataanswer['textarea'] as $key=>$value){
                    // echo '<pre>'; echo $key .''.$value;exit;
                    $datacheckboxInsert=$this->db->insert(db_prefix().'form_results', [
                        'boxid'            => $key,
                        'rel_id'           => $rel_id,
                        'rel_type'         => 'survey',
                        'questionid'       => $key,
                        'resultsetid'      => $resultsetid,
                        'answer'           => $value,
                        'ticketid'         => $ticketid,
                    ]);
                }
                // print_r($datacheckboxInsert);exit;
            }
            if ($datacheckboxInsert) {
                $message = array(
                    'status' => TRUE,
                    'message' => 'Survey Question answer add successfully.'
                    );
                    // $this->response($message, REST_Controller::HTTP_OK);
                    $this->response($message, REST_Controller::HTTP_OK);
            }else{
                    // error
                    $message = array(
                    'status' => FALSE,
                    'message' => 'Survey Question answer add failed.'
                    );
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
        
    }

    public function data_acceptance_get(){
        
        $ticketid=$this->input->get('ticketid');
        
        $this->db->select();
        $this->db->from(db_prefix().'ticket_acceptances');
        $this->db->where('ticketid',$ticketid);
        $data['acceptance'] = $this->db->get()->result();
        $data = $data['acceptance'][0];
        
        // Check if the data store contains
        if ($data){
            // Set the response and exit
            $this->response([
                'status' => TRUE,
                'data' =>$data
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            , REST_Controller::HTTP_NOT_FOUND]); // NOT_FOUND (404) being the HTTP response code
        }
    }

    public function alldatasync_post1()
    {
        //$_POST = json_decode(file_get_contents("php://input"),true);
        // log_message('error', json_encode($_POST));
        // echo 'asdas';exit;
        //Acceptance post
        $tickets=$this->input->post('tokens');
        foreach($tickets as $ticket){
            // $this->update_acceptance_for_offline($ticket);
        }
        $tickets=$this->input->post('tokens');
        foreach($tickets as $ticket){
            // $this->update_acceptance_for_offline($ticket);
        }
        
        //Evidance post
        $evidances=$this->input->post('images');
        foreach($evidances as $evidance){
            // $this->update_evidance_for_offline($evidance);
        }

        //Question answer post
        $answers=$this->input->post('answers');
        foreach($answers as $answer){
            $this->update_answer_for_offline($answer);
        }
    
        if ($_POST){
            // Set the response and exit
            $this->response([
                'status' => TRUE,
                'data' =>$_POST
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            , REST_Controller::HTTP_NOT_FOUND]); // NOT_FOUND (404) being the HTTP response code
        }
            
    }
    

    function update_acceptance_for_offline($ticket){
        // print_r($_FILES['signature']);exit;
        $data['ticketid']=$ticket['ticketid'];
        $data['note']=$ticket['note'];
        $note=$ticket['note'];
        $ticketid=$data['ticketid'];
        $date['dateadded']=date("Y-m-d");
        $dateadded=$date['dateadded'];
        $data['latitude']=$ticket['latitude'];
        $data['longitude']=$ticket['longitude'];
        $data['location_name']=$ticket['location_name'];
    
        $this->db->where('ticketid',$ticketid);
        $result = $this->db->get('tblticket_acceptances')->num_rows();
        
        // insert data
        $this->load->model('tickets_model');
        if($result>0){
            $this->db->where('ticketid', $ticketid);
            $acceptanceid=$this->db->update(db_prefix() . 'ticket_acceptances', [
                        'note' => $note,
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'location_name' => $data['location_name'],
            ]);
        }else{
            $this->db->insert(db_prefix() . 'ticket_acceptances', $data);
            $acceptanceid = $this->db->insert_id();
        }

        
        // handle_acceptance_image_upload($acceptanceid,$ticketid);
        if (isset($_FILES['file_name']['name']) && $_FILES['file_name']['name'] != '') {
            hooks()->do_action('before_upload_staff_profile_image');
            $path = get_upload_path_by_type('ticket') . 'acceptance';
            // Get the temp file path
            $tmpFilePath = $_FILES['file_name']['tmp_name'];
            // Make sure we have a filepath

            if (!empty($tmpFilePath) && $tmpFilePath != '') {
            
                // Getting file extension
                $extension          = strtolower(pathinfo($_FILES['file_name']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = [
                    'jpg',
                    'jpeg',
                    'png',
                ];
    
                $allowed_extensions = hooks()->apply_filters('staff_profile_image_upload_allowed_extensions', $allowed_extensions);
    
                if (!in_array($extension, $allowed_extensions)) {
                    set_alert('warning', _l('file_php_extension_blocked'));
    
                    return false;
                }
                _maybe_create_upload_path($path);
                $filename    = unique_filename($path, $_FILES['file_name']['name']);
                $newFilePath = $path . '/' . $filename;
                
                // Upload the file into the company uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $CI                       = & get_instance();
                    $config                   = [];
                    $config['image_library']  = 'gd2';
                    $config['source_image']   = $newFilePath;
                    $config['new_image']      = 'thumb_' . $filename;
                    $config['maintain_ratio'] = true;
                    $config['width']          = hooks()->apply_filters('staff_profile_image_thumb_width', 320);
                    $config['height']         = hooks()->apply_filters('staff_profile_image_thumb_height', 320);
                    $CI->image_lib->initialize($config);
                    $CI->image_lib->resize();
                    $CI->image_lib->clear();
                    $config['image_library']  = 'gd2';
                    $config['source_image']   = $newFilePath;
                    $config['new_image']      = 'small_' . $filename;
                    $config['maintain_ratio'] = true;
                    $config['width']          = hooks()->apply_filters('staff_profile_image_small_width', 96);
                    $config['height']         = hooks()->apply_filters('staff_profile_image_small_height', 96);
                    $CI->image_lib->initialize($config);
                    $CI->image_lib->resize();
                    $CI->db->where('id', $acceptanceid);
                    $CI->db->update(db_prefix() . 'ticket_acceptances', [
                        'file_name' => $filename,
                        'dateadded' => $dateadded,

                    ]);
                    // Remove original image
                    // unlink($newFilePath);
    
                    // return true;
                }
            }
        }

       return $acceptanceid;
    }


    function update_evidance_for_offline($evidance){
        $data['ticketid']=$evidance['ticketid'];
        $data['filetype']=$evidance['filetype'];
        $filetype=$data['filetype'];
        $ticketid=$data['ticketid'];
        
        if (isset($_FILES['file_name']['name']) && ($_FILES['file_name']['name'] != '' || is_array($_FILES['file_name']['name']) && count($_FILES['file_name']['name']) > 0)) {
        hooks()->do_action('before_upload_staff_profile_image');

            if (!is_array($_FILES['file_name']['name'])) {
                $_FILES['file_name']['name']     = [$_FILES['file_name']['name']];
                $_FILES['file_name']['type']     = [$_FILES['file_name']['type']];
                $_FILES['file_name']['tmp_name'] = [$_FILES['file_name']['tmp_name']];
                $_FILES['file_name']['error']    = [$_FILES['file_name']['error']];
                $_FILES['file_name']['size']     = [$_FILES['file_name']['size']];
            }
            $path = get_upload_path_by_type('ticket') . 'evidanceimage';

            for ($i = 0; $i < count($_FILES['file_name']['name']); $i++) {
                if (_perfex_upload_error($_FILES['file_name']['error'][$i])) {
                    $errors[$_FILES['file_name']['name'][$i]] = _perfex_upload_error($_FILES['file_name']['error'][$i]);

                    continue;
                }

                // Get the temp file path
                $tmpFilePath = $_FILES['file_name']['tmp_name'][$i];
                // Make sure we have a filepath
                if (!empty($tmpFilePath) && $tmpFilePath != '') {
                    _maybe_create_upload_path($path);
                    $originalFilename = unique_filename($path, $_FILES['file_name']['name'][$i]);
                    $filename = app_generate_hash() . '.' . get_file_extension($originalFilename);

                    // In case client side validation is bypassed
                    // if (!_upload_extension_allowed($filename)) {
                    //     continue;
                    // }

                    $newFilePath = $path . '/' . $filename;
                    // Upload the file into the company uploads dir
                    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                        $CI = & get_instance();
                        $data = [
                                'ticketid' => $ticketid,
                                'file_name'  => $filename,
                                'filetype'   => $filetype,
                                'dateadded'  => date('Y-m-d'),
                            ];
                        
                        $CI->db->insert(db_prefix() . 'ticket_evidanceimage', $data);

                        $insert_id = $CI->db->insert_id();
                        // echo $insert_id;exit;
                        if ($insert_id) {
                            if (is_image($newFilePath)) {
                                create_img_thumb($path, $filename);
                            }
                        } else {
                            unlink($newFilePath);

                            return false;
                        }
                    }
                }
            }
        }

        return $insert_id;

    }

    function update_answer_for_offline($answer){
        $ticketid=$answer['ticketid'];
        $rel_id=$answer['rel_id']; //survey_id
        $this->load->model('surveys/surveys_model');
    
        // $dataanswer=$this->input->post('answer');
        $this->db->insert(db_prefix().'surveyresultsets', [
            'date'      => date('Y-m-d H:i:s'),
            'surveyid'  => $rel_id,
            'ip'        => $this->input->ip_address(),
            'useragent' => substr($this->input->user_agent(), 0, 149),
        ]);
        $resultsetid = $this->db->insert_id();
        // print_r(json_decode($answer['checkbox']));exit;
        if ($resultsetid) {
            $answercheck=json_decode($answer['checkbox']);
            if(!empty($answercheck)){
                foreach($answercheck as $key=>$value){
                    foreach($value as $v){
                        if(!empty($v)){
                        // echo '<pre>'; echo $key .''.$v;exit;
                            $datacheckboxInsert=$this->db->insert(db_prefix().'form_results', [
                                'boxid'            => $key,
                                'rel_id'           => $rel_id,
                                'rel_type'         => 'survey',
                                'questionid'       => $key,
                                'resultsetid'      => $resultsetid,
                                'answer'           => $v,
                                'ticketid'         => $ticketid,
                            ]);
                        }   
                    }
                }
                // print_r($datacheckboxInsert);exit;
            }
            $answerradio=json_decode($answer['radio']);
            if(!empty($answerradio)){
                foreach($answerradio as $key=>$value){
                    // echo '<pre>'; echo $key .''.$value;exit;
                    $datacheckboxInsert=$this->db->insert(db_prefix().'form_results', [
                        'boxid'            => $key,
                        'rel_id'           => $rel_id,
                        'rel_type'         => 'survey',
                        'questionid'       => $key,
                        'resultsetid'      => $resultsetid,
                        'answer'           => $value,
                        'ticketid'         => $ticketid,
                    ]);
                }
                // print_r($datacheckboxInsert);exit;
            }
            if(!empty($answer['input'])){
                foreach($answer['input'] as $key=>$value){
                    // echo '<pre>'; echo $key .''.$value;exit;
                    $datacheckboxInsert=$this->db->insert(db_prefix().'form_results', [
                        'boxid'            => $key,
                        'rel_id'           => $rel_id,
                        'rel_type'         => 'survey',
                        'questionid'       => $key,
                        'resultsetid'      => $resultsetid,
                        'answer'           => $value,
                        'ticketid'         => $ticketid,
                    ]);
                }
                // print_r($datacheckboxInsert);exit;
            }
            if(!empty($answer['textarea'])){
                foreach($answer['textarea'] as $key=>$value){
                    // echo '<pre>'; echo $key .''.$value;exit;
                    $datacheckboxInsert=$this->db->insert(db_prefix().'form_results', [
                        'boxid'            => $key,
                        'rel_id'           => $rel_id,
                        'rel_type'         => 'survey',
                        'questionid'       => $key,
                        'resultsetid'      => $resultsetid,
                        'answer'           => $value,
                        'ticketid'         => $ticketid,
                    ]);
                }
                // print_r($datacheckboxInsert);exit;
            }
            return $datacheckboxInsert;
        }
    }


    public function acceptancedatasync_post()
    {
        $tickets=$this->input->post();
        foreach($tickets as $ticket){
            $acceptanceid=$this->updateacceptancedatasync($ticket);
            
        }
        $tickets=$this->input->post();
        foreach($tickets as $ticket){
            $acceptanceid=$this->updateacceptancedatasync($ticket);
        }
    
        if($acceptanceid > 0 && !empty($acceptanceid)){
            // success
            $message = array(
            'status' => TRUE,
            'message' => 'Acceptance add successful.'
            );
            // $this->response($message, REST_Controller::HTTP_OK);
            $this->response($message, REST_Controller::HTTP_OK);
        }else{
            // error
            $message = array(
            'status' => FALSE,
            'message' => 'Acceptance add fail.'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
            
    }

    function updateacceptancedatasync($ticket){
        print_r($_FILES['signature']);exit;
        $data['ticketid']=$ticket['ticketid'];
        $data['note']=$ticket['note'];
        $note=$ticket['note'];
        $ticketid=$data['ticketid'];
        $date['dateadded']=date("Y-m-d");
        $dateadded=$date['dateadded'];
        $data['latitude']=$ticket['latitude'];
        $data['longitude']=$ticket['longitude'];
        $data['location_name']=$ticket['location_name'];
    
        $this->db->where('ticketid',$ticketid);
        $result = $this->db->get('tblticket_acceptances')->num_rows();
        
        // insert data
        $this->load->model('tickets_model');
        if($result>0){
            $this->db->where('ticketid', $ticketid);
            $acceptanceid=$this->db->update(db_prefix() . 'ticket_acceptances', [
                        'note' => $note,
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'location_name' => $data['location_name'],
            ]);
        }else{
            $this->db->insert(db_prefix() . 'ticket_acceptances', $data);
            $acceptanceid = $this->db->insert_id();
        }

        
        // handle_acceptance_image_upload($acceptanceid,$ticketid);
        if (isset($_FILES['signature']['name']) && $_FILES['signature']['name'] != '') {
            hooks()->do_action('before_upload_staff_profile_image');
            $path = get_upload_path_by_type('ticket') . 'acceptance';
            // Get the temp file path
            $tmpFilePath = $_FILES['signature']['tmp_name'];
            // Make sure we have a filepath

            if (!empty($tmpFilePath) && $tmpFilePath != '') {
            
                // Getting file extension
                $extension          = strtolower(pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = [
                    'jpg',
                    'jpeg',
                    'png',
                ];
    
                $allowed_extensions = hooks()->apply_filters('staff_profile_image_upload_allowed_extensions', $allowed_extensions);
    
                if (!in_array($extension, $allowed_extensions)) {
                    set_alert('warning', _l('file_php_extension_blocked'));
    
                    return false;
                }
                _maybe_create_upload_path($path);
                $filename    = unique_filename($path, $_FILES['signature']['name']);
                $newFilePath = $path . '/' . $filename;
                
                // Upload the file into the company uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $CI                       = & get_instance();
                    $config                   = [];
                    $config['image_library']  = 'gd2';
                    $config['source_image']   = $newFilePath;
                    $config['new_image']      = 'thumb_' . $filename;
                    $config['maintain_ratio'] = true;
                    $config['width']          = hooks()->apply_filters('staff_profile_image_thumb_width', 320);
                    $config['height']         = hooks()->apply_filters('staff_profile_image_thumb_height', 320);
                    $CI->image_lib->initialize($config);
                    $CI->image_lib->resize();
                    $CI->image_lib->clear();
                    $config['image_library']  = 'gd2';
                    $config['source_image']   = $newFilePath;
                    $config['new_image']      = 'small_' . $filename;
                    $config['maintain_ratio'] = true;
                    $config['width']          = hooks()->apply_filters('staff_profile_image_small_width', 96);
                    $config['height']         = hooks()->apply_filters('staff_profile_image_small_height', 96);
                    $CI->image_lib->initialize($config);
                    $CI->image_lib->resize();
                    $CI->db->where('id', $acceptanceid);
                    $CI->db->update(db_prefix() . 'ticket_acceptances', [
                        'file_name' => $filename,
                        'dateadded' => $dateadded,

                    ]);
                    // Remove original image
                    // unlink($newFilePath);
    
                    // return true;
                }
            }
        }

       return $acceptanceid;
    }


    public function evidancedatasync_post()
    {
        $data['ticketid']=$this->input->post('ticketid', TRUE);
        $data['filetype']=$this->input->post('filetype', TRUE);
        $filetype=$data['filetype'];
        $ticketid=$data['ticketid'];
        $date['dateadded']=date("Y-m-d");
        $dateadded=$date['dateadded'];
       
        // handle_acceptance_image_upload($acceptanceid,$ticketid);
        if (isset($_FILES['file_name']['name']) && $_FILES['file_name']['name'] != '') {
            hooks()->do_action('before_upload_staff_profile_image');
            $path = get_upload_path_by_type('ticket') . 'evidanceimage';
            // Get the temp file path
            $tmpFilePath = $_FILES['file_name']['tmp_name'];
            // Make sure we have a filepath

            if (!empty($tmpFilePath) && $tmpFilePath != '') {
            
                // Getting file extension
                $extension          = strtolower(pathinfo($_FILES['file_name']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'mp4',
                ];
    
                $allowed_extensions = hooks()->apply_filters('staff_profile_image_upload_allowed_extensions', $allowed_extensions);
    
                if (!in_array($extension, $allowed_extensions)) {
                    set_alert('warning', _l('file_php_extension_blocked'));
    
                    return false;
                }
                _maybe_create_upload_path($path);
                $filename    = unique_filename($path, $_FILES['file_name']['name']);
                $newFilePath = $path . '/' . $filename;
                
                // Upload the file into the company uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $CI                       = & get_instance();
                    $config                   = [];
                    $config['image_library']  = 'gd2';
                    $config['source_image']   = $newFilePath;
                    $config['new_image']      = 'thumb_' . $filename;
                    $config['maintain_ratio'] = true;
                    $config['width']          = hooks()->apply_filters('staff_profile_image_thumb_width', 320);
                    $config['height']         = hooks()->apply_filters('staff_profile_image_thumb_height', 320);
                    $CI->image_lib->initialize($config);
                    $CI->image_lib->resize();
                    $CI->image_lib->clear();
                    $config['image_library']  = 'gd2';
                    $config['source_image']   = $newFilePath;
                    $config['new_image']      = 'small_' . $filename;
                    $config['maintain_ratio'] = true;
                    $config['width']          = hooks()->apply_filters('staff_profile_image_small_width', 96);
                    $config['height']         = hooks()->apply_filters('staff_profile_image_small_height', 96);
                    $CI->image_lib->initialize($config);
                    $CI->image_lib->resize();
                    
                    $this->db->insert(db_prefix() . 'ticket_evidanceimage', [
                        'file_name' => $filename,
                        'dateadded' => $dateadded,
                        'filetype' => $filetype,
                        'ticketid' => $ticketid,
                    ]);
                    $insert_id = $this->db->insert_id();
                    // Remove original image
                    // unlink($newFilePath);
    
                    // return true;
                }
            }
        }
   
        if($insert_id > 0 && !empty($insert_id)){
            // success
            $message = array(
            'status' => TRUE,
            'message' => 'Evidance add successful.'
            );
            // $this->response($message, REST_Controller::HTTP_OK);
            $this->response($message, REST_Controller::HTTP_OK);
        }else{
            // error
            $message = array(
            'status' => FALSE,
            'message' => 'Evidance add fail.'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        
    }
}
