<?php
	namespace dsda;

	if (php_sapi_name()!=='cli') {
		header("HTTP/1.0 404 Not Found");
		exit();
	}

	set_time_limit(360);
	date_default_timezone_set('Europe/Moscow');

	require __DIR__.'/vendor/autoload.php';

	$config = new \dsda\config\config();
	$catcher = new \dsda\catcher\catcher($config);
	$db = new \dsda\dbconnector\dbconnector($config);

	





	if (!file_exists($config->get('path').'/upload/ready/'.$items[0]->name)) {
		$catcher->debug('Ready file ('.$items[0]->name.') for ID ('.$items[0]->id.') not found');
		exit();
	}
	
	$ig = insta_login();

	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		$ffmpeg_os = 'win';
		$ffmpeg_bin = 'ffmpeg.exe';
	} elseif (PHP_OS=='Darwin') {
		$ffmpeg_os = 'mac';
		$ffmpeg_bin = 'ffmpeg';
	} elseif (PHP_OS=='Linux') {
		$ffmpeg_os = 'nix';
		$ffmpeg_bin = 'ffmpeg';
	}
	\InstagramAPI\Media\Video\FFmpeg::$defaultBinary = $config->get('path').'/app/ffmpeg/'.$ffmpeg_os.'/'.$ffmpeg_bin;
	
	
	$options = array(
		'caption' => html_entity_decode(trim($items[0]->description)),
	);

	// get tags
	$tags = $db->query("SELECT t.tag FROM `tags2posts` AS t2p LEFT JOIN `tags` AS t ON t2p.tag_id = t.id WHERE t2p.post_id = ".$items[0]->id.";");
	if ($tags!==false && $tags!==NULL) {
		$tags_ready = array();
		foreach ($tags as $tv) {
			$tags_ready[] = '#'.$tv->tag;
		}
		$tags_to_post = trim(implode(" ",$tags_ready));
		$options['caption'] .= $options['caption']=='' ? $tags_to_post : "\n\n".$tags_to_post;
	}
	
	if (isset($items[0]) && !empty($items[0]->location_name)) {
		$location = null;
		try {
			$location = $ig->location->search('55.752', '37.616',$items[0]->location_name)->getVenues()[0];
		} catch(\Exception $e) {
			$catcher->debug('Something went wrong: '.$e->getMessage().PHP_EOL);
		}
		if ($location!==null) $options['location'] = $location;
	}

	
	try {
		if ($items[0]->type=='image') {
			$ig_item = new \InstagramAPI\Media\Photo\InstagramPhoto($config->get('path').'/upload/ready/'.$items[0]->name);
			$resopnce = $ig->timeline->uploadPhoto($ig_item->getFile(), $options);
		} else {
			$video_options = array(
				'useRecommendedRatio'=>false, // just use what we already have...
				'tmpPath' => $config->get('path').'/upload/temp/',
			);
			$ig_item = new \InstagramAPI\Media\Video\InstagramVideo($config->get('path').'/upload/ready/'.$items[0]->name, $video_options);
			$resopnce = $ig->timeline->uploadVideo($ig_item->getFile(), $options);
			
		}
	} catch (\Exception $e) {
		$catcher->debug('Something went wrong: '.$e->getMessage().PHP_EOL);
	}


	$media_code_raw = isset($resopnce) && $resopnce!==false ? $resopnce->getMedia()->getCode() : false;
	if (isset($media_code_raw) && $media_code_raw!=false && $media_code_raw!=null) {
		$media_code = '<a href="https://instagram.com/p/'.$media_code_raw.'" target="_blank">Post Link!</a>';
		$q = "UPDATE `posts` SET `published` = 1, `date_published` = '".date("Y-m-d H:i:s", time())."', `additional` = '".$media_code_raw."' WHERE `id` = ".$items[0]->id.";";
		$db->query($q);
	} else {
	    $q = "UPDATE `posts` SET `ready` = 0, `caption` = 'Ошибка публикации!!!' WHERE `id` = ".$items[0]->id.";";
		$db->query($q);
		mail('dsda@dsda.ru', 'GPI Error posting!', 'Error while posting: http://gpi.dsda.ru/post/'.$items[0]->id);
		$catcher->debug("Post failed (no post code returned from instagram): ".$items[0]->id.PHP_EOL);
	}
	
	
?>
