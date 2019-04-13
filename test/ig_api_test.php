<?php
set_time_limit(360);

require __DIR__.'/../vendor/autoload.php';

use InstagramAPI\InstagramID;
use InstagramAPI\Utils;

$catcher = new \dsda\catcher\catcher();

function insta_login(){
	global $catcher;
	\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
	$ig = new \InstagramAPI\Instagram(true, true);


	try {
		$loginResponse = $ig->login('kira.4e', 'u337l0rH');
		if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
			$twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
			$catcher->log('Two Factor Identifier required!');
			return array('type'=>'error','message'=>'Two Factor Identifier required!');
		}
	} catch (\Exception $e) {
		return array('type'=>'error','message'=>$e->getMessage());
	}

	return array('type'=>'success', 'message'=>$ig);
}

function parse_tags_from_description(string $text): array {
	preg_match_all("/(#([а-яА-ЯЁёa-zA-Z0-9_]+))/iu", $text, $tags);
	$ctags = array();
	if (isset($tags[2])) {
		foreach($tags[2] as $k=>$v) {
			$ctags[] = trim($v);
		}
	}
	return $ctags;
}

function curl_get($options=false) {

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

function get_item_files($files){
	$files_on_disk = array();
	foreach($files as $k=>$v) {
		$opt = array(
			'referer' => false,
			'url' => $v['url'],
			'useragent' => "23/6.0.1; 640dpi; 1440x2560; ZTE; ZTE A2017U; ailsa_ii; qcom",
			'headers' => false,
		);
		
		$result = curl_get($opt);
		if ($result['data']!==false) {
			$filename = time()."_".rand(10,99)."_".rand(10,99).".".$v['type'];
			file_put_contents('./'.$filename, $result['data']);
			$files_on_disk[] = $filename;
		}
	}
	return $files_on_disk;
}

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
			$file[] = get_media_url($media_type, $carousel_object);
		}
	}
	return $file;
}

$ig = insta_login()['message'];

$shortcode = 'BubOSedlTfB';

$mediaId = InstagramID::fromCode($shortcode);
		
$itemInfo = $ig->media->getInfo($mediaId);

$items = [];
foreach ($itemInfo->getItems() as $item) {
	$single_item = [];
	$single_item['media_type'] = Utils::checkMediaType($item->getMediaType());
	$single_item['source'] = $item->getUser()->getUsername();
	$single_item['description'] = ($item->getCaption()!==NULL) ?  $item->getCaption()->getText() : '';
	$single_item['tags'] = parse_tags_from_description($single_item['description']);
	$location = $item->getLocation();
	$single_item['location'] = [];
	if ($location) {
		$single_item['location']['location_lat'] = $location->getLat();
		$single_item['location']['location_lng'] = $location->getLng();
		$single_item['location']['location_fbid'] = $location->getFacebookPlacesId();
		$single_item['location']['location_name'] = $location->getName();
		$single_item['location']['location_addr'] = $location->getAddress();
		$single_item['location']['location_city'] = $location->getCity();
		$single_item['location']['location_country'] = $location->getCountry();
	}
	// $item->printPropertyDescriptions();
	
	$single_item['files'][] = get_media_url($single_item['media_type'], $item);
	
}

print_r($single_item['files']);

//$single_item['media'] = get_item_files($single_item['files']);
	
//$items[] = $single_item;