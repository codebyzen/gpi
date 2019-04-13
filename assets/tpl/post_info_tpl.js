var post_info = `
	<div class="container-fluid" id="post_id_{%id%}">
		<div class="row">
			<div class="col col-sm-5 col-md-2 text-center">
				<a href="{%url%}" target="_blank"><img class="rounded" src="{%image%}" width=128 height=128></a>
			</div>
			<div class="col col-sm-7 col-md-3 small">
				<dl class="row">
					<dt class="col-sm-3">Тип</dt>
					<dd class="col-sm-9">{%type%}</dd>

					<dt class="col-sm-3">Сети</dt>
					<dd class="col-sm-9">{%networks%}</dd>

					<dt class="col-sm-3">Когда</dt>
					<dd class="col-sm-9">{%date_scheduled%}</dd>

					<dt class="col-sm-3">Ссылка</dt>
					<dd class="col-sm-9">{%network_post_link%}</dd>
				</dl>
			</div>
			<div class="col-sm-12 d-sm-block d-md-none text-muted text-left small lh-125 ">
				{%human_filesize%} {%container%} {%codec%} {%duration%} #{%id%}
			</div>
			<div class="col-md-4 col-sm-12 small lh-125 ">
					<strong class="text-gray-dark">{%status%} {%caption%}</strong>
					<span class="d-block">{%description%}</span>			
					<br>
					<a class="btn btn-sm btn-success post_id_now" data-file-id="{%id%}" href="#">Опубликовать</a>
					<a class="btn btn-sm btn-info" href="{%url%}/post?id={%id%}">Править</a>
					{%clear_publish_status%}
					<a class="btn btn-sm btn-warning delete-post" href="#" data-post-id="{%id%}">Удалить</a>
			</div>
			<div class="col col-sm-3 d-none d-md-block text-muted text-right small lh-125 ">
				{%human_filesize%} {%container%} {%codec%} {%duration%} #{%id%}
			</div>
		</div>
	</div>
	<hr>
`;