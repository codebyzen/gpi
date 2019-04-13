<?php
namespace gpi\getter;

class from {
	
	public $result = null;
	private $config;
	private $db;
	
	function __construct(\dsda\config\config $config, \dsda\dbconnector\dbconnector $db, string $url, array $post) {
		
		$this->config = $config;
		$this->db = $db;
		
		if (!stream_is_local($url)) {
			$network = $this->parse_network($url);
			$url = basename($url);
		} else {
			$network['type'] = 'file';
			$network['credentials'] = [];
		}
		
		include_once($this->config->get('path').'app/getter/networks/'.$network['type'].'.php');
		
		$class_name = '\\gpi\\getter\\'.$network['type'];
		
		$this->db_options = $this->get_db_options();
		
		/**
		 * data fields: array: files, string: description, string: source
		 */
		$data = new $class_name($url, $network['credentials'], $config->get('path').$this->db_options->path_temp);

		
		include($this->config->get('path').'app/worker/video_worker.php');
		
		$video_worker = new \gpi\video_worker();
		$video_worker->set_ffpath($this->config->get('path').'app/ffmpeg/');

		/**
		 * files = array( 0 => array( name=>..., type=>..., codec=>..., container=..., duration=... ) )
		 */
		$files = [];
		foreach($data->result['files'] as $v) {
			$files[] = array_merge(['name'=>$v], $video_worker->get_file_info($v));
		}
		
		$data->result['files'] = $files;
		
		$this->save_to_db($network, $data->result, $post);
	}
	
	/**
	 * Get all from DB options table
	 * @global \dsda\dbconnector\dbconnector $db
	 * @return array Array of Objects
	 */	
	private function get_db_options(){
		$q = "SELECT * FROM `options`;";
		$options = $this->db->query($q);
		$out = new \stdClass();
		foreach($options as $v){
			$name = $v->name;
			$out->$name = $v->value;
		}
		return $out;
	}
	
	private function parse_network(string $url): array {
		
		$url = filter_var($url, FILTER_VALIDATE_URL);
		if ($url===NULL || $url===false) {
			throw new \Exception("No url!", 0);
		}
		
		$url_parsed = parse_url($url);
		
		$known_networks = ['file','instagram.com','coub.com','reddit.com','tumblr.com','twitter.com','vk.com','youtube.com'];
		
		$service = 'url';
		
		foreach($known_networks as $k=>$v) {
			if (preg_match("/".str_replace(".",'\.',$v)."/i", $url_parsed['host'])) {
				$service = explode(".",$v)[0];
			}
		}
		

		if ($service=='file') {
			$credentials = [];
		} elseif ($service=='instagram') {
			$nets = $this->db->query("SELECT * FROM `networks` WHERE `active` = 'TRUE' AND `type` = 'instagram';", false, true);
			if ($nets!==false) $credentials = $nets[0];
		} elseif($service=='twitter') {
			$nets = $this->db->query("SELECT * FROM `networks` WHERE `active` = 'TRUE' AND `type` = 'twitter';", false, true);
			if ($nets!==false) $credentials = $nets[0];
		} elseif($service=='reddit') {
			$nets = $this->db->query("SELECT * FROM `networks` WHERE `active` = 'TRUE' AND `type` = 'reddit';", false, true);
			if ($nets!==false) $credentials = $nets[0];
		} elseif($service=='tumblr') {
			$nets = $this->db->query("SELECT * FROM `networks` WHERE `active` = 'TRUE' AND `type` = 'tumblr';", false, true);
			if ($nets!==false) $credentials = $nets[0];
		}  elseif($service=='vk') {
			$nets = $this->db->query("SELECT * FROM `networks` WHERE `active` = 'TRUE' AND `type` = 'vk';", false, true);
			if ($nets!==false) $credentials = $nets[0];
		} else {
			$credentials = false;
		}
		
		return ['type'=>$service, 'credentials'=>$credentials];
		
	}
	
