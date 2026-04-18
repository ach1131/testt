<?php
include_once "../../config.php";
allowedMethods(["POST"]);

if (isset($_SESSION['userId'])) {
	$user_id = filter_input(INPUT_POST, "user_id", FILTER_VALIDATE_INT);
	$date_start = filter_input(INPUT_POST, "date_start");
	$date_end = filter_input(INPUT_POST, "date_end");
	$reason = filter_input(INPUT_POST, "reason");

	if (isset($user_id, $date_start, $date_end, $reason)) {
		if ($reason == "") {
			sendResponse(400, [], "Вы не указали причину!");
		}

		$date_start_unix = strtotime("{$date_start} 00:00:00");
		$date_end_unix = strtotime("{$date_end} 23:59:59");
		if ($date_start_unix && $date_end_unix) {
			if ($date_start_unix <= $date_end_unix) {
				$status = 0;
				$admin = 0;

				$sender_user = R::findone('users', 'vkid = ? AND access > 0', [$_SESSION['userId']]);
				if (!is_object($sender_user)) {
					sendResponse(400, [], "Доступ запрещён!");
				}

				$target_user = R::findone('users', 'id = ? AND access > 0', [$user_id]);
				if (!is_object($target_user)) {
					sendResponse(400, [], "Такого администратора нет в базе данных!");
				}

				if ($sender_user->id != $target_user->id) {
					if ($sender_user->access < 4) {
						sendResponse(400, [], "Вы не можете выдавать неактив другим администраторам!");
					}

					if ($target_user->access <= 0) {
						sendResponse(400, [], "Этот администратор снят!");
					}

					if ($sender_user->server != $target_user->server && $sender_user->access < 8) {
						sendResponse(400, [], "Вы не можете выдать неактив администратору с другого сервера!");
					}

					$max_access = $sender_user->access >= 6 ? $sender_user->access : $sender_user->access - 1;
					if ($target_user->access > $max_access) {
						sendResponse(400, [], "Ваш доступ не позволяет выдать неактив этому администратору!");
					}

					if (isUserCanAcceptInncative($sender_user->vkid, $sender_user->server)) {
						$status = 1;
						$admin = $sender_user->vkid;
					}
				}

				if (R::findone('inactives', 'nick = ? AND server = ? AND created_at >= NOW() - INTERVAL 60 SECOND', [$target_user->nick, $sender_user->server])) {
					sendResponse(429, [], "Нельзя так часто! Попробуйте через минуту..");
				}

				$new_inactive = R::dispense('inactives');
				$new_inactive->server = $sender_user->server;
				$new_inactive->vkid = $target_user->vkid;
				$new_inactive->nick = $target_user->nick;
				$new_inactive->lvl = $target_user->lvl;
				$new_inactive->reason = $reason;
				$new_inactive->start = $date_start_unix;
				$new_inactive->end = $date_end_unix;
				$new_inactive->status = $status;
				$new_inactive->admin = $admin;
				$inactive_id = R::store($new_inactive);

				if ($inactive_id) {
					$date_1 = date("d.m.Y", $date_start_unix);
					$date_2 = date("d.m.Y", $date_end_unix);
					$days_count_value = ceil(($date_end_unix - $date_start_unix) / 86400);
					$days_count = plural($days_count_value, ['день', 'дня', 'дней']);

					$chiefcf = R::findall('conferences', 'server = ? AND inactive = 1', [$sender_user->server]);
					foreach ($chiefcf as $key => $value) {
						$kb = [
							"buttons" => [
								[
									[
										"action" => [
											"type" => "open_link",
											"label" => "👤 Профиль на сайте",
											"link" => "https://" . SITE_DOMAIN . "/user.php?id={$target_user->id}"
										]
									]
								]
							],
							"inline" => true
						];

						switch ($status) {
							case 1:
								array_push($kb["buttons"], [
									[
										"action" => [
											"type" => "text",
											"label" => "🔙 Отменить",
											"payload" => [
												"command" => "newinactive",
												"idbd" => $inactive_id,
												"type" => "3"
											]
										],
										"color" => "primary"
									]
								]);

								$message = [
									"📝 Установлен неактив администратору!",
									"👥 Игровой сервер: №{$sender_user->server}",
									"",
									"🤴 Администратор: @id{$target_user->vkid} ({$target_user->nick})",
									"👽 Уровень: {$target_user->lvl}LVL.",
									"📅 Начало неактива: {$date_1}",
									"📅 Окончание неактива: {$date_2}",
									"📆 Длительность неактива: {$days_count}",
									"🦖 Причина: {$reason}",
									"",
									"Выдал неактив: @id{$sender_user->vkid} ({$sender_user->nick})"
								];

								$VK_BOT->sendMessage(
									$value['peer'] + 2_000_000_000,
									implode("\n", $message),
									[
										"keyboard" => json_encode($kb),
										"disable_mentions" => true
									]
								);
								break;
							default:
								array_push($kb["buttons"], [
									[
										"action" => [
											"type" => "text",
											"label" => "✅ Одобрить",
											"payload" => [
												"command" => "newinactive",
												"idbd" => $inactive_id,
												"type" => "1"
											]
										],
										"color" => "primary"
									],
									[
										"action" => [
											"type" => "text",
											"label" => "⛔ Отказать",
											"payload" => [
												"command" => "newinactive",
												"idbd" => $inactive_id,
												"type" => "2"
											]
										],
										"color" => "primary"
									]
								]);

								$message = [
									"📝 Новое заявление на неактив!",
									"👥 Игровой сервер: №{$sender_user->server}",
									"",
									"🤴 Администратор: @id{$target_user->vkid} ({$target_user->nick})",
									"👽 Уровень: {$target_user->lvl}LVL.",
									"📅 Начало неактива: {$date_1}",
									"📅 Окончание неактива: {$date_2}",
									"📆 Длина неактива: {$days_count}",
									"🦖 Причина: {$reason}"
								];

								$VK_BOT->sendMessage(
									$value['peer'] + 2_000_000_000,
									implode("\n", $message),
									[
										"keyboard" => json_encode($kb),
										"disable_mentions" => true
									]
								);
								break;
						}
					}

					if ($status == 1) {
						$VK_BOT->sendMessage(
							$target_user->vkid,
							"⚠️ Администратор @id{$sender_user->vkid} ({$sender_user->nick}) установил вам неактив с {$date_1} по {$date_2} ($days_count) c причиной: {$reason}",
							["disable_mentions" => true]
						);
					}
					sendResponse(200, ["answer" => "Заявка успешно отправлена"]);
				} else {
					sendResponse(500, [], "Ошибка сохранения заявки");
				}
			} else {
				sendResponse(400, [], "Дата окончания не может быть раньше начальной");
			}
		}
	} else {
		sendResponse(400, [], "Некорректный запрос");
	}
}
sendResponse(401, [], "Вы не авторизованы");

function isUserCanAcceptInncative($vk_id, $server_id) {
	global $VK_BOT;

	$cf_list = R::findall('conferences', 'server = ? AND inactive = 1', [$server_id]);
	foreach ($cf_list as $cf) {
		try {
			$members = $VK_BOT->request(
				'messages.getConversationMembers',
				['peer_id' => $cf['peer'] + 2_000_000_000]
			)['items'];
		} catch (Exception $e) {
			continue;
		}

		foreach ($members as $member) {
			if ($member['member_id'] == $vk_id) {
				return true;
			}
		}
	}
	return false;
}