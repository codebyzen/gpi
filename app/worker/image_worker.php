<?php
namespace gpi;

/* 
 * Class: gpiimage
 * Name: gpiImage
 * Description: Class for prepare images to post into social networks.
 * It can resize, make blurred background, overflow watermark
 * Author: Eugene Che
 * Email: dsda@dsda.ru
 * Version: 0.1 beta
 */

class image_worker {
	
	public $result = '';
	
	protected function gd_imageflip($image, $mode) {
		if (function_exists('imageflip')) {
			return imageflip($image, $mode);
		}
		$new_width = $src_width = imagesx($image);
		$new_height = $src_height = imagesy($image);
		$new_img = imagecreatetruecolor($new_width, $new_height);
		$src_x = 0;
		$src_y = 0;
		switch ($mode) {
			case '1': // flip on the horizontal axis
				$src_y = $new_height - 1;
				$src_height = -$new_height;
				break;
			case '2': // flip on the vertical axis
				$src_x = $new_width - 1;
				$src_width = -$new_width;
				break;
			case '3': // flip on both axes
				$src_y = $new_height - 1;
				$src_height = -$new_height;
				$src_x = $new_width - 1;
				$src_width = -$new_width;
				break;
			default:
				return $image;
		}
		imagecopyresampled($new_img, $image, 0, 0, $src_x, $src_y, $new_width, $new_height, $src_width, $src_height);
		return $new_img;
	}

	protected function gd_orient_image($file_path, $src_img) {
	
		if (!function_exists('exif_read_data')) {
			trigger_error('image_worker: gd_orient_image: exif_read_data not exist',E_USER_NOTICE);
			return $src_img;
		}
		$file_mime_type = mime_content_type($file_path);
		if ($file_mime_type == 'image/jpeg' || $file_mime_type == 'image/tiff') {
			$exif = @exif_read_data($file_path);
		} else {
			$exif = $src_img;
		}
		if ($exif === false) {
			//trigger_error('image_worker: gd_orient_image: exif is false',E_USER_NOTICE);
			return $src_img;
		}
		if (isset($exif['Orientation'])) {
			$orientation = (int) @$exif['Orientation'];
		} else {
			$orientation = 0;
		}
		if ($orientation < 2 || $orientation > 8) {
			//trigger_error('image_worker: gd_orient_image: no reorient is needed',E_USER_NOTICE);
			return $src_img;
		}
		switch ($orientation) {
			case 2:
				$new_img = $this->gd_imageflip($src_img, defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2);
				break;
			case 3:
				$new_img = imagerotate($src_img, 180, 0);
				break;
			case 4:
				$new_img = $this->gd_imageflip($src_img, defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1);
				break;
			case 5:
				$tmp_img = $this->gd_imageflip($src_img, defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1);
				$new_img = imagerotate($tmp_img, 270, 0);
				imagedestroy($tmp_img);
				break;
			case 6:
				$new_img = imagerotate($src_img, 270, 0);
				break;
			case 7:
				$tmp_img = $this->gd_imageflip($src_img, defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2);
				$new_img = imagerotate($tmp_img, 270, 0);
				imagedestroy($tmp_img);
				break;
			case 8:
				$new_img = imagerotate($src_img, 90, 0);
				break;
			default:
				trigger_error('image_worker: gd_orient_image: orientation is incorrect ('.$orientation.')',E_USER_NOTICE);
				$new_img = $src_img;
		}
		return $new_img;
	}

