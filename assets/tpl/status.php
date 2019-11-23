<?php include($config->get('themepath').'/header.php');?>


		<main role="main" class="container">
			<div class="my-3 p-3 bg-white rounded shadow-sm">
				<h6 class="border-bottom border-gray pb-2 mb-3">Состояние скриптов</h6>
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
				
				apiRequest({request:'get_dashboard_info'}, function(data){
					if (data.type!=='success') { alert('Some error with API!'); return false; }
					if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
					data.opts = data.opts.message;
					console.log(data);
					var tpl = `
					<dl class="row">
						<dt class="col-sm-2" style="text-transform: capitalize;">{%name%}</dt>
						<dd class="col-sm-10">{%value%}</dd>
					</dl>
					`;
					var html = '';
					for (var i in data.opts) {
						for (var ii in data.opts[i]) {
							var _tpl = tpl;
							_tpl = _tpl.replace(new RegExp(/{%name%}/, 'g'), ii.replace(new RegExp(/_/,'g'), " "));
							_tpl = _tpl.replace(new RegExp(/{%value%}/, 'g'), data.opts[i][ii]);
							html += _tpl;
						}
					}

					$('#dashboard-info').append(html);
				});
				gpi_waiter.hide();

				

			});
			

		
			
		</script>
	</body>
</html>
