<?php
include_once "../../config.php";
allowedMethods(["POST"]);

if (!isset($_SESSION['userId'])) {
	sendResponse(401, [], "Вы не авторизованы");
}

$user = R::findOne('users', 'vkid = ? AND access >= 5', [$_SESSION['userId']]);
if (!$user) {
	sendResponse(403, [], "Доступ запрещён");
}

$template = filter_input(INPUT_POST, "template");
if (!isset($template)) {
	sendResponse(400, [], "Некорректный запрос");
}

$server = R::findOne('serverlist', 'id = ?', [$user->server]);
if (!$server) {
	sendResponse(400, [], "Невозможно выполнить это действие");
}

$server->jbinfo = $template;
if (!R::store($server)) {
	sendResponse(500, [], "Ошибка сохранения, попробуйте позже..");
}

sendResponse(200, ["answer" => "Форма передачи жалоб успешно обновлена"]);
