<?php
namespace gpi;

include('post.php');

/**
 * vk_location - ???
 * thumbnail - string with filename
 * caption - string
 * description - Post Description...
 * filename - string with filename
 */
class post_vkontakte extends post {
	public $vk_location = NULL;
	public $thumbnail = NULL;
	
	function __construct(array $post_exist_arguments=[]) {

		// get all vars from this and parent classes
		$vars = parent::get_vars(__CLASS__);

		foreach($vars as $k=>$var) {
			if (array_key_exists($k, $post_exist_arguments)) {
				$this->$k = $post_exist_arguments[$k];
			}
		}
	}
}