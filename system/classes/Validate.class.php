<?php defined('PHPSecurity') or exit('Access Invalid!');

/**
 * Класс проверки
 */
class Validate
{
	/**
	 * Хранение сведений о проверке подлинности
	 *
	 * @var array
	 */
	public $validateparam = array();

	/**
	 * Правила проверки
	 *
	 * @var array
	 */
	private $validator = array(
		"email"           => '/^([.a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\\.[a-zA-Z0-9_-])+/',
		"phone"           => '/^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$/',
		"mobile"          => '/^\+?\d([0-9 \-\(\)]){7,20}$/',
		"url"             => '/^https?:(\\/){2}[A-Za-z0-9]+.[A-Za-z0-9]+[\\/=?%-&_~`@\\[\\]\':+!]*([^<>\"\"])*$/',
		"currency"        => '/^[0-9]+(\\.[0-9]+)?$/',
		"number"          => '/^[0-9]+$/',
		"zip"             => '/^[0-9][0-9]{5}$/',
		"integer"         => '/^[-+]?[0-9]+$/',
		"integerpositive" => '/^[+]?[0-9]+$/',
		"double"          => '/^[-+]?[0-9]+(\\.[0-9]+)?$/',
		"doublepositive"  => '/^[+]?[0-9]+(\\.[0-9]+)?$/',
		"english"         => '/^[A-Za-z]+$/',
		"latin"           => '/^([- A-Za-z0-9_@ ,.\/\\"-:;]+)*?$/',
		"username"        => '/^[A-Za-z0-9\x{4e00}-\x{9fa5}_]/u',
	);


	/**
	 * Validate constructor.
	 */
	public function validate()
	{
		if (!is_array($this->validateparam)) {
			return false;
		}

		foreach ($this->validateparam as $k => $v) {
			if (isset($v['validator'])) {
				$v['validator'] = strtolower($v['validator']);
			}

			if (empty($v['require'])) {
				$v['require'] = false;
			}

			if ($v['input'] == "" && $v['require'] == "true") {
				$this->validateparam[$k]['result'] = false;
			} else {
				$this->validateparam[$k]['result'] = true;
			}

			if ($this->validateparam[$k]['result'] && !empty($v['input'])) {
				if (isset($v['validator'])) {
					switch ($v['validator']) {
						case "custom":
							$this->validateparam[$k]['result'] = $this->check($v['input'], $v['regexp']);
							break;
						case "compare":
							if ($v['operator'] != "") {
								eval("\$result = '" . $v['input'] . "'" . $v['operator'] . "'" . $v['to'] . "'" . ";");
								$this->validateparam[$k]['result'] = $result;
							}
							break;
						case "length":
							// Определение длины закодированной строки
							$input_encode = mb_detect_encoding($v['input'], array('UTF-8', 'windows-1251', 'ASCII',));
							$input_length = mb_strlen($v['input'], $input_encode);

							if (intval($v['min']) >= 0 && intval($v['max']) > intval($v['min'])) {
								$this->validateparam[$k]['result'] = ($input_length >= intval($v['min']) && $input_length <= intval($v['max']));
							} else if (intval($v['min']) >= 0 && intval($v['max']) <= intval($v['min'])) {
								$this->validateparam[$k]['result'] = ($input_length == intval($v['min']));
							}
							break;
						case "range":
							if (intval($v['min']) >= 0 && intval($v['max']) > intval($v['min'])) {
								$this->validateparam[$k]['result'] = (intval($v['input']) >= intval($v['min']) && intval($v['input']) <= intval($v['max']));
							} else if (intval($v['min']) >= 0 && intval($v['max']) <= intval($v['min'])) {
								$this->validateparam[$k]['result'] = (intval($v['input']) == intval($v['min']));
							}
							break;
						default:
							$this->validateparam[$k]['result'] = $this->check($v['input'], $this->validator[$v['validator']]);
					}
				}
			}
		}
		$error = $this->getError();
		$this->validateparam = array();

		return $error;
	}

	/**
	 * Операции с регулярными выражениями
	 *
	 * @param string $str
	 * @param string $validator
	 *
	 * @return bool
	 */
	private function check($str = '', $validator = '')
	{
		if ($str != "" && $validator != "") {
			if (preg_match($validator, $str)) {
				return true;
			} else {
				return false;
			}
		}

		return true;
	}

	/**
	 * Что необходимо проверить
	 *
	 * @param array $validateparam array("input"=>"","require"=>"","validator"=>"","regexp"=>"","operator"=>"","to"=>"","min"=>"","max"=>"",message=>"")
	 *                             Проверяемое значение input
	 *                             require если требуется, true является обязательным false является необязательным
	 *                             validator Тип проверки: Compare, Custom, Length, Range
	 *                             Compare используется для сравнения 2 строк или чисел, operator to, operator операторы сравнения (==,>,<,>=,<=,!=) to используется для сравнения строк;
	 *                             Custom является пользовательской проверкой правил, регулярное выражение, используемое в сочетании с регулярными выражениями;
	 *                             Length длина строки или числа находится в диапазоне от, min и max используются в сочетании с, min является наименьшая длина, max максимальная длина, если не писать max, то считается что длина должна быть равна min;
	 *                             Range является ли число в диапазоне, и min и max используются в сочетании.
	 *                            Примечательно, что если правила, которые будут судить, являются более сложными, целесообразно писать регулярные выражения непосредственно.
	 *
	 * @return void
	 */
	public function setValidate($validateparam)
	{
		$validateparam["result"] = true;
		$this->validateparam = array_merge($this->validateparam, array($validateparam));
	}

	/**
	 * Сообщение об ошибке
	 *
	 * @param
	 *
	 * @return string
	 */
	private function getError()
	{
		foreach ($this->validateparam as $k => $v) {
			if ($v['result'] == false) {
				return $v['message'];
			}
		}

		return null;
	}
}
?>
