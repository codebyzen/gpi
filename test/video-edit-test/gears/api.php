<?php
set_time_limit(360);

// $config = new \dsda\config\config();
// $catcher = new \dsda\catcher\catcher();
// $db = new \dsda\dbconnector\dbconnector();
// $auth = new \dsda\auth\auth(true);
$stdClass = new stdClass();
$config = new $stdClass();
$catcher = new $stdClass();
$db = new $stdClass();
$auth = new $stdClass();

// if ($auth->auth===false) {
// 	header("HTTP/1.0 404 Not Found");
// 	exit();
// }



error_reporting(-1);

/**
 * 
 * @param type $type
 * @param type $message
 * @param type $opts
 */
function out($type,$message,$opts=false) {
	if ($type!=='ok') header("HTTP/1.0 404 Not Found");
	header("HTTP/1.0 200 Ok", True);
	header('Content-Type: application/json; charset=UTF-8');
	exit(json_encode(array('type'=>$type, 'message'=>$message,'opts'=>$opts)));
}


class API {
	
	private $config = null;
	private $options = null;
	private $authRes = false;
	private $db = null;
	private $allowedRoutes = array(
		'sendTask',
	);
	
	function __construct($config=false, $db=false, $authRes=false){
		if ($config==false) out("error", "No config...");
		if ($db==false) out("error", "No db...");
		$this->db = $db;
		$this->config = $config;
		$this->db = $db;
		
		// $this->options = $this->get_db_options();

		if ($authRes==false) {
			out("error", "Wrong auth...");
		} else {
			$this->authRes = $authRes;
		}
		
		$request = filter_input(INPUT_POST, 'request', FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"/^([0-1a-z_-]+)$/is")));
		if ($request!==false && $request!==NULL && in_array($request, $this->allowedRoutes)) {
			$responce = $this->$request();
			out('success', $request, $responce);
		} else {
			out("error", "Wrong request...");
		}
		

	}


	/**
	 * get location from Instagram by location start
	 */
	function sendTask() {
		$in = filter_input(INPUT_POST, 'in', FILTER_SANITIZE_STRING);
		if ($in===NULL || $in===false) return array('type'=>'error','message'=>'No IN in request!');
		$in = strip_tags($in);
		$mb_in = mb_strtolower($in, "UTF-8");

		$out = filter_input(INPUT_POST, 'out', FILTER_SANITIZE_STRING);
		if ($out===NULL || $out===false) return array('type'=>'error','message'=>'No OUT in request!');
		$out = strip_tags($out);
		$mb_out = mb_strtolower($out, "UTF-8");

		
		return array('type'=>'success', 'message'=>[$in,$out]);
	}



	
	
	
	

	

	
	
}

$api	= new API($config, $db, $auth);



?>