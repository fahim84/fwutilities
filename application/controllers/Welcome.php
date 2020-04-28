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
            $this->email->reply_to($get_post['email'], $get_post['name']);
            $this->email->to(SUPPORT_EMAIL,SYSTEM_NAME);
            $this->email->subject($get_post['subject']);
            $this->email->message(nl2br($get_post['message']));
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
}
