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


                    $id = $this->image_model->insert($data);

                    $data['image_id'] = $id;
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

                    $id = $this->image_model->insert($data);

                    $data['image_id'] = $id;
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

                $dst_x = 0;
                $dst_y = 0;
                $src_x = $get_post['x'];
                $src_y = $get_post['y'];

                $dst_w = $get_post['w'];
                $dst_h = $get_post['h'];
                $src_w = $get_post['w'];
                $src_h = $get_post['h'];

                $jpeg_quality = 90;

                $image_data = getimagesize($image_path);

                $mimeType = $image_data['mime'];
                if(preg_match('/^image\/(?:jpg|jpeg)$/i', $mimeType))
                {
                    $src_image = imagecreatefromjpeg($image_path);
                    $dst_image = ImageCreateTrueColor( $dst_w, $dst_h );

                    imagecopyresampled($dst_image,$src_image,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h);
                    imagejpeg($dst_image,$image_path,$jpeg_quality);
                }
                else if(preg_match('/^image\/png$/i', $mimeType))
                {
                    $src_image = imagecreatefrompng($image_path);
                    $dst_image = ImageCreateTrueColor( $dst_w, $dst_h );
                    imagecopyresampled($dst_image,$src_image,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h);
                    imagepng($dst_image,$image_path,floor($jpeg_quality * 0.09));
                }
                else if(preg_match('/^image\/gif$/i', $mimeType))
                {
                    $src_image = imagecreatefromgif($image_path);
                    $dst_image = ImageCreateTrueColor( $dst_w, $dst_h );
                    imagecopyresampled($dst_image,$src_image,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h);
                    imagegif($dst_image,$image_path);
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

    public function add_location()
    {
        $get_post = $this->input->get_post(null,true);
        $upload_path = './uploads/images/';

        if(@$_GET['flip'])
        {
            $image_id = $_GET['image_id'];
            $flip_mode = $_GET['flip'];
            $file = $this->image_model->get_image_by_id($image_id);
            $atti['output_image_name'] = $file->image;
            $atti['output_relative_path'] = $upload_path;
            $atti['image_path'] = $upload_path.$file->image;
            $atti['file_type'] = $file->file_type;
            $atti['flip_mode'] = $flip_mode ? $flip_mode : 'IMG_FLIP_VERTICAL';
            //flipimage($atti);
            rotate_image($atti);
            $width = get_width($upload_path.$file->image);
            $height = get_height($upload_path.$file->image);
            $this->image_model->update($image_id,['width' => $width,'height' => $height]);

            if($file->location_url)
            {
                $file = $this->image_model->get_image_by_id($image_id);
                $atti['output_image_name'] = $file->modified_image;
                $atti['size'] = round($file->width / 53);
                $atti['angle'] = 0;
                $atti['text_location'] = 'bottom_left';
                $atti['image_width'] = $file->width;
                $atti['image_height'] = $file->height;
                $atti['string'] = $file->location_url;
                add_text_to_image($atti);
            }

            redirect(base_url().'image/add_location/?image_id='.$image_id);
        }
        if(@$_GET['download'])
        {
            // Force Download
            $this->load->helper('download');
            force_download($upload_path.$_GET['download'], NULL);
        }

        // Set the validation rules
        $this->form_validation->set_rules('image', 'Image', 'trim');

        $data = [];

        // If the validation worked
        if ($this->form_validation->run())
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
                $data['modified_image'] = $upload_detail['file_name'];
                $data['width'] = $upload_detail['image_width'];
                $data['height'] = $upload_detail['image_height'];
                $data['extension'] = $upload_detail['file_ext'];
                $data['file_type'] = $upload_detail['file_type'];
                $data['image_type'] = $upload_detail['image_type'];
                $data['file_size'] = $upload_detail['file_size'];

                $id = $this->image_model->insert($data);
                $file = $this->image_model->get_image_by_id($data['image_id']);

                $data['image_id'] = $id;
                $image = $data['image'];

                $location = get_image_latitude_longitude($upload_path.$image);
                if($location)
                {
                    $this->image_model->update($data['image_id'],$location);
                    $file = $this->image_model->get_image_by_id($data['image_id']);

                    // Add location to image
                    $atti['output_image_name'] = 'm_'.$image;
                    $atti['output_relative_path'] = $upload_path;
                    $atti['image_path'] = $upload_path.$image;
                    $atti['file_type'] = $data['file_type'];
                    $atti['size'] = round($file->width / 53);
                    $atti['angle'] = 0;
                    $atti['text_location'] = 'bottom_left';
                    $atti['image_width'] = $file->width;
                    $atti['image_height'] = $file->height;
                    $atti['string'] = $file->location_url;

                    add_text_to_image($atti);
                    $this->image_model->update($data['image_id'],['modified_image' => $atti['output_image_name']]);
                }

                redirect(base_url().'image/add_location/?image_id='.$data['image_id']);
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

        if(@$_GET['image_id'] > 0)
        {
            $file = $this->image_model->get_image_by_id($_GET['image_id']);
            $this->data['file'] = $file;
        }
        else
        {
            $this->data['file'] = null;
        }

        $this->data['active'] = 'location';
        $this->load->view('add_location',$this->data);
    }

    public function add_logo()
    {
        $get_post = $this->input->get_post(null,true);
        $upload_path = './uploads/images/';

        if(@$_GET['flip'])
        {
            $image_id = $_GET['image_id'];
            $flip_mode = $_GET['flip'];
            $file = $this->image_model->get_image_by_id($image_id);
            $atti['output_image_name'] = $file->modified_image;
            $atti['output_relative_path'] = $upload_path;
            $atti['image_path'] = $upload_path.$file->modified_image;
            $atti['file_type'] = $file->file_type;
            $atti['flip_mode'] = $flip_mode ? $flip_mode : 'IMG_FLIP_VERTICAL';
            //flipimage($atti);
            rotate_image($atti);

            redirect(base_url().'image/add_logo/?image_id='.$image_id);
        }
        if(@$get_post['image_id'])
        {
            $image_id = $get_post['image_id'];
            $file = $this->image_model->get_image_by_id($image_id);

            //my_var_dump($file);
            //my_var_dump($get_post);
            $width_percent = $get_post['width'] * 100 / $get_post['image_container_width'];
            $height_percent = $get_post['height'] * 100 / $get_post['image_container_height'];

            if($get_post['image_container_width'] < $file->width)
            {
                $width_percent = -1 * abs($width_percent);
            }
            if($get_post['image_container_height'] < $file->height)
            {
                $height_percent = -1 * abs($height_percent);
            }

            $x_percent = $get_post['x'] * 100 / $get_post['image_container_width'];
            $y_percent = $get_post['y'] * 100 / $get_post['image_container_height'];

            if($get_post['image_container_width'] < $file->width)
            {
                $x_percent = -1 * abs($x_percent);
            }
            if($get_post['image_container_height'] < $file->height)
            {
                $y_percent = -1 * abs($y_percent);
            }

            //my_var_dump($width_percent);
            //my_var_dump($height_percent);
            $alti['image_path'] = $upload_path.$file->modified_image;
            $alti['logo_path'] = $upload_path.$file->image2;
            $alti['x'] = round($file->width * $x_percent / 100);
            $alti['y'] = round($file->height * $y_percent / 100);
            $alti['width'] = round($file->width * $width_percent / 100);
            $alti['height'] = round($file->height * $height_percent / 100);

            //my_var_dump($alti);
            //exit;
            add_logo_to_image($alti);

            // Force Download
            $this->load->helper('download');
            force_download($upload_path.$file->modified_image, NULL);
        }

        // Set the validation rules
        $this->form_validation->set_rules('image', 'Image', 'trim');
        $this->form_validation->set_rules('image2', 'Logo', 'trim');

        $data = [];

        // If the validation worked
        if ($this->form_validation->run())
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
                $data['modified_image'] = $upload_detail['file_name'];
                $data['width'] = $upload_detail['image_width'];
                $data['height'] = $upload_detail['image_height'];
                $data['extension'] = $upload_detail['file_ext'];
                $data['file_type'] = $upload_detail['file_type'];
                $data['image_type'] = $upload_detail['image_type'];
                $data['file_size'] = $upload_detail['file_size'];

                $id = $this->image_model->insert($data);
                $file = $this->image_model->get_image_by_id($data['image_id']);

                $data['image_id'] = $id;
                $image = $data['image'];

                # Try to upload file now
                if ($this->upload->do_upload('image2'))
                {
                    # Get uploading detail here
                    $upload_detail = $this->upload->data();

                    $logo['image2'] = $upload_detail['file_name'];
                    $this->image_model->update($id,$logo);
                }
                redirect(base_url().'image/add_logo/?image_id='.$id);
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

            if(@$_GET['image_id'] > 0)
            {
                # Try to upload file now
                if ($this->upload->do_upload('image2'))
                {
                    $id = $_GET['image_id'];

                    $entity = $this->image_model->get_image_by_id($id);
                    if($entity->image2)
                    {
                        // delete existing logo
                        delete_file($upload_path.$entity->image2);
                        $_SESSION['msg_error'][] = $entity->image2.' file deleted!';
                    }

                    # Get uploading detail here
                    $upload_detail = $this->upload->data();

                    $logo['image2'] = $upload_detail['file_name'];
                    $this->image_model->update($id,$logo);
                }
            }
            redirect(base_url().'image/add_logo/?image_id='.$id);

        }

        if(@$_GET['image_id'] > 0)
        {
            $file = $this->image_model->get_image_by_id($_GET['image_id']);
            $this->data['file'] = $file;
        }
        else
        {
            $this->data['file'] = null;
        }

        $this->data['active'] = 'logo';
        $this->load->view('add_logo',$this->data);
    }

    public function add_logo_test()
    {
        $upload_path = './uploads/images/';

        // Add location to image
        $alti['image_path'] = $upload_path.'m_43c61a3246683c77d757830566ca02d1.JPG';
        $alti['logo_path'] = $upload_path.'f2fa08bb8ef016e0c0f8e911ac48fee4.png';

        add_logo_to_image($alti);
    }
}
