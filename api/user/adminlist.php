<?php
include_once '../../config.php';
header('Content-Type: application/json');

class AdminListResponse
{
    public $error = false;
    public $message = "Успех!";
    public $admins = [];
    public $jb = false;
    public $sendjb = false;
    public $warns = false;
    public $server = null;
    public $levelStats = [];

    public function addAdmin($admin)
    {
        $this->admins[] = $admin;
    }
}

class ErrorResponse
{
    public $error = true;
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}

class AdminListService
{
    private $user;
    private $serverInfo;
    private $levelMap = [];
    private $inactiveData = [];

    public function __construct($user)
    {
        $this->user = $user;
        $this->serverInfo = R::findone('serverlist', 'id = ?', [$user->server]);
        $this->loadLevelMap();
        $this->loadInactiveData();
    }

    private function loadLevelMap()
    {
        $levels = R::findall('lvllist');
        foreach ($levels as $level) {
            $this->levelMap[$level->id] = $level->lvl;
        }
    }

    private function loadInactiveData()
    {
        $serverAdmins = R::findall('users', 'server = ? AND accept = 1', [$this->user->server]);
        $adminNicks = array_column($serverAdmins, 'nick');
        
        if (!empty($adminNicks)) {
            $placeholders = str_repeat('?,', count($adminNicks) - 1) . '?';
            $this->inactiveData = R::findall('inactives', 
                "nick IN ($placeholders) AND server = ? AND status = 1", 
                array_merge($adminNicks, [$this->user->server])
            );
        }
    }

    private function getLevelName($levelId)
    {
        return $this->levelMap[$levelId] ?? "Неизвестно";
    }

    private function calculateInactiveDays($admin)
    {
        $allInactiveDays = 0;
        $levelInactiveDays = 0;
        $isCurrentlyInactive = false;

        foreach ($this->inactiveData as $inactive) {
            if ($inactive->nick === $admin->nick && $inactive->server === $admin->server) {
                $days = round(($inactive->end - $inactive->start) / 86400);
                $allInactiveDays += $days;
                
                if ($inactive->start > $this->user->promoted) {
                    $levelInactiveDays += $days;
                }
                
                if ($inactive->start < time() && $inactive->end > time()) {
                    $isCurrentlyInactive = true;
                }
            }
        }

        return [
            'all' => $allInactiveDays + $admin->inactive,
            'level' => $levelInactiveDays + $admin->nowinactive,
            'currently' => $isCurrentlyInactive
        ];
    }

    private function formatPost($post)
    {
        return strlen($post) > 15 ? mb_strimwidth($post, 0, 15, '...') : $post;
    }

    private function generateButtons($admin)
    {
        if (!$this->user || $this->user->access < 5) {
            return ['send' => '', 'popup' => ''];
        }

        $sendButton = '<button type="button" class="btn btn-sm btn-outline-secondary px-1 py-0 mx-1" style="opacity: 0.4;" data-bs-toggle="modal" data-bs-target="#exampleDarkModal' . $admin->id . '">ЖБ</button>';
        
        $popupModal = "<div class=\"modal fade\" id=\"exampleDarkModal{$admin->id}\" tabindex=\"-1\" aria-hidden=\"true\">
            <div class=\"modal-dialog modal-lg modal-dialog-centered\">
                <div class=\"modal-content bg-dark\">
                    <div class=\"modal-header\">
                        <h5 class=\"modal-title text-white\">Передача жалобы для {$admin->nick}</h5>
                        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\" aria-label=\"Close\"></button>
                    </div>
                    <div class=\"modal-body text-white\">
                        <div class=\"alert alert-warning border-0 bg-warning alert-dismissible fade show py-2\" id=\"addErrorForum{$admin->id}\" hidden>
                            <div class=\"d-flex align-items-center\">
                                <div class=\"font-35 text-dark\"><i class='bx bx-info-circle'></i></div>
                                <div class=\"ms-3\">
                                    <h6 class=\"mb-0 text-dark\">Ошибка отправки</h6>
                                    <div class=\"text-dark\" id=\"ErrorTextForum{$admin->id}\"></div>
                                </div>
                            </div>
                            <button type=\"button\" class=\"btn-close text-dark\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
                        </div>
                        <div class=\"alert alert-success border-0 bg-success alert-dismissible fade show py-2\" id=\"addSuccessForum{$admin->id}\" hidden>
                            <div class=\"d-flex align-items-center\">
                                <div class=\"font-35 text-white\"><i class='bx bxs-check-circle'></i></div>
                                <div class=\"ms-3\">
                                    <h6 class=\"mb-0 text-white\">Жалоба успешно передана!</h6>
                                </div>
                            </div>
                            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
                        </div>
                        <p class='mb-0'>Введите ссылку на жалобу, которую небходимо передать администратору <b>{$admin->nick}</b></p>
                        <p>Пользователь должен разрешить отправку сообщений от паблика <a href=\"https://vk.ru/arztools\">Arizona Tools</a></p>
                        <input type=\"text\" class=\"form-control mb-3\" id=\"sendForum{$admin->id}\" placeholder=\"https://forum.arizona-rp.com/threads/5555555/\">
                        <label for=\"commentForum{$admin->id}\" class=\"form-label\">Комментарий (необязательно)</label>
                        <textarea class=\"form-control\" id=\"commentForum{$admin->id}\" rows=\"3\" placeholder=\"Дополнительная информация к жалобе...\"></textarea>
                    </div>
                    <div class=\"modal-footer justify-content-start\">
                        <button type=\"button\" onclick=\"sendthread({$admin->id})\" class=\"btn btn-primary\">Передать</button>    
                        <button type=\"button\" class=\"btn btn-outline-secondary\" data-bs-dismiss=\"modal\">Закрыть</button>    
                    </div>
                </div>
            </div>
        </div>";

        return ['send' => $sendButton, 'popup' => $popupModal];
    }

