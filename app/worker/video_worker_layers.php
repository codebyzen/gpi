<?php
namespace gpi;

/*

old

/var/www/gpi.dsda.ru/data/www/gpi.dsda.ru/app/ffmpeg/nix/ffmpeg 
-i /var/www/gpi.dsda.ru/data/www/gpi.dsda.ru/upload/original/1550257764837_942.mp4 
-i /var/www/gpi.dsda.ru/data/www/gpi.dsda.ru/app/watermark/logo.png 
-hide_banner -timelimit 180 -loglevel error 
-filter_complex "
	[0:v]scale=512:640[main];
	[1:v]scale=iw:ih[watermark];
	[main][watermark]overlay=main_w/4*3-overlay_w/4*3:main_h/5*4-overlay_h
"
-t 59 -c:v libx264 -preset fast -crf 24 -c:a copy -threads 0 -y /var/www/gpi.dsda.ru/data/www/gpi.dsda.ru/upload/ready/1550257764837_942.mp4

*/


/* 
 * Class: gpiFFmpeg
 * Name: gpiFFmpeg
 * Description: Class for prepare videos to post into social networks.
 * It can resize, make blurred background, multiplex, demultiplex,
 * overflow watermark, trim by seconds, loop video to sound duration...
 * Author: Eugene Che
 * Email: dsda@dsda.ru
 * Version: 0.1 beta
 * Copyrights:	gpiFFmpeg - Copyright (c) 2018 by Eugene Che with MIT License,
 *				FFmpeg - Copyright (c) 2000-2018 the FFmpeg developers
 *				FFprobe - Copyright (c) 2007-2018 the FFmpeg developers
 * 
 */

class video_worker {
	
	private $opts = [
		'layers'				=> false,
		'files_path_out'	=> './',
		'multiplex'			=> 'no',
		'background'		=> 'blurred',
		'trim'				=> [0,59],
		'debug'				=> true,
		'ffpath'			=> './',
		'progress_path'		=> './progress.txt',
	];
	public $result = '';
	
	function __construct() {}
	
	function init(array $options=NULL) {
		$this->opts = array_merge($this->opts, $options);
		
		if ($this->opts['layers']==false) {
			throw new \Exception("Video worker: Input files is empty", 0);
		}
		
		$this->check_out_dir(dirname($this->opts['files_path_out']));
		
		if (!in_array($this->opts['multiplex'], ['no','audio','video'])) {
			throw new \Exception("Video worker: Multiplex type task error!", 0);
		}
		
		$this->set_trim($this->opts['trim']);
		
		$this->set_ffpath($this->opts['ffpath']);

		// check for video stream dimensions
		foreach($this->opts['layers'] as $k=>$v) {
			if (isset($v->background) && !in_array($v->background, array('blurred', 'white', 'black'))) { $this->opts['layers'][$k] = 'blurred'; }
			
			if ($v->type=='video') {
				$v->dimension = $this->get_video_frame_size($v->path);
				$v->aspect = $v->dimension[0]/$v->dimension[1];
				$v->margins = array(0,0,0,0); // top, right, bottom, left
				$this->opts['layers'][$k] = $v;
				if ($v->resize=='resize') {
					$dim_w=640; $dim_h=640;
					//$this->resize_to($k, $dim_w, $dim_h, $this->opts['background']);
					
					$this->resizeto_new($k, 640);
				}
			}
		}

		$this->generate_text_background();
		
		foreach($this->opts['layers'] as $v) {
			$this->is_file_ok($v->path);
		}		

		$this->proceed();
	}
	

	private function set_trim(array $trim) {
		if (!is_array($trim)) {
			throw new \Exception("Video worker: Trim is not array!", 0);
		}
		if (count($trim)<2) {
			throw new \Exception("Video worker: Trim has no out marker!", 0);
		}
		if (!is_int($trim[0]) || !is_int($trim[1])) {
			throw new \Exception("Video worker: One of Trim marker is not an integer!", 0);
		}
		$this->opts['trim'] = $trim;
	}

