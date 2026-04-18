<?php
include_once "../../config.php";
allowedMethods(["POST"]);

if (isset($_SESSION['userId'])) {
	$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
	$status = filter_input(INPUT_POST, "status", FILTER_VALIDATE_INT);

	if (isset($id, $status)) {
		$user = R::findOne('users', 'vkid = ?', [$_SESSION['userId']]);
		if ($user) {
			if ($user->access >= 4) {
				$inactive = R::findOne('inactives', 'id = ?', [$id]);
				if ($inactive) {
					if ($inactive->server == $user->server) {
						if (time() - $inactive->end > 86400 * 30) {
							sendResponse(400, [], "Эта заявка уже слишком старая, чтобы изменить её статус!");
						}

						if ($inactive->status == $status) {
							sendResponse(400, [], "Эта заявка уже " . ($status == 1 ? "принята" : "отказана") . "!");
						}

						$inactive->status = $status;
						$inactive->admin = $_SESSION['userId'];
						if (R::store($inactive)) {
							$action = match ((int) $status) {
								1 => "одобрен",
								default => "ОТКЛОНЁН",
							};

							$VK_BOT->sendMessage(
								$inactive->vkid,
								"🔔 {$inactive->nick}, ваш неактив, который вы запросили с " . date('d.m.Y', $inactive->start) . " по " . date('d.m.Y', $inactive->end) . " был {$action} администратором @id{$_SESSION['userId']} ({$user->nick})",
								["disable_mentions" => true]
							);

							sendResponse(200, [
								"answer" => "Статус неактива {$inactive->nick} успешно изменен!",
								"status_info" => ($status == 1
									? '<span class="text-success">Одобрено</span>'
									: '<span class="text-danger">Отказано</span>'
								),
								"admin" => $user->nick
							]);
						} else {
							sendResponse(500, [], "Не удалось изменить статус неактива {$inactive->nick}");
						}
					} else {
						sendResponse(403, [], "Неактив {$inactive->nick} принадлежит серверу №{$inactive->server}");
					}
				} else {
					sendResponse(404, [], "Заявка на неактив с таким ID не найдена");
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
