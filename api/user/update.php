<?php
ini_set('display_errors', 1);
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

$id = @$_POST['id'];
$name = @$_POST['name']; 
$server = @$_POST['server']; 
$lvl = @$_POST['lvl']; 
$post = @$_POST['post']; 
$tag = @$_POST['tag']; 
$forum = @$_POST['forum']; 
$discord = @$_POST['discord']; 
$type = @$_POST['type']; 
$ndate = @$_POST['ndate']; 
$oldate = @$_POST['oldate']; 

$access = @$_POST['access'];
$reprimands = @$_POST['reprimand'];
$reprimandsreason = @$_POST['reprimandreason'];
$warns = @$_POST['warn'];
$warnsreason = @$_POST['warnreason'];

$country = @$_POST['country']; 
$realname = @$_POST['realname']; 
$city = @$_POST['city']; 
$birth = @$_POST['birth']; 
$plusrep = @$_POST['plusrep'];

$note = @$_POST['note'];

if (isset($_SESSION['userId'])) {
	if (
		isset($id) && strlen($id) > 0 &&
		isset($name) && strlen($name) > 0 &&
		isset($server) && strlen($server) > 0 &&
		isset($lvl) && strlen($lvl) > 0 &&
		isset($post) && strlen($post) > 0 &&
		isset($tag) && strlen($tag) > 0 &&
		isset($forum) && strlen($forum) > 0 &&
		isset($discord) && strlen($discord) > 0 &&
		isset($type) && strlen($type) > 0 &&
		isset($ndate) && strlen($ndate) > 0 &&
		isset($oldate) && strlen($oldate) > 0 &&
		isset($access) && strlen($access) > 0 &&
		isset($reprimands) && strlen($reprimands) > 0 &&
		isset($warns) && strlen($warns) > 0 &&
		isset($country) && strlen($country) > 0 &&
		isset($realname) && strlen($realname) > 0 &&
		isset($city) && strlen($city) > 0 &&
		isset($plusrep) && strlen($plusrep) > 0 &&
		isset($birth) && strlen($birth) > 0
	) {
		$user = R::findone('users', 'id = ?', [$id]);
		$myinfo = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
		if (!$myinfo || !$user) {
			return echoResponse(new Response(true, "Что-то пошло не так.. Сообщите разработчикам"));
		}

		$can_edit_user = false;
		if ($myinfo->access == 8) {
			$can_edit_user = true;
		} elseif ($myinfo->access >= 6 && $user->id == $myinfo->id) {
			$can_edit_user = true;
		} else {
			$can_edit_user = $user->access < $myinfo->access;
		}

		if ($can_edit_user) {
			$nazn = match ((int) $type) {
				1 => "Лидерка",
				2 => "Обзвон",
				3 => "Восстановление",
				4 => "Перевод",
				5 => "Судья",
				default => "Неизвестно"
			};

			if ($user) {
				$logtext = "{$myinfo->post} <a href=\"user.php?id={$myinfo->id}\">{$myinfo->nick}</a> установил(а) администратору <a href=\"user.php?id={$user->id}\">{$user->nick}</a>";

				$name = preg_replace('/\s+/', '_', trim($name));
				if ($name != $user->nick) {
					addlog($user->server, $user->vkid, "{$logtext} никнейм {$name} (был: {$user->nick})");
					$user->nick = $name;
				}
				if ($server != $user->server && $myinfo->access == 8) {
					addlog($user->server, $user->vkid, "{$logtext} сервер №{$server} (был: №{$user->server})");
					$user->server = $server;
				}
				if ($lvl != $user->lvl) {
					addlog($user->server, $user->vkid, "{$logtext} уровень {$lvl} (был: {$user->lvl})");
					$user->lvl = $lvl;
				}
				if ($post != $user->post) {
					addlog($user->server, $user->vkid, "{$logtext} должность \"{$post}\" (было: {$user->post})");
					$user->post = $post;
				}
				if ($tag != $user->prefix) {
					addlog($user->server, $user->vkid, "{$logtext} префикс \"{$tag}\" (был: {$user->prefix})");
					$user->prefix = $tag;
				}
				if ($forum != $user->forum) {
					addlog($user->server, $user->vkid, "{$logtext} форумный аккаунт \"{$forum}\" (был: {$user->forum})");
					$user->forum = $forum;
				}
				if ($discord != $user->discord) {
					addlog($user->server, $user->vkid, "{$logtext} \"{$discord}\" (был: {$user->discord})");
					$user->discord = $discord;
				}
				if ($nazn != $user->destination) {
					addlog($user->server, $user->vkid, "{$logtext} тип назначения \"{$nazn}\" (было: {$user->destination})");
					$user->destination = $nazn;
				}
				if (strval($ndate) != strval(date("Y-m-d", $user->dateassign))) {
					addlog($user->server, $user->vkid, "{$logtext} дату назначения " . date("d.m.Y", strtotime($ndate)) . " (было: " . date("d.m.Y", $user->dateassign) . ")");
					$user->dateassign = strtotime($ndate);
				}
				if ($oldate != strval(date("Y-m-d", $user->promoted))) {
					addlog($user->server, $user->vkid, "{$logtext} дату повышения " . date("d.m.Y", strtotime($oldate)) . " (было: " . date("d.m.Y", $user->dateassign) . ")");
					$user->promoted = strtotime($oldate);
				}

				$can_edit_access = false;
				if ($myinfo->access == 8) {
					$can_edit_access = true;
				} else {
					if ($access >= 6) {
						$can_edit_access = false;
					} elseif ($user->access < $myinfo->access && $access < $myinfo->access) {
						$can_edit_access = true;
					}
				}

				if ($can_edit_access && $access != $user->access) {
					addlog($user->server, $user->vkid, "{$logtext} уровень доступа №{$access} (было: №{$user->access})");
					$user->access = $access;
				}

				if ($reprimandsreason == null) {
					$reprimandsreason = "Не указано";
				}
				if ($warnsreason == null) {
					$warnsreason = "Не указано";
				}

				$beforereprimands = $user->reprimands;
				$beforerewarns = $user->warns;
				if ($reprimands != $beforereprimands) {
					if (preg_match("/^\+(\d+)\b/", $reprimands, $matched)) {

						$nowreprimands = $user->reprimands + $matched[1];
						if ($nowreprimands > 3) {
							$user->reprimands = 3;
							$repr = 3;
						} else {
							$user->reprimands = $nowreprimands;
							$repr = $nowreprimands;
						}
						addlog($user->server, $user->vkid, "{$logtext} +{$matched[1]} выговоров по причине: {$reprimandsreason} (было: {$beforereprimands}/3 {$beforerewarns}/2) (стало: {$repr}/3 {$beforerewarns}/2)");
					} elseif (preg_match("/^-(\d+)\b/", $reprimands, $matched)) {
						$nowreprimands = $user->reprimands - $matched[1];
						if ($nowreprimands < 0) {
							$user->reprimands = 0;
							$repr = 0;
						} else {
							$user->reprimands = $nowreprimands;
							$repr = $nowreprimands;
						}
						addlog($user->server, $user->vkid, "{$logtext} -{$matched[1]} выговоров по причине: {$reprimandsreason} (было: {$beforereprimands}/3 {$beforerewarns}/2) (стало: {$repr}/3 {$beforerewarns}/2)");
					} elseif (preg_match("/^(\d+)\b/", $reprimands, $matched)) {
						if ($matched[1] >= 0 && $matched[1] <= 3) {
							$user->reprimands = $matched[1];
							addlog($user->server, $user->vkid, "{$logtext} {$matched[1]} выговоров по причине: {$reprimandsreason} (было: {$beforereprimands}/3 {$beforerewarns}/2) (стало: {$user->reprimands}/3 {$beforerewarns}/2)");
						}
					}
				}

				if ($warns != $beforerewarns) {
					if (preg_match("/^\+(\d+)\b/", $warns, $matched)) {

						$nowwarns = $user->warns + $matched[1];
						if ($nowwarns > 2) {
							$user->warns = 2;
							$wrns = 2;
						} else {
							$user->warns = $nowwarns;
							$wrns = $nowwarns;
						}
						addlog($user->server, $user->vkid, "{$logtext} +{$matched[1]} предупреждений по причине: {$warnsreason} (было: {$beforereprimands}/3 {$beforerewarns}/2) (стало: {$beforereprimands}/3 {$wrns}/2)");
					} elseif (preg_match("/^-(\d+)\b/", $warns, $matched)) {
						$nowwarns = $user->warns - $matched[1];
						if ($nowwarns < 0) {
							$user->warns = 0;
							$wrns = 0;
						} else {
							$user->warns = $nowwarns;
							$wrns = $nowwarns;
						}
						addlog($user->server, $user->vkid, "{$logtext} -{$matched[1]} предупреждений по причине: {$warnsreason} (было: {$beforereprimands}/3 {$beforerewarns}/2) (стало: {$beforereprimands}/3 {$wrns}/2)");
					} elseif (preg_match("/^(\d+)\b/", $warns, $matched)) {
						if ($matched[1] >= 0 && $matched[1] <= 2) {
							$user->warns = $matched[1];
							addlog($user->server, $user->vkid, "{$logtext} {$matched[1]} предупреждений по причине: {$warnsreason} (было: {$beforereprimands}/3 {$beforerewarns}/2) (стало: {$beforereprimands}/3 {$user->warns}/2)");
						}
					}
				}

				if ($country != $user->country) {
					addlog($user->server, $user->vkid, "{$logtext} страну {$country} (было: {$user->country})");
					$user->country = $country;
				}

				if ($realname != $user->realname) {
					addlog($user->server, $user->vkid, "{$logtext} реальное имя {$realname} (было: {$user->realname})");
					$user->realname = $realname;
				}

				if ($city != $user->city) {
					addlog($user->server, $user->vkid, "{$logtext} город {$city} (было: {$user->city})");
					$user->city = $city;
				}

				if ($birth != date('Y-m-d', $user->birthdate)) {
					addlog($user->server, $user->vkid, "{$logtext} дату рождения на {$birth} (было: " . date('Y-m-d', $user->birthdate) . ")");
					$user->birthdate = strtotime($birth);
				}

				if (is_string($note) && $note != $user->note) {
					$action = $note ? ($user->note ? "обновил(а)" : "добавил(а)") : "очистил(а)";
					addlog($user->server, $user->vkid, "{$myinfo->post} <a href=\"user.php?id={$myinfo->id}\">{$myinfo->nick}</a> {$action} заметку для <a href=\"user.php?id={$user->id}\">{$user->nick}</a>");
					$user->note = $note;
				}

				if ($plusrep != $user->plusrep) {
					$old_plusrep = $user->plusrep;
					if (preg_match("/^\+(\d+)\b/", $plusrep, $matched)) {
						$user->plusrep += (int) $matched[1];
						addlog(
							$user->server,
							$user->vkid,
							"{$logtext} +{$matched[1]} репутации (Было: {$old_plusrep}) (Стало: {$user->plusrep})"
						);
					} elseif (preg_match("/^-(\d+)\b/", $plusrep, $matched)) {
						$user->plusrep -= (int) $matched[1];
						addlog(
							$user->server,
							$user->vkid,
							"{$logtext} -{$matched[1]} репутации (Было: {$old_plusrep}) (Стало: {$user->plusrep})"
						);
					} elseif (preg_match("/^(\d+)\b/", $plusrep, $matched)) {
						$user->plusrep = (int) $matched[1];
						addlog(
							$user->server,
							$user->vkid,
							"{$logtext} {$user->plusrep} репутации (Было: {$old_plusrep})"
						);
					}
				}

				$userinfo = $VK_BOT->userInfo($user->vkid, ["photo_200"]);
				if (!isset($userinfo)) {
					return echoResponse(new Response(true, "Не удалось получить ответ от VK, попробуйте позже.."));
				}

				$user->vkname = "{$userinfo['first_name']} {$userinfo['last_name']}";
				$user->vkpic = $userinfo['photo_200'] ?: null;

				$saveall = R::store($user);
				if ($saveall) {
					return echoResponse(new Response(false, "Пользователь успешно обновлен!"));
				}
			} else {
				return echoResponse(new Response(true, "Указанный пользователь не зарегистрирован! {$id}"));
			}
		} else {
			return echoResponse(new Response(true, "Ваш уровень доступа недостаточен!"));
		}
	} else {
		return echoResponse(new Response(true, "Не вписаны все данные!"));
	}
}
echoResponse(new Response(true, "Недостаточно прав!"));

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

function numbercase($num, $titles, $show = true)
{
	$cases = array(2, 0, 1, 1, 1, 2);
	if (!$show) {
		return $titles[($num % 100 > 4 && $num % 100 < 20) ? 2 : $cases[min($num % 10, 5)]];
	} else {
		return $num . " " . $titles[($num % 100 > 4 && $num % 100 < 20) ? 2 : $cases[min($num % 10, 5)]];
	}
}
