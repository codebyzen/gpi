<?php
namespace dsda\fileinfo;

class fileinfo {
	
	public $input_files = [];
		
	function __construct(array $files=[]) {
		
		// check exist files
		foreach($files as $key=>$file) {
			if (!file_exists($file)) {
				throw new \Exception("FileInfo: Input file - ".$file." not found!", 0);
			}
			if (!is_readable($file)) {
				throw new \Exception("FileInfo: Input file - ".$file." not readable!", 0);
			}
		}
		
		// check media files
		foreach($files as $key=>$file) {
			$media_type = $this->get_media_type($file);
			if ($media_type==false) {
				throw new \Exception("FileInfo: file - ".$file." is unknown type!", 0);
			}
			$this->input_files[] = ['type'=>$media_type[0], 'container'=>$media_type[1]];
			unset($media_type);
		}

	}

	private function get_media_type($file_path){
		$container = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
		$name = strtolower(pathinfo($file_path, PATHINFO_FILENAME));
		
		$types = [];
		$types['video'] = ['mov','mp4','avi','wmv','mpeg','m4v','flv', 'webm', 'mkv', 'vob', 'mts', 'm2ts', 'ps', 'ts', 'm2p', 'mpg', 'm2v', '3gp'];
		$types['audio'] = ['aac', 'aiff', 'm4a', 'm2a', 'mp3', 'ogg', 'wav', 'wma', 'webm'];
		$types['image'] = ['jpg', 'jpeg', 'tiff', 'gif', 'bmp', 'tga', 'png', 'webp'];
		
		foreach($types as $type_key=>$type_extensions) {
			$container = array_search($container, $type_extensions);
			if ($container) {
				return array($type_key, $types[$type_extensions][$container]);
			}
		}
		
		return false;
		
	}
	
}
