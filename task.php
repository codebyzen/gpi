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

function set_task_error(int $task_id=null) {
	global $db;
	$db->query("UPDATE `tasks` SET `flag` = 'error' WHERE `id` = ".$task_id.";");
	$db->query("UPDATE `options` SET `value` = 'free' WHERE `name` = 'task';");
}

/**
 * Get all from DB options table
 * @global \dsda\dbconnector\dbconnector $db
 * @return array Array of Objects
 */
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

/**
 * Get last task from DB
 * @global \dsda\dbconnector\dbconnector $db
 * @return object
 */
function get_last_task(){
	global $db;
	$q =	"SELECT tasks.id, tasks.post_id, tasks.multiplex, tasks.size, tasks.background, tasks.watermark, tasks.trim, tasks.text FROM `tasks` "
			. " WHERE `flag`='ready' ORDER BY `id` ASC LIMIT 1;";
	$task = $db->query($q);
	if ($task==false) exit();
	$task = $task[0];
	$task->files = get_file_by_task_id($task->id);
	return $task;
}

/**
 * Get files from files table attend to current task id
 * @global \dsda\dbconnector\dbconnector $db
 * @param int $id
 * @return array of objects
 */
function get_file_by_task_id(int $id=null) {
	global $db;
	if ($id==NULL) return [];
	$q = "SELECT files.id as file_id, files.name, files.type, files.container, files.codec, files.duration, f2t.`order` as `order`
		FROM `files2tasks` AS f2t
		LEFT JOIN files as files ON f2t.file_id = files.id
		WHERE f2t.task_id = ".$id.";";
	
	$file = $db->query($q);
	if ($file==false) {
		$result = false;
	} else {
		$result = $file;
	}
	return $result;
}

/**
 * Check for queue is free 
 * @global \dsda\dbconnector\dbconnector $db
 * @return bool
 */
function is_queue_free(): bool {
	global $db;
	
	// Смотрим свободна ли очередь
	$is_task_free = $db->query("SELECT * FROM `options` WHERE `name` = 'task';");
	if ($is_task_free!=false) {
		// если очередь не свободна
		if ($is_task_free[0]->value!='free') {
			// если очередь занята больше 15 минут
			if (time()-$is_task_free[0]->time>900) {
				// Заносим в лог
				trigger_error('Task with ID: '.$is_task_free[0]->value.' freese on '.date('Y-m-d H:i.s', time()), E_USER_NOTICE);
				// Ставим флаг ошибки на таск
				$db->query("UPDATE `tasks` SET `flag` = 'error' WHERE `id` = ".$is_task_free[0]->id.";");
				$db->query("UPDATE `options` SET `value` = 'free', `time` = ".time()."  WHERE `name` = 'task';");
				return true;
			} else {
				return false; // все нормально, просто такс еще не отработал
			}
		} else {
			return true;
		}
	} else {
		// запись в опциях не найдена
		$db->query("INSERT INTO `options` VALUES(NULL, 'task', 'free', ".time().");");
		return true;
	}
}

/**
 * Process task to image worker
 * @global \dsda\config\config $config
 * @param type $task
 * @return \gpi\image_worker
 */
function process_task_image($task) {
	global $config;
	
	$worker_options_image = [
		'jpeg_quality'=>75,
		'max_width'=>1080,
		'max_height'=>1080,
		'auto_orient'=>1,
		'crop'=>0,
		'watermark_path' => $config->get('path').'assets/images/logo.png'
	];
	
	if (!file_exists($config->get('path').get_db_options('path_temp').$task->files[0]->name)) {
		$fake_worker = new \stdClass;
		$fake_worker->result = 'task: Input file not found ['.$config->get('path').get_db_options('path_temp').$task->files[0]->name.']';
		return $fake_worker;
	}
	
	rename($config->get('path').get_db_options('path_temp').$task->files[0]->name, $config->get('path').get_db_options('path_original').$task->files[0]->name);
	$worker = new image_worker($config->get('path').get_db_options('path_original').$task->files[0]->name, $config->get('path').get_db_options('path_ready').$task->files[0]->name, $worker_options_image);
	
	return $worker;
}


	function get_audio_file($files){
		foreach($files as $v) {
			if ($v->type=='audio') return $v;
		}
	}
	
