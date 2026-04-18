<?php
include_once 'config.php';

if (!isset($_SESSION['userId'])) {
    redirectTo('login.php');
}

$myuser = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);

if ($myuser->access <= 0) {
    redirectTo("index.php");
}

if (!isset($_GET['id']) || strlen($_GET['id']) == 0) {
    redirectTo('user.php?id=' . $myuser->id);
}

$user = R::findone('users', 'id = ?', [$_GET['id']]);
if (!$user) {
    redirectTo('index.php');
}

if (in_array($user->nick, ["Не указано", "Неуказано"])) {
    redirectTo('index.php');
}

$hasAccess = ($myuser->access == 8) || ($user->server == $myuser->server);
if (!$hasAccess) {
    redirectTo('index.php');
}

$servername = R::findone('serverlist', 'id = ?', [$user->server])->servername;
$userlvl = R::findone('lvllist', 'id = ?', [$user->lvl])->lvl;

$edituser = ($myuser->access == 8) ||
    ($myuser->access >= 6 && $myuser->id == $user->id) ||
    ($myuser->access >= 4 && $myuser->access > $user->access);

$server = R::findone('serverlist', 'id = ?', [$user->server]);
$jbs = ($server->jb > 0);

$kickedUser = ($user->access == 0 && $user->accept == 0);

$isServer8 = ($user->server == 8);
$warningsText = $isServer8 ? 'Предупреждения' : 'Выговоры';
$strikesText = $isServer8 ? 'Страйки' : 'Предупреждения';
$warningsReasonText = $isServer8 ? 'пред.' : 'выговор';
$strikesReasonText = $isServer8 ? 'страйк' : 'пред.';

$accessLevels = [
    0 => "Отсутствует",
    1 => "Пользователь",
    2 => "Следящий",
    3 => "Зам./Гл. Следящий",
    4 => "Тех. Администратор",
    5 => "Куратор",
    6 => "Заместитель ГА",
    7 => "Гл. Администратор",
    8 => "Руководство"
];

$destinationTypes = [
    1 => "Лидерка",
    2 => "Обзвон",
    3 => "Восстановление",
    4 => "Перевод",
    5 => "Судья"
];

function getDisabledAttr($condition)
{
    return $condition ? 'disabled' : '';
}
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head>
    <?php head("Профиль"); ?>
</head>

