<?php
include_once "config.php";
[$user, $server] = pageAccess(1);
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head>
    <?php head("Онлайн"); ?>
</head>

<body>
    <div class="wrapper">
        <?php sidebar($user->access); ?>
        <?php topbar($user); ?>
        <div class="page-wrapper">
            <div class="page-content">
                <div class="card radius-10">
                    <div class="card-body d-flex align-items-center">
                        <div class="parent-icon me-2">
                            <i class="bx bx-info-circle bx-sm"></i>
                        </div>
                        <span class="mb-0 text-secondary">
                            Данные автоматически обновляются еженедельно в понедельник утром
                        </span>
                    </div>
                </div>

                <div class="row">
                    <div class="card radius-10">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h5 class="mb-0">Онлайн администрации сервера <?= "{$server->servername} [№{$server->id}]"; ?></h5>

                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="radio" name="period_radio" id="period_last_radio" checked>
                                        <label class="form-check-label" for="period_last_radio" id="period_last">Предыдущая неделя</label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="period_radio" id="period_curr_radio" disabled>
                                        <label class="form-check-label" for="period_curr_radio" id="period_curr">Текущая неделя (скоро)</label>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div id="onlines_table">
                                <div class="spinner-grow spinner-grow-sm me-2" role="status"></div>
                                <span>Получение списка онлайнов...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php footer(); ?>
        </div>

        <div class="modal fade" id="detailedModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailedModalTitle"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body" id="detailedModalBody"></div>
                </div>
            </div>
        </div>

        <?php scripts(); ?>

        <script>
            function clock_format(seconds) {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                const pad = (num) => String(num).padStart(2, '0');
                return `${pad(hours)}:${pad(minutes)}:${pad(secs)}`;
            }

            function showDetailedOnline(nick) {
                $.get(`/api/onlines/detailed.php`, {
                        nick,
                        week: "previous"
                    })
                    .done(res => {
                        if (!res.error) {
                            const dayTableHTML = `
                               <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Онлайн</th>
                                            <th>Репорты</th>
                                            <th>Баны</th>
                                            <th>Варны</th>
                                            <th>Муты</th>
                                            <th>Джайлы</th>
                                            <th>Входов</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${res.data.info.map((day) => `
                                            <tr>
                                                <td>${day.date} (${day.weekday})</td>
                                                <td>${clock_format(day.online)}</td>
                                                <td>${day.reports}</td>
                                                <td>${day.bans}</td>
                                                <td>${day.warns}</td>
                                                <td>${day.mutes}</td>
                                                <td>${day.jails}</td>
                                                <td>${day.sessions}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            `;

                            document.getElementById('detailedModalBody').innerHTML = dayTableHTML;
                            $('#detailedModalTitle').text(`${res.data.nick} — активность по дням`);
                            $('#detailedModal').modal('show');
                        }
                    })
                    .fail(err => {
                        if (typeof err.responseJSON?.error === "string") {
                            round_warning_noti(err.responseJSON.error);
                        } else {
                            round_error_noti(`Ошибка от сервера #${err.status}<br>Попробуйте позже`);
                            console.error(err);
                        }
                    });
            }

            $.get(`/api/onlines/get.php`)
                .done(res => {
                    const tableHTML = `
                        <div class="table-responsive">
                            <table class="table table-hover" id="onlinesList">
                                <thead style="color: #aaa">
                                    <tr>
                                        <th>Никнейм</th>
                                        <th>Уровень</th>
                                        <th>Должность</th>
                                        <th>Онлайн</th>
                                        <th>Репорты</th>
                                        <th>Баны</th>
                                        <th>Варны</th>
                                        <th>Муты</th>
                                        <th>Джайлы</th>
                                        <th>Входов</th>
                                        <th>Обновление</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${res.data.rows.map((admin) => `
                                        <tr style="background: ${admin.nick === '<?= $user->nick ?>' ? '#ffc10730' : 'transparent'}" onclick="showDetailedOnline('${admin.nick}')">
                                            <td>
                                                <a href="/user.php?id=${admin.id}" target="_blank" style="color: #99bbed">
                                                    ${admin.nick}
                                                </a>
                                            </td>
                                            <td>${admin.lvl}</td>
                                            <td style="white-space: normal">${admin.post}</td>
                                            <td>${admin.previous_week.online}</td>
                                            <td>${admin.previous_week.reports}</td>
                                            <td>${admin.previous_week.bans}</td>
                                            <td>${admin.previous_week.warns}</td>
                                            <td>${admin.previous_week.mutes}</td>
                                            <td>${admin.previous_week.jails}</td>
                                            <td>${admin.previous_week.sessions}</td>
                                            <td>${admin.previous_week.last_update}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;

                    document.getElementById('onlines_table').innerHTML = tableHTML;

                    $('#onlinesList').DataTable({
                        paging: false,
                        bFilter: false,
                        order: [
                            [1, 'desc']
                        ],
                        columnDefs: [{
                            targets: 3,
                            render: function(data, type, row) {
                                if (type === 'display') {
                                    return clock_format(data);
                                }
                                return data;
                            }
                        }]
                    });
                })
                .fail(err => {
                    if (typeof err.responseJSON?.error === "string") {
                        round_warning_noti(err.responseJSON.error);
                    } else {
                        round_error_noti(`Ошибка загрузки таблицы #${err.status}<br>Попробуйте позже`);
                        console.error(err);
                    }
                });
        </script>
</body>

</html>