<?php
/**
 * Gallery module for WebMCR
 *
 * My class
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
	private $formats		= array('jpg', 'png');

	// Set constructor vars
	public function __construct($api){
		$this->user			= $api->user;
		$this->db			= $api->db;
		$this->cfg			= $api->cfg;
		$this->api			= $api;
		$this->mcfg			= array();

		if($this->user->lvl < 1){ $this->api->notify("Для доступа к личным изображениям необходима авторизация!", "", "403", 3); }

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Мои изображения" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Мои изображения";
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

	private function image_list(){

		$f_security		= 'my_delete';

		$sql			= "SELECT COUNT(*) FROM `qx_gallery_images` WHERE uid='{$this->user->id}'"; // Set SQL query for pagination function

		$page = '&do=my&pid=';

		$pagination		= $this->api->pagination($this->cfg['rop_images'], $page, $sql); // Set pagination

		$list			= $this->image_array(); // Set content to variable

		$data = array(
			"PAGINATION"	=> $pagination,
			"CONTENT"		=> $list,
			"ALBUMS"		=> $this->album_list(),
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
		);

		return $this->api->sp('my/img-list.html', $data);
	}

	private function album_array(){
		$query = $this->db->query("SELECT `a`.id, `a`.title, `a`.`text`, `i`.img
									FROM `qx_gallery_albums` AS `a`
									LEFT JOIN `qx_gallery_images` AS `i`
										ON `i`.aid=`a`.id AND `i`.uid=`a`.uid
									WHERE `a`.uid='{$this->user->id}'
									GROUP BY `a`.id
									ORDER BY RAND()
									LIMIT 2");

		if(!$query || $this->db->num_rows($query)<=0){ return; }

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

			echo $this->api->sp("my/album-id.html", $data);
		}

		return ob_get_clean();
	}

	private function album_list(){

		$data = array(
			"CONTENT" => $this->album_array(),
		);

		return $this->api->sp("my/album-list.html", $data);
	}

	private function get_categories($selected=1){
		$selected = intval($selected);
		$query = $this->db->query("SELECT id, title FROM `qx_gallery_categories` ORDER BY title ASC");

		if(!$query || $this->db->num_rows($query)<=0){ return '<option value="1">Без категории</option>'; }

		ob_start();

		while($ar = $this->db->get_row($query)){
			$select = ($selected==intval($ar['id'])) ? 'selected' : '';
			echo '<option value="'.intval($ar['id']).'" '.$select.'>'.$this->db->HSC($ar['title']).'</option>';
		}

		return ob_get_clean();
	}

	private function get_albums($selected=1){
		$selected = intval($selected);
		$query = $this->db->query("SELECT id, title FROM `qx_gallery_albums` WHERE uid='{$this->user->id}' ORDER BY title ASC");

		if(!$query || $this->db->num_rows($query)<=0){ return '<option value="1">Без альбома</option>'; }

		ob_start();

		while($ar = $this->db->get_row($query)){
			$select = ($selected==intval($ar['id'])) ? 'selected' : '';
			echo '<option value="'.intval($ar['id']).'" '.$select.'>'.$this->db->HSC($ar['title']).'</option>';
		}

		return ob_get_clean();
	}

	private function image_add(){
		$f_security = "img_add";

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Мои изображения" => MOD_URL.'&do=my',
			"Добавление изображения" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Мои изображения — Добавление изображения";

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=my&op=add", "403", 3); }

			$title = $this->db->safesql(trim(@$_POST['title']));

			if(empty($title)){ $this->api->notify("Не заполнено поле названия", "&do=my&op=add", "Ошибка!", 3); }

			$text = $this->db->safesql(trim(@$_POST['text']));

			$cid = intval(@$_POST['cid']);
			$aid = intval(@$_POST['aid']);

			$query = $this->db->query("SELECT COUNT(*) FROM `qx_gallery_albums` WHERE id='$aid' AND uid='{$this->user->id}'");

			if(!$query){ $this->api->notify("SQL Error: #".__LINE__, "&do=my&op=add", "Ошибка!", 3); }

			$ar = $this->db->get_array($query);

			if($ar[0]<=0){ $aid = 0; }

			$query = $this->db->query("SELECT COUNT(*) FROM `qx_gallery_categories` WHERE id='$cid'");

			if(!$query){ $this->api->notify("SQL Error: #".__LINE__, "&do=my&op=add", "Ошибка!", 3); }

			$ar = $this->db->get_array($query);

			if($ar[0]<=0){ $cid = 1; }

			$new_data = array(
				"date_create" => time(),
				"date_update" => time(),
			);

			$new_data = $this->db->safesql(json_encode($new_data));

			if(!isset($_FILES['img']) || empty($_FILES['img']['size'])){ $this->api->notify("Вы не выбрали изображение", "&do=my&op=add", "Ошибка!", 3); }

			$new_name = $this->upload_image(@$_FILES);

			if($new_name===false){ $this->api->notify("Не удалось загрузить файлы на сервер", "&do=my&op=add", "Ошибка!", 3); }

			$public = (intval(@$_POST['public'])==1) ? 1 : 0;

			$insert = $this->db->query("INSERT INTO `qx_gallery_images`
											(uid, aid, cid, title, `text`, img, `public`, `data`)
										VALUES
											('{$this->user->id}', '$aid', '$cid', '$title', '$text', '$new_name', '$public', '$new_data')");

			if(!$insert){ $this->api->notify("SQL Error: #".__LINE__, "&do=my&op=add", "Ошибка!", 3); }

			$this->api->notify("Новое изображение успешно добавлено", "&do=my", "Поздравляем!", 1);
		}

		$data = array(
			"TITLE"			=> '',
			"TEXT"			=> '',
			"CATEGORIES"	=> $this->get_categories(),
			"ALBUMS"		=> $this->get_albums(),
			"PUBLIC"		=> '',
			"IMG"			=> '',
			"BTN"			=> 'Добавить',
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
		);

		return $this->api->sp("my/img-add.html", $data);
	}

	private function upload_image($file){

		if(empty($file['img']['size'])){ $this->api->notify("Вы не выбрали файл", "&do=my&op=edit&iid=$id", "Ошибка!", 3); }
			
		switch($file['img']['error']){
			case 0: break;
			case 1:
			case 2: $this->api->notify("Максимально допустимый размер файла 2 MB", "&do=my&op=edit&iid=$id", "Ошибка!", 3); break;
			case 3:
			case 4: $this->api->notify("Ошибка загрузки файла", "&do=my&op=edit&iid=$id", "Ошибка!", 3); break;
			case 6: $this->api->notify("Отсутствует временная папка", "&do=my&op=edit&iid=$id", "Ошибка!", 3); break;
			case 7: $this->api->notify("Отсутствуют права на запись", "&do=my&op=edit&iid=$id", "Ошибка!", 3); break;
			default: $this->api->notify("Неизвестная ошибка", "&do=my&op=edit&iid=$id", "Ошибка!", 3); break;
		}

		if(!file_exists($file['img']['tmp_name'])){
			$this->api->notify("Временный файл не существует", "&do=my&op=edit&iid=$id", "Ошибка!", 3);
		}

		$name_img = mb_strtolower($file['img']['name'], 'UTF-8');

		$ext_img = substr(strrchr($name_img, '.'), 1);

		$gis_img = @getimagesize($file['img']['tmp_name']);

		if(!in_array($ext_img, $this->formats)){
			$this->api->notify("Разрешено загружать только форматы: ".$this->db->HSC(implode(', ', $this->formats)), "&do=my&op=add", "Ошибка!", 3);
		}

		if(!$gis_img){ $this->api->notify("Неверный формат изображения", "&do=my&op=edit&iid=$id", "Ошибка!", 3); }

		$new_name = $this->db->safesql(md5($this->api->gen(24)).'.'.$ext_img);

		if(!move_uploaded_file($file['img']['tmp_name'], MCR_ROOT.'qx_upload/gallery/'.$this->user->id.'/'.$new_name)){
			return false;
		}

		return $new_name;
	}

	private function image_edit(){
		$f_security = "img_edit";

		$id = intval(@$_GET['iid']);

		$array = array(
			"Главная" => BASE_URL,
			$this->cfg['title'] => MOD_URL,
			"Мои изображения" => MOD_URL.'&do=my',
			"Редактирование изображения" => '',
		);

		$this->bc		= $this->api->bc($array); // Set breadcrumbs
		$this->title	= "Мои изображения — Редактирование изображения";

		$query = $this->db->query("SELECT aid, cid, title, `text`, img, `public`, `data` FROM `qx_gallery_images` WHERE id='$id' AND uid='{$this->user->id}'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Hacking Attempt!", "&do=my", "403", 3); }

		$ar = $this->db->get_row($query);

		$img = $ar['img'];

		$data = json_decode($ar['data'], true);

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=my&op=edit&iid=$id", "403", 3); }

			$title = $this->db->safesql(trim(@$_POST['title']));

			if(empty($title)){ $this->api->notify("Не заполнено поле названия", "&do=my&op=edit&iid=$id", "Ошибка!", 3); }

			$text = $this->db->safesql(trim(@$_POST['text']));

			$cid = intval(@$_POST['cid']);
			$aid = intval(@$_POST['aid']);

			$query = $this->db->query("SELECT COUNT(*) FROM `qx_gallery_albums` WHERE id='$aid' AND uid='{$this->user->id}'");

			if(!$query){ $this->api->notify("SQL Error: #".__LINE__, "&do=my&op=edit&iid=$id", "Ошибка!", 3); }

			$ar = $this->db->get_array($query);

			if($ar[0]<=0){ $aid = 0; }

			$query = $this->db->query("SELECT COUNT(*) FROM `qx_gallery_categories` WHERE id='$cid'");

			if(!$query){ $this->api->notify("SQL Error: #".__LINE__, "&do=my&op=edit&iid=$id", "Ошибка!", 3); }

			$ar = $this->db->get_array($query);

			if($ar[0]<=0){ $cid = 1; }

			$data['date_update'] = time();

			$new_data = $this->db->safesql(json_encode($data));

			$new_name = "`img`";

			if(isset($_FILES['img']) && !empty($_FILES['img']['size'])){

				$new_name = $this->upload_image(@$_FILES);

				if($new_name===false){ $this->api->notify("Не удалось загрузить файлы на сервер", "&do=my&op=edit&iid=$id", "Ошибка!", 3); }
				
				$new_name = "'$new_name'";

				$old_name = MCR_ROOT.'qx_upload/gallery/'.$this->user->id.'/'.$img;

				if(file_exists($old_name)){ @unlink($old_name); }
			}

			$public = (intval(@$_POST['public'])==1) ? 1 : 0;

			$update = $this->db->query("UPDATE `qx_gallery_images`
										SET aid='$aid', cid='$cid', title='$title', `text`='$text', img=$new_name, `public`='$public', `data`='$new_data'
										WHERE id='$id' AND uid='{$this->user->id}'");

			if(!$update){ $this->api->notify("SQL Error: #".__LINE__, "&do=my&op=edit&iid=$id", "Ошибка!", 3); }

			$this->api->notify("Новое изображение успешно добавлено", "&do=my&op=edit&iid=$id", "Поздравляем!", 1);
		}

		$data = array(
			"TITLE"			=> $this->db->HSC($ar['title']),
			"TEXT"			=> $this->db->HSC($ar['text']),
			"CATEGORIES"	=> $this->get_categories($ar['cid']),
			"ALBUMS"		=> $this->get_albums($ar['aid']),
			"PUBLIC"		=> (intval($ar['public'])==1) ? 'checked' : '',
			"IMG"			=> '<img src="'.BASE_URL.'qx_upload/gallery/'.$this->user->id.'/'.$this->db->HSC($ar['img']).'" alt="IMG" />',
			"BTN"			=> 'Сохранить',
			"F_SET"			=> $this->api->csrf_set($f_security),
			"F_SECURITY"	=> $f_security,
		);

		return $this->api->sp("my/img-add.html", $data);
	}

	private function image_delete(){

		$f_security		= 'my_delete';

		if($_SERVER['REQUEST_METHOD']!='POST' || @$_POST['img_action']!='delete'){ $this->api->notify("Hacking Attempt!", "&do=my", "403", 3); }

		if(!$this->api->csrf_check($f_security)){ $this->api->notify("Hacking Attempt!", "&do=my", "403", 3); }

		$ids = @$_POST['images'];

		if(empty($ids)){ $this->api->notify("Вы не выбрали ни одного элемента!", "&do=my", "403", 3); }

		$ids = $this->api->filter_array_integer($ids);

		$ids = implode(',', $ids);

		$query = $this->db->query("SELECT id, img FROM `qx_gallery_images` WHERE id IN ($ids) AND uid='{$this->user->id}'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->api->notify("Вы не выбрали ни одного элемента!", "&do=my", "403", 3); }

		$ids = array();
		$imgs = array();

		while($ar = $this->db->get_row($query)){
			$ids[] = intval($ar['id']);

			$url = MCR_ROOT.'qx_upload/gallery/'.$this->user->id.'/'.$ar['img'];

			if(file_exists($url)){ @unlink($url); }
		}

		$ids = implode(',', $ids);

		$delete = $this->db->query("DELETE FROM `qx_gallery_images` WHERE id IN ($ids)");

		if(!$delete){ $this->api->notify('DB "my" #('.__LINE__.')', "&do=my", "Внимание!", 3); }

		$this->api->notify('Выбранные элементы успешно удалены', "&do=my", "Поздравляем!", 1);
	}

	public function _list(){

		$op = (isset($_GET['op'])) ? $_GET['op'] : '';

		switch($op){
			case 'add': return $this->image_add(); break;
			case 'edit': return $this->image_edit(); break;
			case 'delete': return $this->image_delete(); break;

			default: return $this->image_list(); break;
		}
	}
}

/**
 * Gallery module for WebMCR
 *
 * My class
 * 
 * @author Qexy.org (admin@qexy.org)
 *
 * @copyright Copyright (c) 2015 Qexy.org
 *
 * @version 1.1.0
 *
 */
?>