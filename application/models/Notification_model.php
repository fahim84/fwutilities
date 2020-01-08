<?php
class Notification_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function get($params = array(), $count_result = false)
	{
		if(isset($params['to_user_id'])) { $this->db->where('to_user_id', $params['to_user_id']); }
		if(isset($params['is_read'])) { $this->db->where('is_read', $params['is_read']); }

		if(isset($params['group_by'])) { $this->db->group_by($params['group_by']); }

		# If true, count results and return it
		if($count_result)
		{
			$this->db->from('notifications');
			$count = $this->db->count_all_results();
			return $count;
		}
		
		if(isset($params['limit'])) { $this->db->limit($params['limit'], $params['offset']); }
		if(isset($params['order_by'])){ $this->db->order_by($params['order_by'],$params['direction']); }
		
		$query = $this->db->get('view_notifications');
		//my_var_dump($this->db->last_query());
		return $query;
		
	}

	public function insert($data)
	{
        $data['date_created'] = date('Y-m-d H:i:s');
        $data['date_updated'] = date('Y-m-d H:i:s');

		if($this->db->insert('notifications', $data))
		{
			$id = $this->db->insert_id();

			return $id;
		}
		return false;
	}
	
	public function update($id,$data)
	{
        $data['date_updated'] = date('Y-m-d H:i:s');
		$this->db->where('notification_id', $id);
		return $this->db->update('notifications',$data);
	}
	
	public function delete($id)
	{
		return $this->db->delete('notifications', array('notification_id' => $id));
	}

	public function get_notification_by_id($id)
	{
		$query = $this->db->get_where('view_notifications',array('notification_id'=>$id));
		if($query->num_rows())
        {
            $row = $query->row();
            $row->logged_in_user_image_url = $row->logged_in_user_image ? base_url().'uploads/users/'.$row->logged_in_user_image : '';
            return $row;
        }
		return false;
	}

    function sendPushNotificationIOS($device_id,$notification)
    {
        $passphrase = 'yahooz';

        // Put your alert message here:
        $message = substr($notification->message,0,150);
        ////////////////////////////////////////////////////////////////////////////////

        $environment = $this->db->get_where('app_settings',['key' => 'PUSH_NOTIFICATION_IOS_ENVIRONMENT'])->row()->value;

        //my_var_dump($this->db->last_query());
        //my_var_dump($environment);
        if($environment == 'development')
        {
            //my_var_dump($environment);
            $push_notification_file = 'ZoyaDevPush.pem';
            $remote_socket_url = 'ssl://gateway.sandbox.push.apple.com:2195';
        }
        else
        {
            //my_var_dump($environment);
            $push_notification_file = 'ZoyaLivePush.pem';
            $remote_socket_url = 'ssl://gateway.push.apple.com:2195';
        }

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'assets/'.$push_notification_file);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);


        // Open a connection to the APNS server
        $fp = stream_socket_client(
            $remote_socket_url, $err,
            $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);


        // Create the payload body
        $body['aps'] = array(
            'alert' => array("title"=>$notification->expert_name,'body'=>$message),
            'sound' => 'default'
        );
        //extra variables if any
        if($notification)
        {
            $body['notification'] = $notification;
        }

        // Encode the payload as JSON
        $payload = json_encode($body);

        // Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $device_id) . pack('n', strlen($payload)) . $payload;

        // Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));

        // Close the connection to the server
        fclose($fp);
    }

    function sendPushNotificationAndroid($device_id,$notification)
    {
        //$notification = json_encode($notification);

        $fields = array
        (
            'registration_ids' => [$device_id],
            'data' => $notification
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($fields),
            CURLOPT_HTTPHEADER => array(
                "Authorization: key=AIzaSyBQqReQO9rz0RLxBYqen-AwVyFTkQ9cVug",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            my_var_dump("cURL Error #:" . $err);
        } else {
            my_var_dump($response);
        }

    }
}


