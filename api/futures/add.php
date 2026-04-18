<?php
include_once "../../config.php";
allowedMethods(["POST"]);

if (!isset($_SESSION['userId'])) {
    sendResponse(401, [], "Вы не авторизованы");
}

$user = R::findOne('users', 'vkid = ? AND access >= 4', [$_SESSION['userId']]);
if (!$user) {
    sendResponse(403, [], "Доступ запрещён");
}

$list = filter_input(INPUT_POST, "list");
if (!$list) {
    sendResponse(400, [], "Вы не указали ни одного пользователя");
}

$errors = [];
$count = 0;

foreach (explode("\n", $list) as $line) {
    $line = trim($line);
    if ($line == "") {
        continue;
    }

    if (preg_match('~^(?:https?://)?vk\.com/([a-zA-Z0-9_.]+)$~', $line, $matches)) {
        $user_id = getVKUserID($matches[1]);
        if ($user_id !== false) {
            $vkname = "Пользователь @id{$user_id}";
            $vk_user = $VK_BOT->userInfo($user_id, ["photo_200"]);
            if (isset($vk_user)) {
                $vkname = "{$vk_user['first_name']} {$vk_user['last_name']}";
            }

            $form = R::findOne('admform', 'vkid = ? AND status = 0 AND time > ? ORDER BY id DESC', [$user_id, strtotime('-24 hours')]);
            if (!$form) {
                $form = R::dispense('admform');
            }

            $form->server = $user->server;
            $form->vkid = $user_id;
            $form->vkname = $vkname;
            $form->time = time();
            $form->admin = $_SESSION['userId'];

            if (R::store($form)) {
                $count++;
                continue;
            }
        }
    }

    $errors[] = $line;
}

sendResponse(200, [
    "warn" => count($errors) > 0,
    "answer" => implode("<br>", [
        "Доступ получили: {$count}",
        "Ошибок: " . count($errors)
    ]),
    "input" => implode("\n", $errors)
]);
