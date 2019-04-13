var post_edit_tpl = `
	<div class="container-fluid post_edit_block" style="padding:0;" id="post_id_{%id%}" data-post-id="{%id%}">
		<div class="row">
			<div class="col-md-4 col-lg-3 col-12">
				<a href="{%linkoriginal%}" target=_blank><img class="rounded img-thumbnail mb-3" src="{%image%}" style="width:100%;"></a>
				<dl class="row small">
					{%file_info%}
				</dl>
			</div>
			<div class="col-md-8 col-lg-9 col-12">
				<button class="close delete-post" aria-label="Delete" data-post-id="{%id%}"><span aria-hidden="true">&times;</span></button>


				<div class="form-group">
					<label class="">Заголовок</label>
					<input class="form-control" name="caption" type="text" placeholder="Art picture..." value="{%caption%}">
					<small class="form-text text-muted">Не публикуется, только для администраторов.</small>
				</div>
				<div class="form-group">
					<label class="">Описание</label>
					<textarea class="form-control d-inline-block" name="description" placeholder="Описание">{%description%}</textarea>
					<small class="form-text text-muted">Описание с тегами (публикуется).</small>
				</div>
				<div class="form-group clearfix">
					<label class="">Теги</label>
					<textarea class="form-control d-inline-block" name="tags" placeholder="art picture painting">{%tags%}</textarea>
					<div class="tags-hints"></div>
					<small class="form-text text-muted float-left">писать через пробел, приклеивается к описанию, публикуется</small>
					<small class="d-none float-right tags-warning text-danger"><strong>Больше 30 тегов!</strong></small>
				</div>
				<div class="form-group">
					<label class="">Локация</label>
					<div class="location_root">
						<div style="position:relative;">
							<input class="form-control" type="text" name="location" placeholder="Место" value="{%location_name%}">
							<input type="hidden" class="form-control" name="facebook_places_id" value="{%facebook_places_id%}">
							<button class="close text-danger clear_location" aria-label="Delete" style="display:block;position:absolute;top:0;right:0;padding:0.3em;"><span aria-hidden="true">×</span></button>
						</div>
						<small class="form-text text-muted">Публикуется</small>
						<label><input type="radio" name="location_source" value="instagram"> Instagram</label> <label><input type="radio" checked name="location_source" value="db"> Сохраненные </label>
						<button class="btn btn-sm btn-success float-right btn-location-request">Найти</button>
						<div class="location_results" style="background-color:rgba(0,0,0,0.1);padding 1rem;"></div>
					</div>
				</div>
				<div class="form-group">
					<label class="">Источник</label>
					<input class="form-control" type="text" name="source" placeholder="https://... или автор" value="{%source%}">
					<small class="form-text text-muted">Не публикуется, только для администраторов.</small>
				</div>
				<div class="form-group">
					<label class="">Куда опубликовать?</label>
					<div class="row">
						<div class="col-4">
							<label><input class="form-control inline" type="checkbox" name="net_instagram" value="instagram" checked> <i class="fab fa-instagram"></i> Instagram</label>
						</div>
						<div class="col-4">
							<label><input class="form-control inline" type="checkbox" name="net_telegram" value="telegram" > <i class="fab fa-telegram"></i> Telegram</label>
						</div>
						<div class="col-4">
							<label><input class="form-control inline" type="checkbox" name="net_vk" value="vk" > <i class="fab fa-vk"></i> ВКонтакте</label>
						</div>
					</div>
				</div>
				<div class="form-group">

					<div class="row">
						<div class="col-12">
							<div id="gpi-ranger-tick-{%id%}">
								<input class="gpi-time-range" type="range" title="5 минутные отрезки дня" min="0" max="287">
								<div class="gpi-time-range-ticks" style="position:relative;"></div>
								<br>
								<div class="row">
									<div class="col-6">
										<input class="form-control" type="date" name="scheduled_date" value="{%scheduled_date%}">
									</div>
									<div class="col-6">
										<input type="time" name="scheduled_time" class="form-control gpi-time-time-test" value="{%scheduled_time%}">
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="row">
						<div class="col-12">
							<label><input class="form-control" style="display:inline;width:auto;height:auto;" type="checkbox" name="ready"> Готов к публикации?</label>
						</div>
					</div>
				</div>

				<button class="btn btn-outline-success float-right mt-2 save-post-info-btn" data-post-id="{%id%}">Save</button>
			</div>
		</div>
		<hr>
	</div>
`