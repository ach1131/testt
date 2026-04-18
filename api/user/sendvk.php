<?php
ini_set('display_errors', 1);
include_once '../../config.php';
header('Content-Type: application/json');

class Response
{
    public $error;
    public $message;

    function __construct($error, $message)
    {
        $this->error = $error;
        $this->message = $message;
    }
}

function echoResponse($response)
{
    echo json_encode($response);
}

$id = @$_POST['id'];
$url = @$_POST['url'];
$comment = @$_POST['comment'];

if (isset($_SESSION['userId'])) {
    if (isset($id) && strlen($id) > 0 && isset($url) && strlen($url) > 0) {
        $myinfo = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
        if ($myinfo->access >= 4) {
            $user = R::findone('users', 'id = ?', [$id]);
            if ($user) {
                $is_arizona = $myinfo->server < 500 || $myinfo->server == 666;
                if ($is_arizona) {
                    $prall = preg_match('/(https:\/\/|)forum\.arizona-rp\.com\/threads\/(\d+)/', $url, $matched);
                } else {
                    $prall = preg_match('/(https:\/\/|)forum\.rodina-rp\.com\/threads\/(\d+)/', $url, $matched);
                }
                if ($prall) {
                    $url = "https://forum." . ($is_arizona ? "arizona" : "rodina") . "-rp.com/threads/" . $matched[2];

                    $formfind = R::findone('serverlist', 'id = ?', [$myinfo->server]);
                    if (isset($formfind)) {
                        if ($formfind->jbinfo != null) {
                            $forma = $formfind->jbinfo;
                        } else {
                            $forma = "⚠ {nick}, на Вас поступила новая жалоба ⚠\n\n👀 Ссылка: {url}\n⏰ Время ответа с момента передачи: 24 часа";
                        }
                    } else {
                        $forma = "⚠ {nick}, на Вас поступила новая жалоба ⚠\n\n👀 Ссылка: {url}\n⏰ Время ответа с момента передачи: 24 часа";
                    }

                    $arrkey = array('{nick}', '{url}', '{mynick}', '{vkid}', '{myvk}', '{comment}');
                    $comment_text = isset($comment) && strlen(trim($comment)) > 0 ? trim($comment) : '-';
                    $arrayvalue = array($user->nick, $url, $myinfo->nick, $user->vkid, $myinfo->vkid, $comment_text);
                    
                    $message_text = str_replace($arrkey, $arrayvalue, $forma);
                    
                    if (strpos($forma, '{comment}') === false && isset($comment) && strlen(trim($comment)) > 0) {
                        $message_text .= "\n\n💬 Комментарий:\n" . trim($comment);
                    }
                    
                    $result_send = $VK_BOT->sendMessage($user->vkid, $message_text);
                    if (!isset($result_send['error'])) {
                        $comment_log = isset($comment) && strlen(trim($comment)) > 0 ? trim($comment) : "нет";
                        $log_text = "{$myinfo->post} <a href=\"user.php?id={$myinfo->id}\">{$myinfo->nick}</a> передал(а) администратору <a href=\"user.php?id={$user->id}\">{$user->nick}</a> жалобу <a href=\"{$url}\" target=\"_blank\">№{$matched[2]}</a> (комментарий: {$comment_log})";
                        addlog($user->server, $user->vkid, $log_text);
                        return echoResponse(new Response(false, "OK"));
                    } else {
                        if ($result_send['error']['error_code'] == 901) {
                            return echoResponse(new Response(true, "Пользователь не разрешил паблику @arztools отправку сообщений"));
                        } else {
                            return echoResponse(new Response(true, "Ошибка ВКонтакте №{$result_send['error']['error_code']}"));
                        }
                    }
                } else {
                    return echoResponse(new Response(true, "Ссылка указана неверно!"));
                }
            } else {
                return echoResponse(new Response(true, "Пользователь не зарегистрирован!"));
            }
        } else {
            return echoResponse(new Response(true, "Ваш доступ не позволяет передавать жалобы!"));
        }
    } else {
        return echoResponse(new Response(true, "Не все данные переданы!"));
    }
}
echoResponse(new Response(true, "Недостаточно прав!"));

function addlog($server = null, $vk = null, $text = null)
{
    if (isset($server) && isset($text)) {
        $newlog = R::dispense('logs');
        $newlog->server = $server;
        $newlog->time = time();
        $newlog->vkuser = $vk;
        $newlog->log = $text;
        R::store($newlog);
    }
}
