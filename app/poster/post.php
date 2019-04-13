<?php
namespace gpi;

class post {

	/**
	 * POST OBJECT
	 * id
	 * user_id
	 * date_added
	 * date_published
	 * date_scheduled
	 * caption
	 * description
	 * location
	 * source
	 * ready
	 * published
	 * flag
	 * additional
	 * 
	 * networks
	 * tags
	 * location
	 * files
	 * 
	 */
	
	
	private $db = null;
	
	function __construct(\dsda\dbconnector\dbconnector $db) {
		$this->db = $db;
	}
	
	public function post_queue(){
		$post = $this->get_post_last();
		foreach($post->networks as $k=>$v) {
			//TODO: закончить
		}
	}
			
	
	private function get_post_location(int $post_id){
		$q = "SELECT * FROM `posts2location` AS p2l LEFT JOIN `locations` AS l ON p2l.location_id = l.id WHERE p2f.post_id = ".$post_id.";";
		$locations = $this->db->query($q);
		if ($locations!==false) {
			return $locations;
		} else {
			return false;
		}
	}
	
	private function get_post_files(int $post_id){
		$q = "SELECT * FROM `files2posts` AS f2p LEFT JOIN `files` AS f ON f2p.file_id = f.id WHERE f2p.post_id = ".$post_id.";";
		$files = $this->db->query($q);
		if ($files!==false) {
			return $files;
		} else {
			return false;
		}
	}
	
	private function get_post_networks(int $post_id){
		$q = "SELECT * FROM `posts2networks` AS p2n LEFT JOIN `networks` AS n ON p2n.network_id = n.id WHERE p2n.post_id = ".$post_id.";";
		$networks = $this->db->query($q);
		if ($networks!==false) {
			return $networks;
		} else {
			return false;
		}
	}
	
	private function get_post_tags(int $post_id){
		$tags = $this->db->query("SELECT t.tag FROM `tags2posts` AS t2p LEFT JOIN `tags` AS t ON t2p.tag_id = t.id WHERE t2p.post_id = ".$post_id.";");
		$tags_out = array();
		if ($tags!==false && $tags!==NULL) {
			foreach($tags as $tk=>$tv) {
				$tags_out[] = trim($tv->tag);
			}
		}
		return implode(" ", $tags_out);
	}
	
	private function get_post_last() {
		$q = "SELECT posts.*
			FROM posts
			WHERE posts.ready = 1 AND posts.published = 0 AND posts.date_scheduled < '".date("Y-m-d H:i:s", time())."' AND posts.flag IS NOT 'error'
			ORDER BY posts.date_scheduled ASC
			LIMIT 0,1;";

		$items = $this->db->query($q,0,1);

		if ($items==false) {
			//throw new \Exception("No items to post, just skip and exit!");
			exit();
		} else {
			$items[0]->location = $this->get_post_location($items[0]->id);
			$items[0]->networks = $this->get_post_networks($items[0]->id);
			$items[0]->files = $this->get_post_files($items[0]->id);
			$items[0]->tags = $this->get_post_tags($items[0]->id);
			return $items[0];
		}
	}

}