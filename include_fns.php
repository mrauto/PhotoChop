<?php

if (session_id() == "") {
	@session_start();
}
function db_connect()
{
	include 'dbinfo.php';
	$result = @mysql_pconnect($host, $dbuser, $dbpass); 
	if (!$result)		return false;
	if (!@mysql_select_db($dbname))		return false;
	return $result;
}

// ***************************** BITS ************************************************
/*
 ******* bitsign ***   superseded by bitsign_rect
 */
function bitsign($filename, $base10=true) {
	// if $base10 is true or omitted, the output will be in base 10 aka decimal
	$bw = 4;
	$src_image = imagecreatefromjpeg($filename); 	// read from file
	imagefilter($src_image, IMG_FILTER_GRAYSCALE);	// turn to grayscale
	// Resample: crop and resize 
	list($w, $h) = getimagesize($filename);
	$dst_image = imagecreatetruecolor($bw, $bw);
	imagecopyresampled ($dst_image, $src_image, 0, 0, 0, 0, $bw, $bw, $w, $h);
	$num='';
	for ($j=0; $j<$bw; $j++) {	// line by line
		for ($i=0; $i<$bw; $i++) {
			$start_x = $i;
			$start_y = $j;
			$rgb = imagecolorat($dst_image, $start_x, $start_y);
			$b = $rgb & 0xFF;
			$num.= ($b > 127? '1' : '0' );
		}
	}
	return $base10 ? base_convert ( $num , 2, 10) : $num;
}
/*
 ******* bitsign_rect ***
 */
function bitsign_rect($filename, $pxx, $pxy, $w, $h, $base10=true ) {
	// returns a 16 bit number from a filename, starting at pxx-pxy of a rect w-h 
	// converted to bw-bw (16) 0-1 cells
	$bw = 4; // 4x4 hard coded here
	$src_image = imagecreatefromjpeg($filename); 	// read from file
	imagefilter($src_image, IMG_FILTER_GRAYSCALE);	// turn to grayscale
	$dst_image = imagecreatetruecolor($bw, $bw);
	// bool imagecopyresampled  (  $dst_image  ,  $src_image  ,  $dst_x  ,  $dst_y  , $src_x  ,  $src_y  ,  $dst_w  ,  $dst_h  ,  $src_w  , $src_h  )
	imagecopyresampled ($dst_image, $src_image, 0, 0, $pxx, $pxy, $bw, $bw, $w, $h);  // copies w,h to bw,bw
	$num='';
	for ($j=0; $j<$bw; $j++) {	// line by line
		for ($i=0; $i<$bw; $i++) {
			$start_x = $i;
			$start_y = $j;
			$rgb = imagecolorat($dst_image, $start_x, $start_y);
			$b = $rgb & 0xFF;
			$num.= ($b > 127? '1' : '0' );
		}
	}
	imagedestroy($src_image);
	imagedestroy($dst_image);
	return $base10 ? base_convert ( $num , 2, 10) : $num;
}

/*
 ******* intsign_rect ***
 */
function intsign_rect($filename, $pxx, $pxy, $w, $h ) {
	// returns a 16 element array of 0-255 numbers 
	$bw = 4; // 4x4 hard coded here
	$a16 = array();
	$src_image = imagecreatefromjpeg($filename); 	// read from file
	imagefilter($src_image, IMG_FILTER_GRAYSCALE);	// turn to grayscale
	$dst_image = imagecreatetruecolor($bw, $bw);
	// bool imagecopyresampled  (  $dst_image  ,  $src_image  ,  $dst_x  ,  $dst_y  , $src_x  ,  $src_y  ,  $dst_w  ,  $dst_h  ,  $src_w  , $src_h  )
	imagecopyresampled ($dst_image, $src_image, 0, 0, $pxx, $pxy, $bw, $bw, $w, $h);  // copies w,h to bw,bw
	for ($j=0; $j<$bw; $j++) {	// line by line
		for ($i=0; $i<$bw; $i++) {
			$a16[$i][$j] =  imagecolorat($dst_image, $i, $j) & 0xFF;
		}
	}
	imagedestroy($src_image);
	imagedestroy($dst_image);
	return $a16;
}
			//$rgb = imagecolorat($dst_image, $i, $j);
			// $b = $rgb & 0xFF;

