<?php
include_once "config.php";
[$user, $server] = pageAccess(5);
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head>
	<?php head("Архивы"); ?>
</head>

<body>
	<div class="wrapper">
		<?php sidebar($user->access); ?>
		<?php topbar($user); ?>
		<div class="page-wrapper">
			<div class="page-content">
				<div class="row">
					<div class="card radius-10">
						<div class="card-body">
							<div class="d-flex align-items-center">
								<h5 class="mb-0">Архивы администрации сервера <?= "{$server->servername} №{$user->server}"; ?></h5>
							</div>
							<hr>
							<div id="archivesTable">
								<div class="spinner-grow spinner-grow-sm me-2" role="status"></div>
								<span>Получение списка архивов...</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php footer(); ?>
		</div>

		<?php scripts(); ?>

		<div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<div class="modal-content bg-dark">
					<div class="modal-header">
						<h5 class="modal-title text-white" id="modalTitle"></h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body text-white">
						<textarea class="form-control" style="height:255px;" id="archiveText" readonly></textarea>
					</div>
					<div class="modal-footer">
						<button type="button" id="copyText" class="btn btn-outline-secondary">Скопировать</button>
						<?php if ($user->server >= 500): ?>
							<a target="_blank" class="btn btn-outline-success" href="https://forum.rodina-rp.com/forums/96/post-thread">Перейти в раздел</a>
						<?php else: ?>
							<a target="_blank" class="btn btn-outline-success" href="https://forum.arizona-rp.com/forums/467/post-thread">Перейти в раздел</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<script>
			function updateArchives() {
				$.get('/api/archives/get_list.php')
					.done(res => {
						const tableRows = res.data.map(row => `
							<tr>
								<td>${row.id}</td>
								<td>
									<a href="/user.php?id=${row.user_id}" target="_blank" style="color: #99bbed">
										${row.nick}
									</a>
								</td>
								<td>${row.lvl}</td>
								<td style="white-space: normal">${row.reason}</td>
								<td>${row.time}</td>
								<td>
									<a class="text-secondary" href="/user.php?id=${row.admin.id}" target="_blank">
										${row.admin.nick}
									</a>
								</td>
								<td>${row.status_info}</td>
								<td>
									<button class="badge bg-secondary action-button" onclick="openArchive(${row.uid})">ОТКРЫТЬ АРХИВ</button>
									<button class="badge bg-success action-button" onclick="updateArchiveStatus(${row.uid}, 1)">ЗАНЕСЁН</button>
									<button class="badge bg-danger action-button" onclick="updateArchiveStatus(${row.uid}, 2)">УДАЛЁН</button>
								</td>
							</tr>
						`).join('');

						document.getElementById('archivesTable').innerHTML = `
							<div class="table-responsive">
								<table class="table table-striped" id="archivesList">
									<thead class="table-light">
										<tr>
											<th>№</th>
											<th>Никнейм</th>
											<th>Уровень</th>
											<th style="white-space: normal">Причина снятия</th>
											<th>Дата снятия</th>
											<th>Кем снят</th>
											<th>Статус</th>
											<th>Действия</th>
										</tr>
									</thead>
									<tbody>
										${tableRows}
									</tbody>
								</table>
							</div>
						`;

						$('#archivesList').DataTable({
							lengthMenu: [
								[25, 50, 100, -1],
								[25, 50, 100, 'Все']
							],
							order: [
								[0, 'desc']
							]
						});
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
			updateArchives();

			function openArchive(uid) {
				$.get(`/api/archives/get_archive.php`, {
						uid
					})
					.done(res => {
						if (!res.error) {
							$('#modalTitle').text(`Архив на ${res.data.nick} [${res.data.lvl} LVL]`);
							$('#archiveText').val(res.data.archive);
							$('#archiveModal').modal('show');
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

			function updateArchiveStatus(id, status) {
				$.post('/api/archives/update.php', {
						id,
						status
					})
					.done(res => {
						round_success_noti(res.data.answer);
						updateArchives();
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

			document.getElementById("copyText").addEventListener("click", () => {
				let text = document.getElementById("archiveText").value;
				navigator.clipboard.writeText(text)
					.then(() => {
						round_success_noti("Архив скопирован в буфер обмена");
					})
					.catch(err => {
						round_error_noti("Не удалось скопировать архив")
						console.error(err);
					});
			});
		</script>
</body>

</html>