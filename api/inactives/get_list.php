<?php
include_once "../../config.php";
allowedMethods(["GET"]);

if (!isset($_SESSION["userId"])) {
    sendResponse(401, [], "Вы не авторизованы");
}

$user = R::findOne("users", "vkid = ? AND access > 0", [$_SESSION["userId"]]);
if (!$user) {
    sendResponse(403, [], "Доступ запрещён");
}

$data = [
    "canUpdate" => $user->access >= 4,
    "rows" => []
];

$rows = R::findAll("inactives", "server = ?", [$user->server]);
if (count($rows) <= 0) {
    sendResponse(200, $data);
}

$vkids = [];
$usersMap = [];

foreach ($rows as $row) {
    $vkids[] = $row->vkid;
    if ($row->admin != 0) {
        $vkids[] = $row->admin;
    }
}

$vkids = array_values(array_unique($vkids));
$users = R::findAll("users", "vkid IN (" . R::genSlots($vkids) . ")", $vkids);

foreach ($users as $u) {
    $usersMap[$u->vkid] = $u;
}

foreach (array_values($rows) as $i => $row) {
    $isMyRow = $row->nick == $user->nick && $row->server == $user->server;

    array_push($data["rows"], [
        "id" => $i + 1,
        "uid" => $row->id,
        "nick" => $row->nick,
        "user_id" => @$usersMap[$row->vkid]->id ?? -1,
        "reason" => ($user->access >= 4 || $isMyRow) ? $row->reason : "<span class='text-secondary'>Скрыто</span>",
        "lvl" => (int) $row->lvl,
        "date_start" => date("d.m.Y", $row->start),
        "date_end" => date("d.m.Y", $row->end),
        "days_count" => ceil(($row->end - $row->start) / 86400),
        "status" => (int) $row->status,
        "status_info" => ($row->status == 1
            ? '<span class="text-success">Одобрено</span>'
            : ($row->status == 0
                ? '<span class="text-secondary">На рассмотрении</span>'
                : '<span class="text-danger">Отказано</span>')),
        "admin" => @$usersMap[$row->admin]->nick ?? "Неизвестный",
    ]);
}

sendResponse(200, $data);
