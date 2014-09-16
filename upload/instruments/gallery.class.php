<?php

class gallery {

	public function is_install(){
		if(!isset($_COOKIE['gallery_install'])){ return true; }
		return false;
	}

	public function install(){
		global $config, $player_lvl;
		ob_start();
		if($player_lvl<15){header("Location: ".BASE_URL); exit;}
		if(!$this->is_install()){ include_once(MCR_ROOT.'install_gallery/install-good.html'); return ob_get_clean(); }

		if($_SERVER['REQUEST_METHOD']=='POST'){
			if(isset($_POST['install'])){
				include_once(MCR_ROOT.'install_gallery/sql.php');
				if(!$sql){ self::setINFO('Ошибка установки', 'install/', 3); }
				setcookie( "gallery_install", 'true', time()*2, '/');
				self::setINFO('Установка успешно завершена!', 'install/', 1);
			}
		}
		
		include_once(MCR_ROOT.'install_gallery/install.html');

		return ob_get_clean();
	}

	private static function init_panel($id){
		ob_start();
		include(QX_DIR_STYLE.'panel.html');
		return ob_get_clean();
	}

	private static function init_formadd(){
		global $user;
		ob_start();
		if(!empty($user)){ include(QX_DIR_STYLE.'form-add.html'); }
		return ob_get_clean();
	}

	public function INFO(){
		ob_start();
		switch($_SESSION['qx_info_t']){
			case 1: $type = 'alert-success'; break;
			case 2: $type = 'alert-info'; break;
			case 3: $type = 'alert-error'; break;

			default: $type = ''; break;
		}

		include_once(QX_DIR_STYLE.'info.html');
		return ob_get_clean();
	}

	private static function setINFO($text, $url='', $type=4){
		$_SESSION['qx_info'] = $text;
		$_SESSION['qx_info_t'] = $type;
		header('Location: '.QX_URL_ROOT.$url); exit;
		return true;
	}

	private static function pagination(){
		ob_start();

		if(isset($_GET['pid'])){$pid = intval($_GET['pid']);}else{$pid = 1;}
		$query = BD("SELECT COUNT(*) FROM `qx_gallery`");
		$ar = MFA($query);
		$max = intval(ceil($ar[0] / QX_ROP));

		if($pid<=0 || $pid>$max){ return ob_get_clean(); }

		if($max>1)
		{

			$FirstPge					='<li><a href="'.QX_URL_ROOT.'p.1"><<</a></li>';
			if($pid-2>0){$Prev2Pge		='<li><a href="'.QX_URL_ROOT.'p.'.($pid-2).'">'.($pid-2).'</a></li>';}else{$Prev2Pge ='';}
			if($pid-1>0){$PrevPge		='<li><a href="'.QX_URL_ROOT.'p.'.($pid-1).'">'.($pid-1).'</a></li>';}else{$PrevPge ='';}
			$SelectPge					='<li><a href="'.QX_URL_ROOT.'p.'.$pid.'"><b>'.$pid.'</b></a></li>';
			if($pid+1<=$max){$NextPge	='<li><a href="'.QX_URL_ROOT.'p.'.($pid+1).'">'.($pid+1).'</a></li>';}else{$NextPge ='';}
			if($pid+2<=$max){$Next2Pge	='<li><a href="'.QX_URL_ROOT.'p.'.($pid+2).'">'.($pid+2).'</a></li>';}else{$Next2Pge ='';}
			$LastPge					='<li><a href="'.QX_URL_ROOT.'p.'.$max.'">>></a></li>';
			include(QX_DIR_STYLE."pagination.html");
		}

		return ob_get_clean();
	}

	private static function thumbs(){
		global $player_lvl;
		ob_start();

		if(isset($_GET['pid'])){$pid = intval($_GET['pid']);}else{$pid = 1;}
		$start = $pid * QX_ROP - QX_ROP;

		$query = QX_QUERY("SELECT * FROM `qx_gallery` ORDER BY `id` DESC LIMIT $start,".QX_ROP."");
		if(!$query || MNR($query)<=0){ echo '<center>Пусто</center>'; return ob_get_clean(); }
		while($ar = MFA($query)){
			$id			= intval($ar['id']);
			$desc		= HSC($ar['desc']);
			$username	= HSC($ar['username']);
			$image		= HSC($ar['image']);
			$ext		= substr(strrchr($image, '.'), 1);
			$date		= date("d.m.Y в H:i:s", $ar['date']);
			$panel		= ($player_lvl>=15) ? self::init_panel($id) : '';

			include(QX_DIR_STYLE.'thumb.html');
		}

		return ob_get_clean();
	}

	public function main(){
		ob_start();

		self::upload();

		$formadd	= self::init_formadd();
		$thumbs		= self::thumbs();
		$pagination = self::pagination();
		include_once(QX_DIR_STYLE.'main.html');
		return ob_get_clean();
	}

	public function gallery_js(){
		ob_start();
		include_once(QX_DIR_STYLE.'js.html');
		return ob_get_clean();
	}

