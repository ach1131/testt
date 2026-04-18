<?php
include_once "../../config.php";
allowedMethods(["GET"]);

if (!isset($_SESSION['userId'])) {
	sendResponse(401, [], "Вы не авторизованы!");
}

$user = R::findOne('users', 'vkid = ? AND access > 0', [$_SESSION['userId']]);
if (!$user) {
	sendResponse(403, [], "Доступ запрещён");
}

function pullUserOnline($userinfo, $onlines)
{
	$result = [
		"last_update" => "Нет данных",
		"online" => 0,
		"reports" => 0,
		"warns" => 0,
		"jails" => 0,
		"bans" => 0,
		"mutes" => 0,
		"sessions" => 0
	];

	foreach ($onlines as $row) {
		if ($row->nick == $userinfo->nick) {
			$data = json_decode($row->data, true);

			$result["last_update"] = date('d.m.Y в H:m', strtotime($row->created_at));
			$result["online"] += $data["online"];
			$result["reports"] += $data["report"];
			$result["warns"] += $data["warn"];
			$result["jails"] += $data["jail"];
			$result["bans"] += $data["ban"];
			$result["mutes"] += $data["mute"] + $data["repmute"];
			$result["sessions"] += $data["sessions"];
		}
	}

	return $result;
}

$week_dates = [
	"previous" => [
		1 => date("Y-m-d", strtotime("monday previous week")),
		7 => date("Y-m-d", strtotime("sunday previous week"))
	],
	"current" => [
		1 => date("Y-m-d", strtotime("monday this week")),
		7 => date("Y-m-d", strtotime("sunday this week"))
	]
];

$all_admins = R::findAll(
	"users",
	"server = ? AND access > 0 AND accept != 0",
	[$user->server]
);

$previous_week_onlines = R::findAll(
	"online",
	"server = ? AND date >= ? AND date <= ?",
	[$user->server, $week_dates["previous"][1], $week_dates["previous"][7]]
);

$current_week_onlines = R::findAll(
	"online",
	"server = ? AND date >= ? AND date <= ?",
	[$user->server, $week_dates["current"][1], $week_dates["current"][7]]
);

$rows = [];
foreach ($all_admins as $admin) {
	if ($admin->lvl <= 4 || $user->access >= 5 || $admin->nick == $user->nick) {
		array_push($rows, [
			"id" => (int) $admin->id,
			"vk" => (int) $admin->vkid,
			"nick" => $admin->nick,
			"post" => $admin->post,
			"lvl" => (int) $admin->lvl,
			"previous_week" => pullUserOnline($admin, $previous_week_onlines),
			"current_week" => pullUserOnline($admin, $current_week_onlines)
		]);
	}
}

sendResponse(200, [
	"answer" => "Онлайн успешно получен",
	"rows" => $rows
]);
