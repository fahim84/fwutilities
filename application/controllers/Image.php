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

                    $data['session_id'] = session_id();
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
        $get_post = $this->input->get_post(null,true);
        $upload_path = './uploads/images/';

        if(@$_GET['download'])
        {
            // Force Download
            $this->load->helper('download');
            force_download($upload_path.$_GET['download'], NULL);
        }
        $this->data['download'] = 0;

        // Set the validation rules
        if(@$get_post['submit_button'] == 'crop')
        {
            $this->form_validation->set_rules('x', 'X', 'required|trim');
            $this->form_validation->set_rules('y', 'Y', 'required|trim');
            $this->form_validation->set_rules('w', 'W', 'required|trim');
            $this->form_validation->set_rules('h', 'H', 'required|trim');
            $this->form_validation->set_rules('image', 'Image', 'required|trim');
        }
        else
        {
            $this->form_validation->set_rules('image', 'Image', 'trim');
        }


        $data = [];
        // If the validation worked
        if ($this->form_validation->run())
        {
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

                    $data['session_id'] = session_id();
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

            if($get_post['submit_button'] == 'crop')
            {
                $row = $this->db->get_where('images',['image_id' => $get_post['image_id']])->row();
                $image_path = $upload_path.$row->image;

                $targ_w = 1056;
                $targ_h = 800;
                $jpeg_quality = 90;

                $image_name = $this->input->get_post('image');
                $src = $image_path;
                $image_data = getimagesize($src);

                $mimeType = $image_data['mime'];
                if(preg_match('/^image\/(?:jpg|jpeg)$/i', $mimeType))
                {
                    $img_r = imagecreatefromjpeg($src);
                    $dst_r = ImageCreateTrueColor( $targ_w, $targ_h );
                    imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],$targ_w,$targ_h,$_POST['w'],$_POST['h']);
                    imagejpeg($dst_r,$src,$jpeg_quality);
                }
                else if(preg_match('/^image\/png$/i', $mimeType))
                {
                    $img_r = imagecreatefrompng($src);
                    $dst_r = ImageCreateTrueColor( $targ_w, $targ_h );
                    imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],$targ_w,$targ_h,$_POST['w'],$_POST['h']);
                    imagepng($dst_r,$src,floor($jpeg_quality * 0.09));
                }
                else if(preg_match('/^image\/gif$/i', $mimeType))
                {
                    $img_r = imagecreatefromgif($src);
                    $dst_r = ImageCreateTrueColor( $targ_w, $targ_h );
                    imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],$targ_w,$targ_h,$_POST['w'],$_POST['h']);
                    imagegif($dst_r,$src);
                }
                else
                {
                    my_var_dump("Could not match mime type");
                    $_SESSION['msg_error'][] = 'Could not match mime type';
                }

                //$_SESSION['msg_success'][] = 'Cropped successful';

                $this->data['download'] = 1;
                $data = (array)$row;

            }

        }

        $this->data['file'] = $data;
        $this->data['active'] = 'crop';
        $this->load->view('image_crop',$this->data);
    }
}
