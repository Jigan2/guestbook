<?php defined('PHPSecurity') or exit('Access Invalid!');

/**
 * Класс шаблона
 *
 * @package    tpl
 *
 */
class Tpl
{
	private static $instance = null;
	private static $output_value = array();
	private static $tpl_dir = '';
	private static $tpl_dirtpl = '';
	private static $layout_file = 'layout';

	private function __construct()
	{
	}

	/**
	 * Экземпляр
	 *
	 * @return obj
	 */
	public static function getInstance()
	{
		if (self::$instance === null || !(self::$instance instanceof Tpl)) {
			self::$instance = new Tpl();
		}

		return self::$instance;
	}

	/**
	 * Настройка каталогов шаблонов
	 *
	 * @param string $dir
	 *
	 * @return bool
	 */
	public static function setDir($dir)
	{
		self::$tpl_dir = $dir;

		return true;
	}

	/**
	 * Задать макет
	 *
	 * @param string $layout
	 *
	 * @return bool
	 */
	public static function setLayout($layout)
	{
		self::$layout_file = $layout;

		return true;
	}

	/**
	 * Отправить переменную в макет
	 *
	 * @param mixed $output
	 * @param       void
	 */
	public static function output($output, $input = '')
	{
		self::getInstance();

		self::$output_value[$output] = $input;
	}

	/**
	 * Вызов шаблона отображения
	 *
	 * @param string $page_name
	 * @param string $layout
	 * @param int    $time
	 */
	public static function showpage($page_name = '', $layout = '', $time = 2000)
	{
		try {
			if (!defined('TPL_NAME')) {
				define('TPL_NAME', 'default');
			}

			self::getInstance();

			$tpl_dir = '';

			if (!empty(self::$tpl_dir)) {
				$tpl_dir = self::$tpl_dir . '/';
			}

			//По умолчанию используется файл макета
			if (empty($layout)) {
				$layout = 'layout/' . self::$layout_file . '.html';
			} else {
				$layout = 'layout/' . $layout . '.html';
			}

			$layout_file = BASE_PATH . '/templates/' . TPL_NAME . '/' . $layout;

			if (empty(self::$tpl_dirtpl)) {
				$tpl_file = BASE_PATH . '/templates/' . TPL_NAME . '/' . $tpl_dir . $page_name . '.html';
			} else {
				$tpl_file = BASE_PATH . '/modules/' . self::$tpl_dirtpl . '/templates/' . TPL_NAME . '/' . $tpl_dir . $page_name . '.html';
			}

			if (file_exists($tpl_file)) {
				//Назначение переменных шаблона
				$output = self::$output_value;

				//Заголовок страницы
				$output['html_title'] = !empty($output['html_title']) ? $output['html_title'] : 'Title';
				$output['seo_keywords'] = !empty($output['seo_keywords']) ? $output['seo_keywords'] : 'Keywords';
				$output['seo_description'] = !empty($output['seo_description']) ? $output['seo_description'] : 'Description';
				$output['ref_url'] = getReferer();

				@header("Content-type: text/html; charset=UTF-8");
				// Определите, следует ли выводить шаблон с помощью макета, если это так, включите файл макета и включите файл шаблона в файл макета
				if ($layout != '') {
					if (file_exists($layout_file)) {
						include_once($layout_file);
					} else {
						throw new Exception('Tpl ERROR:' . 'templates' . '/' . $layout . ' is not exists');
					}
				} else {
					include_once($tpl_file);
				}
			} else {
				throw new Exception('Tpl ERROR:' . 'templates' . '/' . $tpl_dir . $page_name . '.html' . ' is not exists');
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}