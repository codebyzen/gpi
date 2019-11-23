		<?php include($config->get('themepath').'/header.php');?>
		<style>
			.row-hover {
				padding: 0.3em;
			}
			
			.row-hover:nth-child(odd) {
				background-color: rgba(0,0,0,0.01);
			}
			.row-hover:nth-child(even) {
				background-color: rgba(0,0,0,0.03);
			}
			.row-hover:hover {
				background-color: rgba(0,0,0,0.08);
			}
		</style>
		<main role="main" class="container">
			
			<div class="my-3 p-3 bg-white rounded shadow-sm">
				<div id="user_list">
				<?php 
					$users = $db->query("SELECT * FROM `users`");
					if ($users!==false) foreach($users as $k=>$v){
						echo '<div class="row-hover" id="user_row_'.$v->id.'">'.$v->login;
						echo '	<div class="action-fa-wrapper float-right">';
						echo '		<i class="fa fa-pen text-success cursor-pointer action-fa-button user_edit" data-user-id="'.$v->id.'"></i>';
						echo '		<i class="fa fa-lg fa-times text-danger cursor-pointer action-fa-button user_delete" data-user-id="'.$v->id.'"></i>';
						echo '	</div>';
						echo '	<div class="edit_panel"></div>';
						echo '</div>';
					}
				?>
				</div>
			</div>

		</main>

		<?php include($config->get('themepath').'/footer.php');?>
		
		<?php include($config->get('themepath').'/js-vendors.php');?>

		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-js-bootstrap.js"></script>
		
		<script>
			$(function () {
				$('#user_list').on('click', '.user_delete', function(){
					var location_id = $(this).data('user-id');
					if (prompt("Type 'yes' to delete!","yes")!='yes') return false;
					apiRequest({request: 'user_delete', id: location_id}, function(data){
						if (data.type!=='success') { alert('Some error with API!'); return false; }
						if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						if (data.type=='success') {
							notify('success', 'User removed...');
							$('#user_row_'+location_id).slideUp(function(){
								$(this).remove();
							});
						} else {
							notify('error', data.message);
						}
					});
					
				});
				
				
				$('#user_list').on('click', '.user_edit', function(){
					var tpl = `
						<div class="edit_panel" data-user-id="{%user_id%}">
							<input name="id" type="hidden" class="form-control" value="{%user_id%}">
							<input name="login" type="text" class="form-control" placeholder="Login" value="{%login%}">
							<input name="email" type="email" class="form-control" placeholder="Email" value="{%email%}">
							<input name="password" type="password" class="form-control" placeholder="Password... in you want to change...">
							<button class="btn btn-sm btn-success btn-user-save" data-user-id="{%user_id%}">Save</button>
							<button class="btn btn-sm btn-warning btn-user-cancel" data-user-id="{%user_id%}">Cancel</button>
						</div>
					`;
					
					apiRequest({request:'user_by_id', id: $(this).data('user-id')}, function(data){
						if (data.type!=='success') { alert('Some error with API!'); return false; }
						if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						var current_tpl = tpl;
						current_tpl = current_tpl.replace(new RegExp('{%user_id%}', 'g'), (data.opts.id==null ? '' : data.opts.id));				
						current_tpl = current_tpl.replace(new RegExp('{%login%}', 'g'), (data.opts.login==null ? '' : data.opts.login));
						current_tpl = current_tpl.replace(new RegExp('{%email%}', 'g'), (data.opts.email==null ? '' : data.opts.email));
						
						gpi_waiter.show();
						
						var selector = '#user_list .row-hover#user_row_'+data.opts.id+' .edit_panel';
						$(selector).hide(function(){
							$(this).html(current_tpl);
							$(this).slideDown();
							gpi_waiter.hide();
						});
					});
				});
				
				$('#user_list').on('click', '.btn-user-save', function(){
					var user_id = $(this).data('user-id');

					gpi_waiter.show();
					var selector = '#user_list .row-hover#user_row_'+user_id+' .edit_panel';
					var fields = {};
					$(selector).find('.form-control').each(function(){
						fields[$(this).prop('name')] = $(this).val();
					});
					fields.request = 'user_edit';
					console.log(fields);
					apiRequest(fields, function(data){
						if (data.type!=='success') { alert('Some error with API!'); return false; }
						if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						$(selector).slideUp();
						gpi_waiter.hide(function(){
							document.location.reload();
						});
					});

				});
				
				$('#user_list').on('click', '.btn-user-cancel', function(){
					var selector = '#user_list .row-hover#user_row_'+$(this).data('user-id')+' .edit_panel';
					$(selector).slideUp();
				});
				
			});
		</script>
		
		
	</body>
</html>