<?php
include_once "config.php";
[$user, $server] = pageAccess(4);
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head>
    <?php head("Будущие админы"); ?>
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
                                <h5 class="mb-2">Выдать анкеты для заполнения</h5>
                                <p class="text-secondary mb-0">Укажите ниже в столбик список ссылок на VK пользователей, которым хотите выдать доступ к заполнению <a target="_blank" href="/aform.php">анкеты будущего администратора</a>. Не более 20 ссылок за раз</p>
                                <hr>
                                <textarea class="form-control" id="inputFutureAdd" style="height: 350px" placeholder="https://vk.ru/id1&#10;https://vk.ru/id2&#10;и так далее.."></textarea>
                                <span id="inputFutureWarning" class="text-danger mt-3" hidden>Вы привысили лимит: не более 20 ссылок!</span>
                                <button id="giveFormsButton" onclick="giveForms(this)" class="btn btn-primary mt-3" disabled>Выдать доступ</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div class="card radius-10">
                            <div class="card-body">
                                <h5 class="mb-0">Список анкет будущих администраторов сервера <?= "{$server->servername} №{$user->server}"; ?></h5>
                                <hr>
                                <div id="futuresTable">
                                    <div class="spinner-grow spinner-grow-sm me-2" role="status"></div>
                                    <span>Получение списка анкет...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php footer(); ?>
        </div>
    </div>

    <div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header">
                    <h5 class="modal-title text-white" id="modalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-white">
                    <textarea class="form-control" style="height:300px;" id="formText"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" id="copyText" class="btn btn-outline-secondary">Скопировать</button>
                </div>
            </div>
        </div>
    </div>

    <?php scripts(); ?>

    <script>
        const statusInfo = (status) => {
            switch (status) {
                case 0:
                    return '<span class="text-secondary">⏳ На заполнении</span>';
                case 1:
                    return '<span class="text-warning">⚠️ Заполнена</span>';
                case 2:
                    return '<span class="text-success">✅ Одобрена</span>';
                case 3:
                    return '<span class="text-danger">❌ Отменена</span>';
                default:
                    return '<span class="text-secondary">❔ Неизвестно</span>';
            }
        };

        function giveForms(btn) {
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

            let inputElement = document.getElementById('inputFutureAdd');
            $.post('/api/futures/add.php', {
                    list: inputElement.value
                })
                .done(res => {
                    inputElement.value = res.data.input;
                    let notifyFunction = res.data.warn ? round_warning_noti : round_success_noti;
                    notifyFunction(res.data.answer);
                    updateFuturesList();
                })
                .fail(err => {
                    let errorMsg = err.responseJSON?.error || `Ошибка от сервера #${err.status}<br>Попробуйте позже`;
                    round_error_noti(errorMsg);
                    console.error(err);
                })
                .always(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }

        $(document).ready(function() {
            let inputField = $('#inputFutureAdd');
            let limitWarning = $('#inputFutureWarning');
            let giveButton = $('#giveFormsButton');

            function updateLineLimit() {
                let text = inputField.val().trim();
                let lines = text ? text.split(/\r?\n/).length : 0;
                giveButton.prop('disabled', lines == 0 || lines > 20);
                limitWarning.prop('hidden', lines <= 20);
            }

            updateLineLimit();
            inputField.on('input propertychange change keyup paste', updateLineLimit);
        });

        function updateFuturesList() {
            $.get('/api/futures/get_list.php')
                .done(res => {
                    const tableRows = res.data.rows.map(row => `
                        <tr>
                            <td>${row.id}</td>
                            <td><a style='color: #99bbed' target='_blank' href="https://vk.ru/id${row.user_vk.id}">${row.user_vk.name}</a></td>
                            <td>${row.until_time}</td>
                            <td><a class='text-secondary' target='_blank' href="/user.php?id=${row.admin.id}">${row.admin.nick}</a></td>
                            <td>${statusInfo(row.status)}</td>
                            <td>
                                <button class="badge bg-secondary action-button" onclick="openForm(${row.uid})">СМОТРЕТЬ АНКЕТУ</button>
                                <button class="badge bg-success action-button" onclick="updateFormStatus(${row.uid}, 1, this)">ОДОБРИТЬ</button>
                                <button class="badge bg-danger action-button" onclick="updateFormStatus(${row.uid}, 0, this)">ОТМЕНИТЬ</button>
                            </td>
                        </tr>
                    `).join('');

                    document.getElementById('futuresTable').innerHTML = `
                        <div class="table-responsive">
                            <table class="table table-striped" id="futuresList">
                                <thead class="table-light">
                                    <tr>
                                        <th>№</th>
                                        <th>Пользователь</th>
                                        <th>Истекает</th>
                                        <th>Кем выдана</th>
                                        <th>Статус</th>
                                        <th>Управление</th>
                                    </tr>
                                </thead>
                                <tbody>${tableRows}</tbody>
                            </table>
                        </div>
                    `;

                    $('#futuresList').DataTable({
                        lengthMenu: [
                            [25, 50, 100, -1],
                            [25, 50, 100, 'Все']
                        ],
                        order: [
                            [0, 'desc']
                        ],
                        columnDefs: [{
                            targets: 2,
                            render: function(data, type, row) {
                                if (type === 'display' || type === 'filter') {
                                    const date = new Date(data * 1000);
                                    const d = date.toLocaleDateString('ru-RU');
                                    const t = date.toLocaleTimeString('ru-RU', {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });

                                    let r;
                                    const hr = Math.ceil((data - (Date.now() / 1000)) / 3600);
                                    if (hr < 0) {
                                        r = "<span class='text-secondary'>(Истекла)</span>";
                                    } else if (hr < 3) {
                                        r = `<span class='text-warning'>(${hr} ч.)</span>`;
                                    } else {
                                        r = `<span class='text-secondary'>(${hr} ч.)</span>`;
                                    }

                                    return `${d} в ${t} ${r}`;
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
        }
        updateFuturesList();

        function openForm(uid) {
            $.get(`/api/futures/get_form.php`, {
                    uid
                })
                .done(res => {
                    if (!res.error) {
                        $('#modalTitle').text(`Анкета на ${res.data.name}`);
                        $('#formText').val(res.data.form);
                        $('#formModal').modal('show');
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

        function updateFormStatus(id, status, btn) {
            let row = btn.closest('tr');
            let actionCell = row.querySelector("td:last-child");
            let statusCell = row.querySelector("td:nth-last-child(2)");

            document.querySelectorAll('.action-button').forEach(button => {
                button.disabled = true;
                button.style.opacity = "0.5";
            });

            let buttons = actionCell.innerHTML;
            actionCell.innerHTML = `<span class="status-spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

            $.post('/api/futures/update.php', {
                    id,
                    status
                })
                .done(res => {
                    if (res.data.conf_result[1] > 0) {
                        const action = status == 1 ? "добавлен в" : "кикнут из";
                        res.data.answer += `<br>Пользователь ${action} ${res.data.conf_result[0]} конф.`;
                    }

                    round_success_noti(res.data.answer);
                    statusCell.innerHTML = statusInfo(res.data.new_status);
                })
                .fail(err => {
                    let errorMsg = err.responseJSON?.error || `Ошибка от сервера #${err.status}<br>Попробуйте позже`;
                    round_warning_noti(errorMsg);
                    console.error(err);
                })
                .always(() => {
                    actionCell.innerHTML = buttons;
                    document.querySelectorAll('.action-button').forEach(button => {
                        button.disabled = false;
                        button.style.opacity = "1";
                    });
                });
        }

        document.getElementById("copyText").addEventListener("click", () => {
            let text = document.getElementById("formText").value;
            navigator.clipboard.writeText(text)
                .then(() => {
                    round_success_noti("Анкета скопирована в буфер обмена");
                })
                .catch(err => {
                    round_error_noti("Не удалось скопировать анкету")
                    console.error(err);
                });
        });
    </script>
</body>

</html>