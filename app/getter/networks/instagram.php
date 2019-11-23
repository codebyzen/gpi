<?php
namespace gpi\getter;

use InstagramAPI\InstagramID;
use InstagramAPI\Utils;

class instagram {
	
	public $result;
	private $credentials;
	private $temp_path;
	
	function __construct(string $url, array $credentials, string $path) {
		
		$url = filter_var($url, FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"/^([a-z0-9_-]+)$/is")));
		
//		$catcher = new \dsda\catcher\catcher();
//		$catcher->debug(['/getter/instagram/construct ... url'=>$url]);
		
		if ($url===NULL || $url===false) {
			throw new \Exception("No media code!", 0);
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
	 * Get data from instagram CDN servers
	 * @param array $options
	 * @return array
	 * @throws \Exception
	 */
	private function curl_get($options=false) {

		$opt = array(
			'referer' => false,
			'url' => 'localhost',
			'useragent' => '',
			'headers' => array(),
			'post'=> false,
		);

		if ($options!==false) $opt = array_merge($opt, $options);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $opt['url']); // set URL
		curl_setopt($ch, CURLOPT_HEADER, false); // exclude header from output
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // return data from request
		curl_setopt($ch, CURLOPT_USERAGENT, $opt['useragent']);
		curl_setopt($ch, CURLOPT_REFERER, $opt['referer']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $opt['headers']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if (isset($opt['post']) && $opt['post']!=false) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $opt['post']);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		$info = ['data'=>false,'info'=>['http_code'=>0]];

		if (curl_errno($ch) != 0) {
			return $info;
		}
		$ret['data'] = curl_exec($ch);
		$ret['info'] = curl_getinfo($ch);
		curl_close($ch);
		if ($ret['info']['http_code'] == 200) {
			return $ret;
		} else {
			throw new \Exception('Error access to URL [http-code:'.$ret['info']['http_code'].']: '.$opt['url'], 0);
		}
	}
	
	/**
	 * Get files from url and place it to disk
	 * @param array $files
	 * @return string
	 */
	private function get_item_files($files){
		$files_on_disk = array();
		foreach($files as $k=>$v) {
			$opt = array(
				'referer' => false,
				'url' => $v['url'],
				'useragent' => "23/6.0.1; 640dpi; 1440x2560; ZTE; ZTE A2017U; ailsa_ii; qcom",
				'headers' => false,
			);

			$result = $this->curl_get($opt);
			if ($result['data']!==false) {
				$filename = time()."_".rand(10,99)."_".rand(10,99).".".$v['type'];
				file_put_contents($this->temp_path.$filename, $result['data']);
				$files_on_disk[] = $filename;
			}
		}
		return $files_on_disk;
	}

	/**
	 * Get files URL from PHOTO, VIDEO, ALBUM type of Item
	 * @param String $media_type
	 * @param /InstagramAPI/Media $item
	 * @return Array
	 */
	function get_media_url($media_type, $item) {
		$file = [];
		if ($media_type == 'PHOTO') {
			$file = array('url'=>$item->getImageVersions2()->getCandidates()[0]->getUrl(), 'type'=>'jpg');
		} elseif ($media_type == 'VIDEO') {
			$file = array('url'=>$item->getVideoVersions()[0]->getUrl(), 'type'=>'mp4');
		} elseif ($media_type == 'ALBUM') {
			$carousel = $item->getCarouselMedia();
			foreach($carousel as $carousel_object) {
				$media_type = Utils::checkMediaType($carousel_object->getMediaType());
				$file[] = $this->get_media_url($media_type, $carousel_object);
			}
		}
		return $file;
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
	 * Return array: location, string: url, string: ext, array: files, string: source, string: description, array: tags
	 * @return array
	 */
	private function files_get($shortcode){
		$ig = $this->insta_login();
		
		$mediaId = InstagramID::fromCode($shortcode);
		
		$itemInfo = $ig->media->getInfo($mediaId);
		
		$files = [];
		//TODO: почему перечисление в массив? там ведь даже при каруселе всего один айтем где все лежит... может для сторис???
		foreach ($itemInfo->getItems() as $item) {
			$media_type = Utils::checkMediaType($item->getMediaType());
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
						
			$files = $this->get_media_url($media_type, $item);
		}
		
		//XXX: закончил тут 13 апреля 2019 (суббота)
		$files_on_disk = $this->get_item_files($files);
		
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