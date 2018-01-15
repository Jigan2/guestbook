<?php defined('PHPSecurity') or exit('Access Invalid!');

/**
 *
 * Базовый класс
 *
 */
final class Base
{

	/**
	 * Автоподгрузка классов
	 *
	 * @param $class
	 */
	public static function autoload($class)
	{
		if (substr($class, -5) == 'Class') {
			if (!@include(BASE_SYSTEM_PATH . '/libraries/' . substr($class, 0, -5) . '.php')) {
				exit("Class Error: {$class} doesn't exists!");
			}
		} else {
			if (!@include(BASE_SYSTEM_PATH . '/classes/' . $class . '.class.php')) {
				exit("Class Error: {$class} doesn't exists!");
			}
		}
	}

	/**
	 * Инициализация
	 */
	public static function init()
	{
		//session start
		self::start_session();

		$act_file = realpath(BASE_PATH . '/control/' . $_GET['act'] . '.php');

		$class_name = $_GET['act'] . 'Control';

		if (!@include($act_file)) {
			echo "Base Error: access file isn't exists!";
		}

		if (class_exists($class_name)) {
			$main = new $class_name();
			$function = $_GET['op'] . 'Op';

			if (method_exists($main, $function)) {
				$main->$function();
			} elseif (method_exists($main, 'indexOp')) {
				$main->indexOp();
			} else {
				echo "Base Error: function $function not in $class_name!";
			}
		} else {
			echo "Base Error: class $class_name isn't exists!";
		}
	}

	private static function start_session()
	{
		if (preg_match("/^[0-9.]+$/", $_SERVER['HTTP_HOST'])) {
			$subdomain_suffix = $_SERVER['HTTP_HOST'];
		} else {
			$split_url = explode('.', $_SERVER['HTTP_HOST']);
			
			if (isset($split_url[2]) && !empty($split_url[2])) {
				unset($split_url[0]);
			}
			
			$subdomain_suffix = implode('.', $split_url);
		}
		//session.name starke Formulierung hergestellt PHPSESSID, erlaubt keine Änderungen
		@ini_set('session.name', 'PHPSESSID');
		$subdomain_suffix = str_replace('http://', '', $subdomain_suffix);

		if ($subdomain_suffix !== 'localhost') {
			@ini_set('session.cookie_domain', $subdomain_suffix);
		}

		//Хранить сведения о сеансе в виде файла по умолчанию
		session_save_path(BASE_ROOT_PATH . '/data/session');
		session_start();
	}
}