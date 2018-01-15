<?php defined('PHPSecurity') or exit('Access Invalid!');

/**
 * Класс соединения с базой данных
 */
class DB
{

	private static $link = array();

	private static $iftransacte = true;

	private static $db_host = DB_HOST;
	private static $db_user = DB_USER;
	private static $db_pwd = DB_PWD;
	private static $db_name = DB_NAME;
	private static $db_port = DB_PORT;
	private static $db_charset = DB_CHARSET;
	private static $table_prefix = DB_TABLE_PREFIX;

	private function __construct()
	{
		if (!extension_loaded('mysqli')) {
			exit("Db Error: mysqli is not install");
		}
	}

	private static function connect($host = 'slave')
	{
		try {
			if (!in_array($host, array('master', 'slave'))) {
				$host = 'slave';
			}

			if (!empty(self::$link[$host]) && is_object(self::$link[$host])) {
				return;
			}

			self::$link[$host] = @new mysqli(self::$db_host, self::$db_user, self::$db_pwd, self::$db_name, self::$db_port);

			if (mysqli_connect_errno()) {
				throw new Exception("Db Error: database connect failed");
			}

			switch (strtoupper(self::$db_charset)) {
				case 'UTF-8':
					$query_string = "
			                 SET CHARACTER_SET_CLIENT = utf8,
			                 CHARACTER_SET_CONNECTION = utf8,
			                 CHARACTER_SET_DATABASE = utf8,
			                 CHARACTER_SET_RESULTS = utf8,
			                 CHARACTER_SET_SERVER = utf8,
			                 COLLATION_CONNECTION = utf8_general_ci,
			                 COLLATION_DATABASE = utf8_general_ci,
			                 COLLATION_SERVER = utf8_general_ci,
			                 sql_mode=''";
					break;
				case 'WINDOWS-1251':
					$query_string = "
			                SET CHARACTER_SET_CLIENT = cp1251,
			                 CHARACTER_SET_CONNECTION = cp1251,
			                 CHARACTER_SET_DATABASE = cp1251,
			                 CHARACTER_SET_RESULTS = cp1251,
			                 CHARACTER_SET_SERVER = cp1251,
			                 COLLATION_CONNECTION = cp1251_general_ci,
			                 COLLATION_DATABASE = cp1251_general_ci,
			                 COLLATION_SERVER = cp1251_general_ci,
			                 sql_mode=''";
					break;
				default:
					throw new Exception("Db Error: charset is Invalid");
			}

			// Сделать объявление кодирования
			if (!self::$link[$host]->query($query_string)) {
				throw new Exception("Db Error: " . mysqli_error(self::$link[$host]));
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public static function ping($host = 'master')
	{
		if (is_object(self::$link[$host])) {
			self::$link[$host]->close();
			self::$link[$host] = null;
		}
	}

	/**
	 * Выполнить запрос
	 *
	 * @param string $sql
	 *
	 * @return mixed
	 */
	public static function query($sql, $host = 'master')
	{
		self::connect($host);

		$query = self::$link[$host]->query($sql);

		if ($query === false) {
			$error = 'Db Error: ' . mysqli_error(self::$link[$host]);

			Log::record($error . "\r\n" . $sql, Log::ERR);
			Log::record($sql, Log::SQL);

			return false;
		} else {
			Log::record($sql . " [ RunTime:" . addUpTime('queryStartTime', 'queryEndTime', 6) . "s ]", Log::SQL);

			return $query;
		}
	}

	/**
	 * Получить действие запроса
	 *
	 * @param string $sql
	 *
	 * @return bool/null/array
	 */
	public static function getAll($sql, $host = 'slave')
	{
		self::connect($host);
		$result = self::query($sql, $host);

		if ($result === false) {
			return array();
		}
		$array = array();
		while ($tmp = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$array[] = $tmp;
		}

		return !empty($array) ? $array : null;
	}

	/**
	 * Извлечение данных
	 *
	 * @param array  $param    Параметры
	 * @param object $obj_page Категоризация объектов
	 *
	 * @return array
	 */
	public static function select($param, $obj_page = '', $host = 'slave')
	{
		try {
			self::connect($host);

			if (empty($param)) {
				throw new Exception('Db Error: select param is empty!');
			}

			if (empty($param['field'])) {
				$param['field'] = '*';
			}

			if (empty($param['count'])) {
				$param['count'] = 'count(*)';
			}

			if (isset($param['index'])) {
				$param['index'] = 'USE INDEX (' . $param['index'] . ')';
			}

			if (trim($param['where']) != '') {
				if (strtoupper(substr(trim($param['where']), 0, 5)) != 'WHERE') {
					if (strtoupper(substr(trim($param['where']), 0, 3)) == 'AND') {
						$param['where'] = substr(trim($param['where']), 3);
					}
					$param['where'] = 'WHERE ' . $param['where'];
				}
			} else {
				$param['where'] = '';
			}

			$param['where_group'] = '';
			if (!empty($param['group'])) {
				$param['where_group'] .= ' group by ' . $param['group'];
			}

			$param['where_order'] = '';
			if (!empty($param['order'])) {
				$param['where_order'] .= ' order by ' . $param['order'];
			}

			// Определение связанной таблицы
			$tmp_table = explode(',', $param['table']);
			if (!empty($tmp_table) && count($tmp_table) > 1) {

				//Определение, согласованы ли количество таблиц join и условия соединения
				if ((count($tmp_table) - 1) != count($param['join_on'])) {
					throw new Exception('Db Error: join number is wrong!');
				}

				//trim символы пробела
				foreach ($tmp_table as $key => $val) {
					$tmp_table[$key] = trim($val);
				}

				// join on
				for ($i = 1; $i < count($tmp_table); $i++) {
					$tmp_sql = $param['join_type'] . ' `' . self::$table_prefix . $tmp_table[$i] . '` as `' . $tmp_table[$i] . '` ON ' . $param['join_on'][$i - 1] . ' ';
				}

				$sql = 'SELECT ' . $param['field'] . ' FROM `' . self::$table_prefix . $tmp_table[0] . '` as `' . $tmp_table[0] . '` ' . $tmp_sql . ' ' . $param['where'] . $param['where_group'] . $param['where_order'];

				// Вычисление общей информации при наличии разбиения по страницам
				$count_sql = 'SELECT ' . $param['count'] . ' as count FROM `' . self::$table_prefix . $tmp_table[0] . '` as `' . $tmp_table[0] . '` ' . $tmp_sql . ' ' . $param['where'] . $param['where_group'];
			} else {
				$sql       = 'SELECT ' . $param['field'] . ' FROM `' . self::$table_prefix . $param['table'] . '` as `' . $param['table'] . '` ' . $param['index'] . ' ' . $param['where'] . $param['where_group'] . $param['where_order'];
				$count_sql = 'SELECT ' . $param['count'] . ' as count FROM `' . self::$table_prefix . $param['table'] . '` as `' . $param['table'] . '` ' . $param['index'] . ' ' . $param['where'];
			}

			// Если есть класс пагинации
			if ($obj_page instanceof Page) {
				$count_query = self::query($count_sql, $host);
				$count_fetch = mysqli_fetch_array($count_query, MYSQLI_ASSOC);
				$obj_page->setTotalNum($count_fetch['count']);
				$param['limit'] = $obj_page->getLimitStart() . "," . $obj_page->getEachNum();
			}

			if ($param['limit'] != '') {
				$sql .= ' limit ' . $param['limit'];
			}

			$result = self::query($sql, $host);

			if ($result === false) {
				$result = array();
			}

			$array = array();
			while ($tmp = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$array[] = $tmp;
			}

			return $array;
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Операция вставки
	 *
	 * @param string $table_name   Имя таблицы
	 * @param array  $insert_array Вставляемые данные
	 *
	 * @return mixed
	 */
	public static function insert($table_name, $insert_array = array(), $host = 'master')
	{
		self::connect($host);

		if (!is_array($insert_array)) {
			return false;
		}

		$fields = array();
		$value  = array();

		foreach ($insert_array as $key => $val) {
			$fields[] = self::parseKey($key);
			$value[] = self::parseValue($val);
		}

		$sql = 'INSERT INTO `' . self::$table_prefix . $table_name . '` (' . implode(',', $fields) . ') VALUES(' . implode(',', $value) . ')';

		// Если таблица не имеет собственного идентификатора, возвращается true
		$result = self::query($sql, $host);
		$insert_id = self::getLastId($host);

		return $insert_id ? $insert_id : $result;
	}

	/**
	 * Массовая вставка
	 *
	 * @param string $table_name   Имя таблицы
	 * @param array  $insert_array Вставляемые данные
	 *
	 * @return mixed
	 */
	public static function insertAll($table_name, $insert_array = array(), $host = 'master')
	{
		self::connect($host);

		if (!is_array($insert_array[0])) {
			return false;
		}

		$fields = array_keys($insert_array[0]);
		array_walk($fields, array(self, 'parseKey'));
		$values = array();

		foreach ($insert_array as $data) {
			$value = array();

			foreach ($data as $key => $val) {
				$val = self::parseValue($val);
				if (is_scalar($val)) {
					$value[] = $val;
				}
			}

			$values[] = '(' . implode(',', $value) . ')';
		}

		$sql = 'INSERT INTO `' . self::$table_prefix . $table_name . '` (' . implode(',', $fields) . ') VALUES ' . implode(',', $values);
		$result    = self::query($sql, $host);
		$insert_id = self::getLastId($host);

		return $insert_id ? $insert_id : $result;
	}

	/**
	 * Обновление
	 *
	 * @param string $table_name   Имя таблицы
	 * @param array  $update_array Обновляемые данные
	 * @param string $where        Условия
	 *
	 * @return bool
	 */
	public static function update($table_name, $update_array = array(), $where = '', $host = 'master')
	{
		self::connect($host);

		if (!is_array($update_array)) {
			return false;
		}

		$string_value = '';

		foreach ($update_array as $k => $v) {
			if (is_array($v)) {
				switch ($v['sign']) {
					case 'increase':
						$string_value .= " $k = $k + " . $v['value'] . ",";
						break;
					case 'decrease':
						$string_value .= " $k = $k - " . $v['value'] . ",";
						break;
					case 'calc':
						$string_value .= " $k = " . $v['value'] . ",";
						break;
					default:
						$string_value .= " $k = " . self::parseValue($v['value']) . ",";
				}
			} else {
				$string_value .= " $k = " . self::parseValue($v) . ",";
			}
		}

		$string_value = trim(trim($string_value), ',');

		if (trim($where) != '') {
			if (strtoupper(substr(trim($where), 0, 5)) != 'WHERE') {
				if (strtoupper(substr(trim($where), 0, 3)) == 'AND') {
					$where = substr(trim($where), 3);
				}
				$where = ' WHERE ' . $where;
			}
		}

		$sql    = 'UPDATE `' . self::$table_prefix . $table_name . '` AS `' . $table_name . '` SET ' . $string_value . ' ' . $where;
		$result = self::query($sql, $host);

		return $result;
	}

	/**
	 * Удаление
	 *
	 * @param string $table_name Имя таблицы
	 * @param string $where      Условия
	 *
	 * @return bool
	 */
	public static function delete($table_name, $where = '', $host = 'master')
	{
		try {
			self::connect($host);

			if (trim($where) != '') {
				if (strtoupper(substr(trim($where), 0, 5)) != 'WHERE') {
					if (strtoupper(substr(trim($where), 0, 3)) == 'AND') {
						$where = substr(trim($where), 3);
					}
					$where = ' WHERE ' . $where;
				}

				$sql = 'DELETE FROM `' . self::$table_prefix . $table_name . '` ' . $where;

				return self::query($sql, $host);
			} else {
				throw new Exception('Db Error: the condition of delete is empty!');
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Получение идентификатора из предыдущей вставки
	 *
	 * @return int
	 */
	public static function getLastId($host = 'master')
	{
		self::connect($host);

		$id = mysqli_insert_id(self::$link[$host]);

		if (!$id) {
			$result = self::query('SELECT last_insert_id() as id', $host);

			if ($result === false) {
				return false;
			}

			$id = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$id = $id['id'];
		}

		return $id;
	}

	/**
	 * Получить массив последних вставленных идентификаторов
	 *
	 * @param string $host
	 *
	 * @return array
	 */
	public static function getLastIdArray($host = 'master')
	{
		self::connect($host);

		$id  = mysqli_insert_id(self::$link[$host]);
		$ids = [$id];

		if ($id && mysqli_affected_rows(self::$link[$host]) > 1) {
			$ids = range($id, ($id + mysqli_affected_rows(self::$link[$host])) - 1);
		}

		return $ids;
	}

	/**
	 * Получить одну строку информации
	 *
	 * @param array  $param
	 * @param string $fields
	 *
	 * @return array
	 */
	public static function getRow($param, $fields = '*', $host = 'slave')
	{
		self::connect($host);

		$table  = $param['table'];
		$wfield = $param['field'];
		$value  = $param['value'];

		if (is_array($wfield)) {
			$where = array();
			foreach ($wfield as $k => $v) {
				$where[] = $v . "='" . $value[$k] . "'";
			}
			$where = implode(' and ', $where);
		} else {
			$where = $wfield . "='" . $value . "'";
		}

		$sql    = "SELECT " . $fields . " FROM `" . self::$table_prefix . $table . "` WHERE " . $where;
		$result = self::query($sql, $host);

		if ($result === false) {
			return array();
		}

		return mysqli_fetch_array($result, MYSQLI_ASSOC);
	}

	/**
	 * Выполнение операции замены
	 *
	 * @param string $table_name    Имя таблицы
	 * @param array  $replace_array Данные для замены
	 *
	 * @return bool
	 */
	public static function replace($table_name, $replace_array = array(), $host = 'master')
	{
		self::connect($host);

		if (!empty($replace_array)) {
			$string_field = "";
			$string_value = "";

			foreach ($replace_array as $k => $v) {
				$string_field .= " $k ,";
				$string_value .= " '" . $v . "',";
			}

			$sql = 'REPLACE INTO `' . self::$table_prefix . $table_name . '` (' . trim($string_field,', ') . ') VALUES(' . trim($string_value, ', ') . ')';

			return self::query($sql, $host);
		} else {
			return false;
		}
	}

	/**
	 * Возвращает число записей запроса одной таблицы
	 *
	 * @param string $table    Имя таблицы
	 * @param        $condition mixed Критерии запроса, может быть пустым, или может быть массив или строка
	 *
	 * @return int
	 */
	public static function getCount($table, $condition = null, $host = 'slave')
	{
		self::connect($host);

		if (!empty($condition) && is_array($condition)) {
			$where = '';

			foreach ($condition as $key => $val) {
				self::parseKey($key);
				$val = self::parseValue($val);
				$where .= ' AND ' . $key . '=' . $val;
			}

			$where = ' WHERE ' . substr($where, 4);
		} elseif (is_string($condition)) {
			if (strtoupper(substr(trim($condition), 0, 3)) == 'AND') {
				$where = ' WHERE ' . substr(trim($condition), 4);
			} else {
				$where = ' WHERE ' . $condition;
			}
		}
		$sql = 'SELECT COUNT(*) as `count` FROM `' . self::$table_prefix . $table . '` as `' . $table . '` ' . (isset($where) ? $where : '');
		$result = self::query($sql, $host);

		if ($result === false) {
			return 0;
		}

		$result = mysqli_fetch_array($result, MYSQLI_ASSOC);

		return $result['count'];
	}

	/**
	 * Выполнить инструкцию SQL
	 *
	 * @param string $sql
	 *
	 * @return
	 */
	public static function execute($sql, $host = 'master')
	{
		self::connect($host);
		$result = self::query($sql, $host);

		return $result;
	}

	/**
	 * Список всех таблиц
	 *
	 * @return array
	 */
	public static function showTables($host = 'slave')
	{
		self::connect($host);
		$sql    = 'SHOW TABLES';
		$result = self::query($sql, $host);

		if ($result === false) {
			return array();
		}

		$array = array();

		while ($tmp = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$array[] = $tmp;
		}

		return $array;
	}

	/**
	 * Показать сведения о структуре таблицы
	 *
	 * @param string $table
	 *
	 * @return array
	 */
	public static function showColumns($table, $host = 'slave')
	{
		self::connect($host);
		$sql    = 'SHOW COLUMNS FROM `' . self::$table_prefix . $table . '`';
		$result = self::query($sql, $host);

		if ($result === false) {
			return array();
		}

		$array = array();

		while ($tmp = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$array[$tmp['Field']] = array(
				'name'    => $tmp['Field'],
				'type'    => $tmp['Type'],
				'null'    => $tmp['Null'],
				'default' => $tmp['Default'],
				'primary' => (strtolower($tmp['Key']) == 'pri'),
				'autoinc' => (strtolower($tmp['Extra']) == 'auto_increment'),
			);
		}

		return $array;
	}

	/**
	 * Форматирование полей
	 *
	 * @param string $key Имя поля
	 *
	 * @return string
	 */
	public static function parseKey(&$key)
	{
		$key = trim($key);
		if (!preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
			$key = '`' . $key . '`';
		}

		return $key;
	}

	/**
	 * Форматированное значение
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public static function parseValue($value)
	{
		$value = addslashes($value);// Удалить слешь

		return "'" . $value . "'";
	}

	public static function beginTransaction($host = 'master')
	{
		self::connect($host);
		if (self::$iftransacte) {
			self::$link[$host]->autocommit(false);// Отключение автофиксации
		}
		self::$iftransacte = false;
	}

	public static function commit($host = 'master')
	{
		try {
			if (!self::$iftransacte) {
				$result = self::$link[$host]->commit();
				self::$link[$host]->autocommit(true);// Включить автофиксацию
				self::$iftransacte = true;

				if (!$result) {
					throw new Exception("Db Error: " . mysqli_error(self::$link[$host]));
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public static function rollback($host = 'master')
	{
		try {
			if (!self::$iftransacte) {
				$result = self::$link[$host]->rollback();
				self::$link[$host]->autocommit(true);
				self::$iftransacte = true;

				if (!$result) {
					throw new Exception("Db Error: " . mysqli_error(self::$link[$host]));
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}
?>
