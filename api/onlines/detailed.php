<?php
include_once "../../config.php";
allowedMethods([ "GET" ]);

if (!isset($_SESSION["userId"])) {
    sendResponse(401, [], "Вы не авторизованы!");
}

$user = R::findOne("users", "vkid = ? AND access > 0", [ $_SESSION["userId"] ]);
if (!$user) {
    sendResponse(403, [], "Доступ запрещён");
}

$nick = filter_input(INPUT_GET, "nick", FILTER_VALIDATE_REGEXP, [
    "options" => [ "regexp" => "/^[a-zA-Z0-9_]+$/" ]
]);
$week = filter_input(INPUT_GET, "week", FILTER_CALLBACK, [
    "options" => function ($week) {
        return in_array($week, ["current", "previous"], true) ? $week : null;
    }
]);

if (!isset($nick, $week)) {
    sendResponse(400, [], "Некорректный запрос");
}

$find_user = R::findOne(
    "users", "server = ? AND nick = ? AND access > 0",
    [ $user->server, $nick ]
);

if (!$find_user) {
    sendResponse(404, [], "Такой пользователь не найден");
} elseif ($find_user->lvl >= 5 && $user->access <= 5 && $find_user->id !== $user->id) {
    sendResponse(403, [], "У вас нет доступа к просмотру этой информации");
}

$week_dates = [
	"current" => [
        1 => date("Y-m-d", strtotime("monday this week")),
        7 => date("Y-m-d", strtotime("sunday this week"))
    ],
    "previous" => [
        1 => date("Y-m-d", strtotime("monday last week")),
        7 => date("Y-m-d", strtotime("sunday last week"))
    ]
];

$week_info = R::findAll(
	"online", "server = ? AND nick = ? AND date >= ? AND date <= ?", 
	[ $user->server, $nick, $week_dates[$week][1], $week_dates[$week][7] ]
);

function getWeekday($date) {
    $days = ['ВС', 'ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ'];
    $dayIndex = date('w', strtotime($date));
    return $days[$dayIndex] ?? "??";
}

$info = [];
foreach ($week_info as $day) {
    $data = json_decode($day->data, true);
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        continue;
    }
    
    array_push($info, [
        "date" => date('d.m.Y', strtotime($day->date)),
        "weekday" => getWeekday($day->date),
        "online" => (int) $data["online"],
        "reports" => (int) $data["report"],
        "warns" => (int) $data["warn"],
        "jails" => (int) $data["jail"],
        "bans" => (int) $data["ban"],
        "mutes" => (int) ($data["mute"] + $data["repmute"]),
        "sessions" => (int) $data["sessions"]
    ]);
}

sendResponse(200, [ 
	"answer" => "Онлайн за день успешно получен", 
    "nick" => $nick,
	"info" => $info
]);