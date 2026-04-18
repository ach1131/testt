<?php
include_once "config.php";
[$user, $server] = pageAccess(5);
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head>
	<?php head("Форма передачи ЖБ"); ?>
</head>

<body>
	<div class="wrapper">
		<?php sidebar($user->access); ?>
		<?php topbar($user); ?>
		<div class="page-wrapper">
			<div class="page-content">
				<div class="row">
					<center>
						<div class="col-lg-5">
							<div class="card radius-5">
								<div class="card-body">
									<div class="d-grid mb-3">
										<a class="btn shadow-sm btn-white">
											<span class="d-flex justify-content-center align-items-center">
												<img class="me-2" src="assets/images/logo.svg" width="20">
												<span>Изменение формы передачи жалобы сервера <?= $server->servername; ?></span>
											</span>
										</a>
									</div>

									<div class="col-sm mb-3" id="forumList">
										<textarea type="text" class="form-control" id="inputTemplate" style="height: 350px"><?= $server->jbinfo; ?></textarea>
									</div>

									<p class="text-secondary" style="text-align: left; margin-bottom: 0px">➡️ Используйте тег <b>{nick}</b>, чтобы указать никнейм администратора</p>
									<p class="text-secondary" style="text-align: left; margin-bottom: 0px">➡️ Используйте тег <b>{vkid}</b>, чтобы указать ID Вконтакте администратора</p>
									<p class="text-secondary" style="text-align: left; margin-bottom: 0px">➡️ Используйте тег <b>{url}</b>, чтобы указать ссылку на ЖБ</p>
									<p class="text-secondary" style="text-align: left; margin-bottom: 0px">➡️ Используйте тег <b>{mynick}</b>, чтобы указать свой никнейм</p>
									<p class="text-secondary" style="text-align: left; margin-bottom: 0px">➡️ Используйте тег <b>{myvk}</b>, чтобы указать свой ID Вконтакте</p>
									<p class="text-secondary" style="text-align: left; margin-bottom: 0px">➡️ Используйте тег <b>{comment}</b>, чтобы указать комментарий к жалобе</p>
									<p class="text-secondary" id="charsLimit" style="text-align: left; margin-bottom: 0px">⚠️ Ограничение по символам: <b>4096</b></p>

									<div class="d-grid mt-3">
										<input type="button" onclick="editTemplate()" class="btn btn-primary px-4" value="Сохранить" id="saveButton" />
									</div>
								</div>
							</div>
						</div>
					</center>
				</div>
			</div>
			<?php footer(); ?>
		</div>

		<?php scripts(); ?>

		<script>
			function editTemplate() {
				let template = document.getElementById('inputTemplate').value
				$.post('/api/forumedit/update.php', {
						template
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
					})
			}

			$(document).ready(function() {
				$('#charsLimit')[0]['innerHTML'] = `⚠️ Ограничение по символам: осталось <b>${4096 - $('#inputTemplate')[0]['innerHTML'].length}</b> из <b>4096</b>`;
				$('#inputTemplate').keyup(function(data) {
					let num = data.target.value.length
					if ((4096 - num) > 0) {
						$('#charsLimit')[0]['innerHTML'] = `⚠️ Ограничение по символам: осталось <b>${4096 - num}</b> из <b>4096</b>`;
						$('#saveButton')[0]['disabled'] = false;
						$('#saveButton')[0]['hidden'] = false;
						console.log($('#charsLimit')[0]);
					} else {
						$('#charsLimit')[0]['innerHTML'] = `⚠️ Ограничение по символам <b>ПРЕВЫШЕНО</b>`;
						$('#saveButton')[0]['disabled'] = true;
						$('#saveButton')[0]['hidden'] = true;
						round_warning_noti(`Превышено ограничение по сиволам!`);
					}
				});
			});
		</script>
</body>

</html>