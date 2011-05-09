#!/usr/bin/php
<?php

/**
* Watermaker Sérgio Serra 2011 
*
* Applies selected watermark to all files in folder
*
* Usage php watermarker.php <watermark_img> <folder>
*
**/

if ($argc < 3) {
	print "Missing parameters\n";
	print "Usage: php watermarker.php <watermark_img.png> <folder>\n";
	print $argc;
	exit();
}

function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct, $trans = NULL)
{
  $dst_w = imagesx($dst_im);
  $dst_h = imagesy($dst_im);

  // bounds checking
  $src_x = max($src_x, 0);
  $src_y = max($src_y, 0);
  $dst_x = max($dst_x, 0);
  $dst_y = max($dst_y, 0);
  if ($dst_x + $src_w > $dst_w)
    $src_w = $dst_w - $dst_x;
  if ($dst_y + $src_h > $dst_h)
    $src_h = $dst_h - $dst_y;

  for($x_offset = 0; $x_offset < $src_w; $x_offset++)
    for($y_offset = 0; $y_offset < $src_h; $y_offset++)
    {
      // get source & dest color
      $srccolor = imagecolorsforindex($src_im, imagecolorat($src_im, $src_x + $x_offset, $src_y + $y_offset));
      $dstcolor = imagecolorsforindex($dst_im, imagecolorat($dst_im, $dst_x + $x_offset, $dst_y + $y_offset));

      // apply transparency
      if (is_null($trans) || ($srccolor !== $trans))
      {
        $src_a = $srccolor['alpha'] * $pct / 100;
        // blend
        $src_a = 127 - $src_a;
        $dst_a = 127 - $dstcolor['alpha'];
        $dst_r = ($srccolor['red'] * $src_a + $dstcolor['red'] * $dst_a * (127 - $src_a) / 127) / 127;
        $dst_g = ($srccolor['green'] * $src_a + $dstcolor['green'] * $dst_a * (127 - $src_a) / 127) / 127;
        $dst_b = ($srccolor['blue'] * $src_a + $dstcolor['blue'] * $dst_a * (127 - $src_a) / 127) / 127;
        $dst_a = 127 - ($src_a + $dst_a * (127 - $src_a) / 127);
        $color = imagecolorallocatealpha($dst_im, $dst_r, $dst_g, $dst_b, $dst_a);
        // paint
        if (!imagesetpixel($dst_im, $dst_x + $x_offset, $dst_y + $y_offset, $color))
          return false;
        imagecolordeallocate($dst_im, $color);
      }
    }
  return true;
}

//configuration data to implement later
$padding = 5;
$opacity = 100;

// Check for watermark image 
$watermark = imagecreatefrompng($argv[1]);

if (!$watermark) {
	print "Invalid watermark image.\n";
	exit();
} else {
	$watermark_size	= getimagesize($argv[1]);
	$watermark_width = $watermark_size[0];  
	$watermark_height = $watermark_size[1];  
	
	imagealphablending($watermark, false );
	imagesavealpha($watermark, true );
}

//check for dir 
$img_dir = opendir($argv[2]);

if (!$img_dir) {
	print "Invalid folder.\n";
	exit();
}


while (false !== ($file = readdir($img_dir))) {
	
	if ($file != "." && $file != "..") {
		$image_path = $argv[2] . DIRECTORY_SEPARATOR . $file;
		$image = imagecreatefromjpeg($image_path);
		if ($image) {
			$image_size = getimagesize($image_path);  
			$dest_x = $padding; //$image_size[0] - $watermark_width - $padding;  
			$dest_y = $padding; //$image_size[1] - $watermark_height - $padding;
	
			// copy watermark on main image
			imagecopymerge_alpha($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height, $opacity);	
			$image_name = "water_" . $file;
			imagejpeg($image, "{$argv[2]}{$image_name}");	
		}
	}
}

imagedestroy($image);  
imagedestroy($watermark);
print "Done.\n\n"; 
