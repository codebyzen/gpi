		<?php include($config->get('themepath').'/header.php');?>

		<main role="main" class="container">
			
			<div class="my-3 p-3 bg-white rounded shadow-sm">
				<div id="edit-post-form-placeholder"></div>
			</div>

		</main>
		
		<?php include($config->get('themepath').'/footer.php');?>
		
		<?php include($config->get('themepath').'/js-vendors.php');?>
		
		<script src="<?php echo $config->get('assetsurl'); ?>/tpl/post_edit_tpl.js"></script>
		
		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-js-bootstrap.js"></script>
		
		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-render-post-edit-form.js"></script>
		
		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-save-post-info.js"></script>
		
		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-location.js"></script>
		
		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-ranger-tick.js"></script> 
		<link href="<?php echo $config->get('assetsurl');?>/css/ranger-tick.css" rel="stylesheet">
		
		
		<script>
			
			/*jslint unparam: true */
			/*global window, $ */
			$(function () {
				'use strict';
				gpi_waiter.show();
				apiRequest({request: 'bootstrap'}, function(data){ 
					if (data.type!=='success') { alert('Some error with API!'); return false; }
					if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
					data.opts = data.opts.message;
					console.log(data);
				});
				
				var queryString = document.location.search.match(/\?id=([\d]+)/i);
				if (typeof queryString[1] !== 'undefined') {
					apiRequest({request: 'get_post_by_id', id: queryString[1]}, function(data){
						if (data.type!=='success') { alert('Some error with API!'); return false; }
						if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						renderPostEditForm(data.opts[0], post_edit_tpl, '#edit-post-form-placeholder');
						location_init('.post_edit_block[data-post-id='+queryString[1]+']');
						gpi_waiter.hide();
					});
				} else {
					$('main[role=main].container').html('Error in ID...');
					gpi_waiter.hide();
				}
				

				$('#edit-post-form-placeholder').on('click','.save-post-info-btn', function(){
					var post_id = $(this).data('post-id');
					save_post_info(post_id);
				});
				
				$('#edit-post-form-placeholder').on('click', '.delete-post', function(e){
					e.preventDefault();
					//if (prompt("Type 'yes' to delete!","no")!='yes') return false;
					if (confirm("Нажмите OK для удаления!",)!=true) return false;
					var id = $(this).data('post-id');
					apiRequest({
						request: 'delete',
						id: id
					},function(data){
						if (data.type!=='success') { alert('Some error with API!'); return false; }
						if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						$('#post_id_'+id).slideUp(function(){ 
							$(this).remove(); 
							$('#edit-post-form-placeholder').html('<div class="text-center"><a href="'+gpi_url+'/library">Перейти к библиотеке постов...</a></div>');
						});
						
						notify(data.type,data.message);
					});
				});
				
				
				
				
			});
			
			
			
		</script>
		
		
	</body>
</html>
