<?php defined('PHPSecurity') or exit('Access Invalid!');
/**
 * Класс пагинации
 */
class Page
{
	/**
	 * Номер параметра Name в параметре URL
	 */
	private $page_name = "curpage";

	/**
	 * Общая информация
	 */
	private $total_num = 1;

	/**
	 * Ссылка на номер страницы
	 */
	private $page_url = "";

	/**
	 * Количество информации на странице
	 */
	private $each_num = 10;

	/**
	 * Номер текущей страницы
	 */
	private $now_page = 1;

	/**
	 * Задать число номеров страниц
	 */
	private $total_page = 1;

	/**
	 * Главная
	 */
	private $pre_home = "";

	/**
	 * Последний
	 */
	private $pre_last = "";

	/**
	 * Предыдущая страница
	 */
	private $pre_page = "";

	/**
	 * Следующая страница
	 */
	private $next_page = "";

	/**
	 * Тег номера страницы
	 */
	private $left_html = "<li>";
	private $right_html = "</li>";

	/**
	 * Тег границы символа
	 */
	private $left_current_html = "<li>";
	private $right_current_html = "</li>";

	/**
	 * Тег эллипса границы символа
	 */
	private $left_ellipsis_html = "<li>";
	private $right_ellipsis_html = "</li>";

	/**
	 * Обертка для ссылок <a>
	 */
	private $left_inside_a_html = "";
	private $right_inside_a_html = "";

	public function __construct()
	{
		$this->pre_home  = 'Первая';
		$this->pre_last  = 'Последняя';
		$this->pre_page  = 'Предидущая';
		$this->next_page = 'Следующая';

		if (isset($_GET[$this->page_name])) {
			$this->setNowPage($_GET[$this->page_name]);
		} elseif (isset($_POST[$this->page_name])) {
			$this->setNowPage($_POST[$this->page_name]);
		}

		$this->setPageUrl();
	}

	public function get($key)
	{
		return $this->$key;
	}

	public function set($key, $value)
	{
		return $this->$key = $value;
	}

	public function setPageName($page_name)
	{
		$this->page_name = $page_name;

		return true;
	}

	/**
	 * Задать номер текущей страницы
	 *
	 * @param int $page Текущая страница
	 *
	 * @return bool Возвращает результат логического типа
	 */
	public function setNowPage($page)
	{
		$this->now_page = intval($page) > 0 ? intval($page) : 1;

		return true;
	}

	/**
	 * Задание количества страниц на странице
	 *
	 * @param int $num Количество
	 *
	 * @return bool Возвращает результат логического типа
	 */
	public function setEachNum($num)
	{
		$this->each_num = intval($num) > 0 ? intval($num) : 10;

		return true;
	}

	/**
	 * Общее количество страниц
	 *
	 * @param $total_num Количесто
	 *
	 * @return bool
	 */
	public function setTotalNum($total_num)
	{
		$this->total_num = $total_num;

		return true;
	}

	/**
	 * @return int
	 */
	public function getNowPage()
	{
		return $this->now_page;
	}

	public function getTotalPage()
	{
		if ($this->total_page == 1) {
			$this->setTotalPage();
		}

		return $this->total_page;
	}

	public function getTotalNum()
	{
		return $this->total_num;
	}

	public function getEachNum()
	{
		return $this->each_num;
	}

	public function getLimitStart()
	{
		if ($this->getNowPage() <= 1) {
			$tmp = 0;
		} else {
			$this->setTotalPage();
			$this->now_page = $this->now_page > $this->total_page ? $this->total_page : $this->now_page;
			$tmp = ($this->getNowPage() - 1) * $this->getEachNum();
		}

		return $tmp;
	}

	public function getLimitEnd()
	{
		$tmp = $this->getNowPage() * $this->getEachNum();
		if ($tmp > $this->getTotalNum()) {
			$tmp = $this->getTotalNum();
		}

		return $tmp;
	}

	public function setTotalPage()
	{
		$this->total_page = ceil($this->getTotalNum() / $this->getEachNum());
	}

