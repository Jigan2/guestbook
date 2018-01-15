<?php
define('APP_ID', 'guestbook');
define('TPL_NAME', 'default');
define('BASE_PATH', str_replace('\\', '/', dirname(__FILE__)));

if (!@include(dirname(dirname(__FILE__)) . '/settings.php')) {
	exit('settings.php isn\'t exists!');
}

if (!@include(BASE_PATH . '/control/control.php')) {
	exit('control.php isn\'t exists!');
}

define('TEMPLATES_URL', SITE_URL . '/' . APP_ID . '/templates/' . TPL_NAME);

Base::init();
