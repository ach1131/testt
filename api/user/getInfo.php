<?php
include_once '../../config.php';
ini_set('display_errors', 1);
header('Content-Type: application/json');

class Response
{
	public $error;
	public $message;
	public $period;
	public $lastperiod;

	function __construct($error, $message, $period = "", $lastperiod = "")
	{
		$this->error = $error;
		$this->message = $message;
		$this->period = $period;
		$this->lastperiod = $lastperiod;
	}
}

function echoResponse($response)
{
	echo json_encode($response);
}

$vkid = @$_POST['vkid'];

if (isset($_SESSION['userId'])) {
	$myfind = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
	if ($myfind) {
		if ($myfind->access > 0 && $myfind->accept > 0) {
			if (isset($vkid) && strlen($vkid) > 0) {
				$find = R::findone('users', 'vkid = ?', [$vkid]);
				if ($find) {

					$inactiveslist = R::findall('inactives', 'nick = ? AND server = ? AND status = 1', [$find->nick, $find->server]);
					$allneactivedays = $find->inactive;
					$thislvlneactivedays = $find->nowinactive;
					$thislvlneactivedaysnow = 0;
					$inactive = null;

					foreach ($inactiveslist as $key => $value) {
						$allneactivedays = $allneactivedays + round(($value['end'] - $value['start']) / 86400);
						if ($value['start'] > $find->promoted) {
							$thislvlneactivedays = $thislvlneactivedays + round(($value['end'] - $value['start']) / 86400);
						}
						if ($value['start'] < time() && $value['end'] > time()) {
							$inactive = date('d.m.Y', $value['end']);
						}
					}

					$serverFind = R::findone('serverlist', 'id = ?', [$find->server])->servername;
					if ($find->server > 500 and $find->server < 550) {
						$server = "Родина {$serverFind}";
					} else {
						$server = "Arizona {$serverFind}";
					}

					$weekdays = [
						date('Y-m-d', strtotime('monday this week')),
						date('Y-m-d', strtotime('tuesday this week')),
						date('Y-m-d', strtotime('wednesday this week')),
						date('Y-m-d', strtotime('thursday this week')),
						date('Y-m-d', strtotime('friday this week')),
						date('Y-m-d', strtotime('saturday this week')),
						date('Y-m-d', strtotime('sunday this week'))
					];

					$sf = R::findall('arizona_logs_cache', "nick = ? AND server = ? AND createdAt >= ?", ["{$find->nick}", "{$find->server}", "{$weekdays[0]}"]);
					$online = [];
					$lupd = [];
					$reports = [];
					$allrep = [];
					$gjail = [];
					$gwarn = [];
					$gban = [];
					$gmute = [];
					$gkpz = [];
					foreach ($sf as $k => $v) {
						if ($v['type'] == 'day_stats') {
							$dt = json_decode($v['data'], true);
							$onl = (int) $dt['online'] / 1000;
							$rep = (int) $dt['reportsCount'];
							$online[] = $onl;
							$reports[] = $rep;
							$lupd[] = $v;
						}
						if ($v['type'] == 'punish_count_jail') {
							$gjail[] = (int) $v['data'];
						}
						if ($v['type'] == 'punish_count_warn') {
							$gwarn[] = (int) $v['data'];
						}
						if ($v['type'] == 'punish_count_mute') {
							$gmute[] = (int) $v['data'];
						}
						if ($v['type'] == 'punish_count_ban') {
							$gban[] = (int) $v['data'];
						}
						if ($v['type'] == 'punish_count_kpz') {
							$gkpz[] = (int) $v['data'];
						}
					}

					$monday = date('Y-m-d', strtotime('monday previous week'));
					$sunday = date('Y-m-d', strtotime('sunday previous week'));

					$lf = R::findall('arizona_logs_cache', "nick = ? AND server = ? AND (createdAt >= ? AND createdAt < ?)", ["{$find->nick}", "{$find->server}", "{$monday}", "{$weekdays[0]}"]);
					$lonline = [];
					$llupd = [];
					$lreports = [];
					$lallrep = [];
					$lgjail = [];
					$lgwarn = [];
					$lgban = [];
					$lgmute = [];
					$lgkpz = [];
					foreach ($lf as $k => $v) {
						if ($v['type'] == 'day_stats') {
							$ldt = json_decode($v['data'], true);
							$lonl = (int) $ldt['online'] / 1000;
							$lrep = (int) $ldt['reportsCount'];
							$lonline[] = $lonl;
							$lreports[] = $lrep;
							$llupd[] = $v;
						}
						if ($v['type'] == 'punish_count_jail') {
							$lgjail[] = (int) $v['data'];
						}
						if ($v['type'] == 'punish_count_warn') {
							$lgwarn[] = (int) $v['data'];
						}
						if ($v['type'] == 'punish_count_mute') {
							$lgmute[] = (int) $v['data'];
						}
						if ($v['type'] == 'punish_count_ban') {
							$lgban[] = (int) $v['data'];
						}
						if ($v['type'] == 'punish_count_kpz') {
							$lgkpz[] = (int) $v['data'];
						}
					}

					$arraydest = ["Не указано" => 0, "Неизвестно" => 0, "Лидерка" => 1, "Обзвон" => 2, "Восстановление" => 3, "Перевод" => 4, "Судья" => 5];
					$ulvl = R::findone('lvllist', 'id = ?', [$find->lvl]);
					$userarray = array(
						'myaccess' => $myfind->access,
						'id' => $find->id,
						'vk' => $find->vkid,
						'nick' => $find->nick,
						'lvl' => $find->lvl,
						'server' => $find->server,
						'servername' => $server,
						'scores' => $find->scores,
						'access' => $find->access,
						'post' => $find->post,
						'discord' => $find->discord,
						'inStatus' => $inactive,
						'lvlname' => ($ulvl ? $ulvl['lvl'] : 'Неизвестно') . " [№{$find->lvl}]",
						'forum' => preg_replace('/(http(s|)\:\/\/|)(forum\.|)/', '', $find->forum),
						'forumValue' => $find->forum,
						'tag' => $find->prefix,
						'vig' => $find->reprimands,
						'warn' => $find->warns,
						'jb' => $find->jb,
						'inactive' => $find->inactive,
						'nowinactive' => $find->nowinactive,
						'inactiveAll' => $allneactivedays,
						'inactiveNow' => $thislvlneactivedays,
						'gamerep' => $find->lvl > 4 ? "—" : ($find->gamerep == 0 ? "Нет данных" : $find->gamerep),
						'plusrep' => $find->plusrep,
						'daysUpFirst' => $find->days - round((time() - $find->promoted) / 86400) + $thislvlneactivedays,
						'daysUpAll' => $find->days,
						'daySet' => $find->dateassign,
						'daySetValue' => date('Y-m-d', $find->dateassign),
						'daySetNormal' => date('d.m.Y', $find->dateassign),
						'daySetCount' => getDaysFromDate($find->dateassign),
						'dayUp' => $find->promoted,
						'dayUpValue' => date('Y-m-d', $find->promoted),
						'dayUpNormal' => date('d.m.Y', $find->promoted),
						'dayUpCount' => getDaysFromDate($find->promoted),
						'destNum' => $arraydest[$find->destination],
						'destination' => $find->destination,
						'logsinfo' => [
							'online' => format_time(array_sum($online)),
							'reports' => array_sum($reports),
							'gwarn' => array_sum($gwarn),
							'gjail' => array_sum($gjail),
							'gban' => array_sum($gban),
							'gmute' => array_sum($gmute),
							'gkpz' => array_sum($gkpz),
							'update' => (count($lupd) > 0 ? $lupd[count($lupd) - 1] : 'Не обновлялся'),
						],
						'lastogsinfo' => [
							'online' => format_time(array_sum($lonline)),
							'reports' => array_sum($lreports),
							'gwarn' => array_sum($lgwarn),
							'gjail' => array_sum($lgjail),
							'gban' => array_sum($lgban),
							'gmute' => array_sum($lgmute),
							'gkpz' => array_sum($lgkpz),
							'update' => (count($llupd) > 0 ? $llupd[count($llupd) - 1] : 'Не обновлялся'),
						]
					);

					if ($myfind->access >= 4) {
						$userarray['country'] = $find->country;
						$userarray['realname'] = $find->realname;
						$userarray['city'] = $find->city;
						$userarray['birthValue'] = date('Y-m-d', $find->birthdate);
						$userarray['birth'] = $find->birthdate;
						$userarray['note'] = $find->note;
					}

					$period = "С " . date('d.m.Y', strtotime($weekdays[0])) . " по " . date('d.m.Y', strtotime($weekdays[6]));
					$lastperiod = "С " . date('d.m.Y', strtotime($monday)) . " по " . date('d.m.Y', strtotime($sunday));
					return echoResponse(new Response(false, $userarray, $period, $lastperiod));
				} else {
					return echoResponse(new Response(true, "Пользователь <a href=\"https://vk.ru/id{$vkid}\" не найден!"));
				}
			} else {
				return echoResponse(new Response(true, "Не переданы все данные"));
			}
		} else {
			return echoResponse(new Response(true, "Недостаточно прав"));
		}
	} else {
		return echoResponse(new Response(true, "Мелкий еще)"));
	}
}
echoResponse(new Response(true, "Недостаточно прав!"));

function format_time($t, $f = ':')
{
	return sprintf("%02d%s%02d%s%02d", floor($t / 3600), $f, ($t / 60) % 60, $f, $t % 60);
}

function getDaysFromDate($date)
{
	$datediff = time() - $date;
	return round($datediff / (60 * 60 * 24));
}
