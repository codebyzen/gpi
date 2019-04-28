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



$ig = insta_login()['message'];

\InstagramAPI\Utils::$ffmpegBin = '/Users/ugputu/Work/dsda/gpi_v2/app/ffmpeg/mac/ffmpeg';
\InstagramAPI\Utils::$ffprobeBin = '/Users/ugputu/Work/dsda/gpi_v2/app/ffmpeg/mac/ffprobe';


$options = [
	'caption'=>"Test\nCaption\n#infovideo", 
	'title'=>"Test\nTitle\n#infovideo",
	'tmpPath'=>'./'
];

$videoFilename = '/Users/ugputu/Work/dsda/gpi_v2/test/video_1_361.mp4';
//$ig_media_object = new \InstagramAPI\Media\Video\InstagramVideo($videoFilename, ['targetFeed' => \InstagramAPI\Constants::FEED_TV]);

$ig_media_object = new \InstagramAPI\Request\TV($ig);
$responce = $ig_media_object->uploadVideo($videoFilename, ['title'=>'Test\nTitle\n#infovideo', 'share_enabled'=>1]); // post to IGTV

//$ig_media_object = new \InstagramAPI\Media\Video\InstagramVideo('/Users/ugputu/Work/dsda/gpi_v2/test/video_1_363.mp4', ['tmpPath'=>'./','targetFeed' => \InstagramAPI\Constants::FEED_TV]); // post to IGTV
//$ig_media_object = new \InstagramAPI\Media\Video\InstagramVideo('/Users/ugputu/Work/dsda/gpi_v2/test/video_1_362.mp4'); // just post in timeline
//$responce = $ig->timeline->uploadVideo($ig_media_object->getFile(), $options);

print_r($responce);

//$mediaId = InstagramID::fromCode($shortcode);