	/**
	 * Debug function
	 * @param string $text Any type of variables to print on screen
	 * @return void Just echo input var
	 */
	private function d($text) {
		if ($this->opts['debug']==true) {
			if (is_array($text) || is_object($text)) {
				ob_start();
					print_r($text);
					$text = ob_get_contents();
				ob_end_clean();
			}
			echo $text.PHP_EOL;
		}
	}
	
	
	/**
	 * Set path to ffmpeg and ffprobe
	 * @param type $ffpath
	 * @return boolean
	 * @throws \Exception
	 */
	public function set_ffpath($ffpath) {
		
		if ($ffpath==false) { return false; }
		
		if (!file_exists($ffpath)){
			throw new \Exception("Video worker: ffpath is not exist [".$ffpath."]", 0);
		}
		if (!is_dir($ffpath)) {
			throw new \Exception("Video worker: ffpath is not directory [".$ffpath."]", 0);
		}
		
		$this->opts['ffpath'] = $ffpath;
		
		$mpeg_path = $this->get_ff('mpeg');
		
		if (file_exists(!$mpeg_path)){
			throw new \Exception("Video worker: ffmpeg binary is not exist [".$mpeg_path."]", 0);
		}
		
		$probe_path = $this->get_ff('probe');
		
		if (file_exists(!$probe_path)){
			throw new \Exception("Video worker: ffprobe binary is not exist [".$probe_path."]", 0);
		}
	}

	private function generate_text_background(){

		$video_key = $this->get_stream('video');
		$video = $this->opts['layers'][$video_key];
		
		$text_key = $this->get_stream('text');
		if ($text_key===false) return false;
		$text = $this->opts['layers'][$text_key];
		
		$full_dim = array($video->dimension[0]+$video->margins[0]*2,$video->dimension[1]+$video->margins[1]*2);		
		
		$tw = new \gpi\text_worker($full_dim[0], $full_dim[1], $text->text, $text->font_path, $text->path);
	}
	
	/**
	 * Check for file exist and readable
	 * @param string $file Path to file
	 * @return boolean True if all is ok or false if not
	 * @throws \Exception
	 */
	private function is_file_ok($file) {
		if (!file_exists($file)) {
			throw new \Exception('Video worker: File not exist! ('.$file.')', 0);
			return false;
		}
		if (!is_file($file)) {
			throw new \Exception('Video worker: This is not a file! ('.$file.')', 0);
			return false;
		}
		if (!is_readable($file)) {
			throw new \Exception('Video worker: This file is not readable! ('.$file.')', 0);
			return false;
		}
		return true;
	}
	
	/**
	 * Check for path exist and writable 
	 * @param string $path Path to directory
	 * @return boolean True if all is ok or false if not
	 * @throws \Exception
	 */
	private function check_out_dir($path) {
		if (!file_exists($path)) {
			throw new \Exception('Video worker: Directory not exist! ('.$path.')', 0);
			return false;
		}
		if (!is_dir($path)) {
			throw new \Exception('Video worker: This is not a directody! ('.$path.')', 0);
			return false;
		}
		if (!is_writable($path)) {
			throw new \Exception('Video worker: This directory is not writable! ('.$path.')', 0);
			return false;
		}
		return true;
	}

	/**
	 * Return FF[probe/mpeg] full path depends on OS type
	 * @param string $what probe or mpeg
	 * @return string Full path to FFProbe or FFMpeg
	 */
	private function get_ff($what){
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$ffmpeg_os = 'win';
			$ffmpeg_bin = 'ffmpeg.exe';
			$ffprobe_bin = 'ffprobe.exe';
		} elseif (PHP_OS=='Darwin') {
			$ffmpeg_os = 'mac';
			$ffmpeg_bin = 'ffmpeg';
			$ffprobe_bin = 'ffprobe';
		} elseif (PHP_OS=='Linux') {
			$ffmpeg_os = 'nix';
			$ffmpeg_bin = 'ffmpeg';
			$ffprobe_bin = 'ffprobe';
		}
		if ($what=='probe') {
			$file = $ffprobe_bin;
		} else {
			$file = $ffmpeg_bin;
		}
		if ($this->opts['ffpath']!==false) {
			$filepath = $this->opts['ffpath'].$ffmpeg_os.DIRECTORY_SEPARATOR.$file;
		} else {
			$filepath = dirname(__FILE__).DIRECTORY_SEPARATOR.'ffmpeg'.DIRECTORY_SEPARATOR.$ffmpeg_os.DIRECTORY_SEPARATOR.$file;
		}
		
