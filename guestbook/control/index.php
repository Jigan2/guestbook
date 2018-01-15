<?php defined('PHPSecurity') or exit('Access Invalid!');

class indexControl extends Control
{
	/**
	 * Главная страница
	 */
	public function indexOp()
	{

		$model = Model('guestbook');

		$order = (isset($_GET['order']) && $_GET['order'] == 1) ? 'asc' : 'desc';

		if (isset($_GET['key'])) {
			switch (trim($_GET['key'])) {
				case '1':
					$order = 'user_name ' . $order;
					break;
				case '2':
					$order = 'user_email ' . $order;
					break;
				case '3':
					$order = 'addtime ' . $order;
					break;
				default:
					$order = 'id desc';
					break;
			}
		} else {
			$order = 'id desc';
		}

		$messages = $model->getList(array(), 25, $order, '*', 25);

		Tpl::output('messages', $messages);
		Tpl::output('count_message', $model->getCount());
		Tpl::output('show_page', $model->showpage());
		Tpl::output('total_page', $model->showpage('gettotalnum'));

		Tpl::showpage('index');
	}

	/**
	 * Добавление сообщения
	 */
	public function addOp()
	{
		$result = chksubmit(true, true, 'num');

		$js = "$('.form__loading').hide();";

		if (!$result) {
			showDialog('Несанкционированный доступ', '', 'error', $js);
		} elseif ($result === -11) {
			showDialog('Несанкционированный доступ', '', 'error', $js);
		} elseif ($result === -12) {
			showDialog('Проверочный код введен не верно', '', 'error', $js);
		}

		$obj_validate = new Validate();
		$obj_validate->validateparam = array(
			array("input" => $_POST['user_name'], "require"=>"true", "validator" => "latin", "message"   => 'Имя обязательно и должно состоять из латинского алфавита'),
			array("input" => $_POST['user_email'], "require"=>"true", "validator" => "email",  "message"   => 'Вы не ввели email или ввели в неверном формате'),
			array("input" => $_POST['homepage'], "validator" => "url",  "message" => 'Вы ввели url в неверном формате'),
			array("input" => $_POST['message'], "require"=>"true",  "message" => 'Вы не ввели сообщение'),
		);

		$error = $obj_validate->validate();

		if (!empty($error)) {
			showDialog($error, '', 'error', $js);
		}

		$insert = array();
		$insert['user_name'] = trim($_POST['user_name']);
		$insert['user_ip'] = getIp();
		$insert['user_browser'] = $_SERVER["HTTP_USER_AGENT"];
		$insert['user_email'] = trim($_POST['user_email']);
		$insert['addtime'] = time();
		$insert['message'] = trim($_POST['message']);
		$insert['homepage'] = trim($_POST['homepage']);

		$model = Model('guestbook');
		$result = $model->save($insert);

		if ($result) {
			showDialog('Ваше сообщение успешно добавлено', 'reload', 'succ', $js);
		} else {
			showDialog('Не удалось добавить сообщение', '', 'error', $js);
		}
	}
}
