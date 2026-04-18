<?php
include_once '../../config.php';
ini_set('display_errors', 1);
header('Content-Type: application/json');

class Response
{
	public $error;
	public $message;

	function __construct($error, $message)
	{
		$this->error = $error;
		$this->message = $message;
	}
}

function echoResponse($response)
{
	echo json_encode($response);
}

$id = @$_POST['user'];
$date = @$_POST['date'];

if (isset($_SESSION['userId'])) {
	if (
		isset($id) && isset($date) && strlen($id) > 0 && strlen($date) > 0
	) {
		$find = R::findone('users', 'id = ?', [$id]);
		if ($find) {
			$find->birthdate = strtotime($date);
			$save = R::store($find);
			if ($save) {
				return echoResponse(new Response(false, "Дата рождения обновлена!"));
			} else {
				return echoResponse(new Response(true, "Отказано в доступе"));
			}
		} else {
			return echoResponse(new Response(true, "Отказано в доступе"));
		}
	} else {
		return echoResponse(new Response(true, "Не вписаны все данные! {$id} | {$date}"));
	}
}
echoResponse(new Response(true, "Недостаточно прав!"));
