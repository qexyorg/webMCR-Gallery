<?php
/**
 * Gallery module for WebMCR
 *
 * Albums class
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

		if($this->user->lvl < 1){ $this->api->notify("Для доступа к личным альбомам необходима авторизация!", "", "403", 3); }

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Мои альбомы" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Мои альбомы";
	}

	private function album_array(){

		$end		= $this->cfg['rop_albums']; // Set end pagination

		$start		= $this->api->pagination($end, 0, 0); // Set start pagination

		$query = $this->db->query("SELECT `a`.id, `a`.title, `a`.`text`, `i`.img
									FROM `qx_gallery_albums` AS `a`
									LEFT JOIN `qx_gallery_images` AS `i`
										ON `i`.aid=`a`.id AND `i`.uid=`a`.uid
									WHERE `a`.uid='{$this->user->id}'
									GROUP BY `a`.id
									ORDER BY `a`.id DESC
									LIMIT $start,$end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("albums/album-none.html"); }

		ob_start();

		while($ar = $this->db->get_row($query)){

			$id = intval($ar['id']);

			$img = $this->db->HSC($ar['img']);
			$img_url = (empty($img)) ? BASE_URL.'qx_upload/gallery/empty.png' : BASE_URL.'qx_upload/gallery/'.$this->user->id.'/'.$img;

			$data = array(
				"ID" => $id,
				"TITLE" => $this->db->HSC($ar['title']),
				"IMG" => $img_url,
				"TEXT" => $this->db->HSC($ar['text']),
			);

			echo $this->api->sp("albums/album-id.html", $data);
		}

		return ob_get_clean();
	}

	private function album_list(){

		$f_security		= 'my_delete';

		$sql			= "SELECT COUNT(*) FROM `qx_gallery_albums` WHERE uid='{$this->user->id}'"; // Set SQL query for pagination function

		$pagination		= $this->api->pagination($this->cfg['rop_albums'], '&do=albums&pid=', $sql); // Set pagination

		$data = array(
			"CONTENT"		=> $this->album_array(),
			"PAGINATION"	=> $pagination,
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
		);

		return $this->api->sp("albums/album-list.html", $data);
	}

	private function album_add(){
		$f_security = "alb_add";

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Мои альбомы" => MOD_URL.'&do=albums',
			"Добавление альбома" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Мои альбомы — Добавление альбома";

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=albums&op=add", "403", 3); }

			$title = $this->db->safesql(trim(@$_POST['title']));

			if(empty($title)){ $this->api->notify("Не заполнено поле названия", "&do=albums&op=add", "Ошибка!", 3); }

			$text = $this->db->safesql(trim(@$_POST['text']));

			$new_data = array(
				"date_create" => time(),
				"date_update" => time(),
			);

			$new_data = $this->db->safesql(json_encode($new_data));

			$insert = $this->db->query("INSERT INTO `qx_gallery_albums`
											(uid, title, `text`, `data`)
										VALUES
											('{$this->user->id}', '$title', '$text', '$new_data')");

			if(!$insert){ $this->api->notify("SQL Error: #".__LINE__, "&do=albums&op=add", "Ошибка!", 3); }

			$this->api->notify("Новый альбом успешно добавлен", "&do=albums", "Поздравляем!", 1);
		}

		$data = array(
			"TITLE"			=> '',
			"TEXT"			=> '',
			"BTN"			=> 'Добавить',
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
		);

		return $this->api->sp("albums/album-add.html", $data);
	}

	private function album_edit(){
		$f_security = "alb_edit";

		$id = intval(@$_GET['iid']);

		$query = $this->db->query("SELECT title, `text`, `data` FROM `qx_gallery_albums` WHERE id='$id' AND uid='{$this->user->id}'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Hacking Attempt!", "&do=albums", "403", 3); }

		$ar = $this->db->get_row($query);

		$data = json_decode($ar['data'], true);

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Мои альбомы" => MOD_URL.'&do=albums',
			"Редактирование альбома" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Мои альбомы — Редактирование альбома";

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=albums&op=edit&iid=$id", "403", 3); }

			$title = $this->db->safesql(trim(@$_POST['title']));

			if(empty($title)){ $this->api->notify("Не заполнено поле названия", "&do=albums&op=edit&iid=$id", "Ошибка!", 3); }

			$text = $this->db->safesql(trim(@$_POST['text']));

			$data['date_update'] = time();

			$new_data = $this->db->safesql(json_encode($data));

			$update = $this->db->query("UPDATE `qx_gallery_albums`
										SET title='$title', `text`='$text', `data`='$new_data'
										WHERE id='$id' AND uid='{$this->user->id}'");

			if(!$update){ $this->api->notify("SQL Error: #".__LINE__, "&do=albums&op=edit&iid=$id", "Ошибка!", 3); }

			$this->api->notify("Альбом успешно обновлен", "&do=albums&op=edit&iid=$id", "Поздравляем!", 1);
		}

		$data = array(
			"TITLE"			=> $this->db->HSC($ar['title']),
			"TEXT"			=> $this->db->HSC($ar['text']),
			"BTN"			=> 'Сохранить',
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
		);

		return $this->api->sp("albums/album-add.html", $data);
	}

	private function album_delete(){

		$f_security		= 'my_delete';

		if($_SERVER['REQUEST_METHOD']!='POST' || @$_POST['alb_action']!='delete'){ $this->api->notify("Hacking Attempt!", "&do=my", "403", 3); }

		if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=my", "403", 3); }

		$ids = @$_POST['albums'];

		if(empty($ids)){ $this->api->notify("Вы не выбрали ни одного элемента!", "&do=albums", "403", 3); }

		$ids = $this->api->filter_array_integer($ids);

		$ids = implode(',', $ids);

		$query = $this->db->query("SELECT id FROM `qx_gallery_albums` WHERE id IN ($ids) AND uid='{$this->user->id}'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Вы не выбрали ни одного элемента!", "&do=my", "403", 3); }

		$ids = array();

		while($ar = $this->db->get_row($query)){ $ids[] = intval($ar['id']); }

		$ids = implode(',', $ids);

		$delete = $this->db->query("DELETE FROM `qx_gallery_albums` WHERE id IN ($ids)");

		if(!$delete){ $this->api->notify('DB "my" #('.__LINE__.')', "&do=my", "Внимание!", 3); }

		$query = $this->db->query("SELECT img FROM `qx_gallery_images` WHERE aid IN ($ids) AND uid='{$this->user->id}'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify('Выбранные элементы успешно удалены', "&do=my", "Поздравляем!", 1); }

		while($ar = $this->db->get_row($query)){
			$url = MCR_ROOT.'qx_upload/gallery/'.$this->user->id.'/'.$ar['img'];

			if(file_exists($url)){ @unlink($url); }
		}

		$delete = $this->db->query("DELETE FROM `qx_gallery_images` WHERE aid IN ($ids)");

		if(!$delete){ $this->api->notify('DB "my" #('.__LINE__.')', "&do=my", "Внимание!", 3); }

		$this->api->notify('Выбранные элементы успешно удалены', "&do=my", "Поздравляем!", 1);
	}

	private function image_array(){

		$end		= $this->cfg['rop_images']; // Set end pagination

		$start		= $this->api->pagination($end, 0, 0); // Set start pagination

		$query = $this->db->query("SELECT id, title, `text`, img, `data`

									FROM `qx_gallery_images`

									WHERE uid='{$this->user->id}'

									ORDER BY id DESC

									LIMIT $start,$end");

		if(!$query || $this->db->num_rows($query)<=0){ return $this->api->sp("my/img-none.html"); } // Check returned result

		ob_start();

		while($ar = $this->db->get_row($query)){

			$img = $this->db->HSC($ar['img']);

			$data = array(
				"ID" => intval($ar['id']),
				"TITLE" => $this->db->HSC($ar['title']),
				"TEXT" => $this->db->HSC($ar['text']),
				"IMG" => $img,
				"IMG_URL" => BASE_URL.'qx_upload/gallery/'.$this->user->id.'/'.$img,
			);

			echo $this->api->sp("my/img-id.html", $data);
		}

		return ob_get_clean();
	}

	private function album_view(){

		$id = intval(@$_GET['iid']);

		$f_security		= 'my_delete';

		$query = $this->db->query("SELECT title FROM `qx_gallery_albums` WHERE id='$id' AND uid='{$this->user->id}'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify('Доступ запрещен', "&do=my", "Ошибка!", 3); }

		$ar = $this->db->get_row($query);

		$title = $this->db->HSC($ar['title']);

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Мои альбомы" => MOD_URL.'&do=albums',
			$title => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Мои альбомы — $title";

		$sql			= "SELECT COUNT(*) FROM `qx_gallery_images` WHERE aid='$id' AND uid='{$this->user->id}'"; // Set SQL query for pagination function

		$page = '&do=albums&op=view&iid='.$id.'&pid=';

		$pagination		= $this->api->pagination($this->cfg['rop_images'], $page, $sql); // Set pagination

		$list			= $this->image_array($id); // Set content to variable

		$data = array(
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $list,
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
		);

		return $this->api->sp('my/img-list.html', $data);
	}

	public function _list(){

		$op = (isset($_GET['op'])) ? $_GET['op'] : '';

		switch($op){
			case 'add': return $this->album_add(); break;
			case 'edit': return $this->album_edit(); break;
			case 'delete': return $this->album_delete(); break;
			case 'view': return $this->album_view(); break;

			default: return $this->album_list(); break;
		}
	}
}

/**
 * Gallery module for WebMCR
 *
 * Albums class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.1.0
 *
 */
?>