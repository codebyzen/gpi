<?php
namespace gpi;


include('./video_worker_layers.php');

$str = "КЛИКБЕЙТНЫЙ\nЗАГОЛОВОК!!!";

$vw = new video_worker();

$type_defnitions = [
	'TIMELINE' => ['MIN_RATIO' => 0.8,  'MAX_RATIO' => 1.91, 'RECOMMENDED_RATIO' => 1,      'MIN_DURATION' => 3,  'MAX_DURATION' => 60],
	   'STORY' => ['MIN_RATIO' => 0.56, 'MAX_RATIO' => 0.67, 'RECOMMENDED_RATIO' => 0.5625, 'MIN_DURATION' => 3,  'MAX_DURATION' => 15],
	    'IGTV' => ['MIN_RATIO' => 0.5,  'MAX_RATIO' => 0.8,  'RECOMMENDED_RATIO' => 0.5625, 'MIN_DURATION' => 15, 'MAX_DURATION' => 600],
];

class emptyClass {};

$text = new emptyClass();
$text->text = "КЛИКБЕЙТНЫЙ ЗАГОЛОВОК!!!";
$text->type = 'text';
$text->font_path = './fonts/arialblack.ttf';
$text->path = './text.jpg';

$video = new emptyClass();
$video->path = 'video_2.mp4';
$video->type = 'video';
$video->resize = true;

$audio = new emptyClass();
$audio->path = 'audio.mp3';
$audio->type = 'audio';

$watermark = new emptyClass();
$watermark->path = 'logo.png';
$watermark->type = 'watermark';


$opts = [
	'layers'			=> false,
	'files_path_out'	=> './',
	'multiplex'			=> 'audio',
	'audio'				=> $audio,
	'background'		=> 'blurred',
	'trim'				=> [0,70],
	'debug'				=> true,
	'ffpath'			=> '../../app/ffmpeg/',
	'progress_path'		=> './progress.txt',
	'destination'		=> $type_defnitions['IGTV'],
];

$opts['layers'] = [
	$text,
	$video,
	$watermark
];

// print_r($opts);
// exit();
$vw->init($opts);
print_r($vw->result);

?>