	/**
	 * Prepare _POST data
	 * @param array $post
	 * @return \stdClass
	 * @throws \Exception If 
	 */
	private function check_post_data(array $post): \stdClass {
		/**
		 * POST:
		 * - in: int
		 * - out: int
		 * - multiplex: string 'multiplex_by_video|multiplex_by_audio|keep'
		 * - watermark: boolean
		 * - resize: string 'keep|resize'
		 * - background: string 'blurred|white|black'
		 * - description: string
		 * - source: string
		 */
		
		
		$postdata = new \stdClass();
		
		$postdata->multiplex = filter_var($post['multiplex'], FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"/^(no|video|audio)$/is")));
		if ($postdata->multiplex===NULL || $postdata->multiplex===false) {
			throw new \Exception('No multiplex in request');
		}
		
		$postdata->resize = filter_var($post['resize'], FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"/^(resize|keep)$/is")));
		if ($postdata->resize===NULL || $postdata->resize===false) { $postdata->resize='resize'; }
		
		$postdata->in = filter_var($post['in'], FILTER_VALIDATE_INT);
		if ($postdata->in===NULL || $postdata->in===false) { $postdata->in=0; }
		
		$postdata->out = filter_var($post['out'], FILTER_VALIDATE_INT);
		if ($postdata->out===NULL || $postdata->out===false) { $postdata->out=59; }
		
		$postdata->watermark = filter_var($post['watermark'], FILTER_VALIDATE_BOOLEAN);
		if ($postdata->watermark===NULL) { $postdata->watermark=true; }
		
		
		if (!isset($post['text'])) {$post['text']=NULL;}
		$postdata->text = filter_var($post['text'], FILTER_SANITIZE_SPECIAL_CHARS);
		if ($postdata->text===NULL || $postdata->text===false) { $postdata->text=false; }
		
		$postdata->background = filter_var($post['background'], FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>"/^(blurred|white|black)$/is")));
		if ($postdata->background===NULL || $postdata->background===false) { $postdata->background='blurred'; }
		
		return $postdata;
		
	}
	
	/**
	 * Put location and posts2locations to DB
	 * @param int $post_id
	 * @param array $location
	 */
	private function location_save(int $post_id, array $location) {
		// check location from grabber
		$location_db_res = $this->db->query("SELECT * FROM `locations` WHERE `facebook_places_id` = ".$location['location_fbid']);
		if ($location_db_res!==false) {
			$location_db_id = $location_db_res[0]->id;
			$this->db->query("DELETE FROM `posts2locations` WHERE `post_id` = ".$post_id.";");
			$this->db->query("INSERT INTO `posts2locations` VALUES(NULL, ".$post_id.", ".$location_db_id.");");
		} else {
			$q = "INSERT INTO `locations` VALUES(NULL, '".$location_name."', '".mb_strtolower($location_name, "UTF-8")."', '".$location['location_lat']."', '".$location['location_lng']."', '".$location['location_fbid']."', '".$location['location_addr']."', '".$location['location_city']."', '".$location['location_country']."');";
			$location_db_id = $this->db->query($q);				
		}

	}

	/**
	 * Put tags to tags and tags2posts DB
	 * @param int $post_id
	 * @param array $tags
	 */
	private function tags_save(int $post_id, array $tags){
		// check tags from grabber
		foreach($tags as $t_k=>$t_v) {
			$t_v = mb_strtolower($t_v, 'UTF-8');
			$is_tag_exist = $this->db->query("SELECT * FROM `tags` WHERE `tag` = '".$t_v."';");
			if ($is_tag_exist!==false && $is_tag_exist!==null) {
				$tag_id = $is_tag_exist[0]->id;
			} else {
				$tag_id = $this->db->query("INSERT INTO `tags` VALUES(NULL, '".$t_v."');");
			}
			$this->db->query("INSERT INTO `tags2posts` VALUES(NULL, ".$tag_id.", ".$post_id.")");
		}
	}
	
	/**
	 * Save post to db
	 * @param type $user_id
	 * @param type $description
	 * @param type $source
	 */
	private function post_save(int $user_id, string $description, string $source): int {
		$q = "INSERT INTO `posts` VALUES (NULL, ".$user_id.", '".date("Y-m-d H:i:s", time())."', NULL, NULL, NULL, '".$description."', '".$source."', 0, 0, '', '');";
		$post_id = $this->db->query($q);
		return $post_id;
	}
	
	/**
	 * Put task to DB
	 * @param int $post_id
	 * @param string $multiplex
	 * @param string $is_resize
	 * @param string $background
	 * @param bool $watermark
	 * @param array $trim
	 * @param string $text
	 * @return type
	 */
	private function task_save(int $post_id, string $multiplex, string $is_resize, string $background, bool $watermark, array $trim, string $text=NULL): int {
		$q = "INSERT INTO `tasks` VALUES (NULL, ".$post_id.", '".$multiplex."', '".$is_resize."', '".$background."', ".$watermark.", '". json_encode($trim)."', '".$text."', 'ready');";
		$task_id = $this->db->query($q);
		return $task_id;
	}
	
	
	/**
	 * Put file info to DB
	 * @param string $name
	 * @param string $type
	 * @param string $container
	 * @param string $codec
	 * @param int $duration
	 * @param int $time
	 * @param string $status ready|
	 * @return int file ID
	 */
	private function files_save(string $name, string $type, string $container, string $codec, int $duration, int $time, string $status): int {
		$q = "INSERT INTO `files` VALUES (NULL, '".$name."', '".$type."', '".$container."', '".$codec."', ".$duration.", ".$time.", '".$status."');";
		$file_id = $this->db->query($q);
		return $file_id;
	}
	
	/**
	 * Put file ID, task ID and order to DB
	 * @param int $file_id
	 * @param int $task_id
	 * @param int $file_order
	 */
	private function files2tasks_save(int $file_id, int $task_id, int $file_order){
		$q = "INSERT INTO `files2tasks` VALUES (NULL, ".$file_id.", ".$task_id.", ".$file_order.");";
		$this->db->query($q);
	}
	
	/**
	 * Save post, task and files info to DB
	 * @param array $data Result from grabber
	 * @param array $post Raw post data
	 * @return bool
	 */
	private function save_to_db(array $network, array $data, array $post): bool {
	
		/**
		 * 1. check post data
		 * 2. check grabber results
		 * 3. put to post
		 * 4. put to task
		 * 5. put to files
		 * 6. put to files2tasks
		 */
		
		
		// 1. check post data
		$postdata = $this->check_post_data($post);
		
		$auth = new \dsda\auth\auth(true);
		
		// 3. put to post
		$post_id = $this->post_save($auth->auth->id, $data['description'], $data['source']);
		
		// 4. put to task
		$task_id = $this->task_save($post_id, $postdata->multiplex, $postdata->resize, $postdata->background, $postdata->watermark, array($postdata->in, $postdata->out), $postdata->text);

		// 5. put to files
		foreach ($data['files'] as $file_key=>$file_var) {
			$file_id = $this->files_save($file_var['name'], $file_var['type'], $file_var['container'], $file_var['codec'], $file_var['duration'], time(), 'original');

			// 6. put to files2tasks 
			$this->files2tasks_save($file_id, $task_id, $file_key);
		}
		
	
		if (isset($data['location']) && !empty($data['location'])) {
			$this->location_save($post_id, $data['location']);
		}
		
		if (isset($data['tags']) && !empty($data['tags'])) {
			$this->tags_save();
		}
		
		// assemble return array
		return true;
		
	}
	
}