/*
 ******* bit_count 
*/
function bit_count($num=0) {
	// count bits of 16 bit binary representation of a decimal input
	$format = '%1$16b';
	return substr_count(sprintf($format, $num), '1');
}

/*
 ******* persists 
 usage:: persists($a3, 'a3', 'array()'); //for array, iclude array(1,2,3)" or smthing like that
 or :    persists($filename, 'filename', '*undefined*');
*/
function persists(&$var, $varname, $default='') {
	if (isset($_SESSION[$varname])  )   $var = $_SESSION[$varname]; // persist session val
	if (isset($_REQUEST[$varname])) 	$var = $_REQUEST[$varname];  //get or post override session
	// 	if (!isset($var)  )   $var = $default; // if all else fails
	if (!isset($var)  )  eval("\$var=\$default;"); // if undefined, read default supplied or funct-default
	    // needs the eval() in case $default contains "array(1,2,3)" or smthing like that
		// the \ before the $ is VERY important
	$_SESSION[$varname] = $var; // memorize
	return;
}


//******************************* COLOR *******************************************

/*
 ******* averagergb 
 */
function averagergb($img) {
    $w = imagesx($img);
    $h = imagesy($img);
    $r = $g = $b = 0;
    for($y = 0; $y < $h; $y++) {
        for($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($img, $x, $y);
            $r += $rgb >> 16;
            $g += $rgb >> 8 & 255;
            $b += $rgb & 255;
        }
    }
    $pxls = $w * $h;
	$rd = round($r / $pxls);
	$gd = round($g / $pxls);
	$bd = round($b / $pxls);
	return array($rd, $gd, $bd);
}
/*
 ******* averagergbxy for *part* of img, start at x y , width w, h
 */
function averagergbxy($img, $xstart, $ystart, $w, $h) {
    $r = $g = $b = 0;
    for($y = $ystart; $y < $ystart + $h; $y++) {
        for($x = $xstart; $x < $xstart + $w; $x++) {
            $rgb = imagecolorat($img, $x, $y);
            $r += $rgb >> 16;
            $g += $rgb >> 8 & 255;
            $b += $rgb & 255;
        }
    }
    $pxls = $w * $h;
	$rd = round($r / $pxls);
	$gd = round($g / $pxls);
	$bd = round($b / $pxls);
	return array($rd, $gd, $bd);
}

/*
 ******* rgbhex
 */
function rgbhex($r, $g, $b)
// efficient way to translate to hex by using sprintf format %02X
{
	return sprintf('%02X%02X%02X', $r, $g, $b);
}

/*
 ******* rgb_hsv
 */
function rgb_hsv_1($r, $g, $b){ // I prefer r g b separate in input; array output.
	$minVal = min($r, $g, $b);
    $maxVal = max($r, $g, $b);
    $delta  = $maxVal - $minVal;
    $v = $maxVal / 255;
    if ($delta == 0) {
        $h = 0;
        $s = 0;
    } else {
        $s = $delta / $maxVal;
		// *** this computation for h is not optimized!!! beuark!
        $del_R = ((($maxVal - $r) / 6) + ($delta / 2)) / $delta;
        $del_G = ((($maxVal - $g) / 6) + ($delta / 2)) / $delta;
        $del_B = ((($maxVal - $b) / 6) + ($delta / 2)) / $delta;

        if ($r == $maxVal){
            $h = $del_B - $del_G;
        } else if ($g == $maxVal) {
            $h = (1 / 3) + $del_R - $del_B;
        } else if ($b == $maxVal) {
            $h = (2 / 3) + $del_G - $del_R;
        }
        if ($h < 0){
            $h++;
        }
        if ($h > 1) {
            $h--;
        }
    }
    $h = round($h * 360);
    $s = round($s * 100);
    $v = round($v * 100);
    return array($h, $s, $v);
}

