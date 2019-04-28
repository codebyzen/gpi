<?php
define("strict_types",1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Create some objects */
$image = new Imagick();
$draw = new ImagickDraw();
$pixel = new ImagickPixel( 'gray' );

/* New image */
$image->newImage(800, 75, $pixel);

/* Black text */
$draw->setFillColor('yellow');
$draw->setStrokeColor('black');

/* Font properties */
$draw->setFont('./fonts/emojione-svg.otf');
$draw->setFontSize( 30 );

//$str = "yummy cake\u{D83C}\u{DF82}\u{D83C}\u{DF70}";
$str = "ПЕРВАЯ СТРОКА "."\u{1F601}";
//$str = "ПЕРВАЯ СТРОКА "."\u{1F601} "."&#128515; "." 😁 "." &#9924; "." &#128514; "." &#8635; ";
//$str = utf8_encode($str);
//$str = iconv("UCS-2", "UTF-8", preg_replace("/(.)/","\xf0\$1", $str));
//$gp = new genpic(640, 640, $str, "seguiemj.ttf");
//$gp = new genpic(640, 640, $str, "emojione-android.ttf");

/* Create text */
$image->annotateImage($draw, 10, 45, 0, $str);

/* Give image a format */
$image->setImageFormat('jpg');

/* Output the image with headers */
//header('Content-type: image/png');
//echo $image;

file_put_contents("test_imagick.jpg", $image);


echo "done".PHP_EOL;