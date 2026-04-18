<?php
include_once 'config.php';

if (!isset($_SESSION['userId'])) {
	redirectTo('login.php');
}

$user = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
if ($user->accept == 0) {
	redirectTo('reg.php');
}

$server = R::findone('serverlist', 'id = ?', [$user->server]);
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head> <?php head("Администрация"); ?> </head>

<body>
	<div class="wrapper">
		<?php sidebar($user->access); ?>
		<header>
			<div class="topbar d-flex align-items-center">
				<nav class="navbar navbar-expand">
					<div class="mobile-toggle-menu">
						<i class='bx bx-menu'></i>
					</div>
					<div class="top-menu ms-auto"></div>
					<div class="user-box dropdown">
						<a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#"
							role="button" data-bs-toggle="dropdown" aria-expanded="false">
							<img src="<?php echo $_SESSION['photo'] ?>" class="user-img" alt="user avatar">
							<div class="user-info ps-3">
								<p class="user-name mb-0"><? echo $user->nick; ?></p>
								<p class="designattion mb-0"><? echo $user->post; ?></p>
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

		<div class="page-wrapper">
			<div class="page-content">
				<div class="row">
					<div class="card radius-10">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between flex-wrap">
								<h5 class="mb-0 mb-2 mb-md-0">Администрация сервера
									<?php echo $server->servername . ' [№' . $user->server . ']'; ?>
								</h5>
								<div id="levelCounters" class="d-flex flex-wrap gap-1 gap-md-2"></div>
							</div>
							<hr>
							<div id="tableADD">
								<div class="spinner-grow spinner-grow-sm me-2" role="status"></div>
								<span>Получение списка администрации...</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="overlay toggle-icon"></div>
			<a href="javaScript:;" class="back-to-top">
				<i class='bx bxs-up-arrow-alt'></i>
			</a>

			<?php footer(); ?>
		</div>

		<script src="assets/js/bootstrap.bundle.min.js"></script>
		<script src="assets/js/jquery.min.js"></script>
		<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
		<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
		<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
		<script src="assets/plugins/chartjs/js/Chart.min.js"></script>
		<script src="assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js"></script>
		<script src="assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js"></script>
		<script src="assets/plugins/jquery.easy-pie-chart/jquery.easypiechart.min.js"></script>
		<script src="assets/plugins/sparkline-charts/jquery.sparkline.min.js"></script>
		<script src="assets/plugins/jquery-knob/excanvas.js"></script>
		<script src="assets/plugins/jquery-knob/jquery.knob.js"></script>
		<script>
			function logout() {
				$.get('/api/logout.php').done(function() {
					location.reload()
				})
			}
			
			function updateLevelCounters(levelStats) {
				const countersContainer = document.getElementById('levelCounters');
				if (!countersContainer || !levelStats || levelStats.length === 0) {
					return;
				}
				
				let countersHTML = '';
				let totalCount = 0;
				
				levelStats.forEach(stat => {
					totalCount += stat.count;
					countersHTML += `
						<div class="d-flex align-items-center mb-1 mb-md-0 border border-secondary rounded overflow-hidden">
							<div style="background-color: ${stat.color}; color: white; font-size: 0.7rem; font-weight: bold; padding: 0.2rem 0.4rem; min-width: 3rem; text-align: center; border-radius: 0;">
								${stat.level} LVL
							</div>
							<div class="bg-transparent text-white" style="font-size: 0.7rem; font-weight: bold; padding: 0.2rem 0.4rem; min-width: 1.5rem; text-align: center; border-radius: 0;">
								${stat.count}
							</div>
						</div>
					`;
				});
				
				countersHTML += `
					<div class="d-flex align-items-center mb-1 mb-md-0 border border-primary rounded overflow-hidden">
						<div class="bg-secondary text-white" style="font-size: 0.7rem; font-weight: bold; padding: 0.2rem 0.4rem; min-width: 4rem; text-align: center; border-radius: 0;">
							ВСЕГО
						</div>
						<div class="bg-transparent text-white" style="font-size: 0.7rem; font-weight: bold; padding: 0.2rem 0.4rem; min-width: 1.5rem; text-align: center; border-radius: 0;">
							${totalCount}
						</div>
					</div>
				`;
				
				countersContainer.innerHTML = countersHTML;
			}
		</script>
		<script>
			function adminUpdate() {
				$.get(`/api/user/adminlist.php`).done(function(data) {
					updateLevelCounters(data.levelStats || []);
					
					let s = `<div class="table-responsive">
						<table class="table table-striped" id="adminList">
						<thead style="color: #aaa">
							<tr>
								<th>LVL</th>
								<th>Пользователь</th>
								<th>Имя</th>
								<th>Дни (LVL)</th>
								<th>Дни (ALL)</th>
								<th>Репутация</th>
								<th>Доп. Репутация</th>
								<th>${data.server == 8 ? "Преды" : "Выговоры"}</th>
								<th>${data.server == 8 ? `Страйки` : `Преды`}</th>
								<th>Жалобы</th>
								<th>Неактивы</th>
								<th>Должность</th>
							</tr>
						</thead>
					<tbody>`

					if (!data.error) {
						for (let admin of data.admins) {
							s += `
								<tr>
									<td style="vertical-align: middle">
										<span class="d-inline-block px-2 py-0 bg-light border rounded">
											${admin.lvl}
										</span>
									</td>
									<td style="vertical-align: middle">
									<div class="d-flex align-items-center justify-content-between">
										<div class="d-flex align-items-center">
										<img src="${admin.image}" class="rounded-circle" width="25">
										<a href="user.php?id=${admin.id}" target="_blank" style="color: white">
											<div class="font-14 col-sm-4 ms-2" style="font-weight: 500; color: white;">
											<span class="${admin.inStatus === true ? `text-warning` : `text`}">
												${admin.nick}
											</span>
											</div>
										</a>
										<span class="text-secondary mx-1">(${admin.prefix})</span>
										</div>
										<div>
										${data.sendjb ? `${admin.buttonsend}` : ``}
										</div>
									</div>
									</td>
									<td style="vertical-align: middle">
										<a href="https://vk.ru/id${admin.vk}" target="_blank" style="color: #99bbed">
											${admin.realname}
										</a>
									</td>
									<td style="vertical-align: middle;">${admin.daylvl}</td>
									<td style="vertical-align: middle;">${admin.dayall}</td>
									<td style="vertical-align: middle;">
										${admin.lvl > 4 ? "—" : (admin.gamerep == 0 ? "н/д" : admin.gamerep)}
									</td>
									<td style="vertical-align: middle;">${admin.plusrep}</td>
									<td style="vertical-align: middle">${admin.reprimands}</td>
									${data.warns ? `<td style="vertical-align: middle">${admin.warns}</td>` : ``}
									${data.jb ? `<td style="vertical-align: middle">${admin.jb}</td>` : ``}
									<td style="vertical-align: middle">${admin.inactive}</td>
									<td style="vertical-align: middle">${admin.fullpost}</td>
									${data.sendjb ? `${admin.buttonpopup}` : ``}
								</tr>`;
						}
					}
					s += `</tbody></table></div>`
					document.getElementById('tableADD').innerHTML = s;

					$(document).ready(function() {
						$("#adminList").DataTable({
							paging: false,
							stateSave: true,
							order: [
								[0, "desc"]
							],
						});
					});
				}).catch(function(data) {
					round_error_noti(`Ошибка на стороне сервера #${data.status}<br>Попробуйте позже`);
					console.log(data);
				})
			}
			adminUpdate();
		</script>

		<script src="assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
		<script>
			function sendthread(e) {
				let n = document.getElementById(`sendForum${e}`).value;
				let comment = document.getElementById(`commentForum${e}`).value;
				$.post("/api/user/sendvk.php", {
					id: e,
					url: n,
					comment: comment
				}).done(function(n) {
					document.getElementById("error");
					n.error ? round_warning_noti(n.message) : ($(function() {
						$(`#exampleDarkModal${e}`).modal("toggle")
					}), document.getElementById(`sendForum${e}`).value = null, document.getElementById(`commentForum${e}`).value = null, round_success_noti("Жалоба успешно передана!"))
				}).catch(function(e) {
					round_error_noti(`Ошибка на стороне сервера #${e.status}<br>Попробуйте позже`), console.log(e)
				})
			}
		</script>
		<script src="assets/js/app.js"></script>
		<script>
			$(function() {
				$('[data-bs-toggle="popover"]').popover();
				$('[data-bs-toggle="tooltip"]').tooltip();
			})
		</script>

		<script src="assets/plugins/notifications/js/lobibox.min.js"></script>
		<script src="assets/plugins/notifications/js/notifications.min.js"></script>
		<script src="assets/plugins/notifications/js/notification-custom-script.js"></script>
</body>

</html>