function rgb_hsv_2($r,$g,$b) { 
	$r = $r/255;				 // [0,1]
	$g = $g/255;
	$b = $b/255;
	$v = max($r,$g,$b); 
	$t = min($r,$g,$b); 
	$s =($v==0)? 0 :($v-$t)/$v; 
	if ($s==0) 
		$h = -1; 
	else { 
		$a=$v-$t; 
		$cr=($v-$r)/$a; 
		$cg=($v-$g)/$a; 
		$cb=($v-$b)/$a; 
		$h=($r==$v)?$cb-$cg:(($g==$v)?2+$cr-$cb:(($b==$v)?$h=4+$cg-$cr:0)); 
		$h=60*$h; 
		$h=($h<0)?$h+360:$h; 
	} 
	return array($h,$s,$v); 
} 

// $c = array($hue, $saturation, $brightness) 
// $hue=[0..360], $saturation=[0..1], $brightness=[0..1] 
function hsv_rgb_2($c) { 
	list($h,$s,$v)=$c; 
	if ($s==0) 
		return array($v,$v,$v); 
	else { 
		$h=($h%=360)/60; 
		$i=floor($h); 
		$f=$h-$i; 
		$q[0]=$q[1]=$v*(1-$s); 
		$q[2]=$v*(1-$s*(1-$f)); 
		$q[3]=$q[4]=$v; 
		$q[5]=$v*(1-$s*$f); 
		//return(array($q[($i+4)%5],$q[($i+2)%5],$q[$i%5])); 
		return(array($q[($i+4)%6],$q[($i+2)%6],$q[$i%6])); //[1] 
	} 
} 

function rgb_hsl_2($clrR, $clrG, $clrB){
     
    $clrMin = min($clrR, $clrG, $clrB);
    $clrMax = max($clrR, $clrG, $clrB);
    $deltaMax = $clrMax - $clrMin;
     
    $L = ($clrMax + $clrMin) / 510;
     
    if (0 == $deltaMax){
        $H = 0;
        $S = 0;
    }
    else{
        if (0.5 > $L){
            $S = $deltaMax / ($clrMax + $clrMin);
        }
        else{
            $S = $deltaMax / (510 - $clrMax - $clrMin);
        }

        if ($clrMax == $clrR) {
            $H = ($clrG - $clrB) / (6.0 * $deltaMax);
        }
        else if ($clrMax == $clrG) {
            $H = 1/3 + ($clrB - $clrR) / (6.0 * $deltaMax);
        }
        else {
            $H = 2 / 3 + ($clrR - $clrG) / (6.0 * $deltaMax);
        }

        if (0 > $H) $H += 1;
        if (1 < $H) $H -= 1;
    }
    return array($H*360, $S*100,$L*100);
}

function rgb_hsl($r, $g, $b) {  
    // **made in emanu**  no 510, much shorter.
	$minc = min($r, $g, $b);
    $maxc = max($r, $g, $b);
    $delta  = $maxc - $minc; 	 
    $l  = ($maxc + $minc) / 2 ; 	 // not normalized yet! [0 255]
    if ($delta == 0) {
		$h  = 0;
		$s  = 0;
    } else { 
        $s  = ( $l  < 128 ) ? 0.5 * $delta / $l  : 0.5 * $delta / ( 255 - $l  );
        if ($maxc == $r) $h = 1+    ($g - $b) / (6.0 * $delta );
        if ($maxc == $g) $h = 1/3 + ($b - $r) / (6.0 * $delta );
		if ($maxc == $b) $h = 2/3 + ($r - $g) / (6.0 * $delta );
        if ($h < 0) $h += 1;
        if ($h >= 1) $h -= 1; // [0 1[
	}
    $h = round($h * 360); // 0 359
    $s = round($s * 100);
    $l = round($l /255 * 100); 
    return array($h, $s, $l);
}

