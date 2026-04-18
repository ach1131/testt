<?php
include_once "../../config.php";
allowedMethods(["POST"]);

if (isset($_SESSION['userId'])) {
	$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
	$vk = filter_input(INPUT_POST, "vk", FILTER_VALIDATE_INT);
	$status = filter_input(INPUT_POST, "status", FILTER_VALIDATE_INT);

	if ((isset($id) || isset($vk)) && isset($status)) {
		$user = R::findOne('users', 'vkid = ?', [$_SESSION['userId']]);
		if ($user) {
			if ($user->access >= 5) {
				if (isset($vk)) {
					$archive = R::findOne('admarchive', 'vk = ? ORDER BY id DESC', [$vk]);
				} else {
					$archive = R::findOne('admarchive', 'id = ?', [$id]);
				}

				if ($archive) {
					if ($archive->server == $user->server) {
						if ($archive->sending == $status) {
							sendResponse(400, [], "Этот архив уже имеет такой статус!");
						}

						$archive->sending = $status;
						if (R::store($archive)) {
							sendResponse(200, ["answer" => "Статус архива успешно изменен"]);
						} else {
							sendResponse(500, [], "Не удалось изменить статус архива");
						}
					} else {
						sendResponse(403, [], "Этот архив не принадлежит вашему серверу");
					}
				} else {
					sendResponse(404, [], "Архив не найден");
				}
			} else {
				sendResponse(403, [], "Вы не можете это сделать");
			}
		} else {
			sendResponse(403, [], "Вы не можете это сделать");
		}
	} else {
		sendResponse(400, [], "Некорректный запрос");
	}
}
sendResponse(401, [], "Вы не авторизованы");
