<?php
ini_set('display_errors', 1);
include_once '../../config.php';
header('Content-Type: application/json');

class Response
{
	public $error;
	public $message;

	function __construct($error, $message, $type = 0)
	{
		$this->error = $error;
		$this->message = $message;
		$this->type = $type;
	}
}

function echoResponse($response)
{
	echo json_encode($response);
}

$id = @$_POST['id'];
$type = @$_POST['type'];
$value = @$_POST['value'];

if (isset($_SESSION['userId'])) {
	if (isset($id) && isset($type) && isset($value) && strlen($id) > 0 && strlen($type) > 0 && strlen($value) > 0) {
		$myinfo = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
		if ($myinfo->access >= 4) {
			$userfind = R::findone('users', 'id = ?', [$id]);
			if ($userfind) {
				$logtext = "{$myinfo->post} <a href=\"user.php?id={$myinfo->id}\">{$myinfo->nick}</a> установил администратору <a href=\"user.php?id={$userfind->id}\">{$userfind->nick}</a>";

				if ($type == 1) {
					$beforeinactive = $userfind->inactive;
					$userfind->inactive = $value;
					if ($beforeinactive == $value) {
						return echoResponse(new Response(true, "Значение не должно совпадать с предыдущим.", $type));
					}
					$saveuser = R::store($userfind);
					if ($saveuser) {
						addlog($userfind->server, $userfind->vkid, "{$logtext} дополнительные дни общего неактива на {$value} (было: {$beforeinactive})");
						return echoResponse(new Response(false, "Общие дни неактива успешно изменены.", $type));
					} else {
						return echoResponse(new Response(true, "Ошибка при изменении. Попробуйте позже", $type));
					}
				} elseif ($type == 2) {
					$beforenow = $userfind->nowinactive;
					$before = $userfind->inactive - $beforenow;
					$userfind->inactive = $before + $value;
					$userfind->nowinactive = $value;
					if ($beforenow == $value) {
						return echoResponse(new Response(true, "Значение не должно совпадать с предыдущим.", $type));
					}
					$saveuser = R::store($userfind);
					if ($saveuser) {
						addlog($userfind->server, $userfind->vkid, "{$logtext} дополнительные дни неактива на лвл на {$value} (было: {$beforenow})");
						return echoResponse(new Response(false, "Дни неактива на данном LVL успешно изменены.", $type));
					} else {
						return echoResponse(new Response(true, "Ошибка при изменении. Попробуйте позже", $type));
					}
				} elseif ($type == 3) {
					$beforedays = $userfind->days;
					$userfind->days = $value;
					if ($beforedays == $value) {
						return echoResponse(new Response(true, "Значение не должно совпадать с предыдущим.", $type));
					}
					$saveuser = R::store($userfind);
					if ($saveuser) {
						addlog($userfind->server, $userfind->vkid, "{$logtext} дни до повышения на {$value} (было: {$beforedays})");
						return echoResponse(new Response(false, "Дни повышения успешно изменены.", $type));
					} else {
						return echoResponse(new Response(true, "Ошибка при изменении. Попробуйте позже", $type));
					}
				} elseif ($type == 4) {
					$beforescore = $userfind->scores;
					$userfind->scores = $value;
					if ($beforescore == $value) {
						return echoResponse(new Response(true, "Значение не должно совпадать с предыдущим.", $type));
					}
					$saveuser = R::store($userfind);
					if ($saveuser) {
						addlog($userfind->server, $userfind->vkid, "{$logtext} значение Очков Рейтинга на {$value} (было: {$beforescore})");
						return echoResponse(new Response(false, "Очки рейтинга успешно изменены.", $type));
					} else {
						return echoResponse(new Response(true, "Ошибка при изменении. Попробуйте позже", $type));
					}
				} else {
					return echoResponse(new Response(true, "Указанный тип №{$type} не найден!"));
				}
			} else {
				return echoResponse(new Response(true, "Пользователь с ID {$id} не найден!"));
			}
		} else {
			return echoResponse(new Response(true, "Недостаточно прав!"));
		}
	} else {
		return echoResponse(new Response(true, "Не переданы все данные!"));
	}
}
echoResponse(new Response(true, "Отказано в доступе!"));

function addlog($server = null, $vk = null, $text = null)
{
	if (isset($server) && isset($text)) {
		$newlog = R::dispense('logs');
		$newlog->server = $server;
		$newlog->time = time();
		$newlog->vkuser = $vk;
		$newlog->log = $text;
		$save = R::store($newlog);
	}
}
