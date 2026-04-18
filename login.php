<?php
include_once 'config.php';
?>

<!doctype html>
<html lang="en" class="dark-theme">

<head>
	<?php head("Авторизация"); ?>
</head>

<body class="bg-login">
	<div class="wrapper d-flex flex-column min-vh-100">
		<div class="flex-grow-1 d-flex align-items-center justify-content-center py-3">
			<div class="container-fluid px-3">
				<div class="row justify-content-center">
					<div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
						<div class="card shadow-sm border-0 bg-dark">
							<div class="card-body p-3 p-md-4">
								<div class="text-center mb-3">
									<img src="assets/images/logo.svg" width="72" height="72" alt="Logo" class="mb-2">
									<h4 class="text-white my-2">ARIZONA ADMINS</h4>
									<p class="text-secondary mb-1">Панель управления администрацией</p>
								</div>
								<hr>
								<?php
								$url = 'http://oauth.vk.ru/authorize';
								$params = ['client_id' => VK_CLIENT_ID, 'redirect_uri'  => VK_CLIENT_REDIRECT_URI_FORM_0, 'response_type' => 'code'];
								?>
								<div class="d-grid gap-1">
									<a style="background-color: #4680C2; color: #fff;" class="btn btn-sm shadow-sm py-2" href="#" id="vkAuthBtn">
										<div class="d-flex align-items-center justify-content-center">
											<img class="me-2" src="assets/images/vk.svg" width="18" height="18" alt="VK">
											<span class="fw-semibold">Войти через VK</span>
										</div>
									</a>
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

			const vkAuthBtn = document.getElementById('vkAuthBtn');

			vkAuthBtn.addEventListener('click', function(e) {
				e.preventDefault();

				const baseUrl = '<?php echo $url; ?>';
				const params = <?php echo json_encode($params); ?>;

				const authUrl = baseUrl + '?' + new URLSearchParams(params).toString();
				window.location.href = authUrl;
			});
		});
	</script>

	<script src="assets/js/bootstrap.bundle.min.js"></script>
	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/plugins/simplebar/js/simplebar.min.js"></script>
	<script src="assets/plugins/metismenu/js/metisMenu.min.js"></script>
	<script src="assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
	<script src="assets/js/app.js"></script>
</body>

</html>