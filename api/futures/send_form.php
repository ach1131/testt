<?php
include_once '../../config.php';
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

$birth = @$_POST['birth'];
$nick = @$_POST['nick'];
$newnick = @$_POST['newnick'];
$name = @$_POST['name'];
$reason = @$_POST['reason'];
$forum = @$_POST['forum'];
$type = @$_POST['type'];
$nicks = @$_POST['nicks'];
$oldnick = @$_POST['oldnick'];
$discord = @$_POST['discord'];
$lvl = @$_POST['lvl'];
$start = @$_POST['start'];
$end = @$_POST['end'];
$city = @$_POST['city'];
$mails = @$_POST['mails'];

if (isset($nicks) && strlen($nicks) > 0) {
	$nicks = preg_split('/[\s,]+/', $nicks, -1, PREG_SPLIT_NO_EMPTY);
	$nicks = implode(", ", $nicks);
} else {
	$nicks = "Нет";
}

if (isset($mails) && strlen($mails) > 0) {
	$mails = preg_split('/[\s,]+/', $mails, -1, PREG_SPLIT_NO_EMPTY);
	$mails = implode(", ", $mails);
} else {
	$mails = "Нет";
}

if (isset($_SESSION['vk']) and $_SESSION['type'] == 1) {
	if (isset($type) && strlen($type) > 0) {
		if ($type == 0) {
			if (
				isset($nick) && strlen($nick) > 0 &&
				isset($name) && strlen($name) > 0 &&
				isset($birth) && strlen($birth) > 0 &&
				isset($reason) && strlen($reason) > 0 &&
				isset($discord) && strlen($discord) > 0 &&
				isset($forum) && strlen($forum) > 0
			) {
				$findform = R::findOne('admform', 'vkid = ? AND status = 0 AND time > ? ORDER BY id DESC', [$_SESSION['vk'], strtotime('-24 hours')]);
				if ($findform) {
					if ($findform->status == 0) {
						$ip = explode(',', getUserIP());
						$findform->status = 1;
						$findform->isnew = 1;
						$findform->ip = $ip[0];
						$findform->nick = trim($nick);
						$findform->newnick = isset($newnick) && strlen($newnick) > 0 ? $newnick : null;
						$findform->name = trim($name);
						$findform->birth = $birth;
						$findform->reason = trim($reason);
						$findform->forum = trim($forum);
						$findform->accounts = $nicks;
						$findform->mails = $mails;
						$findform->discord = trim($discord);

						$save = R::store($findform);
						if ($save) {
							$kb = [
								"buttons" => [
									[
										[
											"action" => [
												"type" => "text",
												"label" => "Добавить в беседу будущих",
												"payload" => [
													"command" => "newformadm",
													"type" => 1,
													"vkid" => $_SESSION['vk'],
													"bdid" => $save
												]
											],
											"color" => "positive"
										]
									],
									[
										[
											"action" => [
												"type" => "text",
												"label" => "Удалить",
												"payload" => [
													"command" => "newformadm",
													"type" => 2,
													"vkid" => $_SESSION['vk'],
													"bdid" => $save
												]
											],
											"color" => "negative"
										]
									]
								],
								"inline" => true
							];

							$confa = R::findOne('conferences', 'server = ? AND admform = 1 AND peer != 0 ORDER BY id DESC', [$findform->server]);
							if (!$confa) {
								return echoResponse(new Response(true, "Нет бесед для отправки форм, сообщите руководству сервера!"));
							} else {
								if ($findform->server >= 500 && $findform->server != 666) {
									$form_text = implode("\n", [
										"[РАНЕЕ НЕ БЫЛ АДМИНИСТРАТОРОМ]",
										"",
										"-------------------------",
										"Ник: {$nick} " . (isset($newnick) && strlen($newnick) > 0 && $newnick != $nick ? "(Новый ник: {$newnick})" : ""),
										"Имя: {$name}",
										"Возраст: {$birth}",
										"Занимал ли пост лидера фракции: {$reason}",
										"VK: https://vk.ru/id{$_SESSION['vk']}",
										"Ссылка на активный форумный аккаунт: {$forum}",
										"Текущий IP адрес кандидата: {$ip[0]}",
										"Discord: {$discord}",
										"Почта(ы): {$mails}",
										"Играл ли кандидат на других серверах проекта (Если да, то каких): {$nicks}"
									]);
								} else {
									$form_text = implode("\n", [
										"[РАНЕЕ НЕ БЫЛ АДМИНИСТРАТОРОМ]",
										"",
										"-------------------------",
										"Ник: {$nick} " . (isset($newnick) && strlen($newnick) > 0 && $newnick != $nick ? "(Новый ник: {$newnick})" : ""),
										"Имя: {$name}",
										"Возраст: {$birth}",
										"Занимал ли пост лидера фракции: {$reason}",
										"VK: https://vk.ru/id{$_SESSION['vk']}",
										"Почта(ы): {$mails}",
										"Ссылка на активный форумный аккаунт: {$forum}",
										"Текущий IP адрес кандидата: {$ip[0]}",
										"Играл ли кандидат на других серверах проекта (Если да, то каких): {$nicks}"
									]);
								}

								$response = $VK_BOT->sendMessage(
									$confa['peer'] + 2_000_000_000,
									$form_text,
									["keyboard" => json_encode($kb)]
								);

								$response = json_decode($response, true);
								if (!isset($response['error'])) {
									return echoResponse(new Response(false, "Успех!"));
								}
								return echoResponse(new Response(true, "Ошибка ВКонтакте №{$response['error']['error_code']}"));
							}
						}
						return echoResponse(new Response(true, "Не удалось сохранить форму в Базе Данных!"));
					}
					return echoResponse(new Response(true, "Отказано в доступе №1!"));
				}
				return echoResponse(new Response(true, "Вы не найдены в Базе Данных!"));
			}
			return echoResponse(new Response(true, "Не вписаны все данные!"));
		} elseif ($type == 1) {
			if (
				isset($nick) && strlen($nick) > 0 &&
				isset($oldnick) && strlen($oldnick) > 0 &&
				isset($name) && strlen($name) > 0 &&
				isset($discord) && strlen($discord) > 0 &&
				isset($reason) && strlen($reason) > 0 &&
				isset($lvl) && strlen($lvl) > 0 &&
				isset($start) && strlen($start) > 0 &&
				isset($end) && strlen($end) > 0 &&
				isset($birth) && strlen($birth) > 0 &&
				isset($city) && strlen($city) > 0 &&
				isset($forum) && strlen($forum) > 0
			) {
				$findform = R::findOne('admform', 'vkid = ? AND status = 0 AND time > ? ORDER BY id DESC', [$_SESSION['vk'], strtotime('-24 hours')]);
				if ($findform) {
					if ($findform->status == 0) {
						$ip = explode(',', getUserIP());
						$findform->status = 1;
						$findform->isnew = 0;
						$findform->nick = trim($nick);
						$findform->newnick = (isset($oldnick) && strlen($oldnick) > 0 ? trim($oldnick) : null);
						$findform->name = trim($name);
						$findform->ip = $ip[0];
						$findform->discord = trim($discord);
						$findform->start = strtotime($start);
						$findform->end = strtotime($end);
						$findform->lvl = $lvl;
						$findform->reason = trim($reason);
						$findform->forum = trim($forum);
						$findform->accounts = $nicks;
						$findform->mails = $mails;
						$findform->birth = $birth;
						$findform->city = trim($city);

						$save = R::store($findform);
						if ($save) {
							$kb = [
								"buttons" => [
									[
										[
											"action" => [
												"type" => "text",
												"label" => "Добавить в беседу будущих",
												"payload" => [
													"command" => "newformadm",
													"type" => 1,
													"vkid" => $_SESSION['vk'],
													"bdid" => $save
												]
											],
											"color" => "positive"
										]
									],
									[
										[
											"action" => [
												"type" => "text",
												"label" => "Удалить",
												"payload" => [
													"command" => "newformadm",
													"type" => 2,
													"vkid" => $_SESSION['vk'],
													"bdid" => $save
												]
											],
											"color" => "negative"
										]
									]
								],
								"inline" => true
							];

							$confa = R::findOne('conferences', 'server = ? AND admform = 1 AND peer != 0 ORDER BY id DESC', [$findform->server]);
							if (!$confa) {
								return echoResponse(new Response(true, "Нет бесед для отправки форм, сообщите руководству сервера!"));
							} else {
								if ($findform->server >= 500 && $findform->server != 666) {
									$form_text = implode("\n", [
										"[ВОССТАНОВЛЕНИИЕ]",
										"",
										"-------------------------",
										"Ник: {$nick} " . (isset($oldnick) && strlen($oldnick) > 0 && $oldnick != $nick ? "(был {$oldnick})" : "(не менялся)"),
										"Имя: {$name}",
										"Возраст: {$birth}",
										"Город проживания: {$city}",
										"VK: https://vk.ru/id{$_SESSION['vk']}",
										"Discord: {$discord}",
										"Почта(ы): {$mails}",
										"Какой уровень был у кандидата: {$lvl}",
										"На какой уровень будет восстановлен: [УКАЗАТЬ]",
										"Находится ли в чёрном списке на других серверах Rodina RP: [Да/Нет]",
										"Ссылка на форумный(ные) аккаунт(ы): {$forum}",
										"Почему он должен быть восстановлен: [УКАЗАТЬ]",
										"Были ли выговоры при администрировании: [Да/Нет]",
										"Причина снятия: {$reason}",
										"В какой период занимал пост администратора: с " . date("d.m.Y", strtotime($start)) . " по " . date("d.m.Y", strtotime($end)),
										"Ссылка на архив администратора: [УКАЗАТЬ]"
									]);
								} else {
									$form_text = implode("\n", [
										"[ВОССТАНОВЛЕНИИЕ]",
										"",
										"-------------------------",
										"Ник: {$nick} " . (isset($oldnick) && strlen($oldnick) > 0 && $oldnick != $nick ? "(был {$oldnick})" : "(не менялся)"),
										"Имя: {$name}",
										"VK: https://vk.ru/id{$_SESSION['vk']}",
										"Discord: {$discord}",
										"Почта(ы): {$mails}",
										"Reg ip: [УКАЗАТЬ]",
										"Last ip: [УКАЗАТЬ]",
										"Какой уровень администрирования был у кандидата: {$lvl}",
										"На какой уровень будет восстановлен: [УКАЗАТЬ]",
										"Ссылка на форумный(ные) аккаунт(ы): {$forum}",
										"Почему он должен быть восстановлен: [УКАЗАТЬ]",
										"За что был снят: {$reason}",
										"В какой период занимал пост администратора: с " . date("d.m.Y", strtotime($start)) . " по " . date("d.m.Y", strtotime($end)),
										"Аккаунты на других серверах если имеются (так же указать был ли администратором на др. сервере): {$nicks}",
										"Ссылка на Архив Администраторов (если архивов несколько с разных серверов, указать): [УКАЗАТЬ]",
										"Дополнительная информация: [УКАЗАТЬ]"
									]);
								}

								$response = $VK_BOT->sendMessage(
									$confa['peer'] + 2_000_000_000,
									$form_text,
									["keyboard" => json_encode($kb)]
								);

								$response = json_decode($response, true);
								if (!isset($response['error'])) {
									return echoResponse(new Response(false, "Успех!"));
								}
								return echoResponse(new Response(true, "Ошибка ВКонтакте №{$response['error']['error_code']}"));
							}
						}
						return echoResponse(new Response(true, "Не удалось сохранить форму в Базе Данных!"));
					}
					return echoResponse(new Response(true, "Отказано в доступе №1!"));
				}
				return echoResponse(new Response(true, "Вы не найдены в Базе Данных!"));
			}
			return echoResponse(new Response(true, "Не вписаны все данные!"));
		}
		return echoResponse(new Response(true, "Не найден тип №{$type}!"));
	}
	return echoResponse(new Response(true, "Не указан тип анкеты!"));
}
return echoResponse(new Response(true, "Недостаточно прав!"));

function getUserIP()
{
	$ipaddress = '';
	if (isset($_SERVER['HTTP_CLIENT_IP']))
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if (isset($_SERVER['HTTP_X_FORWARDED']))
		$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	else if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
		$ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
	else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
		$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	else if (isset($_SERVER['HTTP_FORWARDED']))
		$ipaddress = $_SERVER['HTTP_FORWARDED'];
	else if (isset($_SERVER['REMOTE_ADDR']))
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	else
		$ipaddress = 'UNKNOWN';
	return $ipaddress;
}
