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

function addlog($server = null, $user = null, $text = null)
{
	if (isset($server) && isset($text)) {
		$newlog = R::dispense('logs');
		$newlog->server = $server;
		$newlog->time = time();
		$newlog->vkuser = $user;
		$newlog->log = $text;
		R::store($newlog);
	} else {
		return echoResponse(new Response(true, "Не переданы данные для занесения в лог!"));
	}
}

function getAge($then)
{
	$then = date('Ymd', strtotime($then));
	$diff = date('Ymd') - $then;
	return substr($diff, 0, -4);
}

$name = @$_POST['name'];
$server = @$_POST['server'];
$lvl = @$_POST['lvl'];
$tag = @$_POST['tag'];
$forum = @$_POST['forum'];
$discord = @$_POST['discord'];
$ndate = @$_POST['ndate'];
$oldate = @$_POST['oldate'];
$post = @$_POST['post'];
$type = @$_POST['type'];
$country = @$_POST['country'];
$realname = @$_POST['realname'];
$city = @$_POST['city'];
$birth = @$_POST['birth'];

if (isset($_SESSION['userId'])) {
	if (
		isset($name) && isset($server) && strlen($name) > 0 && strlen($server) > 0 &&
		isset($lvl) && isset($tag) && strlen($lvl) > 0 && strlen($tag) > 0 &&
		isset($forum) && strlen($forum) > 0 && isset($post) && strlen($post) > 0 &&
		isset($discord) && isset($ndate) && strlen($discord) > 0 && strlen($ndate) > 0 &&
		isset($oldate) && strlen($oldate) > 0 && isset($type) && strlen($type) > 0 &&
		isset($country) && strlen($country) > 0 && isset($city) && strlen($city) > 0 &&
		isset($realname) && strlen($realname) > 0 && isset($birth) && strlen($birth) > 0
	) {
		switch ((int) $type) {
			case 1:
				$nazn = "Лидерка";
				$lvlvalue = "leader";
				break;
			case 2:
				$nazn = "Обзвон";
				$lvlvalue = "obzvon";
				break;
			case 3:
				$nazn = "Восстановление";
				$lvlvalue = "vosst";
				break;
			case 4:
				$nazn = "Перевод";
				$lvlvalue = "perevod";
				break;
			case 5:
				$nazn = "Судья";
				$lvlvalue = "judge";
				break;
			default:
				$nazn = "Обзвон";
				$lvlvalue = "obzvon";
				break;
		}

		$user = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
		if ($user) {
			if ($user->accept == 0) {
				if (!isMessagesFromGroupAllowed($_SESSION['userId'])) {
					return echoResponse(new Response(true, "Разрешите отправку сообщений от сообщества https://vk.ru/arztools/"));
				}

				$timendate = strtotime($ndate);
				$timeoldate = strtotime($oldate);
				$userforum = str_replace('https://', '', $forum);

				$plus = $lvl + 1;
				$amass = R::findone('serverlist', 'id = ?', [$server]);
				$upmassive = json_decode($amass->$lvlvalue, true)["{$plus}"];

				$userinfo = $VK_BOT->userInfo($_SESSION['userId'], ["photo_200"]);
				if (!isset($userinfo)) {
					return echoResponse(new Response(true, "Не удалось получить ответ от VK, попробуйте позже.."));
				}

				$avatar = $userinfo['photo_200'] ?: null;
				$vname = "{$userinfo['first_name']} {$userinfo['last_name']}";
				
				$name = preg_replace('/\s+/', '_', trim($name));

				$user->nick = $name;
				$user->server = $server;
				$user->lvl = $lvl;
				$user->post = $post;
				$user->vkname = $vname;
				$user->vkpic = $avatar;
				$user->prefix = trim(preg_replace("/(\/|\||\•|\×)/", '', $tag));
				$user->accept = 1;
				$user->plusrep = 0;

				if ($user->access == 0) {
					$user->access = 1;
				}

				if ($upmassive['days']) {
					$user->days = $upmassive['days'];
				} else {
					$user->days = 0;
				}

				$user->dateassign = $timendate;
				$user->promoted = $timeoldate;
				$user->forum = $userforum;
				$user->discord = $discord;
				$user->destination = $nazn;
				$user->country = $country;
				$user->realname = $realname;
				$user->city = $city;
				$user->birthdate = strtotime($birth);

				try {
					$saveuser = R::store($user);
				} catch (Exception $e) {
					return echoResponse(new Response(true, "Некоторые данные указаны некорректно!"));
				}

				addlog($server, $_SESSION['userId'], "<a href=\"user.php?id={$saveuser}\">{$name}</a> зарегистрировался, как администратор {$lvl} уровня");

				if ($saveuser) {
					$kb = [
						"buttons" => [
							[
								[
									"action" => [
										"type" => "open_link",
										"label" => "🌐 Профиль на сайте",
										"link" => "https://" . SITE_DOMAIN . "/user.php?id={$saveuser}"
									]
								]
							],
							[
								[
									"action" => [
										"type" => "text",
										"label" => "➕ В беседу админов",
										"payload" => [
											"command" => "addconference",
											"vkid" => $_SESSION['userId'],
											"type" => "2",
											"server" => $server
										]
									],
									"color" => "primary"
								]
							]
						],
						"inline" => true
					];

					if ($lvl > 0 && $lvl < 3) {
						array_unshift($kb["buttons"][1], [
							"action" => [
								"type" => "text",
								"label" => "➕ В беседу хелперов",
								"payload" => [
									"command" => "addconference",
									"vkid" => $_SESSION['userId'],
									"type" => "1",
									"server" => $server
								]
							],
							"color" => "primary"
						]);
					}

					R::selectDatabase('default');
					foreach (R::findall('conferences', 'server = ? AND reg = 1', [$server]) as $key => $value) {
						$VK_BOT->sendMessage(
							$value['peer'] + 2_000_000_000,
							implode("\n", [
								"☀ Регистрация на сайте ☀",
								"",
								"👤 Игровой ник: [id{$_SESSION['userId']}|{$name}] [BD: {$user->id}]",
								"🏷️ Префикс: " . trim(preg_replace("/(\/|\||\•|\×)/", '', $tag)),
								"🎮 Сервер:  [№{$server}]",
								"📶 Уровень: {$lvl}",
								"💰 Должность: {$post}",
								"🌚 Причина назначения: {$nazn}",
								"",
								"🤠 Реальное имя: {$realname}",
								"🔞 Возраст: " . getAge(date('Y-m-d', strtotime($birth))),
								"🌆 Страна / Город: {$country} / {$city}",
								"",
								"📅 Дата назначения: " . date("d.m.Y", $timendate),
								"📅 Дата повышения: " . date("d.m.Y", $timeoldate),
								"",
								"📱 Discord: {$discord}",
								"🔗 Форумный аккаунт: https://{$userforum}"
							]),
							[
								'keyboard' => json_encode($kb),
								'disable_mentions' => true
							]
						);
					}

					return echoResponse(new Response(false, "Пользователь успешно обновлен!"));
				}
			} else {
				return echoResponse(new Response(true, "Вы уже зарегистрированы!"));
			}
		} else {
			return echoResponse(new Response(true, "У Вас нет доступа к регистрации!"));
		}
	} else {
		return echoResponse(new Response(true, "Не вписаны все данные!"));
	}
}
return echoResponse(new Response(true, "Недостаточно прав!"));
