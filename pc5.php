<?php 
/* pc5.php July  2010  ver 0.3.5.1
 *
 * july: adds mem limit++ ; zip output ; links as target=_blank ; ...
 * june: adds fullsize module and longdesc tag
 *
 * 'pcds' is the photochop datastore for project data and settings
 */
 
ini_set('memory_limit','192M'); //for php fatal error memory exhausted

 
include 'include_fns.php';
if (session_id() == "") {
	@session_start();
}

// on cold start there is no session and $apar will be unserialized from disk
// and feed sensible defaults for all the other persists;
// if there is a session (warm start in a new tab or subsequent calls,
// $apar will be already in Session, DO NOT Unserialize, of course, just restore from Session
if (isset ($_SESSION['apar'])) { // not cold start
	$apar = $_SESSION['apar'];
} else {
	if (is_file('pcds')) { //****** * UN-SERIALIZATION IF FINDS STORE *** **** !!!
		$s = file_get_contents('pcds');
		$apar = unserialize($s);
		//echo "!!!*** Cold start: unserialize \$apar <br/>";  //  
	}
}
// $apar could still be not set if store absent and cold start both, call this EXTRA-COLD 
if (!isset($apar)) {
	$apar = array(
		'wp'=>6, 
		'hp'=>4, 
		'reso'=>300, 
		'filename'=>'demo/1.jpg', 
		'urlname'=>'http://localhost/mosa2/mod/split/8.jpg', 
		'upname'=>'', 
		'pathout'=>'out/',
		'nw'=>3,
		'ox'=>0.1, 
		'projname'=>'p1', 
		'picname'=>'', 
		'randmini'=>'mini/minipic.jpg', 
		); 
} else {
	// from last saved session or store - might be overruled by $request later
	$wp = $apar['wp'];
	$hp = $apar['hp'];  
	$reso = $apar['reso'];  
	$filename = $apar['filename'];  
	$urlname = $apar['urlname'];  
	$upname = $apar['upname'];  
	$pathout = $apar['pathout'];  
	$nw = $apar['nw'];  
	$ox = $apar['ox'];  
	$projname = $apar['projname'];  
	$picname = $apar['picname'];  
	$randmini = $apar['randmini'];  
}

// persists - the vars are defined so the defa should never be read
// but the vars read from $apar could be overwritten if there are new $_Requested values
persists($wp,   'wp', 6);
persists($hp,   'hp', 4);
persists($reso, 'reso', 300); 
persists($filename, 'filename', 'demo/1.jpg'); 
persists($urlname, 'urlname', 'http://localhost/mosa2/mod/split/8.jpg'); 
persists($upname, 'upname', 'album/8.jpg'); 
persists($pathout, 'pathout', 'out/');  
persists($w0, 'w0', 3000); 
persists($h0, 'h0', 2000); 
persists($nw,   'nw', 3);
persists($ox,   'ox', 0.1);
persists($projname,   'projname', 'p1');

