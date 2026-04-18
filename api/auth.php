<?php

include_once '../config.php';

if (isset($_GET['tauth']) && $_GET['tauth'] >= 0) {
    if (isset($_GET['code'])) {
        $result = true;
        $params = [
            'client_id' => VK_CLIENT_ID,
            'client_secret' => VK_CLIENT_SECRET,
            'code' => $_GET['code'],
            'redirect_uri' => ($_GET['tauth'] == 1 ? VK_CLIENT_REDIRECT_URI_FORM_1 : VK_CLIENT_REDIRECT_URI_FORM_0)
        ];

        $token = json_decode(file_get_contents('https://oauth.vk.ru/access_token' . '?' . urldecode(http_build_query($params))), true);

        if (isset($token['access_token'])) {
            $params = [
                'uids' => $token['user_id'],
                'fields' => 'uid,first_name,last_name,screen_name,sex,bdate,photo_200',
                'access_token' => $token['access_token'],
                'v' => '5.101'
            ];

            $userInfo = json_decode(file_get_contents('https://api.vk.ru/method/users.get' . '?' . urldecode(http_build_query($params))), true);
            if (isset($userInfo['response'][0]['id'])) {
                $userInfo = $userInfo['response'][0];
                $result = true;
            }
        }

        if ($result) {
            if ($_GET['tauth'] == 0) {
                $user = R::findone('users', 'vkid = ?', [$userInfo['id']]);
                
                if ($user) {
                    $_SESSION['userId'] = $userInfo['id'];
                    $_SESSION['first_name'] = $userInfo['first_name'];
                    $_SESSION['last_name'] = $userInfo['last_name'];
                    $_SESSION['photo'] = $userInfo['photo_200'];
                    if ($user->accept == 1) {
                        $returnUrl = getReturnUrl();
                        if ($returnUrl) {
                            header("Location: {$returnUrl}");
                            exit;
                        }
                        redirectTo('index.php');
                    } else {
                        redirectTo('reg.php');
                    }
                } else {
                    $_SESSION['userId'] = $userInfo['id'];
                    $_SESSION['first_name'] = $userInfo['first_name'];
                    $_SESSION['last_name'] = $userInfo['last_name'];
                    $_SESSION['photo'] = $userInfo['photo_200'];
                    redirectTo('reg.php');
                }
            } elseif ($_GET['tauth'] == 1) {
                $_SESSION['vk'] = $userInfo['id'];
                $_SESSION['type'] = 1;
                $_SESSION['first_name'] = $userInfo['first_name'];
                $_SESSION['last_name'] = $userInfo['last_name'];
                redirectTo('aform.php');
            } else {
                $_SESSION['userId'] = $userInfo['id'];
                $_SESSION['first_name'] = $userInfo['first_name'];
                $_SESSION['last_name'] = $userInfo['last_name'];
                $_SESSION['photo'] = $userInfo['photo_200'];
                $returnUrl = getReturnUrl();
                if ($returnUrl) {
                    header("Location: {$returnUrl}");
                    exit;
                }
                redirectTo('index.php');
            }
        }
    }
} else {
    if (isset($_GET['hash']) && isset($_GET['uid']) && isset($_GET['first_name']) && isset($_GET['last_name']) && isset($_GET['photo'])) {
        if ($_GET['hash'] == md5(VK_CLIENT_ID . $_GET['uid'] . VK_CLIENT_SECRET)) {
            if (!isset($_GET['type'])) {
                $user = R::findone('users', 'vkid = ?', [$_GET['uid']]);
                
                if ($user) {
                    $_SESSION['userId'] = $_GET['uid'];
                    $_SESSION['first_name'] = $_GET['first_name'];
                    $_SESSION['last_name'] = $_GET['last_name'];
                    $_SESSION['photo'] = $_GET['photo'];
                    if ($user->accept == 1) {
                        $returnUrl = getReturnUrl();
                        if ($returnUrl) {
                            header("Location: {$returnUrl}");
                            exit;
                        }
                        redirectTo('index.php');
                    } else {
                        redirectTo('reg.php');
                    }
                } else {
                    $_SESSION['userId'] = $_GET['uid'];
                    $_SESSION['first_name'] = $_GET['first_name'];
                    $_SESSION['last_name'] = $_GET['last_name'];
                    $_SESSION['photo'] = $_GET['photo'];
                    redirectTo('reg.php');
                }
            } else {
                if ($_GET['type'] == 1) {
                    $_SESSION['vk'] = $_GET['uid'];
                    $_SESSION['type'] = 1;
                    $_SESSION['first_name'] = $_GET['first_name'];
                    $_SESSION['last_name'] = $_GET['last_name'];
                    redirectTo('aform.php');
                } else {
                    $returnUrl = getReturnUrl();
                    if ($returnUrl) {
                        header("Location: {$returnUrl}");
                        exit;
                    }
                    redirectTo('index.php');
                }
            }
        }
    }
}
