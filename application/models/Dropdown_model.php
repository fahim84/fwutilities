<?php
class Dropdown_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

    public function get($params = array(), $count_result = false)
    {
        if(isset($params['dropdown'])) { $this->db->where('dropdown', $params['dropdown']); }
        if(isset($params['select'])) { $this->db->select($params['select']); }
        if(isset($params['group_by'])) { $this->db->group_by($params['group_by']); }

        if(isset($params['keyword']) and $params['keyword']!='')
        {
            $this->db->like('dropdown', $params['keyword']);
        }

        # If true, count results and return it
        if($count_result)
        {
            $this->db->from('dropdowns');
            $count = $this->db->count_all_results();
            return $count;
        }

        if(isset($params['limit'])) { $this->db->limit($params['limit'], $params['offset']); }
        if(isset($params['order_by'])){ $this->db->order_by($params['order_by'],$params['direction']); }

        $query = $this->db->get('dropdowns');
        //my_var_dump($this->db->last_query());
        return $query;
    }

    public function get_all_as_array()
    {
        $dropdowns = [];
        $query = $this->db->get('dropdowns');
        foreach ($query->result() as $row)
        {
            $dropdowns[$row->dropdown][$row->key] = $row->value;
        }

        return $dropdowns;
    }

    public function insert($data)
    {
        if($this->db->insert('dropdowns', $data))
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
        $this->db->where('id', $id);
        return $this->db->update('dropdowns',$data);
        //my_var_dump($this->db->last_query());
    }

    public function delete($id)
    {
        return $this->db->delete('dropdowns', ['id' => $id]);
    }

    public function get_dropdown_by_id($id)
    {
        $query = $this->db->get_where('dropdowns',['id'=>$id]);
        if($query->num_rows())
        {
            $row = $query->row();
            return $row;
        }
        return false;
    }

}