	private static function namegen($length=10) {

		$chars	= 'abcdefghijklmnopqrstuvwxyz0123456789_';

		$string	= "";

		$len	= strlen($chars) - 1;  
		while (strlen($string) < $length){
			$string .= $chars[mt_rand(0,$len)];  
		}

		return $string;
	}

	private static function upload(){
		global $user, $player, $player_lvl;
		if($_SERVER['REQUEST_METHOD']=='POST' && !empty($user)){
			if(isset($_FILES['image'],$_POST['submit'])){
				$desc		= MRES($_POST['desc']);
				$date		= time();
				$f_array	= array('jpg', 'png', 'jpeg');
				$f_name		= mb_strtolower($_FILES['image']['name']);
				$f_tmp		= $_FILES['image']['tmp_name'];
				$f_error	= intval($_FILES['image']['error']);
				$f_ext		= substr(strrchr($f_name, '.'), 1);
				$f_size		= @getimagesize($f_tmp);
				$f_newname	= self::namegen().'.'.$f_ext;
				$f_newpath	= QX_DIR_UPLOAD.$f_newname;
				$f_newthumb	= QX_DIR_THUMBS.$f_newname;

				if(!in_array($f_ext, $f_array) || empty($f_size)){ self::setINFO('Допустимы только форматы jpg, png, jpeg - ', '', 3); }

				switch($f_error){
					case 0: break;
					case 1:
					case 2: self::setINFO('Максимально допустимый размер файла 2 MB', '', 3); break;
					case 3:
					case 4: self::setINFO('Ошибка загрузки файла!', '', 3); break;
					case 6: self::setINFO('Отсутствует временная папка!', '', 3); break;
					case 7: self::setINFO('Отсутствуют права на запись!', '', 3); break;

					default: self::setINFO('Неизвестная ошибка!', '', 3); break;
				}

				if(!file_exists($f_tmp)) { self::setINFO('Ошибка! Временный файл не существует.', '', 3); }

				switch($f_ext){
					case 'jpg':
					case 'jpeg': $icf = @ImageCreateFromJPEG ($f_tmp); break;
					case 'png': $icf = @ImageCreateFromPNG ($f_tmp); break;

					default: self::setINFO('Неверное расширение изображения.', '', 3); break;
				}

				if(!move_uploaded_file($f_tmp, $f_newpath)){ self::setINFO('Отсутствуют права на запись папки.', '', 3); }

				if($f_size[0]>QX_SIZE){
					$height	= $f_size[1];
					$width	= $f_size[0];
					$factor	= $width / QX_SIZE;
					$new_h	= ceil($height / $factor);
					$ictc	= @ImageCreateTrueColor (QX_SIZE, $new_h);
					@imageAlphaBlending($ictc, false);
					@imageSaveAlpha($ictc, true);
					@ImageCopyResampled ($ictc, $icf, 0, 0, 0, 0, QX_SIZE, $new_h, $width, $height);

					switch($f_ext){
						case 'jpg':
						case 'jpeg': @ImageJPEG ($ictc, $f_newthumb, 100); break;
						case 'png': @ImagePNG ($ictc, $f_newthumb); break;

						default: self::setINFO('Ошибка создания временного файла.', '', 3); break;
					}

				}else{
					if(!copy($f_newpath, $f_newthumb)){ self::setINFO('Ошибка копирования файла.', '', 3); }
				}

				$query = QX_QUERY("INSERT INTO `qx_gallery` (`desc`, image, username, `date`) VALUES ('$desc', '$f_newname', '$player', '$date')");

				if(!$query){ self::setINFO('Ошибка MySQL!', '', 3); }

				@imagedestroy($icf);

				self::setINFO('Файл успешно загружен на сервер.', '', 1);
			}elseif(isset($_POST['delete']) && $player_lvl>=15){
				$id = intval($_POST['delete']);
				$query = QX_QUERY("DELETE FROM `qx_gallery` WHERE id='$id'");

				if(!$query){ self::setINFO('Ошибка удаления!', '', 3); }
				self::setINFO('Изображение успешно удалено из базы!', '', 0);
			}else{
				self::setINFO('Hacking Attempt!', '', 3);
			}

			return true;
		}
	}

	public function image($thumb = false){
		if(!isset($_GET['img']) || empty($_GET['img'])){ self::setINFO('Hacking Attempt!', '', 3); }

		$img_id	= intval($_GET['img']);
		$query	= QX_QUERY("SELECT image FROM `qx_gallery` WHERE id='$img_id'");

		if(!$query || MNR($query)<=0){ self::setINFO('Изображение не существует!', '', 3); }

		$ar		= MFA($query);
		$image	= HSC($ar[0]);
		$format	= substr(strrchr($image, '.'), 1);
		if($format=='jpg'){ $format = 'jpeg'; }

		header("Content-type: image/".$format." ");
		$path = (!$thumb) ? QX_DIR_UPLOAD.$image : QX_DIR_UPLOAD.'thumbs/'.$image;
		echo file_get_contents($path);
	}


}

?>