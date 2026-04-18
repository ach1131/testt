<?php
include_once "../../config.php";
allowedMethods(["POST"]);

if (isset($_SESSION['userId'])) {
	$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
	$status = filter_input(INPUT_POST, "status", FILTER_VALIDATE_INT);

	if (isset($id) && isset($status)) {
		$user = R::findOne('users', 'vkid = ?', [$_SESSION['userId']]);
		if ($user) {
			if ($user->access >= 4) {
				$form = R::findOne('admform', 'id = ?', [$id]);
				if ($form) {
					if ($form->server == $user->server) {
						if (!$form->nick && $status == 1) {
							sendResponse(400, [], "Эта анкета не заполнена, её нельзя одобрить");
						} else if ($form->status == 3 && $status == 0) {
							sendResponse(400, [], "Эта анкета уже отменена!");
						} else if ($form->status == 2 && $status == 1) {
							sendResponse(400, [], "Эта анкета уже одобрена!");
						}

						$form->status = $status == 1 ? 2 : 3;
						$conf_futures = R::findAll("conferences", "server = ? AND badm = 1", [$form->server]);
						$conf_result = [0, count($conf_futures)];

						foreach ($conf_futures as $cf) {
							$res = match ($form->status) {
								2 => $USERBOT->request('messages.addChatUser', ['chat_id' => $cf->uid, 'user_id' => $form->vkid]),
								3 => $USERBOT->request('messages.removeChatUser', ['chat_id' => $cf->uid, 'member_id' => $form->vkid])
							};

							if (!isset($res["error"])) {
								$conf_result[0]++;
							}

							sleep(1);
						}

						if (R::store($form)) {
							sendResponse(200, [
								"answer" => "Статус анкеты успешно изменен",
								"new_status" => $form->status,
								"conf_result" => $conf_result
							]);
						} else {
							sendResponse(500, [], "Не удалось изменить статус анкеты");
						}
					} else {
						sendResponse(403, [], "Этот анкета не принадлежит вашему серверу");
					}
				} else {
					sendResponse(404, [], "Анкета не найдена");
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
