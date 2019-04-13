<?php
namespace dsda;

set_time_limit(360);

require __DIR__.'/../vendor/autoload.php';

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
	private $authRes = false;
	private $db = null;
	private $allowedRoutes = array(
		'bootstrap',
		'instagram_location',
		'db_location',
		'get_post_by_id',
		'store',
		'delete',
		'posts_library',
		'get_dashboard_info',
		'social_grabber',
		'post_id_now',
		'clear_publish_status',
		'location_delete',
		'autoschedule',
		'user_delete',
		'user_by_id',
		'user_edit',
		'tags_hints',
		'is_file_locked',
		'get_schedule_for_date',
		'sterilise',
		'taskprogress',
	);
	
	function __construct($config=false, $db=false, $authRes=false){
		if ($config==false) out("error", "No config...");
		if ($db==false) out("error", "No db...");
		$this->db = $db;
		$this->config = $config;
		$this->db = $db;

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
	 * Clear DB and uploads directory
	 */
	private function sterilise(){
		$tables = ['files','files2posts','files2tasks','posts','tasks','posts2locations','posts2networks','tags2posts'];
		$q = '';
		foreach ($tables as $v) {
			$q .= 'DELETE FROM `'.$v.'`;';
		}
		$this->db->query($q);
		$dirs = ['original','temp','ready','thumbnail'];
		$maindir = dirname(__DIR__).\DIRECTORY_SEPARATOR.'upload'.\DIRECTORY_SEPARATOR;
		foreach($dirs as $dir) {
			$files = scandir($maindir.$dir);
			foreach($files as $file) {
				$targetfile = $maindir.$dir.\DIRECTORY_SEPARATOR.$file;
				if ($file=='.' || $file=='..' || is_dir($targetfile) || $file=='.htaccess') {
					continue;
				} else {
					unlink($targetfile);
				}
			}
		}
	}
	
	
	/**
	 * Uses in $this->insta_location(),
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
	
	
	/**
	 * get location from Instagram by location start
	 */
	function instagram_location() {
		$query = filter_input(INPUT_POST, 'query', FILTER_SANITIZE_STRING);
		if ($query===NULL || $query===false) return array('type'=>'error','message'=>'No locations in request!');
		$query = strip_tags($query);
		$mb_query = mb_strtolower($query, "UTF-8");
		
		$locations = array();
		
		$ig = $this->insta_login();
		if ($ig['type']=='error') {
			return array('type'=>'error','message'=>$ig['message']);
		} else {
			$ig = $ig['message'];
		}
		
		try {
			// Initialize the state.
			$rankToken = null;
			$max_iter = 2;
			$iter = 0;
			$excludeList = array();
			do {
				// Request the page.
				$response = $ig->location->findPlaces($query, $excludeList, $rankToken);

				// In this example we're simply printing the IDs of this page's items.
				foreach ($response->getItems() as $item) {
					$location = $item->getLocation();
					// Add the item ID to the exclusion list, to tell Instagram's server
					// to skip that item on the next pagination request.
					$facebook_places_id = $location->getFacebookPlacesId();
					$excludeList[] = $facebook_places_id;
					// Let's print some details about the item.
					$locations[] = array(
						'name' => $item->getTitle(),
						'lat' => $location->getLat(),
						'lng' => $location->getLng(),
						'addr' => $location->getAddress(),
						'city' => $location->getCity(),
						'country' => $location->getCountry(),
						'facebook_places_id' => $facebook_places_id,
					);
				}

				// Now we must update the rankToken variable.
				$rankToken = $response->getRankToken();

				if ($iter>=$max_iter) {
					break;
				} else {
					$iter++;
				}
				
				// Sleep for 5 seconds before requesting the next page. This is just an
				// example of an okay sleep time. It is very important that your scripts
				// always pause between requests that may run very rapidly, otherwise
				// Instagram will throttle you temporarily for abusing their API!
				sleep(5);
			} while ($response->getHasMore());
		} catch (\Exception $e) {
			return array('type'=>'error','message'=>$e->getMessage());
		}
		
		foreach($locations as $k=>$v) {
			if (!preg_match("/(". $mb_query.")/isu", $v['name'])) continue;
			foreach($v as $kv=>$vv) {
				$v[$kv] = strip_tags($v[$kv]);
				//$v[$kv] = addslashes($v[$kv]);
				$v[$kv] = \SQLite3::escapeString($v[$kv]);
			}
			
			$q = "SELECT * FROM `locations` WHERE `facebook_places_id` = '".$v['facebook_places_id']."'";
			$exist_locations = $this->db->query($q);
		}
		return array('type'=>'success', 'message'=>$locations);
	}


	function db_location(){
		$query = filter_input(INPUT_POST, 'query', FILTER_SANITIZE_STRING);
		if ($query===NULL || $query===false) return array('type'=>'error', 'message'=>'No query in request!');
		$query = strip_tags($query);
		$mb_query = mb_strtolower($query, "UTF-8");
		
		$locations = array();
		
		$q = "SELECT * FROM `locations` WHERE `name_lower` LIKE '%".$mb_query."%'";
		$db_locations = $this->db->query($q);
		if ($db_locations!==false) foreach($db_locations as $v) {
			$locations[] = array(
				'name' => $v->name,
				'lat' => $v->lat,
				'lng' => $v->lng,
				'addr' => $v->addr,
				'city' => $v->city,
				'country' => $v->country,
				'facebook_places_id' => $v->facebook_places_id,
			);
		}
		return array('type'=>'success', 'message'=>$locations);
	}
	
		
	/**
	 * check for database tables exist
	 */
	function bootstrap() {
		//TODO: check base tables
		return array('type'=>'success', 'message'=>'Bootstraped OK!');
	}

	
	function get_post_networks(int $post_id){
		$q = "SELECT n.* FROM posts2networks AS p2n LEFT JOIN networks as n ON n.id = p2n.network_id WHERE p2n.post_id = ".$post_id;
		$networks = $this->db->query($q);
		if ($networks==false) {
			$networks = [];
		}
		return $networks;
	}
	
	function get_tags_by_post_id($post_id){
		// get tags
		$tags = $this->db->query("SELECT t.tag FROM `tags2posts` AS t2p LEFT JOIN `tags` AS t ON t2p.tag_id = t.id WHERE t2p.post_id = ".$post_id.";");
		$tags_out = array();
		if ($tags!==false && $tags!==NULL) {
			foreach($tags as $tk=>$tv) {
				$tags_out[] = trim($tv->tag);
			}
		}
		return implode(" ", $tags_out);
	}

	function get_post_files($post_id){
		$q = "SELECT * FROM `files2posts` AS f2p LEFT JOIN `files` AS f ON f2p.file_id = f.id WHERE f2p.post_id = ".$post_id.";";
		$files = $this->db->query($q);
		if ($files!==false) {
			return $files;
		} else {
			return false;
		}
	}
	
	
	function get_post_by_id() {
		$request = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
		if ($request===false || $request===NULL) {
			return array('type'=>'error', 'message'=>'No ID in request');;
		}
		
		$q = "	SELECT 
					posts.*, 
					loc.name as location_name, 
					loc.id as location_id, 
					loc.lat as location_lat, 
					loc.lng as location_lng, 
					loc.facebook_places_id as facebook_places_id,
					GROUP_CONCAT(nets.network_id, ';') AS networks
		   FROM posts
				LEFT JOIN
				posts2networks AS nets ON nets.post_id = posts.id
				LEFT JOIN
				posts2locations AS p2l ON p2l.post_id = posts.id
				LEFT JOIN
				locations AS loc ON loc.id = p2l.loc_id
		  WHERE posts.id = ".$request."
		  GROUP BY posts.id;
		";
		$items = $this->db->query($q);		
		if ($items==false || $items==NULL) return array('type'=>'error', 'message'=>'No post found!');
		
		$files = $this->get_post_files($items[0]->id);
		
		$items[0]->files=$files;
		
		// get tags
		$items[0]->tags = $this->get_tags_by_post_id($request);
		
		return array('type'=>'success', 'message'=>$items);
		
	}
	
	/**
	 * вызывается после загрузки и нажатия сохранить пост и сохраняет информацию о файле в базу
	 */
	function store(){
		
		$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
		if ($id===NULL || $id===false) out("error", "ID is incorrect!");

		$caption = filter_input(INPUT_POST, 'caption', FILTER_SANITIZE_STRING);
		if ($caption===NULL || $caption===false) $caption = NULL;
		$caption = strip_tags($caption);

		$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
		if ($description===NULL || $description===false) $description = NULL;
		$description = strip_tags($description);
		
		$tags = filter_input(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
		if ($tags===NULL || $tags===false) {
			$tags = array();
		} else {
			$tags = strip_tags($tags);
			preg_match_all("/([а-яА-ЯЁёa-zA-Z0-9_]+)/iu", trim($tags), $tags_cleared);
			$tags = isset($tags_cleared[1]) ? $tags_cleared[1] : array();
		}
		
		$scheduled_date = filter_input(INPUT_POST, 'scheduled_date', FILTER_VALIDATE_REGEXP, array('options'=>array("regexp"=>"/^([\d]{2,4}-[\d]{1,2}-[\d]{1,2})$/is")));
		if ($scheduled_date===NULL || $scheduled_date===false) $scheduled_date = NULL;
		$scheduled_date = strip_tags($scheduled_date);

		$scheduled_time = filter_input(INPUT_POST, 'scheduled_time', FILTER_VALIDATE_REGEXP, array('options'=>array("regexp"=>"/^([\d]{1,2}\:[\d]{1,2}(\:[\d]{1,2})?)$/is")));
		if ($scheduled_time===NULL || $scheduled_time===false) $scheduled_time = NULL;
		$scheduled_time = strip_tags($scheduled_time);
		
		$source = filter_input(INPUT_POST, 'source', FILTER_SANITIZE_STRING);
		if ($source===NULL || $source===false) $source = NULL;
		$source = strip_tags($source);
		
		
		$facebook_places_id = filter_input(INPUT_POST, 'facebook_places_id', FILTER_VALIDATE_INT);
		if ($facebook_places_id===NULL || $facebook_places_id===false) $facebook_places_id = NULL;
		

		$nets = array();
		$nets_inputs = array(
			'net_instagram'=>array(	'filter'    => FILTER_VALIDATE_REGEXP,
								'options'   => array("regexp"=>"/^([a-z]+)$/is")
						),
			'net_telegram'=>array(	'filter'    => FILTER_VALIDATE_REGEXP,
								'options'   => array("regexp"=>"/^([a-z]+)$/is")
						),
			'net_vk'=>array(	'filter'    => FILTER_VALIDATE_REGEXP,
								'options'   => array("regexp"=>"/^([a-z]+)$/is")
						),
		);
		
		$net_data = filter_input_array(INPUT_POST, $nets_inputs);
		foreach($net_data as $k=>$v) {
			if ($v!=NULL && $v!=false && !empty($v)) $nets[] = strip_tags($v);;
		}
		
		$ready = filter_input(INPUT_POST, 'ready', FILTER_VALIDATE_BOOLEAN);
		if ($ready==NULL || $ready==false) {
			$ready = 0;
		} else {
			$ready = 1;
		}

		if (!$this->db->tableExist('posts')) {
			$this->bootstrap();
		}
		
		if (strlen($scheduled_time)<6) $scheduled_time .= ':00';

		$scheduled = ($scheduled_date!=NULL && $scheduled_time!=NULL) ? $scheduled_date." ".$scheduled_time : NULL;
		
		$q = "UPDATE `posts` SET `caption` = '".$caption."', `date_scheduled` = '".$scheduled."', `description` = '".$description."', `location` = '',  `source` = '".$source."', `ready` = ".$ready." WHERE `id` = '".$id."';";
		$this->db->query($q);
		
		
		if ($facebook_places_id!=NULL) {
			$location = $this->db->query("SELECT * FROM `locations` WHERE `facebook_places_id` = ".$facebook_places_id);
			if ($location!==false) {
				$this->db->query("DELETE FROM `posts2locations` WHERE `post_id` = ".$id.";");
				$this->db->query("INSERT INTO `posts2locations` VALUES(NULL, ".$id.", ".$location[0]->id.");");
			}
		} else {
			$this->db->query("DELETE FROM `posts2locations` WHERE `post_id` = ".$id.";");
		}
		
		// delete all from tags2posts with this post_id and insert another once new ones
		$q = "DELETE FROM `tags2posts` WHERE `post_id` = ".$id.";";
		$this->db->query($q);
		foreach($tags as $t_k=>$t_v) {
			$t_v = mb_strtolower($t_v, 'UTF-8');
			$is_tag_exist = $this->db->query("SELECT * FROM `tags` WHERE `tag` = '".$t_v."';");
			if ($is_tag_exist!==false && $is_tag_exist!==null) {
				$tag_id = $is_tag_exist[0]->id;
			} else {
				$tag_id = $this->db->query("INSERT INTO `tags` VALUES(NULL, '".$t_v."');");
			}
			$this->db->query("INSERT INTO `tags2posts` VALUES(NULL, ".$tag_id.", ".$id.")");
		}
		
		// delete all from posts2nets with this post_id and insert another once new ones
		$q = "DELETE FROM `posts2networks` WHERE `post_id` = ".$id.";";
		$this->db->query($q);
		if (!empty($nets)) {
			foreach($nets as $v) {
				$q = "INSERT INTO `posts2networks` VALUES(NULL, ".$id.", '".$v."', '".$id."_".$v."');";
				$this->db->query($q);
			}
		}
		
		return array('type'=>'success', 'message'=>'Post stored OK!');
	}
	
	
	function delete(){
		$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
		if ($id===false || $id===NULL) {
			return array('type'=>'error', 'message'=>'No ID in request!');
		}
		$post = $this->get_post_by_id($id);
		if ($post['type']=='error') {
			return $post;
		} else {
			$post = $post['message'][0];
		}
		
		try {
			if (file_exists($this->config->get('path').'/upload/original/'.$post->name_original) && is_file($this->config->get('path').'/upload/original/'.$post->name_original)) {
				unlink($this->config->get('path').'/upload/original/'.$post->name_original);
			}
		} catch (Exception $e) {
			throw new \Exception($e);
		}
		
		try {
			if (file_exists($this->config->get('path').'/upload/ready/'.$post->name) && is_file($this->config->get('path').'/upload/ready/'.$post->name)) {
				unlink($this->config->get('path').'/upload/ready/'.$post->name);
			}
		} catch (Exception $e) {
			throw new \Exception($e);
		}
		
		try {
			if (file_exists($this->config->get('path').'/upload/thumbnail/'.$post->thumbnail) && is_file($this->config->get('path').'/upload/thumbnail/'.$post->thumbnail)) {
				unlink($this->config->get('path').'/upload/thumbnail/'.$post->thumbnail);
			}
		} catch (Exception $e) {
			throw new \Exception($e);
		}
			
		$q = "DELETE FROM `posts` WHERE `id` = '".$id."';";
		$this->db->query($q);
		
		$q = "DELETE FROM `posts2networks` WHERE `post_id` = '".$id."';";
		$this->db->query($q);
		
		$q = "DELETE FROM `posts2locations` WHERE `post_id` = '".$id."';";
		$this->db->query($q);
		
		
		return array('type'=>'success', 'message'=>'Post deleted!');
	}
	
		
	
	function posts_library(){
		
		$from = filter_input(INPUT_POST, 'from', FILTER_VALIDATE_INT);
		if ($from===NULL || $from===false) $from = 0;
		
		$filter = filter_input(INPUT_POST, 'filter', FILTER_VALIDATE_REGEXP, array('options'=>array('regexp'=>"/(all|published|query|draft|errors)/is")));
		
		if ($filter===NULL || $filter===false) $filter = 'all';

		switch ($filter) {
			case "published";
				$filter_query = ["WHERE posts.published = 1", "ORDER BY posts.date_scheduled DESC"];
				break;
			case "query";
				$filter_query = ["WHERE posts.published = 0 AND `ready` = 1", "ORDER BY posts.date_scheduled ASC"];
				break;
			case "draft";
				$filter_query = ["WHERE posts.ready = 0", "ORDER BY posts.id DESC"];
				break;
			case "errors";
				$filter_query = ["WHERE posts.flag = 'error'", "ORDER BY posts.id DESC"];
				break;
			default:
				$filter_query = ["", "ORDER BY posts.id DESC"];
				break;
		}
		
		$q = "	SELECT posts.*
				FROM posts 
				".$filter_query[0]."
				GROUP BY posts.id
				".$filter_query[1]."
				LIMIT ".$from.",10;";

		$items = $this->db->query($q);
		if ($items==false) {
			$items = [];
		}
		
		
		if ($items) foreach($items as $k=>$v) {
			$items[$k]->tags = $this->get_tags_by_post_id($v->id);
			$items[$k]->files = $this->get_post_files($v->id);
			$items[$k]->networks = $this->get_post_networks($v->id);
		}
		
		
		return array('type'=>'success', 'message'=>$items);
	}
	
	
	function get_dashboard_info() {
		$info = array(
			'posts' => array(),
			'files' => array(),
			'system' => array(),
		);
		// total posts
		$q_total_posts = "SELECT COUNT(id) as cnt FROM `posts`;";
		$r_total_posts = $this->db->query($q_total_posts);
		if ($r_total_posts!==false) {
			$info['posts']['total'] = $r_total_posts[0]->cnt;
		}
		
		// total posted
		$q_total_posted = "SELECT COUNT(id) as cnt FROM `posts` WHERE `published` = 1;";
		$r_total_posted = $this->db->query($q_total_posted);
		if ($r_total_posted!==false) {
			$info['posts']['posted'] = $r_total_posted[0]->cnt;
		}
		
		// total is_ready
		$q_total_ready = "SELECT COUNT(id) as cnt FROM `posts` WHERE `ready` = 1;";
		$r_total_ready = $this->db->query($q_total_ready);
		if ($r_total_ready!==false) {
			$info['posts']['ready'] = $r_total_ready[0]->cnt;
		}
		
		// total unpublished
		$q_total_unpublished = "SELECT COUNT(id) as cnt FROM `posts` WHERE `published` = 0;";
		$r_total_unpublished = $this->db->query($q_total_unpublished);
		if ($r_total_unpublished!==false) {
			$info['posts']['unpublished'] = $r_total_unpublished[0]->cnt;
		}
		
		// total drafts		
		$q_total_ready = "SELECT COUNT(id) as cnt FROM `posts` WHERE `ready` = 0;";
		$r_total_ready = $this->db->query($q_total_ready);
		if ($r_total_ready!==false) {
			$info['posts']['drafts'] = $r_total_ready[0]->cnt;
		}
		
		// files/thumbnails absent
		$q_posts = "SELECT id FROM `posts`;";
		$r_posts = $this->db->query($q_posts);
		if ($r_posts!==false) {
			
			
			foreach($r_posts as $v_posts) {
				$file_path = [];
				$post_files = $this->get_post_files($v_posts->id);
				$info['post_files'] = $post_files;
				foreach($post_files as $v_files) {
					$fpath = $this->config->get('path').DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.$v_files->status.DIRECTORY_SEPARATOR.$v_files->name;
					$file_path[] = $fpath;
					if (!file_exists($fpath)) { $info['files']['absent_files'][] = ['post_id'=>$v_posts->id,'status'=>$v_files->status, 'name'=> $v_files->name]; }
				}
			}
		}
		
		// get system info
		ob_start();
			passthru('whoami');
			$info['system']['user'] = trim(ob_get_contents());
		ob_end_clean();
		
		ob_start();
			passthru('ffmpeg -version |grep "ffmpeg version"');
			$info['system']['ffmpeg'] = trim(ob_get_contents());
		ob_end_clean();
		
		ob_start();
			passthru('ffprobe -version |grep "ffprobe version"');
			$info['system']['ffprobe'] = trim(ob_get_contents());
		ob_end_clean();
		
		ob_start();
			passthru('php -v');
			$info['system']['php'] = trim(ob_get_contents());
		ob_end_clean();
		

		
		return array('type'=>'success', 'message'=>$info);
	}
	
	function post_id_now(){
		$post_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
		if ($post_id===NULL || $post_id===false) return array('type'=>'error', 'message'=>'No ID in request!');
		

		
		$q = "	SELECT posts.*,
			GROUP_CONCAT(nets.network_id, ';') AS networks,
			locations.name AS location_name,
			locations.lat AS location_lat,
			locations.lng AS location_lng,
			locations.facebook_places_id AS location_facebook_places_id
			FROM posts
			LEFT JOIN posts2networks AS nets ON nets.post_id = posts.id
			LEFT JOIN posts2locations AS p2l ON p2l.post_id = posts.id
			LEFT JOIN locations ON locations.id = p2l.loc_id
			WHERE posts.id = ".$post_id."
			GROUP BY posts.id
			ORDER BY posts.date_scheduled ASC
			LIMIT 0,1;";

		$items = $this->db->query($q);
		
		if ($items==false) {
			return array('type'=>'error', 'message'=>'No posts found!');
		}
		
		$ig = $this->insta_login();
		if ($ig['type']=='error') {
			return array('type'=>'error','message'=>$ig['message']);
		} else {
			$ig = $ig['message'];
		}
		
		
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$ffmpeg_os = 'win';
			$ffmpeg_bin = 'ffmpeg.exe';
			$ffprobe_bin = 'ffprobe.exe';
		} elseif (PHP_OS=='Darwin') {
			$ffmpeg_os = 'mac';
			$ffmpeg_bin = 'ffmpeg';
			$ffprobe_bin = 'ffprobe';
		} elseif (PHP_OS=='Linux') {
			$ffmpeg_os = 'nix';
			$ffmpeg_bin = 'ffmpeg';
			$ffprobe_bin = 'ffprobe';
		}
		//\InstagramAPI\Media\Video\FFmpeg::$defaultBinary = $this->config->get('path').'/app/ffmpeg/'.$ffmpeg_os.'/'.$ffmpeg_bin;
		\InstagramAPI\Utils::$ffmpegBin = $this->config->get('path').'/app/ffmpeg/'.$ffmpeg_os.'/'.$ffmpeg_bin;
		\InstagramAPI\Utils::$ffprobeBin = $this->config->get('path').'/app/ffmpeg/'.$ffmpeg_os.'/'.$ffprobe_bin;
		
		
		$options = array(
			'caption' => html_entity_decode(trim($items[0]->description)),
		);
		
		// get tags
		$tags = $this->db->query("SELECT t.tag FROM `tags2posts` AS t2p LEFT JOIN `tags` AS t ON t2p.tag_id = t.id WHERE t2p.post_id = ".$post_id.";");
		if ($tags!==false && $tags!==NULL) {
			$tags_ready = array();
			foreach ($tags as $tv) {
				$tags_ready[] = '#'.$tv->tag;
			}
			$tags_to_post = trim(implode(" ",$tags_ready));
			$options['caption'] .= $options['caption']=='' ? $tags_to_post : "\n\n".$tags_to_post;
		}

		if (!empty($items[0]->location_name)) {
			$location = null;
			try {
				$location = $ig->location->search('55.752', '37.616',$items[0]->location_name)->getVenues()[0];
			} catch(\Exception $e) {
				return array('type'=>'error', 'message'=>$e->getMessage());
			}
			if ($location!==null) $options['location'] = $location;
		}

		try {
			if ($items[0]->type=='image') {
				$ig_item = new \InstagramAPI\Media\Photo\InstagramPhoto($this->config->get('path').'/upload/ready/'.$items[0]->name);
				$resopnce = $ig->timeline->uploadPhoto($ig_item->getFile(), $options);
			} else {
				$video_options = array(
					'useRecommendedRatio'=>false, // just use what we already have...
					'tmpPath' => $config->get('path').'/upload/temp/',
				);
				$ig_item = new \InstagramAPI\Media\Video\InstagramVideo($this->config->get('path').'/upload/ready/'.$items[0]->name, $video_options);
				$resopnce = $ig->timeline->uploadVideo($ig_item->getFile(), $options);
			}
		} catch (\Exception $e) {
			return array('type'=>'error', 'message'=>$e->getMessage());
		}

		$media_code_raw = $resopnce ? $resopnce->getMedia()->getCode() : false;
		if (isset($media_code_raw) && $media_code_raw!=false && $media_code_raw!=null) {
			$media_code = '<a href="https://instagram.com/p/'.$media_code_raw.'" target="_blank">Post Link!</a>';
			$q = "UPDATE `posts` SET `published` = 1, `date_published` = '".date("Y-m-d H:i:s", time())."', `additional` = '".$media_code_raw."' WHERE `id` = ".$items[0]->id.";";
			$this->db->query($q);
			$ret_type = 'success';
		} else {
			$media_code = "Fail fetching code!";
			$ret_type = 'error';
		}
		
	
		return array('type'=>$ret_type, 'message'=>$media_code);
		
		
	}
	
	
	function clear_publish_status(){
		$post_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
		if ($post_id===NULL || $post_id===false) return array('type'=>'error', 'message'=>'No ID in request!');
		
		$this->db->query("UPDATE `posts` SET `published` = 0, `additional` = NULL WHERE `id` = ".$post_id.";");
		
		return array('type'=>'success', 'message'=>'Status updated!');
		
	}

	function location_delete(){
		$loc_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
		if ($loc_id===NULL || $loc_id===false) return array('type'=>'error', 'message'=>'No ID in request!');
		
		$this->db->query("DELETE FROM `locations` WHERE `id` = ".$loc_id.";");
		
		return array('type'=>'success', 'message'=>'Location deleted!');
	}
	
	
	function get_schedule_for_date(){
		$date = filter_input(INPUT_POST, 'date', FILTER_VALIDATE_INT);
		if ($date===NULL || $date===false) return array('type'=>'error', 'message'=>'No timestamp in request!');

		$q = "SELECT `date_scheduled` FROM `posts` WHERE `date_scheduled` >= '".date("Y-m-d", $date)." 00:00:00' AND `date_scheduled` <= '".date("Y-m-d", $date)." 23:59:59' AND `ready` = 1 ORDER BY `date_scheduled` DESC;";
		$scheduled = $this->db->query($q);
		if ($scheduled!==false && $scheduled!==NULL) {
			$sp = array();
			foreach($scheduled as $k=>$v) {
				$sp[] = date("H:i",strtotime($v->date_scheduled));
			}
			return array('type'=>'success', 'message'=>$sp);
		} else {
			return array('type'=>'error', 'message'=>array());
		}
	}
	
	
	function user_delete(){
		$user_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
		if ($user_id===NULL || $user_id===false) return array('type'=>'error', 'message'=>'No ID in request!');
		
		$this->db->query("DELETE FROM `users` WHERE `id` = ".$user_id.";");
		
		return array('type'=>'success', 'message'=>'User deleted!');
	}
	
	function user_edit(){
		$user_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
		if ($user_id===NULL || $user_id===false) return array('type'=>'error', 'message'=>'No ID in request!');
		
		$setterFields = array();
		
		$setterFields['login'] = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_SPECIAL_CHARS);
		
		$setterFields['email'] = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
		
		$setterFields['password'] = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
		if ($setterFields['password']==NULL || $setterFields['password']==false) {
			$setterFields['password'] = NULL;
		} else {
			$setterFields['password'] = md5($setterFields['password']);
		}
		
		$setters = array();
		
		foreach($setterFields as $k=>$v) {
			if ($v!==NULL) {
				$setters[] = "'".$k."' = '".$v."'";
			}
		}
		
		$setters = implode(",", $setters);
		
		if (!empty($setters)) {
			$this->db->query("UPDATE `users` SET ".$setters." WHERE `id` = ".$user_id.";");
			return '';
			return array('type'=>'success', 'message'=>'User updated!');
		}
		return array('type'=>'success', 'message'=>'User does not have any editions!');
		
	}
	
	
	function user_by_id(){
		$user_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
		if ($user_id===NULL || $user_id===false) return array('type'=>'error', 'message'=>'No ID in request!');
		
		$user = $this->db->query("SELECT * FROM `users` WHERE `id` = ".$user_id.";");
		if ($user!==false && $user!==NULL) {
			return array('type'=>'success', 'message'=>$user[0]);
		} else {
			return array('type'=>'error', 'message'=>'User does not exist!');
		}
	}
	
	
	function tags_hints(){
		$tags = filter_input(INPUT_POST, 'tag', FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"/([а-яА-ЯЁёa-zA-Z0-9\s_]+)/iu")));
		if ($tags===NULL || $tags===false) return array('type'=>'error', 'message'=>'Пустой запрос!');

		$q = "SELECT * FROM `tags` WHERE `tag` LIKE '%".$tags."%';";
		$tag = $this->db->query($q);
		if ($tag!==false && $tag!==NULL) {
			
			$allTags = array();
			foreach($tag as $v) {
				$allTags[] = '<span class="badge badge-info badge-pill cursor-pointer">'.$v->tag.'</span>';
			}
			
			return array('type'=>'success', 'message'=>implode(" ",$allTags));
		} else {
			return array('type'=>'success', 'message'=>'');
		}		
	}
	
	private function taskprogress(){
		$queue = $this->db->query("SELECT * FROM `options` WHERE `name` = 'task';");
		if ($queue==false || $queue[0]->value=='free') {
			return array('type'=>'error', 'message'=>'Progress is unaviable (Queue is free)!');
		}
		
		$task = $this->db->query("SELECT * FROM `tasks` WHERE `id` = ".$queue[0]->value.";");
		if ($task[0]!==false) {
			$end_time = json_decode($task[0]->trim);
			$end_time = $end_time[1] - $end_time[0];
		}
		
		$tmp_dir = $this->db->query("SELECT * FROM `options` WHERE `name` = 'path_temp';");
		if ($tmp_dir!==false) {
			$tmp_path = $this->config->get('path').$tmp_dir[0]->value;
			if (!file_exists($tmp_path)) {
				return array('type'=>'error', 'message'=>'Progress is unaviable (FFMpeg progress file not found)!');
			}
			$rawcontent = trim(file_get_contents($tmp_path));
			if (empty($rawcontent)) {
				return array('type'=>'error', 'message'=>'Progress is unaviable (taskprogress is empty)!');
			}
			$rawcontent = explode("\n", $rawcontent);
			$rawcontent = array_reverse($rawcontent);
			if ($rawcontent[0]=="progress=end") {
				return array('type'=>'success', 'message'=> ['task_id'=>$task[0]->id,'progress'=>100]);
			}

			foreach($rawcontent as $k=>$v) {
				$line = explode("=",$v);
				if ($line[0] == "out_time") {
					$digits = explode(":",$line[1]);
					$current_time = floor($digits[2]) + ($digits[1]*60) + ($digits[0]*60*60);
					$percent = floor($current_time/($end_time/100));
					return array('type'=>'success', 'message'=> ['task_id'=>$task[0]->id,'progress'=>$percent]);
				}
			}
			return array('type'=>'success', 'message'=> ['task_id'=>$task[0]->id,'progress'=>0]);
		} else {
			return array('type'=>'error', 'message'=>'Progress is unaviable! (No path_temp in DB)');
		}
		return array('type'=>'error', 'message'=>'Progress is unaviable! (No [out_time] in FFMpeg progress file)');
	}
	
	
}

$api	= new API($config, $db, $auth);



?>