		return $filepath;
	}
	
	
	/**
	 * Get duration for media files
	 * @param string $file_path
	 * @return int
	 */
	public function get_file_info($file_path) {
		
		$container = pathinfo($file_path, PATHINFO_EXTENSION);
		//$name = pathinfo($file_path, PATHINFO_FILENAME);

		$finfo = finfo_open(FILEINFO_MIME_TYPE); // возвращает mime-тип
		$mime = explode("/",finfo_file($finfo, $file_path));
		finfo_close($finfo);
		
		$duration = 0;
		$codec = false;
		$type = false;
		
		$console = [];
		$exitcode = 0;
		ob_start();
			$cmd = $this->get_ff('probe').' -loglevel panic -show_entries stream=codec_type,codec_name,duration -of default=noprint_wrappers=1:nokey=1 '.$file_path;
			exec($cmd, $console, $exitcode);
			if ($exitcode==0) {
				$codec = trim($console[0]);
				$type = trim($console[1]);
				$duration = trim($console[2]);
			} else {
				throw new \Exception("Video worker: FFprobe error with: ".$file_path.' (exitcode: '.$exitcode.')'.PHP_EOL.implode(PHP_EOL, $console).PHP_EOL.$cmd);
			}
		ob_end_clean();
		
		if ($type!==$mime[0]) { $type = $mime[0]; $codec = $mime[1]; }
		return array("type"=>$type,"container"=>$container, 'name'=> basename($file_path),"codec"=>$codec, "duration"=>$duration,"exitcode"=>$exitcode, "console"=>$console);
	}
	

	/**
	 * Check for variable divide by 2. If not, floor or ceil to int divided by 2
	 * @param int/float $var Int or float
	 * @return int
	 */
	private function fix2div($var) {
		if (round($var) != $var ) {
			if (ceil($var)%2==0) {
				$out = ceil($var);
			} else {
				$out = floor($var);
			}
		} else {
			if ($var%2==0) {
				$out = $var;
			} else {
				$out = $var+1;
			}
		}
		//$this->d($out);
		return $out;
	}
	
	/**
	 * Return first $this->opts['files'] item for video or audio
	 * @param string $type [video/audio]
	 * @return array
	 */
	private function get_stream($type='video'){
		foreach($this->opts['layers'] as $k=>$v) {
			if ($v->type==$type) {
				return $k;
			}
		}
		return false;
	}
	

	/**
	 * Get video stream dimensions by ffprobe
	 * @param string $file
	 * @return array First item is width, second is height
	 */
	private function get_video_frame_size($file) {
		$cmdline = $this->get_ff('probe').' -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 '.$file;
		$console = array();
		$code = 0;
		exec($cmdline, $console, $code);
		$size = explode("x",$console[0]);
		return array($size[0], $size[1]);
	}
	
	/**
	 * Calculate source video stream width and height to insert into setted $w and $h
	 * Also calculate paddings and set background types
	 * @param int $w Result box width
	 * @param int $h Result box height
	 * @param string $background Blurred or white or black
	 */
	public function resize_to($video_layer_key, $w,$h,$background='blurred') {
		if (!in_array($background, array('blurred', 'white', 'black'))) { $background = 'blurred'; }

		$video = $this->opts['layers'][$video_layer_key];
		
	
		if ($video->aspect<$w/$h) { // too tall
			$this->d('Side -> resize by vertical side');
			$pow = $video->dimension[1]/$h;

			$video->dimension[0] = $this->fix2div($video->dimension[0]/$pow);
			$video->dimension[1] = $h;
		
			$video->margins = array(
				round(($video->dimension[1]*0.8-$video->dimension[0])/2),
				0
			);
			
			$video->background = $background;
		}elseif($video->aspect>$w/$h){ // too width
			$this->d('Side -> resize by horisontal side');
			$pow = $video->dimension[0]/$w;
			
			$video->dimension[0] = $w;
			$video->dimension[1] = $this->fix2div($video->dimension[1]/$pow);
						
			$video->margins = array(
				0,
				round(($video->dimension[0]/1.7777777778-$video->dimension[1])/2)
			);
			
			$video->background = $background;
		}else{
			$this->d('Side -> Equal');
			$video->dimension[1] = $h;
			$video->dimension[0] = $w;
		}
		
		
		$this->opts['layers'][$video_layer_key] = $video;
		
	}

	public function resizeto_new($video_layer_key,$fixed_width) {
		
		$video = $this->opts['layers'][$video_layer_key];
		$w = $video->dimension[0];
		$h = $video->dimension[1];
		
		$text_key = $this->get_stream('text');
		if ($text_key!==false) {
			$h = $h+($h/100*20);
		}
		
		$media = [$w,$h];
		$back = [$w,$h];

		if ($w/$h>1.7777777778) { // width
			$back = [$fixed_width, $this->fix2div($fixed_width/1.7777777778)];
			$media = [$back[0], $this->fix2div($h/($w/$back[0]))];
		} elseif($w/$h<0.8) { // tall
			$back = [$fixed_width, $this->fix2div($fixed_width/0.8)];
			$media = [$this->fix2div($w/($h/$back[1])), $back[1]];
		} else {
			$media[1] = $media[1];
		}
		
		if ($text_key!==false) {
			$media[1] = $this->fix2div($media[1]-($media[1]/100*20));
			$back[1] = $this->fix2div($back[1]-($back[1]/100*20));
		}

		$this->opts['layers'][$video_layer_key]->dimension = $media;
		$this->opts['layers'][$video_layer_key]->margins = [$this->fix2div(($back[0]-$media[0])/2),$this->fix2div(($back[1]-$media[1])/2)];
		
		
	}
	
	private function full_length_and_loops_calculate(){
		$video_key = $this->get_stream('video');
		
		$loops = 0;
		
		$full_length = $this->opts['layers'][$video_key]->duration;
		if ($this->opts['multiplex']!=='no' && isset($this->opts['audio'])) {
			// set video loop to overflow audio length
			$full_length = max(array($this->opts['audio']->duration, $this->opts['layers'][$video_key]->duration));
			if ($this->opts['multiplex']=='audio') {
				$loops = floor($this->opts['audio']->duration/$this->opts['layers'][$video_key]->duration);
				$full_length = $this->opts['audio']->duration;
				//$loops = ($this->opts['trim'][1]-$this->opts['trim'][0])/min([$this->get_stream('audio')->duration,$this->get_stream('video')->duration]); //TODO: разобраться с этим
			}
		}
		
		return [$loops,$full_length];
		
	}

	
	private function compile_filters(){

		$filters = array();
	
		$main_key = $this->get_stream('video');
		$text_key = $this->get_stream('text');
		$watermark_key = $this->get_stream('watermark');

		$video = $this->opts['layers'][$main_key];
		$full_dim = array($video->dimension[0]+$video->margins[0]*2,$video->dimension[1]+$video->margins[1]*2);		
		
		$textbox_height = 0;
		if ($text_key!==false) {
			$textbox_height = $full_dim[1]/100*20;
			$full_dim[1] = $full_dim[1]+$textbox_height;
		}
		
		foreach($this->opts['layers'] as $k=>$v) {
			if (isset($v->resize) && $v->resize=='resize') {
				$filters[] = '['.$k.':v]scale='.$v->dimension[0].':'.$v->dimension[1].'['.$v->type.']';
			} else {
				$filters[] = '['.$k.':v]scale=iw:ih['.$v->type.']';
			}
		}
		
		if ($watermark_key!==false) {
			$filters[] = '[video][watermark]overlay=main_w/4*3-overlay_w/4*3:main_h/5*4-overlay_h[video]';
		}
		
	
		// create blurred [back]
		if ($this->opts['background']) {
			if ($this->opts['background']=='blurred') {
				$filters[] = '[video]split[video][back]';
				$filters[] = '[back]scale='.($full_dim[0]).':'.($full_dim[1]).', setsar=1:1[back]';
				$filters[] = '[back]boxblur=luma_radius=\'min(h,w)/20\':luma_power=1:chroma_radius=\'min(cw,ch)/20\':chroma_power=1[back]';
				$filters[] = 'color=color=black@.5:size='.$full_dim[0].'x'.$full_dim[1].':d=1[coloroverlay]';
				$filters[] = '[back][coloroverlay]overlay[back]';
			} elseif($this->opts['background']=='black') {
				$filters[] = 'color=color=black@1:size='.$full_dim[0].'x'.$full_dim[1].':d=1[back]';
			} elseif($this->opts['background']=='white') {
				$filters[] = 'color=color=white@1:size='.$full_dim[0].'x'.$full_dim[1].':d=1[back]';
			}
			$filters[] = '[back][video]overlay='.($video->margins[0]).':'.($video->margins[1]).'[video]';
		}

		if ($text_key!==false) {
			$filters[] = '[text][video]overlay=0:'.$textbox_height.'[video]';
		}
		
		$filters[] = 'color=color=black@.5:size='.$full_dim[0].'x'.$full_dim[1].':d=1[resultback]';
		$filters[] = '[resultback][video]overlay=0:0';
		
//		$f = '';
//		foreach($filters as $k=>$v) {
//			$filters[$k] = "\t".$v.'; \\';
//		}
//		$f = implode("\n",$filters);
//		return '"'.$f.'"';
		
		$f = '"'.implode('; ',$filters).'"';
		return $f;
	}
	
	
	/**
	 * Compile ffmpeg options magic and exec this crazy command
	 */
	public function proceed(){

		$cmd = array();
		$cmd[] = $this->get_ff('mpeg');
		if (!$this->opts['debug']) {
			$cmd[] = '-loglevel panic';
			$cmd[] = '-hide_banner';
		}
		$cmd[] = '-progress '.$this->opts['progress_path'];
		
		$cmd[] = '-timelimit 840';

		
		list($stream_loops, $full_length) = $this->full_length_and_loops_calculate();
			
		
		foreach($this->opts['layers'] as $l_k=>$l_v) {
			if ($l_v->type=='video') {
				if ($stream_loops>1) { $cmd[] = '-stream_loop '.$stream_loops; } 
			}
			$cmd[] = '-i '.$l_v->path;
		}

		// if multiplex set audio
		if ($this->opts['multiplex']!=='no' && isset($this->opts['audio'])) {
			$cmd[] = '-i '.$this->opts['audio']->path;
		}

		$cmd[] = '-filter_complex';
		$cmd[] = $this->compile_filters();
		
		if ($this->opts['trim']!==false) {
			// set trim length to longest stream
			if ($this->opts['trim'][0]>$full_length) {
				//throw new \Exception('IN mark greater then media duration!');
				$this->opts['result'] = 'IN mark greater then media duration!';
				return false;
			}
			if ($this->opts['trim'][1]>$full_length) {
				$this->opts['trim'][1]=$full_length;
			}
			if ($this->opts['trim'][0]>=$this->opts['trim'][1]) {
				//throw new \Exception('IN mark greater then OUT mark or equal!');
				$this->result = 'IN mark greater then OUT mark or equal!';
				return false;
			}
			$cmd[] = '-ss '.($this->opts['trim'][0]);
			$cmd[] = '-t '.($this->opts['trim'][1]-$this->opts['trim'][0]);
		} else {
			$cmd[] = '-ss 0';
			$cmd[] = '-t '.$full_length;
		}
		
		$cmd[] = '-c:v libx264';
		$cmd[] = '-preset fast';
		$cmd[] = '-crf 24';
		if ($this->opts['multiplex']=='no') {
			$cmd[] = '-c:a copy';
		}
		$cmd[] = '-threads 0';
		
		$cmd[] = '-strict normal';
		
		$cmd[] = "-y";
		
		$result_filename = time().'_'.rand(10,99).'_'.rand(10,99).'.mp4';
		$cmd[] = $this->opts['files_path_out'].$result_filename;
		$cmdline = implode(' \\'.PHP_EOL, $cmd);


		$console = array();
		$code = 0;
	
		//exec($cmdline.' > /dev/null &', $console, $code);
		
		// print_r($this->opts);
		// echo PHP_EOL.PHP_EOL;
		// echo $cmdline.PHP_EOL.PHP_EOL;
		exec($cmdline, $console, $code);
		if ($code!==0) {
			$this->result = "Exit code is: ".$code.PHP_EOL.PHP_EOL.implode("\n",$console);
		} else {
			$this->result = 'ok';
			$this->file_info = $this->get_file_info($this->opts['files_path_out'].$result_filename);
			unlink($this->opts['progress_path']);
		}
		
	}
	
}