//****************** CROP ********************


// ----- getcropdim**************WARNING ROUNDING ISSUES??
//
function getcropdim($fn_img)
//usage:     list($x, $y, $w, $h) = getcropdim($filename);
{
    // compute dimensions for (cropped) square
    list($width, $height) = getimagesize($fn_img);
    if ($width > $height) {         // landscape
	    $h = $height;
	    $w = $h;
	    $x = ($width - $w)/2;
	    $y = 0;
    } else {                        // portrait
	    $w = $width;
	    $h = $w;
	    $y = ($height - $h)/2;
	    $x = 0;
    }
    return array($x, $y, $w, $h);
}
/*
	function getcroplettdim() {
		//list($width, $height) = getimagesize($fn_img);
		if ($this->width > $this->height*3.0/4.0) {         // reduce width, keep height
			$h = $this->height;
			$w = $h*3/4;
			$x = round(($this->width - $w)/2.0);
			$w = round($w);
			$y = 0;
		} else {                        // reduce height, keep width
			$w = $this->width;
			$h = $w*4/3;
			$y = round(($this->height - $h)/2.0);
			$h = round($h);
			$x = 0;
		}
		return array($x, $y, $w, $h);
	}
*/


/*
 *****   findfile
 */


//This function searches a directory and returns an array of all files whose filename matches the specified //regular expression. It's similar in concept to the Unix find program.
function findfile($location='',$fileregex='') {
   if (!$location or !is_dir($location) or !$fileregex) {
      return false;
   }

   $matchedfiles = array();

   $all = opendir($location);
   while ($file = readdir($all)) {
      if (is_dir($location.'/'.$file) and $file <> ".." and $file <> ".") {
         $subdir_matches = findfile($location.'/'.$file,$fileregex);
         $matchedfiles = array_merge($matchedfiles,$subdir_matches);
         unset($file);
      }
      elseif (!is_dir($location.'/'.$file)) {
         if (preg_match($fileregex,$file)) {
            array_push($matchedfiles,$location.'/'.$file);
         }
      }
   }
   closedir($all);
   unset($all);
   return $matchedfiles;
}

function fastimagecopyresampled (&$dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
  // Author: Tim Eckel - Date: 09/07/07 - Version: 1.1 - Project: FreeRingers.net - Freely distributable - These comments must remain.
  // 2 = Up to 95 times faster.  Images appear a little sharp, some prefer this over a quality of 3.
  // 3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled, just faster.
  // 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
  // 5 = No speedup. Just uses imagecopyresampled, no advantage over imagecopyresampled.

  if (empty($src_image) || empty($dst_image) || $quality <= 0) return false; 
  if ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
    $temp = imagecreatetruecolor ($dst_w * $quality + 1, $dst_h * $quality + 1);
    imagecopyresized ($temp, $src_image, 0, 0, $src_x, $src_y, $dst_w * $quality + 1, $dst_h * $quality + 1, $src_w, $src_h);
    imagecopyresampled ($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $dst_w * $quality, $dst_h * $quality);
    imagedestroy ($temp);
  } else imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
  return true;
}




/*
 *****   query_select
 */
function query_select($name, $query, $default='')
{
  $conn = db_connect();
  $result = mysql_query($query, $conn);
  if (!$result)
    return(0);
  $select  = "<SELECT NAME=\"$name\">";
  $select .= "<OPTION VALUE=\"\">-- Choose --</OPTION>";
  for ($i=0; $i < mysql_numrows($result); $i++) {
    $opt_code = mysql_result($result, $i, 0);
    $opt_desc = mysql_result($result, $i, 1);
    $select .= "<OPTION VALUE=\"$opt_code\"";
    if ($opt_code == $default) {
      $select .= ' SELECTED';
    }
    $select .=  ">[$opt_code] $opt_desc</OPTION>";
  }
  $select .= "</SELECT>\n";
  return($select);
}

