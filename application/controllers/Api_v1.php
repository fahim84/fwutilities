<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'libraries/REST_Controller.php');

class Api_v1 extends REST_Controller
{

    function __construct()
    {
        parent::__construct();

        $this->logRequest();
    }

    public function logRequest()
    {

        $content = "====================================== \n";

        $content .= print_r($_REQUEST, TRUE);

        $content .= "====================================== \n";

        $fp = fopen('./uploads/' . "api_log.txt", "a+");

        fwrite($fp, $content);

        fclose($fp);
    }

    private function check_token()
    {
        $headers = getallheaders();

        if (!isset($headers['Authtoken'])) {
            $this->response(['code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => 'failed', 'msg' => "missing authtoken in HTTP headers"], REST_Controller::HTTP_UNAUTHORIZED);
        } elseif ($headers['Authtoken'] == '') {
            $this->response(['code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => 'failed', 'msg' => "empty authtoken in HTTP headers"], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $query = $this->user_model->verify_token($headers['Authtoken']);
        $row = $query->num_rows() ? $query->row() : false;
        if ($row) {
            $row->image_url = $row->image ? base_url().'uploads/users/'.$row->image : '';
            return $row;
        } else {
            $this->response(['code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => 'failed', 'msg' => "invalid token"], REST_Controller::HTTP_UNAUTHORIZED);
        }
    }

    public function update_user_post()
    {
        $user = self::check_token();

        $id = $user->user_id;

        if ($this->input->get_post('email') !== NULL) {
            $email = $this->input->get_post('email');

            if ($this->user_model->email_already_exists($email, $id)) {
                $this->response(['code' => REST_Controller::HTTP_CONFLICT, 'status' => 'failed', 'msg' => "Email address already taken"], REST_Controller::HTTP_CONFLICT);
            }
        }

        # all possible columns define here
        $possible_columns = ['initial','emp_code','firstname','lastname','fullname','email','pin','password','designation','gender','dob','about','manager_id','city','region'];

        $sql_data = [];
        # loop through all columns
        foreach ($possible_columns as $column) {
            # if column is present in $_REQUEST, include it in sql query
            if ($this->input->get_post($column) !== NULL) {
                $sql_data[$column] = $this->input->get_post($column);
            }
        }

        # if password is given, change it into md5 encryption
        if (isset($sql_data['password'])) {
            $sql_data['password'] = md5($sql_data['password']);
        }

        $delete_old_file = $this->input->get_post('delete_old_file');
        $upload_path = './uploads/users/';
        $uploaded_file_array = (isset($_FILES['image']) and $_FILES['image']['size'] > 0 and $_FILES['image']['error'] == 0) ? $_FILES['image'] : '';
        # Show uploading error only when the file uploading attempt exist.
        if (is_array($uploaded_file_array)) {
            $delete_old_file = true;
        }

        if ($delete_old_file) {
            $oldfile = $user->image;

            # Delete old file if there was any
            if (delete_file($upload_path . $oldfile)) {
                $this->user_model->update($id, ['image' => '']);
                //my_var_dump($this->db->last_query());
            }
        }

        # File uploading configuration
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'jpg|png|jpeg';
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);

        # Try to upload file now
        if ($this->upload->do_upload('image')) {
            # Get uploading detail here
            $upload_detail = $this->upload->data();

            $sql_data['image'] = $upload_detail['file_name'];
            $image = $sql_data['image'];

            # Get width and height and resize image keeping aspect ratio same
            $image_path = $upload_path . $image;
            $width = get_width($image_path);
            $width > 800 ? resize_image2($image_path, 800, '', 'W') : '';
            $height = get_height($image_path);
            $height > 800 ? resize_image2($image_path, '', 800, 'H') : '';
        }

        $result = $this->user_model->update($id, $sql_data);
        //my_var_dump($this->db->last_query());
        if ($result === FALSE) {
            $this->response(['code' => REST_Controller::HTTP_EXPECTATION_FAILED, 'status' => 'failed', 'msg' => "Some database error"], REST_Controller::HTTP_EXPECTATION_FAILED);
        } else {
            $user = $this->user_model->get_user_by_id($id);

            # All tags values must be defined here
            $user_user_id = $user->user_id;
            $user_firstname = $user->firstname;
            $user_lastname = $user->lastname;
            $user_fullname = $user->fullname;
            $user_initial = $user->initial;
            $user_empcode = $user->empcode;
            $user_email = $user->email;

            # Get corresponding template for this notification
            $template = $this->template_model->get_template_by_id(1);

            if($template)
            {
                eval("\$template->title = \"$template->title\";"); // replace tags with their values
                eval("\$template->message = \"$template->message\";"); // replace tags with their values

                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $id;
                $notification['to_user_id'] = $id;
                $notification['notification_type'] = 'Update user profile';
                $notification['title'] = $template->title;
                $notification['message'] = $template->message;
                $notification['stakeholder_id'] = 0;
                $notification['interaction_id'] = 0;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($user);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($id,$notification,'IOS');
            }

            $updated_columns = array_keys($sql_data);
            $msg = count($updated_columns) . ' field(s) updated [' . implode(',', $updated_columns) . ']';
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'msg' => $msg, 'data' => $user], REST_Controller::HTTP_OK);
        }
    }

    public function login_user_post()
    {
        if ($this->input->get_post('email') === NULL and $this->input->get_post('initial') === NULL) {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "email/initial parameter is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }
        if ($this->input->get_post('password') === NULL and $this->input->get_post('pin') === NULL) {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "password/pin parameter is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }

        $email = $this->input->get_post('email') ? $this->input->get_post('email') : $this->input->get_post('initial');
        $initial = $this->input->get_post('initial');
        $password = $this->input->get_post('password') ? $this->input->get_post('password') : $this->input->get_post('pin');
        $pin = $this->input->get_post('pin');

        $user = $this->user_model->check_login(['email' => $email, 'password' => $password]);

        if ($user) {
            if ($user->deleted) {
                $this->response(['code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => 'failed', 'msg' => "Your account is deleted by administration..."], REST_Controller::HTTP_UNAUTHORIZED);
            }
            if ($user->is_activated)// login success
            {
                $sql_data['last_login'] = date('Y-m-d H:i:s');
                $user->last_login = $sql_data['last_login'];

                if($user->authtoken == '')
                {
                    $sql_data['authtoken'] = md5($user->user_id);
                    $user->authtoken = $sql_data['authtoken'];
                }

                $this->user_model->update($user->user_id, $sql_data); // update token

                # insert device_id
                $device_id = $this->input->get_post('device_id');
                $version_number = $this->input->get_post('version_number');
                if($device_id)
                {
                    $this->user_model->insert_device($user->user_id,$device_id,'IOS',$version_number);
                }

                # All tags values must be defined here
                $user_user_id = $user->user_id;
                $user_firstname = $user->firstname;
                $user_lastname = $user->lastname;
                $user_fullname = $user->fullname;
                $user_initial = $user->initial;
                $user_empcode = $user->empcode;
                $user_email = $user->email;

                # Get corresponding template for this notification
                $template = $this->template_model->get_template_by_id(2);

                if($template)
                {
                    eval("\$template->title = \"$template->title\";"); // replace tags with their values
                    eval("\$template->message = \"$template->message\";"); // replace tags with their values

                    # Send push notification
                    $notification = [];
                    $notification['logged_in_user_id'] = $user->user_id;
                    $notification['to_user_id'] = $user->user_id;
                    $notification['notification_type'] = 'Login user';
                    $notification['title'] = $template->title;
                    $notification['message'] = $template->message;
                    $notification['stakeholder_id'] = 0;
                    $notification['interaction_id'] = 0;
                    $notification['dept_id'] = 0;
                    $notification['group_id'] = 0;
                    $notification['organization_id'] = 0;
                    $notification['data'] = json_encode($user);

                    $notification_id = $this->notification_model->insert($notification);

                    $notification = $this->notification_model->get_notification_by_id($notification_id);

                    $this->user_model->send_push_notification($user->user_id, $notification, 'IOS');
                }
                $user->is_new = 0;

                $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'msg' => 'Login successfully...', 'data' => $user, 'version_detail' => $this->general_model->get_latest_version(),], REST_Controller::HTTP_OK);
            } else {
                $this->response(['code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => 'failed', 'msg' => "Your account is not activated."], REST_Controller::HTTP_UNAUTHORIZED);
            }
        } else {
            $this->response(['code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => 'failed', 'msg' => "Incorrect credentials, please try again."], REST_Controller::HTTP_UNAUTHORIZED);
        }
    }

    public function login_facebook_post() {
        $facebook_token = $this->input->get_post('facebook_token');
        $device = $this->input->get_post('device');
        $device_id = $this->input->get_post('device_id')==null?'':$this->input->get_post('device_id');

        if (!isset($facebook_token))
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => 'facebook_token is missing'], REST_Controller::HTTP_BAD_REQUEST);
        if (!isset($device))
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => 'device is missing'], REST_Controller::HTTP_BAD_REQUEST);

        $graph_user = $this->user_model->get_facebook_user_from_token($facebook_token);
        if (isset($graph_user->error)) {
            $this->response(['code' => $graph_user->error_code, 'status' => 'failed', 'msg' => $graph_user->error], $graph_user->error_code);
        }
        else {
            //
            if ($this->user_model->social_id_already_exists($graph_user->id)) { //Update
                $user = $this->user_model->check_login_by_social_id($graph_user->id);
                if ($user) {
                    if ($user->deleted)
                        $this->response(['code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => 'failed', 'msg' => "Your account is deleted by administration..."], REST_Controller::HTTP_UNAUTHORIZED);
                    if ($user->is_activated)// login success
                    {
                        $sql_data['last_login'] = date('Y-m-d H:i:s');
                        if (isset($device_id)) {
                            $sql_data['device_id'] = $device_id;
                            $user->device_id = $device_id;
                        }

                        $user->last_login = $sql_data['last_login'];
                        $user->image_url = isset($user->image_url)?$user->image_url:$graph_user->image;


                        if ($this->input->get_post('device')) {
                            $sql_data['device'] = $this->input->get_post('device');
                            $user->device = $sql_data['device'];
                        }

                        if ($this->input->get_post('device_id')) {
                            $sql_data['device_id'] = $this->input->get_post('device_id');
                            $user->device_id = $sql_data['device_id'];
                        }
                        $sql_data['email'] = $graph_user->email;
                        $this->user_model->update($user->user_id, $sql_data); // update token
                        $user->is_new = 0;
                        unset($user->password);
                        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'msg' => 'Login successfully...', 'data' => $user], REST_Controller::HTTP_OK);
                    } else {
                        $this->response(['code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => 'failed', 'msg' => "Your account is not activated."], REST_Controller::HTTP_UNAUTHORIZED);
                    }
                } else {
                    $this->response(['code' => REST_Controller::HTTP_UNAUTHORIZED, 'status' => 'failed', 'msg' => "Incorrect credentials, please try again."], REST_Controller::HTTP_UNAUTHORIZED);
                }
            }
            else {
                //INSERT DATA
                $sql_data['facebook_id'] = $graph_user->id;
                $sql_data['device_id'] = $device_id;
                $sql_data['device'] = $device;
                $sql_data['fullname'] = $graph_user->name;
                $sql_data['email'] = $graph_user->email;
                $sql_data['password'] = '';
                $sql_data['image_facebook'] = $graph_user->image;
                //$sql_data['dob'] = $graph_user->dob;
                //$sql_data['gender'] = $graph_user->gender;
                $sql_data['is_activated'] = 1;
                $id = $this->user_model->insert($sql_data);
                //UPDATE DATA FOR AUTHTOKEN
                if ($id === FALSE) {
                    $this->response(['code' => REST_Controller::HTTP_EXPECTATION_FAILED, 'status' => 'failed', 'msg' => "Some database error"], REST_Controller::HTTP_EXPECTATION_FAILED);
                } else {
                    $authtoken = md5($id); // generate token
                    $this->user_model->update($id, ['last_login' => date('Y-m-d H:i:s'), 'authtoken' => $authtoken]); // update token

                    $user = $this->user_model->get_user_by_id($id);
                    $user->image_url = $graph_user->image;
                    $user->is_new = 1;
                    unset($user->password);

                    $msg = 'Login success';
                    $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'msg' => $msg, 'id' => $id, 'data' => $user], REST_Controller::HTTP_OK);
                }
            }
        }
    }

    public function logout_post()
    {
        $user = self::check_token();

        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'msg' => 'Logout successfully...', 'data' => $user], REST_Controller::HTTP_OK);
    }

    public function forgot_password_post()
    {
        if ($this->input->get_post('email') === NULL) {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "email parameter is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }
        if ($this->input->get_post('email') == '') {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "email parameter is empty"], REST_Controller::HTTP_BAD_REQUEST);
        }

        $email = $this->input->get_post('email');

        if ($user = $this->user_model->email_already_exists($email)) {
            $id = $user->user_id;
            $md5_id = md5($id);
            $plain_password = substr($md5_id, 0, 8);

            // save new password in database
            $this->user_model->update($id, ['password' => md5($plain_password)]);

        } else {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "No email address was found, please try again"], REST_Controller::HTTP_BAD_REQUEST);
        }


        $message = 'Hi,<p>You have requested that ' . SYSTEM_NAME . ' reset your account password.<br>Your new password is ' . $plain_password . '​<br><p>After logging in with your new password, please change it immediately by going into Settings and updating your account information.</p><br>Thanks for using the app!<p></p></p>';
        $subject = SYSTEM_NAME . ' password recovery';
        $this->load->library('email');


        # Send email to user
        $this->email->clear(TRUE);
        $this->email->set_mailtype("html");
        $this->email->from(SYSTEM_EMAIL, SYSTEM_NAME);
        $this->email->to($email);
        $this->email->subject($subject);
        $this->email->message($message);
        $email_sent = $this->email->send();

        //$email_sent = mail($email,$subject,$message);

        # All tags values must be defined here
        $user_user_id = $user->user_id;
        $user_firstname = $user->firstname;
        $user_lastname = $user->lastname;
        $user_fullname = $user->fullname;
        $user_initial = $user->initial;
        $user_empcode = $user->empcode;
        $user_email = $user->email;
        $new_password = $plain_password;

        # Get corresponding template for this notification
        $template = $this->template_model->get_template_by_id(3);

        if($template)
        {
            eval("\$template->title = \"$template->title\";"); // replace tags with their values
            eval("\$template->message = \"$template->message\";"); // replace tags with their values

            # Send push notification
            $notification = [];
            $notification['logged_in_user_id'] = $user->user_id;
            $notification['to_user_id'] = $user->user_id;
            $notification['notification_type'] = 'Password changed';
            $notification['title'] = $template->title;
            $notification['message'] = $template->message;
            $notification['stakeholder_id'] = 0;
            $notification['interaction_id'] = 0;
            $notification['dept_id'] = 0;
            $notification['group_id'] = 0;
            $notification['organization_id'] = 0;
            $notification['data'] = json_encode($user);

            $notification_id = $this->notification_model->insert($notification);

            $notification = $this->notification_model->get_notification_by_id($notification_id);

            $this->user_model->send_push_notification($user->user_id, $notification, 'IOS');
        }
        $msg = 'An email has been sent including the new password.';
        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'msg' => $msg, 'email_sent' => $email_sent], REST_Controller::HTTP_OK);

    }

    public function add_stakeholder_post()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        # all possible columns define here
        $possible_columns = ['firstname', 'lastname', 'stakeholder_code', 'ioselas_id', 'email',
            'address','city','region','province','country','latitude','longitude',
            'gender','dob','mobile','telephone', 'extension', 'about','is_activated','dri_user_id',
            'requested', 'attached_stakeholder_id','request_response','request_from','status_by_hod','status_by_admin','admin_id'];

        # loop through all columns
        foreach ($possible_columns as $column) {
            # if column is present in $_REQUEST, include it in sql query
            if ($this->input->get_post($column) !== NULL) {
                $sql_data[$column] = $this->input->get_post($column);
            }
        }

        if (!isset($sql_data)) {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "sql data is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }

        if(isset($sql_data['firstname']) or isset($sql_data['lastname']))
        {
            $sql_data['fullname'] = $sql_data['firstname'].' '.$sql_data['lastname'];
        }

        $sql_data['request_from'] = $user_id;

        # File uploading configuration
        $upload_path = './uploads/stakeholders/';
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'jpg|png|jpeg';
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);

        # Try to upload file now
        if ($this->upload->do_upload('image')) {
            # Get uploading detail here
            $upload_detail = $this->upload->data();

            $sql_data['image'] = $upload_detail['file_name'];
            $image = $sql_data['image'];

            # Get width and height and resize image keeping aspect ratio same
            $image_path = $upload_path . $image;
            $width = get_width($image_path);
            $width > 800 ? resize_image2($image_path, 800, '', 'W') : '';
            $height = get_height($image_path);
            $height > 800 ? resize_image2($image_path, '', 800, 'H') : '';
        }
        if (! isset($sql_data['is_activated']) )
        $sql_data['is_activated'] = 1;

        $id = $this->stakeholder_model->insert($sql_data);

        $entity = $this->stakeholder_model->get_stakeholder_by_id($id);
        $this->_add_stakeholder_profile($id,$user_id);
        $profile = $this->stakeholder_model->get_stakeholder_profile_by_id($id);

        # update stakeholder_code
        $stakeholder_code = $profile->dept_short_code.$profile->group_short_code.$id;
        $this->stakeholder_model->update($id,['stakeholder_code' => $stakeholder_code]);
        $entity->stakeholder_code = $stakeholder_code;

        if($entity->dri_user_id)
        {
            $to_user = $this->user_model->get_user_by_id($entity->dri_user_id);
            # All tags values must be defined here
            $logged_user_fullname = $user->fullname;
            $to_user_fullname = $to_user->fullname;
            $stakeholder_name = $entity->fullname;
            $to_user_initial = $to_user->initial;
            $logged_user_initial = $user->initial;
            $group = $entity->group;

            # Get corresponding template for this notification
            $template = $this->template_model->get_template_by_id(4);

            if($template)
            {
                eval("\$template->title = \"$template->title\";"); // replace tags with their values
                eval("\$template->message = \"$template->message\";"); // replace tags with their values

                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $entity->dri_user_id;
                $notification['notification_type'] = 'New stakeholder added';
                $notification['title'] = $template->title;
                $notification['message'] = $template->message;
                $notification['stakeholder_id'] = $entity->stakeholder_id;
                $notification['interaction_id'] = 0;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($entity->dri_user_id, $notification, 'IOS');
            }
        }

        if($profile->dept_head_id)
        {
            $to_user = $this->user_model->get_user_by_id($entity->dri_user_id);
            # All tags values must be defined here
            $logged_user_fullname = $user->fullname;
            $to_user_fullname = $to_user->fullname;
            $stakeholder_name = $entity->fullname;
            $department = $profile->department;
            $to_user_initial = $to_user->initial;
            $logged_user_initial = $user->initial;
            $group = $entity->group;

            # Get corresponding template for this notification
            $template = $this->template_model->get_template_by_id(5);

            if($template)
            {
                eval("\$template->title = \"$template->title\";"); // replace tags with their values
                eval("\$template->message = \"$template->message\";"); // replace tags with their values

                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $profile->dept_head_id;
                $notification['notification_type'] = 'New stakeholder added';
                $notification['title'] = $template->title;
                $notification['message'] = $template->message;
                $notification['stakeholder_id'] = $entity->stakeholder_id;
                $notification['interaction_id'] = 0;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($profile->dept_head_id, $notification, 'IOS');
            }

            # Send notification to HOD for approval request
            if($entity->requested != '')
            {
                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $profile->dept_head_id;
                $notification['notification_type'] = 'Stakeholder approval for HOD';
                $notification['title'] = 'Request for new stakeholder approval';
                $notification['message'] = "$user->fullname has requested to add new stakeholder $entity->fullname";
                $notification['stakeholder_id'] = $entity->stakeholder_id;
                $notification['interaction_id'] = 0;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($profile->dept_head_id, $notification, 'IOS');
            }
            # Send notification to Admin for approval request
            if($entity->requested != '' and $entity->admin_id > 0)
            {
                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $entity->admin_id;
                $notification['notification_type'] = 'Stakeholder approval for Admin';
                $notification['title'] = 'Request for new stakeholder approval';
                $notification['message'] = "$user->fullname has requested to add new stakeholder $entity->fullname";
                $notification['stakeholder_id'] = $entity->stakeholder_id;
                $notification['interaction_id'] = 0;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($entity->admin_id, $notification, 'IOS');
            }
        }

        # All tags values must be defined here
        $logged_user_fullname = $user->fullname;
        $stakeholder_name = $entity->fullname;
        $logged_user_initial = $user->initial;
        $group = $entity->group;

        # Get corresponding template for this notification
        $template = $this->template_model->get_template_by_id(6);

        if($template)
        {
            eval("\$template->title = \"$template->title\";"); // replace tags with their values
            eval("\$template->message = \"$template->message\";"); // replace tags with their values

            # Send push notification
            $notification = [];
            $notification['logged_in_user_id'] = $user->user_id;
            $notification['to_user_id'] = $user->user_id;
            $notification['notification_type'] = 'New stakeholder added';
            $notification['title'] = $template->title;
            $notification['message'] = $template->message;
            $notification['stakeholder_id'] = $entity->stakeholder_id;
            $notification['interaction_id'] = 0;
            $notification['dept_id'] = 0;
            $notification['group_id'] = 0;
            $notification['organization_id'] = 0;
            $notification['data'] = json_encode($entity);

            $notification_id = $this->notification_model->insert($notification);

            $notification = $this->notification_model->get_notification_by_id($notification_id);

            $this->user_model->send_push_notification($user->user_id, $notification, 'IOS');
        }
        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'data' => $entity,'profile'=>$profile], REST_Controller::HTTP_OK);
    }

    private function _add_stakeholder_profile($id,$user_id,$update_only = false)
    {
        $sql_data['user_id'] = $user_id;
        $sql_data['stakeholder_id'] = $id;

        # all possible columns define here
        $possible_columns = ['dept_id', 'organization_id', 'group_id', 'attitude_nn', 'comm_focus',
            'rx_influence','influence','priority','planned_engagement','ongoing_engagement','frequency',
            'geo_scope','kpi_objective','job_title'];

        # loop through all columns
        foreach ($possible_columns as $column) {
            # if column is present in $_REQUEST, include it in sql query
            if ($this->input->get_post($column) !== NULL) {
                $sql_data[$column] = $this->input->get_post($column);
            }
        }

        if($update_only)
        {
            $id = $this->stakeholder_model->update_profile($id,$sql_data);
        }
        else
        {
            $id = $this->stakeholder_model->insert_profile($sql_data);
        }

        return $id;
    }

    public function update_stakeholder_post()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        if ($this->input->get_post('stakeholder_id') === NULL) {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "stakeholder_id parameter is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }
        if ($this->input->get_post('stakeholder_id') == '') {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "stakeholder_id parameter is empty"], REST_Controller::HTTP_BAD_REQUEST);
        }

        $stakeholder_id = $this->input->get_post('stakeholder_id');

        $entity = $this->stakeholder_model->get_stakeholder_by_id($stakeholder_id);

        if($entity ===  false)
        {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "The entity you want to update does not exist."], REST_Controller::HTTP_BAD_REQUEST);
        }

        # all possible columns define here
        $possible_columns = ['firstname', 'lastname', 'stakeholder_code', 'ioselas_id', 'email',
            'address','city','region','province','country','latitude','longitude',
            'gender','dob','mobile','telephone', 'extension', 'about','is_activated','dri_user_id',
            'requested', 'attached_stakeholder_id','request_response','request_from','status_by_hod','status_by_admin','admin_id'];

        # loop through all columns
        foreach ($possible_columns as $column) {
            # if column is present in $_REQUEST, include it in sql query
            if ($this->input->get_post($column) !== NULL) {
                $sql_data[$column] = $this->input->get_post($column);
            }
        }

        if (!isset($sql_data)) {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "sql data is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }

        if(isset($sql_data['firstname']) or isset($sql_data['lastname']))
        {
            $sql_data['fullname'] = $sql_data['firstname'].' '.$sql_data['lastname'];
        }

        $delete_old_file = $this->input->get_post('delete_old_file');
        $upload_path = './uploads/stakeholders/';
        $uploaded_file_array = (isset($_FILES['image']) and $_FILES['image']['size'] > 0 and $_FILES['image']['error'] == 0) ? $_FILES['image'] : '';
        # Show uploading error only when the file uploading attempt exist.
        if (is_array($uploaded_file_array)) {
            $delete_old_file = true;
        }

        if ($delete_old_file)
        {
            $oldfile = $entity->image;

            # Delete old file if there was any
            if (delete_file($upload_path . $oldfile)) {
                $this->stakeholder_model->update($stakeholder_id, ['image' => '']);
                //my_var_dump($this->db->last_query());
            }
        }

        # File uploading configuration
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'jpg|png|jpeg';
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);

        # Try to upload file now
        if ($this->upload->do_upload('image')) {
            # Get uploading detail here
            $upload_detail = $this->upload->data();

            $sql_data['image'] = $upload_detail['file_name'];
            $image = $sql_data['image'];

            # Get width and height and resize image keeping aspect ratio same
            $image_path = $upload_path . $image;
            $width = get_width($image_path);
            $width > 800 ? resize_image2($image_path, 800, '', 'W') : '';
            $height = get_height($image_path);
            $height > 800 ? resize_image2($image_path, '', 800, 'H') : '';
        }

        $result = $this->stakeholder_model->update($stakeholder_id, $sql_data);
        //my_var_dump($this->db->last_query());
        if ($result === FALSE) {
            $this->response(['code' => REST_Controller::HTTP_EXPECTATION_FAILED, 'status' => 'failed', 'msg' => "Some database error"], REST_Controller::HTTP_EXPECTATION_FAILED);
        } else {

            $entity = $this->stakeholder_model->get_stakeholder_by_id($stakeholder_id);
            $this->_add_stakeholder_profile($stakeholder_id,$user_id,true);
            $profile = $this->stakeholder_model->get_stakeholder_profile_by_id($stakeholder_id);

            if($entity->dri_user_id)
            {
                $to_user = $this->user_model->get_user_by_id($entity->dri_user_id);
                # All tags values must be defined here
                $logged_user_fullname = $user->fullname;
                $to_user_fullname = $to_user->fullname;
                $stakeholder_name = $entity->fullname;
                $to_user_initial = $to_user->initial;
                $logged_user_initial = $user->initial;
                $group = $entity->group;

                # Get corresponding template for this notification
                $template = $this->template_model->get_template_by_id(7);

                if($template)
                {
                    eval("\$template->title = \"$template->title\";"); // replace tags with their values
                    eval("\$template->message = \"$template->message\";"); // replace tags with their values

                    # Send push notification
                    $notification = [];
                    $notification['logged_in_user_id'] = $user->user_id;
                    $notification['to_user_id'] = $entity->dri_user_id;
                    $notification['notification_type'] = 'Stakeholder updated';
                    $notification['title'] = $template->title;
                    $notification['message'] = $template->message;
                    $notification['stakeholder_id'] = $entity->stakeholder_id;
                    $notification['interaction_id'] = 0;
                    $notification['dept_id'] = 0;
                    $notification['group_id'] = 0;
                    $notification['organization_id'] = 0;
                    $notification['data'] = json_encode($entity);

                    $notification_id = $this->notification_model->insert($notification);

                    $notification = $this->notification_model->get_notification_by_id($notification_id);

                    $this->user_model->send_push_notification($entity->dri_user_id, $notification, 'IOS');
                }
            }

            if($profile->dept_head_id)
            {
                $to_user = $this->user_model->get_user_by_id($entity->dri_user_id);
                # All tags values must be defined here
                $logged_user_fullname = $user->fullname;
                $to_user_fullname = $to_user->fullname;
                $stakeholder_name = $entity->fullname;
                $department = $profile->department;
                $to_user_initial = $to_user->initial;
                $logged_user_initial = $user->initial;
                $group = $entity->group;

                # Get corresponding template for this notification
                $template = $this->template_model->get_template_by_id(8);

                if($template)
                {
                    eval("\$template->title = \"$template->title\";"); // replace tags with their values
                    eval("\$template->message = \"$template->message\";"); // replace tags with their values

                    # Send push notification
                    $notification = [];
                    $notification['logged_in_user_id'] = $user->user_id;
                    $notification['to_user_id'] = $profile->dept_head_id;
                    $notification['notification_type'] = 'Stakeholder updated';
                    $notification['title'] = $template->title;
                    $notification['message'] = $template->message;
                    $notification['stakeholder_id'] = $entity->stakeholder_id;
                    $notification['interaction_id'] = 0;
                    $notification['dept_id'] = 0;
                    $notification['group_id'] = 0;
                    $notification['organization_id'] = 0;
                    $notification['data'] = json_encode($entity);

                    $notification_id = $this->notification_model->insert($notification);

                    $notification = $this->notification_model->get_notification_by_id($notification_id);

                    $this->user_model->send_push_notification($profile->dept_head_id, $notification, 'IOS');
                }
            }

            if( isset($sql_data['status_by_hod']) )
            {
                $status = $sql_data['status_by_hod'] == 1 ? 'approved' : 'rejected';
                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $entity->request_from;
                $notification['notification_type'] = 'Stakeholder approval for HOD';
                $notification['title'] = "Stakeholder request $status";
                $notification['message'] = "$user->fullname has $status stakeholder $entity->fullname";
                $notification['stakeholder_id'] = $entity->stakeholder_id;
                $notification['interaction_id'] = 0;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($entity->request_from, $notification, 'IOS');
            }

            if( isset($sql_data['status_by_admin']) )
            {
                $status = $sql_data['status_by_admin'] == 1 ? 'approved' : 'rejected';
                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $entity->request_from;
                $notification['notification_type'] = 'Stakeholder approval for HOD';
                $notification['title'] = "Stakeholder request $status";
                $notification['message'] = "$user->fullname has $status stakeholder $entity->fullname";
                $notification['stakeholder_id'] = $entity->stakeholder_id;
                $notification['interaction_id'] = 0;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($entity->request_from, $notification, 'IOS');

                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $profile->dept_head_id;
                $notification['notification_type'] = 'Stakeholder approval for HOD';
                $notification['title'] = "Stakeholder request $status";
                $notification['message'] = "$user->fullname has $status stakeholder $entity->fullname";
                $notification['stakeholder_id'] = $entity->stakeholder_id;
                $notification['interaction_id'] = 0;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($profile->dept_head_id, $notification, 'IOS');
            }

            # All tags values must be defined here
            $logged_user_fullname = $user->fullname;
            $stakeholder_name = $entity->fullname;
            $department = $profile->department;
            $logged_user_initial = $user->initial;
            $group = $entity->group;

            # Get corresponding template for this notification
            $template = $this->template_model->get_template_by_id(9);

            if($template)
            {
                eval("\$template->title = \"$template->title\";"); // replace tags with their values
                eval("\$template->message = \"$template->message\";"); // replace tags with their values

                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $user->user_id;
                $notification['notification_type'] = 'Stakeholder updated';
                $notification['title'] = $template->title;
                $notification['message'] = $template->message;
                $notification['stakeholder_id'] = $entity->stakeholder_id;
                $notification['interaction_id'] = 0;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($user->user_id, $notification, 'IOS');
            }
            $updated_columns = array_keys($sql_data);
            $msg = count($updated_columns) . ' field(s) updated [' . implode(',', $updated_columns) . ']';
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'msg' => $msg, 'data' => $entity,'profile'=>$profile], REST_Controller::HTTP_OK);
        }
    }

    public function get_users_get()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        $keyword = $this->input->get_post('keyword');

        $dept_id = $this->input->get_post('dept_id');
        $group_id = $this->input->get_post('group_id');

        $limit = $this->input->get_post('limit') !== NULL ? $this->input->get_post('limit') : 5000;
        $offset = $this->input->get_post('offset') !== NULL ? $this->input->get_post('offset') : 0;

        $query_params['limit'] = $limit;
        $query_params['offset'] = $offset;
        $query_params['order_by'] = 'user_id';
        $query_params['direction'] = 'ASC';

        if ($group_id > 0) $query_params['group_id'] = $group_id;
        if ($dept_id > 0) $query_params['dept_id'] = $dept_id;

        if ($keyword != '') $query_params['keyword'] = $keyword;

        $rows = $this->user_model->get($query_params);
        //my_var_dump($this->db->last_query());

        foreach ($rows->result() as $row)
        {
            $row->image_url = $row->image ? base_url().'uploads/users/'.$row->image : '';
            $list[] = $row;
        }


        if(! isset($list)) {
            $this->response(['code' => REST_Controller::HTTP_NO_CONTENT, 'status' => 'success', 'msg' => "Record not found"], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','total_records'=>count($list), 'data' => $list], REST_Controller::HTTP_OK);
        }
    }

    public function get_organizations_get()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        $keyword = $this->input->get_post('keyword');

        $limit = $this->input->get_post('limit') !== NULL ? $this->input->get_post('limit') : 5000;
        $offset = $this->input->get_post('offset') !== NULL ? $this->input->get_post('offset') : 0;

        $query_params['limit'] = $limit;
        $query_params['offset'] = $offset;
        $query_params['order_by'] = 'organization';
        $query_params['direction'] = 'ASC';

        if ($keyword != '') $query_params['keyword'] = $keyword;

        $rows = $this->organization_model->get($query_params);
        //my_var_dump($this->db->last_query());

        foreach ($rows->result() as $row)
        {
            $row->image_url = $row->image ? base_url().'uploads/organizations/'.$row->image : '';
            $list[] = $row;
        }


        if(! isset($list)) {
            $this->response(['code' => REST_Controller::HTTP_NO_CONTENT, 'status' => 'success', 'msg' => "Record not found"], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','total_records'=>count($list), 'data' => $list], REST_Controller::HTTP_OK);
        }
    }

    public function get_stakeholders_get()
    {
        $headers = getallheaders();

        if (isset($headers['Authtoken']))
        {
            $user = self::check_token();
            $user_id = $user->user_id;
            if($this->input->get_post('show_all') != 1)
            {
                $query_params['user_id'] = $user_id;
            }
        }

        $stakeholder_id = $this->input->get_post('stakeholder_id');
        $dept_id = $this->input->get_post('dept_id');
        $interaction_id = $this->input->get_post('interaction_id');
        $group_id = $this->input->get_post('group_id');
        $organization_id = $this->input->get_post('organization_id');

        $keyword = $this->input->get_post('keyword');

        $limit = $this->input->get_post('limit') !== NULL ? $this->input->get_post('limit') : 5000;
        $offset = $this->input->get_post('offset') !== NULL ? $this->input->get_post('offset') : 0;

        $query_params['limit'] = $limit;
        $query_params['offset'] = $offset;
        $query_params['order_by'] = 'stakeholder_id';
        $query_params['direction'] = 'DESC';
        $query_params['is_activated'] = 1;

        if ($stakeholder_id > 0) $query_params['stakeholder_id'] = $stakeholder_id;
        if ($dept_id > 0) $query_params['dept_id'] = $dept_id;
        if ($interaction_id > 0) $query_params['interaction_id'] = $interaction_id;
        if ($group_id > 0) $query_params['group_id'] = $group_id;
        if ($organization_id > 0) $query_params['organization_id'] = $organization_id;
        if ($keyword != '') $query_params['keyword'] = $keyword;

        $rows = $this->stakeholder_model->get($query_params);
        //my_var_dump($this->db->last_query());

        foreach ($rows->result() as $row)
        {
            $row->image_url = $row->image ? base_url().'uploads/stakeholders/'.$row->image : '';
            $row->interactions_count = $this->db->query("SELECT COUNT(*) AS `count` FROM interactions_stakeholders WHERE stakeholder_id=$row->stakeholder_id")->row()->count;

            $profile = $this->stakeholder_model->get_stakeholder_profile_by_id($row->stakeholder_id);

            $row->profiles = $profile ? [$profile] : [];
            $list[] = $row;

            /*$id = $row->stakeholder_id;
            $profile = $this->stakeholder_model->get_stakeholder_profile_by_id($id);

            # update stakeholder_code
            $stakeholder_code = $profile->dept_short_code.$profile->group_short_code.$id;
            $this->stakeholder_model->update($id,['stakeholder_code' => $stakeholder_code]);*/
        }


        if(! isset($list)) {
            $this->response(['code' => REST_Controller::HTTP_NO_CONTENT, 'status' => 'success', 'msg' => "Record not found"], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','total_records'=>count($list), 'data' => $list], REST_Controller::HTTP_OK);
        }
    }

    public function get_interactions_get()
    {
        $headers = getallheaders();

        if (isset($headers['Authtoken']))
        {
            $user = self::check_token();
            $user_id = $user->user_id;
            if($this->input->get_post('show_all') != 1)
            {
                $query_params['user_id'] = $user_id;
            }
        }

        $interaction_id = $this->input->get_post('interaction_id');
        $stakeholder_id = $this->input->get_post('stakeholder_id');
        $dept_id = $this->input->get_post('dept_id');

        $keyword = $this->input->get_post('keyword');

        $limit = $this->input->get_post('limit') !== NULL ? $this->input->get_post('limit') : 5000;
        $offset = $this->input->get_post('offset') !== NULL ? $this->input->get_post('offset') : 0;

        $query_params['limit'] = $limit;
        $query_params['offset'] = $offset;
        $query_params['order_by'] = 'date_created';
        $query_params['direction'] = 'DESC';
        $query_params['status'] = 1;

        if ($interaction_id > 0) $query_params['interaction_id'] = $interaction_id;
        if ($stakeholder_id > 0) $query_params['stakeholder_id'] = $stakeholder_id;
        if ($dept_id > 0) $query_params['dept_id'] = $dept_id;
        if ($keyword != '') $query_params['keyword'] = $keyword;

        //my_var_dump($query_params);
        $rows = $this->interaction_model->get($query_params);
        //my_var_dump($this->db->last_query());

        foreach ($rows->result() as $row)
        {
            $row->assets = $this->interaction_model->get_interaction_assets($row->interaction_id);
            $row->referrals = $this->interaction_model->get_interaction_referrals($row->interaction_id);
            $row->stakeholders = $this->interaction_model->get_interaction_stakeholders($row->interaction_id);
            $row->users = $this->interaction_model->get_interaction_users($row->interaction_id);

            $list[] = $row;
        }


        if(! isset($list)) {
            $this->response(['code' => REST_Controller::HTTP_NO_CONTENT, 'status' => 'success', 'msg' => "Record not found"], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','total_records'=>count($list), 'data' => $list], REST_Controller::HTTP_OK);
        }
    }

    public function get_groups_get()
    {
        $user = self::check_token();
        $user_id = $user->user_id;
        $group_id = $this->input->get_post('group_id');
        if($this->input->get_post('show_all') != 1)
        {
            $query_params['user_id'] = $user_id;
        }

        $keyword = $this->input->get_post('keyword');

        $limit = $this->input->get_post('limit') !== NULL ? $this->input->get_post('limit') : 5000;
        $offset = $this->input->get_post('offset') !== NULL ? $this->input->get_post('offset') : 0;

        $query_params['limit'] = $limit;
        $query_params['offset'] = $offset;
        $query_params['order_by'] = 'group_id';
        $query_params['direction'] = 'ASC';

        if ($group_id > 0) $query_params['group_id'] = $group_id;
        if ($this->input->get_post('select')) $query_params['select'] = $this->input->get_post('select');
        if ($keyword != '') $query_params['keyword'] = $keyword;

        $rows = $this->group_model->get($query_params);
        //my_var_dump($this->db->last_query());

        foreach ($rows->result() as $row)
        {
            $row->image_url = $row->image ? base_url().'uploads/departments/'.$row->image : '';
            $row->stakeholders_count = $this->db->query("SELECT COUNT(stakeholder_id) `count` FROM stakeholders_point_of_contacts WHERE group_id=$row->group_id")->row()->count;
            $list[] = $row;
        }


        if(! isset($list)) {
            $this->response(['code' => REST_Controller::HTTP_NO_CONTENT, 'status' => 'success', 'msg' => "Record not found"], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','total_records'=>count($list), 'data' => $list], REST_Controller::HTTP_OK);
        }
    }

    public function get_departments_get()
    {
        $user = self::check_token();
        $user_id = $user->user_id;
        $dept_id = $this->input->get_post('dept_id');
        if($this->input->get_post('show_all') != 1)
        {
            $query_params['user_id'] = $user_id;
        }

        $keyword = $this->input->get_post('keyword');

        $limit = $this->input->get_post('limit') !== NULL ? $this->input->get_post('limit') : 5000;
        $offset = $this->input->get_post('offset') !== NULL ? $this->input->get_post('offset') : 0;

        $query_params['limit'] = $limit;
        $query_params['offset'] = $offset;
        $query_params['order_by'] = 'dept_id';
        $query_params['direction'] = 'ASC';

        if ($dept_id > 0) $query_params['dept_id'] = $dept_id;
        if ($this->input->get_post('select')) $query_params['select'] = $this->input->get_post('select');
        if ($keyword != '') $query_params['keyword'] = $keyword;

        $rows = $this->dept_model->get($query_params);
        //my_var_dump($this->db->last_query());

        foreach ($rows->result() as $row)
        {
            $row->image_url = $row->image ? base_url().'uploads/departments/'.$row->image : '';
            $row->stakeholders_count = $this->db->query("SELECT COUNT(stakeholder_id) `count` FROM stakeholders_point_of_contacts WHERE dept_id=$row->dept_id")->row()->count;
            $row->users_count = $this->db->query("SELECT COUNT(*) AS `count` FROM 
            (SELECT user_id FROM departments WHERE dept_id=$row->dept_id
            UNION
            SELECT user_id FROM users_departments WHERE dept_id=$row->dept_id)
            AS mytable")->row()->count;
            $list[] = $row;
        }


        if(! isset($list)) {
            $this->response(['code' => REST_Controller::HTTP_NO_CONTENT, 'status' => 'success', 'msg' => "Record not found"], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','total_records'=>count($list), 'data' => $list], REST_Controller::HTTP_OK);
        }
    }

    public function add_interaction_post()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        # all possible columns define here
        $possible_columns = ['discussion', 'dept_id','date_created','mode'];

        # loop through all columns
        foreach ($possible_columns as $column) {
            # if column is present in $_REQUEST, include it in sql query
            if ($this->input->get_post($column) !== NULL) {
                $sql_data[$column] = $this->input->get_post($column);
            }
        }

        if (!isset($sql_data)) {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "sql data is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }
        $sql_data['creator_id'] = $user_id;
        $id = $this->interaction_model->insert($sql_data);

        # assets
        $this->interaction_model->update_interaction_assets($id);

        # referrals
        $this->interaction_model->update_interaction_referrals($id,$this->input->get_post('referrals'));

        # stakeholders
        $this->interaction_model->update_interaction_stakeholders($id,$this->input->get_post('stakeholders'));

        # users
        $this->interaction_model->update_interaction_users($id,$this->input->get_post('users'));

        $entity = $this->interaction_model->get_interaction_by_id($id);

        $users = $this->input->get_post('users');
        if(is_array($users) and count($users))
        {
            foreach ($users as $uid)
            {
                # All tags values must be defined here
                $logged_user_fullname = $user->fullname;
                $discussion = $entity->discussion;
                $department = $entity->department;
                $logged_user_initial = $user->initial;
                $group = $entity->group;

                # Get corresponding template for this notification
                $template = $this->template_model->get_template_by_id(10);

                if($template)
                {
                    eval("\$template->title = \"$template->title\";"); // replace tags with their values
                    eval("\$template->message = \"$template->message\";"); // replace tags with their values

                    # Send push notification
                    $notification = [];
                    $notification['logged_in_user_id'] = $user->user_id;
                    $notification['to_user_id'] = $uid;
                    $notification['notification_type'] = 'Interaction added';
                    $notification['title'] = $template->title;
                    $notification['message'] = $template->message;
                    $notification['stakeholder_id'] = 0;
                    $notification['interaction_id'] = $entity->interaction_id;
                    $notification['dept_id'] = 0;
                    $notification['group_id'] = 0;
                    $notification['organization_id'] = 0;
                    $notification['data'] = json_encode($entity);

                    $notification_id = $this->notification_model->insert($notification);

                    $notification = $this->notification_model->get_notification_by_id($notification_id);

                    $this->user_model->send_push_notification($uid, $notification, 'IOS');
                }
            }
        }
        $referrals = $this->input->get_post('referrals');
        if(is_array($referrals) and count($referrals))
        {
            foreach ($referrals as $referral)
            {
                # All tags values must be defined here
                $logged_user_fullname = $user->fullname;
                $discussion = $entity->discussion;
                $department = $entity->department;
                $logged_user_initial = $user->initial;
                $group = $entity->group;

                # Get corresponding template for this notification
                $template = $this->template_model->get_template_by_id(11);

                if($template)
                {
                    eval("\$template->title = \"$template->title\";"); // replace tags with their values
                    eval("\$template->message = \"$template->message\";"); // replace tags with their values

                    # Send push notification
                    $notification = [];
                    $notification['logged_in_user_id'] = $user->user_id;
                    $notification['to_user_id'] = $referral;
                    $notification['notification_type'] = 'Interaction added';
                    $notification['title'] = $template->title;
                    $notification['message'] = $template->message;
                    $notification['stakeholder_id'] = 0;
                    $notification['interaction_id'] = $entity->interaction_id;
                    $notification['dept_id'] = 0;
                    $notification['group_id'] = 0;
                    $notification['organization_id'] = 0;
                    $notification['data'] = json_encode($entity);

                    $notification_id = $this->notification_model->insert($notification);

                    $notification = $this->notification_model->get_notification_by_id($notification_id);

                    $this->user_model->send_push_notification($referral, $notification, 'IOS');
                }
            }
        }

        if($entity->dept_head_id)
        {
            # All tags values must be defined here
            $logged_user_fullname = $user->fullname;
            $discussion = $entity->discussion;
            $department = $entity->department;
            $logged_user_initial = $user->initial;
            $group = $entity->group;

            # Get corresponding template for this notification
            $template = $this->template_model->get_template_by_id(12);

            if($template)
            {
                eval("\$template->title = \"$template->title\";"); // replace tags with their values
                eval("\$template->message = \"$template->message\";"); // replace tags with their values

                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $entity->dept_head_id;
                $notification['notification_type'] = 'Interaction added';
                $notification['title'] = $template->title;
                $notification['message'] = $template->message;
                $notification['stakeholder_id'] = 0;
                $notification['interaction_id'] = $entity->interaction_id;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($entity->dept_head_id, $notification, 'IOS');
            }
        }

        # All tags values must be defined here
        $logged_user_fullname = $user->fullname;
        $discussion = $entity->discussion;
        $department = $entity->department;
        $logged_user_initial = $user->initial;
        $group = $entity->group;

        # Get corresponding template for this notification
        $template = $this->template_model->get_template_by_id(13);

        if($template)
        {
            eval("\$template->title = \"$template->title\";"); // replace tags with their values
            eval("\$template->message = \"$template->message\";"); // replace tags with their values

            # Send push notification
            $notification = [];
            $notification['logged_in_user_id'] = $user->user_id;
            $notification['to_user_id'] = $user->user_id;
            $notification['notification_type'] = 'Interaction added';
            $notification['title'] = $template->title;
            $notification['message'] = $template->message;
            $notification['stakeholder_id'] = 0;
            $notification['interaction_id'] = $entity->interaction_id;
            $notification['dept_id'] = 0;
            $notification['group_id'] = 0;
            $notification['organization_id'] = 0;
            $notification['data'] = json_encode($user);

            $notification_id = $this->notification_model->insert($notification);

            $notification = $this->notification_model->get_notification_by_id($notification_id);

            $this->user_model->send_push_notification($user->user_id, $notification, 'IOS');
        }

        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'data' => $entity], REST_Controller::HTTP_OK);
    }

    public function update_interaction_post()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        if ($this->input->get_post('interaction_id') === NULL) {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "interaction_id parameter is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }
        if ($this->input->get_post('interaction_id') == '') {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "interaction_id parameter is empty"], REST_Controller::HTTP_BAD_REQUEST);
        }

        $id = $this->input->get_post('interaction_id');

        $entity = $this->interaction_model->get_interaction_by_id($id);

        if($entity ===  false)
        {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "The entity you want to update does not exist."], REST_Controller::HTTP_BAD_REQUEST);
        }

        # all possible columns define here
        $possible_columns = ['discussion', 'dept_id','date_created','mode'];

        # loop through all columns
        foreach ($possible_columns as $column) {
            # if column is present in $_REQUEST, include it in sql query
            if ($this->input->get_post($column) !== NULL) {
                $sql_data[$column] = $this->input->get_post($column);
            }
        }

        if (isset($sql_data)) {
            //$this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "sql data is missing"], REST_Controller::HTTP_BAD_REQUEST);

            $result = $this->interaction_model->update($id, $sql_data);
            //my_var_dump($this->db->last_query());
            if ($result === FALSE) {
                $this->response(['code' => REST_Controller::HTTP_EXPECTATION_FAILED, 'status' => 'failed', 'msg' => "Some database error"], REST_Controller::HTTP_EXPECTATION_FAILED);
            }
        }

        # assets
        $this->interaction_model->update_interaction_assets($id);

        # referrals
        $this->interaction_model->update_interaction_referrals($id,$this->input->get_post('referrals'));

        # stakeholders
        $this->interaction_model->update_interaction_stakeholders($id,$this->input->get_post('stakeholders'));

        # users
        $this->interaction_model->update_interaction_users($id,$this->input->get_post('users'));

        $entity = $this->interaction_model->get_interaction_by_id($id);

        $users = $this->input->get_post('users');
        if(is_array($users) and count($users))
        {
            foreach ($users as $uid)
            {
                # All tags values must be defined here
                $logged_user_fullname = $user->fullname;
                $discussion = $entity->discussion;
                $department = $entity->department;
                $logged_user_initial = $user->initial;
                $group = $entity->group;

                # Get corresponding template for this notification
                $template = $this->template_model->get_template_by_id(14);

                if($template)
                {
                    eval("\$template->title = \"$template->title\";"); // replace tags with their values
                    eval("\$template->message = \"$template->message\";"); // replace tags with their values

                    # Send push notification
                    $notification = [];
                    $notification['logged_in_user_id'] = $user->user_id;
                    $notification['to_user_id'] = $uid;
                    $notification['notification_type'] = 'Interaction updated';
                    $notification['title'] = $template->title;
                    $notification['message'] = $template->message;
                    $notification['stakeholder_id'] = 0;
                    $notification['interaction_id'] = $entity->interaction_id;
                    $notification['dept_id'] = 0;
                    $notification['group_id'] = 0;
                    $notification['organization_id'] = 0;
                    $notification['data'] = json_encode($entity);

                    $notification_id = $this->notification_model->insert($notification);

                    $notification = $this->notification_model->get_notification_by_id($notification_id);

                    $this->user_model->send_push_notification($uid, $notification, 'IOS');
                }
            }
        }
        $referrals = $this->input->get_post('referrals');
        if(is_array($referrals) and count($referrals))
        {
            foreach ($referrals as $referral)
            {
                # All tags values must be defined here
                $logged_user_fullname = $user->fullname;
                $discussion = $entity->discussion;
                $department = $entity->department;
                $logged_user_initial = $user->initial;
                $group = $entity->group;

                # Get corresponding template for this notification
                $template = $this->template_model->get_template_by_id(15);

                if($template)
                {
                    eval("\$template->title = \"$template->title\";"); // replace tags with their values
                    eval("\$template->message = \"$template->message\";"); // replace tags with their values

                    # Send push notification
                    $notification = [];
                    $notification['logged_in_user_id'] = $user->user_id;
                    $notification['to_user_id'] = $referral;
                    $notification['notification_type'] = 'Interaction updated';
                    $notification['title'] = $template->title;
                    $notification['message'] = $template->message;
                    $notification['stakeholder_id'] = 0;
                    $notification['interaction_id'] = $entity->interaction_id;
                    $notification['dept_id'] = 0;
                    $notification['group_id'] = 0;
                    $notification['organization_id'] = 0;
                    $notification['data'] = json_encode($entity);

                    $notification_id = $this->notification_model->insert($notification);

                    $notification = $this->notification_model->get_notification_by_id($notification_id);

                    $this->user_model->send_push_notification($referral, $notification, 'IOS');
                }
            }
        }

        if($entity->dept_head_id)
        {
            # All tags values must be defined here
            $logged_user_fullname = $user->fullname;
            $discussion = $entity->discussion;
            $department = $entity->department;
            $logged_user_initial = $user->initial;
            $group = $entity->group;

            # Get corresponding template for this notification
            $template = $this->template_model->get_template_by_id(16);

            if($template)
            {
                eval("\$template->title = \"$template->title\";"); // replace tags with their values
                eval("\$template->message = \"$template->message\";"); // replace tags with their values

                # Send push notification
                $notification = [];
                $notification['logged_in_user_id'] = $user->user_id;
                $notification['to_user_id'] = $entity->dept_head_id;
                $notification['notification_type'] = 'Interaction updated';
                $notification['title'] = $template->title;
                $notification['message'] = $template->message;
                $notification['stakeholder_id'] = 0;
                $notification['interaction_id'] = $entity->interaction_id;
                $notification['dept_id'] = 0;
                $notification['group_id'] = 0;
                $notification['organization_id'] = 0;
                $notification['data'] = json_encode($entity);

                $notification_id = $this->notification_model->insert($notification);

                $notification = $this->notification_model->get_notification_by_id($notification_id);

                $this->user_model->send_push_notification($entity->dept_head_id, $notification, 'IOS');
            }
        }

        # All tags values must be defined here
        $logged_user_fullname = $user->fullname;
        $discussion = $entity->discussion;
        $department = $entity->department;
        $logged_user_initial = $user->initial;
        $group = $entity->group;

        # Get corresponding template for this notification
        $template = $this->template_model->get_template_by_id(17);

        if($template)
        {
            eval("\$template->title = \"$template->title\";"); // replace tags with their values
            eval("\$template->message = \"$template->message\";"); // replace tags with their values

            # Send push notification
            $notification = [];
            $notification['logged_in_user_id'] = $user->user_id;
            $notification['to_user_id'] = $user->user_id;
            $notification['notification_type'] = 'Interaction updated';
            $notification['title'] = $template->title;
            $notification['message'] = $template->message;
            $notification['stakeholder_id'] = 0;
            $notification['interaction_id'] = $entity->interaction_id;
            $notification['dept_id'] = 0;
            $notification['group_id'] = 0;
            $notification['organization_id'] = 0;
            $notification['data'] = json_encode($user);

            $notification_id = $this->notification_model->insert($notification);

            $notification = $this->notification_model->get_notification_by_id($notification_id);

            $this->user_model->send_push_notification($user->user_id, $notification, 'IOS');
        }

        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'msg' => '', 'data' => $entity], REST_Controller::HTTP_OK);
    }

    public function add_organization_post()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        # all possible columns define here
        $possible_columns = ['org_code', 'organization', 'objective', 'geo_scope', 'address1',
            'address2','address3','telephone1','telephone2','telephone3',
            'city','region','province','country','latitude','longitude'];

        # loop through all columns
        foreach ($possible_columns as $column) {
            # if column is present in $_REQUEST, include it in sql query
            if ($this->input->get_post($column) !== NULL) {
                $sql_data[$column] = $this->input->get_post($column);
            }
        }

        if (!isset($sql_data)) {
            $this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "sql data is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }

        # File uploading configuration
        $upload_path = './uploads/organizations/';
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'jpg|png|jpeg';
        $config['encrypt_name'] = true;

        $this->load->library('upload', $config);

        # Try to upload file now
        if ($this->upload->do_upload('image')) {
            # Get uploading detail here
            $upload_detail = $this->upload->data();

            $sql_data['image'] = $upload_detail['file_name'];
            $image = $sql_data['image'];

            # Get width and height and resize image keeping aspect ratio same
            $image_path = $upload_path . $image;
            $width = get_width($image_path);
            $width > 800 ? resize_image2($image_path, 800, '', 'W') : '';
            $height = get_height($image_path);
            $height > 800 ? resize_image2($image_path, '', 800, 'H') : '';
        }
        $sql_data['is_activated'] = 1;

        $sql_data['groups'] = is_array($this->input->get_post('groups')) ? $this->input->get_post('groups') : [];

        $id = $this->organization_model->insert($sql_data);

        $this->organization_model->update($id,['org_code'=>"ORG$id"]);

        $entity = $this->organization_model->get_organization_by_id($id);

        # All tags values must be defined here
        $logged_user_fullname = $user->fullname;
        $organization = $entity->organization;
        $logged_user_initial = $user->initial;

        # Get corresponding template for this notification
        $template = $this->template_model->get_template_by_id(18);

        if($template)
        {
            eval("\$template->title = \"$template->title\";"); // replace tags with their values
            eval("\$template->message = \"$template->message\";"); // replace tags with their values

            # Send push notification
            $notification = [];
            $notification['logged_in_user_id'] = $user->user_id;
            $notification['to_user_id'] = $user->user_id;
            $notification['notification_type'] = 'Interaction updated';
            $notification['title'] = $template->title;
            $notification['message'] = $template->message;
            $notification['stakeholder_id'] = 0;
            $notification['interaction_id'] = 0;
            $notification['dept_id'] = 0;
            $notification['group_id'] = 0;
            $notification['organization_id'] = $entity->organization_id;
            $notification['data'] = json_encode($entity);

            $notification_id = $this->notification_model->insert($notification);

            $notification = $this->notification_model->get_notification_by_id($notification_id);

            $this->user_model->send_push_notification($user->user_id, $notification, 'IOS');
        }

        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'data' => $entity], REST_Controller::HTTP_OK);
    }

    public function get_dropdown_get()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        if ($this->input->get_post('dropdown') === NULL) {
            //$this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "dropdown parameter is missing"], REST_Controller::HTTP_BAD_REQUEST);
        }
        if ($this->input->get_post('dropdown') == '') {
            //$this->response(['code' => REST_Controller::HTTP_BAD_REQUEST, 'status' => 'failed', 'msg' => "dropdown parameter is empty"], REST_Controller::HTTP_BAD_REQUEST);
        }

        $dropdown = $this->input->get_post('dropdown');

        $this->db->select('dropdown,key,value,sequence');
        if($dropdown)
        {
            $this->db->where('dropdown',$dropdown);
        }
        $this->db->order_by('sequence','ASC');
        //$this->db->group_by('dropdown');
        $query = $this->db->get('dropdowns');
        foreach ($query->result() as $row)
        {
            $list[] = $row;
        }

        $this->db->select('dropdown');
        $this->db->group_by('dropdown');
        $query = $this->db->get('dropdowns');
        foreach ($query->result() as $row)
        {
            $possible[] = $row->dropdown;
        }

        if(! isset($list)) {

            $this->response(['code' => REST_Controller::HTTP_NO_CONTENT, 'status' => 'success', 'msg' => "Record not found",'Dropdown_possible_values'=>$possible], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','total_records'=>count($list), 'data' => $list,'Dropdown_possible_values'=>$possible], REST_Controller::HTTP_OK);
        }
    }

    public function get_cities_get()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        $query_params['select'] = 'city';
        $query_params['keyword'] = $this->input->get_post('keyword');
        $query_params['limit'] = $this->input->get_post('limit') !== NULL ? $this->input->get_post('limit') : 50;
        $query_params['offset'] = $this->input->get_post('offset') !== NULL ? $this->input->get_post('offset') : 0;
        $query_params['order_by'] = 'city';
        $query_params['direction'] = 'ASC';
        $query_params['group_by'] = 'city';
        if($this->input->get_post('province')!==NULL) $query_params['province'] = $this->input->get_post('province');

        $rows = $this->general_model->get_cities($query_params);
        //my_var_dump($this->db->last_query());

        $cities = [];
        foreach ($rows->result() as $row) {
            $cities[] = $row->city;
        }

        $provinces = [];
        $this->db->select('province');
        $this->db->group_by('province');
        $query = $this->db->get('cities');
        foreach ($query->result() as $row)
        {
            $provinces[] = $row->province;
        }
        $data['provinces'] = $provinces;
        $data['cities'] = $cities;

        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success', 'data' => $data], REST_Controller::HTTP_OK);

    }

    public function get_counts_get()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        $counts['departments_count'] = $this->db->query("SELECT COUNT(*) AS `count` FROM 
            (SELECT dept_id FROM departments WHERE user_id=$user_id
            UNION
            SELECT dept_id FROM users_departments WHERE user_id=$user_id)
            AS mytable")->row()->count;
        $counts['stakeholders_count'] = $this->db->query("SELECT COUNT(*) AS `count` FROM
            (SELECT stakeholder_id FROM stakeholders WHERE dri_user_id=$user_id
            UNION
            SELECT stakeholder_id FROM stakeholders_point_of_contacts WHERE user_id=$user_id)
            AS mytable")->row()->count;

        $counts['stakeholders_dri_count'] = $this->db->query("SELECT COUNT(stakeholder_id) AS `count` FROM stakeholders WHERE dri_user_id=$user_id")->row()->count;

        $counts['interactions_count'] = $this->db->query("SELECT COUNT(*) AS `count` FROM
            (SELECT interaction_id FROM interactions WHERE creator_id=$user_id
            UNION
            SELECT interaction_id FROM interactions_users WHERE user_id=$user_id)
            AS mytable")->row()->count;

        $counts['interactions_referrals_count'] = $this->db->query("SELECT COUNT(interaction_id) AS `count` FROM interactions_referrals WHERE user_id=$user_id")->row()->count;

        $counts['notification_count_unread'] = $this->db->query("SELECT COUNT(*) AS `count` FROM notifications WHERE to_user_id=$user_id AND is_read=0")->row()->count;
        $counts['notification_count'] = $this->db->query("SELECT COUNT(*) AS `count` FROM notifications WHERE to_user_id=$user_id")->row()->count;


        $counts['departments_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM departments")->row()->count;
        $counts['organizations_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM organizations")->row()->count;
        $counts['interactions_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM interactions")->row()->count;
        $counts['groups_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM groups")->row()->count;
        $counts['stakeholders_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM stakeholders")->row()->count;

        $counts['version_detail'] = $this->general_model->get_latest_version();
        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','data' => $counts], REST_Controller::HTTP_OK);

    }

    public function get_notifications_get()
    {
        $user = self::check_token();
        $user_id = $user->user_id;

        $keyword = $this->input->get_post('keyword');

        $limit = $this->input->get_post('limit') !== NULL ? $this->input->get_post('limit') : 5000;
        $offset = $this->input->get_post('offset') !== NULL ? $this->input->get_post('offset') : 0;

        $query_params['to_user_id'] = $user_id;
        $query_params['is_read'] = 0;
        $query_params['limit'] = $limit;
        $query_params['offset'] = $offset;
        $query_params['order_by'] = 'notification_id';
        $query_params['direction'] = 'DESC';

        if ($keyword != '') $query_params['keyword'] = $keyword;

        $rows = $this->notification_model->get($query_params);
        //my_var_dump($this->db->last_query());

        foreach ($rows->result() as $row)
        {
            $list[] = $row;
        }


        if(! isset($list)) {
            $this->response(['code' => REST_Controller::HTTP_NO_CONTENT, 'status' => 'success', 'msg' => "Record not found"], REST_Controller::HTTP_OK);
        }
        else
        {
            $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','total_records'=>count($list), 'data' => $list], REST_Controller::HTTP_OK);
        }
    }

    public function get_counts_all_users_get()
    {
        $users = $this->db->get('users');

        foreach ($users->result() as $user)
        {
            $user_id = $user->user_id;
            $counts['user_id'] = $user_id;
            $counts['initial'] = $user->initial;
            $counts['fullname'] = $user->fullname;

            $counts['departments_count'] = $this->db->query("SELECT COUNT(*) AS `count` FROM 
            (SELECT dept_id FROM departments WHERE user_id=$user_id
            UNION
            SELECT dept_id FROM users_departments WHERE user_id=$user_id)
            AS mytable")->row()->count;
            $counts['stakeholders_count'] = $this->db->query("SELECT COUNT(*) AS `count` FROM
            (SELECT stakeholder_id FROM stakeholders WHERE dri_user_id=$user_id
            UNION
            SELECT stakeholder_id FROM stakeholders_point_of_contacts WHERE user_id=$user_id)
            AS mytable")->row()->count;

            $counts['stakeholders_dri_count'] = $this->db->query("SELECT COUNT(stakeholder_id) AS `count` FROM stakeholders WHERE dri_user_id=$user_id")->row()->count;

            $counts['interactions_count'] = $this->db->query("SELECT COUNT(*) AS `count` FROM
            (SELECT interaction_id FROM interactions WHERE creator_id=$user_id
            UNION
            SELECT interaction_id FROM interactions_users WHERE user_id=$user_id)
            AS mytable")->row()->count;

            $counts['interactions_referrals_count'] = $this->db->query("SELECT COUNT(interaction_id) AS `count` FROM interactions_referrals WHERE user_id=$user_id")->row()->count;

            $counts['notification_count_unread'] = $this->db->query("SELECT COUNT(*) AS `count` FROM notifications WHERE to_user_id=$user_id AND is_read=0")->row()->count;
            $counts['notification_count'] = $this->db->query("SELECT COUNT(*) AS `count` FROM notifications WHERE to_user_id=$user_id")->row()->count;


            $counts['departments_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM departments")->row()->count;
            $counts['organizations_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM organizations")->row()->count;
            $counts['interactions_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM interactions")->row()->count;
            $counts['groups_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM groups")->row()->count;
            $counts['stakeholders_count_total'] = $this->db->query("SELECT COUNT(*) AS `count` FROM stakeholders")->row()->count;


            $result[] = $counts;
        }

        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','data' => $result], REST_Controller::HTTP_OK);

    }

    public function get_reports_get()
    {
        $query = $this->db->query("
        SELECT COUNT(stakeholder_id) AS stakeholders_count,
        (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) AS interaction_count
        FROM stakeholders
        WHERE (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) =0
        
        UNION 
        
        SELECT COUNT(stakeholder_id) AS stakeholders_count,
        (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) AS interaction_count
        FROM stakeholders
        WHERE (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) BETWEEN 1 AND 3
        
        UNION
        
        SELECT COUNT(stakeholder_id) AS stakeholders_count,
        (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) AS interaction_count
        FROM stakeholders
        WHERE (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) BETWEEN 4 AND 6
        
        UNION
        
        SELECT COUNT(stakeholder_id) AS stakeholders_count,
        (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) AS interaction_count
        FROM stakeholders
        WHERE (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) BETWEEN 7 AND 10
        
        UNION
        
        SELECT COUNT(stakeholder_id) AS stakeholders_count,
        (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) AS interaction_count
        FROM stakeholders
        WHERE (SELECT COUNT(*) FROM interactions_stakeholders WHERE stakeholder_id=stakeholders.stakeholder_id) > 10");

        $legend[] = '0';
        $legend[] = '1-3';
        $legend[] = '4-6';
        $legend[] = '7-10';
        $legend[] = '>10';
        $i=0;
        foreach ($query->result() as $row)
        {
            $stakeholders_count_interaction_wise[$legend[$i]] = ['interaction_count_segment' => $legend[$i],'stakeholders_count' => $row->stakeholders_count];
            $i++;
        }

        $result['stakeholders_count_interaction_wise'] = $stakeholders_count_interaction_wise;
        $result['stakeholders_count_by_province'] = $this->db->query("
        SELECT COUNT(*) AS stakeholders_count,province FROM stakeholders 
        GROUP BY province")->result();

        $result['interactions_count_by_province'] = $this->db->query("
        SELECT COUNT(*) AS interactions_count,province FROM view_interactions_stakeholders 
        GROUP BY province")->result();

        $result['stakeholders_count_by_month'] = $this->db->query("
        SELECT COUNT(*) AS stakeholders_count,
        DATE_FORMAT(date_created,'%Y-%m') AS `year_month` 
        FROM stakeholders 
        GROUP BY `year_month`")->result();

        $result['interactions_count_by_month'] = $this->db->query("
        SELECT COUNT(*) AS interactions_count,
        DATE_FORMAT(date_created,'%Y-%m') AS `year_month` 
        FROM interactions 
        GROUP BY `year_month`")->result();

        $result['stakeholders_count_by_month_and_department'] = $this->db->query("
        SELECT COUNT(*) AS stakeholders_count,department,
        DATE_FORMAT(date_created,'%Y-%m') AS `year_month` 
        FROM view_stakeholders 
        GROUP BY `year_month`,department")->result();

        $result['stakeholders_count_by_month_and_group'] = $this->db->query("
        SELECT COUNT(*) AS stakeholders_count,`group`,
        DATE_FORMAT(date_created,'%Y-%m') AS `year_month` 
        FROM view_stakeholders 
        GROUP BY `year_month`,`group`")->result();

        $result['interactions_count_by_month_and_department'] = $this->db->query("
        SELECT COUNT(*) AS interactions_count,department,
        DATE_FORMAT(date_created,'%Y-%m') AS `year_month` 
        FROM view_interactions 
        GROUP BY `year_month`,department")->result();

        $this->response(['code' => REST_Controller::HTTP_OK, 'status' => 'success','data' => $result], REST_Controller::HTTP_OK);

    }

    public function send_notification_to_all_get()
    {
        $notification = new stdClass();
        $notification->title = 'Testing FCM';
        $notification->message = 'This is testing message of notification';

        $query = $this->db->get_where('users_devices', ['device_id !=' => '']);
        my_var_dump($this->db->last_query());
        foreach ($query->result() as $row) {
            if ($row->type == 'IOS') {
                //$this->notification_model->sendPushNotificationIOS($row->device_id, $notification);
            }
            if ($row->type == 'ANDROID' or $row->type == 'IOS') {
                my_var_dump("Sending push notification to $row->user_id $row->device_id $row->type");
                $this->notification_model->sendPushNotificationAndroid($row->device_id, $notification);
            }
        }
    }
}