<?php
include_once '../../config.php';
header('Content-Type: application/json');

class ListResponse extends Response
{
	public $admins = array();

	function __construct()
	{
		$this->error = false;
		$this->message = "Успех!";
	}

	function addAdmin($admin)
	{
		array_push($this->admins, $admin);
	}
}

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
	echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

$picserver = [
	NULL => [1 => "https://i.imgur.com/RBh3pF1.png", 2 => "https://i.imgur.com/WEBAAp5.png", 3 => "https://i.imgur.com/t1QDcDt.png", 4 => "https://i.imgur.com/l96e21n.png", 5 => "https://i.imgur.com/bOIztFy.png", 7 => "https://i.imgur.com/SudtLo8.png"],
	7 => [1 => "https://i.imgur.com/RBh3pF1.png", 2 => "https://i.imgur.com/WEBAAp5.png", 3 => "https://i.imgur.com/t1QDcDt.png", 4 => "https://i.imgur.com/l96e21n.png", 5 => "https://i.imgur.com/bOIztFy.png"],
	8 => [1 => "https://i.ibb.co/YPtrfYH/HYgHOQW.png", 2 => "https://i.ibb.co/xMZmKDs/bESf9dp.png", 3 => "https://i.ibb.co/qrvLt8K/Fni67Nl.png", 4 => "https://i.ibb.co/RTDz1tH/HtmNKBU.png", 5 => "https://i.ibb.co/FHm5WFN/78TPtdL.png", 6 => "https://i.ibb.co/FBmscL1/A152tc9.png", 7 => "https://i.imgur.com/S4O7BJT.png"],
	10 => [1 => "https://i.imgur.com/RBh3pF1.png", 2 => "https://i.imgur.com/WEBAAp5.png", 3 => "https://i.imgur.com/t1QDcDt.png", 4 => "https://i.imgur.com/l96e21n.png", 5 => "https://i.imgur.com/bOIztFy.png"],
	12 => [1 => "https://i.imgur.com/RBh3pF1.png", 2 => "https://i.imgur.com/WEBAAp5.png", 3 => "https://i.imgur.com/t1QDcDt.png", 4 => "https://i.imgur.com/l96e21n.png", 5 => "https://i.imgur.com/bOIztFy.png"],
	22 => [1 => "https://i.imgur.com/RBh3pF1.png", 2 => "https://i.imgur.com/WEBAAp5.png", 3 => "https://i.imgur.com/t1QDcDt.png", 4 => "https://i.imgur.com/l96e21n.png", 5 => "https://i.imgur.com/bOIztFy.png", 7 => "https://i.imgur.com/SudtLo8.png"],
	103 => [1 => "https://i.imgur.com/RBh3pF1.png", 2 => "https://i.imgur.com/WEBAAp5.png", 3 => "https://i.imgur.com/t1QDcDt.png", 4 => "https://i.imgur.com/l96e21n.png", 5 => "https://i.imgur.com/bOIztFy.png", 7 => "https://i.yapx.ru/TnfO9.png"],
	502 => [1 => "https://i.imgur.com/5qOLUti.png", 2 => "https://i.imgur.com/JizXA12.png", 3 => "https://i.imgur.com/5oNgqX3.png", 4 => "https://i.imgur.com/5doXKPH.png", 5 => "https://i.imgur.com/qJHWlF7.png"]
];

$ga = null;
$zga = null;
$curators = null;
$fourLVL = null;
$threeLVL = null;
$twoLVL = null;
$oneLVL = null;
$admlist = null;

$cone = 0;
$ctwo = 0;
$cthree = 0;
$cfour = 0;
$cfive = 0;
$csix = 0;
$cseven = 0;

