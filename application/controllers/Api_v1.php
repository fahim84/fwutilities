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
}