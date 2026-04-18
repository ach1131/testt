<?php
require_once "lib/EnvLoader.php";
require_once "lib/rb.php";

EnvLoader::load();

ini_set('session.cookie_lifetime', 24 * 60 * 60);
ini_set('session.gc_maxlifetime', 24 * 60 * 60);
session_set_cookie_params(24 * 60 * 60);

session_start();

define('SIDE_BAR_NAME', 'Admins ' . (EnvLoader::isProduction() ? "" : "<span class='text-secondary'>(β)</span>"));
define('SITE_DOMAIN', EnvLoader::get('SITE_DOMAIN', (EnvLoader::isProduction() ? "admin" : "a-test") . ".arztools.tech"));

define('VK_CLIENT_ID', EnvLoader::get('VK_CLIENT_ID'));
define('VK_CLIENT_SECRET', EnvLoader::get('VK_CLIENT_SECRET'));
define('VK_CLIENT_REDIRECT_URI', "https://" . SITE_DOMAIN . "/api/auth.php");
define('VK_CLIENT_REDIRECT_URI_FORM_0', "https://" . SITE_DOMAIN . "/api/auth.php?tauth=0");
define('VK_CLIENT_REDIRECT_URI_FORM_1', "https://" . SITE_DOMAIN . "/api/auth.php?tauth=1");
define('VK_GROUP_ID', EnvLoader::get('VK_GROUP_ID'));

