<?php

namespace DigitalStar\vk_api;

require_once('config_library.php');

class Auth
{
    private $login = null;
    private $pass = null;
    private $cookie = null;
    private $useragent = DEFAULT_USERAGENT;
    private $id_app = DEFAULT_ID_APP;
    private $access_token = '';
    private $scope = '';
    private $method = '';
    private $default_scope = DEFAULT_SCOPE;
    private $is_auth = 0;
    private $auth_method = 0;
    private $captcha_sid = null;

    public function __construct($login, $pass = null, $other = null, $mobile = true)
    {
        if (!isset($login))
            throw new VkApiException("Укажите логин и пароль либо куки");
        if (is_array($other)) {
            if (isset($other['useragent']))
                $this->useragent = $other['useragent'];
            if (isset($other['id_app']))
                $this->id_app = $other['id_app'];
        }
        if (isset($pass)) {
            $this->login = $login;
            $this->pass = $pass;
            $this->method = 'pass';
            if (!$mobile)
                $this->auth_method = 1;
        } else {
            $this->method = 'cookie';
            $this->cookie = json_decode($login, true);
            $this->auth_method = 1;
        }
    }

    public function auth()
    {
        if ($this->auth_method == 0)
            throw new VkApiException("Только для авторизации через приложение");
        $this->loginInVK();
    }

    private function loginInVK()
    {
        $query_main_page = $this->getCURL('https://vk.ru/');
        preg_match('/name=\"ip_h\" value=\"(.*?)\"/s', $query_main_page['body'], $ip_h);
        preg_match('/name=\"lg_h\" value=\"(.*?)\"/s', $query_main_page['body'], $lg_h);

        $values_auth = [
            'act' => 'login',
            'role' => 'al_frame',
            '_origin' => 'https://vk.ru',
            'utf8' => '1',
            'email' => $this->login,
            'pass' => $this->pass,
            'lg_h' => $lg_h[1],
            'ig_h' => $ip_h[1]
        ];
        $get_url_redirect_auch = $this->getCURL('https://login.vk.ru/?act=login', $values_auth);

        if (!isset($get_url_redirect_auch['header']['location']))
            throw new VkApiException("Ошибка, ссылка редиректа не получена");

        $auth_page = $this->getCURL($get_url_redirect_auch['header']['location'][0]);

        if (!isset($auth_page['header']['set-cookie']))
            throw new VkApiException("Ошибка, куки пользователя не получены");

        $this->is_auth = 1;
    }

    private function getCURL($url, $post_values = null, $cookie = true)
    {
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, $this->useragent);
            if (isset($post_values)) {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $post_values);
            }

            if ($cookie and isset($this->cookie)) {
                $send_cookie = [];
                foreach ($this->cookie as $cookie_name => $cookie_val) {
                    $send_cookie[] = "$cookie_name=$cookie_val";
                }
                curl_setopt($curl, CURLOPT_COOKIE, join('; ', $send_cookie));
            }

            curl_setopt(
                $curl,
                CURLOPT_HEADERFUNCTION,
                function ($curl, $header) use (&$headers) {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2)
                        return $len;

                    $name = strtolower(trim($header[0]));
                    if (isset($headers) and !array_key_exists($name, $headers))
                        $headers[$name] = [trim($header[1])];
                    else
                        $headers[$name][] = trim($header[1]);

                    return $len;
                }
            );

            $out = curl_exec($curl);
            curl_close($curl);
            if (isset($headers['set-cookie']))
                $this->parseCookie($headers['set-cookie']);
            return ['header' => $headers, 'body' => $out];
        }
    }

    private function parseCookie($new_cookie)
    {
        foreach ($new_cookie as $cookie) {
            preg_match("!(.*?)=(.*?);(.*)!s", $cookie, $preger);
            if ($preger[2] == 'DELETED')
                unset($this->cookie[$preger[1]]);
            else
                $this->cookie[$preger[1]] = $preger[2] . ';' . $preger[3];
        }
    }

    public function dumpCookie()
    {
        return json_encode($this->cookie);
    }

    public function isAuth()
    {
        $header = $this->getCURL("https://vk.ru/feed")['header'];
        if (isset($header['location'][0]) and strpos($header['location'][0], 'login.vk.ru'))
            return False;
        return True;
    }

    public function getAccessToken($captcha_key = null, $captcha_sid = null)
    {
        if ($this->access_token != '')
            return $this->access_token;
        if ($this->auth_method) {
            if ($this->is_auth == 0)
                $this->loginInVK();
            if ($this->access_token == '')
                $this->access_token = $this->generateAccessToken();
        } else {
            if (isset($this->captcha_sid))
                $captcha_sid = $this->captcha_sid;
            $this->access_token = $this->generateAccessTokenMobile($captcha_key, $captcha_sid);
        }
        return $this->access_token;
    }

    private function generateAccessToken($scope = null, $resend = false)
    {
        $this->scope = [];
        if (!isset($scope)) {
            $scope = $this->default_scope;
        }
        foreach (preg_split("!,!", $scope) as $one_scope)
            $this->scope[] = $one_scope;
        $scope = "&scope=$scope";

        if ($resend)
            $scope .= "&revoke=1";

        $token_url = 'https://oauth.vk.ru/authorize?client_id=' . $this->id_app .
            $scope .
            '&response_type=token';

        $get_url_token = $this->getCURL($token_url);

        if (isset($get_url_token['header']['location'][0]))
            $url_token = $get_url_token['header']['location'][0];
        else {
            preg_match('!location.href = "(.*)"\+addr!s', $get_url_token['body'], $url_token);

            if (!isset($url_token[1])) {
                throw new VkApiException("Не получилось получить токен на этапе получения ссылки подтверждения");
            }
            $url_token = $url_token[1];
        }

        $access_token_location = $this->getCURL($url_token)['header']['location'][0];

        if (preg_match("!access_token=(.*?)&!s", $access_token_location, $access_token) != 1)
            throw new VkApiException("Не удалось найти access_token в строке ридеректа, ошибка:" . $this->getCURL($access_token_location, null, false)['body']);
        return $access_token[1];
    }

    private function generateAccessTokenMobile($captcha_key, $captcha_sid)
    {
        if (!isset($this->pass))
            throw new VkApiException("Метод работает только с логином и паролем");

        $captcha = '';
        $this->scope = [];
        $scope = $this->default_scope;
        foreach (preg_split("!,!", $scope) as $one_scope)
            $this->scope[] = $one_scope;
        $scope = "&scope=$scope";

        if (isset($captcha_sid) and isset($captcha_key))
            $captcha = "&captcha_sid=$captcha_sid&captcha_key=$captcha_key";

        $token_url = 'https://oauth.vk.ru/token?grant_type=password&client_id=2274003&client_secret=hHbZxrka2uZ6jB1inYsH' .
            '&username=' . $this->login .
            '&password=' . $this->pass .
            $scope .
            $captcha;
        $response_auth = $this->getCURL($token_url, null, false)['body'];
        $response_auth = json_decode($response_auth, true);

        if (isset($response_auth['access_token']))
            return $response_auth['access_token'];
        else
            throw new VkApiException(json_encode($response_auth));
    }
}
