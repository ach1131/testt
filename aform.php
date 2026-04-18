<?php
include_once 'config.php';
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head>
	<?php head("Выдача будущим"); ?>
</head>

<body class="bg-login">
	<div class="wrapper d-flex flex-column min-vh-100">
		<div class="flex-grow-1 d-flex align-items-center justify-content-center py-3">
			<div class="container-fluid px-3">
				<div class="row justify-content-center">
					<div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
						<div class="card shadow-sm border-0 bg-dark">
							<div class="card-body p-3 p-md-4">
								<div class="text-center mb-3">
									<img src="assets/images/logo.svg" width="72" height="72" alt="Logo" class="mb-2">
									<h4 class="text-white my-2">ARIZONA ADMINS</h4>
									<p class="text-secondary mb-1">Форма будущего администратора</p>
								</div>
								<hr>
								<?php if (isset($_SESSION['vk']) and $_SESSION['type'] == 1): ?>
									<?php
									$finduser = R::findOne('admform', 'vkid = ? AND time > ? ORDER BY id DESC', [$_SESSION['vk'], strtotime('-24 hours')]);
									if ($finduser && $finduser->status == 0):
									?>
										<div class="d-grid mb-3">
											<button class="btn shadow-sm py-2" style="background-color: #4680C2; color: #fff;" disabled>
												<span class="d-flex justify-content-center align-items-center">
													<img class="me-2" src="assets/images/vk.svg" width="18" height="18" alt="VK">
													<span class="fw-semibold">Заполнение формы как <?php echo $finduser->vkname; ?></span>
												</span>
											</button>
										</div>
										<div id="editType">
											<div class="mb-3">
												<label for="inputAdmin" class="form-label text-white">Занимали ли ранее пост
													администратора?</label>
												<select class="form-select bg-dark text-white border-secondary" id="inputAdmin">
													<option value="0">Нет, не занимал(а)</option>
													<option value="1">Да, занимал(а)</option>
												</select>
											</div>
											<div class="d-grid mb-3">
												<button type="submit"
													onclick="changeType(document.getElementById('inputAdmin').value)"
													class="btn btn-primary py-2">
													<i class='bx bx-user me-1'></i>Далее
												</button>
											</div>
										</div>
										<div class="form-body row g-3" id="typeNewAdmin" hidden>
											<div class="col-sm-6">
												<label for="inputNick" class="form-label text-white">Никнейм</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="inputNick"
													placeholder="Ваш игровой никнейм">
											</div>
											<div class="col-sm-6">
												<label for="inputNewNick" class="form-label text-white">Новый никнейм</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="inputNewNick"
													placeholder="Необязательно">
											</div>
											<div class="col-sm-6">
												<label for="inputName" class="form-label text-white">Имя</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="inputName"
													placeholder="Ваше реальное имя">
											</div>
											<div class="col-sm-6">
												<label for="inputBirth" class="form-label text-white">Возраст</label>
												<input type="number" class="form-control bg-dark text-white border-secondary" id="inputBirth" min="0" max="99"
													placeholder="Ваш реальный возраст">
											</div>
											<div class="col-sm-6">
												<label for="inputReason" class="form-label text-white">Тип назначения:</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="inputReason"
													placeholder="Обзвон / Лидерка [с .. по ..]">
											</div>
											<div class="col-sm-6">
												<label for="inputForum" class="form-label text-white">Форумный аккаунт</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="inputForum"
													placeholder="Ссылка">
											</div>
											<div class="col-sm-6">
												<label for="inputDiscord" class="form-label text-white">Discord</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="inputDiscord"
													placeholder="@username или ID">
											</div>
											<div class="col-sm-12">
												<label for="inputMails" class="form-label text-white">Ваши email-адреса (почты)</label>
												<textarea type="text" class="form-control bg-dark text-white border-secondary" style="height:120px" id="inputMails" placeholder="Перечислите все ваши адреса электронных почт через запятую"></textarea>
											</div>
											<div class="col-sm-12">
												<label for="inputNicks" class="form-label text-white">Аккаунты на других серверах</label>
												<textarea type="text" class="form-control bg-dark text-white border-secondary" style="height:120px" id="inputNicks" placeholder="Перечислите все аккаунты которые вы помните через запятую&#10;Например: Sam_Mason - Tucson, Roy_Shelby - Red-Rock&#10;&#10;Если нет, то оставьте поле пустым"></textarea>
											</div>
											<div class="col-12">
												<div class="d-grid mb-3">
													<button type="submit" onclick="create()" class="btn btn-primary py-2">
														<i class='bx bx-user me-1'></i>Отправить
													</button>
												</div>
											</div>
										</div>

										<div class="form-body row g-3" id="typeOldAdmin" hidden>
											<div class="col-sm-6">
												<label for="oldInputNick" class="form-label text-white">Никнейм</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="oldInputNick"
													placeholder="Ваш игровой никнейм">
											</div>
											<div class="col-sm-6">
												<label for="oldInputNickEnd" class="form-label text-white">Никнейм на момент снятия</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="oldInputNickEnd"
													placeholder="Ваш игровой никнейм">
											</div>
											<div class="col-sm-6">
												<label for="oldInputName" class="form-label text-white">Имя</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="oldInputName"
													placeholder="Ваше реальное имя">
											</div>
											<div class="col-sm-6">
												<label for="oldInputBirth" class="form-label text-white">Возраст</label>
												<input type="number" class="form-control bg-dark text-white border-secondary" id="oldInputBirth" min="0" max="99"
													placeholder="Ваш реальный возраст">
											</div>
											<div class="col-sm-6">
												<label for="oldInputCity" class="form-label text-white">Город проживания</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="oldInputCity"
													placeholder="Ваш город">
											</div>
											<div class="col-sm-6">
												<label for="oldInputDiscord" class="form-label text-white">Discord</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="oldInputDiscord"
													placeholder="@username или ID">
											</div>
											<div class="col-sm-4">
												<label for="oldInputLVL" class="form-label text-white">Уровень при снятии</label>
												<input type="number" class="form-control bg-dark text-white border-secondary" id="oldInputLVL" min="1" max="7"
													placeholder="От 1 до 7">
											</div>
											<div class="col-sm-4">
												<label for="oldInputReason" class="form-label text-white">Причина снятия</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="oldInputReason"
													placeholder="Причина">
											</div>
											<div class="col-sm-4">
												<label for="oldInputForum" class="form-label text-white">Форумный аккаунт</label>
												<input type="text" class="form-control bg-dark text-white border-secondary" id="oldInputForum"
													placeholder="Ссылка">
											</div>
											<div class="col-sm-6">
												<label for="oldInputStart" class="form-label text-white">Дата назначения</label>
												<input type="date" class="form-control bg-dark text-white border-secondary" id="oldInputStart">
											</div>
											<div class="col-sm-6">
												<label for="oldInputEnd" class="form-label text-white">Дата снятия</label>
												<input type="date" class="form-control bg-dark text-white border-secondary" id="oldInputEnd">
											</div>
											<div class="col-sm-12">
												<label for="oldInputMails" class="form-label text-white">Ваши email-адреса (почты)</label>
												<textarea type="text" class="form-control bg-dark text-white border-secondary" style="height:120px" id="oldInputMails" placeholder="Перечислите все ваши адреса электронных почт через запятую"></textarea>
											</div>
											<div class="col-sm-12">
												<label for="oldInputNicks" class="form-label text-white">Аккаунты на других серверах</label>
												<textarea type="text" class="form-control bg-dark text-white border-secondary" style="height:120px" id="oldInputNicks" placeholder="Перечислите все аккаунты которые вы помните&#10;Например: Sam_Mason - Tucson, Roy_Shelby - Red-Rock&#10;&#10;Если нет, то оставьте поле пустым"></textarea>
											</div>
											<div class="col-12">
												<div class="d-grid mb-3">
													<button type="submit" onclick="create()" class="btn btn-primary py-2">
														<i class='bx bx-user me-1'></i>Отправить
													</button>
												</div>
											</div>
										</div>
									<?php elseif ($finduser && $finduser->status == 1): ?>
										<div class="alert alert-success border-0 bg-success alert-dismissible fade show">
											<div class="d-flex align-items-center">
												<div class="ms-3">
													<h5 class="mb-0 text-white mb-2">Ваша заполненная форма на рассмотрении!</h5>
													<div class="text-white">Форма, которую вы ранее заполнили находится на рассмотрении у руководства сервера! Это может занять некоторое время, пожалуйста ожидайте..</div>
												</div>
											</div>
										</div>
									<?php else: ?>
										<div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
											<div class="d-flex align-items-center">
												<div class="ms-3">
													<h5 class="mb-0 text-white mb-2">Недостаточно прав!</h5>
													<div class="text-white">У Вас недостаточно прав для заполнения формы будущего администратора. Обратитесь к руководству своего сервера!</div>
												</div>
											</div>
										</div>
									<?php endif; ?>
									<div class="col-12 text-center mt-3">
										<a href="/api/logout.php" class="text-secondary">Выйти</a>
									</div>
								<?php else: ?>
									<div class="d-grid mb-3">
										<?php
										include_once 'config.php';
										$url = 'http://oauth.vk.ru/authorize';
										$params = ['client_id' => VK_CLIENT_ID, 'redirect_uri' => VK_CLIENT_REDIRECT_URI_FORM_1, 'response_type' => 'code'];
										?>
										<a class="btn shadow-sm py-2" style="background-color: #4680C2; color: #fff;"
											href="<?php echo $url . '?' . urldecode(http_build_query($params)); ?>">
											<span class="d-flex justify-content-center align-items-center">
												<img class="me-2" src="assets/images/vk.svg" width="18" height="18" alt="VK">
												<span class="fw-semibold">Авторизоваться через VK</span>
											</span>
										</a>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="bg-dark border-top border-secondary">
			<div class="container-fluid px-3 py-2">
				<div class="text-center">
					<div class="d-flex flex-column flex-sm-row justify-content-center gap-2 gap-sm-3">
						<a class="text-secondary text-decoration-none" style="font-size: 0.75rem;" href="#" data-bs-toggle="modal" data-bs-target="#policy">
							Политика конфиденциальности
						</a>
						<a class="text-secondary text-decoration-none" style="font-size: 0.75rem;" href="#" data-bs-toggle="modal" data-bs-target="#sogl">
							Пользовательское соглашение
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="policy" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content border-0 shadow-lg bg-dark">
				<div class="modal-header bg-primary text-white border-0">
					<h5 class="modal-title fw-bold">
						<i class="bx bx-shield me-2"></i>Политика конфиденциальности
					</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body p-4 bg-dark">
					<div id="policy-content" class="markdown-content"></div>
				</div>
				<div class="modal-footer border-0 bg-dark">
					<button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
						<i class="bx bx-check me-1"></i>Понятно
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="sogl" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content border-0 shadow-lg bg-dark">
				<div class="modal-header bg-primary text-white border-0">
					<h5 class="modal-title fw-bold">
						<i class="bx bx-file me-2"></i>Пользовательское соглашение
					</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body p-4 bg-dark">
					<div id="agreement-content" class="markdown-content"></div>
				</div>
				<div class="modal-footer border-0 bg-dark">
					<button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
						<i class="bx bx-check me-1"></i>Понятно
					</button>
				</div>
			</div>
		</div>
	</div>

	<script src="assets/js/bootstrap.bundle.min.js"></script>

	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
	<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
	<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>

	<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
	<script src="assets/js/app.js"></script>

	<script>
		function loadMarkdown(id, path) {
			fetch(path)
				.then(response => response.text())
				.then(mdText => {
					document.getElementById(id).innerHTML = marked.parse(mdText);
				});
		}

		document.addEventListener("DOMContentLoaded", () => {
			loadMarkdown("policy-content", "assets/legal/politics.md");
			loadMarkdown("agreement-content", "assets/legal/agreement.md");
		});
	</script>

	<script>
		function create() {
			let type = document.getElementById('inputAdmin').value
			if (type == 0) {
				let nick = document.getElementById('inputNick').value
				let newnick = document.getElementById('inputNewNick').value
				let name = document.getElementById('inputName').value
				let birth = document.getElementById('inputBirth').value
				let reason = document.getElementById('inputReason').value
				let forum = document.getElementById('inputForum').value
				let nicks = document.getElementById('inputNicks').value
				let discord = document.getElementById('inputDiscord').value
				let mails = document.getElementById('inputMails').value

				$.post('/api/futures/send_form.php', {
					nick,
					newnick,
					name,
					birth,
					reason,
					forum,
					type,
					nicks,
					discord,
					mails
				}).done(function(data) {
					let error = document.getElementById('error')
					if (data.error) {
						round_warning_noti(data.message);
					} else {
						round_success_noti('Форма заполнена, ожидайте инструкций от Ст. Администрации!');
						setTimeout(function() {
							location.reload();
						}, 2500);
					}
				}).catch(function(data) {
					round_error_noti(`Ошибка на стороне сервера #${data.status}<br>Попробуйте позже`);
					console.log(data)
				})

			} else if (type == 1) {
				let nick = document.getElementById('oldInputNick').value
				let oldnick = document.getElementById('oldInputNickEnd').value
				let name = document.getElementById('oldInputName').value
				let discord = document.getElementById('oldInputDiscord').value
				let reason = document.getElementById('oldInputReason').value
				let lvl = document.getElementById('oldInputLVL').value
				let forum = document.getElementById('oldInputForum').value
				let start = document.getElementById('oldInputStart').value
				let end = document.getElementById('oldInputEnd').value
				let nicks = document.getElementById('oldInputNicks').value
				let birth = document.getElementById('oldInputBirth').value
				let city = document.getElementById('oldInputCity').value
				let mails = document.getElementById('oldInputMails').value

				$.post('/api/futures/send_form.php', {
					type,
					nick,
					oldnick,
					name,
					discord,
					mails,
					reason,
					lvl,
					forum,
					start,
					end,
					nicks,
					city,
					birth
				}).done(function(data) {
					let error = document.getElementById('error')
					if (data.error) {
						round_warning_noti(data.message);
					} else {
						round_success_noti('Форма заполнена, ожидайте инструкций от Ст. Администрации!');
						setTimeout(function() {
							location.reload();
						}, 2500);
					}
				}).catch(function(data) {
					round_error_noti(`Ошибка на стороне сервера #${data.status}<br>Попробуйте позже`);
					console.log(data)
				})
			} else {
				round_error_noti(`Не найден указанный тип анкеты!`);
			}
		}

		function changeType(type) {
			if (type == 0) {
				document.getElementById('editType').hidden = true;
				document.getElementById('typeNewAdmin').hidden = false;
			} else if (type == 1) {
				document.getElementById('editType').hidden = true;
				document.getElementById('typeOldAdmin').hidden = false;
			}
		}
	</script>

	<script src="assets/plugins/notifications/js/lobibox.min.js"></script>
	<script src="assets/plugins/notifications/js/notifications.min.js"></script>
	<script src="assets/plugins/notifications/js/notification-custom-script.js"></script>
</body>

</html>