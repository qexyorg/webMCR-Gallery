<?php 
if (!defined('MCR')) exit;

$page = 'Галерея'; $menu->SetItemActive('qx_gallery');

define('QX_URL_ROOT', BASE_URL.'go/gallery/'); // Ссылка на галлерею
define('QX_DIR_STYLE', STYLE_URL.'Default/gallery/'); // Директория со стилями
define('QX_URL_UPLOAD', BASE_URL.'qx_upload/gallery/'); // Ссылка с изображениями
define('QX_DIR_UPLOAD', MCR_ROOT.'qx_upload/gallery/'); // Директория с изображениями
define('QX_DIR_THUMBS', QX_DIR_UPLOAD.'thumbs/'); // Директория с превьюшками
define('QX_ROP', 20); // результатов на страницу
define('QX_SIZE', 165); // максимальная ширина в пикселях для миниатюры

$_SESSION['num_q'] = 0;

function MFA($result){		return mysql_fetch_array($result);			}
function MNR($result){		return mysql_num_rows($result);				}
function MRES($result){		return mysql_real_escape_string($result);	}
function HSC($result){		return htmlspecialchars($result);			}
function QX_QUERY($query){	$_SESSION['num_q']++; return BD($query);	}

require_once(MCR_ROOT.'instruments/gallery.class.php'); $gallery = new gallery;

if(isset($_SESSION['qx_info'])){ define('QX_INFO', $gallery->INFO()); }else{ define('QX_INFO', ''); }

$content_js .= $gallery->gallery_js();

if(isset($_GET['do'])){ $do = $_GET['do']; }else{ $do = 'main'; }

if($gallery->is_install()){ $do = 'install'; }

switch($do){
	case 'image': echo $gallery->image(); exit; break;
	case 'thumb': echo $gallery->image(true); exit; break;
	case 'install':	$content_main = $gallery->install();	break;

	default: $content_main = $gallery->main(); break;
}

unset($_SESSION['num_q']);
if(isset($_SESSION['qx_info'])){unset($_SESSION['qx_info']); unset($_SESSION['qx_info_t']);}
?>