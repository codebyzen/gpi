<?php

namespace dsda;

require __DIR__.'/vendor/autoload.php';

$config = new \dsda\config\config();

$catcher = new \dsda\catcher\catcher();

$db = new \dsda\dbconnector\dbconnector();

$auth = new \dsda\auth\auth(false);
$authRes = $auth->check();


/**
 * Router simple code
 * in -> filtered input
 * url -> parsed url with parse_url()
 * path -> is just relative url path like smth/smth
 * allowed_pages -> is strings with correct pages names
 * file -> page file name (path + .php)
 */
$router = new \stdClass();
$router->in = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"/^([0-9a-z\?=\/_-]+)$/is")));
//$router->in = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"/^([0-9a-z\\\.\?&\/_=-]+)$/is")));
if ($router->in!==null && $router->in!==false) {
	$router->url = parse_url($config->get('url').$router->in);
	if (isset($router->url['path'])) {
		$router->path = explode("/",trim($router->url['path'],"/"));
	}
} else {
	$router->path = [];
}
$router->allowed_pages = array('', 'index', 'library', 'post', 'schedule', 'networks', 'users', 'locations', 'status', 'actions');
$router->file = 'index.php';
if (isset($router->path) && !empty($router->path) && in_array($router->path[0], $router->allowed_pages)) {
	$router->file = $router->path[0].'.php';
}

if ($authRes===false) {
	include($config->get('themepath').'/form.auth.php');
} else {
	if (file_exists($config->get('themepath').'/'.$router->file)) {
		include($config->get('themepath').'/'.$router->file);
	} else {
		$router->file = 'index.php';
		include($config->get('themepath').'/'.$router->file);
	}
}



?>