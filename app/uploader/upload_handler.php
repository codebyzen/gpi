<?php

/*
 * jQuery File Upload Plugin PHP Class
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

if (!defined('AUTHED')) {
	exit();
}

class UploadHandler {

	protected $options;
	public $result_of_script = false;
	// PHP File Upload error message codes:
	// http://php.net/manual/en/features.file-upload.errors.php
	protected $error_messages = array(
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Missing a temporary folder',
		7 => 'Failed to write file to disk',
		8 => 'A PHP extension stopped the file upload',
		'post_max_size' => 'The uploaded file exceeds the post_max_size directive in php.ini',
		'max_file_size' => 'File is too big',
		'min_file_size' => 'File is too small',
		'max_number_of_files' => 'Maximum number of files exceeded',
		'max_width' => 'Image exceeds maximum width',
		'min_width' => 'Image requires a minimum width',
		'max_height' => 'Image exceeds maximum height',
		'min_height' => 'Image requires a minimum height',
		'abort' => 'File upload aborted',
		'image_resize' => 'Failed to resize image'
	);
	protected $image_objects = array();

	public function __construct($options = null, $initialize = true, $error_messages = null) {
		$this->response = array();
		$this->options = array(
			'script_url' => $this->get_full_url() . '/' . $this->basename($this->get_server_var('SCRIPT_NAME')),
			'upload_dir' => dirname($this->get_server_var('SCRIPT_FILENAME')) . '/files/',
			'upload_url' => $this->get_full_url() . '/files/',
			'access_control_allow_origin' => '*',
			'access_control_allow_credentials' => false,
			'access_control_allow_methods' => array(
				'OPTIONS',
				'HEAD',
				'GET',
				'POST',
				'PUT',
				'PATCH'
			),
			'access_control_allow_headers' => array(
				'Content-Type',
				'Content-Range',
				'Content-Disposition'
			),
			// By default, allow redirects to the referer protocol+host:
			'redirect_allow_target' => '/^' . preg_quote(
					parse_url($this->get_server_var('HTTP_REFERER'), PHP_URL_SCHEME)
					. '://'
					. parse_url($this->get_server_var('HTTP_REFERER'), PHP_URL_HOST)
					. '/', // Trailing slash to not match subdomains by mistake
					'/' // preg_quote delimiter param
			) . '/',
			// Read files in chunks to avoid memory limits when download_via_php
			// is enabled, set to 0 to disable chunked reading of files:
			'readfile_chunk_size' => 10 * 1024 * 1024, // 10 MiB
			// The php.ini settings upload_max_filesize and post_max_size
			// take precedence over the following max_file_size setting:
			'max_file_size' => null,
			// The maximum number of files for the upload directory:
			'max_number_of_files' => null,
			// Defines which files are handled as image files:
			'image_file_types' => '/\.(gif|jpe?g|png)$/i',
			// Use exif_imagetype on all files to correct file extensions:
			'correct_image_extensions' => false,
			// Set the following option to false to enable resumable uploads:
			'discard_aborted_uploads' => true,
			'image_versions' => array(
				// The empty image version key defines options for the original image.
				// Keep in mind: these image manipulations are inherited by all other image versions from this point onwards. 
				// Also note that the property 'no_cache' is not inherited, since it's not a manipulation.
				'' => array(
					// Automatically rotate images based on EXIF meta data:
					'auto_orient' => true
				),
				'thumbnail' => array(
					// Uncomment the following to use a defined directory for the thumbnails
					// instead of a subdirectory based on the version identifier.
					// Make sure that this directory doesn't allow execution of files if you
					// don't pose any restrictions on the type of uploaded files, e.g. by
					// copying the .htaccess file from the files directory for Apache:
					//'upload_dir' => dirname($this->get_server_var('SCRIPT_FILENAME')).'/thumb/',
					//'upload_url' => $this->get_full_url().'/thumb/',
					// Uncomment the following to force the max
					// dimensions and e.g. create square thumbnails:
					// 'auto_orient' => true,
					// 'crop' => true,
					// 'jpeg_quality' => 70,
					// 'no_cache' => true, (there's a caching option, but this remembers thumbnail sizes from a previous action!)
					// 'strip' => true, (this strips EXIF tags, such as geolocation)
					'max_width' => 80, // either specify width, or set to 0. Then width is automatically adjusted - keeping aspect ratio to a specified max_height.
					'max_height' => 80 // either specify height, or set to 0. Then height is automatically adjusted - keeping aspect ratio to a specified max_width.
				)
			),
			'print_response' => true
		);
		if ($options) {
			$this->options = $options + $this->options;
		}
		if ($error_messages) {
			$this->error_messages = $error_messages + $this->error_messages;
		}
		if ($initialize) {
			$this->initialize();
		}
	}

	protected function initialize() {
		$result = false;
		switch ($this->get_server_var('REQUEST_METHOD')) {
			case 'OPTIONS':
			case 'HEAD':
				$this->head();
				$this->options['print_response'] = false;
				break;
			case 'GET':
				$result = $this->get($this->options['print_response']);
				break;
			case 'PATCH':
			case 'PUT':
			case 'POST':
				$result = $this->post($this->options['print_response']);
				break;
			default:
				$this->options['print_response'] = false;
				header('HTTP/1.1 405 Method Not Allowed');
		}
		$this->result_of_script = array('result' => $result, 'print' => $this->options['print_response']);
	}

	protected function get_full_url() {
		$https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0 ||
				!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
				strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
		return
				($https ? 'https://' : 'http://') .
				(!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] . '@' : '') .
				(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] .
				($https && $_SERVER['SERVER_PORT'] === 443 ||
				$_SERVER['SERVER_PORT'] === 80 ? '' : ':' . $_SERVER['SERVER_PORT']))) .
				substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
	}

	protected function get_upload_path($file_name = null, $version = null) {
		if ($file_name == null || $file_name == false)
			$file_name = '';
		if (empty($version)) {
			$version_path = '';
		} else {
			$version_dir = isset($this->options['image_versions'][$version]['upload_dir']) ? $this->options['image_versions'][$version]['upload_dir'] : false;
			if ($version_dir) {
				return $version_dir . $file_name;
			}
			$version_path = $version . '/';
		}
		return $this->options['upload_dir'] . $version_path . $file_name;
	}

	
	protected function get_query_separator($url) {
		return strpos($url, '?') === false ? '?' : '&';
	}

	// Fix for overflowing signed 32 bit integers,
	// works for sizes up to 2^32-1 bytes (4 GiB - 1):
	protected function fix_integer_overflow($size) {
		if ($size < 0) {
			$size += 2.0 * (PHP_INT_MAX + 1);
		}
		return $size;
	}

	protected function get_file_size($file_path, $clear_stat_cache = false) {
		if ($clear_stat_cache) {
			if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
				clearstatcache(true, $file_path);
			} else {
				clearstatcache();
			}
		}
		return $this->fix_integer_overflow(filesize($file_path));
	}

	protected function is_valid_file_object($file_name) {
		$file_path = $this->get_upload_path($file_name);
		if (is_file($file_path) && $file_name[0] !== '.') {
			return true;
		}
		return false;
	}

	protected function get_file_object($file_name) {
		if ($this->is_valid_file_object($file_name)) {
			$file = new \stdClass();
			$file->name = $file_name;
			$file->size = $this->get_file_size(
					$this->get_upload_path($file_name)
			);
			$file->url = $this->options['upload_url'].$file->name;
			foreach ($this->options['image_versions'] as $version => $options) {
				if (!empty($version)) {
					if (is_file($this->get_upload_path($file_name, $version))) {
						$file->$version = $file->name;
					}
				}
			}

			return $file;
		}
		return null;
	}

	protected function get_file_objects($iteration_method = 'get_file_object') {
		$upload_dir = $this->get_upload_path();
		if (!is_dir($upload_dir)) {
			return array();
		}
		return array_values(array_filter(array_map(
								array($this, $iteration_method), scandir($upload_dir)
		)));
	}

	protected function count_file_objects() {
		return count($this->get_file_objects('is_valid_file_object'));
	}

	protected function get_error_message($error) {
		return isset($this->error_messages[$error]) ? $this->error_messages[$error] : $error;
	}

	public function get_config_bytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		$val = (int) $val;
		switch ($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $this->fix_integer_overflow($val);
	}

	
	/**
	 * Check for all allowed params type, size, postsize files max count, already uploaded, width, height and else...
	 * @param string $uploaded_file
	 * @param string $file
	 * @param string $error
	 * @return boolean
	 */
	protected function validate($uploaded_file, $file, $error) {
		// Return error message if message present
		if ($error) {
			$file->error = $this->get_error_message($error);
			return false;
		}
		// fix content length
		$content_length = $this->fix_integer_overflow((int) $this->get_server_var('CONTENT_LENGTH'));
		$post_max_size = $this->get_config_bytes(ini_get('post_max_size'));
		
		// check for max post size
		if ($post_max_size && ($content_length > $post_max_size)) {
			$file->error = $this->get_error_message('post_max_size');
			return false;
		}
		
		// check for file already uploaded
		if ($uploaded_file && is_uploaded_file($uploaded_file)) {
			$file_size = $this->get_file_size($uploaded_file);
		} else {
			$file_size = $content_length;
		}
		
		// check for max file size
		if ($this->options['max_file_size'] && ($file_size > $this->options['max_file_size'] || $file->size > $this->options['max_file_size'])) {
			$file->error = $this->get_error_message('max_file_size');
			return false;
		}
		
		// fheck for min file size
		if ($file_size < 1) {
			$file->error = $this->get_error_message('min_file_size');
			return false;
		}
		
		// check for max files number
		if (is_int($this->options['max_number_of_files']) &&
				($this->count_file_objects() >= $this->options['max_number_of_files']) &&
				// Ignore additional chunks of existing files:
				!is_file($this->get_upload_path($file->name))) {
			$file->error = $this->get_error_message('max_number_of_files');
			return false;
		}
		
		// all ok
		return true;
	}

	protected function upcount_name_callback($matches) {
		$index = isset($matches[1]) ? ((int) $matches[1]) + 1 : 1;
		$ext = isset($matches[2]) ? $matches[2] : '';
		return ' (' . $index . ')' . $ext;
	}

	protected function upcount_name($name) {
		return preg_replace_callback(
				'/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/', array($this, 'upcount_name_callback'), $name, 1
		);
	}

	protected function get_unique_filename($name, $content_range) {
		while (is_dir($this->get_upload_path($name))) {
			$name = $this->upcount_name($name);
		}
		// Keep an existing filename if this is part of a chunked upload:
		$uploaded_bytes = $this->fix_integer_overflow((int) $content_range[1]);
		while (is_file($this->get_upload_path($name))) {
			if ($uploaded_bytes === $this->get_file_size($this->get_upload_path($name))) {
				break;
			}
			$name = $this->upcount_name($name);
		}
		return $name;
	}


	protected function trim_file_name($name) {
		// Remove path information and dots around the filename, to prevent uploading
		// into different directories or replacing hidden system files.
		// Also remove control characters and spaces (\x00..\x20) around the filename:
		$name = trim($this->basename(stripslashes($name)), ".\x00..\x20");
		// Use a timestamp for empty filenames:
		if (!$name) {
			$name = str_replace('.', '-', microtime(true));
		}
		return $name;
	}

	protected function get_file_name($file_path, $name, $size, $type, $error, $index, $content_range) {
		$name = $this->trim_file_name($name);
		return $this->get_unique_filename($name, $content_range);
	}




	protected function get_image_size($file_path) {
		if (!function_exists('getimagesize')) {
			trigger_error('upload_handler: get_image_size: Function not found: getimagesize', E_USER_NOTICE);
			return false;
		}
		if (file_exists($file_path)) {
			$dim = @getimagesize($file_path);
			return array($dim[0], $dim[1]);
		} else {
			return false;
		}
	}

	protected function is_valid_image_file($file_path) {
		if (!preg_match($this->options['image_file_types'], $file_path)) {
			return false;
		}
		if (function_exists('exif_imagetype')) {
			return @exif_imagetype($file_path);
		}
		$image_info = $this->get_image_size($file_path);
		return $image_info && $image_info[0] && $image_info[1];
	}

	protected function handle_image_file($file_path, $file) {
		$failed_versions = array();
		foreach ($this->options['image_versions'] as $version => $options) {
			if ($this->gd_create_scaled_image($file->name, $version, $options)) {
				if (!empty($version)) {
					$file->$version = $file->name;
				} else {
					$file->size = $this->get_file_size($file_path, true);
				}
			} else {
				$failed_versions[] = $version ? $version : 'original';
			}
		}
		if (count($failed_versions)) {
			$file->error = $this->get_error_message('image_resize')	. ' (' . implode($failed_versions, ', ') . ')';
		}
	}

	protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
		$file = new \stdClass();
		$file->name = $this->get_file_name($uploaded_file, $name, $size, $type, $error, $index, $content_range);
		$file->size = $this->fix_integer_overflow((int) $size);
		$file->type = $type;
		
		//ob_start();print_r($file);trigger_error(ob_get_contents(), E_USER_NOTICE);ob_end_clean();
		
		if ($this->validate($uploaded_file, $file, $error)) {
			$upload_dir = $this->get_upload_path();
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0755, true);
			}
			$file_path = $this->get_upload_path($file->name);
			$append_file = $content_range && is_file($file_path) && $file->size > $this->get_file_size($file_path);
			if ($uploaded_file && is_uploaded_file($uploaded_file)) {
				// multipart/formdata uploads (POST method uploads)
				if ($append_file) {
					file_put_contents($file_path, fopen($uploaded_file, 'r'), FILE_APPEND);
				} else {
					move_uploaded_file($uploaded_file, $file_path);
				}
			} else {
				// Non-multipart uploads (PUT method support)
				file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0 );
			}
			$file_size = $this->get_file_size($file_path, $append_file);
			if ($file_size === $file->size) {
				// file uploaded success
				$file->url = $this->options['upload_url'].$file->name;
				$file->uploaded_flag = true;
			} else {
				$file->size = $file_size;
				if (!$content_range && $this->options['discard_aborted_uploads']) {
					unlink($file_path);
					$file->error = $this->get_error_message('abort');
				}
			}

		}
		return $file;
	}

	protected function readfile($file_path) {
		$file_size = $this->get_file_size($file_path);
		$chunk_size = $this->options['readfile_chunk_size'];
		if ($chunk_size && $file_size > $chunk_size) {
			$handle = fopen($file_path, 'rb');
			while (!feof($handle)) {
				echo fread($handle, $chunk_size);
				@ob_flush();
				@flush();
			}
			fclose($handle);
			return $file_size;
		}
		return readfile($file_path);
	}

	protected function get_post_param($id) {
		if (isset($_POST[$id])) {
			return @$_POST[$id];
		} else {
			return false;
		}
	}

	protected function get_query_param($id) {
		if (isset($_GET[$id])) {
			return @$_GET[$id];
		} else {
			return false;
		}
	}

	protected function get_server_var($id) {
		if (isset($_SERVER[$id])) {
			return @$_SERVER[$id];
		} else {
			return false;
		}
	}


	protected function get_version_param() {
		return $this->basename(stripslashes($this->get_query_param('version')));
	}

	protected function get_singular_param_name() {
		return substr('files', 0, -1);
	}

	protected function get_file_name_param() {
		$name = $this->get_singular_param_name();
		return $this->basename(stripslashes($this->get_query_param($name)));
	}

	protected function get_file_names_params() {
		$params = $this->get_query_param('files');
		if (!$params) {
			return null;
		}
		foreach ($params as $key => $value) {
			$params[$key] = $this->basename(stripslashes($value));
		}
		return $params;
	}

	protected function get_file_type($file_path) {
		switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
			case 'jpeg':
			case 'jpg':
				return 'image/jpeg';
			case 'png':
				return 'image/png';
			case 'gif':
				return 'image/gif';
			default:
				return '';
		}
	}


	protected function send_content_type_header() {
		header('Vary: Accept');
		if (strpos($this->get_server_var('HTTP_ACCEPT'), 'application/json') !== false) {
			header('Content-type: application/json');
		} else {
			header('Content-type: text/plain');
		}
	}

	protected function send_access_control_headers() {
		header('Access-Control-Allow-Origin: ' . $this->options['access_control_allow_origin']);
		header('Access-Control-Allow-Credentials: '
				. ($this->options['access_control_allow_credentials'] ? 'true' : 'false'));
		header('Access-Control-Allow-Methods: '
				. implode(', ', $this->options['access_control_allow_methods']));
		header('Access-Control-Allow-Headers: '
				. implode(', ', $this->options['access_control_allow_headers']));
	}

	public function generate_response($content, $print_response = true) {
		$this->response = $content;
		if ($print_response) {
			$json = json_encode($content);
			$redirect = stripslashes($this->get_post_param('redirect'));
			if ($redirect && preg_match($this->options['redirect_allow_target'], $redirect)) {
				header('Location: ' . sprintf($redirect, rawurlencode($json)));
				return;
			}
			$this->head();
			if ($this->get_server_var('HTTP_CONTENT_RANGE')) {
				$files = isset($content['files']) ? $content['files'] : null;
				if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
					header('Range: 0-' . ($this->fix_integer_overflow((int) $files[0]->size) - 1));
				}
			}
			echo $json;
		}
		return $content;
	}

	public function head() {
		header('Pragma: no-cache');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Content-Disposition: inline; filename="files.json"');
		// Prevent Internet Explorer from MIME-sniffing the content-type:
		header('X-Content-Type-Options: nosniff');
		if ($this->options['access_control_allow_origin']) {
			$this->send_access_control_headers();
		}
		$this->send_content_type_header();
	}

	public function get($print_response = true) {
		$file_name = $this->get_file_name_param();
		if ($file_name) {
			$response = array(
				$this->get_singular_param_name() => $this->get_file_object($file_name)
			);
		} else {
			$response = array(
				'files' => $this->get_file_objects()
			);
		}
		return array($response, $print_response);
	}

	public function post($print_response = true) {
		$upload = @$_FILES['files'];
		// Parse the Content-Disposition header, if available:
		$content_disposition_header = $this->get_server_var('HTTP_CONTENT_DISPOSITION');
		$file_name = $content_disposition_header ? rawurldecode(preg_replace('/(^[^"]+")|("$)/', '', $content_disposition_header)) : null;
		// Parse the Content-Range header, which has the following form:
		// Content-Range: bytes 0-524287/2000000
		$content_range_header = $this->get_server_var('HTTP_CONTENT_RANGE');
		$content_range = $content_range_header ? preg_split('/[^0-9]+/', $content_range_header) : null;
		$size = $content_range ? $content_range[3] : null;
		$files = array();
		if ($upload) {
			if (is_array($upload['tmp_name'])) {
				// param_name is an array identifier like "files[]",
				// $upload is a multi-dimensional array:
				foreach ($upload['tmp_name'] as $index => $value) {
					$files[] = $this->handle_file_upload(
							$upload['tmp_name'][$index], $file_name ? $file_name : $upload['name'][$index], $size ? $size : $upload['size'][$index], $upload['type'][$index], $upload['error'][$index], $index, $content_range
					);
				}
			} else {
				// param_name is a single object identifier like "file",
				// $upload is a one-dimensional array:
				$files[] = $this->handle_file_upload(
						isset($upload['tmp_name']) ? $upload['tmp_name'] : null, $file_name ? $file_name : (isset($upload['name']) ?
						$upload['name'] : null), $size ? $size : (isset($upload['size']) ?
						$upload['size'] : $this->get_server_var('CONTENT_LENGTH')), isset($upload['type']) ?
						$upload['type'] : $this->get_server_var('CONTENT_TYPE'), isset($upload['error']) ? $upload['error'] : null, null, $content_range
				);
			}
		}
		$response = array('files' => $files);
		return array($response, $print_response);
	}

	protected function basename($filepath, $suffix = null) {
		$splited = preg_split('/\//', rtrim($filepath, '/ '));
		return substr(basename('X' . $splited[count($splited) - 1], $suffix), 1);
	}

}