if (isset($_SESSION['userId'])) {
	$user = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
	if ($user) {
		if ($user->access <= 0) {
			return echoResponse(new Response(true, "Недостаточно прав!"));
		}

		$response = new ListResponse();
		if ($user->server == 103) {
			$serverlist = R::findall('users', 'server = ? AND accept = 1 ORDER BY `users`.`promoted` ASC', [$user->server]);
		} else {
			$serverlist = R::findall('users', 'server = ? AND accept = 1 AND vkid != 133031610 ORDER BY `users`.`dateassign` ASC', [$user->server]);
		}
		$count = 0;
		foreach ($serverlist as $key => $value) {
			$count += 1;

			if ($value['lvl'] == 7) {
				$cseven += 1;
				$ga .= "[URL='http://vk.ru/id{$value['vkid']}']{$value['nick']} [" . (preg_replace("/(\/|\||\s+|\•|\×|\•|\×)/", '', $value['prefix'])) . "][/URL] - [URL='{$value['forum']}'][COLOR=rgb(8, 138, 8)]{$value['post']}[/COLOR][/URL]\n";
			}
			if ($value['lvl'] == 6) {
				$csix += 1;
				$zga .= "[URL='http://vk.ru/id{$value['vkid']}']{$value['nick']} [" . (preg_replace("/(\/|\||\s+|\•|\×)/", '', $value['prefix'])) . "][/URL] - [URL='{$value['forum']}'][COLOR=rgb(58, 194, 87)]{$value['post']}[/COLOR][/URL]\n";
			}
			if ($value['lvl'] == 5) {
				$cfive += 1;
				$curators .= "[URL='http://vk.ru/id{$value['vkid']}']{$value['nick']} [" . (preg_replace("/(\/|\||\s+|\•|\×)/", '', $value['prefix'])) . "][/URL] - [URL='{$value['forum']}'][COLOR=rgb(169, 69, 202)]{$value['post']}[/COLOR][/URL]\n";
			}
			if ($value['lvl'] == 4) {
				$cfour += 1;
				$fourLVL .= "[URL='http://vk.ru/id{$value['vkid']}']{$value['nick']} [" . (preg_replace("/(\/|\||\s+|\•|\×)/", '', $value['prefix'])) . "][/URL] - [URL='{$value['forum']}'][COLOR=rgb(87, 64, 221)]{$value['post']}[/COLOR][/URL]\n";
			}
			if ($value['lvl'] == 3) {
				$cthree += 1;
				$threeLVL .= "[URL='http://vk.ru/id{$value['vkid']}']{$value['nick']} [" . (preg_replace("/(\/|\||\s+|\•|\×)/", '', $value['prefix'])) . "][/URL] - [URL='{$value['forum']}'][COLOR=rgb(249, 114, 35)]{$value['post']}[/COLOR][/URL]\n";
			}
			if ($value['lvl'] == 2) {
				$ctwo += 1;
				$twoLVL .= "[URL='http://vk.ru/id{$value['vkid']}']{$value['nick']} [" . (preg_replace("/(\/|\||\s+|\•|\×)/", '', $value['prefix'])) . "][/URL] - [URL='{$value['forum']}'][COLOR=rgb(84, 172, 210)]" . ($user->server > 500 ? "{$value['post']}" : "Хелпер") . "[/COLOR][/URL]\n";
			}
			if ($value['lvl'] == 1) {
				$cone += 1;
				$oneLVL .= "[URL='http://vk.ru/id{$value['vkid']}']{$value['nick']} [" . (preg_replace("/(\/|\||\s+|\•|\×)/", '', $value['prefix'])) . "][/URL] - [URL='{$value['forum']}'][COLOR=rgb(135, 207, 211)]Хелпер[/COLOR][/URL]\n";
			}
		}

		if ($cseven == 0) {
			$ga .= null;
		}
		if ($csix == 0) {
			$zga .= "Вакантно - [COLOR=rgb(58, 194, 87)]Заместитель ГА[/COLOR]\n";
		}
		if ($cfour == 0) {
			$fourLVL .= "Отсутствует - [COLOR=rgb(87, 64, 221)]Администратор[/COLOR]\n";
		}
		if ($cthree == 0) {
			$threeLVL .= "Отсутствует - [COLOR=rgb(249, 114, 35)]Мл. Администратор[/COLOR]\n";
		}
		if ($ctwo == 0) {
			$twoLVL .= "Отсутствует - [COLOR=rgb(84, 172, 210)]Хелпер[/COLOR]\n";
		}
		if ($cone == 0) {
			$oneLVL .= "Отсутствует - [COLOR=rgb(135, 207, 211)]Хелпер[/COLOR]\n";
		}

		if (isset($picserver[$user->server][7])) {
			$admlist .= "[CENTER][IMG]{$picserver[$user->server][7]}[/IMG]

";
		} else {
			$admlist .= "[CENTER]";
		}

		if (isset($picserver[$user->server][6])) {
			$admlist .= "[IMG]{$picserver[$user->server][6]}[/IMG]

[SIZE=4]{$ga}{$zga}[/SIZE]


[IMG]" . ($picserver[$user->server][5] ?: $picserver[NULL][5]) . "[/IMG]

[SIZE=4]{$curators}[/SIZE]";
		} else {
			$admlist .= "[IMG]" . ($picserver[$user->server][5] ?: $picserver[NULL][5]) . "[/IMG]

[SIZE=4]{$ga}{$zga}{$curators}[/SIZE]";
		}

		$admlist .= "

[IMG]" . ($picserver[$user->server][4] ?: $picserver[NULL][4]) . "[/IMG]

[SIZE=4]{$fourLVL}[/SIZE]


[IMG]" . ($picserver[$user->server][3] ?: $picserver[NULL][3]) . "[/IMG]

[SIZE=4]{$threeLVL}[/SIZE]


[IMG]" . ($picserver[$user->server][2] ?: $picserver[NULL][2]) . "[/IMG]

[SIZE=4]{$twoLVL}[/SIZE]


[IMG]" . ($picserver[$user->server][1] ?: $picserver[NULL][1]) . "[/IMG]

[SIZE=4]{$oneLVL}[/SIZE]

[COLOR=rgb(163, 143, 132)]Общее количество администраторов - {$count} чел.[/COLOR]
[/CENTER]

[RIGHT][COLOR=rgb(196, 196, 196)]P.S Ники кликабельны -> ВК Админа | Должность кликабельна -> Форум админа
Последнее обновление списка: " . date('d.m.Y в H:i') . "[/COLOR][/RIGHT]
";
		$response->addAdmin(array(
			'adminlist' => $admlist
		));
		if ($count > 0) {
			return echoResponse($response);
		}
	} else {
		return echoResponse(new Response(true, "Вы не зарегистрированы!"));
	}
}
echoResponse(new Response(true, "Недостаточно прав!"));
