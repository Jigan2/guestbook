<?php defined('PHPSecurity') or exit('Access Invalid!');
/**
 * Общие функции
 */

/**
 * Создание экземпляров модели базы данных
 *
 * @param string $model Наименование модели
 *
 * @return obj Возвращает объект
 */
function Model($model = null)
{
	try {
		$file = BASE_PATH . '/model/' . $model . '.model.php';

		if (!file_exists($file)) {
			return new Model($model);
		} else {

			require_once($file);

			$class_name = $model . 'Model';

			if (!class_exists($class_name)) {
				throw new Exception('Model Error:  Class ' . $class_name . " doesn't exists!");
			} else {
				return new $class_name();
			}
		}
	} catch (Exception $e) {
		echo $e->getMessage();
	}
}


/**
 * Время записи (microtime)
 *
 * @param        $start
 * @param string $end
 * @param int    $dec
 *
 * @return string
 */
function addUpTime($start, $end = '', $dec = 3)
{
	static $_info = array();
	if (!empty($end)) { // Статистическое время
		if (!isset($_info[$start])) {
			$_info[$start] = microtime(true);
		}

		if (!isset($_info[$end])) {
			$_info[$end] = microtime(true);
		}

		return number_format(($_info[$end] - $_info[$start]), $dec);
	} else { // Рекордное время
		$_info[$start] = microtime(true);
	}
}

/*
 * Переопределить $_SERVER['REQUREST_URI']
 */
function request_uri()
{
	if (isset($_SERVER['REQUEST_URI'])) {
		$uri = $_SERVER['REQUEST_URI'];
	} else {
		if (isset($_SERVER['argv'])) {
			$uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0];
		} else {
			$uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
		}
	}

	return $uri;
}

/**
 * Исходный адрес
 *
 * @param
 *
 * @return string
 */
function getReferer()
{
	return empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
}

/**
 * Создать проверочный код
 *
 * @param string $nchash
 *
 * @return string
 */
function makeSeccode($nchash)
{
	$seccode      = random(6, 1);
	$seccodeunits = '';

	$s            = sprintf('%04s', base_convert($seccode, 10, 23));
	$seccodeunits = 'ABCEFGHJKMPRTVXY2346789';

	if ($seccodeunits) {
		$seccode = '';
		for ($i = 0; $i < 4; $i++) {
			$unit    = ord($s{$i});
			$seccode .= ($unit >= 48 && $unit <= 57) ? $seccodeunits[$unit - 48] : $seccodeunits[$unit - 87];
		}
	}

	setNcCookie('seccode' . $nchash, encrypt(strtoupper($seccode) . "\t" . (time()) . "\t" . $nchash, MD5_KEY), 3600);

	return $seccode;
}

/**
 * Проверка Seccode
 *
 * @param string $nchash
 * @param string $value
 *
 * @return boolean
 */
function checkSeccode($nchash, $value)
{
	list($checkvalue, $checktime, $checkidhash) = explode("\t", decrypt(getNcCookie('seccode' . $nchash), MD5_KEY));

	$return = $checkvalue == strtoupper($value) && $checkidhash == $nchash;

	if (!$return) {
		setNcCookie('seccode' . $nchash, '', -3600);
	}

	return $return;
}

/**
 * Настройка cookie
 *
 * @param string $name   cookie название
 * @param string $value  cookie значение
 * @param int    $expire cookie активный цикл
 * @param string $path   cookie по умолчанию путь /
 * @param string $domain cookie домен
 * @param string $secure безопасное HTTPS соединение для передачи cookie, по умолчанию false
 */
function setNcCookie($name, $value, $expire = '3600', $path = '', $domain = '', $secure = false)
{
	if (empty($path)) {
		$path = '/';
	}

	if (empty($domain)) {
		$domain = '';
	}

	$name           = defined('COOKIE_PRE') ? COOKIE_PRE . $name : strtoupper(substr(md5(MD5_KEY), 0, 4)) . '_' . $name;
	$expire         = intval($expire) ? intval($expire) : (intval(SESSION_EXPIRE) ? intval(SESSION_EXPIRE) : 3600);
	$result         = @setcookie($name, $value, time() + $expire, $path, $domain, $secure);
	$_COOKIE[$name] = $value;
}

