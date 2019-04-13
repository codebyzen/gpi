<?php
namespace gpi\getter;

class coub {
	
	public $result = null;
	private $temp_path = null;

	function __construct(string $url, array $credentials, string $path) {
		$url = filter_var($url, FILTER_VALIDATE_URL);
		if ($url===NULL || $url===false) {
			throw new \Exception("No url!", 0);
		}
		if (!file_exists( dirname($path) )) {
			throw new \Exception("No upload path!", 0);
		} else {
			$this->temp_path = $path;
		}
		
		$result = [];
		$result = $this->get_json_data($url);
				
		$urls = array(
			'video'=>$json_result['video'],
			'audio'=>$json_result['audio'],
		);
		
		$result['files'] = $this->download_files($urls);
		
		$this->result = $result;
		
	}
	
	
	function curl_get($options=false) {

		$opt = array(
			'referer' => false,
			'url' => 'localhost',
			'useragent' => '',
			'headers' => array(),
		);

		if ($options!==false) $opt = array_merge($opt, $options);

		// print_r($opt);
		// echo PHP_EOL;

		// create a new cURL resource
		$ch = curl_init();

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $opt['url']); // set URL
		curl_setopt($ch, CURLOPT_HEADER, false); // exclude header from output
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // return data from request
		curl_setopt($ch, CURLOPT_USERAGENT, $opt['useragent']);
		curl_setopt($ch, CURLOPT_REFERER, $opt['referer']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $opt['headers']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if (isset($post) && $post!=false) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_USERAGENT, '');

		// grab URL and pass it to the browser
		if (curl_errno($ch) != 0) return false;
		$raw = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] == 200) {
			return $raw;
		} else {
			throw new \Exception('Error access to URL [http-code:'.$info['http_code'].']: '.$opt['url'], 0);
		}
	}
	
	function get_json_data($url) {
		// OLD!!!
		//		$options = array(
		//			'referer'=> 'coub.com',
		//			'url' => $url,
		//			'useragent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36',
		//		);
		//		
		//		$html = $this->curl_get($options);
		//		
		//		preg_match("/<script id='coubPageCoubJson'[^>]+>(.*)<\/script>/Usi",$html,$m);
		//		if (isset($m[1])) {
		//			$json = json_decode($m[1]);
		//		} else {
		//			throw new \Exception('Coub JSON not exist!');
		//		}
		
		$url_parsed = parse_url($url);
		$url_parsed = basename($url_parsed['path']);
		$media_code = trim($url_parsed);
		
		$options = array(
			'referer'=> 'coub.com',
			'url' => 'http://coub.com/api/v2/coubs/'.$media_code.'.json',
			'useragent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36',
		);

		$json = $this->curl_get($options);
		
		
		$json = json_decode($json);

		$tags = array();
		foreach($json->tags as $k=>$v) {
			$tag = str_replace(' ','',$v->title);
			$tag = strip_tags($tag);
			preg_match_all("/([а-яА-ЯЁёa-zA-Z0-9_]+)/iu", trim($tag), $tags_cleared);
			if (isset($tags_cleared[1])){
				$tag = $tags_cleared[1][0];
			}else{
				continue;
			}
			$tags[] = $tag;
			$tags = array_unique($tags);
		}
		
		if (isset($json->file_versions->html5->video->high)) {
			$v = $json->file_versions->html5->video->high->url;
		}elseif(isset($json->file_versions->html5->video->med)){
			$v = $json->file_versions->html5->video->med->url;
		}elseif(isset($json->file_versions->html5->video->low)){
			$v = $json->file_versions->html5->video->low->url;
		}else{
			$v = false;
		}
		
		
		if (isset($json->file_versions->html5->audio->high)) {
			$a = $json->file_versions->html5->audio->high->url;
		}elseif(isset($json->file_versions->html5->audio->med)){
			$a = $json->file_versions->html5->audio->med->url;
		}elseif(isset($json->file_versions->html5->audio->low)){
			$a = $json->file_versions->html5->audio->low->url;
		}else{
			$a = false;
		}
		
		
		$grabber_result = array(
			'video' => $v,
			'audio' => $a,
			'tags' => $tags,
			'description' => $json->title,
			'source' => 'Coub - '.$json->channel->title,
		);
		
		return $grabber_result;
	}

	function download_files($urls){
		$tmpfname = time().'_'.rand(10,99).'_'.rand(10,99);
		$files = array();
		
		foreach($urls as $type => $url) {
			$options = array(
				'referer'=> 'coub.com',
				'url' => $url,
				'useragent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36',
			);
		
			$data = $this->curl_get($options);
			
			if ($type=='video') {
				$array = unpack('v*', $data);
				if ($array[1]=='19392') {
					$data[0] = chr(0);
					$data[1] = chr(0);
				}
				file_put_contents($this->temp_path.$tmpfname.'.mp4', $data);
				$files['video'] = $tmpfname.'.mp4';
			} else {
				file_put_contents($this->temp_path.$tmpfname.'.mp3', $data);
				$files['audio'] = $tmpfname.'.mp3';
			}
			
		}
		return $files;
	}
	
	
	
}