	/**
	 * Формирование погинации
	 *
	 * @return string
	 */
	public function show()
	{
		$this->setTotalPage();

		$html_page = '';
		$this->left_current_html = '<li><span class="page__current">';
		$this->right_current_html = '</span></li>';
		$this->left_inside_a_html = '<span>';
		$this->right_inside_a_html = '</span>';

		$html_page .= '<ul>';

		if ($this->getNowPage() <= 1) {
			$html_page .= '<li>' . $this->left_inside_a_html . $this->pre_home . $this->right_inside_a_html . '</li>';
			$html_page .= '<li>' . $this->left_inside_a_html . $this->pre_page . $this->right_inside_a_html . '</li>';
		} else {
			$html_page .= '<li><a class="page__link" href="' . $this->page_url . '1">' . $this->left_inside_a_html . $this->pre_home . $this->right_inside_a_html . '</a></li>';
			$html_page .= '<li><a class="page__link" href="' . $this->page_url . ($this->getNowPage() - 1) . '">' . $this->left_inside_a_html . $this->pre_page . $this->right_inside_a_html . '</a></li>';
		}

		$html_page .= $this->getNowBar();

		if ($this->getNowPage() == $this->getTotalPage() || $this->getTotalPage() == 0) {
			$html_page .= '<li>' . $this->left_inside_a_html . $this->next_page . $this->right_inside_a_html . '</li>';
			$html_page .= '<li>' . $this->left_inside_a_html . $this->pre_last . $this->right_inside_a_html . '</li>';
		} else {
			$html_page .= '<li><a class="page__link" href="' . $this->page_url . ($this->getNowPage() + 1) . '">' . $this->left_inside_a_html . $this->next_page . $this->right_inside_a_html . '</a></li>';
			$html_page .= '<li><a class="page__link" href="' . $this->page_url . $this->getTotalPage() . '">' . $this->left_inside_a_html . $this->pre_last . $this->right_inside_a_html . '</a></li>';
		}

		$html_page .= '</ul>';

		return $html_page;
	}

	private function getNowBar()
	{
		/**
		 * Эффекты отображения
		 * В середине 7, 2 слева и справа, при отсутствии не отображается многоточие
		 */

		// Определяет, является ли текущая страница больше 7
		if ($this->getNowPage() >= 7) {

			// Установить многоточие впереди, и вычислите номер стартовой страницы
			$begin = $this->getNowPage() - 2;
		} else {

			// Менее 7, нет многоточия
			$begin = 1;
		}

		// Вычисление номера конечной страницы
		if ($this->getNowPage() + 5 < $this->getTotalPage()) {

			// Увеличить многоточие
			$end = $this->getNowPage() + 5;
		} else {
			$end = $this->getTotalPage();
		}

		// Упорядочить весь стиль нумерации страниц
		$result = '';

		if ($begin > 1) {
			$result .= $this->setPageHtml(1, 1) . $this->setPageHtml(2, 2);
			$result .= $this->left_ellipsis_html . '<span>...</span>' . $this->right_ellipsis_html;
		}

		// содержимое средней секции
		for ($i = $begin; $i <= $end; $i++) {
			$result .= $this->setPageHtml($i, $i);
		}

		if ($end < $this->getTotalPage()) {
			$result .= $this->left_ellipsis_html . '<span>...</span>' . $this->right_ellipsis_html;
		}

		return $result;
	}

	private function setPageHtml($page_name, $page)
	{
		if ($this->getNowPage() == $page) {
			$result = $this->left_current_html . $page . $this->right_current_html;
		} else {
			$result = $this->left_html . "<a class='page__link' href='" . $this->page_url . $page . "'>" . $this->left_inside_a_html . $page_name . $this->right_inside_a_html . "</a>" . $this->right_html;
		}

		return $result;
	}

	private function setPageUrl()
	{
		$uri = request_uri();

		$_SERVER['REQUEST_URI'] = $uri;

		// Когда нет QUERY_STRING
		if (empty($_SERVER['QUERY_STRING'])) {
			$this->page_url = $_SERVER['REQUEST_URI'] . "?" . $this->page_name . "=";
		} else {
			if (stristr($_SERVER['QUERY_STRING'], $this->page_name . '=')) {

				// Адрес имеет параметры страницы
				$this->page_url = str_replace($this->page_name . '=' . $this->now_page, '', $_SERVER['REQUEST_URI']);
				$last = $this->page_url[strlen($this->page_url) - 1];

				if ($last == '?' || $last == '&') {
					$this->page_url .= $this->page_name . "=";
				} else {
					$this->page_url .= '&' . $this->page_name . "=";
				}

			} else {
				$this->page_url = $_SERVER['REQUEST_URI'] . '&' . $this->page_name . '=';
			}
		}

		return true;
	}
}

?>