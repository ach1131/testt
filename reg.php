<?php
require_once 'config.php';

$accesslogin = false;
if ($_SESSION['userId']) {
	$user = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
	if ($user) {
		if ($user->registration == 1) {
			if ($user->accept == 0) {
				$accesslogin = true;
			} else {
				redirectTo('index.php');
			}
		} else {
			$accesslogin = false;
		}
	} else {
		$accesslogin = false;
	}
} else {
	redirectTo('login.php');
}
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head>
	<?php head("Регистрация"); ?>
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
									<p class="text-secondary mb-1">Регистрация администратора</p>
								</div>
								<hr>
								<div class="alert alert-danger border-0 bg-danger alert-dismissible fade show py-2"
									<?php if ($accesslogin) {
										echo "hidden";
									} ?>>
									<div class="d-flex align-items-center">
										<div class="font-35 text-white"><i class='bx bxs-message-square-x'></i>
										</div>
										<div class="ms-3">
											<h6 class="mb-0 text-white">Регистрация недоступна!</h6>
											<div class="text-white">Руководство вашего сервера ещё не выдало вам доступ к регистрации</div>
										</div>
									</div>
								</div>
								<div class="d-grid mb-3">
									<a class="btn shadow-sm py-2" style="background-color: #4680C2; color: #fff; cursor: default;" tabindex="-1">
										<span class="d-flex justify-content-center align-items-center">
											<img class="me-2" src="assets/images/vk.svg" width="18" height="18" alt="VK">
											<span class="fw-semibold">Регистрация как:
												<?php echo "{$_SESSION['first_name']} {$_SESSION['last_name']}"; ?></span>
										</span>
									</a>
								</div>
								<div class="form-body row g-3">
									<div class="col-sm-4">
										<label for="inputNick" class="form-label text-white">Никнейм</label>
										<input type="text" class="form-control bg-dark text-white border-secondary" id="inputNick"
											placeholder="Sam_Mason" <?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
									</div>
									<div class="col-sm-4">
										<label for="inputServer" class="form-label text-white">Сервер</label>
										<select class="form-select bg-dark text-white border-secondary" id="inputServer"
											aria-label="Default select example" <?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
											<?php
											$serverlist = R::findall('serverlist', 'id < 1000');
											foreach ($serverlist as $key => $value) {
												if ($user->server > 0) {
													if ($value['id'] == $user->server) {
														echo '<option value="' . $value['id'] . '" selected>' . $value['servername'] . ' [№' . $value['id'] . ']</option>';
													} else {
														echo '<option value="' . $value['id'] . '" disabled>' . $value['servername'] . ' [№' . $value['id'] . ']</option>';
													}
												} else {
													echo '<option value="' . $value['id'] . '">' . $value['servername'] . ' [№' . $value['id'] . ']</option>';
												}
											}
											?>
										</select>
									</div>
									<div class="col-sm-4">
										<label for="inputType" class="form-label text-white">Назначение</label>
										<select class="form-select bg-dark text-white border-secondary" id="inputType"
											aria-label="Default select example" <?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
											<option value="1">Лидерка</option>
											<option value="2">Обзвон</option>
											<option value="3">Восстановление</option>
											<option value="4">Перевод</option>
											<option value="5">Судья</option>
										</select>
									</div>
									<div class="col-sm-6">
										<label for="inputLvl" class="form-label text-white">Уровень (От 1 до 7)</label>
										<input type="number" class="form-control bg-dark text-white border-secondary" id="inputLvl" min="1" max="7"
											placeholder="1 - 7" <?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
									</div>
									<div class="col-sm-6">
										<label for="inputPost" class="form-label text-white">Должность</label>
										<input type="text" class="form-control bg-dark text-white border-secondary" id="inputPost" placeholder="Хелпер"
											<?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
									</div>
									<div class="col-sm-4">
										<label for="inputPrefix" class="form-label text-white">Префикс (Тег)</label>
										<input type="text" class="form-control bg-dark text-white border-secondary" id="inputPrefix"
											placeholder="// Mason" <?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
									</div>
									<div class="col-sm-4">
										<label for="inputForum" class="form-label text-white">Форумный аккаунт</label>
										<input type="text" class="form-control bg-dark text-white border-secondary" id="inputForum" placeholder="Ссылка"
											<?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
									</div>
									<div class="col-sm-4">
										<label for="inputDiscord" class="form-label text-white">Discord</label>
										<input type="text" class="form-control bg-dark text-white border-secondary" id="inputDiscord"
											placeholder="mason" <?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
									</div>
									<div class="col-sm-6">
										<label for="inputAssign" class="form-label text-white">Дата назначения</label>
										<input class="result form-control bg-dark text-white border-secondary" id="inputAssign" type="date" <?php if (!$accesslogin) {
											echo "disabled";
										} ?>>
									</div>
									<div class="col-sm-6">
										<label for="inputUpdate" class="form-label text-white">Дата повышения</label>
										<input class="result form-control bg-dark text-white border-secondary" id="inputUpdate" type="date" <?php if (!$accesslogin) {
											echo "disabled";
										} ?>>
									</div>
									<div class="col-sm-3">
										<label for="inputRealname" class="form-label text-white">Реальное имя</label>
										<input type="text" class="form-control bg-dark text-white border-secondary" id="inputRealname" placeholder="Иван"
											<?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
									</div>
									<div class="col-sm-3">
										<label for="inputCountry" class="form-label text-white">Страна</label>
										<input type="text" class="form-control bg-dark text-white border-secondary" id="inputCountry" placeholder="США"
											<?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
									</div>
									<div class="col-sm-3">
										<label for="inputCity" class="form-label text-white">Город</label>
										<input type="text" class="form-control bg-dark text-white border-secondary" id="inputCity"
											placeholder="Лос-Анджелес" <?php if (!$accesslogin) {
												echo "disabled";
											} ?>>
									</div>
									<div class="col-sm-3">
										<label for="inputBirth" class="form-label text-white">Дата рождения</label>
										<input type="date" class="form-control bg-dark text-white border-secondary" id="inputBirth" <?php if (!$accesslogin) {
											echo "disabled";
										} ?>>
									</div>
									<?php if ($accesslogin): ?>
										<div class="col-12">
											<div class="d-grid">
												<button type="submit" onclick="create()" class="btn btn-primary py-2">
													<i class='bx bx-user me-1'></i>Зарегистрироваться
												</button>
											</div>
										</div>
									<?php endif ?>
									<div class="col-12 text-center mt-3">
										<a href="/api/logout.php" class="text-secondary">Не регистрироваться</a>
									</div>
								</div>
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
			let name = document.getElementById('inputNick').value
			let server = document.getElementById('inputServer').value
			let lvl = document.getElementById('inputLvl').value
			let post = document.getElementById('inputPost').value
			let tag = document.getElementById('inputPrefix').value
			let forum = document.getElementById('inputForum').value
			let discord = document.getElementById('inputDiscord').value
			let ndate = document.getElementById('inputAssign').value
			let oldate = document.getElementById('inputUpdate').value
			let type = document.getElementById('inputType').value

			let country = document.getElementById('inputCountry').value
			let realname = document.getElementById('inputRealname').value
			let city = document.getElementById('inputCity').value
			let birth = document.getElementById('inputBirth').value
			$.post('/api/user/create.php', {
				'name': name,
				'server': server,
				'lvl': lvl,
				'post': post,
				'tag': tag,
				'forum': forum,
				'discord': discord,
				'ndate': ndate,
				'oldate': oldate,
				'type': type,
				'country': country,
				'realname': realname,
				'city': city,
				'birth': birth
			}).done(function(data) {
				let error = document.getElementById('error')
				if (data.error) {
					round_warning_noti(data.message);
				} else {
					round_success_noti('Аккаунт успешно создан!');
					location.reload()
				}
			}).catch(function(data) {
				round_error_noti(`Ошибка на стороне сервера #${data.status}<br>Попробуйте позже`);
				console.log(data)
			})
		}
	</script>

	<script src="assets/plugins/notifications/js/lobibox.min.js"></script>
	<script src="assets/plugins/notifications/js/notifications.min.js"></script>
	<script src="assets/plugins/notifications/js/notification-custom-script.js"></script>
</body>

</html>