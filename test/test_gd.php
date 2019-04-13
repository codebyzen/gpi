<?php
define("strict_types",1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class genpic {

	private $image = NULL;
	private $video_width = 0;
	private $video_height = 0;
	private $text = "";
	private $text_padding_left = 0;
	private $text_padding_top = 0;
	private $text_height = 0;
	private $textbox_height = 0;
	private $textbox_width = 0;
	private $fontsize = 200;
	private $fontfile = NULL;

	function __construct(int $w=NULL, int $h=NULL, string $t=NULL, string $fontfile=NULL){
		$this->fontfile = __DIR__.DIRECTORY_SEPARATOR.$fontfile;
		$this->video_width = $w;
		$this->video_height = $h;
		$this->textbox_height = ($this->video_height/100*20);
		$this->textbox_width = $this->video_width-($this->video_width/100*5);
		$this->text_padding_left = ($this->video_width-$this->textbox_width)/2;
		$this->text_padding_top = ($this->textbox_height/100*5);
		$this->text = $t;
		$this->image_create();
		$this->image_put_fake_video();
		$this->calculate_font_size();
		$this->image_put_text();
		$this->image_output();
	}

	function image_create(){
		$this->image = imagecreatetruecolor($this->video_width,$this->video_height+$this->textbox_height);
		$this->background = imagecolorallocate ($this->image, 255,255,255);
		$this->gray = imagecolorallocate ($this->image, 99,99,99);
		$this->black = imagecolorallocate ($this->image, 0,0,0);
		imagefill ($this->image, 0, 0, $this->background);
	}

	function correct_imagettfbbox_dimentions(array $arr=null, bool $debug=false) {
		if ($debug) {
		echo PHP_EOL.PHP_EOL."			     [".$arr[6].'x'.$arr[7]."]    [".$arr[4].'x'.$arr[5]."]
				+------------+
				|            |
				|            |
				+------------+
 			     [".$arr[0].'x'.$arr[1]."]    [".$arr[2].'x'.$arr[3]."]
			".PHP_EOL.PHP_EOL;
		}
		$xs = [0,2,6,4];
		$ys = [1,3,7,5];

		$xs_diff = 0;
		foreach($xs as $k=>$v) { if ($arr[$v]<0) { $xs_diff = abs($arr[$v]); } }
		$ys_diff = 0;
		foreach($ys as $k=>$v) { if ($arr[$v]<0) { $ys_diff = abs($arr[$v]); } }

		foreach($xs as $k=>$v) { $arr[$v] = $arr[$v]+$xs_diff; }
		foreach($ys as $k=>$v) { $arr[$v] = $arr[$v]+$ys_diff; }
		if ($debug) {
		echo PHP_EOL.PHP_EOL."			     [".$arr[6].'x'.$arr[7]."]    [".$arr[4].'x'.$arr[5]."]
				+------------+
				|            |
				|            |
				+------------+
 			     [".$arr[0].'x'.$arr[1]."]    [".$arr[2].'x'.$arr[3]."]
			".PHP_EOL.PHP_EOL;
		}
		return $arr;
	}

	function calculate_font_size(){
		// fix padding if Й in first line
		if (preg_match("/(й|Й)/",explode("\n",$this->text)[0])) { $this->text_padding_top += 10; }
		for($size=200;$size>5;$size--){
			//$sizearray=imagettfbbox($size, 0, $this->fontfile, $this->text);
			$sizearray=imageftbbox($size, 0, $this->fontfile, $this->text);
			$sizearray = $this->correct_imagettfbbox_dimentions($sizearray,false);

			$width=$sizearray[6] + $sizearray[4] + $this->text_padding_left;
			$height = $sizearray[7] + $sizearray[1] + $this->text_padding_top;
			if($width<$this->textbox_width && $height<$this->textbox_height){
				// echo $width.' '.$height.PHP_EOL;
				$this->text_height = $height;
				$this->fontsize = $size;
				//imagettftext ($this->image, $this->fontsize, 0, 0, $this->fontsize, $this->black , $this->fontfile, $this->text);
				return true;
			}
		}
		throw new \Exception("title putter: maybe too long text...",0);
	}

	private function image_put_text(){
		$top = (($this->textbox_height - $this->text_height) / 2) + $this->text_padding_top + $this->fontsize;
		$paragraph = explode("\n",$this->text);
		$left = 0;
		foreach ($paragraph as $string){
			//$stringdim=imagettfbbox($this->fontsize, 0, $this->fontfile, $string);
			$stringdim=imageftbbox($this->fontsize, 0, $this->fontfile, $string);
			$stringdim = $this->correct_imagettfbbox_dimentions($stringdim, false);
			$left = $this->text_padding_left + (( $this->textbox_width - $stringdim[4] ) / 2);
			//imagettftext($this->image, $this->fontsize, 0, $left, $top, $this->black , $this->fontfile, $string);
			imagefttext($this->image, $this->fontsize, 0, $left, $top, $this->black , $this->fontfile, $string);
			$top += $this->fontsize + $this->fontsize * 0.5;
		}
	}



	function image_put_fake_video(){
		imagefilledrectangle ($this->image, 0, $this->textbox_height, $this->video_width,$this->video_height+$this->textbox_height, $this->gray);
	}

	function image_output(){
		imagejpeg($this->image, __DIR__.DIRECTORY_SEPARATOR.'test_gd.jpg', 75);
	}

}

$str = "КЛИКБЕЙТНЫЙ\nЗАГОЛОВОК!!!";
//$str = utf8_encode($str);
//$str = iconv("UCS-2", "UTF-8", preg_replace("/(.)/","\xf0\$1", $str));
//$gp = new genpic(640, 640, $str, "seguiemj.ttf");
//$gp = new genpic(640, 640, $str, "emojione-android.ttf");
$gp = new genpic(640, 640, $str, "./fonts/arialb.ttf");

echo "done".PHP_EOL;