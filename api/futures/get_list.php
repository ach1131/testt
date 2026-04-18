<?php
include_once "../../config.php";
allowedMethods(["GET"]);

if (!isset($_SESSION["userId"])) {
    sendResponse(401, [], "Вы не авторизованы");
}

$user = R::findOne("users", "vkid = ? AND access >= 4", [$_SESSION["userId"]]);
if (!$user) {
    sendResponse(403, [], "Доступ запрещён");
}

$data = ["rows" => []];
$forms = R::findAll("admform", "server = ?", [$user->server]);
if (count($forms) > 0) {
    $adminVKIds = array_values(array_unique(array_column($forms, "admin")));
    $admins = [];

    if (!empty($adminVKIds)) {
        $all_admins = R::findAll("users", "vkid IN (" . R::genSlots($adminVKIds) . ")", $adminVKIds);
        foreach ($all_admins as $a) {
            $admins[$a->vkid] = $a;
        }
    }

    foreach (array_values($forms) as $i => $row) {
        $admin = $admins[$row->admin] ?? null;

        array_push($data["rows"], [
            "id" => $i + 1,
            "uid" => $row->id,
            "user_vk" => [
                "id" => (int) $row->vkid,
                "name" => $row->vkname
            ],
            "until_time" => $row->time + 86400,
            "status" => (int) $row->status,
            "admin" => [
                "id" => $admin ? (int) $admin->id : -1,
                "nick" => $admin->nick ?? "Неизвестно"
            ]
        ]);
    }
}

sendResponse(200, $data);
