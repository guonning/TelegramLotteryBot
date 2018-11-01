<?php
$url = $_GET['url'];

$file = file_get_contents($url);

$i = count(explode('.',$url)) - 1;
$file_type = explode('.',$url)[$i];

$filename = md5(microtime(true)).".$file_type";

file_put_contents($filename,$file);

// http://www.runoob.com/w3cnote/php-image2base64.html
if($fp = fopen($filename,"rb", 0)) 
{ 
    $gambar = fread($fp,filesize($filename)); 
    fclose($fp); 
    $base64 = chunk_split(base64_encode($gambar)); 

    // 输出
    echo $base64; 
    //echo 'data:image/jpg;base64,'.$base64;
} 
