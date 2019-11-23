<?php
require __DIR__.'/../../vendor/autoload.php';

$config = new \dsda\config\config();
$catcher = new \dsda\catcher\catcher();
$db = new \dsda\dbconnector\dbconnector();
$auth = new \dsda\auth\auth(1); // (boolean) isAjax 

error_reporting(0);

if ($auth===false) {
	header("HTTP/1.0 404 Not Found");
	exit();
}

define('AUTHED', 'true');


/**
 * 0========================8
 */

require($config->get('path').'app/uploader/upload_handler.php');

/**
 * Get all from DB options table
 * @global \dsda\dbconnector\dbconnector $db
 * @return array Array of Objects
 */
function get_db_options(){
	global $db;
	$q = "SELECT * FROM `options`;";
	$options = $db->query($q);
	$out = new \stdClass();
	foreach($options as $v){
		$name = $v->name;
		$out->$name = $v->value;
	}
	return $out;
}

$db_options = get_db_options();

$uploader_options = array(
	'upload_dir' => $config->get('path').$db_options->path_temp,
	'upload_url' => $config->get('url').$db_options->path_temp
);

$upload_handler = new UploadHandler($uploader_options);
$result = $upload_handler->result_of_script ? $upload_handler->result_of_script['result'][0] : false;

/* check for upload compleate */
if (
		$result &&
		isset($result['files']) && 
		!empty($result['files']) &&
		isset($result['files'][0]->uploaded_flag) &&
		$result['files'][0]->uploaded_flag===true
) {

	//$finfo = new \dsda\fileinfo\fileinfo(array($uploader_options['upload_dir'].$result['files'][0]->name));
	//$upload_handler->result_of_script['result'][0]['files'][0]->test = $finfo->input_files[0]['type'];
	
	include_once $config->get('path').'/app/getter/from.php';
	$post = ['in'=>0,'out'=>0,'multiplex'=>'no','watermark'=>true,'resize'=>'resize','background'=>'blurred'];
	//$catcher->debug($uploader_options['upload_dir'].$result['files'][0]->name);
	$getter = new gpi\getter\from($config, $db, $uploader_options['upload_dir'].$result['files'][0]->name, $post);
	
	$upload_handler->generate_response($upload_handler->result_of_script['result'][0],$upload_handler->result_of_script['print']);

} else {
//	$catcher->debug($upload_handler->result_of_script['result'][0]);
//	$catcher->debug($upload_handler->result_of_script['print']);
	if ($upload_handler->result_of_script!==false) {
		$upload_handler->generate_response($upload_handler->result_of_script['result'][0],$upload_handler->result_of_script['print']);
	}
}


/**
 * 0========================8
 */