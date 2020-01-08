<?php
class Template_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function get($params = [], $count_result = false)
	{
		if(isset($params['select'])) { $this->db->select($params['select']); }
		if(isset($params['template_id'])) { $this->db->where('template_id',$params['template_id']); }
		if(isset($params['where_in'])) { $this->db->where_in('template_id',$params['where_in']); }
		if(isset($params['where_not_in'])) { $this->db->where_not_in('template_id',$params['where_not_in']); }

		if(isset($params['keyword']) and $params['keyword']!='')
		{
			$this->db->like('title', $params['keyword']);
		}
		
		# If true, count results and return it
		if($count_result)
		{
			$this->db->from('templates');
			$count = $this->db->count_all_results();
			return $count;
		}
		
		if(isset($params['limit'])) { $this->db->limit($params['limit'], $params['offset']); }
		if(isset($params['order_by'])){ $this->db->order_by($params['order_by'],$params['direction']); }
		
		$query = $this->db->get('templates');
		//my_var_dump($this->db->last_query());
		return $query;
	}
	
	public function insert($data)
	{
		if($this->db->insert('templates', $data))
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
		$this->db->where('template_id', $id);
		return $this->db->update('templates',$data);
		//my_var_dump($this->db->last_query());
	}
	
	public function delete($id)
	{
		return $this->db->delete('templates', ['template_id' => $id]);
	}

	public function get_template_by_id($id)
	{
		$query = $this->db->get_where('templates',['template_id'=>$id,'status' => 1]);
        if($query->num_rows())
        {
            return $query->row();
        }
		return false;
	}

	public function template_already_exists($title, $id=false)
	{
		if($id > 0) $this->db->where('template_id !=',$id);
		$query = $this->db->get_where('templates',['title'=>$title]);
		return $query->num_rows() ? $query->row() : false;
	}
}


