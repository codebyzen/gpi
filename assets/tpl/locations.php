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
				<div id="location_list">
				<?php 
					$locations_result = $db->query("SELECT * FROM `locations`");
					if ($locations_result!==false) foreach($locations_result as $k=>$v){
						echo '<div class="row-hover" id="location_row_'.$v->id.'"><a href="https://yandex.ru/maps/?text='.$v->lat.','.$v->lng.'&z=14" target=_blank>'.$v->name.'</a>';
						echo '<button class="close delete_location" aria-label="Delete" data-location-id="'.$v->id.'"><span >×</span></button>';
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
				$('#location_list').on('click', 'button.close.delete_location', function(){
					var location_id = $(this).data('location-id');
					if (prompt("Type 'yes' to delete!","yes")!='yes') return false;
					apiRequest({request: 'location_delete', id: location_id}, function(data){
						if (data.type!=='success') { alert('Some error with API!'); return false; }
						if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						if (data.type=='success') {
							notify('success', 'Location removed...');
							$('#location_row_'+location_id).slideUp(function(){
								$(this).remove();
							});
						} else {
							notify('error', data.message);
						}
					});
					
				});
			});
		</script>
		
		
	</body>
</html>