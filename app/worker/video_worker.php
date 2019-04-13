<?php
namespace gpi;


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
		'files'				=> false,
		'files_path_in'		=> './',
		'files_path_out'	=> './',
		'multiplex'			=> 'no',
		'resize'			=> 'resize',
		'background'		=> 'blurred',
		'watermark'			=> false,
		'watermark_path'	=> null,
		'trim'				=> [0,59],
		'debug'				=> true,
		'ffpath'			=> './',
		'progress_path'		=> './progress.txt',
		'font_path'			=> '',
		'text'				=> '',
		'text_image_path'	=> '',
	];
	public $result = '';
	
	function __construct() {}
	
	function init(array $options=NULL) {
		$this->opts = array_merge($this->opts, $options);
		
		if ($this->opts['files']==false) {
			throw new \Exception("Video worker: Input files is empty", 0);
		}
		
		foreach($this->opts['files'] as $v) {
			$this->is_file_ok($this->opts['files_path_in'].$v->name);
		}
		
		$this->check_out_dir(dirname($this->opts['files_path_out']));
		
		if (!in_array($this->opts['multiplex'], ['no','audio','video'])) {
			throw new \Exception("Video worker: Multiplex type task error!", 0);
		}
		
		
		$this->set_watermark($this->opts['watermark'], $this->opts['watermark_path']);
		
		$this->set_trim($this->opts['trim']);
		
		$this->set_ffpath($this->opts['ffpath']);
		
		// set ids to streams
		foreach($this->opts['files'] as $k=>$v) {
			//$this->opts['files'][$k] = array_merge(array('path'=>$v),$this->get_file_info($v));
			//$this->opts['files'][$k]['id'] = md5(time().$v.rand(1000,999999));
		}
		
		//print_r($this->opts);
		//exit();

		// check for video stream dimensions
		$video = $this->get_stream('video');
			if ($video==false) {
				throw new \Exception('Video worker: Input files has no video stream', 0);
			}
			//$this->d($video);
			$video->dimension = $this->get_video_frame_size($this->opts['files_path_in'].$video->name);
			$video->aspect = $video->dimension[0]/$video->dimension[1];
			$video->margins = array(0,0,0,0); // top, right, bottom, left
		$this->set_stream($video);
		
		if ($this->opts['resize']=='resize') {
			$dim_w=640; $dim_h=640;
			// if (!empty($text)) { $dim_w=640; $dim_h=840; }
			$this->resize_to($dim_w, $dim_h, $this->opts['background']);
		}

		$this->generate_text_background();
		
		$this->proceed();
	}
	

	/**
	 * Set watermark file to video stream
	 * @param string $watermark_path Path to file with watermark (png or gif or other image file)
	 */
	private function set_watermark($watermark, $watermark_path) {
		if ($watermark==false) {
			$this->opts['watermark']=false;
			$this->opts['watermark_path']=null;
			return true;
		} else {
			if ($watermark_path!==false && $this->is_file_ok($watermark_path) && $this->opts['watermark']==true) {
				$this->opts['watermark_path'] = $watermark_path;
			}
		}
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
		$video = $this->get_stream('video');
		$tw = new \gpi\text_worker($video->dimension[0], $video->dimension[1], $this->opts['text'], $this->opts['font_path'], $this->opts['text_image_path']);
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
		foreach($this->opts['files'] as $v) {
			if ($v->type==$type) {
				return $v;
			}
		}
		return false;
	}
	
	/**
	 * Set $this->opts['files'] item by ID
	 * @param array $stream Item of $this->opts['files']
	 */
	private function set_stream($stream){
		foreach($this->opts['files'] as $k=>$v) {
			if ($v->file_id==$stream->file_id) {
				$this->opts['files'][$k] = $stream;
			}
		}
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
	public function resize_to($w,$h,$background='blurred') {
		if (!in_array($background, array('blurred', 'white', 'black'))) { $background = 'blurred'; }
		$video = $this->get_stream('video');
		
		if ($video->aspect<$w/$h) { // resize by vertical side
			$this->d('Side -> resize by vertical side');
			$pow = $video->dimension[1]/$h;

			$video->dimension[0] = $this->fix2div($video->dimension[0]/$pow);
			$video->dimension[1] = $h;
		
			$video->margins = array(
				round(($video->dimension[1]*0.8-$video->dimension[0])/2),
				0
			);
			
			$video->background = $background;
		}elseif($video->aspect>$w/$h){ // resize by horisontal side
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
		$this->set_stream($video);
	}
	

	
	/**
	 * Check for both audio and video streams for correct multiplexing
	 * @return boolean If video and audio files set - true, else - false
	 */
	private function has_audio_and_video_inputs(){
		$has_audio = false;
		$has_video = false;
		foreach($this->opts['files'] as $v) {
			if ($v->type=='audio') { $has_audio = true; }
			if ($v->type=='video') { $has_video = true; }
		}
		if ($has_audio==true && $has_video==true) { return true; }
		return false;
	}
	
	private function compile_filters(){

		$filters = array();
	
		$video = $this->get_stream('video');
		
		$full_dim = array($video->dimension[0]+$video->margins[0]*2,$video->dimension[1]+$video->margins[1]*2);
		
		// scale video to needle
		$filters[] = '[0:v]scale='.$video->dimension[0].':'.$video->dimension[1].'[main]';
		
		if (isset($this->opts['watermark']) && $this->opts['watermark']==true) {
			$filters[] = '[1:v]scale=iw:ih[watermark]';
			$filters[] = '[main][watermark]overlay=main_w/4*3-overlay_w/4*3:main_h/5*4-overlay_h[main]';
		}
		
		// create blurred [back]
		if (isset($video->background) && !empty($video->background)) {
			if ($video->background=='blurred') {
				$filters[] = '[0:v]scale='.($full_dim[0]).':'.($full_dim[1]).', setsar=1:1[back]';
				$filters[] = '[back]boxblur=luma_radius=\'min(h,w)/20\':luma_power=1:chroma_radius=\'min(cw,ch)/20\':chroma_power=1[back]';
				$filters[] = 'color=color=black@.5:size='.$full_dim[0].'x'.$full_dim[1].':d=1[coloroverlay]';
				$filters[] = '[back][coloroverlay]overlay[back]';
			} elseif($video->background=='black') {
				$filters[] = 'color=color=black@1:size='.$full_dim[0].'x'.$full_dim[1].':d=1[back]';
			} elseif($video->background=='white') {
				$filters[] = 'color=color=white@1:size='.$full_dim[0].'x'.$full_dim[1].':d=1[back]';
			}
			$filters[] = '[back][main]overlay='.($video->margins[0]).':'.($video->margins[1]);
		} else {
			$filters[] = 'color=color=black@1:size='.$full_dim[0].'x'.$full_dim[1].':d=1[back]';
			$filters[] = '[back][main]overlay=0:0';
		}
		
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

		
		$full_length = $this->get_stream('video')->duration;
		if ($this->opts['multiplex']!=='no') {
			// set video loop to overflow audio length
			$full_length = max(array($this->get_stream('audio')->duration, $this->get_stream('video')->duration));
			if ($this->opts['multiplex']=='audio') {
				$loops = floor($this->get_stream('audio')->duration/$this->get_stream('video')->duration);
				$full_length = $this->get_stream('audio')->duration;
				//$loops = ($this->opts['trim'][1]-$this->opts['trim'][0])/min([$this->get_stream('audio')->duration,$this->get_stream('video')->duration]); //TODO: разобраться с этим
				$cmd[] = '-stream_loop '.$loops;
			}
		}
		
		if (!empty($this->opts['text'])) {
			$cmd[] = '-i '.$this->opts['text_image_path'];
		}
		
		$cmd[] = '-i '. $this->opts['files_path_in'].$this->get_stream('video')->name;

		if ($this->opts['watermark']!==false) {
			$cmd[] = '-i '.$this->opts['watermark_path'];
		}
		

		// if multiplex set audio
		if ($this->opts['multiplex']!=='no' && $this->has_audio_and_video_inputs()) {
			$cmd[] = '-i '.$this->opts['files_path_in'].$this->get_stream('audio')->name;
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
		//echo $cmdline.PHP_EOL;
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