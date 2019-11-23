<?php
namespace gpi;
define("strict_types",1);

class post_base {
	public $caption = NULL;
	public $description = NULL;
	public $filename = NULL;
	
	
	function __construct(post_base $class_object, array $post_exist_arguments=[]) {

		// get all vars from child class
		$vars = self::get_vars($class_object);

		foreach($vars as $k=>$var) {
			if (array_key_exists($k, $post_exist_arguments)) {
				$class_object->$k = $post_exist_arguments[$k];
			}
		}
	}
	
	/**
	 * Get variables from class object
	 * @param post $class
	 * @return array
	 * @throws \Exception
	 */
	static function get_vars($class) {
		if (is_object($class)) {
			return get_class_vars(get_class($class));
		} else {
			throw new \Exception("Can't get vars from non object element!");
		}
		
	}
	
	/**
	 * Check for required fields from post_???_fields class object
	 * @param \gpi\post $class_name
	 * @param \gpi\post_fields $class_fields_name
	 * @return boolean
	 * @throws \Exception
	 */
	static function check_post_fields(post_base $class_name, post_fields $class_fields_name){
		$class_vars = self::get_vars($class_name);
		if ($class_vars==false) {
			throw new \Exception("Can't get vars for ".get_class($class_name)."!",0);
		}
		
		$required_vars = self::get_vars($class_fields_name);
		if ($required_vars==false) {
			throw new \Exception("Can't get vars for ".get_class($class_name)."!",0);
		}
		
		foreach($required_vars as $k=>$v) {
			if ($v==true && (!isset($class_name->$k) || empty($class_name->$k))) {
				throw new \Exception("Key '".$k."' requred for ".get_class($class_name)." to post!",0);
			}
		}
		
		return true;
	}
	
}

