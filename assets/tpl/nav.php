		<nav class="navbar navbar-expand-lg fixed-top navbar-dark bg-dark">
			<a class="navbar-brand mr-auto mr-lg-0" href="<?php echo $config->get('url');?>">GoPostIt</a>
			<button class="navbar-toggler p-0 border-0" type="button" data-toggle="offcanvas">
			<span class="navbar-toggler-icon"></span>
			</button>

			<div class="navbar-collapse offcanvas-collapse" id="navbarsExampleDefault">
				<ul class="navbar-nav mr-auto">
					<li class="nav-item <?php echo ($router->path[0]=='') ? 'active' : '';?>">
						<a class="nav-link" href="<?php echo $config->get('url');?>">Главная</a>
					</li>
					
					<li class="nav-item dropdown <?php echo ($router->path[0]=='cards' || $router->path[0]=='users' || $router->path[0]=='networks') ? 'active' : '';?>">
						<a class="nav-link dropdown-toggle" href="<?php echo $config->get('url');?>" id="dropdown00" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Библиотека</a>
						<div class="dropdown-menu" aria-labelledby="dropdown01">
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/library/all">Все<br><small class="text-muted mt-0">Все медиа в порядке загрузки в систему</small></a>
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/library/draft">Не готовые<br><small class="text-muted mt-0">Не описанные медиа</small></a>
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/library/query">Очередь<br><small class="text-muted mt-0">Задания на публикацию</small></a>
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/library/published">Опубликованные<br><small class="text-muted mt-0">Просмотр описания опубликованных медиа</small></a>
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/library/errors">С ошибками<br><small class="text-muted mt-0">Информация о медиа неопубликованных<br> в результате ошибок</small></a>
						</div>
					</li>
					
					<li class="nav-item <?php echo ($router->path[0]=='schedule') ? 'active' : '';?>">
						<a class="nav-link" href="<?php echo $config->get('url');?>/schedule">Очередь</a>
					</li>
					<li class="nav-item <?php echo ($router->path[0]=='actions') ? 'active' : '';?>">
						<a class="nav-link" href="<?php echo $config->get('url');?>/actions">Хаки</a>
					</li>
					<li class="nav-item dropdown <?php echo ($router->path[0]=='cards' || $router->path[0]=='users' || $router->path[0]=='networks') ? 'active' : '';?>">
						<a class="nav-link dropdown-toggle" href="<?php echo $config->get('url');?>" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Настройки</a>
						<div class="dropdown-menu" aria-labelledby="dropdown01">
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/cards">Карточки<br><small class="text-muted mt-0">Заготовки для постов</small></a>
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/locations">Локации<br><small class="text-muted mt-0">Библиотека локаций</small></a>
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/users">Пользователи<br><small class="text-muted mt-0">Управление пользователями</small></a>
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/networks">Сети<br><small class="text-muted mt-0">Настройки социальных сетей</small></a>
							<a class="dropdown-item lh-125" href="<?php echo $config->get('url');?>/status">Статус<br><small class="text-muted mt-0">Состояние скриптов и базы данных</small></a>
						</div>
					</li>
				</ul>
				
				<div>
					<ul class="navbar-nav mr-auto">
						<li class="nav-item">
							<a class="nav-link" href="<?php echo $config->get('url');?>/?action=logout">Выйти</a>
						</li>
					</ul>					
				</div>
			</div>
		</nav>