/**
 * Получение значения файла cookie
 *
 * @param string $name
 *
 * @return unknown
 */
function getNcCookie($name = '')
{
	$name = defined('COOKIE_PRE') ? COOKIE_PRE . $name : strtoupper(substr(md5(MD5_KEY), 0, 4)) . '_' . $name;

	return $_COOKIE[$name];
}

/**
 * Получение значения hash кода проверки
 *
 * @param
 *
 * @return string
 */
function getNchash($act = '', $op = '')
{
	$act = $act ? $act : $_GET['act'];
	$op  = $op ? $op : $_GET['op'];

	return substr(md5(SITE_URL . $act . $op), 0, 8);
}

/**
 * Возвращает полный каталог файла шаблона
 *
 * @param str $tplpath
 *
 * @return string
 */
function template($tplpath)
{
	$tpl = BASE_PATH . '/templates/' . TPL_NAME . '/' . $tplpath . '.html';

	return $tpl;
}

/**
 * Замена url
 *
 * @param array $param
 */
function replaceParam($param)
{
	$current_param = $_GET;
	$purl          = array();
	$purl['act']   = $current_param['act'];
	unset($current_param['act']);
	$purl['op'] = $current_param['op'];
	unset($current_param['op']);
	unset($current_param['curpage']);
	$purl['param'] = $current_param;

	if (!empty($param)) {
		foreach ($param as $key => $val) {
			$purl['param'][$key] = $val;
		}
	}

	return 'index.php?act=' . $purl['act'] . '&op=' . $purl['op'] . '&' . http_build_query($purl['param']);
}

/**
 * Обнаружение фиксации формы
 *
 * @param  $check_token   Проверить token
 * @param  $check_captcha Проверить captcha
 * @param  $return_type   'alert','num'
 *
 * @return boolean
 */
function chksubmit($check_token = false, $check_captcha = false, $return_type = 'alert')
{
	if (isset($_POST['form_submit'])) {
		$submit = $_POST['form_submit'];
	} elseif (isset($_GET['form_submit'])) {
		$submit = $_GET['form_submit'];
	}

	if ($submit != 'ok') {
		return false;
	}

	if ($check_token && !Security::checkToken()) {
		if ($return_type == 'alert') {
			showDialog('Token error!');
		} else {
			return -11;
		}
	}

	if ($check_captcha) {
		if (!checkSeccode($_POST['nchash'], $_POST['captcha'])) {
			setNcCookie('seccode' . $_POST['nchash'], '', -3600);
			if ($return_type == 'alert') {
				showDialog('Неверный код!');
			} else {
				return -12;
			}
		}

		setNcCookie('seccode' . $_POST['nchash'], '', -3600);
	}

	return true;
}

/**
 * Функция зашифровки
 *
 * @param string $txt Строка, которая должна быть зашифрована
 * @param string $key Ключ
 *
 * @return string
 */
function encrypt($txt, $key = '')
{
	if (empty($txt)) {
		return $txt;
	}

	if (empty($key)) {
		$key = md5(MD5_KEY);
	}

	$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
	$ikey  = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
	$nh1   = rand(0, 64);
	$nh2   = rand(0, 64);
	$nh3   = rand(0, 64);
	$ch1   = $chars{$nh1};
	$ch2   = $chars{$nh2};
	$ch3   = $chars{$nh3};
	$nhnum = $nh1 + $nh2 + $nh3;
	$knum  = 0;
	$i     = 0;

	while (isset($key{$i})) {
		$knum += ord($key{$i++});
	}

	$mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
	$txt   = base64_encode(time() . '_' . $txt);
	$txt   = str_replace(array('+', '/', '='), array('-', '_', '.'), $txt);
	$tmp   = '';
	$j     = 0;
	$k     = 0;
	$tlen  = strlen($txt);
	$klen  = strlen($mdKey);

	for ($i = 0; $i < $tlen; $i++) {
		$k   = $k == $klen ? 0 : $k;
		$j   = ($nhnum + strpos($chars, $txt{$i}) + ord($mdKey{$k++})) % 64;
		$tmp .= $chars{$j};
	}

	$tmplen = strlen($tmp);
	$tmp    = substr_replace($tmp, $ch3, $nh2 % ++$tmplen, 0);
	$tmp    = substr_replace($tmp, $ch2, $nh1 % ++$tmplen, 0);
	$tmp    = substr_replace($tmp, $ch1, $knum % ++$tmplen, 0);

	return $tmp;
}

