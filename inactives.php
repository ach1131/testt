<?php
include_once "config.php";
[$user, $server] = pageAccess(1);
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head>
    <?php head("Неактивы"); ?>
</head>

<body>
    <div class="wrapper">
        <?php sidebar($user->access); ?>
        <?php topbar($user); ?>
        <div class="page-wrapper">
            <div class="page-content">
                <div class="row">
                    <div class="col-lg-3 flex-column">
                        <div class="card radius-10 flex-grow-1">
                            <div class="card-body d-flex flex-column">
                                <h5 class="mb-2">Запросить неактив</h5>
                                <p class="text-secondary mb-0">Заполните поля ниже, чтобы создать заявку на запрос неактива. Заявка будет рассмотрена руководством сервера</p>

                                <hr>

                                <div class="col-12 mb-3">
                                    <label for="selectUser" class="form-label">Никнейм администратора</label>
                                    <select class="form-select" id="selectUser" <?= $user->access < 4 ? "disabled" : "" ?> aria-label="Default select example">
                                        <?php
                                        $options = "";
                                        if ($user->access < 4) {
                                            $options .= "<option value='{$user->id}' selected>{$user->nick} [{$user->lvl} LVL] - {$user->post}</option>";
                                        } else {
                                            $min_access = $user->access >= 6 ? $user->access : $user->access - 1;
                                            foreach (R::findAll('users', 'id = ? OR (server = ? AND access <= ? AND access > 0) ORDER BY lvl DESC', [$user->id, $user->server, $min_access]) as $u) {
                                                $selected = $user->id == $u->id ? "selected" : "";
                                                $options .= "<option value='{$u->id}' {$selected}>{$u->nick} [{$u->lvl} LVL] - {$u->post}</option>";
                                            }
                                        }
                                        echo $options;
                                        ?>
                                    </select>
                                </div>

                                <div class="col-sm-12 mb-3">
                                    <label for="inputStartDate" class="form-label">Дата начала (включительно)</label>
                                    <input type="date" id="inputStartDate" class="form-control" value="<?= date('Y-m-d'); ?>">
                                </div>

                                <div class="col-sm-12 mb-3">
                                    <label for="inputEndDate" class="form-label">Дата окончания (включительно)</label>
                                    <input type="date" id="inputEndDate" class="form-control" value="<?= date('Y-m-d', strtotime('+1 days')); ?>">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="inputReason" class="form-label">Причина</label>
                                    <input class="result form-control" id="inputReason" type="text" placeholder="Плохое самочуствие">
                                </div>

                                <button class="btn btn-primary" id="sentButton" onclick="sentApplication()">Отправить заявку</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div class="card radius-10">
                            <div class="card-body">
                                <h5 class="mb-0">Заявки на неактив сервера <?= "{$server->servername} №{$user->server}"; ?></h5>
                                <hr>
                                <div id="inactivesTable">
                                    <div class="spinner-grow spinner-grow-sm me-2" role="status"></div>
                                    <span>Получение списка неактивов...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php footer(); ?>
        </div>
    </div>

    <?php scripts(); ?>

    <script>
        function sentApplication() {
            const btn = document.getElementById('sentButton');
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

            const user_id = document.getElementById('selectUser').value;
            const date_start = document.getElementById('inputStartDate').value;
            const date_end = document.getElementById('inputEndDate').value;
            const reason = document.getElementById('inputReason').value;

            $.post('/api/inactives/create.php', {
                    user_id,
                    date_start,
                    date_end,
                    reason
                })
                .done(res => {
                    round_success_noti(res.data.answer);
                    $('#inputReason').val("");
                    updateApplications();
                })
                .fail(err => {
                    if (typeof err.responseJSON?.error === "string") {
                        round_warning_noti(err.responseJSON.error);
                    } else {
                        round_error_noti(`Ошибка от сервера #${err.status}<br>Попробуйте позже`);
                        console.error(err);
                    }
                })
                .always(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }

        function updateApplications() {
            $.get('/api/inactives/get_list.php')
                .done(res => {
                    const tableRows = res.data.rows.map(row => `
                        <tr>
                            <td>${row.id}</td>
                            <td>
                                <a href="/user.php?id=${row.user_id}" target="_blank" style="color: #99bbed">
                                    ${row.nick}
                                </a>
                            </td>
                            <td style="text-wrap: initial">${row.reason}</td>
                            <td>${row.date_start}</td>
                            <td>${row.date_end}</td>
                            <td>${row.days_count}</td>
                            <td>${row.status_info}${row.status != 0 ? (row.admin ? (' / ' + row.admin) : '') : ''}</td>
                            ${res.data.canUpdate ? `
                                <td>
                                    <button class="badge bg-success action-button" onclick="updateInactiveStatus(${row.uid}, 1, this)">ОДОБРИТЬ</button>
									<button class="badge bg-danger action-button" onclick="updateInactiveStatus(${row.uid}, 2, this)">ОТКАЗАТЬ</button>
                                </td>
                            ` : ''}
                        </tr>
                    `).join('');

                    document.getElementById('inactivesTable').innerHTML = `
                        <div class="table-responsive">
                            <table class="table table-striped" id="inactivesList">
                                <thead class="table-light">
                                    <tr>
                                        <th>№</th><th>Никнейм</th><th>Причина</th><th>Начало</th><th>Окончание</th><th>Дней</th><th>Статус</th>
                                        ${res.data.canUpdate ? '<th>Управление</th>' : ''}
                                    </tr>
                                </thead>
                                <tbody>${tableRows}</tbody>
                            </table>
                        </div>
                    `;

                    $('#inactivesList').DataTable({
                        lengthMenu: [
                            [25, 50, 100, -1],
                            [25, 50, 100, 'Все']
                        ],
                        order: [
                            [0, 'desc']
                        ],
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
        }
        updateApplications();

        function updateInactiveStatus(id, status, btn) {
            let row = btn.closest('tr');
            let actionCell = row.querySelector("td:last-child");
            let statusCell = row.querySelector("td:nth-last-child(2)");
            let buttons = actionCell.innerHTML;

            actionCell.innerHTML = `<span class="status-spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

            $.post('/api/inactives/update.php', {
                    id,
                    status
                })
                .done(res => {
                    round_success_noti(res.data.answer);
                    statusCell.innerHTML = `${res.data.status_info} / ${res.data.admin}`;
                })
                .fail(err => {
                    if (typeof err.responseJSON?.error === "string") {
                        round_warning_noti(err.responseJSON.error);
                    } else {
                        round_error_noti(`Ошибка от сервера #${err.status}<br>Попробуйте позже`);
                        console.error(err);
                    }
                })
                .always(() => {
                    actionCell.innerHTML = buttons;
                });
        }
    </script>
</body>

</html>