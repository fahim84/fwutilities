<?php
//require_once 'vendor/autoload.php';
class User_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function get($params = [], $count_result = false)
	{
        if(isset($params['is_activated'])) { $this->db->where('is_activated', $params['is_activated']); }
		if(isset($params['select'])) { $this->db->select($params['select']); }
		if(isset($params['where_in'])) { $this->db->where_in('user_id',$params['where_in']); }
		if(isset($params['where_not_in'])) { $this->db->where_not_in('user_id',$params['where_not_in']); }

		if(isset($params['keyword']) and $params['keyword']!='')
		{
			$this->db->like('fullname', $params['keyword']);
			$this->db->or_like('email', $params['keyword']);
			$this->db->or_like('mobile', $params['keyword']);
			$this->db->or_like('city', $params['keyword']);
			$this->db->or_like('province', $params['keyword']);
			$this->db->or_like('address', $params['keyword']);
		}
		
		# If true, count results and return it
		if($count_result)
		{
			$this->db->from('users');
			$count = $this->db->count_all_results();
			return $count;
		}
		
		if(isset($params['limit'])) { $this->db->limit($params['limit'], $params['offset']); }
		if(isset($params['order_by'])){ $this->db->order_by($params['order_by'],$params['direction']); }
		
		$query = $this->db->get('users');
		//my_var_dump($this->db->last_query());
		return $query;
		
	}
	
	public function insert($data)
	{
		$data['date_created'] = date('Y-m-d H:i:s');
		$data['date_updated'] = date('Y-m-d H:i:s');

        $roles = [];
        if(isset($data['roles']))
        {
            $roles = $data['roles'];
            unset($data['roles']);
        }

        $rights = [];
        if(isset($data['rights']))
        {
            $rights = $data['rights'];
            unset($data['rights']);
        }

		if($this->db->insert('users', $data))
		{
            //my_var_dump($this->db->last_query());
			$user_id =  $this->db->insert_id();

            foreach($roles as $role_id)
            {
                $insert_query = $this->db->insert_string('users_roles', ['user_id' => $user_id,'role_id' => $role_id]);
                $insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO',$insert_query);
                $this->db->query($insert_query);
            }

            foreach($rights as $right_id)
            {
                $insert_query = $this->db->insert_string('users_rights', ['user_id' => $user_id,'right_id' => $right_id]);
                $insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO',$insert_query);
                $this->db->query($insert_query);
            }

            return $user_id;
		}
        //my_var_dump($this->db->last_query());
		return false;
	}

	public function update($id,$data)
	{
		$data['date_updated'] = date('Y-m-d H:i:s');

        $roles = [];
        if(isset($data['roles']))
        {
            $this->db->delete('users_roles', ['user_id' => $id]);

            $roles = $data['roles'];
            unset($data['roles']);
        }
        foreach($roles as $role_id)
        {
            $insert_query = $this->db->insert_string('users_roles', ['user_id' => $id,'role_id' => $role_id]);
            $insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO',$insert_query);
            $this->db->query($insert_query);
        }

        $rights = [];
        if(isset($data['rights']))
        {
            $this->db->delete('users_rights', ['user_id' => $id]);

            $rights = $data['rights'];
            unset($data['rights']);
        }
        foreach($rights as $right_id)
        {
            $insert_query = $this->db->insert_string('users_rights', ['user_id' => $id,'right_id' => $right_id]);
            $insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO',$insert_query);
            $this->db->query($insert_query);
        }

		$this->db->where('user_id', $id);
		return $this->db->update('users',$data);
		//my_var_dump($this->db->last_query());
	}
	
	public function delete($id)
	{
		$entity = self::get_user_by_id($id);
		if($entity->image != '')
		{
			$upload_path = './uploads/users/';
			delete_file($upload_path.$entity->image);
			$_SESSION['msg_error'][] = $entity->image.' file deleted!';
		}

		return $this->db->delete('users', ['user_id' => $id]);
	}

	public function get_user_roles($id)
    {
        # Fetch roles of this user
        $query = $this->db->get_where('view_users_roles',['user_id'=>$id]);
        $roles = [];
        foreach($query->result() as $row)
        {
            $roles[$row->role_id] = $row->role;
        }
        return $roles;
    }

    public function get_user_rights($id)
    {
        $sql = "SELECT * FROM rights WHERE right_id IN(
                SELECT right_id FROM roles_rights WHERE role_id IN(
                SELECT role_id FROM users_roles WHERE user_id=$id))
                
                UNION
                
                SELECT * FROM rights WHERE right_id IN(
                SELECT right_id FROM users_rights WHERE user_id=$id)
                ORDER BY right_id";
        $query = $this->db->query($sql);
        $rights = [];
        foreach($query->result() as $row)
        {
            $rights[$row->right_id] = $row->right;
        }
        return $rights;
    }

    public function get_user_special_rights($id)
    {
        $sql = "SELECT * FROM rights WHERE right_id IN(
                SELECT right_id FROM users_rights WHERE user_id=$id)
                ORDER BY right_id";
        $query = $this->db->query($sql);
        $rights = [];
        foreach($query->result() as $row)
        {
            $rights[$row->right_id] = $row->right;
        }
        return $rights;
    }

	public function get_user_by_id($id)
	{
		$query = $this->db->get_where('users',['user_id'=>$id]);
        if($query->num_rows())
        {
            $row = $query->row();
            $row->roles = self::get_user_roles($id);
            $row->rights = self::get_user_rights($id);
            $row->image_url = $row->image ? base_url().'uploads/users/'.$row->image : '';
            return $row;
        }
		return false;
	}

    public function get_user_by_md5_id($id)
    {
        $this->db->where('MD5(user_id)', "'$id'", FALSE);
        $query = $this->db->get('users');
        if($query->num_rows())
        {
            $row = $query->row();
            $row->roles = self::get_user_roles($row->user_id);
            $row->rights = self::get_user_rights($row->user_id);
            $row->image_url = $row->image ? base_url().'uploads/users/'.$row->image : '';
            return $row;
        }

        return false;
    }

	public function get_profile($id)
	{
		$query = $this->db->get_where('users',['user_id'=>$id]);
		return $query->num_rows() ? $query->row() : false;
	}


	public function email_already_exists($email, $id=false)
	{
		if($id > 0) $this->db->where('user_id !=',$id);
		$query = $this->db->get_where('users',['email'=>$email]);
		return $query->num_rows() ? $query->row() : false;
	}
	
	public function matched_with_current_password($id,$current_password)
	{
		$query = $this->db->get_where('users',['user_id'=>$id, 'password'=>md5($current_password)]);
		return $query->num_rows() ? $query->row() : false;
	}
	
	public function check_login($login)
	{
	    // try email and password
		$query = $this->db->get_where('users',['email'=>$login['email'], 'password' => md5($login['password'])]);
		if($query->num_rows())
        {
            $row = $query->row();
            $row->roles = self::get_user_roles($row->user_id);
            $row->rights = self::get_user_rights($row->user_id);
            $row->image_url = $row->image ? base_url().'uploads/users/'.$row->image : '';
            return $row;
        }
		return false;
	}
	
	public function reset_duplicate_device_id($device_id)
	{
		$this->db->where('device_id',$device_id);
		$this->db->update('users',['device_id'=>'']);
	}
	
	public function verify_token($authtoken)
	{
		$this->db->where('authtoken',$authtoken);
		$this->db->where('is_activated',1);
		$this->db->where('deleted',0);
		return $this->db->get('users');
	}

	public function get_user_by_token($authtoken)
	{
		$this->db->where('authtoken',$authtoken);
		$this->db->where('is_activated',1);
		$this->db->where('deleted',0);
		$query = $this->db->get('users');

		if($query->num_rows())
        {
            $row = $query->row();
            $row->roles = self::get_user_roles($row->user_id);
            $row->rights = self::get_user_rights($row->user_id);
            $row->image_url = $row->image ? base_url().'uploads/users/'.$row->image : '';
            return $row;
        }
        return false;
	}

    public function get_rights($params = [], $count_result = false)
    {
        if(isset($params['select'])) { $this->db->select($params['select']); }
        if(isset($params['where_in'])) { $this->db->where_in('user_id',$params['where_in']); }
        if(isset($params['where_not_in'])) { $this->db->where_not_in('user_id',$params['where_not_in']); }

        if(isset($params['keyword']) and $params['keyword']!='')
        {
            $this->db->like('right', $params['keyword']);
        }

        # If true, count results and return it
        if($count_result)
        {
            $this->db->from('rights');
            $count = $this->db->count_all_results();
            return $count;
        }

        if(isset($params['limit'])) { $this->db->limit($params['limit'], $params['offset']); }
        if(isset($params['order_by'])){ $this->db->order_by($params['order_by'],$params['direction']); }

        $query = $this->db->get('rights');
        //my_var_dump($this->db->last_query());
        return $query;

    }

    public function insert_device($user_id,$device_id,$type,$version_number)
    {
        $sql = "INSERT IGNORE INTO users_devices SET user_id=$user_id,device_id='$device_id',type='$type',version='$version_number'";
        return $this->db->query($sql);
    }

    public function send_push_notification($user_id,$payload,$type)
    {
        $query = $this->db->get_where('users_devices',['user_id'=>$user_id,'type'=>$type]);
        foreach($query->result() as $row)
        {
            if($row->device_id)
            {
                $this->general_model->sendPushNotificationIOS($row->device_id,$payload);
            }
        }
    }

    public function get_facebook_user_from_token($fb_token)
    {
        $user= new stdClass();

        $fb = new Facebook\Facebook([
            'app_id' => '1927943484122171',
            'app_secret' => '793bb600169c4abeedb80f094714b0a7',
            'default_graph_version' => 'v2.11',
        ]);

        try {
            $response = $fb->get('/me?fields=id,name,email', $fb_token);
            $graph_user = $response->getGraphUser();
            $user->id = $graph_user->getId();
            $user->name = $graph_user->getName();
            $user->email = isset($graph_user['email'])?$graph_user->getEmail():"$user->id@facebook.com";
            $user->image = "https://graph.facebook.com/" . $graph_user->getId() ."/picture?type=large";
            //$user->dob = $graph_user->getBirthday()->format('Y-m-d');
            //$user->gender = $graph_user->getGender();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            $user->error = $e->getMessage();
            $user->error_code = REST_Controller::HTTP_UNAUTHORIZED;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            $user->error = $e->getMessage();
            $user->error_code = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
        }catch(Throwable $e) {
            $user->error = $e->getMessage();
            $user->error_code = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
        }
        return $user;
    }

    public function facebook_id_already_exists($id)
    {
        $query = $this->db->get_where('users',['facebook_id'=>$id]);
        return $query->num_rows() ? $query->row() : false;
    }

    public function check_login_by_facebook_id($id)
    {
        $this->db->select('*');
        $query = $this->db->get_where('users',['facebook_id'=>$id]);
        if($query->num_rows())
        {
            $row = $query->row();
            $row->image_url = $row->image ? base_url().'uploads/users/'.$row->image : '';
            if($row->image == '' and $row->facebook_id != '')
            {
                $row->image_url = $row->image_facebook;
            }

            return $row;
        }
        return false;
    }
}


