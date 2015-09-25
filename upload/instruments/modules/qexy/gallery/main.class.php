<?php
/**
 * Gallery module for WebMCR
 *
 * Main class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.1.0
 *
 */

// Check Qexy constant
if (!defined('QEXY')){ exit("Hacking Attempt!"); }

class module{

	// Set default variables
	private $user			= false;
	private $db				= false;
	private $api			= false;
	public	$title			= '';
	public	$bc				= '';
	private	$cfg			= array();

	// Set constructor vars
	public function __construct($api){
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->cfg			= $api->cfg;
		$this->api			= $api;
		$this->mcfg			= array();

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Список изображений" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Список изображений";
	}

	private function image_array(){

		$end		= $this->cfg['rop_images']; // Set end pagination

		$start		= $this->api->pagination($end, 0, 0); // Set start pagination

		$mcfg			= $this->api->getMcrConfig();
		$bd_names		= $mcfg['bd_names'];
		$bd_users		= $mcfg['bd_users'];
		$site_ways		= $mcfg['site_ways'];

		$category = "";

		if(isset($_GET['cid'])){
			$category = "AND cid='".intval($_GET['cid'])."'";
		}

		$query = $this->db->query("SELECT id, uid, title, `text`, img, `data`

									FROM `qx_gallery_images`

									WHERE `public`='1' $category

									ORDER BY id DESC

									LIMIT $start,$end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("list/img-none.html"); } // Check returned result

		ob_start();

		while($ar = $this->db->get_row($query)){

			$uid = intval($ar['uid']);

			$img = $this->db->HSC($ar['img']);

			$data = array(
				"ID" => intval($ar['id']),
				"UID" => $uid,
				"TITLE" => $this->db->HSC($ar['title']),
				"TEXT" => $this->db->HSC($ar['text']),
				"IMG" => $img,
				"IMG_URL" => BASE_URL.'qx_upload/gallery/'.$uid.'/'.$img,
			);

			echo $this->api->sp("list/img-id.html", $data);
		}

		return ob_get_clean();
	}

	private function image_list(){

		$category = "";
		$page = "&pid=";

		if(isset($_GET['cid'])){
			$category = " AND cid='".intval($_GET['cid'])."'";
			$page = '&cid='.intval($_GET['cid']).'&pid=';
		}

		$sql			= "SELECT COUNT(*) FROM `qx_gallery_images` WHERE `public`='1' $category"; // Set SQL query for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_images'], $page, $sql); // Set pagination

		$list			= $this->image_array(); // Set content to variable

		$data = array(
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $list,
			"CATEGORIES"	=> $this->get_categories(),
		);

		return $this->api->sp('list/img-list.html', $data);
	}

	private function get_categories(){
		$query = $this->db->query("SELECT id, title FROM `qx_gallery_categories` ORDER BY title ASC");

		if(!$query || $this->db->num_rows($query)<=0){ return '<li><a href="'.MOD_URL.'&cid=1">Без категории</a></li>'; }

		ob_start();

		while($ar = $this->db->get_row($query)){
			echo '<li><a href="'.MOD_URL.'&cid='.intval($ar['id']).'">'.$this->db->HSC($ar['title']).'</a></li>';
		}

		return ob_get_clean();
	}

	public function _list(){
		return $this->image_list();
	}
}

/**
 * Gallery module for WebMCR
 *
 * Main class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.1.0
 *
 */
?>