/**
 * Функция расшифровки
 *
 * @param string $txt Строка для расшифровки
 * @param string $key Секретный ключ
 *
 * @return string
 */
function decrypt($txt, $key = '', $ttl = 0)
{
	if (empty($txt)) {
		return $txt;
	}

	if (empty($key)) {
		$key = md5(MD5_KEY);
	}

	$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
	$ikey  = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
	$knum  = 0;
	$i     = 0;
	$tlen  = @strlen($txt);

	while (isset($key{$i})) {
		$knum += ord($key{$i++});
	}

	$ch1   = @$txt{$knum % $tlen};
	$nh1   = strpos($chars, $ch1);
	$txt   = @substr_replace($txt, '', $knum % $tlen--, 1);
	$ch2   = @$txt{$nh1 % $tlen};
	$nh2   = @strpos($chars, $ch2);
	$txt   = @substr_replace($txt, '', $nh1 % $tlen--, 1);
	$ch3   = @$txt{$nh2 % $tlen};
	$nh3   = @strpos($chars, $ch3);
	$txt   = @substr_replace($txt, '', $nh2 % $tlen--, 1);
	$nhnum = $nh1 + $nh2 + $nh3;
	$mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
	$tmp   = '';
	$j     = 0;
	$k     = 0;
	$tlen  = @strlen($txt);
	$klen  = @strlen($mdKey);

	for ($i = 0; $i < $tlen; $i++) {
		$k = $k == $klen ? 0 : $k;
		$j = strpos($chars, $txt{$i}) - $nhnum - ord($mdKey{$k++});

		while ($j < 0) {
			$j += 64;
		}

		$tmp .= $chars{$j};
	}

	$tmp = str_replace(array('-', '_', '.'), array('+', '/', '='), $tmp);
	$tmp = trim(base64_decode($tmp));

	if (preg_match("/\d{10}_/s", substr($tmp, 0, 11))) {
		if ($ttl > 0 && (time() - substr($tmp, 0, 11) > $ttl)) {
			$tmp = null;
		} else {
			$tmp = substr($tmp, 11);
		}
	}

	return $tmp;
}

/**
 * Получить IP
 *
 *
 * @return string
 */