/*
 *****   query_select1
 */
function query_select1($name, $query, $default='')
{
  $conn = db_connect();
  $result = mysql_query($query, $conn);
  if (!$result)
    return(0);
  $select  = "<SELECT NAME=\"$name\">";
  //$select .= "<OPTION VALUE=\"\">-- Choose --</OPTION>";
  for ($i=0; $i < mysql_numrows($result); $i++) {
    $opt_code = mysql_result($result, $i, 0);
    //$opt_desc = mysql_result($result, $i, 1);
    $select .= "<OPTION VALUE=\"$opt_code\"";
    if ($opt_code == $default) {
      $select .= ' SELECTED';
    }
    $select .=  ">$opt_code</OPTION>";
  }
  $select .= "</SELECT>\n";
  return($select);
}

/*
 *****   select_hue
 */
function select_hue($selhue, $hfactor) {
	$conn = db_connect();
	$query = "SELECT distinct round( h / $hfactor ) as huerange FROM `pics` WHERE 1 order by 1 ";
	$result = mysql_query($query);
	// $select contains the html for the select statement ie the dropdown
	$select  = "\n<SELECT name='huerange'> 
					<option value='all'>All hues</option>";
	
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	//   fetches a huerange at a time; exits when empty
		$huerange = trim($row["huerange"]);
		$select .= "\n<OPTION VALUE=\"$huerange\"";
		if ($huerange == trim($selhue)) {
		  $select .= ' SELECTED';
		}
		$select .=  ">$huerange</OPTION>";
	}
	$select .= "</SELECT>\n";
	return $select;
}

// ***** select_fol ------- dropdown containing the folder  
//
function select_fol($selfolder) {
	$conn = db_connect();
	$query = "select distinct left(name, 8) as folder 
				  FROM pics 
				  WHERE 1
				  ORDER by 1 ";
	$result = mysql_query($query);
	$select  = "<SELECT name='folder'> 
					<option value='all'>All</option>";//for none
	
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$folder = trim($row["folder"]);
		$select .= "<OPTION VALUE=\"$folder\"";
		if ($folder == trim($selfolder)) {
		  $select .= ' SELECTED';
		}
		$select .=  ">$folder</OPTION>";
	}
	$select .= "</SELECT>";
	return $select;
}

// ***** select_row ------- dropdown containing the options for ROW field  
//
function select_row($selectrow) {
	$acol = array('name', 'h', 's', 'l');
	
	$select = "<SELECT name='row'> ";
	foreach ($acol as $field) {
		$select .= " <OPTION VALUE=\"$field\"";
		if ($field == trim($selectrow)) {
		  $select .= ' SELECTED';
		}
		$select .=  ">$field</OPTION>";
	}
	$select .= "</SELECT>";
	return $select;
}

// ***** select_col ------- dropdown containing the options for COL field  
//
function select_col($selectcol) {
	$acol = array('name', 'h', 's', 'l');
	
	$select = "<SELECT name='col'> ";
	foreach ($acol as $field) {
		$select .= " <OPTION VALUE=\"$field\"";
		if ($field == trim($selectcol)) {
		  $select .= ' SELECTED';
		}
		$select .=  ">$field</OPTION>";
	}
	$select .= "</SELECT>";
	return $select;
}
// HTML INCLUDES ----------------------------
//
/*
 *****   header_u
 */
function header_u($pagetitle, $msgh='', $menucode='13', $left=true)
//
{
	$_SESSION['pagetitle'] = $pagetitle;
	$_SESSION['msgh'] = $msgh;
	$_SESSION['menucode'] = $menucode;
	$_SESSION['left'] = $left;
	include('headaum.php');
    return true;
}

?>
