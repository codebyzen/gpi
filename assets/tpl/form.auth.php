<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="description" content="">
		<meta name="author" content="">
		<link rel="icon" href="https://getbootstrap.com/favicon.ico">

		<title><?php echo $config->get('appName').' '.$config->get('appVersion'); ?></title>

		<!-- Bootstrap core CSS -->
		<link href="./assets/bootstrap-4.1.3-dist/css/bootstrap.min.css" rel="stylesheet">

		<!-- Custom styles for this template -->
		<style>
			form {
				--input-padding-x: 0.75rem;
				--input-padding-y: 0.75rem;
			}
			
			html, body {
				height: 100%;
			}

			body {
				display: -ms-flexbox;
				display: flex;
				-ms-flex-align: center;
				align-items: center;
				padding-top: 40px;
				padding-bottom: 40px;
				background-color: #f5f5f5;
			}

			.form-signin {
				width: 100%;
				max-width: 420px;
				padding: 15px;
				margin: auto;
			}

			.form-label-group {
				position: relative;
				margin-bottom: 1rem;
			}

			.form-label-group > input,
			.form-label-group > label {
				padding: var(--input-padding-y) var(--input-padding-x);
			}


			.form-label-group input {
				background: transparent;
				border-radius: 0;
				border: none;
				border-bottom: 1px solid gray;
			}

			.form-label-group input:focus {
				background: transparent;
			}

			.form-label-group > label {
				position: absolute;
				top: 0;
				left: 0;
				display: block;
				width: 100%;
				margin-bottom: 0; /* Override default `<label>` margin */
				line-height: 1;
				color: #495057;
				cursor: text; /* Match the input under the label */
				transition: all .1s ease-in-out;
			}

			.form-label-group input::-webkit-input-placeholder {
				color: transparent;
			}

			.form-label-group input:-ms-input-placeholder {
				color: transparent;
			}

			.form-label-group input::-ms-input-placeholder {
				color: transparent;
			}

			.form-label-group input::-moz-placeholder {
				color: transparent;
			}

			.form-label-group input::placeholder {
				color: transparent;
			}

			.form-label-group input:not(:placeholder-shown) {
				/* padding-top: calc(var(--input-padding-y) + var(--input-padding-y) * (2 / 3)); */
				/* padding-bottom: calc(var(--input-padding-y) / 3); */
			}

			.form-label-group input:not(:placeholder-shown) ~ label {
				/* padding-top: calc(var(--input-padding-y) / 4); */
				/* padding-bottom: calc(var(--input-padding-y) / 4); */
				padding-top: 0;
				margin-top: -5px;
				font-size: 12px;
				color: #777;
			}

			/* Fallback for Edge
			-------------------------------------------------- */
			@supports (-ms-ime-align: auto) {
				.form-label-group > label {
					display: none;
				}
				.form-label-group input::-ms-input-placeholder {
					color: #777;
				}
			}

			/* Fallback for IE
			-------------------------------------------------- */
			@media all and (-ms-high-contrast: none), (-ms-high-contrast: active) {
				.form-label-group > label {
					display: none;
				}
				.form-label-group input:-ms-input-placeholder {
					color: #777;
				}
			}
			input:focus {
				outline-color: transparent !important;
				-webkit-box-shadow: none !important;
				box-shadow: none !important;
			}
		</style>
	</head>

	<body>
		<form class="form-signin" method="POST" action="<?php echo $config->get('url'); ?>">
			<div class="text-center mb-4">
				<img class="mb-4" src="./assets/images/gpi-solid.svg" alt="" width="72" height="72">
				<h1 class="h3 mb-3 font-weight-normal"><?php echo $config->get('appName'); ?></h1>
				<p>Instagram & Telegram posting engine v.<?php echo $config->get('appVersion'); ?></p>
			</div>

			<div class="form-label-group">
				<input type="text" name="login" id="inputEmail" class="form-control" placeholder="Email" required="" autofocus="">
				<label for="inputEmail">Email</label>
			</div>

			<div class="form-label-group">
				<input type="password" name="password" id="inputPassword" class="form-control" placeholder="Пароль" required="">
				<label for="inputPassword">Пароль</label>
			</div>

			<div class="checkbox mb-3">
				<label>
					<input type="checkbox" value="remember-me"> Запомнить меня
				</label>
			</div>
			<button class="btn btn-lg btn-primary btn-block" type="submit">Войти</button>
			<p class="mt-5 mb-3 text-muted text-center small"><?php echo $config->get('appDeveloper');?> &copy; 2018</p>
			<input type="hidden" name="auth" value="auth">
		</form>


	</body>
</html>