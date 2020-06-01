<?php
//require_once 'vendor/autoload.php';
class Image_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function get($params = [], $count_result = false)
	{
		if(isset($params['keyword']) and $params['keyword']!='')
		{
			$this->db->like('image', $params['keyword']);
			$this->db->or_like('width', $params['keyword']);
			$this->db->or_like('height', $params['keyword']);
			$this->db->or_like('extension', $params['keyword']);
			$this->db->or_like('image_type', $params['keyword']);
			$this->db->or_like('file_type', $params['keyword']);
		}
		
		# If true, count results and return it
		if($count_result)
		{
			$this->db->from('images');
			$count = $this->db->count_all_results();
			return $count;
		}
		
		if(isset($params['limit'])) { $this->db->limit($params['limit'], $params['offset']); }
		if(isset($params['order_by'])){ $this->db->order_by($params['order_by'],$params['direction']); }
		
		$query = $this->db->get('images');
		//my_var_dump($this->db->last_query());
		return $query;
		
	}
	
	public function insert($data)
	{
		$data['created_at'] = date('Y-m-d H:i:s');
		$data['updated_at'] = date('Y-m-d H:i:s');

		if($this->db->insert('images', $data))
		{
			$id =  $this->db->insert_id();
            return $id;
		}
		return false;
	}

	public function update($id,$data)
	{
		$data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->where('image_id', $id);
		return $this->db->update('images',$data);
	}
	
	public function delete($id)
	{
		$entity = self::get_image_by_id($id);
		if($entity->image != '')
		{
			$upload_path = './uploads/images/';
			delete_file($upload_path.$entity->image);
			$_SESSION['msg_error'][] = $entity->image.' file deleted!';
		}
        if($entity->image2 != '')
        {
            $upload_path = './uploads/images/';
            delete_file($upload_path.$entity->image2);
            $_SESSION['msg_error'][] = $entity->image2.' file deleted!';
        }
        if($entity->modified_image != '')
        {
            $upload_path = './uploads/images/';
            delete_file($upload_path.$entity->modified_image);
            $_SESSION['msg_error'][] = $entity->modified_image.' file deleted!';
        }

		return $this->db->delete('images', ['image_id' => $id]);
	}

	public function get_image_by_id($id)
	{
		$query = $this->db->get_where('images',['image_id'=>$id]);
        if($query->num_rows())
        {
            $row = $query->row();
            $row->image_url = $row->image ? base_url().'uploads/images/'.$row->image : '';
            $row->image2_url = $row->image2 ? base_url().'uploads/images/'.$row->image2 : '';
            $row->modified_image_url = $row->modified_image ? base_url().'uploads/images/'.$row->modified_image : '';
            $row->location_url = ($row->latitude or $row->longitude) ? "http://www.google.com/maps/place/$row->latitude,$row->longitude" : '';
            return $row;
        }
		return false;
	}
}


