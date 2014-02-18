<?php
   $file_to_compress = "index-.php"; //any type of file
   $gz_file_to_produce="testgz000.gz";

   $data = implode("", file($file_to_compress));
   $gzdata = gzencode($data, 9);
   $fp = fopen($gz_file_to_produce, "w");
   fwrite($fp, $gzdata);
   fclose($fp);
   echo 'ok';
?>