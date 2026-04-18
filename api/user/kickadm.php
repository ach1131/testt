<?php
ini_set('display_errors', 1);
include_once '../../config.php';
header('Content-Type: application/json');

class Response
{
    public $error;
    public $message;

    function __construct($error, $message, $archive = null)
    {
        $this->error = $error;
        $this->message = $message;
        $this->archive = $archive;
    }
}

function echoResponse($response)
{
    echo json_encode($response);
}

$vkid = @$_POST['vkid'];
$reason = @$_POST['reason'];
$hide = @$_POST['hide'];

if (isset($_SESSION['userId'])) {
    if (isset($vkid) && strlen($vkid) > 0 && isset($hide) && strlen($hide) > 0 && isset($reason) && strlen($reason) > 0) {
        $user = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
        if ($user) {
            if ($user->access >= 6) {
                send_kick($vkid, $reason, $hide);
            } else {
                return echoResponse(new Response(true, "Вам недоступно данное действие!"));
            }
        } else {
            return echoResponse(new Response(true, "Вы не зарегистрированы!"));
        }
    } else {
        return echoResponse(new Response(true, "Не переданы все данные! {$vkid} | {$reason} | {$hide}"));
    }
} else {
    return echoResponse(new Response(true, "Недостаточно прав!"));
}


