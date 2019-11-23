		<?php include($config->get('themepath').'/header.php');?>


		<main role="main" class="container">
			<div class="my-3 p-3 bg-white rounded shadow-sm">
				<h6 class="border-bottom border-gray pb-2 mb-3">Автоматизация процессов</h6>

				Стерилизация системы<br>
				<button class="btn btn-danger btn-sm" id="action_sterilise">Выполнить</button>		
				
				<hr>
				
				Подписаться из поста<br>
				<input class="form-control" placeholder="Ссылка на пост в инстаграме">
				<button class="btn btn-danger btn-sm">Подписаться на всех</button>
				<button class="btn btn-warning btn-sm">Подписаться на первых 100</button>
				<button class="btn btn-info btn-sm">Подписаться на последних 100</button>
				<hr>
				
				Подписаться на всех кто подписался на меня
				<button class="btn btn-success btn-sm">Сделать</button>
				<hr>

				Обновить данные о подписках и подписчиках
				<button class="btn btn-success btn-sm">Сделать</button>
				<hr>
				
				Сообщение для всех кто подписался на меня
				<textarea class="form-control"></textarea>
				<button class="btn btn-success btn-sm">Сохранить</button>
				<hr>
				
				Подписчики / кто / когда / ссылка<br>
				Подпики / кто / когда / взаимно ли / ссылка<br>
				<button class="btn btn-danger btn-sm">Убрать всех не взаимных</button>
				<button class="btn btn-warning btn-sm">Убрать всех не взаимных на кого подписались больше недели назад</button>
				<button class="btn btn-info btn-sm">Убрать всех не взаимных на кого подписались больше месяца назад</button>
				<button class="btn btn-danger btn-sm">Убрать всех на кого подписались больше недели назад</button>
				<button class="btn btn-danger btn-sm">Убрать всех на кого подписались больше месяца назад</button>
				<hr>
				
			
			</div>
		</main>

		<?php include($config->get('themepath').'/footer.php');?>

		<?php include($config->get('themepath').'/js-vendors.php');?>

		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-js-bootstrap.js"></script>

		
		<script>
		
			$(function () {
				'use strict';
				
				$(document).on('click','#action_sterilise', function(){
					if (confirm('Sure to do this?')==false) return false;
					apiRequest({request: 'sterilise'}, function(data){ 
						if (data.type!=='success') { alert('Some error with API!'); return false; }
						alert('Sterilised succesful!');						
					});
				});
				
				/*
				apiRequest({request: 'bootstrap'}, function(data){ 
					if (data.type!=='success') { alert('Some error with API!'); return false; }
					if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
					data.opts = data.opts.message;
					update_posts_preview(0, post_info, '#posts_preview', 'posts_library');
				});
				*/

			});

		</script>
		
		
		
	</body>
</html>