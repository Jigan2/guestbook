<?php defined('PHPSecurity') or exit('Access Invalid!');

/**
 * Проверочный код
 *
 */
class seccodeControl
{
	/**
	 * Создать проверочный код
	 *
	 */
	public function makecodeOp()
	{
		if (isset($_SERVER['HTTP_REFERER'])) {
			$refererhost = parse_url($_SERVER['HTTP_REFERER']);
			$refererhost['host'] .= !empty($refererhost['port']) ? (':' . $refererhost['port']) : '';
		}

		$seccode = makeSeccode($_GET['nchash']);

		@header("Expires: -1");
		@header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", false);
		@header("Pragma: no-cache");
		$code = new Seccode();
		$code->code = $seccode;
		$code->width = 90;
		$code->height = 26;
		$code->background = 1;
		$code->adulterate = 1;
		$code->scatter = '';
		$code->color = 1;
		$code->size = 0;
		$code->shadow = 1;
		$code->animator = 0;
		$code->datapath = BASE_ROOT_PATH . '/data/resource/seccode/';
		$code->display();
	}

	/**
	 * Проверка AJAX
	 *
	 */
	public function checkOp()
	{
		if (checkSeccode($_GET['nchash'], $_GET['captcha'])) {
			exit('true');
		} else {
			exit('false');
		}
	}
}
