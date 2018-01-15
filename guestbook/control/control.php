<?php defined('PHPSecurity') or exit('Access Invalid!');

/**
 * Контрольный класс
 */
class Control
{
	public function __construct()
	{
		Tpl::setLayout('layout');
		Tpl::output('html_title', 'Гостевая книга');
	}
}