<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class CSVReader {

    var $fields;            /** columns names retrieved after parsing */ 
    var $separator = ';';    /** separator used to explode each line */
    var $enclosure = '"';    /** enclosure used to decorate each field */
    var $max_row_size = 4096;    /** maximum row size to be used for decoding */

    function parse_file($p_Filepath) {

        $file = fopen($p_Filepath, 'r');
        $this->fields = fgetcsv($file, $this->max_row_size, $this->separator, $this->enclosure);
        //$keys_values = explode(',',$this->fields[0]);
		$keys_values = str_getcsv($this->fields[0]);

        $content    =   array();
        $keys   =   $this->escape_string($keys_values);

        $i  =   1;
        while( ($row = fgetcsv($file, $this->max_row_size, $this->separator, $this->enclosure)) != false ) 
		{
            if( $row != null ) 
			{ 
                //$values =   explode(',',$row[0]);
				$values = str_getcsv($row[0]);
                if(count($keys) == count($values))
				{
                    $arr    =   array();
                    $new_values =   array();
                    $new_values =   $this->escape_string($values);
                    for($j=0;$j<count($keys);$j++)
					{
                        if($keys[$j] != ""){
                            $arr[$keys[$j]] =   $new_values[$j];
							
							if($keys[$j] == 'conditions' and $new_values[$j])
							{
								$arr[$keys[$j]] = explode('--',$new_values[$j]);
							}
							if($keys[$j] == 'procedures' and $new_values[$j])
							{
								$arr[$keys[$j]] = explode('--',$new_values[$j]);
							}
							if($keys[$j] == 'specialities' and $new_values[$j])
							{
								$arr[$keys[$j]] = explode('--',$new_values[$j]);
							}
							if($keys[$j] == 'timings' and $new_values[$j])
							{
								$normal_timings = [];
								$chunks = explode('|',$new_values[$j]);
								foreach($chunks as $chunk)
								{
									if($chunk)
									{
										$normal_timings[] = explode('--',$chunk);
									}
								}
								$arr['timings'] = $normal_timings;
							}
                        }
                    }
					
					$arr['status'] = 'OK';
                    $content[$i]=   $arr;
                    
                }
				else
				{
					$content[$i]=  ['status' => 'Error'];
				}
				$i++;
            }
        }
        fclose($file);
        return $content;
    }
	
	function parse_file2($p_Filepath) {

        $file = fopen($p_Filepath, 'r');
        $this->fields = fgetcsv($file, $this->max_row_size, $this->separator, $this->enclosure);
        //$keys_values = explode(',',$this->fields[0]);
		$keys_values = str_getcsv($this->fields[0]);

        $content    =   array();
        $keys   =   $this->escape_string($keys_values);

        $i  =   1;
        while( ($row = fgetcsv($file, $this->max_row_size, $this->separator, $this->enclosure)) != false ) {            
            if( $row != null ) { // skip empty lines
                $values =   explode(',',$row[0]);
				//$values = str_getcsv($row[0]);
                if(count($keys) == count($values)){
                    $arr    =   array();
                    $new_values =   array();
                    $new_values =   $this->escape_string($values);
                    for($j=0;$j<count($keys);$j++){
                        if($keys[$j] != ""){
                            $arr[$keys[$j]] =   $new_values[$j];
							
							
                        }
                    }

                    $content[$i]=   $arr;
                    $i++;
                }
            }
        }
        fclose($file);
        return $content;
    }

    function escape_string($data){
        $result =   array();
        foreach($data as $row){
            $result[]   =   str_replace('"', '',$row);
        }
        return $result;
    }   
}