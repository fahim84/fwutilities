<?php
phpinfo();

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