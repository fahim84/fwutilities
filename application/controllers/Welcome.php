<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

    public $data;

	public function index()
	{
        $this->data['image'] = null;
	    $this->data['active'] = 'home';
		$this->load->view('index',$this->data);
	}

    public function about()
    {
        $this->data['active'] = 'about';
        $this->load->view('about',$this->data);
    }

    public function contact()
    {
        // Set the validation rules
        $this->form_validation->set_rules('name', 'Name', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required');
        $this->form_validation->set_rules('subject', 'Subject', 'trim|required');
        $this->form_validation->set_rules('message', 'Message', 'trim|required');

        // If the validation worked
        if ($this->form_validation->run())
        {
            $get_post = $this->input->get_post(null,true);

            // send email here
            $this->email->clear(TRUE);
            $this->email->set_mailtype("html");
            $this->email->from(SYSTEM_EMAIL, $get_post['name']);
            $this->email->to(SUPPORT_EMAIL,SYSTEM_NAME);
            $this->email->subject($get_post['subject']);
            $this->email->message($get_post['message']);
            if($this->email->send())
            {
                $_SESSION['msg_success'][] = 'Email has been sent, you will get response soon.';
            }
            else
            {
                $_SESSION['msg_error'][] = 'An error occurred while sending email.';
            }
        }

        $this->data['active'] = 'contact';
        $this->load->view('contact',$this->data);
    }

    public function upload()
    {
        // Set the validation rules
        $this->form_validation->set_rules('target_width', 'Width', 'trim');
        $this->form_validation->set_rules('target_height', 'Height', 'trim');

        // If the validation worked
        if ($this->form_validation->run())
        {
            $get_post = $this->input->get_post(null,true);

            # File uploading configuration
            $upload_path = './uploads/images/';
            $config['upload_path'] = $upload_path;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';
            $config['encrypt_name'] = true;
            $config['max_size'] = 51200; //KB

            $this->load->library('upload', $config);

            # Try to upload file now
            if ($this->upload->do_upload('image'))
            {
                # Get uploading detail here
                $upload_detail = $this->upload->data();

                $get_post['image'] = $upload_detail['file_name'];
                $image = $get_post['image'];
            }
            else
            {
                $uploaded_file_array = (isset($_FILES['image']) and $_FILES['image']['name']!='') ? $_FILES['image'] : '';

                # Show uploading error only when the file uploading attempt exist.
                if( is_array($uploaded_file_array) )
                {
                    $uploading_error = $this->upload->display_errors();
                    $_SESSION['msg_error'][] = $uploading_error;
                }
            }
        }

        $this->data['image'] = @$image;
        $this->data['active'] = 'home';
        $this->load->view('index',$this->data);
    }
}
