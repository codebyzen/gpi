<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="description" content="Go Post It">
		<meta name="author" content="Eugene Che">
		<link rel="icon" href="<?php echo $config->get('url'); ?>/favicon.ico">

		<title><?php echo $config->get('appName').' '.$config->get('appVersion');?></title>

		<!-- FontAwesome 5.5.0 -->
		<link href="<?php echo $config->get('assetsurl'); ?>/fontawesome-free-5.5.0-web/css/all.css" rel="stylesheet">
		
		<!-- Bootstrap core CSS -->
		<link href="<?php echo $config->get('assetsurl'); ?>/bootstrap-4.1.3-dist/css/bootstrap.min.css" rel="stylesheet">

		<!-- Custom styles for this template -->
		<link href="<?php echo $config->get('assetsurl'); ?>/css/offcanvas.css" rel="stylesheet">
		<link href="<?php echo $config->get('assetsurl'); ?>/css/main.css" rel="stylesheet">
		

		<!-- Custom styles for this template -->
		<link href="<?php echo $config->get('assetsurl'); ?>/css/sticky-footer-navbar.css" rel="stylesheet">
		
		<link href="<?php echo $config->get('assetsurl'); ?>/toastr-2.1.1/toastr.css" rel="stylesheet">

		<script src="<?php echo $config->get('assetsurl'); ?>/js/jquery-3.3.1.min.js"></script> 
		<script> window.gpi_url = '<?php echo $config->get('url'); ?>'; </script>
	</head>
	<body class="bg-light">
		
		<?php include($config->get('themepath').'/nav.php');?>