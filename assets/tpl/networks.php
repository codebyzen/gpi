<?php include($config->get('themepath').'/header.php');?>


		<main role="main" class="container">
			<div class="my-3 p-3 bg-white rounded shadow-sm">
				<h6 class="border-bottom border-gray pb-2 mb-3">Настройка сетей</h6>
				<div id="dashboard-info" class="small lh-100"></div>
			</div>
		</main>

		<?php include($config->get('themepath').'/footer.php');?>


		<?php include($config->get('themepath').'/js-vendors.php');?>

		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-js-bootstrap.js"></script>
		
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
				gpi_waiter.hide();
			});
			

		
			
		</script>
	</body>
</html>
