<?php

function fix2div($var) {
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

$text = 'Тест';
$d = [280,500];
//$d = [1280,496];
//$d = [1080,1080];
//$d = [600,300];
//$d = [640,640];


// если есть текст, то прибавляем к высоте еще 20%
// смотрим проходит ли по аспекту
// если нет то вписываем в аспект
// если есть текст то отнимаем у высоты 20%

echo PHP_EOL;