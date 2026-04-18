<?php
include_once "../../config.php";
allowedMethods(["GET"]);

if (!isset($_SESSION['userId'])) {
    sendResponse(401, [], "Вы не авторизованы!");
}

$user = R::findOne('users', 'vkid = ? AND access >= 5', [$_SESSION['userId']]);
if (!$user) {
    sendResponse(403, [], "Доступ запрещён!");
}

$data = [];
$rows = R::findAll('admarchive', 'server = ?', [$user->server]);
if (count($rows) > 0) {
    $vkids = array_values(array_merge(
        array_column($rows, 'admin'),
        array_column($rows, 'vk')
    ));

    $usersMap = [];
    $all_admins = R::findAll("users", "vkid IN (" . R::genSlots($vkids) . ")", $vkids);
    foreach ($all_admins as $admin) {
        $usersMap[$admin->vkid] = $admin;
    }

    foreach (array_values($rows) as $i => $row) {
        $data[] = [
            'id' => $i + 1,
            'uid' => $row['id'],
            'nick' => $row['usernick'] ?? "<span class='text-secondary'>Не указан</span>",
            'reason' => $row['reason'] ?? "<span class='text-secondary'>Не указана</span>",
            'user_id' => (int) @$usersMap[$row['vk']]->id,
            'lvl' => $row['lvl'],
            'time' => date("d.m.Y", $row['ldate']),
            'admin' => [
                "id" => @$usersMap[$row['admin']]->id ?? -1,
                "nick" => @$usersMap[$row['admin']]->nick ?? "Неизвестно"
            ],
            'status' => (int) $row['sending'],
            'status_info' => match ((int) $row['sending']) {
                1 => "<span class='text-success'>✅ Занесён</span>",
                0 => "<span class='text-secondary'>⌛ На рассмотрении</span>",
                default => "<span class='text-danger'>❌ Удален</span>",
            }
        ];
    }
}

sendResponse(200, $data);
