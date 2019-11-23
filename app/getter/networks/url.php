<?php
namespace gpi\getter;

class url {
	
	public $result = null;
	
	function __construct(string $url, array $credentials, string $path) {
		
		$catcher = new \dsda\catcher\catcher();
		$catcher->debug([$url, $credentials, $path]);
		
//		if (!file_exists($url)) {
//			throw new \Exception('gpi\getter\url: No URL found! ('.$url.')', 0);
//		}
//		if (!is_readable($url)) {
//			throw new \Exception('gpi\getter\url: URL readable! ('.$url.')', 0);
//		}
		if (!file_exists( dirname($path) )) {
			throw new \Exception('gpi\getter\url: No upload path! ('.$path.')', 0);
		}
	
		$url_parsed = parse_url($url);
		$source = $url_parsed['host'];
		$remotefilename = basename($url_parsed['path']);
		$remotefileext = pathinfo($remotefilename, PATHINFO_EXTENSION);


		$handle = fopen($url, "rb");
		if (FALSE === $handle) {
			throw new \Exception('gpi\getter\url: Can not open stream! ('.$url.')', 0);
		}

		$filename = time()."_".rand(10,99)."_".rand(10,99).".".$remotefileext;
		
		$contents = '';
		while (!feof($handle)) {
			$contents = fread($handle, 8192);
			file_put_contents($path.$filename, $contents, FILE_APPEND);
		}
		fclose($handle);
	
		
		
		$result = array(
			'location' => $url,
			'files' => [$path.$filename],
			'source' => $source
		);
		
		$this->result = $result;
		
	}

}