<?php defined('PHPSecurity') or exit('Access Invalid!');

/**
 * Модель гостевой книги
 */
class guestbookModel extends Model
{

	public function __construct()
	{
		parent::__construct('guestbook');
	}

	/**
	 * Список
	 *
	 * @param array $condition
	 *
	 */
	public function getList($condition = array(), $page = '', $order = 'id desc', $field = '*', $limit = '')
	{
		$result = $this->where($condition)->field($field)->order($order)->page($page)->limit($limit)->select();

		return $result;
	}

	/*
	 * Добавление
	 * @param array $param
	 * @return bool
	 */
	public function save($param)
	{
		return $this->insert($param);
	}

	/*
	 * Изменение
	 * @param array $update
	 * @param array $condition
	 * @return bool
	 */
	public function modify($update, $condition)
	{
		return $this->where($condition)->update($update);
	}

	/*
	 * Удаление
	 * @param array $condition
	 * @return bool
	 */
	public function drop($condition)
	{
		return $this->where($condition)->delete();
	}

	/**
	 * Количество
	 * @param unknown $condition
	 */
	public function getCount($condition = array()) {
		return $this->where($condition)->count();
	}

}
