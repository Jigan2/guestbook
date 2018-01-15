<?php
//error_reporting(E_ALL & ~E_NOTICE);
define('BASE_ROOT_PATH',str_replace('\\','/',dirname(__FILE__)));
define('BASE_SYSTEM_PATH',BASE_ROOT_PATH.'/system');

define('SITE_URL', 'http://kdm.ru'); // Адрес сайта
define('PHPSecurity', true); // Защита файлов от доступа из вне
define('MD5_KEY', md5('771b5bf2f24297a5c373ef2ce549c0e6')); // Ключь шифрования
define('COOKIE_PRE', 'B7C5_'); // Префикс куки
define('SESSION_EXPIRE', 3600); // Срок хранения куки

// Данные соединения с базой данных
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PWD', '1111');
define('DB_NAME', 'test');
define('DB_PORT', '3306');
define('DB_CHARSET', 'UTF-8');
define('DB_TABLE_PREFIX', 'm_');

require BASE_SYSTEM_PATH . '/Base.class.php';
require BASE_SYSTEM_PATH . '/function/core.php';

if(function_exists('spl_autoload_register')) {
	spl_autoload_register(array('Base', 'autoload'));
} else {
	function __autoload($class) {
		return Base::autoload($class);
	}
}

$_GET['act'] = is_string($_GET['act']) ? strtolower($_GET['act']) : (is_string($_POST['act']) ? strtolower($_POST['act']) : null);
$_GET['op']  = is_string($_GET['op']) ? strtolower($_GET['op']) : (is_string($_POST['op']) ? strtolower($_POST['op']) : null);

$_GET['act'] = preg_match('/^[\w]+$/i', $_GET['act']) ? $_GET['act'] : 'index';
$_GET['op']  = preg_match('/^[\w]+$/i', $_GET['op']) ? $_GET['op'] : 'index';

//Если не нужно фильтровать запросы GET POST, то включите в массив $ignore = array('ignore');
$ignore = array();

$_GET = !empty($_GET) ? Security::getAddslashesForInput($_GET, $ignore) : array();
$_POST = !empty($_POST) ? Security::getAddslashesForInput($_POST, $ignore) : array();
$_REQUEST = !empty($_REQUEST) ? Security::getAddslashesForInput($_REQUEST, $ignore) : array();
$_SERVER = !empty($_SERVER) ? Security::getAddSlashes($_SERVER) : array();