// letsee if a pic has been uploaded
if ( (isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name']))) {
	$type = basename($_FILES['picture']['type']);
	$picturename = $_FILES['picture']['name'];
	switch ($type) {
	case 'jpeg': 
		$upload = true;
		$upname = "album/$picturename";
		move_uploaded_file($_FILES['picture']['tmp_name'], $upname);
		break;
	default:        print 'Invalid picture format: '.
						  $_FILES['picture']['type'];
					$upload = false;
	}
} else $upload = false;
//header('Location: '.$_POST['destination']."?upname=$upname");

$picname0 = $picname ; 	// we want to detect a change in active pic > newpic= project name change and uncheck crop!
$newpic = false; 		// no need to recreate minipic if not new pic

// Selection of source file from 3 inputs
// 'upload' feeds into 'url'
//if (isset($_GET['upname']) && $_GET['upname']!=='' ) {
if ( $upload ) {
	//$upname = $_GET['upname'];
	$urlname = $upname ;
	$projname = basename($urlname, '.jpg'); // projname Must change to not overwrite
	$filename = 'none of those';  // drop down should take heed
	$newpic = true; // 
} else {
	$upname = '';
}
// url if 'none of those'
if ($filename=='none of those') $picname = $urlname; else $picname = $filename;
// -- END selection Input
// cancel old projname if new pic!
if ($picname!==$picname0) {
	$projname = basename($picname, '.jpg'); // projname Must change to not overwrite	
	$newpic = true;
}

// C H E C K B O X E S ************ ------------ ------- -------- --------
// checkbox for   processing    
if (isset($_REQUEST['process']) && $_REQUEST['process']!=='' ) {
	$processchecked = 'checked';
	$process = 'on';
} else {
	$processchecked = ' ';
	$process = '';
}
// checkbox for   crop    
if (isset($_REQUEST['crop']) && $_REQUEST['crop']!=='' && !$newpic ) { // new pic > no crop!
	$cropchecked = 'checked';
	$crop = 'on';
} else {
	$cropchecked = ' ';
	$crop = '';
}
 
//-- Establish metrics for project **********  

list ($w0, $h0) = getimagesize($picname);	 

// scope 
$w = ceil( ($w0 / $nw) * (1 + ($ox/$wp) * ($nw-1)/$nw )); // overlay x
$h = ceil( $w * $hp / $wp );

// for the chopped display when process to out is on
$w2 = $w / 10;
$h2 = $h / 10;

// calc delta from overlay-x
// delta-x is same percentage of w as ox is of wp  dx/w=ox/wp -> dx=w*ox/wp  (PIXELS)
$dx = ceil( $w * $ox / $wp );
$dy = ceil ( $dx * $hp / $wp );

// calc nh before startY
$nhstar0 =    $h0 *  $nw * $wp  / $w0  / $hp   ; 
$nh0 = ceil($nhstar0);
$wastepx0 = ceil(($nhstar0 - floor($nhstar0)) / $nhstar0  * $h0) ;
if ($crop == 'on') { //  chop the pic to save paper
	$starty = $wastepx0 ;
	$hproc = $h0 - $wastepx0;
} else {
	$starty =  0 ;
	$hproc = $h0 ;

}

// calc Nh
$nhstar =  ceil( 100 * $hproc *  $nw * $wp  / $w0  / $hp ) /100   ; 
$nh = ceil($nhstar);
$nbpics  = $nh * $nw;
$waste = ceil (($nh - $nhstar) / $nh *100) ;
$wastepx = ceil(($nhstar - floor($nhstar)) / $nhstar  * $h0) ;

// calc w1
$sidebarwidth = 200;
$ratiothumb = $w0 / $sidebarwidth;
$w1 = $w0 / $ratiothumb; // display width 
$h1 = $h0 / $ratiothumb; // respect print aspect ratio

// calc w3
$overlaywidth = 500;
$ratiothumb3 = $w0 / $overlaywidth;
$w3 = $w0 / $ratiothumb3; // display width 
$h3 = $h0 / $ratiothumb3; // respect print aspect ratio

// if need new minipic
if ($newpic) {
	unlink('mini/minipic4.jpg');
	copy ('mini/minipic3.jpg', 'mini/minipic4.jpg');
	copy ('mini/minipic2.jpg', 'mini/minipic3.jpg');
	copy ('mini/minipic1.jpg', 'mini/minipic2.jpg');
	copy ('mini/minipic.jpg', 'mini/minipic1.jpg');
	// copy a mini to folder mini
	$image_p = imagecreatetruecolor($w1, $h1);
	$image = imagecreatefromjpeg($picname);
	imagecopyresampled($image_p, $image, 0, 0, 0, 0, $w1, $h1, $w0, $h0);
	// Output
	imagejpeg($image_p, 'mini/minipic.jpg', 100);
	// we need to make a copy with a different name (random / always different) to 'fool' the browser cache
	// it's just useful for the display
	$randmini = 'mini/mini' . mt_rand() . '.jpg';
	imagejpeg($image_p, $randmini, 100);
	
	//imagedestroy(image);
	//imagedestroy(image_p);
}
// END copy a mini to folder mini


// now that base metrics are set and minipic is available, prepare the chopped minipics
$src_image = imagecreatefromjpeg('mini/minipic.jpg');
for ($j=0; $j < $nh; $j++) {
	for ($i=0; $i < $nw; $i++) {
		$x = $i * $w1 / $nw ; //no overlap management necessary
		$y = $starty / $ratiothumb + ($j *  $w1/$nw  *  $hp/$wp);
		$rcindex = "-c$i-r$j";
		$outfile = "mini/mini" . $rcindex . ".jpg";  // ie: CTr0c2.jpg in mod/split/out/
		$dst_image = imagecreatetruecolor($w1 / $nw, $w1/$nw  *  $hp/$wp);
		imagecopy($dst_image, $src_image, 0, 0, $x, $y, $w1 / $nw, $w1/$nw  *  $hp/$wp);
		imagejpeg($dst_image, $outfile, 100);
		//imagedestroy(dst_image);
	}
}
//imagedestroy(src_image);
// END chopped minis creation

// prepare the medium pics
//  imagecopyresampled (  $dst_image ,  $src_image ,  $dst_x ,  $dst_y ,  $src_x ,  $src_y ,  $dst_w ,  $dst_h ,  $src_w ,  $src_h )

$src_image = imagecreatefromjpeg($picname);
for ($j=0; $j < $nh; $j++) {
	for ($i=0; $i < $nw; $i++) {
		$x = $i * $w0 / $nw ; //no overlap management necessary
		$y = $starty / $ratiothumb3 + ($j *  $w0/$nw  *  $hp/$wp);
		$rcindex = "-c$i-r$j";
		$outfile = "medium/mini" . $rcindex . ".jpg";  // ie: CT-r0c2.jpg in /medium/
		$dst_image = imagecreatetruecolor($overlaywidth, $overlaywidth  *  $hp/$wp);
		imagecopyresampled($dst_image, $src_image, 0, 0, $x, $y, $overlaywidth, $overlaywidth * $hp/$wp , $w0 / $nw, $w0/$nw  *  $hp/$wp);
		imagejpeg($dst_image, $outfile, 100);
		//imagedestroy(dst_image);
	}
}




 ?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title> Photo_Chop! <?php echo $projname ; ?> </title>
    <link href="_css/main.css" rel="stylesheet" type="text/css" media="screen, projection" />
    <link href="_css/cupertino/jquery-ui-1.7.3.custom.css" rel="stylesheet" type="text/css"
        media="screen, projection" />

    <script type="text/javascript" src="_scripts/jquery-1.3.2.min.js"></script>
    <script type="text/javascript" src="_scripts/jquery-ui-1.7.3.custom.min.js"></script>
	
	<link href="fullsize/fullsize.css" media="screen" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="fullsize/jquery.fullsize.js"></script>

    <script type="text/javascript" src="_scripts/ajaxupload.js"></script>
	
	
    <script type="text/javascript">
        $(function() {
            // set up the   accordion   - , event: 'mouseover'     _css/sunny/jquery-ui-1.7.2.custom.css
            $("#newsSection").accordion({ header: "h4", active: false, collapsible: true, autoHeight: false });

            // create the image rotator
            setInterval("rotateImages()", 2000);
			//$("img").fullsize();
        });

        function rotateImages() {
            var oCurPhoto = $('#photoShow div.current');
            var oNxtPhoto = oCurPhoto.next();
            if (oNxtPhoto.length == 0)
                oNxtPhoto = $('#photoShow div:first');

            oCurPhoto.removeClass('current').addClass('previous');
            oNxtPhoto.css({ opacity: 0.0 }).addClass('current').animate({ opacity: 1.0 }, 1000,
                function() {
                    oCurPhoto.removeClass('previous');
                });
        }
		
		$(function(){
			$("img").fullsize();
				
			/* file upload */
			var interval;
			
			new AjaxUpload('button3', {
				action: 'upload.php',
				name: 'userfile',
				
				onSubmit : function(file , ext){
					// Allow only images. You should add security check on the server-side.
					if (ext && /^(jpg|jpeg)$/.test(ext)){
						/* Setting data */
						this.setData({
							'key': 'This string will be send with the file'
						});					
						//$('#newsSection .text').text('  Uploading ' + file);
						$('#newsSection .text').text('Uploading' );

						interval = window.setInterval(function(){
							var text = $('#newsSection .text').text();
							if (text.length < 28){
								$('#newsSection .text').text(text + ' . ' );					
							} else {
								$('#newsSection .text').text('Uploading' );				
							}
						}, 200);
						
					} else {					
						// extension is not allowed
						$('#newsSection .text').text('Error: only images are allowed');
						// cancel upload
						return false;				
					}		
				},
				
				onComplete : function(file){
					window.clearInterval(interval);
					$('<li></li>').appendTo($('#newsSection .files')).text(file + ' ready.');
					$('#urlname').attr('value', 'album/' + file);
					$('#newsSection .text').text(' ');	
	
				}	
			});		
		});

		
		
		
    </script>

	    <style type="text/css">
		
		.text {			
			font-size: 14px; color: #C7D92C; text-align: center; padding-top: 15px;
		}
		.files {			
			font-size: 14px; color: #C7D92C; text-align: center; padding-top: 15px;
		}
	</style>

	
	
	
</head>
<body>
    <div id="wrapper">
        <div id="header">
            <div id="contactButton">
                <a href="mailto:contact@anythingsoft.com" title="contact us">Contact Us</a></div>
            <img src="_assets/banner.jpg" width="770" height="110" alt="anythingsoft" />
            <div id="mainNav">
                <ul>
                    <li><a href="#" class="current">Home</a></li>
                    <li><a href="#">Cars</a></li>
                    <li><a href="#">Gallery</a></li>
                </ul>
            </div>
        </div>
        <div id="identifier">
            <img src="_assets/banner_bot.jpg" width="770" height="45" alt="height should be 90" />
		</div>
			
	<!-- S I D E B A R ** * * * * * * * ********************* * * * * * * * * * * * * <img src="_images/surf1.jpg"  alt="me" longdesc="_images/swells_large.jpg" />  -->
			 
        <div id="sidebar">
            
            <h3>
                Source</h3>
            <p>
			<?php  
				$color = 'green';
				$text = 'Excellent';
				
				if ( $h/$hp < 72*2 ) { $color = 'orange'; $text='OK'; }
				if ( $h/$hp < 72 ) { $color = 'red'; $text = 'LOW'; }
				//echo "<img src=\"mini/minipic.jpg\" width=$w1 height=$h1 border=1 >"; // $randmini
				echo "<img src=\"$randmini\" width=$w1 height=$h1 border=1 >"; // 
				
				echo " Filename: <strong>$picname</strong> 			<br />  
					Dimensions: $w0 x $h0 			<br /> 
					Output: $nw x $nh x $w x $h 	<br /> 
					Resolution: <span style='color: $color;'> $text </span>
					 ";   // Needed overlap: $dx  x  $dy px. <br />   
			?>
			
			</p>
            <h3>
                Layout Preview</h3>
            <p> 

			<?php
				echo "<table align='center' border='0' cellspacing='2' cellpadding='2' >";
				//echo "<br/>";
				for ($j=0; $j < $nh; $j++) {
					echo "<tr>";
					for ($i=0; $i < $nw; $i++) {
						$rcindex = "-c$i-r$j";
						echo "<td width='60' ><img src='mini/mini$rcindex.jpg' longdesc='medium/mini$rcindex.jpg' border='1' alt='fullsize!' /></td>";
					}
					echo "</tr>";
				}
				echo "</table>";
				
			?>
			 </p>
			<h3>
				Stats `<?php echo $projname; ?>`</h3>
			<p>
			Needed in Height: <?php echo $nh, ' (exact: ', $nhstar, ') <br > Total nbpics: ', $nbpics, ' - waste: ', $waste, '% <br > ' ;?>
			Final W x H: <?php echo $wp * $nw, ' x ', $nhstar * $hp,  ' in. ' ;?>
			i.e. : <?php echo ceil( $wp * $nw * 25.4 ), 'mm  x ', ceil( 25.4 * $nhstar * $hp), 'mm';?>						
			</p>

            <h3>
                Output Filenames</h3>
            <p>
			<?php
			$rcindex= '-r0-c0';
			$outfile = $pathout . trim($projname) . $rcindex . ".jpg";   
			echo ' From: ', $outfile;
			$rcindex= "-r$nh-c$nw";
			$outfile = $pathout . trim($projname) . $rcindex . ".jpg";   
			echo ' To: ', $outfile;
			?>
			</p>

			            <h3>
                Recent Projects</h3>

            <div id="photoShow">
                <div class="current">
                    <a href="#">
                        <img src="mini/minipic1.jpg" alt="Photo Gallery" width="200" height="160"
                            class="gallery" /></a>
                </div>
                <div>
                    <a href="#">
                        <img src="mini/minipic2.jpg" alt="Photo Gallery" width="200" height="160"
                            class="gallery" /></a></div>
                <div>
                    <a href="#">
                        <img src="mini/minipic3.jpg" alt="Photo Gallery" width="200" height="160"
                            class="gallery" /></a></div>
                <div>
                    <a href="#">
                        <img src="mini/minipic4.jpg" alt="Photo Gallery" width="200" height="160"
                            class="gallery" /></a></div>
            </div>

        </div>
        <div id="mainContent">
            <h1>
                Welcome to Photo Chop!</h1>
				
				<?php
				
// output main content +++++++++++++++++++++++++++++++++++++++++++++++

if ($process == 'on') { //  means we create the prints (.jpg to be printed)
	$src_image = imagecreatefromjpeg($picname);
	echo "<p><em>The individual pics can be checked and 'Saved As...' 1 by 1 or downloaded all at once in the ZIP supplied. </em></p>";
	
	echo "<table>";
	for ($j=0; $j < $nh; $j++) {
		echo "<tr>";
		for ($i=0; $i < $nw; $i++) {
			$x = $i * ($w - $dx);
			$y = $starty + ($j * ($h - $dy));
			$rcindex = "-c$i-r$j";
			$outfile = $pathout . trim($projname) . $rcindex . ".jpg";  // ie: CTr0c2.jpg in mod/split/out/
			$dst_image = imagecreatetruecolor($w, $h);
			imagecopy($dst_image, $src_image, 0, 0, $x, $y, $w, $h);
			imagejpeg($dst_image, $outfile, 100);
			//imagedestroy(dst_image);
			echo "<td> <a href='$outfile' target='_blank'><img src='$outfile' width='$w2' height='$h2' border='1' title='x $x  - y: $y - outfile: $outfile'></a> </td>";
		}
		echo "</tr>";

	}
	echo "</table>";

	imagedestroy ($src_image);
	
	// create a zip file ----------------
	$zip = new ZipArchive();
	$projname = trim($projname);
	$zipfilename = "./zip/$projname.zip";
	if ($zip->open($zipfilename, ZIPARCHIVE::CREATE)!==TRUE) {
		exit("cannot create the zip: <$zipfilename>\n");
	}
	for ($j=0; $j < $nh; $j++) {
		for ($i=0; $i < $nw; $i++) {
			$rcindex = "-c$i-r$j";
			$outfile = $pathout . trim($projname) . $rcindex . ".jpg";  // ie: projname-c0-r2.jpg
			$zip->addFile( "$outfile","$outfile");
		}
	}
	//$zip->addFile($thisdir . "/jmg125.jpg","/2.jpg");
	//echo "num zip files: " . $zip->numFiles . "\n";
	//echo "zip status:" . $zip->status . "\n";
	echo "<a href='$zipfilename' target='_blank'>Click to download $projname.zip)</a>";
	$zip->close();
	//- end zip ---------------------------
	
	
	
	// uncheck now it s done
	$processchecked = ' ';
	$process = '';

}

echo "</p>";

// -1- save parameters to apar
	$apar['wp']=$wp;
	$apar['hp']=$hp;  
	$apar['reso']=$reso;  
	$apar['filename']=$filename;  
	$apar['urlname']=$urlname;  
	$apar['pathout']=$pathout;  
	$apar['nw']=$nw;  
	$apar['ox']=$ox;  
	$apar['projname']=$projname;  
	$apar['picname']=$picname;  
	$apar['randmini']=$randmini;  
// -2- save $apar to SESSION -------- 
$_SESSION['apar'] = $apar; 
// -3- save $apar to $s to STORE  ------ -----!
$s = serialize($apar);
file_put_contents('pcds', $s);

// PIC
// echo "<p><img src=\"$picname\" width=$w1 height=$h1 border=1 >", ' ';

//************ FORM ************ --------- --------
?>
	
		<hr/>

            <div id="newsSection">
				<form action='pc5.php' method="post" enctype="multipart/form-data" >

				<!-- h4 module -->
				<h4>
					<span class="subhead">PAPER OPTIONS (3 critical dimensions)</span></h4>
                <p>
					Paper dimensions: <input name='wp' value ='<?php echo $wp ;?>'  size='1'>in. x 
					<input name='hp' value ='<?php echo $hp ;?>'  size='1'>in. <br /> 
					Needed Horizontal Overlap <input name='ox' value ='<?php echo $ox ;?>'  size='2'>in. </p>
				<!-- END h4 module -->
			
				<h4>
					<span class="subhead">INPUT OPTIONS </span></h4>
                <p>
					 
					* either: enter URL: <input id='urlname' name='urlname' value ='<?php echo $urlname ;?>'  size='25'>  <br />
					 
					* or:	<!--<a href='uploadphoto.php'>Upload from local filesystem</a> <br />
					<input type="hidden" name="destination" value="pc5.php"> -->
					<!--Upload from local filesystem <input id="button3" type="file" name="picture"  accept="image/jpeg" size="30"> -->
					<a href="#" id="button3">Upload Image to Album</a> <br/>
					
			<span class="text"></span>
			<span class="files"></span>

					<br/>
					<!-- <input type="submit" value="Upload it!"> <br/> -->
					
					* or: Presets (demo)
						<select name='filename' id='filename'>
							<option value='none of those'    <?php echo $filename=='none of those'? 'selected':' '; ?> >none of those</option>
							<option value='demo/1.jpg'    <?php echo $filename=='demo/1.jpg'? 'selected':' '; ?> >demo/1.jpg</option>
							<option value='demo/2.jpg'    <?php echo $filename=='demo/2.jpg'? 'selected':' '; ?> >demo/2.jpg</option>
							<option value='demo/3.jpg'    <?php echo $filename=='demo/3.jpg'? 'selected':' '; ?> >demo/3.jpg</option>
							<option value='demo/4.jpg'    <?php echo $filename=='demo/4.jpg'? 'selected':' '; ?> >demo/4.jpg</option>
							<option value='demo/5.jpg'    <?php echo $filename=='demo/5.jpg'? 'selected':' '; ?> >demo/5.jpg</option>
							<option value='demo/6.jpg'    <?php echo $filename=='demo/6.jpg'? 'selected':' '; ?> >demo/6.jpg</option>
							<option value='demo/7.jpg'    <?php echo $filename=='demo/7.jpg'? 'selected':' '; ?> >demo/7.jpg</option>
						</select>   </p>

				<h4>
					<span class="subhead">LAYOUT (just 1 question) </span></h4>
                <p>

					How many Prints Wide: <input name='nw' value ='<?php echo $nw ;?>'  size='1'>   
					<br/>
					<label for='crop'> Crop Vertical </label>
					<input type='checkbox' id='crop' name='crop' <?php echo $cropchecked; ?> > - 
					Start Y: 
					<input name='starty' value ='<?php echo $starty ;?>'  size='2'>px 			</p>

				<h4>
					<span class="subhead">OUTPUT OPTIONS</span></h4>
                <p>
					<label for='process'> Output to Folder </label>
					<input type='checkbox' id='process' name='process' <?php echo $processchecked; ?> > 
					(check here to do the Chopping when everything above looks ok)
					<br />
					Output Folder Name:<input name='pathout' value ='<?php echo $pathout ;?>'  size='15' readonly > 
					<br />
					Project Name (short, no spaces): <input name='projname' value ='<?php echo $projname ;?>'  size='7'> 	</p>

				<br/>
				<input type='submit' value='Refresh / Process with Options'>
		</form>

		<br/>
				
            <h2>
                Help & Tips</h2>
                <h4>
                    <span class="subhead">Getting Started</span></h4>
                <p>
                    Visit each section of the "accordion" above, as needed, to set the parameters for the project.  Every time you change a parameter you should (if you want) press the big 'Refresh' button to see what that changes for your project. Visit the other Help sections below to learn about the many options and displays. </p>
					
                <h4>
                    <span class="subhead">Tip: Paper Options</span></h4>
                <p>
				The `Paper dimensions:`  needs be entered only once if you always work with the same paper that's why it's not always taking up space on your screen. The Overlap input is necessary if you notice the printer can't help but trimming a little bit of your picture. If you paste together your PhotoChop(tm) project it will be obvious if you need it or not. 
				</p>

                <h4>
                    <span class="subhead">Tip: Input Options</span></h4>
                <p>
				Here you select the picture that's going to be chopped: it can be uploaded from you computer, any URL from the internet (like a picture from your Picasa(tm) or Flickr(tm) account), or one of the preselected pictures of the demo dropdown. (good for testing)
				</p>


					<h4>
                    <span class="subhead">Tip: Output Options</span></h4>
                <p>
				Only when you're ready to "Chop" should you tick the CheckBox <strong>Output to Folder</strong> that will create each individual picture both in the output folder and on the main screen for easy download.  
				</p>
                <h4>
                    <span class="subhead">Tip: Project Name</span></h4>
                <p><em>Do not forget the <strong>Project Name</strong> (in Output Options) as it will be the prefix of all pics to be printed so it has to be unique if you have several projects. </em> If you 'output' twice with the same project name, and did not save your individual pictures immediately, they will be overwritten. 
				</p>
	
					
					
				
            </div>
			<!-- p -->
            <p>

			
        </div>
        <div id="footer">
            <p>
                &copy; AnythingSoft.com  <a href="privacy.htm"> Privacy Policy</a> | <a href="terms.htm">
                    Terms and Conditions</a></p>
        </div>
    </div>
	<!-- 
	Mauris mauris ante, blandit et, ultrices a, suscipit eget, quam. Integer ut neque. Vivamus nisi metus, molestie vel, gravida in, condimentum sit amet, nunc. Nam a nibh. Donec suscipit eros. Nam mi. Proin viverra leo ut odio. Curabitur malesuada. Vestibulum a velit eu ante scelerisque vulputate.
	-->
</body>
</html>