function getIp()
{
	if (@$_SERVER['HTTP_CLIENT_IP'] && $_SERVER['HTTP_CLIENT_IP'] != 'unknown') {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (@$_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown') {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return preg_match('/^\d[\d.]+\d$/', $ip) ? $ip : '';
}

/**
 * Получение случайных чисел
 *
 * @param int $length длина
 * @param int $numeric
 *
 * @return string
 */
function random($length, $numeric = 0)
{
	$seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
	$hash = '';
	$max  = strlen($seed) - 1;
	for ($i = 0; $i < $length; $i++) {
		$hash .= $seed{mt_rand(0, $max)};
	}

	return $hash;
}

/**
 * Дмалоговое окно
 *
 * @param string $msg       сообщение
 * @param        string     /array $url перейти по адресу, когда $url при задании структуры массива array('msg'=>'Сообщение','url'=>'Перейти по адресу');
 * @param string $show_type формат по умолчанию html
 * @param string $msg_type  Тип информации succ успешно, error ошибка
 * @param string $is_show   Показать ссылку перехода, по умолчанию 1, показать
 * @param int    $time      Время перехода, по умолчанию 3 секунды
 *
 * @return string
 */
function showMessage($msg, $url = '', $show_type = 'html', $msg_type = 'succ', $is_show = 1, $time = 3000)
{
	/**
	 * Если по умолчанию пусто, перейти к предыдущей ссылке
	 */
	$url = ($url != '' ? $url : getReferer());

	$msg_type = in_array($msg_type, array('succ', 'error')) ? $msg_type : 'error';

	/**
	 * Тип вывода
	 */
	switch ($show_type) {
		case 'json':
			$return = '{';
			$return .= '"msg":"' . $msg . '",';
			$return .= '"url":"' . $url . '"';
			$return .= '}';
			echo $return;
			break;
		case 'exception':
			echo '<!DOCTYPE html>';
			echo '<html>';
			echo '<head>';
			echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
			echo '<title></title>';
			echo '<style type="text/css">';
			echo 'body { font-family: "Verdana";padding: 0; margin: 0;}';
			echo 'h2 { font-size: 12px; line-height: 30px; border-bottom: 1px dashed #CCC; padding-bottom: 8px;width:800px; margin: 20px 0 0 150px;}';
			echo 'dl { float: left; display: inline; clear: both; padding: 0; margin: 10px 20px 20px 150px;}';
			echo 'dt { font-size: 14px; font-weight: bold; line-height: 40px; color: #333; padding: 0; margin: 0; border-width: 0px;}';
			echo 'dd { font-size: 12px; line-height: 40px; color: #333; padding: 0px; margin:0;}';
			echo '</style>';
			echo '</head>';
			echo '<body>';
			echo '<h2>Системная информация</h2>';
			echo '<dl>';
			echo '<dd>' . $msg . '</dd>';
			echo '<dt><p /></dt>';
			echo '<dd>Ошибка в работе системы</dd>';
			echo '<dd><p /><p /><p /><p /></dd>';
			echo '</dl>';
			echo '</body>';
			echo '</html>';
			exit;
			break;
		case 'javascript':
			echo "<script>";
			echo "alert('" . $msg . "');";
			echo "location.href='" . $url . "'";
			echo "</script>";
			exit;
			break;
		default:
			Tpl::setDir('');
			Tpl::output('html_title', 'Сообщение');
			Tpl::output('msg', $msg);
			Tpl::output('url', $url);
			Tpl::output('msg_type', $msg_type);
			Tpl::output('is_show', $is_show);
			Tpl::showpage('msg', 'msg_layout', $time);
	}
	exit;
}

/**
 * AJAX оповещение
 *
 * @param string $message    сообщение
 * @param string $url        url перехода
 * @param stting $alert_type Тип подсказки error/succ/notice
 * @param string $extrajs    Расширение JS
 * @param int    $time       Время до закрытия или перехода
 */
function showDialog($message = '', $url = '', $alert_type = 'error', $extrajs = '', $time = 3)
{
	if (empty($_GET['inajax'])) {
		if ($url == 'reload') {
			$url = '';
		}
		showMessage($message . $extrajs, $url, 'html', $alert_type, 1, $time * 1000);
	}
	$message = str_replace("'", "\\'", strip_tags($message));

	$paramjs = null;
	if ($url == 'reload') {
		$paramjs = 'window.location.reload()';
	} elseif ($url != '') {
		$paramjs = 'window.location.href =\'' . $url . '\'';
	}
	if ($paramjs) {
		$paramjs = 'function (){' . $paramjs . '}';
	} else {
		$paramjs = 'null';
	}
	$modes = array('error' => 'alert', 'succ' => 'succ', 'notice' => 'notice', 'js' => 'js');
	$cover = $alert_type == 'error' ? 1 : 0;
	$extra = 'showDialog(\'' . $message . '\', \'' . $modes[$alert_type] . '\', null, ' . ($paramjs ? $paramjs : 'null') . ', ' . $cover . ', null, null, null, null, ' . (is_numeric($time) ? $time : 'null') . ', null);';
	$extra = $extra ? '<script type="text/javascript" reload="1">' . $extra . '</script>' : '';
	if ($extrajs != '' && substr(trim($extrajs), 0, 7) != '<script') {
		$extrajs = '<script type="text/javascript" reload="1">' . $extrajs . '</script>';
	}
	$extra .= $extrajs;
	@ob_end_clean();
	@header("Expires: -1");
	@header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", false);
	@header("Pragma: no-cache");
	@header("Content-type: text/xml; charset=UTF-8");

	$string = '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
	$string .= '<root><![CDATA[' . $message . $extra . ']]></root>';
	echo $string;
	exit;
}
