<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require __DIR__ . '/API_Controller.php';

class Login extends API_Controller {
    public function __construct() {
        parent::__construct();
    }

    public function login_api() {
        header("Access-Control-Allow-Origin: *");
        // API Configuration
        $this->_apiConfig(['methods' => ['POST'], ]);
        // you user authentication code will go here, you can compare the user with the database or whatever
        $payload = ['id' => "14", 'other' => "Some other data"];
        // Load Authorization Library or Load in autoload config file
        $this->load->library('authorization_token');
        // generate a token
        $token = $this->authorization_token->generateToken($payload);
        // return data
        $this->api_return(['status' => true, "result" => ['token' => $token, ], ], 200);
    }

    public function acceptance_post()
    {
        // API Configuration
        $this->_apiConfig(['methods' => ['POST'], ]);
        $data['ticketid']=$this->input->post('ticketid', TRUE);
        $data['note']=$this->input->post('note', TRUE);
        $ticketid=$data['ticketid'];
      
        // insert data
        $this->load->model('tickets_model');
        $this->db->insert(db_prefix() . 'ticket_acceptances', $data);
        $acceptanceid = $this->db->insert_id();

        // handle_acceptance_image_upload($acceptanceid,$ticketid);
        if (isset($_FILES['file_name']['name']) && $_FILES['file_name']['name'] != '') {
            hooks()->do_action('before_upload_staff_profile_image');
            $path = get_upload_path_by_type('ticket') . $ticketid .'/'. $acceptanceid;
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
                $this->api_return(['status' => true, "result" => ['data' => $message, ], ], 200);
        }else{
                // error
                $message = array(
                'status' => FALSE,
                'message' => 'Acceptance add fail.'
                );
                $this->api_return(['status' => false ], 200);
        }
        
    }

    public function index() {
        // API Configuration
        $_POST = json_decode(file_get_contents("php://input"),true);
        $this->_apiConfig(['methods' => ['POST'], ]);
        $this->load->model('Authentication_model');
        $this->load->library('form_validation');
        // you user authentication code will go here, you can compare the user with the database or whatever
        $this->form_validation->set_rules('password', _l('admin_auth_login_password'), 'required');
        $this->form_validation->set_rules('email', _l('admin_auth_login_email'), 'trim|required|valid_email');
        $data = array();
        if ($this->input->post()) {
            if ($this->form_validation->run() !== false) {
                $email    = $this->input->post('email');
                $password = $this->input->post('password', false);
                $remember = $this->input->post('remember');

                $data = $this->Authentication_model->login($email, $password, $remember, true);
                if($data==true)
                    $this->api_return(['status' => true, "result" => ['data' => get_staff(get_staff_user_id()), ], ], 200);
                else
                    $this->api_return(['status' => false ], 200);
            }
        }else
        // return data
        $this->api_return(['status' => false,  ], 200);
    }

    /**
     * view method
     *
     * @link [api/user/view]
     * @method POST
     * @return Response|void
     */
    public function view() {
        header("Access-Control-Allow-Origin: *");
        // API Configuration [Return Array: User Token Data]
        $user_data = $this->_apiConfig(['methods' => ['POST'], 'requireAuthorization' => true, ]);
        // return data
        $this->api_return(['status' => true, "result" => ['user_data' => $user_data['token_data']], ], 200);
    }
    
    public function api_key() {
        $this->_APIConfig(['methods' => ['POST'], 'key' => ['header', 'Set API Key'], ]);
    }
}
