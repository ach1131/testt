<?php
include_once "../../config.php";
allowedMethods(["GET"]);

if (!isset($_SESSION['userId'])) {
    sendResponse(401, [], "Вы не авторизованы!");
}

$uid = filter_input(INPUT_GET, "uid", FILTER_VALIDATE_INT);
if (!isset($uid)) {
    sendResponse(400, [], "Некорректный запрос");
}

$user = R::findOne('users', 'vkid = ? AND access >= 5', [$_SESSION['userId']]);
if (!$user) {
    sendResponse(403, [], "Доступ запрещён");
}

$row = R::findOne('admarchive', 'id = ?', [$uid]);
if (!$row) {
    sendResponse(404, [], "Архив не найден");
}

if ($row->server != $user->server) {
    sendResponse(403, [], "Доступ запрещён");
}

sendResponse(200, [
    'nick' => $row['usernick'],
    'lvl' => $row['lvl'],
    'archive' => $row['archive']
]);
