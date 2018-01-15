<?php defined('PHPSecurity') or exit('Access Invalid!');

/**
 * Класс проверки
 * Фильтрация инъекций SQL, фильтрация XSS и фильтрация CSRF
 *
 * Способ вызова токена
 * Вывод: вызов непосредственно в шаблоне getToken
 * Проверка: вызов в месте проверки checkToken
 *
 */
class Security
{
	/**
	 * Получение токена
	 * Автоматический вывод скрытых полей HTML
	 *
	 * @param
	 *
	 * @return void Возвращает результат в виде строки
	 */
	public static function getToken()
	{
		$token = encrypt(time(), md5(MD5_KEY));
		echo "<input type='hidden' name='formhash' value='" . $token . "' />";
	}

	public static function getTokenValue()
	{
		return encrypt(time(), md5(MD5_KEY));
	}

	/**
	 * Определить правильность токена
	 *
	 * @param
	 *
	 * @return bool Возвращает результат логического типа
	 */
	public static function checkToken()
	{
		$data = decrypt($_POST['formhash'], md5(MD5_KEY));

		return $data && (time() - $data < 5400);
	}

	/**
	 * Заменить символы & " < >
	 *
	 * @param unknown_type $string
	 *
	 * @return unknown
	 */
	public static function fliterHtmlSpecialChars($string)
	{
		$string = strip_tags($string);
		$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1',
			str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));

		return $string;
	}

	/**
	 * Фильтрация параметров
	 *
	 * @param       array   Содержимое параметров
	 * @param array $ignore Игнорировать
	 *
	 * @return array
	 *
	 */
	public static function getAddslashesForInput($array, $ignore = array())
	{
		if (!function_exists('htmlawed')) {
			require(BASE_SYSTEM_PATH . '/function/htmlawed.php');
		}

		if (!empty($array)) {
			while (list($k, $v) = each($array)) {
				if (is_string($v)) {
					if (get_magic_quotes_gpc()) {
						$v = stripslashes($v);
					}

					if ($k != 'statistics_code') {
						if (!in_array($k, $ignore)) {
							// Если это не редактор, то удаляем < > & "
							$v = self::fliterHtmlSpecialChars($v);
						} else {
							$v = htmlawed($v, array(
								'safe'     => 1,
								'elements' => 'a,abbr,acronym,address,area,b,bdo,big,blockquote,br,button,caption,center,cite,code,col,colgroup,dd,del,dfn,dir,div,dl,dt,em,fieldset,font,form,h1,h2,h3,h4,h5,h6,hr,i,iframe,img,input,ins,isindex,kbd,label,legend,li,map,menu,ol,optgroup,option,p,param,pre,q,rb,rbc,rp,rt,rtc,s,samp,select,small,span,strike,strong,sub,sup,table,tbody,td,textarea,tfoot,th,thead,tr,tt,u,ul',
							),
								'iframe=src(match="/^https?:\/\/(?:www.)?youtube\.com\/embed\/.*$/i"), allowfullscreen'
							);
						}

						if ($k == 'ref_url') {
							$v = str_replace('&amp;', '&', $v);
						}
					}

					$array[$k] = !in_array($k, $ignore) ? addslashes(trim($v)) : $v;
				} else if (is_array($v)) {
					$array[$k] = self::getAddslashesForInput($v);
				}
			}

			return $array;
		} else {
			return false;
		}
	}

	public static function getAddSlashes($array)
	{
		if (!empty($array)) {
			while (list($k, $v) = each($array)) {
				if (is_string($v)) {
					if (!get_magic_quotes_gpc()) {
						$v = addslashes($v);
					}
				} else if (is_array($v)) {
					$array[$k] = self::getAddSlashes($v);
				}
			}

			return $array;
		} else {
			return false;
		}
	}
}
