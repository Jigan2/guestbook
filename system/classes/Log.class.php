<?php defined('PHPSecurity') or exit('Access Invalid!');

/**
 * Журнал ошибок
 *
 */
class Log
{

	const SQL = 'SQL';
	const ERR = 'ERR';
	private static $log = array();

	public static function record($message, $level = self::ERR)
	{
		$now = @date('Y-m-d H:i:s', time());

		switch ($level) {
			case self::SQL:
				self::$log[] = "[{$now}] {$level}: {$message}\r\n";
				break;
			case self::ERR:
				$log_file = BASE_ROOT_PATH . '/data/log/' . date('Ymd', TIMESTAMP) . '.log';
				$url = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
				$url .= " ( act={$_GET['act']}&op={$_GET['op']} ) ";
				$content = "[{$now}] {$url}\r\n{$level}: {$message}\r\n";
				file_put_contents($log_file, $content, FILE_APPEND);
				break;
		}
	}

	public static function read()
	{
		return self::$log;
	}
}