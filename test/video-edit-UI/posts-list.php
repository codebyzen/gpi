<!DOCTYPE html>
<html>
	<head>
		<title>Day queue</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<script src="../../assets/jQuery/jquery-3.4.1.min.js"></script>
		
		<script src="../../assets/jQuery/jquery-ui.min.js"></script>
		<link href="../../assets/jQuery/jquery-ui.min.css" rel="stylesheet">
	
		
		<script src="../../assets/js/popper.min.js"></script>
		
		<script src="../../assets/bootstrap-4.3.1-dist/js/bootstrap.min.js"></script>
		
		<link href="../../assets/bootstrap-4.3.1-dist/css/bootstrap.min.css" rel="stylesheet">
		
		
		<link href="../../assets/fontawesome-free-5.5.0-web/css/all.css" rel="stylesheet">
		<script src="../../assets/fontawesome-free-5.5.0-web/js/fontawesome.min.js"></script>
		
		<style>
			/*(xs) Extra small devices (portrait phones, less than 576px)*/
			@media (max-width: 575px) { .card-columns { column-count: 1; } }

			/*(sm) Small devices (landscape phones, 576px and up)*/
			@media (min-width: 576px) and (max-width: 767px) { .card-columns { column-count: 1; } }

			/*(md) Medium devices (tablets, 768px and up)*/
			@media (min-width: 768px) and (max-width: 991px) { .card-columns { column-count: 2; } }

			/*(lg) Large devices (desktops, 992px and up)*/
			@media (min-width: 992px) and (max-width: 1199px) { .card-columns { column-count: 3; } }

			/*(xl) Extra large devices (large desktops, 1200px and up)*/
			@media (min-width: 1200px) { .card-columns { column-count: 3; } }
			
			.card-image-header {
				background-size: cover;
				background-repeat: no-repeat;
				background-position: 50% 50%;
				width: 100%;
				height: 220px;
			}
			
			
		</style>

		
		
		<script>
			$(function () {
				$('[data-toggle="tooltip"]').tooltip()
				
				$('#queue_sortable_cards').sortable({
					revert: true,
				});
				
				$("#queue_sortable_cards .card").disableSelection();
				
			});
		</script>
		
	</head>
	<body>
		
		<div class="container">
			<div class="row">
				<div class="col">
		
		
					<div class="card-columns mb-4" id="queue_sortable_cards">

							<?php
								$files = range(1, 21);
								shuffle($files);

								foreach($files as $k=>$img_name) {
									$color = ['success', 'info', 'danger', ''];
									$bg_color_class='alert-'.$color[rand(0,count($color)-1)];

									$post_type_classes = [
										["video", "play-circle"],
										["image", "image"],
										["igtv", "tv"],
										["carousel", "copy"],
									];

									$text = function(){
										$count = rand(1,10);
										$t = 'Lorem ipsum dolor sit amet'; 
										$r = '';
										for($i=0;$i<$count;$i++) {
											$r .= $t.' ';
										}
										return $r;
									};

									shuffle($post_type_classes);


									$post_type = $post_type_classes[0][0];
									$post_type_class = $post_type_classes[0][1];



							?>

								<div class="card <?php echo $bg_color_class;?>">
									<div class="card-image-header" style="background-image: url('./images/<?php echo $img_name;?>.jpg');"></div>
									<div class="card-body">
										<p class="card-text"><?php echo $text();?></p>
										<p class="card-text"></p>
									</div>
									<ul class="list-group list-group-flush">

										<li class="list-group-item bg-transparent"><em>#lorem #ipsum #dolor #sit #amet</em></li>

										<li class="list-group-item bg-transparent">
											<div class="container-fluid p-0">
												<div class="row">
													<div class="col-2">
														<i class="fa fa-<?php echo $post_type_class;?>" data-toggle="tooltip" data-placement="top" title="<?php echo $post_type;?>" ></i> 
													</div>
													<?php if ($post_type!='carousel'){ ?>
														<div class="col">
																<i class="fa fa-expand"></i> <?php echo rand(640,1280);?>x<?php echo rand(640,1280);?>
														</div>
													<?php } else { 
													?>
														<div class="col">
															<span class="badge badge-secondary">
													<?php
															echo rand(2,10);
													?>
															</span>
														</div>
													<?php
													}
													?>

													<?php if ($post_type=='igtv' || $post_type=='video'){ ?>										
														<div class="col">
															<i class="fa fa-clock"></i> <?php echo rand(0,10);?>:<?php echo rand(0,59);?>
														</div>
													<?php } ?>
												</div>
											</div>
										</li>

									</ul>
									<div class="card-footer">
										<div class="container-fluid p-0">
											<div class="row">
												<div class="col-6">
													<a href="#" class="card-link text-dark"><i class="fa fa-pen fa-xs"></i> Edit post</a>
												</div>
												<div class="col-6 text-right">
													<a href="#" class="card-link text-right text-danger"><i class="fa fa-times"></i> Delete post</a>
												</div>
											</div>
										</div>


									</div>
								</div>

							<?php
								}
							?>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
