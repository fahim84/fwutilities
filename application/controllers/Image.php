<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Image extends CI_Controller {

    public $data;

    public function resize()
    {
        // Set the validation rules
        $this->form_validation->set_rules('target_width', 'Width', 'trim');
        $this->form_validation->set_rules('target_height', 'Height', 'trim');

        $data = [];
        // If the validation worked
        if ($this->form_validation->run())
        {
            $get_post = $this->input->get_post(null,true);

            $upload_path = './uploads/images/';

            if($get_post['submit_button'] == 'upload')
            {
                # File uploading configuration
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

                    $data['image'] = $upload_detail['file_name'];
                    $data['width'] = $upload_detail['image_width'];
                    $data['height'] = $upload_detail['image_height'];
                    $data['extension'] = $upload_detail['file_ext'];
                    $data['file_type'] = $upload_detail['file_type'];
                    $data['image_type'] = $upload_detail['image_type'];
                    $data['file_size'] = $upload_detail['file_size'];
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $data['updated_at'] = date('Y-m-d H:i:s');

                    $this->db->insert('images',$data);

                    $data['image_id'] = $this->db->insert_id();
                    $image = $data['image'];
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

            if($get_post['submit_button'] == 'download')
            {
                $row = $this->db->get_where('images',['image_id' => $get_post['image_id']])->row();
                if($get_post['target_width'] and $get_post['target_height'])
                {
                    # Get width and height and resize image keeping aspect ratio same
                    $image_path = $upload_path.$row->image;

                    resize_image2($image_path, $get_post['target_width'], '', 'W');
                    resize_image2($image_path, '', $get_post['target_height'], 'W');

                    // Force Download
                    $this->load->helper('download');
                    force_download($image_path, NULL);
                }
            }

        }

        $this->data['file'] = $data;
        $this->data['active'] = 'resize';
        $this->load->view('image_resize',$this->data);
    }

    public function crop()
    {
        // Set the validation rules
        $this->form_validation->set_rules('target_width', 'Width', 'trim');
        $this->form_validation->set_rules('target_height', 'Height', 'trim');

        $data = [];
        // If the validation worked
        if ($this->form_validation->run())
        {
            $get_post = $this->input->get_post(null,true);

            $upload_path = './uploads/images/';

            if($get_post['submit_button'] == 'upload')
            {
                # File uploading configuration
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

                    $data['image'] = $upload_detail['file_name'];
                    $data['width'] = $upload_detail['image_width'];
                    $data['height'] = $upload_detail['image_height'];
                    $data['extension'] = $upload_detail['file_ext'];
                    $data['file_type'] = $upload_detail['file_type'];
                    $data['image_type'] = $upload_detail['image_type'];
                    $data['file_size'] = $upload_detail['file_size'];
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $data['updated_at'] = date('Y-m-d H:i:s');

                    $this->db->insert('images',$data);

                    $data['image_id'] = $this->db->insert_id();
                    $image = $data['image'];
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

            if($get_post['submit_button'] == 'download')
            {
                $row = $this->db->get_where('images',['image_id' => $get_post['image_id']])->row();
                if($get_post['target_width'] and $get_post['target_height'])
                {
                    # Get width and height and resize image keeping aspect ratio same
                    $image_path = $upload_path.$row->image;

                    resize_image2($image_path, $get_post['target_width'], '', 'W');
                    resize_image2($image_path, '', $get_post['target_height'], 'W');

                    // Force Download
                    $this->load->helper('download');
                    force_download($image_path, NULL);
                }
            }

        }

        $this->data['file'] = $data;
        $this->data['active'] = 'resize';
        $this->load->view('image_resize',$this->data);
    }
}
