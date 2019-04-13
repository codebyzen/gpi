<?php
namespace gpi\getter;

class file {
	
	public $result = null;
	
	function __construct(string $url, array $credentials, string $path) {
		if (!file_exists($url)) {
			throw new \Exception('gpi\getter\file: No local file found! ('.$path.$url.')', 0);
		}
		if (!is_readable($url)) {
			throw new \Exception('gpi\getter\file: Local file is not readable! ('.$path.$url.')', 0);
		}
		if (!file_exists( dirname($path) )) {
			throw new \Exception('gpi\getter\file: No upload path! ('.$path.')', 0);
		}
		
		$result = ['source'=>'','description'=>''];
		
		
		
		$result['files'] = [$url];
		
		$this->result = $result;
		
	}

}