function sendResponse(int $code = 200, array $data = [], string|null $error = null)
{
    $response = [
        "code" => $code,
        "data" => $data
    ];

    if (isset($error)) {
        $response["error"] = $error;
    }

    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$DB_CONFIG = EnvLoader::getDatabaseCredentials();
R::setup($DB_CONFIG['dsn'], $DB_CONFIG['username'], $DB_CONFIG['password']);
if (!R::testConnection()) {
    sendResponse(500, [], "Критическая ошибка на стороне сайта");
}

require_once "lib/simplevk-master/autoload.php";

use DigitalStar\vk_api\VK_api as vk_api;

$VK_BOT = vk_api::create(EnvLoader::get('BOT_MAIN_TOKEN'), "5.199");
$USERBOT = vk_api::create(EnvLoader::get('USERBOT_TOKEN'), '5.199');

function allowedMethods(array $methods = ["POST"])
{
    if (!in_array($_SERVER["REQUEST_METHOD"], $methods)) {
        sendResponse(405, [], "Недопустимый метод запроса");
    }
}

function getBaseUrl()
{
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    return $url . $_SERVER['HTTP_HOST'];
}

function getCurrentUrl()
{
    $baseUrl = getBaseUrl();
    $requestUri = $_SERVER['REQUEST_URI'];
    return $baseUrl . $requestUri;
}

function redirectTo($path)
{
    if ($path === 'login.php' && !isset($_SESSION['return_url'])) {
        $currentUrl = getCurrentUrl();
        if (!str_contains($currentUrl, 'login.php') && !str_contains($currentUrl, 'logout.php')) {
            $_SESSION['return_url'] = $currentUrl;
        }
    }
    
    $baseUrl = getBaseUrl();
    $redirectUrl = "{$baseUrl}/{$path}";
    header("Location: {$redirectUrl}");
    exit;
}

function getReturnUrl()
{
    if (isset($_SESSION['return_url'])) {
        $returnUrl = $_SESSION['return_url'];
        unset($_SESSION['return_url']);
        return $returnUrl;
    }
    return null;
}

function isMessagesFromGroupAllowed($user_id)
{	
	global $VK_BOT;

	$response = $VK_BOT->request('messages.isMessagesFromGroupAllowed', [
        'group_id' => VK_GROUP_ID,
        'user_id' => intval($user_id),
        'v' => '5.131'
    ]);

    return $response['is_allowed'] === 1;
}

function getVKUserID(string|int $user)
{
    if (is_numeric($user)) {
        $user = "id{$user}";
    }

    global $USERBOT;
    $obj = $USERBOT->request('utils.resolveScreenName', [
        'screen_name' => $user
    ]);

    $user_id = @$obj["object_id"];
    $type = @$obj["type"];

    if (!$user_id || $type !== "user") {
        return false;
    }
    return $user_id;
}

function plural($num, $titles, $show = true)
{
    $cases = [2, 0, 1, 1, 1, 2];
    $result = $titles[($num % 100 > 4 && $num % 100 < 20) ? 2 : $cases[min($num % 10, 5)]];
    return $show ? "{$num} {$result}" : $result;
}

function head(string|null $title)
{
?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/images/logo.svg" type="image/svg+xml" />
    <link href="assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
    <link href="assets/plugins/metismenu/css/metisMenu.min.css" rel="stylesheet" />
    <link href="assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/pace.min.css" rel="stylesheet" />
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <link href="assets/css/icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dark-theme.css" />
    <script src="assets/js/pace.min.js"></script>
    <link rel="stylesheet" href="assets/plugins/notifications/css/lobibox.min.css" />
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <?php
    if ($title) {
        echo "<title id='siteTitle'>" . $title . " - Arizona Admins</title>";
    } else {
        echo "<title id='siteTitle'>Arizona Admins</title>";
    }
}

function sidebar($userAccess)
{
    $menuItems = [
        ['index.php', 'bx bx-shield-quarter', 'Администрация', 1],
        ['onlines.php', 'bx bx-time', 'Онлайн', 1],
        ['inactives.php', 'bx bx-pause-circle', 'Неактивы', 1],
        ['futures.php', 'bx bx-list-check', 'Будущие админы', 4],
        ['forumadm.php', 'bx bx-message-detail', 'Форумный список', 4],
        ['archives.php', 'bx bx-folder', 'Архивы', 5],
        ['forumedit.php', 'bx bx-edit', 'Форма передачи ЖБ', 5],
    ];

    ?>
    <div class="sidebar-wrapper" data-simplebar="true">
        <div class="sidebar-header" style="user-select: none;">
            <div>
                <img src="assets/images/logo.svg" class="logo-icon" alt="Arizona Admins" style="filter: none; ">
            </div>
            <div>
                <h4 class="logo-text" style="margin-top: 5px; font-weight: 500;">
                    <?= SIDE_BAR_NAME; ?>
                </h4>
            </div>
            <div class="toggle-icon ms-auto">
                <i class="bx bx-arrow-to-left" style="margin-top: 5px;"></i>
            </div>
        </div>
        <ul class="metismenu" id="menu">
            <?php foreach ($menuItems as [$link, $icon, $title, $access]): ?>
                <?php if ($userAccess >= $access): ?>
                    <li>
                        <a href="<?= $link; ?>">
                            <div class="parent-icon">
                                <i class='<?= $icon; ?>'></i>
                            </div>
                            <div class="menu-title" style="white-space: nowrap;"><?= $title; ?></div>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($userAccess >= 1): ?>
                <li>
                    <a href="https://lead.arztools.tech/" target="_blank">
                        <div class="parent-icon">
                            <i class='bx bx-shield-alt'></i>
                        </div>
                        <div class="menu-title" style="white-space: nowrap;">Лидерская система</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($userAccess >= 5): ?>
                <li>
                    <a href="https://arztools.tech/grafana/dashboards/f/bey067jr4f3lsc/?orgId=1" target="_blank">
                        <div class="parent-icon"><i class='bx bx-bar-chart'></i></div>
                        <div class="menu-title" style="white-space: nowrap;">Мониторинг</div>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($userAccess >= 6): ?>
                <li>
                    <a href="https://vk.me/join/3MUv4qrsBDwt0BZM0tfSIU0fI2TNls0wPv0=" target="_blank">
                        <div class="parent-icon"><i class='bx bx-conversation'></i></div>
                        <div class="menu-title" style="white-space: nowrap;">Беседа руководства</div>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
<?php
}

function topbar($userInfo)
{
?>
    <header>
        <div class="topbar d-flex align-items-center">
            <nav class="navbar navbar-expand">
                <div class="mobile-toggle-menu"><i class='bx bx-menu'></i></div>
                <div class="top-menu ms-auto"></div>
                <div class="user-box dropdown">
                    <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= $_SESSION['photo'] ?>" class="user-img" alt="user avatar">
                        <div class="user-info ps-3">
                            <p class="user-name mb-0"><?= $userInfo->nick; ?></p>
                            <p class="designattion mb-0"><?= $userInfo->post; ?></p>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="user.php">
                                <i class="bx bx-user"></i>
                                <span>Профиль</span>
                            </a>
                        </li>
                        <li>
                            <div class="dropdown-divider mb-0"></div>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/api/logout.php">
                                <i class='bx bx-log-out-circle'></i>
                                <span>Выход</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
<?php
}

function scripts()
{
?>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/js/logout.js"></script>
    <script src="assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="assets/plugins/notifications/js/lobibox.min.js"></script>
    <script src="assets/plugins/notifications/js/notifications.min.js"></script>
    <script src="assets/plugins/notifications/js/notification-custom-script.js"></script>
    <script src="assets/js/app.js"></script>
<?php
}

function footer()
{
?>
    <div class="overlay toggle-icon"></div>
    <a href="javaScript:;" class="back-to-top">
        <i class='bx bxs-up-arrow-alt'></i>
    </a>
    <footer class="page-footer">
        <p class="mb-0" style="color: #666">Arizona Admins © 2022 - 2025</p>
    </footer>
<?php
}

function pageAccess($needAccess = 0)
{
    if (!isset($_SESSION['userId'])) {
        redirectTo('login.php');
    }

    $user = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
    if ($user->accept == 0) {
        redirectTo('reg.php');
    }

    if ($user->access < $needAccess) {
        redirectTo('index.php');
    }

    $server = R::findone('serverlist', 'id = ?', [$user->server]);
    return [$user, $server];
}
