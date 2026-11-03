<?php
function thumb_gallery($name,$filename,$new_w,$new_h,$dir_name){
	$system = explode($dir_name,$name);
	$size 	= getimagesize($dir_name.$filename);
	
	if (preg_match("/jpeg|jpg|JPEG|JPG/",$system[0])){$src_img=imagecreatefromjpeg($dir_name.$filename);}
	if (preg_match("/gif|GIF/",$system[0])){$src_img=imagecreatefromgif($dir_name.$filename);}
	if (preg_match("/png|PNG/",$system[0])){$src_img=imagecreatefrompng($dir_name.$filename);}

	$old_x	 = imagesx($src_img);
	$old_y	 = imagesy($src_img);
  	$thumb_w = round(($old_y * $new_h) / $old_x);
	$thumb_h = round(($old_y * $new_w) / $old_x);
 	$dst_img = imagecreatetruecolor($new_w,$thumb_h);
	$transparent = imagecolorallocate($dst_img,0,255,0);
	imagecolortransparent($dst_img,$transparent);
	imagealphablending($dst_img, false);
	imagesavealpha($dst_img, true);
 	$file_name = $dir_name.$filename;
 	imagecopyresampled($dst_img,$src_img,0,0,0,0,$new_w,$thumb_h,$old_x,$old_y);  
 	if (preg_match("/png/",$system[0])) {
		imagepng($dst_img,$file_name); 
	} else {
		imagejpeg($dst_img,$file_name); 
	}
	imagedestroy($dst_img); 
	imagedestroy($src_img); 
	return $file_name;
}