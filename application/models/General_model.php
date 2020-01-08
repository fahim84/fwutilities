<?php
class General_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

    public function get_cities($params = array(), $count_result = false)
    {
        if(isset($params['province'])) { $this->db->where('province', $params['province']); }
        if(isset($params['city'])) { $this->db->where('city', $params['city']); }
        if(isset($params['area'])) { $this->db->where('area', $params['area']); }
        if(isset($params['select'])) { $this->db->select($params['select']); }
        if(isset($params['group_by'])) { $this->db->group_by($params['group_by']); }

        if(isset($params['keyword']) and $params['keyword']!='')
        {
            $this->db->like('city', $params['keyword']);
        }

        # If true, count results and return it
        if($count_result)
        {
            $this->db->from('cities');
            $count = $this->db->count_all_results();
            return $count;
        }

        if(isset($params['limit'])) { $this->db->limit($params['limit'], $params['offset']); }
        if(isset($params['order_by'])){ $this->db->order_by($params['order_by'],$params['direction']); }

        $query = $this->db->get('cities');
        //my_var_dump($this->db->last_query());
        return $query;
    }

    function sendPushNotificationIOS($device_id,$notification)
    {
        // Put your device token here (without spaces):
        //$deviceToken = '0f744707bebcf74f9b7c25d48e3358945f6aa01da5ddb387462c7eaf61bbad78';
        //echo 'device ID : ' . $deviceToken . ' ';
        // Put your private key's passphrase here:
        $passphrase = 'amjad';

        // Put your alert message here:
        $message = substr($notification->message,0,150);
        ////////////////////////////////////////////////////////////////////////////////


        $ctx = stream_context_create();
        //stream_context_set_option($ctx, 'ssl', 'local_cert', 'pushcert.pem'); // development
        //$remote_socket_url = 'ssl://gateway.sandbox.push.apple.com:2195'; // development
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'pushcert_live.pem'); // live
        $remote_socket_url = 'ssl://gateway.push.apple.com:2195'; // live

        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);


        // Open a connection to the APNS server
        $fp = stream_socket_client(
            $remote_socket_url, $err,
            $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        //if (!$fp)
        //exit("Failed to connect: $err $errstr" . PHP_EOL);

        //echo ' Connected to APNS' . PHP_EOL;

        // Create the payload body
        $body['aps'] = array(
            'badge' => 9,
            'alert' => array("title"=>$notification->title,'body'=>$message),
            'sound' => 'default'
        );
        //extra variables if any
        if($notification)
        {
            $body['notification'] = $notification;
        }
        //$body['auto'] = $auto;

        // Encode the payload as JSON
        $payload = json_encode($body);

        // Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $device_id) . pack('n', strlen($payload)) . $payload;

        // Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));
        /*
        if (!$result)
            echo 'Message not delivered' . PHP_EOL;
        else
            echo $message . ' Message successfully delivered' . PHP_EOL;
        */
        // Close the connection to the server
        fclose($fp);
    }
    function sendPushNotificationAndroid($device_id,$notification)
    {
        $to_user_id = $notification->to_user_id;

        $notification = json_encode($notification);
        $url = "https://fcm.googleapis.com/fcm/send";
        $fields = array
        (
            'registration_ids' => [$device_id],
            'data' => array("notification"=>$notification,
                'notification_count' => self::get_badge_notification($to_user_id)

            ),
            //'notification_count' => self::get_badge_notification($notification->to_user_id),
        );


        $headers = array(
            'Authorization: key= AIzaSyAzpmT6csNJTq58Q5f9Ht0Chc1Zl9ESzzc',
            'Content-Type: application/json'
        );

        //open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        //execute post
        $result = curl_exec($ch);

        if ($result === FALSE)
        {
            die('Curl failed: ' . curl_error($ch));
        }

        // Close connection
        curl_close($ch);

        //file_put_contents("andorid_push.txt", self::get_badge_notification($notification->to_user_id));
        return $result;
    }

    public function get_latest_version()
    {
        $this->db->limit(1, 0);
        $this->db->where('status',1);
        $this->db->order_by('version_number','DESC');
        $query = $this->db->get('settings');
        $row = $query->row();
        return $row;
    }
}


