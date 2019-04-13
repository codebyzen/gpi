/* render and update DOM from request name */
function update_posts_preview(from, tpl, target_object, apiRequestName, filter){
	window.gpi_loaded_previews_from = from;
	apiRequest(
			{
				request: apiRequestName,
				from: from,
				filter: filter
			}, 
			function(data){
				
				if (data.type!=='success') { alert('Some error with API!'); return false; }
				if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
				data.opts = data.opts.message;
				
				if (data.opts==false) {
					if (window.gpi_loaded_previews_from!=0) {
					    console.log('No more date... Load last...');
					    notify('info','No more posts...');
						update_posts_preview(window.gpi_loaded_previews_from - 10, tpl, target_object, apiRequestName, filter);
					}
				}

				var posts_tpl_preview = '';
				for (var i in data.opts) {
					posts_tpl_preview += render_posts_preview(data.opts[i], tpl);
				}
				$(target_object).append('<div style="display:none;" class="hidden-posts-list">'+posts_tpl_preview+'</div>');
				$(target_object+' .hidden-posts-list').slideDown();

			}
	)
}

/* render post preview html drom data object */
function render_posts_preview(post, tpl){
	console.log(post);
	var post_tpl_preview = tpl;

	post_tpl_preview = post_tpl_preview.replace('{%image%}', gpi_url+'/upload/thumbnail/'+post.thumbnail);
	post_tpl_preview = post_tpl_preview.replace('{%url%}', gpi_url+'/upload/ready/'+post.name);

	if (post.networks && post.networks!=null) {
		var networks = post.networks;
		var networks_tpl = '<i class="fab fa-lg fa-{%name%}"></i> ';
		var networks_html = '';
		for (var net_key in networks) {
			networks_html+=networks_tpl.replace('{%name%}', networks[net_key]);									
		}
		post_tpl_preview = post_tpl_preview.replace('{%networks%}', networks_html);
	} else {
		post_tpl_preview = post_tpl_preview.replace('{%networks%}', '<strong class="text-danger">Не назначены</strong>');
	}


	var status_tpl = '<i class="fas text-muted fa-check"></i>';	
	if (post.ready == 1) status_tpl = '<i class="fas text-info fa-check"></i>';
	if (post.published == 1) status_tpl = '<i class="fas text-info fa-check-double"></i>';
	post_tpl_preview = post_tpl_preview.replace('{%status%}', status_tpl);

	post_tpl_preview = post_tpl_preview.replace('{%type%}', post.type);

	if (!post.scheduled) post.scheduled = 'Undefined';
	post_tpl_preview = post_tpl_preview.replace('{%date_scheduled%}', (post.date_scheduled ? post.date_scheduled.replace("T", " / "): '-'));
	if (!post.caption) post.caption='Без заголовка';

	if (post.published==1) {
		clear_publish_status_btn = '<a class="btn btn-sm btn-danger clear_publish_status" href="#" data-file-id="'+post.id+'">Clear status</a>';
	} else {
		clear_publish_status_btn = '';
	}
	post_tpl_preview = post_tpl_preview.replace('{%clear_publish_status%}', clear_publish_status_btn);

	post_tpl_preview = post_tpl_preview.replace(new RegExp(/(\{%caption%\})/, 'g'), post.caption);
	post_tpl_preview = post_tpl_preview.replace(new RegExp(/(\{%human_filesize%\})/, 'g'), humanSize(post.size,2));
	post_tpl_preview = post_tpl_preview.replace(new RegExp(/(\{%container%\})/, 'g'), post.container);
	post_tpl_preview = post_tpl_preview.replace(new RegExp(/(\{%codec%\})/, 'g'), post.codec==false ? '' : post.codec);
	post_tpl_preview = post_tpl_preview.replace(new RegExp(/(\{%duration%\})/, 'g'), post.duration==0 || post.duration==false ? '' : post.duration);

	post_tpl_preview = post_tpl_preview.replace(new RegExp(/(\{%network_post_link%\})/, 'g'), post.additional!=null && post.additional!='' && post.additional!=0 ? '<a href="https://instagram.com/p/'+post.additional+'" target=_blank>ссылка</a>' : '');
	
	

	if (!post.description) post.description='';

	if (typeof post.tags == 'undefined' || !post.tags) post.tags = '';
	post.tags = post.tags.replace(new RegExp(/\s/, 'g'), ' #');
	if (post.tags!='') {
		post.tags = '#'+post.tags;
	}

	post_tpl_preview = post_tpl_preview.replace('{%description%}', post.description.replace(new RegExp(/\n/, 'g'), '<br>') + "<br><br>" + post.tags);


	post_tpl_preview = post_tpl_preview.replace(new RegExp(/(\{%id%\})/, 'g'), post.id);
	post_tpl_preview = post_tpl_preview.replace('{%url%}', gpi_url);
	
	return post_tpl_preview;
	
}