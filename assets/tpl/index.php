<?php include($config->get('themepath').'/header.php');?>


		<main role="main" class="container">
		
		

			<div class="d-flex align-items-center p-3 my-3 text-white-50 bg-purple rounded shadow-sm" id="dropzone">
				<div class="text-center container-fluid">
					<h6 class="mb-0 text-white">Перетащите файлы сюда</h6>
					<small>или кликните для выбора файлов</small>
				</div>
				<input id="fileupload" type="file" name="files[]" multiple="multiple">
			</div>
			
			<div class=" align-items-center p-3 my-3 rounded shadow-sm" id="url_grabber">
				<small class="text-muted">Ссылка на пост в Instagram или Coub:</small>
				<div class="d-flex text-white-50 ">
					<input class="form-control" type="text" placeholder="">
					<button class="btn btn-info">Скачать</button>
				</div>
				<div id="url_grabber_options" class="d-none">
					<strong>Как склеить?</strong>
					<label>
						<input type="radio" name="multiplex_by" value="audio" checked="checked">
						склеить по аудио
					</label>
					<label>
						<input type="radio" name="multiplex_by" value="video">
						склеить по видео
					</label>
					<br>
					<strong>Фон</strong>
					<label>
						<input type="radio" name="background" value="blurred" checked="checked">
						Размыть
					</label>
					<label>
						<input type="radio" name="background" value="black">
						Черный
					</label>
					<label>
						<input type="radio" name="background" value="white">
						Белый
					</label>
					<br>
					<strong>Обрезать</strong>
					<label>
						Точка входа
						<input type="number" name="in_t" value="0" min="0">
					</label>
					<label>
						Точка выхода
						<input type="number" name="out_t" value="59">						
					</label>
				</div>
				<div class="text-muted">
				    <small>Дополнительные сервисы: <a href="https://loadit.xyz/" target=_blank>loadit.xyz</a> или <a href="https://mycoub.ru/"  target=_blank>mycoub.ru</a></small>
				</div>
			</div>
			

			<!-- The global progress bar -->
			<div id="progress" class="progress">
				<div class="progress-bar progress-bar-success"></div>
			</div>
			
			<div class="my-3 p-3 bg-white rounded shadow-sm d-none" id="just_uploaded_previews">
				<h6 class="border-bottom border-gray pb-2 mb-4">Загруженные...</h6>
				
				<div id="post-placeholder">
					
				</div>
			</div>

		</main>

		<?php include($config->get('themepath').'/footer.php');?>


		<?php include($config->get('themepath').'/js-vendors.php');?>

		<script src="<?php echo $config->get('assetsurl'); ?>/tpl/post_info_tpl.js"></script>

		<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
		<script src="<?php echo $config->get('assetsurl');?>/jQuery-upload/js/vendor/jquery.ui.widget.js"></script>
		<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
		<script src="<?php echo $config->get('assetsurl');?>/jQuery-upload/js/jquery.iframe-transport.js"></script>
		<!-- The basic File Upload plugin -->
		<script src="<?php echo $config->get('assetsurl');?>/jQuery-upload/js/jquery.fileupload.js"></script>
		
		<script src="<?php echo $config->get('assetsurl');?>/js/gpi-js-bootstrap.js"></script>

		<script src="<?php echo $config->get('assetsurl'); ?>/js/gpi-render-post-preview.js"></script>

		<script>
			
			
			
			$('#dropzone').bind('dragover', function (e) {
				$(this).addClass('dragover');
				return false;
			});

			$('#dropzone').bind('dragleave', function(e) {
				$(this).removeClass('dragover');
				return false;
			});
			
			$('#dropzone').bind('drop', function(event) {
				event.preventDefault();
				$(this).removeClass('dragover');
				//dropZone.addClass('drop');
			});

			function getFileExt(name) {
				var splited = name.split(".");
				var ext = false;
				if (splited.length==2 && splited[0]=='') return ext;
				if (splited.length<=1) return ext;
				ext = splited[splited.length-1].trim();
				return ext=='' ? false : ext;
			}
			
			/*jslint unparam: true */
			/*global window, $ */
			$(function () {
				'use strict';

				$('#fileupload').fileupload({
					dropZone: $('#dropzone'),
					timeout: 180000,
					maxChunkSize: 2000000, // 2 MB
					add: function (e, data) {
						//console.log(data);
						var count = data.files.length;
						var i;
						for (i = 0; i < count; i++) {
							data.files[i].uploadName = Date.now() + '_' + Math.round(Math.random() * 100) + '_' + Math.round(Math.random() * 100) + '.' + getFileExt(data.files[i].name);
						}
						
						var fu = $(this).data('blueimp-fileupload') || $(this).data('fileupload');
						var that = this;
						
						for (i = 0; i < count; i++) {
							$.getJSON(window.gpi_url+'/app/uploader/uploader.php', {file: data.files[i].name, maxChunkSize: fu.options.maxChunkSize}, function (result) {
								//console.log(result);
								var file = result.file;
								data.uploadedBytes = file && file.size;
								$.blueimp.fileupload.prototype.options.add.call(that, e, data);
							});
						};
						data.submit();
					},
					url: window.gpi_url+'/app/uploader/uploader.php',
					dataType: 'json',
					done: function (e, data) {
						$.each(data.result.files, function (index, data) {
							if (!$('#just_uploaded_previews').is(':visible')) {
								$('#just_uploaded_previews').hide().removeClass('d-none').slideDown();
							}
							var post_html = render_posts_preview(data, post_info);
							$('#just_uploaded_previews #post-placeholder').append(post_html);
							$('#progress .progress-bar').css({'width': '0%'});
						});
					},
//					success: function (result, textStatus, jqXHR) { console.log("SUCCESS", result, textStatus, jqXHR); },
					error: function (jqXHR, textStatus, errorThrown) { 
						console.log("ERROR");
						console.log("jqXHR: ", jqXHR);
						console.log("textStatus", textStatus);
						console.log("errorThrown", errorThrown);
					},
//					complete: function (result, textStatus, jqXHR) { console.log("COMPLETE",result, textStatus, jqXHR); },
					progressall: function (e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						$('#progress .progress-bar').css(
							'width',
							progress + '%'
						);
					}
				}).prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled');

				
		
				/**
				 * Bootstrap (check db tables)
				 */
				gpi_waiter.show();
				apiRequest({request: 'bootstrap'}, function(data){ 
					if (data.type!=='success') { alert('Some error with API!'); return false; }
					if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
					data.opts = data.opts.message;
					//console.log(data); 
				});
				gpi_waiter.hide();

				/**
				 * Delete 
				 */
				$('#just_uploaded_previews').on('click', '.delete-post', function(e){
					e.preventDefault();
					//if (prompt("Type 'yes' to delete!","no")!='yes') return false;
					if (confirm("Нажмите OK для удаления файла!")!=true) return false;
					var id = $(this).data('post-id');
					gpi_waiter.show();
					apiRequest({
						request: 'delete',
						id: id
					},function(data){
						if (data.type!=='success') { alert('Some error with API!'); return false; }
						if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
						data.opts = data.opts.message;
						$('#post_id_'+id).slideUp(function(){ $(this).remove(); });
						gpi_waiter.hide();
						notify(data.type,data.message);
					});
				});
				
				
				$('#url_grabber').on('change keyup click','input[type=text]', function(){
					var url = $('#url_grabber input[type=text]').val();
					if (url=='') return false;
					var url_parsed = new URL(url);
					if (url_parsed.hostname=='coub.com') {
						$('#url_grabber_options').removeClass('d-none');
					} else {
						$('#url_grabber_options').addClass('d-none');
					}
				});
				
				/**
				 * Grab from Instagram or Coub
				 */
				$('#url_grabber').on('click', 'button', function(){
					gpi_waiter.show();
					var inp_url = $('#url_grabber input').val();
					var background = $('#url_grabber_options input[type=radio][name="background"]:checked').val();
					
					var in_t = $('#url_grabber_options input[type=number][name="in_t"]').val();
					var out_t = $('#url_grabber_options input[type=number][name="out_t"]').val();
					
					
					var url_parsed = new URL(inp_url);
					if (url_parsed.hostname=='coub.com') {
						var task_type = 'multiplex_by_'+$('#url_grabber_options input[type=radio][name="multiplex_by"]:checked').val();
					} else {
						var task_type = 'keep';
					}
					
					
					apiRequest({request:'social_grabber', url: inp_url, task_type: task_type, background: background, in: in_t, out: out_t}, function(data){
						if (data.type!=='success') {
							alert('Some error with API!'); 
							gpi_waiter.hide();
							return false; 
						}
						if (data.opts.type!=='success') { 
							alert('Some error with API engine:\n'+data.opts.message); 
							gpi_waiter.hide();
							return false; 
						}
						data.opts = data.opts.message;
						notify(data.type, data.message);
						if (data.type!="error") {
							gpi_waiter.hide();
							if (!$('#just_uploaded_previews').is(':visible')) {
								$('#just_uploaded_previews').hide().removeClass('d-none').slideDown();
							}
							var post_html = render_posts_preview(data.opts, post_info);
							$('#just_uploaded_previews #post-placeholder').append(post_html);
						} else {
							alert('Some error with API engine:\n'+data.opts.message); 
							gpi_waiter.hide();
						}
						
					});
				});

			});
			


						
			
			
		</script>
	</body>
</html>
