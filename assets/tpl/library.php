		<?php include($config->get('themepath').'/header.php');?>


		<main role="main" class="container">

			<div class="my-3 p-3 bg-white rounded shadow-sm">
				<h6 class="border-bottom border-gray pb-2 mb-3">Библиотека</h6>
				<div id="posts_preview">
				</div>
				<small class="d-block text-center mt-3">
					<a href="#" id='load_next_btn'>Загрузить еще</a>
				</small>
			</div>
		</main>

		<?php include($config->get('themepath').'/footer.php');?>

		<?php include($config->get('themepath').'/js-vendors.php');?>

		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-js-bootstrap.js"></script>

		<script src="<?php echo $config->get('assetsurl'); ?>/js/gpi-render-post-preview.js"></script>
		
		<script src="<?php echo $config->get('assetsurl'); ?>/tpl/post_info_tpl.js"></script>
		
		<script>
		
		    var gpi_filter = '';
		
			$(function () {
				'use strict';
				
				gpi_waiter.show();
				
				apiRequest({request: 'bootstrap'}, function(data){
					gpi_waiter.hide();
					if (data.type!=='success') { alert('Ошибка (bootstrap) API!'); return false; }
					if (data.opts.type!=='success') { alert('Ошибка скрипта:\n'+data.opts.message); return false; }
					data.opts = data.opts.message;
					update_posts_preview(0, post_info, '#posts_preview', 'posts_library', '<?php echo $router->path[1];?>');
				});
				
				
				$('#posts_preview').on('click', '.delete-post', function(e){
					e.preventDefault();
					//if (prompt("Введите 'yes' для удаления!","no")!='yes') return false;
					if (confirm("Нажмите 'Ok' для удаления!")!=true) return false;
					var id = $(this).data('post-id');
					apiRequest({
						request: 'delete',
						id: id
					},function(data){
						if (data.type!=='success') { alert('Ошибка API!'); return false; }
						if (data.opts.type!=='success') { alert('Ошибка скрипта:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						$('#post_id_'+id).slideUp(function(){ $(this).remove(); });
						notify(data.type,data.message);
					});
				});
				
				
				$('#posts_preview').on('click', '.post_id_now', function(e){
					e.preventDefault();
					if (prompt("Введите 'now' для немедленной публикации!","no")!='now') return false;
					gpi_waiter.show();
					var post_id = $(this).data('file-id');
					apiRequest({
						request: 'post_id_now',
						id: post_id
					},function(data){
						gpi_waiter.hide();
						if (data.type!=='success') { alert('Ошибка API!'); return false; }
						if (data.opts.type!=='success') { alert('Ошибка скрипта:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						$('#post_id_'+post_id).slideUp(function(){ $(this).remove(); });
						notify(data.type,data.opts);
					});
				});
				
				$('#posts_preview').on('click', '.clear_publish_status', function(e){
					e.preventDefault();
					if (confirm('Очистить статус "Опубликовано"?')==false) return false;
					var post_id = $(this).data('file-id');
					apiRequest({
						request: 'clear_publish_status',
						id: post_id
					},function(data){
						if (data.type!=='success') { alert('Ошибка API!'); return false; }
						if (data.opts.type!=='success') { alert('Ошибка скрипта:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						$('#post_id_'+post_id).slideUp(function(){ $(this).remove(); });
						notify(data.type,data.opts);
					});
				});
				
				$('#load_next_btn').on('click', function(e){
					e.preventDefault();
					update_posts_preview(window.gpi_loaded_previews_from+10, post_info, '#posts_preview', 'posts_library', '<?php echo $router->path[1];?>');
				});
				
			
			});
			
	
			
			
			
			
			
		</script>
		
		
		
	</body>
</html>