<body>
    <div class="wrapper">
        <?php sidebar($myuser->access); ?>
        <header>
            <div class="topbar d-flex align-items-center">
                <nav class="navbar navbar-expand">
                    <div class="mobile-toggle-menu"><i class='bx bx-menu'></i></div>
                    <div class="top-menu ms-auto"></div>
                    <div class="user-box dropdown">
                        <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $_SESSION['photo'] ?>" class="user-img" alt="user avatar">
                            <div class="user-info ps-3">
                                <p class="user-name mb-0"><?php echo $myuser->nick; ?></p>
                                <p class="designattion mb-0"><?php echo $myuser->post; ?></p>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="user.php"><i
                                        class="bx bx-user"></i><span>Профиль</span></a>
                            </li>
                            <li>
                                <div class="dropdown-divider mb-0"></div>
                            </li>
                            <li><a class="dropdown-item" href="/api/logout.php"><i
                                        class='bx bx-log-out-circle'></i><span>Выход</span></a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </header>
        <div class="page-wrapper">
            <div class="page-content">
                <div class="container">
                    <div class="main-body">
                        <div class="row">
                            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                                <div class="breadcrumb-title pe-3">Профиль пользователя</div>
                                <div class="ps-3">
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb mb-0 p-0">
                                            <li class="breadcrumb-item"><a href="index.php"><i
                                                        class="bx bx-home-alt"></i></a>
                                            </li>
                                            <li class="breadcrumb-item active" aria-current="page"
                                                id="infoIDProfileUser">Загрузка...</li>
                                        </ol>
                                    </nav>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex flex-column align-items-center text-center">
                                            <img src="<?php echo $user->vkpic; ?>" alt="Admin"
                                                class="rounded-circle p-1 bg-none" width="120">
                                            <div class="mt-3">
                                                <h4 id="infoNickNameUser"
                                                    class="<?php echo $kickedUser ? 'text-danger mb-1' : ''; ?>">
                                                    Загрузка...</h4>
                                                <p class="text-secondary mb-1" id="infoPostUser">Загрузка...</p>
                                                <p class="text-warning mb-1" id="infoInactiveUser" hidden>Загрузка...
                                                </p>
                                                <p class="text-muted font-size-sm" id="infoServerUser">Загрузка...</p>
                                                <a target="_blank" rel="noopener noreferrer" id="infoVKUser"><button
                                                        class="btn btn-primary">VK</button></a>
                                                <a target="_blank" rel="noopener noreferrer" id="infoForumUser"><button
                                                        class="btn btn-outline-secondary">Форумник</button></a>
                                            </div>
                                        </div>
                                        <hr class="my-4" />
                                        <ul class="list-group list-group-flush">
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                <h6 class="mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-award me-2 icon-inline">
                                                        <circle cx="12" cy="8" r="7"></circle>
                                                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88">
                                                        </polyline>
                                                    </svg>
                                                    Уровень:
                                                </h6>
                                                <span class="text-secondary" id="infoLVLUser">Загрузка...</span>
                                            </li>
                                            <?php
                                            $scores = json_decode($server->scores, true);
                                            if ($scores['status'] == true):
                                                ?>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                    <h6 class="mb-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                            width="24" height="24"
                                                            class="feather feather-award me-2 icon-inline" fill="none"
                                                            stroke="currentColor" stroke-linecap="round"
                                                            stroke-linejoin="round" stroke-width="2">
                                                            <path d="M18 8h1a4 4 0 0 1 0 8h-1" />
                                                            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z" />
                                                            <line x1="6" x2="6" y1="1" y2="4" />
                                                            <line x1="10" x2="10" y1="1" y2="4" />
                                                            <line x1="14" x2="14" y1="1" y2="4" />
                                                        </svg>
                                                        Рейтинг:
                                                    </h6>
                                                    <?php if ($edituser && !$kickedUser): ?>
                                                        <a class="text-secondary" style="padding-right: 0;" type="button"
                                                            data-bs-toggle="modal" data-bs-target="#editScores"
                                                            id="infoScoreUser">Загрузка...</a>
                                                        <div class="modal fade" id="editScores" tabindex="-1"
                                                            aria-hidden="true">
                                                            <div class="modal-dialog modal modal-dialog-centered">
                                                                <div class="modal-content bg-dark">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title text-white">Редактирование
                                                                            рейтинга</h5>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body text-white">
                                                                        <p style="text-align: center;">Изменение рейтинговой
                                                                            системы для <b><?php echo $user->nick; ?></b></p>
                                                                        <hr>
                                                                        <p>Введите новое значение <b>Очков Рейтинга</b></p>
                                                                        <input type="text" class="form-control" id="scoreInput1"
                                                                            placeholder="<?php echo $user->scores; ?>"
                                                                            value="<?php echo $user->scores; ?>">
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-light"
                                                                            data-bs-dismiss="modal">Закрыть</button>
                                                                        <button type="button"
                                                                            onclick="changeuser(4, `scoreInput1`)"
                                                                            class="btn btn-dark"
                                                                            data-bs-dismiss="modal">Подтвердить</button>
                                                                        <!-- <button type="button" onclick="changeuser(1, `inactiveInput1`)" class="btn btn-dark">Подтвердить</button> -->
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-secondary" id="infoScoreUser">Загрузка...</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endif; ?>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                <h6 class="mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-minus-circle me-2 icon-inline">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <line x1="8" y1="12" x2="16" y2="12"></line>
                                                    </svg>
                                                    <?php echo $warningsText; ?>
                                                </h6>
                                                <span class="text-secondary" id="infoReprimandsUser">Загрузка</span>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                <h6 class="mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-frown me-2 icon-inline">
                                                        <circle cx="12" cy="12" r="10"></circle>
                                                        <path d="M16 16s-1.5-2-4-2-4 2-4 2"></path>
                                                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                                                    </svg>
                                                    <?php echo $strikesText; ?>
                                                </h6>
                                                <span class="text-secondary" id="infoWarnsUser">Загрузка...</span>
                                            </li>
                                            <?php if ($jbs): ?>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                    <h6 class="mb-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="feather feather-folder-minus me-2 icon-inline">
                                                            <path
                                                                d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z">
                                                            </path>
                                                            <line x1="9" y1="14" x2="15" y2="14"></line>
                                                        </svg>
                                                        Жалобы
                                                    </h6>
                                                    <span class="text-secondary" id="infoJBUser">Загрузка...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                <h6 class="mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-battery-charging me-2">
                                                        <path
                                                            d="M5 18H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.19M15 6h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-3.19">
                                                        </path>
                                                        <line x1="23" y1="13" x2="23" y2="11"></line>
                                                        <polyline points="11 6 7 12 13 12 9 18"></polyline>
                                                    </svg>
                                                    Дней неактива (LVL):
                                                </h6>
                                                <?php if ($edituser && !$kickedUser): ?>
                                                    <a class="text-secondary" type="button"
                                                        data-bs-toggle="modal" data-bs-target="#editInactive2"
                                                        id="infoNowInactiveUser">Загрузка...</a>
                                                    <div class="modal fade" id="editInactive2" tabindex="-1"
                                                        aria-hidden="true">
                                                        <div class="modal-dialog modal modal-dialog-centered">
                                                            <div class="modal-content bg-dark">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title text-white">Изменение неактива на
                                                                        данном лвле</h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body text-white">
                                                                    <p>Введите количество дней доп.неактива для
                                                                        <b><?php echo $user->nick; ?></b>
                                                                    </p>
                                                                    <p>Данное количество увлечивает неактив <b>на данном
                                                                            уровне</b></p>
                                                                    <input type="text" class="form-control"
                                                                        id="inactiveInput2"
                                                                        placeholder="<?php echo $user->nowinactive; ?>"
                                                                        value="<?php echo $user->nowinactive; ?>">
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light"
                                                                        data-bs-dismiss="modal">Закрыть</button>
                                                                    <button type="button"
                                                                        onclick="changeuser(2, `inactiveInput2`)"
                                                                        class="btn btn-dark"
                                                                        data-bs-dismiss="modal">Подтвердить</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-secondary" id="infoNowInactiveUser">Загрузка...</span>
                                                <?php endif; ?>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                <h6 class="mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-battery me-2">
                                                        <rect x="1" y="6" width="18" height="12" rx="2" ry="2"></rect>
                                                        <line x1="23" y1="13" x2="23" y2="11"></line>
                                                    </svg>
                                                    Дней неактива (всего):
                                                </h6>
                                                <?php if ($edituser && !$kickedUser): ?>
                                                    <a class="text-secondary" type="button"
                                                        data-bs-toggle="modal" data-bs-target="#editInactive1"
                                                        id="infoAllInactiveUser">Загрузка...</a>
                                                    <div class="modal fade" id="editInactive1" tabindex="-1"
                                                        aria-hidden="true">
                                                        <div class="modal-dialog modal modal-dialog-centered">
                                                            <div class="modal-content bg-dark">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title text-white">Изменение неактива за
                                                                        всё время</h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body text-white">
                                                                    <p>Введите количество дней доп.неактива для
                                                                        <b><?php echo $user->nick; ?></b>
                                                                    </p>
                                                                    <p>Данное количество увлечивает <b>общий</b> неактив</p>
                                                                    <input type="text" class="form-control"
                                                                        id="inactiveInput1"
                                                                        placeholder="<?php echo $user->inactive; ?>"
                                                                        value="<?php echo $user->inactive; ?>">
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light"
                                                                        data-bs-dismiss="modal">Закрыть</button>
                                                                    <button type="button"
                                                                        onclick="changeuser(1, `inactiveInput1`)"
                                                                        class="btn btn-dark"
                                                                        data-bs-dismiss="modal">Подтвердить</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-secondary" id="infoAllInactiveUser">Загрузка...</span>
                                                <?php endif; ?>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                <h6 class="mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-align-center me-2">
                                                        <line x1="18" y1="10" x2="6" y2="10"></line>
                                                        <line x1="21" y1="6" x2="3" y2="6"></line>
                                                        <line x1="21" y1="14" x2="3" y2="14"></line>
                                                        <line x1="18" y1="18" x2="6" y2="18"></line>
                                                    </svg>
                                                    Игровая репутация:
                                                </h6>
                                                <span class="text-secondary" id="infoReputationUser">Загрузка...</span>
                                            </li>
                                            <?php if (!$kickedUser): ?>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                    <h6 class="mb-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="feather feather-framer me-2">
                                                            <path d="M5 16V9h14V2H5l14 14h-7m-7 0l7 7v-7m-7 0h7"></path>
                                                        </svg>
                                                        Дней до повышения:
                                                    </h6>
                                                    <?php if ($edituser): ?>
                                                        <a class="text-secondary" type="button"
                                                            data-bs-toggle="modal" data-bs-target="#editInactive3"
                                                            id="infoDayToUpUser">Загрузка...</a>
                                                        <div class="modal fade" id="editInactive3" tabindex="-1"
                                                            aria-hidden="true">
                                                            <div class="modal-dialog modal modal-dialog-centered">
                                                                <div class="modal-content bg-dark">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title text-white">Изменение дней до
                                                                            повышения</h5>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body text-white">
                                                                        <p>Введите количество дней до повышения для
                                                                            <b><?php echo $user->nick; ?></b>
                                                                        </p>
                                                                        <input type="text" class="form-control"
                                                                            id="inactiveInput3"
                                                                            placeholder="<?php echo $user->days; ?>">
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-light"
                                                                            data-bs-dismiss="modal">Закрыть</button>
                                                                        <button type="button"
                                                                            onclick="changeuser(3, 'inactiveInput3')"
                                                                            class="btn btn-dark"
                                                                            data-bs-dismiss="modal">Подтвердить</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-secondary" id="infoDayToUpUser">Загрузка...</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endif; ?>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                <h6 class="mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-user-plus me-2 icon-inline">
                                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                        <circle cx="8.5" cy="7" r="4"></circle>
                                                        <line x1="20" y1="8" x2="20" y2="14"></line>
                                                        <line x1="23" y1="11" x2="17" y2="11"></line>
                                                    </svg>
                                                    Назначен(а):
                                                </h6>
                                                <span class="text-secondary" id="infoSetAdminUser">Загрузка...</span>
                                            </li>
                                            <li
                                                class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                                <h6 class="mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-trending-up me-2 icon-inline">
                                                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                                        <polyline points="17 6 23 6 23 12"></polyline>
                                                    </svg>
                                                    Повышен(а):
                                                </h6>
                                                <span class="text-secondary" id="infoUpAdminUser">Загрузка...</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <?php if ($myuser->access >= 4 && !$kickedUser): ?>
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="d-flex align-items-center mb-2">Передача жалобы</h5>
                                            <small class="text-secondary">Вставьте ссылку на жалобу для
                                                <?php echo $user->nick; ?>. Администратор получит уведомление в VK</small>
                                            <hr class="my-3" />
                                            <form class="row g-2">
                                                <input type="text" class="form-control" id="sendForum"
                                                    placeholder="https://forum.arizona-rp.com/threads/123456"
                                                    oninput="toggleButton()">
                                                <label for="commentForum" class="form-label">Комментарий (необязательно)</label>
                                                <textarea class="form-control" id="commentForum" rows="3" placeholder="Дополнительная информация к жалобе..."></textarea>
                                                <input type="button" id="sendButton" onclick="sendThread()"
                                                    class="btn btn-primary px-3" value="Отправить" disabled />
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="number" id="userVKID" value="<?php echo $user->vkid; ?>" hidden>
                            <?php if ($edituser): ?>
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="form-body">
                                                <?php
                                                $archivefind = R::findOne('admarchive', 'vk = ? ORDER BY id DESC', [$user->vkid]);
                                                $archive = false;
                                                $kickAdminNick = null;

                                                if ($user->accept == 0) {
                                                    if ($archivefind) {
                                                        $findAdminKick = R::findone('users', 'vkid = ?', [$archivefind->admin]);
                                                        $lvlkick = $archivefind->lvl;
                                                        $kickAdminNick = ($findAdminKick ? $findAdminKick->nick : null);
                                                        $birth = (preg_match("/Возраст: (\d+)/", $archivefind->archive, $matched) ? $matched[1] : "Неизвестно");

                                                        $date = round((time() - $archivefind->ldate) / 86400);
                                                        $reason = $archivefind->reason;
                                                        $archive = true;
                                                    }
                                                }

                                                if ($kickedUser):
                                                ?>
                                                    <div class="alert alert-warning border-0 bg-warning alert-dismissible fade show py-2"
                                                        id="userKicked">
                                                        <div class="d-flex align-items-center">
                                                            <div class="font-35 text-dark"><i class='bx bx-info-circle'></i>
                                                            </div>
                                                            <div class="ms-3">
                                                                <h6 class="mb-0 text-dark">Администратор был
                                                                    снят<?php echo ($kickAdminNick ? " администратором {$kickAdminNick}" : ''); ?>!
                                                                </h6>
                                                                <?php if ($archive): ?>
                                                                    <div class="text-dark">
                                                                        Причина снятия: <b><?php echo $reason; ?></b><br>
                                                                        Уровень при снятии: <b><?php echo $lvlkick; ?></b><br>
                                                                        Дней с момента снятия: <b><?php echo $date; ?></b><br>
                                                                        Возраст на момент снятия: <b><?php echo $birth; ?></b>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <p>Не найден архив снятого администратора в Базе Данных!</p>
                                                                <?php endif; ?>
                                                                <div class="text-dark" id="userKicked"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <form class="row g-3">
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Никнейм</label>
                                                        <input type="text" class="form-control" id="inputNick"
                                                            placeholder="Sam_Mason" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputSelectCountry" class="form-label">Сервер</label>
                                                        <select class="form-select" id="inputServer"
                                                            aria-label="Default select example" <?php echo getDisabledAttr($myuser->access < 8 || $kickedUser); ?>>
                                                            <?php
                                                            $servers = R::findall('serverlist', 'id < 1000');
                                                            foreach ($servers as $key => $value) {
                                                                if ($value['id'] != $user->server) {
                                                                    echo "<option value=\"{$value['id']}\" " . ($value['connected'] == 0 ? "disabled" : "") . ">{$value['servername']} [№{$value['id']}]</option>";
                                                                } else {
                                                                    echo "<option value=\"{$value['id']}\" selected>{$value['servername']} [№{$value['id']}]</option>";
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputEmailAddress" class="form-label">Уровень</label>
                                                        <input type="number" class="form-control" id="inputLvl" min="1"
                                                            max="7" placeholder="1 - 7" <?php echo getDisabledAttr($kickedUser); ?>>
                                                        <input type="number" id="userID" value="<?php echo $user->id; ?>"
                                                            hidden>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputChoosePassword"
                                                            class="form-label">Должность</label>
                                                        <input type="text" class="form-control" id="inputPost"
                                                            placeholder="Администратор" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Префикс (Тег)</label>
                                                        <input type="text" class="form-control" id="inputPrefix"
                                                            placeholder="// Mason" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Форумный
                                                            аккаунт</label>
                                                        <input type="text" class="form-control" id="inputForum"
                                                            placeholder="forum.arizona-rp.com/conor" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Discord</label>
                                                        <input type="text" class="form-control" id="inputDiscord"
                                                            placeholder="mason" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Тип
                                                            назначения</label>
                                                        <select class="form-select" id="inputType" <?php echo getDisabledAttr($kickedUser); ?>>
                                                            <?php
                                                            foreach ($destinationTypes as $key => $value) {
                                                                $selected = ($value == $user->destination) ? 'selected' : '';
                                                                echo "<option value=\"{$key}\" {$selected}>{$value}</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Дата
                                                            назначения</label>
                                                        <input class="result form-control" id="inputAssign" type="date"
                                                            <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Дата
                                                            повышения</label>
                                                        <input class="result form-control" id="inputUpdate" type="date"
                                                            <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Доп. Репутация</label>
                                                        <input type="text" class="form-control" id="inputPlusrep"
                                                            placeholder="(+/-) кол-во" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputSelectCountry" class="form-label">Доступ</label>
                                                        <select class="form-select" id="inputAccess" <?php echo getDisabledAttr($kickedUser || $myuser->access <= $user->access); ?>>
                                                            <?php
                                                            foreach ($accessLevels as $key => $value) {
                                                                $canEdit = ($myuser->access == 8) ||
                                                                    ($key < 6 && $user->access < $myuser->access && $key < $myuser->access);

                                                                $selected = ($key == $user->access) ? 'selected' : '';
                                                                $disabled = $canEdit ? '' : 'disabled';

                                                                if (!$canEdit && $key == 8) break;

                                                                echo "<option value=\"{$key}\" {$selected} {$disabled}>{$value}</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <?php if (!$kickedUser): ?>
                                                        <div class="col-sm-3">
                                                            <label for="inputFirstName" class="form-label"><?php echo $warningsText; ?></label>
                                                            <input type="text" class="form-control" id="inputReprimand"
                                                                placeholder="(+/-) 0-3">
                                                        </div>
                                                        <div class="col-sm-3">
                                                            <label for="inputFirstName" class="form-label">Причина (<?php echo $warningsReasonText; ?>)</label>
                                                            <input type="text" class="form-control" id="inputReprimandReason"
                                                                placeholder="Причина">
                                                        </div>
                                                        <div class="col-sm-3">
                                                            <label for="inputFirstName" class="form-label"><?php echo $strikesText; ?></label>
                                                            <input type="text" class="form-control" id="inputWarn"
                                                                placeholder="(+/-) 0-2">
                                                        </div>
                                                        <div class="col-sm-3">
                                                            <label for="inputFirstName" class="form-label">Причина (<?php echo $strikesReasonText; ?>)</label>
                                                            <input type="text" class="form-control" id="inputWarnReason"
                                                                placeholder="Причина">
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Реальное имя</label>
                                                        <input type="text" class="form-control" id="inputRealname"
                                                            placeholder="Иван" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Страна</label>
                                                        <input type="text" class="form-control" id="inputCountry"
                                                            placeholder="Россия" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Город</label>
                                                        <input type="text" class="form-control" id="inputCity"
                                                            placeholder="Москва" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <label for="inputFirstName" class="form-label">Дата рождения</label>
                                                        <input type="date" class="form-control" id="inputBirth" <?php echo getDisabledAttr($kickedUser); ?>>
                                                    </div>
                                                    <div class="col-sm-12">
                                                        <label for="inputFirstName" class="form-label">Заметки (видны всем)</label>
                                                        <textarea
                                                            class="form-control"
                                                            id="inputNote"
                                                            style="height: 150px"
                                                            placeholder="Пусто"
                                                            <?php echo getDisabledAttr($kickedUser); ?>></textarea>
                                                    </div>
                                                    <center>
                                                        <div class="col-sm-9 text-secondary">
                                                            <?php if (!$kickedUser): ?>
                                                                <div class="row row-cols-auto g-2 justify-content-center">
                                                                    <div class="col">
                                                                        <button type="button" onclick="updateuser()"
                                                                            class="btn btn-primary px-5">Сохранить
                                                                            изменения</button>
                                                                    </div>
                                                                    <?php if ($myuser->access >= 6 && $myuser->access > $user->access): ?>
                                                                        <div class="col">
                                                                            <button type="button"
                                                                                class="btn btn-outline-danger px-5"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#removeAdmin">Снять
                                                                                администратора</button>
                                                                        </div>
                                                                        <div class="modal fade" id="removeAdmin" tabindex="-1"
                                                                            aria-hidden="true">
                                                                            <div class="modal-dialog modal modal-dialog-centered">
                                                                                <div class="modal-content bg-dark">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title text-white">Снятие
                                                                                            администратора</h5>
                                                                                        <button type="button" class="btn-close"
                                                                                            data-bs-dismiss="modal"
                                                                                            aria-label="Close"></button>
                                                                                    </div>
                                                                                    <div class="modal-body text-white">
                                                                                        <div id="firstKick">
                                                                                            <p>Введите причину снятия администратора
                                                                                                <b><?php echo $user->nick; ?></b>
                                                                                            </p>
                                                                                            <input type="text" class="form-control"
                                                                                                id="kickReason"
                                                                                                placeholder="Продажа виртуальной валюты">
                                                                                            <br>
                                                                                            <select class="form-select"
                                                                                                id="kickHide">
                                                                                                <option value="0" selected>Причина
                                                                                                    видна всем</option>
                                                                                                <option value="1">Причина скрыта
                                                                                                </option>
                                                                                            </select>
                                                                                        </div>
                                                                                        <div class="col-sm-12" id="admArchive"
                                                                                            hidden>
                                                                                            <label for="archiveText"
                                                                                                class="form-label">Архив
                                                                                                администратора</label>
                                                                                            <div class="row row-cols-auto g-2">
                                                                                                <textarea type="text"
                                                                                                    class="form-control"
                                                                                                    style="height:120px"
                                                                                                    id="archiveText"
                                                                                                    placeholder="Архив администратора тра-ля-ля"></textarea>
                                                                                                <div class="col-sm-12"><input
                                                                                                        type="button" id="copyText"
                                                                                                        class="btn btn-outline-warning px-5"
                                                                                                        value="Скопировать в буфер обмена" />
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="modal-footer" id="btnList">
                                                                                        <button type="button" class="btn btn-light"
                                                                                            data-bs-dismiss="modal">Закрыть</button>
                                                                                        <button type="button"
                                                                                            onclick="kickAdmin(<?= $user->vkid; ?>)"
                                                                                            id="acceptKick"
                                                                                            class="btn btn-dark">Подтвердить</button>
                                                                                        <button type="button"
                                                                                            onclick="closeArchive(<?= $user->vkid; ?>)"
                                                                                            id="acceptSendArchive"
                                                                                            class="btn btn-danger" hidden>Архив
                                                                                            заполнен</button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>                                        
                            
                            <?php echo ($edituser ? "" : '<div class="col-lg-8">'); ?>
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3">Последние действия</h5>
                                    <table id="logsTable" class="table table-striped table-bordered"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th style="width: 80px;">Дата</th>
                                                <th style="width: auto; word-wrap: break-word;">Действие</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $log = R::findall('logs', "vkuser = ? ORDER BY time DESC", [$user->vkid]);
                                            foreach ($log as $key => $value) {
                                                $date = date('d.m.Y H:i:s', $value['time']);
                                                $timestamp = $value['time'];
                                                echo "<tr><td data-sort=\"{$timestamp}\">{$date}</td><td>{$value['log']}</td></tr>";
                                            }
                                            if (empty($log)) {
                                                echo "<tr><td colspan='2'>Нет данных</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php echo ($edituser ? "" : '</div>'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php footer(); ?>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <script src="assets/plugins/datatable/js/jquery.dataTables.min.js"></script>

    <script>
        function toggleButton() {
            const input = document.getElementById('sendForum').value;
            const button = document.getElementById('sendButton');
            button.disabled = input.trim() === '';
        }

        function sendThread() {
            const id = document.getElementById('userID').value;
            const input = document.getElementById('sendForum');
            const comment = document.getElementById('commentForum');
            const button = document.getElementById('sendButton');

            $.post('/api/user/sendvk.php', {
                'id': id,
                'url': input.value,
                'comment': comment.value
            }).done(function(data) {
                if (data.error) {
                    round_warning_noti(data.message);
                } else {
                    round_success_noti('Жалоба успешно передана администратору!');
                    button.disabled = true;
                    input.value = "";
                    comment.value = "";
                }
            }).catch(function(data) {
                round_error_noti(`Ошибка на стороне сервера. Попробуйте позже`);
                console.log(data);
            });
        }
    </script>
    <script>
        function closeArchive(vk_id) {
            $.post('/api/archives/update.php', {
                    vk: vk_id,
                    status: 1
                })
                .done(res => {
                    round_success_noti(res.data.answer);
                    $('#removeAdmin').modal('toggle');
                })
                .fail(err => {
                    const errorMsg = err.responseJSON?.error || `Ошибка от сервера #${err.status}<br>Попробуйте позже`;
                    const notificationType = typeof err.responseJSON?.error === "string" ?
                        round_warning_noti : round_error_noti;
                    notificationType(errorMsg);
                    console.error(err);
                });
        }

        function updatePage() {
            $.post('/api/user/getInfo.php', {
                'vkid': document.getElementById('userVKID').value
            }).done(function(data) {
                if (data.error) {
                    round_warning_noti(data.message);
                    return;
                }

                const info = data.message;
                const access = info.myaccess >= 6 || (info.myaccess >= 4 && info.access < info.myaccess);

                document.getElementById('infoVKUser').href = `https://vk.ru/id${info.vk}`;
                document.getElementById('infoForumUser').href = `https://forum.${info.forum}`;

                $('#siteTitle').html(`${info.nick} - Arizona Admins`);
                $('#infoNickNameUser').html(`${info.nick}`);
                $('#infoPostUser').html(`${info.post}`);
                $('#infoServerUser').html(info.servername);
                $('#infoIDProfileUser').html(`${info.nick} [ID: ${info.id}]`);
                $('#infoLVLUser').html(info.lvlname);
                $('#infoReprimandsUser').html(info.vig);
                $('#infoWarnsUser').html(info.warn);
                $('#infoJBUser').html(info.jb);
                $('#infoReputationUser').html(info.gamerep);
                $('#infoSetAdminUser').html(`${info.daySetNormal} (${info.daySetCount} д.)`);
                $('#infoUpAdminUser').html(`${info.dayUpNormal} (${info.dayUpCount} д.)`);
                $('#infoScoreUser').html(access ? `${info['scores']} ${info['access'] > 0 ? `<i class='bx bx-pencil text-warning'></i>` : ``}` : `${info['scores']}`);
                
                if (info.inStatus) {
                    $('#infoInactiveUser').html(`В неактиве до ${info.inStatus}`).show();
                }

                const editIcon = info.access > 0 ? `<i class='bx bx-pencil text-warning'></i>` : '';
                $('#infoAllInactiveUser').html(access ? `${info.inactiveAll} (D: ${info.inactive}) ${editIcon}` : info.inactiveAll);
                $('#infoNowInactiveUser').html(access ? `${info.inactiveNow} (D: ${info.nowinactive}) ${editIcon}` : info.inactiveNow);
                $('#infoDayToUpUser').html(access ? `${info.daysUpFirst} (All: ${info.daysUpAll}) ${editIcon}` : `${info.daysUpFirst} / ${info.daysUpAll}`);

                $('[data-bs-toggle="popover"]').popover();
                $('[data-bs-toggle="tooltip"]').tooltip();

                if (access) {
                    if (info.access > 0) {
                        document.getElementById('inactiveInput1').value = info.inactive;
                        document.getElementById('inactiveInput2').value = info.nowinactive;
                        document.getElementById('inactiveInput3').value = info.daysUpAll;
                    }

                    const fields = {
                        'inputNick': info.nick,
                        'inputLvl': info.lvl,
                        'inputPost': info.post,
                        'inputPrefix': info.tag,
                        'inputForum': info.forumValue,
                        'inputDiscord': info.discord,
                        'inputAssign': info.daySetValue,
                        'inputUpdate': info.dayUpValue,
                        'inputPlusrep': info.plusrep,
                        'inputReprimand': info.vig,
                        'inputWarn': info.warn,
                        'inputCountry': info.country,
                        'inputRealname': info.realname,
                        'inputCity': info.city,
                        'inputBirth': info.birthValue,
                        'inputNote': info.note,
                        'inputReprimandReason': '',
                        'inputWarnReason': ''
                    };

                    Object.entries(fields).forEach(([id, value]) => {
                        document.getElementById(id).value = value;
                    });
                }
            }).catch(function(data) {
                round_error_noti(`Ошибка на стороне сервера #${data.status}<br>Попробуйте позже`);
                console.log(data);
            });
        }
        updatePage();
    </script>
    <script>
        $(document).ready(function() {
            $('#logsTable').DataTable({
                order: [
                    [0, 'desc']
                ],
                columnDefs: [
                    {
                        targets: 0,
                        type: 'num'
                    }
                ]
            });
        });
    </script>
    <script src="assets/js/user.js"></script>
    <script>
        document.getElementById("copyText").onclick = function() {
            document.getElementById("archiveText").select();
            document.execCommand("copy");
        }
    </script>
    <script src="assets/js/app.js"></script>
    <script>
        $(function() {
            $('[data-bs-toggle="popover"]').popover();
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
    <script src="assets/plugins/notifications/js/lobibox.min.js"></script>
    <script src="assets/plugins/notifications/js/notifications.min.js"></script>
    <script src="assets/plugins/notifications/js/notification-custom-script.js"></script>
</body>

</html>