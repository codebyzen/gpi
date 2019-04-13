<?php
namespace gpi;

include('post_base.php');
include('post_fields.php');

class post_instagram_fields extends post_fields {
	public $filename = true;
}

/**
 * fbid_location - long
 * thumbnail - string with filename
 * caption - string
 * description - Post Description...
 * filename - string with filename
 */
class post_instagram extends post_base {
	public $fbid_location = NULL;
	public $thumbnail = NULL;
	
	/**
	 * Create class object and put arguments as array
	 * @param array $post_exist_arguments
	 */
	function __construct(array $post_exist_arguments=[]) {
		parent::__construct($this, $post_exist_arguments);
	}
	
	private function insta_login(){
		$nets = $this->db->query("SELECT * FROM `networks` WHERE `active` = TRUE AND `type` = 'instagram';");
		if ($nets!==false) $nets_config = $nets[0];

		//\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
		$ig = new \InstagramAPI\Instagram($nets_config->instagram['debug'], $nets_config->instagram['truncatedDebug']);


		try {
			$loginResponse = $ig->login($nets_config->instagram['username'], $nets_config->instagram['password']);
			if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
				$twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
				$catcher->debug('Two Factor Identifier required!');
				return false;
			}
		} catch (\Exception $e) {
			if (preg_match("/Challenge required/", $e->getMessage())) {
				mail('dsda@dsda.ru', 'GPI Challenge required!', 'Check your Instagram account for Challenge required action!');
			}
			throw new \Exception('Something went wrong: '.$e->getMessage());
			return NULL;
		}

		return $ig;
	}
	
	function post(){
		if (parent::check_post_fields($this, new post_instagram_fields())) {
			echo "Go posting use current class fields".PHP_EOL;
		} else {
			$this->insta_login();
		}
	}
}