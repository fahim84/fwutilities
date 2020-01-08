<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function my_var_dump($string)
{
    $http_host = isset($_SERVER['HTTP_HOST']) ? true : false;
    $line_break = isset($_SERVER['HTTP_HOST']) ? "<br>" : "\n";
    $pre_tag_open = isset($_SERVER['HTTP_HOST']) ? "<pre>" : "\n";
    $pre_tag_close = isset($_SERVER['HTTP_HOST']) ? "</pre>" : "\n";

	if(is_array($string) or is_object($string))
	{
		echo $pre_tag_open;
		print_r($string);
		echo $pre_tag_close;
	}
	elseif(is_string($string))
	{
		echo $string.$line_break;
	}
	else
	{
		echo $pre_tag_open;
		var_dump($string);
		echo $pre_tag_close;
	}
}


function delete_file($path_and_filename)
{
	if(file_exists($path_and_filename))
	{
		if(is_file($path_and_filename))
		{
			if(unlink($path_and_filename))
			{
				return true;
			}
			else return false;
		}else return false;
	}else return false;
}

function isValidEmail($email)
{
	//return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email);
	
	if(!preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $email)) 
	{
    	//$msg = 'email is not valid';
		return false;
	}
	else
	{
		return true;
	}
}

function isValidURL($url)
{
	return preg_match('|^http(s)?://[a-z0-9-]+(\.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}
function addhttp($url) 
{
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    return $url;
}
function my_redirect($url,$target='')
{
	echo "<script>window.parent.location=\"".$url."\"</script>";
}

function display_success_message()
{
	if(isset($_SESSION['msg_success']))
	{
		$errors	=	array();
		$numarray	=	array();
		$strarray	=	array();
		$string ="";
		$string2 ="";
		if(is_array($_SESSION['msg_success']))
		{
			foreach($_SESSION['msg_success'] as $msgvalue)
			{
					$strarray[]	=	$msgvalue;
			}
			$string	.=	implode("<br>",$strarray);
		}
		else
		{
			$string	.=	$_SESSION['msg_success'];
		}

		unset($_SESSION['msg_success']);
		return "$string";
	}
	else
	{
		return "";
	}	
}
function display_error()
{
	if(isset($_SESSION['msg_error']))
	{
		$errors	=	array();
		$numarray	=	array();
		$strarray	=	array();
		$string ="";
		$string2 ="";
		if(is_array($_SESSION['msg_error']))
		{
			foreach($_SESSION['msg_error'] as $msgvalue)
			{
					$strarray[]	=	$msgvalue;
			}
			$string	.=	implode("<br>",$strarray);
		}
		else
		{
			$string	.=	$_SESSION['msg_error'];
		}

		unset($_SESSION['msg_error']);
		return "$string";
	}
	else
	{
		return "";
	}	
}

function make_table($data,$columns,$table_class = 'make_table',$tr_class = 'make_table_tr', $td_class = 'make_table_td',$default_value='&nbsp;')
{
	#################### REQUIRED INPUT ####################
	/*
	1. REQUIRED
	$data should be an array, and array key must be 
	an integer starting with 0 and must contain 
	further iteration in sequence. For example
	
	$data[0] = "any value";
	$data[1] = "any value";
	$data[2] = "any value";
	$data[3] = "any value";
	
	2. REQUIRED
	$columns must be a variable
	$columns must have integer value greater than 0
	*/
	#################### REQUIRED INPUT ####################

	$no_of_cells = count($data);
	$no_of_rows = ceil($no_of_cells/$columns);
	$no_of_total_cells = $columns*$no_of_rows;
	$extra_cells = $no_of_total_cells-$no_of_cells;
	
	#################### SUMMARY FOR DEBUGGING ####################
	#	echo "Number of columns: $columns<br>";
	#	echo "Number of rows: $no_of_rows<br>";
	#	echo "Number of data Cells: $no_of_cells<br>";
	#	echo "Number of Extra Cells: $extra_cells<br>";
	#	echo "Number of Total Cells: $no_of_total_cells<br>";
	#################### SUMMARY FOR DEBUGGING ####################
	
	$key = 0;	# THIS VARIABLE WILL BE INCREMENTED ON EARCH CELL

	$HTML = "<table class=\"$table_class\" >";
	for($i=0;$i<$no_of_rows;$i++)	# THIS LOOP WILL GENERATE TABLE ROWS
	{
		$HTML .= "<tr class=\"$tr_class\">";	# START TABLE ROW
		
		for($j=0;$j<$columns;$j++)	# THIS LOOP WILL GENERATE TABLE CELLS
		{
			if(isset($data[$key]))	# IF DATA CELL EXISTS
			{
				$HTML .= "<td class=\"$td_class\">";	# START TABLE CELL
				$HTML .= $data[$key];
				$HTML .= "</td>";	# END TABLE CELL
			}
			else
			{
				$HTML .= "<td class=\"$td_class\">";	# START TABLE CELL
				$HTML .= $default_value;	# $data[$key];
				$HTML .= "</td>";	# END TABLE CELL			
			}
			$key++;
		}
		
		$HTML .= "</tr>";	# END TABLE ROW
	}
	$HTML .= "</table>";
	
	
	#echo $HTML;
	return $HTML;
}

function handle_post_request_from_angularjs()
{
	if(isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
		$_POST = array_merge($_POST, (array) json_decode(trim(file_get_contents('php://input')), true));
	}
}

function resize_image2($url, $newWidth='', $newHeight='', $Base='')
{
	list($iw, $ih, $imageType) = getimagesize($url);
	$imageType = image_type_to_mime_type($imageType);
	
	switch($imageType)
	{
		case "image/gif":
			$image = imagecreatefromgif($url);
		break;
		
		case "image/pjpeg":
			$image = imagecreatefromjpeg($url);
		break;
		
		case "image/jpeg":
			$image = imagecreatefromjpeg($url);
		break;
		
		case "image/jpg":
			$image = imagecreatefromjpeg($url);
		break;
		
		case "image/png":
			$image = imagecreatefrompng($url);
		break;
		
		case "image/x-png":
			$image = imagecreatefrompng($url);
		break;
	}
	
	$orig_width = imagesx($image);
	$orig_height = imagesy($image);
	
	if($Base=='W')
	{
		$width = $newWidth;
		$height = (($orig_height * $newWidth) / $orig_width);
		$new_image = imagecreatetruecolor($newWidth, $height);
	}
	else if($Base=='H')
	{
		$width = (($orig_width * $newHeight) / $orig_height);
		$height = $newHeight;
		$new_image = imagecreatetruecolor($width, $newHeight);
	}

	// preserve transparency
	if($imageType == "image/x-png" or $imageType == "image/png" or $imageType == "image/gif"){
		imagecolortransparent($new_image, imagecolorallocatealpha($new_image, 0, 0, 0, 127));
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
	}

	imagecopyresized($new_image, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
	
	switch($imageType)
	{
		case "image/gif":
			imagegif($new_image, $url);
		break;
		
		case "image/pjpeg":
			imagejpeg($new_image, $url, 100);
		break;
		
		case "image/jpeg":
			imagejpeg($new_image, $url, 100);
		break;
		
		case "image/jpg":
			imagejpeg($new_image, $url, 100); 
		break;
		
		case "image/png":
			imagepng($new_image, $url);
		break;
		
		case "image/x-png":
			imagepng($new_image, $url);
		break;
	}
		
		
	
}

//You do not need to alter these functions
function get_height($image)
{
	$size = getimagesize($image);
	$height = $size[1];
	return $height;
}

//You do not need to alter these functions
function get_width($image)
{
	$size = getimagesize($image);
	$width = $size[0];
	return $width;
}

function get_format_date($un_formatted,$format = 'd M, Y' )
{
	$today = date('Y-m-d');
	$tomorrow = date('Y-m-d',strtotime($today.'+ 1 day '));
	$yesterday = date('Y-m-d',strtotime($today.'- 1 day '));

	$timestamp = strtotime($un_formatted);
	$standard_date = date('Y-m-d',$timestamp);

	if($standard_date == $today) return 'Today';
	if($standard_date == $tomorrow) return 'Tomorrow';
	if($standard_date == $yesterday) return 'Yesterday';

	$formatted_date = date($format,$timestamp);
	return $formatted_date;
}
function time_elapsed_string($datetime, $full = false) 
{
	$now = new DateTime;
	$ago = new DateTime($datetime);
	$diff = $now->diff($ago);        $diff->w = floor($diff->d / 7);
	$diff->d -= $diff->w * 7;        $string = array(
		'y' => 'year',
		'm' => 'month',
		'w' => 'week',
		'd' => 'day',
		'h' => 'hour',
		'i' => 'minute',
		's' => 'second',
	);
	foreach ($string as $k => &$v) {
		if ($diff->$k) {
			$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
		} else {
			unset($string[$k]);
		}
	}        if (!$full) $string = array_slice($string, 0, 1);
	return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function upload_image_using_base64($path,$base64_string)
{
	$file_name = md5(uniqid(mt_rand())).'.jpg';
	$img = file_put_contents($path.$file_name,base64_decode($base64_string)); 
	if($img !== false) 
	{
		return $file_name; 
	}
	return false;
}

function get_formatted_mobile_number($unformatted_mobile)
{
	return preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '($1) $2-$3', $unformatted_mobile);
}

function get_current_location()
{
    $client_ip_address = $_SERVER['REMOTE_ADDR'];
    //my_var_dump($_SERVER['REMOTE_ADDR']);

    $result = file_get_contents("http://freegeoip.net/json/$client_ip_address");
    $result = json_decode($result);

    return $result;
    my_var_dump($result->latitude);
    my_var_dump($result->longitude);
}
function get_user_location_time_zone()
{
    $client_ip_address = $_SERVER['REMOTE_ADDR'];
    //my_var_dump($_SERVER['REMOTE_ADDR']);

    $result = file_get_contents("http://freegeoip.net/json/$client_ip_address");
    $result = json_decode($result);
    return $result->time_zone ? $result->time_zone :'UTC';
}
function get_time_zone_offset_by_latitude_longitude($latitude,$longitude)
{
    $key = GOOGLE_MAP_KEY;
    $timestamp = time();
    //$api_end_point = "https://maps.googleapis.com/maps/api/timezone/json?timestamp=$timestamp&location=$latitude,$longitude&key=$key&sensor=false";
    $api_end_point = "https://maps.googleapis.com/maps/api/timezone/json?timestamp=$timestamp&location=$latitude,$longitude&sensor=false";
    //my_var_dump($api_end_point);
    $result = file_get_contents($api_end_point);
    $result = json_decode($result);
    //my_var_dump($result);
    if(isset($result->rawOffset))
    {
        $offset = $result->rawOffset/60/60;
        if($offset > 0)
        {
            $offset = sprintf("%02d", $offset);
            return '+'.$offset;
        }
        elseif($offset < 0)
        {
            $offset = sprintf("%03d", $offset);
            return $offset;
        }
        return 0;
    }
    return 0;
}
function stubhub_date_format($timestamp,$offset)
{
    $offset = $offset.'00';
    $php_formated_date = date('c',$timestamp);
    $last_six_characters = substr($php_formated_date,-6,6);
    $stubhub_formated_date = str_replace($last_six_characters,$offset,$php_formated_date);
    return $stubhub_formated_date;
}
function get_days_difference($datetime,$offset)
{
    date_default_timezone_set('UTC');
    //my_var_dump($datetime);
    $converted_timestamp = time()+($offset * 60 * 60);
    //my_var_dump("Current date on $offset offset: ".date('Y-m-d H:i:s',$converted_timestamp));

    $local_date = substr($datetime,0,10);
    //my_var_dump($local_date);
    $datetime1 = new DateTime($local_date);
    $datetime2 = new DateTime(date('Y-m-d',$converted_timestamp));
    $interval = $datetime1->diff($datetime2);
    $difference = (integer) $interval->format('%R%a');
    //my_var_dump($difference);
    return $difference;

}
function hex_to_rgb($hex_color)
{
    $rgb_color = [];
    list($rgb_color[0], $rgb_color[1], $rgb_color[2]) = sscanf($hex_color, "#%02x%02x%02x");
    return $rgb_color;
}

function distance_of_two_rgb_colors($col1,$col2) {
    $delta_r = $col1[0] - $col2[0];
    $delta_g = $col1[1] - $col2[1];
    $delta_b = $col1[2] - $col2[2];
    return $delta_r * $delta_r + $delta_g * $delta_g + $delta_b * $delta_b;
}

function are_colors_matching($hex_color1,$hex_color2,$distance=60)
{
    $rgb_color1 = hex_to_rgb($hex_color1);
    $rgb_color2 = hex_to_rgb($hex_color2);

    $red_distance = abs($rgb_color1[0] - $rgb_color2[0]);
    $green_distance = abs($rgb_color1[1] - $rgb_color2[1]);
    $blue_distance = abs($rgb_color1[2] - $rgb_color2[2]);

    //my_var_dump('Red: '.$red_distance);
    //my_var_dump('Green: '.$green_distance);
    //my_var_dump('Blue: '.$blue_distance);
    if( ($red_distance >=0 and $red_distance <= $distance) and ($green_distance >=0 and $green_distance <= $distance) and ($blue_distance >=0 and $blue_distance <= $distance) )
    {
        return 1;
    }
    return 0;

}

function custom_sort($a,$b) {
    return $a->weight<$b->weight;
}