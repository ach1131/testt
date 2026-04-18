<?php
include_once 'config.php';

if (!isset($_SESSION['userId'])) {
	redirectTo('login.php');
}

$user = R::findone('users', 'vkid = ?', [$_SESSION['userId']]);
if ($user->accept == 0) {
	redirectTo('reg.php');
}

if ($user->access < 4) {
	redirectTo('index.php');
}

$servername = R::findone('serverlist', 'id = ?', [$user->server])->servername;
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head><?php head("Форумный список"); ?></head>

<body>
	<div class="wrapper">
		<?php sidebar($user->access); ?>
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
								<p class="user-name mb-0"><? echo $user->nick; ?></p>
								<p class="designattion mb-0"><? echo $user->post; ?></p>
							</div>
						</a>
						<ul class="dropdown-menu dropdown-menu-end">
							<li>
								<a class="dropdown-item" href="user.php"><i
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
				<div class="row">
					<div class="card radius-10">
						<div class="card-body">
							<div class="d-flex align-items-center">
								<div>
									<h5 class="mb-0">Список администрации для форума сервера
										<?php echo $servername . ' №' . $user->server; ?></h5>
								</div>
							</div>
							<hr>
							<div id="tableADD">
								<span>Загрузка списка администрации для форума
									<?php echo "{$servername} №{$user->server}" ?>...</span>
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
		</script>
		<script>
			$.get(`/api/user/forumlist.php`).done(function(data) { //table table-striped table-bordered
				let s = '<div class="col-sm" id="forumList"><textarea type="text" class="form-control" id="inputForumList" style="height: 500px">'
				if (!data.error) {
					for (let admin of data.admins) {
						s += `${admin.adminlist}`
					}
					s += `</textarea></div><br><center><div class="col-lg"><input type="button" id="copyText" class="btn btn-primary px-4" value="Скопировать в буфер обмена" /></div></center>`
				}
				document.getElementById('tableADD').innerHTML = s;
			}).catch(function(data) {
				console.log(data)
			})
		</script>
		<script src="assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
		<script>
			function waitForElm(selector) {
				return new Promise(resolve => {
					if (document.querySelector(selector)) {
						return resolve(document.querySelector(selector));
					}

					const observer = new MutationObserver(mutations => {
						if (document.querySelector(selector)) {
							resolve(document.querySelector(selector));
							observer.disconnect();
						}
					});

					observer.observe(document.body, {
						childList: true,
						subtree: true
					});
				});
			}
			waitForElm('#forumList').then((elm) => {
				var text = document.getElementById("inputForumList");
				var btn = document.getElementById("copyText");
				btn.onclick = function() {
					text.select();
					document.execCommand("copy");
				}

			});
		</script>
		<script src="assets/js/app.js"></script>
</body>

</html>