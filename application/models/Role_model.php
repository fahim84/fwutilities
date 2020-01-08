<?php
class Role_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function get($params = [], $count_result = false)
	{
		if(isset($params['select'])) { $this->db->select($params['select']); }
		if(isset($params['where_in'])) { $this->db->where_in('role_id',$params['where_in']); }
		if(isset($params['where_not_in'])) { $this->db->where_not_in('role_id',$params['where_not_in']); }

		if(isset($params['keyword']) and $params['keyword']!='')
		{
			$this->db->like('role', $params['keyword']);
		}
		
		# If true, count results and return it
		if($count_result)
		{
			$this->db->from('roles');
			$count = $this->db->count_all_results();
			return $count;
		}
		
		if(isset($params['limit'])) { $this->db->limit($params['limit'], $params['offset']); }
		if(isset($params['order_by'])){ $this->db->order_by($params['order_by'],$params['direction']); }
		
		$query = $this->db->get('roles');
		//my_var_dump($this->db->last_query());
		return $query;
		
	}
	
	public function insert($data)
	{
		if($this->db->insert('roles', $data))
		{
            //my_var_dump($this->db->last_query());
			$id =  $this->db->insert_id();

            return $id;
		}
        //my_var_dump($this->db->last_query());
		return false;
	}

	public function update($id,$data)
	{
		$this->db->where('role_id', $id);
		return $this->db->update('roles',$data);
		//my_var_dump($this->db->last_query());
	}
	
	public function delete($id)
	{
		return $this->db->delete('roles', ['role_id' => $id]);
	}

	public function get_role_by_id($id)
	{
		$query = $this->db->get_where('roles',['role_id'=>$id]);
        if($query->num_rows())
        {
            return $query->row();
        }
		return false;
	}

	public function role_already_exists($role, $id=false)
	{
		if($id > 0) $this->db->where('role_id !=',$id);
		$query = $this->db->get_where('roles',['role'=>$role]);
		return $query->num_rows() ? $query->row() : false;
	}
}


