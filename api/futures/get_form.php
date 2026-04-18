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

$user = R::findOne('users', 'vkid = ? AND access >= 4', [$_SESSION['userId']]);
if (!$user) {
    sendResponse(403, [], "Доступ запрещён");
}

$row = R::findOne('admform', 'id = ?', [$uid]);
if (!$row) {
    sendResponse(404, [], "Анкета не найдена");
}

if (!in_array($row->status, [1, 2])) {
    sendResponse(403, [], "Эта анкета не заполнена, её нельзя посмотреть");
}

if ($row->server != $user->server) {
    sendResponse(403, [], "Доступ запрещён");
}

if ($row->isnew) {
    if ($user->server >= 500) {
        $form = implode("\n", [
            "[РАНЕЕ НЕ БЫЛ АДМИНИСТРАТОРОМ]",
            "",
            "-------------------------",
            "Ник: {$row->nick} " . (isset($row->newnick) && strlen($row->newnick) > 0 && $row->newnick != $nick ? "(Новый ник: {$row->newnick})" : ""),
            "Имя: {$row->name}",
            "Возраст: " . $row->birth,
            "Занимал ли пост лидера фракции: {$row->reason}",
            "VK: https://vk.ru/id{$row->vkid}",
            "Ссылка на активный форумный аккаунт: {$row->forum}",
            "Текущий IP адрес кандидата: {$row->ip}",
            "Discord: {$row->discord}",
            "Почта(ы): {$row->mails}",
            "Играл ли кандидат на других серверах проекта (Если да, то каких): {$row->accounts}"
        ]);
    } else {
        $form = implode("\n", [
            "[РАНЕЕ НЕ БЫЛ АДМИНИСТРАТОРОМ]",
            "",
            "-------------------------",
            "Ник: {$row->nick} " . (isset($row->newnick) && strlen($row->newnick) > 0 && $row->newnick != $nick ? "(Новый ник: {$row->newnick})" : ""),
            "Имя: {$row->name}",
            "Возраст: " . $row->birth,
            "Занимал ли пост лидера фракции: {$row->reason}",
            "VK: https://vk.ru/id{$row->vkid}",
            "Почта(ы): {$row->mails}",
            "Ссылка на активный форумный аккаунт: {$row->forum}",
            "Текущий IP адрес кандидата: {$row->ip}",
            "Играл ли кандидат на других серверах проекта (Если да, то каких): {$row->accounts}"
        ]);
    }
} else {
    if ($user->server >= 500) {
        $form = implode("\n", [
            "[ВОССТАНОВЛЕНИИЕ]",
            "",
            "-------------------------",
            "Ник: {$row->nick} " . (isset($row->newnick) && strlen($row->newnick) > 0 && $row->newnick != $nick ? "(был {$row->newnick})" : "(не менялся)"),
            "Имя: {$row->name}",
            "Возраст: " . $row->birth,
            "Город проживания: {$row->city}",
            "VK: https://vk.ru/id{$row->vkid}",
            "Discord: {$row->discord}",
            "Почта(ы): {$row->mails}",
            "Какой уровень был у кандидата: {$row->lvl}",
            "На какой уровень будет восстановлен: [УКАЗАТЬ]",
            "Находится ли в чёрном списке на других серверах Rodina RP: [Да/Нет]",
            "Ссылка на форумный(ные) аккаунт(ы): {$row->forum}",
            "Почему он должен быть восстановлен: [УКАЗАТЬ]",
            "Были ли выговоры при администрировании: [Да/Нет]",
            "Причина снятия: {$row->reason}",
            "В какой период занимал пост администратора: с " . date("d.m.Y", $row->start) . " по " . date("d.m.Y", $row->end),
            "Ссылка на архив администратора: [УКАЗАТЬ]"
        ]);
    } else {
        $form = implode("\n", [
            "[ВОССТАНОВЛЕНИИЕ]",
            "",
            "-------------------------",
            "Ник: {$row->nick} " . (isset($row->newnick) && strlen($row->newnick) > 0 && $row->newnick != $nick ? "(был {$row->newnick})" : "(не менялся)"),
            "Имя: {$row->name}",
            "VK: https://vk.ru/id{$row->vkid}",
            "Discord: {$row->discord}",
            "Почта(ы): {$row->mails}",
            "Reg ip: [УКАЗАТЬ]",
            "Last ip: [УКАЗАТЬ]",
            "Какой уровень администрирования был у кандидата: {$row->lvl}",
            "На какой уровень будет восстановлен: [УКАЗАТЬ]",
            "Ссылка на форумный(ные) аккаунт(ы): {$row->forum}",
            "Почему он должен быть восстановлен: [УКАЗАТЬ]",
            "За что был снят: {$row->reason}",
            "В какой период занимал пост администратора: с " . date("d.m.Y", $row->start) . " по " . date("d.m.Y", $row->end),
            "Аккаунты на других серверах если имеются (так же указать был ли администратором на др. сервере): {$row->accounts}",
            "Ссылка на Архив Администраторов (если архивов несколько с разных серверов, указать): [УКАЗАТЬ]",
            "Дополнительная информация: [УКАЗАТЬ]"
        ]);
    }
}

sendResponse(200, [
    'name' => $row->vkname,
    'form' => $form
]);
