<?php
namespace gpi;

if (php_sapi_name()!=='cli') {
	header("HTTP/1.0 404 Not Found");
	exit();
}

set_time_limit(360);
date_default_timezone_set('Europe/Moscow');

require __DIR__.'/vendor/autoload.php';

$config = new \dsda\config\config();
$config->set('themepath', $config->get('path').'/assets/tpl');
$config->set('themeurl', $config->get('url').'/assets/tpl');
$config->set('assetsurl', $config->get('url').'/assets');

$catcher = new \dsda\catcher\catcher($config);

$db = new \dsda\dbconnector\dbconnector($config);

include_once($config->get('path').DIRECTORY_SEPARATOR.'app/worker/image_worker.php');
include_once($config->get('path').DIRECTORY_SEPARATOR.'app/worker/text_worker.php');
include_once($config->get('path').DIRECTORY_SEPARATOR.'app/worker/video_worker_layers.php');

function get_db_options(string $option=null){
	global $db;
	if ($option==null) return null;
	$q = "SELECT * FROM `options` WHERE `name` = '".$option."';";
	$options = $db->query($q);
	$out = null;
	foreach($options as $v){
		if ($option == $v->name) {
			$out = $v->value;
		}
	}
	return $out;
}


	
	
	$file_0 = new \stdClass;
	$file_0->file_id = 11;
	$file_0->name = 'video123.mp4';
	$file_0->type = 'video';
	$file_0->container = 'mp4';
	$file_0->codec = 'h264';
	$file_0->duration = 10;
	$file_0->order = 0;

	$file_1 = new \stdClass;
	$file_1->file_id = 12;
	$file_1->name = 'audio123.mp3';
	$file_1->type = 'audio';
	$file_1->container = 'mp3';
	$file_1->codec = 'mpeg layer-III';
	$file_1->duration = 80;
	$file_1->order = 0;
	
	$task = new \stdClass;
	
	$task->id = 13;
    $task->post_id = 62;
    $task->multiplex = 'audio';
    $task->size = 'resize';
    $task->background = 'blurred';
    $task->watermark = 1;
    $task->trim = json_encode([0,20]);
    $task->text = "ЦИРК\nУЕХАЛ!!!";
	$task->files = [$file_0, $file_1];

	
	$layers = [];
	
	function get_audio_file($files){
		foreach($files as $v) {
			if ($v->type=='audio') return $v;
		}
	}
	
	
	if (isset($task->text) && !empty($task->text)) {
		$layer = new \stdClass;
		$layer->type = 'text';
		$layer->path = $config->get('path').get_db_options('path_temp').'t_'.time().'_'.rand(10,99).'_'.rand(10,99).'.jpg';
		$layer->text = $task->text;
		$layer->font_path = $config->get('path').'assets'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'arial_black.ttf';
		$layers[-999] = $layer;
	}

	foreach($task->files as $v) {
		if ($v->type=='video') {
			$layer = $v;
			$layer->path = $config->get('path').get_db_options('path_temp').$v->name;
			$layer->resize = $task->size;
			$layers[] = $layer;
		}
	}

	if ($task->watermark) {
		$layer = new \stdClass;
		$layer->type = 'watermark';
		$layer->path = $config->get('path').'assets'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'logo.png';
		$layers[999] = $layer;
		
	}
	
	ksort($layers);
	$layers_ready = [];
	foreach($layers as $k=>$v) {
		$layers_ready[] = $v;
	}
	
	
	$worker_options_video = [
		'layers'			=> $layers_ready,
		'files_path_out'	=> $config->get('path').get_db_options('path_ready'),
		'multiplex'			=> $task->multiplex,
		'background'		=> $task->background,
		'trim'				=> json_decode($task->trim, true),
		'debug'				=> true,
		'ffpath'			=> $config->get('path').'app'.DIRECTORY_SEPARATOR.'ffmpeg'.DIRECTORY_SEPARATOR, // just directory!
		'progress_path'		=> $config->get('path'). get_db_options('path_temp').'taskprogress.txt',
	];
	
	if ($task->multiplex!=='no') {
		$audio = get_audio_file($task->files);
		$worker_options_video['audio'] = $audio;
		$worker_options_video['audio']->path = $config->get('path').get_db_options('path_temp').$audio->name;
		unset($audio);
	}
	
	
	$worker = new video_worker();
	$worker->init($worker_options_video);
	
	//print_r($worker);


?> 