<?php
namespace gpi\getter;

class instagram {
	
	public $result;
	private $credentials;
	
	function __construct(string $url, array $credentials, string $path) {
		$url = filter_var($url, FILTER_VALIDATE_URL);
		if ($url===NULL || $url===false) {
			throw new \Exception("No url!", 0);
		}
		
		if (!file_exists( dirname($path) )) {
			throw new \Exception("Upload path not found!",0);
		} else {
			$this->temp_path = $path;
		}
		
		if (!isset($credentials['username']) || empty($credentials['username']) || !isset($credentials['password']) || empty($credentials['password'])) {
			throw new \Exception("Instagram credentials wrong!",0);
		}
		
		$this->credentials = $credentials;
		
		$this->result = $this->files_get($url);
			
		
	}
	
	/**
	 * Uses in $this->insta_location(),
	 * @return \InstagramAPI\Instagram|boolean
	 */
	private function insta_login(){
		
		\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
		$ig = new \InstagramAPI\Instagram(false, false);
		
		
		try {
			$loginResponse = $ig->login($this->credentials['username'], $this->credentials['password']);
			if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
				$twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
				throw new \Exception('Two Factor Identifier required! ('.$twoFactorIdentifier.')',0);
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(),0);
		}
		
		return $ig;
	}
	
	/**
	 * Copy all hashtags from description
	 * @param type $text
	 * @return type
	 */
	private function parse_tags_from_description(string $text): array {
		preg_match_all("/(#([а-яА-ЯЁёa-zA-Z0-9_]+))/iu", $text, $tags);
		$ctags = array();
		if (isset($tags[2])) {
			foreach($tags[2] as $k=>$v) {
				$ctags[] = trim($v);
			}
		}
		return $ctags;
	}
	
	/**
	 * Get short code for access to media
	 * @param type $shortcode
	 * @return type
	 */
	function shortcode_to_mediaid(string $shortcode): string {
		$alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
		$mediaid = 0;
		foreach(str_split($shortcode) as $letter) {
			$mediaid = ($mediaid*64) + strpos($alphabet, $letter);
		}
		return $mediaid;
	}
	
	
	/**
	 * Return array: location, string: url, string: ext, array: files, string: source, string: description, array: tags
	 * @return array
	 */
	private function files_get(){
		$ig = $this->insta_login();
		
		$shortcode = basename(parse_url($url)['path']);
		
		// Hack to short long code
		if (strlen($shortcode)>11) {
			$shortcode = substr($shortcode, 0, 11);
		}
		
		$mediaId = $this->shortcode_to_mediaid($shortcode);

		$itemInfo = $ig->media->getInfo($mediaId);
		
		$files = array();	
		foreach ($itemInfo->getItems() as $item) {
			$media_type = $item->getMediaType();
			$source = $item->getUser()->getUsername();
			$description = ($item->getCaption()!==NULL) ?  $item->getCaption()->getText() : '';
			$tags = $this->parse_tags_from_description($description);
			$location = $item->getLocation();
			$loc_arr = array();
			if ($location) {
				$loc_arr['location_lat'] = $location->getLat();
				$loc_arr['location_lng'] = $location->getLng();
				$loc_arr['location_fbid'] = $location->getFacebookPlacesId();
				$loc_arr['location_name'] = $location->getName();
				$loc_arr['location_addr'] = $location->getAddress();
				$loc_arr['location_city'] = $location->getCity();
				$loc_arr['location_country'] = $location->getCountry();
			}
			
			if ($media_type == 1) {
				// Photo
				$files[] = array('url'=>$item->getImageVersions2()->getCandidates()[0]->getUrl(), 'type'=>'jpg');
			} elseif ($media_type == 2) {
				// Video
				$files[] = array('url'=>$item->getVideoVersions()[0]->getUrl(), 'type'=>'mp4');
			}
		}

		$files_on_disk = array();
		foreach($files as $k=>$v) {
			$result = $this->curl_get($v['url'], false, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36");
			if ($result['type']!=='error') {
				$filename = time()."_".rand(10,99)."_".rand(10,99).".".$v['type'];
				file_put_contents($this->temp_path.$filename, $result['message']);
				$files_on_disk[] = $filename;
			}
		}
		
		$grabber_result = array(
			'location' => $loc_arr,
			'files' => $files_on_disk,
			'source' => $source,
			'description' => $description,
			'tags' => $tags,
		);
		
		return $grabber_result;
	}
	

}