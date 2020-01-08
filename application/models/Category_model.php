<?php
class Category_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function get($params = array(), $count_result = false)
	{
		if(isset($params['category_id'])) { $this->db->where('category_id', $params['category_id']); }
		if(isset($params['status'])) { $this->db->where('status', $params['status']); }
		if(isset($params['select'])) { $this->db->select($params['select']); }
		if(isset($params['where_in'])) { $this->db->where_in('category_id',$params['where_in']); }
        if(isset($params['where_not_in'])) { $this->db->where_not_in('category_id',$params['where_not_in']); }
        if(isset($params['newsfeed_ids']) and is_array($params['newsfeed_ids']) and count($params['newsfeed_ids']))
        {
            $newsfeed_ids = implode(',',$params['newsfeed_ids']);
            $this->db->where_in('category_id',"SELECT category_id FROM newsfeeds_categories WHERE newsfeed_id IN($newsfeed_ids)",false);
        }

        if(isset($params['keyword']) and $params['keyword']!='')
		{
			$this->db->like('category', $params['keyword']);
		}

		# If true, count results and return it
		if($count_result)
		{
			$this->db->from('categories');
			$count = $this->db->count_all_results();
			return $count;
		}
		
		if(isset($params['limit'])) { $this->db->limit($params['limit'], $params['offset']); }
		if(isset($params['order_by'])){ $this->db->order_by($params['order_by'],$params['direction']); }
		
		$query = $this->db->get('view_categories');
		//my_var_dump($this->db->last_query());
		return $query;
	}
	
	public function insert($data)
	{
		if($this->db->insert('categories', $data))
		{
            $id = $this->db->insert_id();

            return $id;
		}
        //my_var_dump($this->db->last_query());
		return false;
	}

	public function update($id,$data)
	{
		$this->db->where('category_id', $id);
		return $this->db->update('categories',$data);
		//my_var_dump($this->db->last_query());
	}
	
	public function delete($id)
	{
        $entity = self::get_category_by_id($id);
		return $this->db->delete('categories', ['category_id' => $id]);
	}
	
	public function get_category_by_id($id)
	{
		$query = $this->db->get_where('view_categories',['category_id'=>$id]);
        if($query->num_rows())
        {
            $row = $query->row();
            return $row;
        }
		return false;
	}

    public function category_already_exists($category, $id=false)
    {
        if($id > 0) $this->db->where('category_id !=',$id);
        $query = $this->db->get_where('view_categories',['category'=>$category]);
        return $query->num_rows() ? $query->row() : false;
    }

}