/**
 * Process task to video worker
 * @global \dsda\config\config $config
 * @param type $task
 * @return \gpi\video_worker
 */
function process_task_video($task) {
	global $config;
	
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
		'debug'				=> false,
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
	
	
	foreach($task->files as $v) {
		//rename($config->get('path').get_db_options('path_temp').$v->name, $config->get('path').get_db_options('path_original').$v->name);
	}
	
	return $worker;
	
}

function task_has_files_for_multiplex(array $files=null){
	if ($files==null) return false;
	if (count($files)!=2) return false;
	$has_audio = false;
	$has_video = false;
	foreach($files as $v) {
		if ($v->type=='video') { $has_video=true; }
		if ($v->type=='audio') { $has_audio=true; }
	}
	if ($has_audio && $has_video) {
		return true;
	}
	
	return fasle;
}

function get_task_media_type(array $files=null) {
	if ($files==null) return false;
	foreach($files as $v) {
		if ($v->type=='video') { return 'video'; }
		if ($v->type=='image') { return 'image'; }
	}
}

/**
 * =================================== 
 * ============ MAIN CODE ============ 
 * ===================================
 */

// check for free queue
if (!is_queue_free()) {
	exit();
}


// get task
$task = get_last_task();
if ($task==false) exit();


// если задание на мультиплекс, то проверим кол-во входных файлов
if ($task->multiplex !== 'no') {
	if (!task_has_files_for_multiplex($task->files)) {
		set_task_error($task->id);
		throw new \Exception('Task: No enought files to multiplex! Task ID: '.$task->id);
	}
}

// занимаем очередь таском
//$db->query("UPDATE `options` SET `value` = '".$task->id."', `time` = ".time()."  WHERE `name` = 'task';");

// смотрим тип файла
switch (get_task_media_type($task->files)) {
	case 'video':
		$worker = process_task_video($task);
		break;
	case 'image':
		$worker = process_task_image($task);
		break;
	default:
		set_task_error($task->id);
		throw new \Exception("Media type is unknown! Task ID: ".$task[0]->id);
		break;
}
 
//print_r($worker);exit();
/**
 * min aspect 0.8 (portrait)
 * max aspect 1.7777777778 (landscape)
 */

// ТАКС ЗАПУСКАЕМ КАЖДЫЕ 5 СЕКУНД, СМОТРИМ ВСЕ ЗАДАНИЯ, И КОТОРЫЕ НЕ НУЖДАЮТСЯ В ОБРАБОТКЕ РАСКИДЫВАЕМ В БАЗУ
// ПОТОМ СМОТРИМ ПЕРВЫЙ КОТОРЫЙ НУЖДАЕТСЯ И ЗАНИМАЕМСЯ ИМ

if ($worker->result!='ok') {
	$db->query("UPDATE `tasks` SET `flag` = 'error' WHERE `id` = ".$task->id.";");
	$catcher->log($worker->result);
} else {
	$q = "INSERT INTO `files` VALUES("
			. "NULL, "
			. "'".(isset($worker->file_info['name'])?$worker->file_info['name']:NULL)."', "
			. "'".(isset($worker->file_info['type'])?$worker->file_info['type']:NULL)."', "
			. "'".(isset($worker->file_info['container'])?$worker->file_info['container']:NULL)."', "
			. "'".(isset($worker->file_info['codec'])?$worker->file_info['codec']:NULL)."', "
			. (isset($worker->file_info['duration'])?$worker->file_info['duration']:NULL).", "
			. "".time().", "
			. "'ready');";

	$file_id = $db->query($q);
	$db->query("INSERT INTO `files2posts` VALUES(NULL, ".$file_id.", ".$task->post_id.", ".$task->files[0]->order.");");
	$db->query("UPDATE `tasks` SET `flag` = 'compleate' WHERE `id` = ".$task->id.";");
	$db->query("UPDATE `options` SET `value` = 'free', `time` = ".time()."  WHERE `name` = 'task';");
}





?>