    private function calculateLevelStats($admins)
    {
        $levelStats = [];
        $levelColors = [
            1 => 'rgb(0, 151, 161)', // бирюзовый
            2 => 'rgb(0, 119, 167)',  // голубой
            3 => 'rgb(170, 125, 0)',  // оранжевый
            4 => 'rgb(0, 156, 60)',   // фиолетовый
            5 => 'rgb(117, 0, 156)',  // розовый
            6 => 'rgb(142, 182, 0)',   // зеленый
            7 => 'rgb(0, 107, 0)'    // темно-зеленый
        ];
        
        // Инициализируем все уровни от 1 до 7 с нулевым счетчиком
        for ($level = 1; $level <= 7; $level++) {
            $levelStats[$level] = [
                'level' => $level,
                'count' => 0,
                'color' => $levelColors[$level] ?? 'rgb(128, 128, 128)'
            ];
        }
        
        // Подсчитываем реальное количество администрации по уровням
        foreach ($admins as $admin) {
            $level = $admin->lvl;
            if (isset($levelStats[$level])) {
                $levelStats[$level]['count']++;
            }
        }
        
        // Сортируем по уровню (от меньшего к большему)
        ksort($levelStats);
        return array_values($levelStats);
    }

    public function getAdminList()
    {
        $response = new AdminListResponse();
        
        $response->jb = $this->serverInfo->jb > 0;
        $response->warns = $this->serverInfo->warns > 0;
        $response->sendjb = $this->user->access >= 5;
        $response->server = $this->user->server;

        $serverAdmins = R::findall('users', 'server = ? AND accept = 1 ORDER BY `users`.`lvl` DESC', [$this->user->server]);
        
        if (empty($serverAdmins)) {
            return $response;
        }
        
        // Добавляем статистику по уровням
        $response->levelStats = $this->calculateLevelStats($serverAdmins);
        
        foreach ($serverAdmins as $admin) {
            $inactiveInfo = $this->calculateInactiveDays($admin);
            $buttons = $this->generateButtons($admin);

            $response->addAdmin([
                'number' => count($response->admins) + 1,
                'image' => $admin->vkpic ?: "https://vk.ru/images/camera_200.png",
                'id' => $admin->id,
                'nick' => $admin->nick,
                'prefix' => trim(preg_replace('/^[^\p{L}]+/u', '', $admin->prefix)),
                'vk' => $admin->vkid,
                'realname' => $admin->realname,
                'inStatus' => $inactiveInfo['currently'],
                'lvl' => $admin->lvl,
                'daylvl' => round(abs(time() - $admin->promoted) / 86400),
                'dayall' => round(abs(time() - $admin->dateassign) / 86400),
                'forum' => $admin->forum,
                'discord' => $admin->discord,
                'gamerep' => $admin->gamerep,
                'plusrep' => $admin->plusrep,
                'inactivelvl' => $inactiveInfo['level'],
                'inactive' => $inactiveInfo['all'],
                'buttonsend' => $buttons['send'],
                'buttonpopup' => $buttons['popup'],
                'lvlname' => $this->getLevelName($admin->lvl),
                'post' => $this->formatPost($admin->post),
                'fullpost' => $admin->post,
                'reprimands' => $admin->reprimands,
                'warns' => $admin->warns,
                'jb' => $admin->jb
            ]);
        }

        return $response;
    }
}

function echoResponse($response)
{
    echo json_encode($response, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
}

if (isset($_SESSION['userId'])) {
    $user = R::findone('users', 'vkid = ? AND access > 0', [$_SESSION['userId']]);
    if ($user) {
        $service = new AdminListService($user);
        $response = $service->getAdminList();
        echoResponse($response);
        exit;
    }
}

echoResponse(new ErrorResponse("Недостаточно прав!"));