function send_kick($uid, $reason, $hide = 0)
{
    global $VK_BOT;
    global $USERBOT;

    $reasoninfo = (isset($reason) && isset($reason) > 0 ? $reason : "Не указано");
    $reasonhide = ($hide == 1 ? "Не разглашается" : $reasoninfo);

    $myinfo = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
    $user = R::findone('users', 'vkid = ?', [$uid]);

    if ($user->vkid == $_SESSION['userId'] && $_SESSION['userId'] != 133031610) {
        return echoResponse(new Response(true, "Вы не можете снять самого себя!"));
    } elseif ($user->access > $myinfo->access) {
        return echoResponse(new Response(true, "Нельзя снять администратора, доступом выше!"));
    } else {
        if ($user) {
            if ($user->accept == 1) {
                if ($myinfo->server == $user->server || $myinfo->access == 8) {
                    $countcf = 0;

                    if ($myinfo->access < $user->access) {
                        return echoResponse(new Response(true, "Нельзя снять администратора с доступом, выше Вашего!"));
                    }

                    $conflist = R::findall('conferences', 'server = ? AND kick = 1 AND peer != 0', [$user->server]);

                    $textkick = "👀 Администратор {$user->nick} ({$user->lvl}LVL) снят с поста администратора по причине: {$reasoninfo}!\nСнял: [id{$_SESSION['userId']}|{$myinfo->nick}]\n\nИсключение из этих бесед:\n";
                    foreach ($conflist as $key => $value) {
                        $confapeer = $value['peer'] + 2000000000;
                        $countcf += 1;
                        if ($countcf > 0) {
                            $kick = $VK_BOT->request('messages.removeChatUser', ['chat_id' => $value['peer'], 'member_id' => $user->vkid]);
                            if (!isset($kick['error'])) {
                                $textkick .= "{$countcf}) Беседа \"{$value['name']}\" (Статус: ✅)\n";
                                $VK_BOT->sendMessage(
                                    $confapeer,
                                    "🤖 {$myinfo->post} [id{$_SESSION['userId']}|{$myinfo->nick}] исключил [id{$user->vkid}|{$user->nick}] ({$user->lvl}LVL)\nПричина: {$reasonhide}",
                                    ["disable_mentions" => true]
                                );
                            } else {
                                if ($kick['error']['error_code'] !== 935) {
                                    $textkick .= "{$countcf}) Беседа \"{$value['name']}\" (Статус: ⚠️ {$kick['error']['error_msg']} [#{$kick['error']['error_code']}])\n";
                                }
                            }
                        }
                    }
                    if ($countcf == 0) {
                        $textkick .= "- Не найдено бесед для быстрого кика!\n";
                    }

                    $pubfind = R::findone('serverlist', 'id = ? AND public != 0', [$user->server]);
                    if ($pubfind) {
                        $pkick = $USERBOT->request("groups.removeUser", ["group_id" => $pubfind->public, "user_id" => $user->vkid]);
                        if (!isset($pkick['error'])) {
                            $textkick .= "\n⚜️ Пользователь успешно исключен из [club{$pubfind->public}|админ-группы ВК]!\n";
                        } else {
                            $textkick .= "\n⚠️ Не удалось исключить пользователя из [club{$pubfind->public}|админ-группы ВК].\nПричина: {$pkick['error']['error_msg']} [#{$pkick['error']['error_code']}]\n";
                        }
                    }
                    $textkick .= "\n👾 Discord: {$user->discord}\n🤖 Форумный аккаунт: {$user->forum}\n👀 Форма: /makeadminoff {$user->nick} 0\n";
                    $bac = $user->access;
                    $user->accept = 0;
                    $user->registration = 0;
                    $user->access = 0;
                    $saveuser = R::store($user);
                    if ($saveuser) {
                        $textkick .= "\n💙 Пользователь успешно деактивирован!\n";
                    } else {
                        $textkick .= "\n⚠️ Возникла ошибка при деактивировании пользователя!\n";
                    }

                    $banacc = "Неизвестно";
                    $sar = [
                        2 => [1 => 'Да', 2 => 'Да', 3 => 'Да', 4 => 'Да', 5 => 'Да', 6 => 'Да'],
                        8 => [1 => 'Да', 2 => 'Да', 3 => 'Да', 4 => 'Да', 5 => 'Да', 6 => 'Да'],
                        10 => [1 => 'Да', 2 => 'Да', 3 => 'Нет', 4 => 'Нет', 5 => 'Да', 6 => 'Да'],
                        28 => [1 => 'Да', 2 => 'Да', 3 => 'Да', 4 => 'Да', 5 => 'Да', 6 => 'Да'],
                    ];
                    if (isset($sar[$user->server]) && isset($sar[$user->server][$user->lvl])) {
                        $banacc = $sar[$user->server][$user->lvl];
                    }

                    $archive = "Ник администратора который был снят: {$user->nick}
Возраст: " . ($user->birthdate > 1000 ? getAge(date('Y-m-d', $user->birthdate)) : $user->birthdate) . "
VK: https://vk.ru/id{$user->vkid}
Discord: {$user->discord}
Город проживания: {$user->city}
Ссылка на форумный аккаунт: https://" . preg_replace('/(http(s|)\:\/\/|)/', '', $user->forum) . "
За что был снят [Полное описание причины снятия]: {$reasoninfo}
Период администрирования с " . date("d.m.Y", $user->dateassign) . " по " . date("d.m.Y", time()) . "
Был ли следящим фракции: [Указать фракцию]: {$user->post}
Получал ли бан аккаунта в связи со снятием: " . $banacc;

                    $archivenew = R::dispense('admarchive');
                    $archivenew->server = $user->server;
                    $archivenew->vk = $user->vkid;
                    $archivenew->reason = $reason;
                    $archivenew->fdate = $user->dateassign;
                    $archivenew->ldate = time();
                    $archivenew->usernick = $user->nick;
                    $archivenew->access = $bac;
                    $archivenew->lvl = $user->lvl;
                    $archivenew->admin = $_SESSION['userId'];
                    $archivenew->archive = $archive;
                    $archivesave = R::store($archivenew);
                    if ($archivesave) {
                        $archivetext = "🟢 Занесен в БД";
                    } else {
                        $archivetext = "⚠️ Ошибка при занесении в БД";
                    }
                    $textkick .= "\n\n👥 Архив на администратора 👥\n🤖 Статус: {$archivetext}\n\n🐣 Обновите список администрации на форуме -> https://" . SITE_DOMAIN . "/forumadm.php";

                    $sendlist = R::findall('conferences', 'server = ? AND chief = 1 AND peer != 0', [$user->server]);
                    foreach ($sendlist as $key => $value) {
                        $VK_BOT->SendMessage(
                            $value['peer'] + 2_000_000_000,
                            $textkick,
                            ['disable_mentions' => true]
                        );
                    }

                    addlog($user->server, $user->vkid, "{$myinfo->post} <a href=\"user.php?id={$myinfo->id}\">{$myinfo->nick}</a> снял <a href=\"user.php?id={$user->id}\">{$user->nick}</a> ({$user->lvl}) по причине: {$reasonhide}");
                    return echoResponse(new Response(false, "Воспользуйтесь командой /archives в боте ВК, чтобы закрыть архив на {$user->nick}", $archive));
                } else {
                    return echoResponse(new Response(true, "Администратор {$user->nick} не привязан к Вашему серверу!"));
                }
            } else {
                return echoResponse(new Response(true, "Администратор {$user->nick} не подтвержден!"));
            }
        } else {
            return echoResponse(new Response(true, "Указанный пользователь не найден в Базе Данных (\"{$uid}\")"));
        }
    }
}

function addlog($server = null, $user = null, $text = null)
{
    if (isset($server) && isset($text)) {
        $newlog = R::dispense('logs');
        $newlog->server = $server;
        $newlog->time = time();
        $newlog->vkuser = $user;
        $newlog->log = $text;
        $save = R::store($newlog);
    }
}
function getAge($then)
{
    $then = date('Ymd', strtotime($then));
    $diff = date('Ymd') - $then;
    return substr($diff, 0, -4);
}