	/**
	 * Create scaled image from $file_name. $version influence to file new name
	 * @param string $sFile Path to file
	 * @param array $options with [jpeg_quality[0-100], auto_orient[boolean], crop[boolean], max_width, max_height]
	 * @return Image resource
	 */
	protected function gd_create_scaled_image($sFile=null, $options) {
		
	
		$type = pathinfo($sFile, PATHINFO_EXTENSION);
		$name = pathinfo($sFile, PATHINFO_FILENAME);
		
		switch ($type) {
			case 'jpg':
			case 'jpeg':
				$src_func = 'imagecreatefromjpeg';
				$image_quality = isset($options['jpeg_quality']) ? $options['jpeg_quality'] : 75;
				$tnExt = 'jpg';
				break;
			case 'gif':
				$src_func = 'imagecreatefromgif';
				$image_quality = isset($options['jpeg_quality']) ? $options['jpeg_quality'] : 75; //$image_quality = null;
				$tnExt = 'jpg';
				break;
			case 'png':
				$src_func = 'imagecreatefrompng';
				$image_quality = isset($options['jpeg_quality']) ? $options['jpeg_quality'] : 75; //$image_quality = isset($options['png_quality']) ? $options['png_quality'] : 9;
				$tnExt = 'jpg';
				break;
			default:
				return false;
		}
		
		$src_img = $src_func($sFile);
			
		$image_oriented = false;
		if (!empty($options['auto_orient'])) {
			$src_img = $this->gd_orient_image($sFile, $src_img);
		}
		
		$max_width = $img_width = imagesx($src_img);
		$max_height = $img_height = imagesy($src_img);
		if (!empty($options['max_width'])) {
			$max_width = $options['max_width'];
		}
		if (!empty($options['max_height'])) {
			$max_height = $options['max_height'];
		}
		$scale = min($max_width / $img_width, $max_height / $img_height);
		if ($scale >= 1) {
			//return $src_img; //XXX: if uncomment lower images will not be upscaled to 1080 on longest dimention
		}
		if (empty($options['crop'])) {
			$new_width = $img_width * $scale;
			$new_height = $img_height * $scale;
			$dst_x = 0;
			$dst_y = 0;
			$new_img = imagecreatetruecolor($new_width, $new_height);
		} else {
			if (($img_width / $img_height) >= ($max_width / $max_height)) {
				$new_width = $img_width / ($img_height / $max_height);
				$new_height = $max_height;
			} else {
				$new_width = $max_width;
				$new_height = $img_height / ($img_width / $max_width);
			}
			$dst_x = 0 - ($new_width - $max_width) / 2;
			$dst_y = 0 - ($new_height - $max_height) / 2;
			$new_img = imagecreatetruecolor($max_width, $max_height);
		}
		// Handle transparency in GIF and PNG images:
		/*
		switch ($type) {
			case 'gif':
			case 'png':
				imagecolortransparent($new_img, imagecolorallocate($new_img, 0, 0, 0));
			case 'png':
				imagealphablending($new_img, false);
				imagesavealpha($new_img, true);
				break;
		}
		*/
		$success = imagecopyresampled($new_img, $src_img, $dst_x, $dst_y, 0, 0, $new_width, $new_height, $img_width, $img_height);
		
		imagedestroy($src_img);
		return $new_img;
	}	
	
	function watermark($image, $watermark){
		$stamp = imagecreatefrompng($watermark);

		$marge_right = 10;
		$marge_bottom = 10;
		$sx = imagesx($stamp);
		$sy = imagesy($stamp);

		imagecopy($image, $stamp, imagesx($image) - $sx - $marge_right, imagesy($image) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
		imagedestroy($stamp);
		return $image;
	}
	
	
	/**
	 * @param type $in_path
	 * @param type $out_path
	 * @param array $options array('jpeg_quality'=>0..100, 'max_width'=>int, 'max_height'=>int, 'auto_orient'=>boolean], 'watermark_path'=>string)
	 */
	function __construct(string $in_path=null, string $out_path=null, array $options=NULL) {

		
		
		if (!file_exists($in_path)) {
			$this->result = 'image_worker: input file not found ['.$in_path.']';
		}
		
		$presetted_options = array('jpeg_quality'=>75, 'auto_orient'=>true, 'max_width'=>1080, 'max_height'=>1080, 'watermark_path'=>false);
		
		$options = array_merge($presetted_options, $options);
		

		$image = $this->gd_create_scaled_image($in_path, $options);

		if ($image===false) {
			$this->result = 'image_worker: gd_create_scaled_image error with ['.$in_path.']';
		} else {
			if ($options['watermark_path']!==false && file_exists($options['watermark_path'])) {
				$image = $this->watermark($image, $options['watermark_path']);
			}
			imagejpeg($image, $out_path, 100);
			$this->result = 'ok';
			$this->file_info = ['name'=> basename($out_path),'type'=>'image', 'container'=>'jpg', 'codec'=>'jpeg', 'duration'=>0];
		}
		
	}
	
}