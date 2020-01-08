<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Profiler Sections
| -------------------------------------------------------------------------
| This file lets you determine whether or not various sections of Profiler
| data are displayed when the Profiler is enabled.
| Please see the user guide for info:
|
|	http://codeigniter.com/user_guide/general/profiling.html
|
*/

if(isset($_SESSION['admin']))
{
	$config['full_tag_open'] = "<ul class='pagination pagination-green margin-bottom-10'>";
	$config['full_tag_close'] ="</ul>";
	$config['num_tag_open'] = '<li>';
	$config['num_tag_close'] = '</li>';
	$config['cur_tag_open'] = "<li class='disabled'><li class='active'><a href='#'>";
	$config['cur_tag_close'] = "<span class='sr-only'></span></a></li>";
	$config['next_tag_open'] = "<li>";
	$config['next_tagl_close'] = "</li>";
	$config['prev_tag_open'] = "<li>";
	$config['prev_tagl_close'] = "</li>";
	$config['first_tag_open'] = "<li>";
	$config['first_tagl_close'] = "</li>";
	$config['last_tag_open'] = "<li>";
	$config['last_tagl_close'] = "</li>";
	$config['prev_link'] = '<i class="ti-angle-left"></i>';
	$config['next_link'] = '<i class="ti-angle-right"></i>';
	$config['page_query_string'] = TRUE;
	$config['uri_segment'] = 3;
	$config['num_links'] = 2;
	$config['first_link'] = false;
	$config['last_link'] = false;
}
else
{
	$config['page_query_string'] = TRUE;
	$config['uri_segment'] = 3;
	$config['num_links'] = 2;
	$config['full_tag_open'] = '<ul>';
	$config['full_tag_close'] = '</ul>';
    $config['num_tag_open'] = '<li>';
    $config['num_tag_close'] = '</li>';
	$config['cur_tag_open'] = '<li class="active">';
	$config['cur_tag_close'] = '</li>';
	
	$config['attributes'] = array('class' => 'numeric');
	
	$config['prev_link'] = 'PREV';
	$config['prev_tag_open'] = '<li class="next removeborder">';
	$config['prev_tag_close'] = '</li>';
	
	$config['next_link'] = 'NEXT';
	$config['next_tag_open'] = '<li class="next removeborder">';
	$config['next_tag_close'] = '</li>';
	
	$config['first_link'] = false;
	$config['last_link'] = false;
}

