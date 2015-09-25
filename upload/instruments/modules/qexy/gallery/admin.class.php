<?php
/**
 * Gallery module for WebMCR
 *
 * Admin class
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

		if($this->user->lvl < $this->cfg['lvl_admin']){
			$this->api->notify("Доступ запрещен!", "", "403", 3);
		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Панель управления" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Панель управления";
	}

	private function settings(){
		$f_security		= 'gallery_settings';

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=403", "403", 3); }

			$this->cfg['title'] = $this->db->HSC(strip_tags(@$_POST['title']));
			$this->cfg['lvl_access'] = intval(@$_POST['lvl_access']);
			$this->cfg['lvl_admin'] = intval(@$_POST['lvl_admin']);
			$this->cfg['rop_images'] = (intval(@$_POST['rop_images'])<=0) ? 1 : intval(@$_POST['rop_images']);
			$this->cfg['rop_albums'] = (intval(@$_POST['rop_albums'])<=0) ? 1 : intval(@$_POST['rop_albums']);

			if(!$this->api->savecfg($this->cfg, 'configs/gallery.cfg.php')){
				$this->api->notify("Произошла ошибка сохранения настроек", "&do=admin", "Ошибка!", 3);
			}
			
			$this->api->notify("Настройки успешно сохранены", "&do=admin", "Поздравляем!", 1);

		}

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_URL.'&do=admin',
			"Настройки" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Панель управления — Настройки";

		$data = array(
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
		);

		return $this->api->sp('admin/settings_main.html', $data);
	}

	private function categories(){
		$act = (isset($_GET['act'])) ? $_GET['act'] : 'main';

		switch($act){
			case 'add': return $this->categories_add(); break;
			case 'edit': return $this->categories_edit(); break;
			case 'delete': return $this->categories_delete(); break;

			default: return $this->categories_list(); break;
		}
	}

	private function images(){
		$act = (isset($_GET['act'])) ? $_GET['act'] : 'main';

		switch($act){
			//case 'add': return $this->images_add(); break;
			//case 'edit': return $this->images_edit(); break;
			case 'delete': return $this->images_delete(); break;

			default: return $this->images_list(); break;
		}
	}

	private function categories_array(){

		$end		= $this->cfg['rop_adm_cats']; // Set end pagination

		$start		= $this->api->pagination($end, 0, 0); // Set start pagination

		$query = $this->db->query("SELECT id, title
									FROM `qx_gallery_categories`
									ORDER BY id DESC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("admin/category-none.html"); }

		ob_start();

		while($ar = $this->db->get_row($query)){

			$data = array(
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
			);

			echo $this->api->sp("admin/category-id.html", $data);
		}

		return ob_get_clean();
	}

	private function categories_list(){
		$f_security		= 'gallery_categories';

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_URL.'&do=admin',
			"Категории" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Панель управления — Категории";

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=admin&op=categories", "403", 3); }

			$ids = @$_POST['id'];

			if(!is_array($ids) || empty($ids)){ $this->api->notify("Не выбрано ни одного элемента", "&do=admin&op=categories", "Ошибка!", 3); }

			$ids = $this->api->filter_array_integer($ids);

			$ids = implode(',', $ids);

			$delete = $this->db->query("DELETE FROM `qx_gallery_categories` WHERE id IN($ids)");

			if(!$delete){ $this->api->notify("SQL Error: #".__LINE__, "&do=admin&op=categories", "Ошибка!", 3); }

			$update = $this->db->query("UPDATE `qx_gallery_images` SET cid='1' WHERE cid IN($ids)");

			if(!$update){ $this->api->notify("SQL Error: #".__LINE__, "&do=admin&op=categories", "Ошибка!", 3); }

			$this->api->notify("Выбранные категории успешно удалены", "&do=admin&op=categories", "Поздравляем!", 1);
		}

		$sql			= "SELECT COUNT(*) FROM `qx_gallery_categories`"; // Set SQL query for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_adm_cats'], '&do=admin&op=categories&pid=', $sql); // Set pagination

		$data = array(
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
			"CATEGORIES"	=> $this->categories_array(),
			"PAGINATION"	=> $pagination,
		);

		return $this->api->sp("admin/category-list.html", $data);
	}

	private function categories_add(){
		$f_security		= 'gallery_categories_add';

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_URL.'&do=admin',
			"Категории" => MOD_URL.'&do=admin&op=categories',
			"Добавление" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Панель управления — Категории — Добавление";

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=admin&op=categories", "403", 3); }

			$title = $this->db->safesql(trim(@$_POST['title']));
			$text = $this->db->safesql(trim(@$_POST['text']));

			if(empty($title)){ $this->api->notify("Не заполнено поле названия", "&do=admin&op=categories&act=add", "Ошибка!", 3); }

			$insert = $this->db->query("INSERT INTO `qx_gallery_categories`
											(title, `text`)
										VALUES
											('$title', '$text')");

			if(!$insert){ $this->api->notify("SQL Error: #".__LINE__, "&do=admin&op=categories&act=add", "Ошибка!", 3); }

			$this->api->notify("Категория успешно добавлена", "&do=admin&op=categories", "Поздравляем!", 1);
		}

		$data = array(
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
			"TITLE"			=> "",
			"TEXT"			=> "",
			"BTN"			=> "Добавить",
		);

		return $this->api->sp("admin/category-change.html", $data);
	}

	private function categories_edit(){
		$f_security		= 'gallery_categories_edit';

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_URL.'&do=admin',
			"Категории" => MOD_URL.'&do=admin&op=categories',
			"Редактирование" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Панель управления — Категории — Редактирование";

		$id = intval(@$_GET['iid']);

		$query = $this->db->query("SELECT title, `text` FROM `qx_gallery_categories` WHERE id='$id'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Категория недоступна!", "&do=admin&op=categories", "404", 3); }

		$ar = $this->db->get_row($query);

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=admin&op=categories&act=edit&iid=$id", "403", 3); }

			$title = $this->db->safesql(trim(@$_POST['title']));
			$text = $this->db->safesql(trim(@$_POST['text']));

			if(empty($title)){ $this->api->notify("Не заполнено поле названия", "&do=admin&op=categories&act=edit&iid=$id", "Ошибка!", 3); }

			$update = $this->db->query("UPDATE `qx_gallery_categories` SET title='$title', `text`='$text' WHERE id='$id'");

			if(!$update){ $this->api->notify("SQL Error: #".__LINE__, "&do=admin&op=categories&act=edit&iid=$id", "Ошибка!", 3); }

			$this->api->notify("Категория успешно сохранена", "&do=admin&op=categories&act=edit&iid=$id", "Поздравляем!", 1);
		}

		$data = array(
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
			"TITLE"			=> $this->db->HSC($ar['title']),
			"TEXT"			=> $this->db->HSC($ar['text']),
			"BTN"			=> "Сохранить",
		);

		return $this->api->sp("admin/category-change.html", $data);
	}

	private function images_array(){

		$end		= $this->cfg['rop_adm_images']; // Set end pagination

		$start		= $this->api->pagination($end, 0, 0); // Set start pagination

		$this->mcfg	= $this->api->getMcrConfig();

		$bd_names	= $this->mcfg['bd_names'];
		$bd_users	= $this->mcfg['bd_users'];

		$query = $this->db->query("SELECT `i`.id, `i`.title, `i`.uid, `i`.cid, `i`.`img`, `i`.`public`, `i`.`data`,
											`c`.title AS `category`,
											`u`.`{$bd_users['login']}`
									FROM `qx_gallery_images` AS `i`
									LEFT JOIN `qx_gallery_categories` AS `c`
										ON `c`.id=`i`.cid
									LEFT JOIN `{$bd_names['users']}` AS `u`
										ON `u`.`{$bd_users['id']}`=`i`.uid
									ORDER BY `i`.id DESC
									LIMIT $start, $end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("admin/image-none.html"); }

		ob_start();

		while($ar = $this->db->get_row($query)){

			$uid = intval($ar['uid']);
			$data = json_decode($ar['data']);
			$login = $ar[$bd_users['login']];

			$data = array(
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"CID" => intval($ar['cid']),
				"IMG" => BASE_URL.'qx_upload/gallery/'.$uid.'/'.$this->db->HSC($ar['img']),
				"CATEGORY" => (is_null($ar['category'])) ? "Удалено" : $this->db->HSC($ar['category']),
				"PUBLIC" => (intval($ar['public'])==1) ? 'Публичное' : 'Приватное',
				"LOGIN" => (is_null($login)) ? "Удалено" : $this->db->HSC($login),
				"DATE_CREATE" => date("d.m.Y в H:i", $data->date_create),
				"DATE_UPDATE" => date("d.m.Y в H:i", $data->date_update),
			);

			echo $this->api->sp("admin/image-id.html", $data);
		}

		return ob_get_clean();
	}

	private function images_list(){
		$f_security		= 'gallery_images';

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Панель управления" => MOD_URL.'&do=admin',
			"Изображения" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Панель управления — Изображения";

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=admin&op=images", "403", 3); }

			$ids = @$_POST['id'];

			if(!is_array($ids) || empty($ids)){ $this->api->notify("Не выбрано ни одного элемента", "&do=admin&op=images", "Ошибка!", 3); }

			$ids = $this->api->filter_array_integer($ids);

			$ids = implode(',', $ids);

			$query = $this->db->query("SELECT uid, `img` FROM `qx_gallery_images` WHERE id IN ($ids)");

			if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Не выбрано ни одного элемента", "&do=admin&op=images", "Ошибка!", 3); }

			while($ar = $this->db->get_row($query)){

				$name = MCR_ROOT.'qx_upload/gallery/'.intval($ar['uid']).'/'.$this->db->HSC($ar['img']);

				if(file_exists($name)){ @unlink($name); }
			}

			$delete = $this->db->query("DELETE FROM `qx_gallery_images` WHERE id IN($ids)");

			if(!$delete){ $this->api->notify("SQL Error: #".__LINE__, "&do=admin&op=images", "Ошибка!", 3); }

			$this->api->notify("Выбранные изображения успешно удалены", "&do=admin&op=images", "Поздравляем!", 1);
		}

		$sql			= "SELECT COUNT(*) FROM `qx_gallery_images`"; // Set SQL query for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_adm_images'], '&do=admin&op=images&pid=', $sql); // Set pagination

		$data = array(
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
			"IMAGES"		=> $this->images_array(),
			"PAGINATION"	=> $pagination,
		);

		return $this->api->sp("admin/image-list.html", $data);
	}

	public function _list(){
		$op = (isset($_GET['op'])) ? $_GET['op'] : 'settings';

		switch($op){
			case 'categories': return $this->categories(); break;
			case 'images': return $this->images(); break;

			default: return $this->settings(); break;
		}
	}

}

/**
 * Gallery module for WebMCR
 *
 * Admin class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.1.0
 *
 */
?>