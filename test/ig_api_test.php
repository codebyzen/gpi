<?php
namespace dsda;

set_time_limit(360);

require __DIR__.'/../../vendor/autoload.php';

$config = new \dsda\config\config();
$catcher = new \dsda\catcher\catcher();
$db = new \dsda\dbconnector\dbconnector();
$auth = new \dsda\auth\auth(true);

if ($auth->auth===false) {
	header("HTTP/1.0 404 Not Found");
	exit();
}

include_once($config->get('path').'app/getter/from.php');

error_reporting(-1);

class ig_api_test {
	/**
	 * @return \InstagramAPI\Instagram|boolean
	 */
	private function insta_login(){
		$nets = $this->db->query("SELECT * FROM `networks` WHERE `active` = TRUE AND `type` = 'instagram';");
		if ($nets!==false) $nets_config = $nets[0];
		
		
		\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
		$ig = new \InstagramAPI\Instagram($nets_config->debug, $nets_config->truncatedDebug);
		
		
		try {
			$loginResponse = $ig->login($nets_config->username, $nets_config->password);
			if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
				$twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
				$catcher->debug('Two Factor Identifier required!');
				return array('type'=>'error','message'=>'Two Factor Identifier required!');
			}
		} catch (\Exception $e) {
			return array('type'=>'error','message'=>$e->getMessage());
		}
		
		return array('type'=>'success', 'message'=>$ig);
	}
	
	function social_grabber(){

		$url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_STRING);
		if ($url===NULL || $url===false) return array('type'=>'error','message'=>'No url in request');
		
		$getter = new \gpi\getter\from($this->config, $this->db, $url, $_POST);
		
		// assemble return array
		return array("type" => 'success');
	}
	
	
	function __construct() {
		$this->insta_login();
		
	}
	
}