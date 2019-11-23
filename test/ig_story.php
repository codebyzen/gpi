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

$ig = insta_login()['message'];


$showStoryUsername = "mama_nysya";

$userID = $ig->people->getUserIdForName($showStoryUsername);
$storyFeed = $ig->story->getUserStoryFeed($userID);
$storyCount= count($storyFeed->getReel()->getItems());
$all_item = [];
for ($i=0; $i < $storyCount; $i++) {

	$single_item = [];
	$single_item['media_type'] = $storyFeed->getReel()->getItems()[$i]->getMediaType()==1 ? 'jpg' : 'mp4';
	$single_item['source'] = $showStoryUsername;
	$single_item['description'] = ($storyFeed->getReel()->getItems()[$i]->getCaption()!==NULL) ?  $storyFeed->getReel()->getItems()[$i]->getCaption()->getText() : '';
	$single_item['tags'] = parse_tags_from_description($single_item['description']);
	$single_item['location'] = [];

	if ($single_item['media_type']=='jpg') {
		$single_item['files'][] = $storyFeed->getReel()->getItems()[$i]->getImageVersions2()->getCandidates()[0]->getUrl();
		// if ($storyFeed->getReel()->getItems()[$i]->getStoryCta()==null) {
		// 	echo "noWebUri".PHP_EOL;
		// } else {
		// 	echo "webUri: ".$storyFeed->getReel()->getItems()[$i]->getStoryCta()[0]->getLinks()[0]->getWebUri().PHP_EOL;
		// }
	} else {
		$single_item['files'][] = $storyFeed->getReel()->getItems()[$i]->getVideoVersions()[0]->getUrl();
		// if ($storyFeed->getReel()->getItems()[$i]->getStoryCta()==null) {
		// 	echo "noWebUri".PHP_EOL;
		// } else {
		// 	echo "webUri: ".$storyFeed->getReel()->getItems()[$i]->getStoryCta()[0]->getLinks()[0]->getWebUri().PHP_EOL;
		// }
	}

	$all_item[] = $single_item;

}

print_r($all_item);

?>