function renderPostEditForm(data, post_edit_tpl, destination_parent) {

	var file_info = '';
	var infoItemTitle = '<dt class="col-1 col-md-2">{%title%}</dt>';
	var infoItemVal = '<dd class="col-5 col-md-10 mb-0">{%value%}</dd>';
	info_fields = {
		date_added: {
			name: 'Date', 
			get: function(data){
				return (typeof data.date_added!='undefined') ? data.date_added : null;
			}
		},
		type: {
			name: 'Type', 
			get: function(data){ 
				return (typeof data.type!='undefined') ? data.type : null;
			}
		},
		container: {
			name: 'Cont.', 
			get: function(data){ 
				return (typeof data.container!='undefined') ? data.container : null;
			}
		},
		duration: {
			name: 'Dur.', 
			get: function(data){ 
				return (typeof data.duration!='undefined' && data.duration!=0) ? data.duration : null;
			}
		},
		size: {
			name: 'Size', 
			get: function(data){ 
				return (typeof data.size!='undefined') ? humanSize(data.size,2) : null;
			}
		}
	};
	for (var i in info_fields) {
		var name = info_fields[i].name;
		var value = info_fields[i].get(data);
		var fileInfoItem = '';
		if (value!==null) {
			fileInfoItem += infoItemTitle.replace('{%title%}', name);
			fileInfoItem += infoItemVal.replace('{%value%}', value);
		}
		file_info += fileInfoItem;
	}
	
	var post_edit_tpl_current = post_edit_tpl;

	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%location_name%}', 'g'), (data.location_name==null ? '' : data.location_name));
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%facebook_places_id%}', 'g'), (data.facebook_places_id==null ? '' : data.facebook_places_id));
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%location_lat%}', 'g'), (data.location_lat==null ? '' : data.location_lat));
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%location_lng%}', 'g'), (data.location_lng==null ? '' : data.location_lng));
	
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%caption%}', 'g'), (data.caption==null ? '' : data.caption));
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%description%}', 'g'), (data.description==null ? '' : data.description));
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%tags%}', 'g'), (data.tags==null ? '' : data.tags));	
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%source%}', 'g'), (data.source==null ? '' : data.source));
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%linkoriginal%}', 'g'), (data.name==null ? '#' : window.gpi_url+'/upload/original/'+data.name));
	
	
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%id%}', 'g'), data.id);
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%fileName%}', 'g'), data.name);
	if (data.type==='video') {
		post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%image%}', 'g'), window.gpi_url+'/upload/thumbnail/'+data.thumbnail);
	} else {
		post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%image%}', 'g'), window.gpi_url+'/upload/ready/'+data.name);
	}
	
	if (data.date_scheduled!=null) {
		var tomorrow = new Date(data.date_scheduled);
	} else {
		var tomorrow = new Date(new Date().getTime() + (24 * 60 * 60 * 1000));
	}
	
	var f_n_month = function(tomorrow){ var d = tomorrow.getMonth() + 1; return (d.toString().length==1) ? "0"+d.toString() : d; }
	var f_n_day = function(tomorrow){ var d = tomorrow.getDate(); return (d.toString().length==1) ? "0"+d.toString() : d; }
 	var f_date = tomorrow.getFullYear()+'-'+(f_n_month(tomorrow))+'-'+(f_n_day(tomorrow));

	var f_n_hours = function(tomorrow){ var d = tomorrow.getHours(); return (d.toString().length==1) ? "0"+d.toString() : d; }
	var f_n_minutes = function(tomorrow){ var d = tomorrow.getMinutes(); return (d.toString().length==1) ? "0"+d.toString() : d; }
	var f_time = f_n_hours(tomorrow) + ':' + f_n_minutes(tomorrow);
	
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%scheduled_date%}', 'g'), f_date);
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%scheduled_time%}', 'g'), f_time);
	
	post_edit_tpl_current = post_edit_tpl_current.replace(new RegExp('{%file_info%}', 'g'), file_info);
	
	post_edit_tpl_current = $(post_edit_tpl_current);
	
	if (data.networks!=null) {
		var networks = data.networks.split(';');
		for(i in networks) {
			$(post_edit_tpl_current).find("input[name=net_"+networks[i]+"]").each(function(){
				$(this).prop({'checked':'checked'});
			});
		}
	}
	
	if (data.ready!=null) {
		if (data.ready=='1') {
			var input_ready = $(post_edit_tpl_current).find('input[name=ready]')[0];
			$(input_ready).prop({'checked':'checked'});
		}
	}
	
	$(destination_parent).append(post_edit_tpl_current);
	
	var current_post_object_name = ' #post_id_'+data.id;
	
	resizeTextarea(destination_parent+current_post_object_name+' textarea[name="description"]');
	resizeTextarea(destination_parent+current_post_object_name+' textarea[name="tags"]');
	if (checkforWordsLimits(current_post_object_name+' textarea[name="tags"]', 30)==false) {
		alert('Лимит на хештеги 30!\nОписание и теги опубликовыны не будут!');
		$('.tags-warning').removeClass('d-none');
	}
	
	$(destination_parent).on('keyup', current_post_object_name+' textarea[name="description"]', function(){
		resizeTextarea($(this));
	});
	
	$(destination_parent).on('blur', current_post_object_name+' textarea[name="tags"]', function(){
		resizeTextarea($(this));
		if (checkforWordsLimits($(this), 30)==false) {
			$(destination_parent+current_post_object_name+' .tags-warning').removeClass('d-none');
		} else {
			$('.tags-warning').addClass('d-none');
		}
	});
	
	/* click on tag hints */
	$(destination_parent).on('click', current_post_object_name+' .tags-hints .badge-pill', function(){
		var tagsArea = $(destination_parent+current_post_object_name+' textarea[name="tags"]');
		var text = $(tagsArea).val();
		var tag = $(this).html();
		
		var startIndex = 0;
		var stopIndex = window.gpi_tags_cursor_position;
		for (var i=stopIndex; i>=0; i--) {
		  if (text.charAt(i)==' ' || text.charAt(i) == '') {
			startIndex = i;
			break;
		  }
		}

		var before_type = text.substring(0, startIndex).split(" ");
		before_type.pop();
		var after_type = text.substring(stopIndex, text.length).split(" ");

		var newCursorPos = before_type.join(" ").length+tag.length+1;

		var finishTagsString = before_type.concat([tag].concat(after_type));

		var filtered = finishTagsString.filter(function (el) {
		  return el != null && el != '';
		});

		$(tagsArea).val(filtered.join(" "));
		
		setCursorPos($(tagsArea)[0], newCursorPos, newCursorPos);
		
		$(tagsArea).focus();
		
		
	});
	
	$(destination_parent).on('blur', current_post_object_name+' textarea[name="tags"]', function(){
		setTimeout(function(){
			var tagsHits = $(destination_parent+' .tags-hints');
			$(tagsHits).html('');
		}, 300);

	});
	
	/* tags check and show hints */
	function tagsHints(sObj, destination_parent, dObj) {
		var tags = $(sObj).val();
		var cursorPosition = getCursorPos($(sObj)[0]);
		window.gpi_tags_cursor_position = cursorPosition.start;	 // для того чтобы запомнить куда вставлять тег
		// дойти до конца или до следующего пробела и отрезать все дальше
		for (var i=cursorPosition.start; i<=tags.length; i++) {
			if (tags.charAt(i)==' ') {
				tags = tags.substring(0, i);
				break;
			}
			if (i>=tags.length) {
				break;
			}
		}
		tags = tags.split(' ');
		currentTag = tags[tags.length-1];
		if (currentTag.trim()=='') return false;

		//TODO: убрать уже используемые

		apiRequest({request: 'tags_hints', tag: currentTag}, function(data){
			if (data.type=='success') {
				$(destination_parent+' .'+dObj).html(data.opts.message);
			}
		});
	}
	
	$(destination_parent).on('keyup', current_post_object_name+' textarea[name="tags"]', function(e){
		var k = e.originalEvent.keyCode;
		if (k == 20 /* Caps lock */
			|| k == 16 /* Shift */
			|| k == 9 /* Tab */
			|| k == 27 /* Escape Key */
			|| k == 17 /* Control Key */
			|| k == 91 /* Windows Command Key */
			|| k == 19 /* Pause Break */
			|| k == 18 /* Alt Key */
			|| k == 91 /* CMD left macos key */
			|| k == 93 /* Right Click Point Key */
			|| ( k >= 35 && k <= 40 ) /* Home, End, Arrow Keys */
			|| k == 45 /* Insert Key */
			|| ( k >= 33 && k <= 34 ) /*Page Down, Page Up */
			|| (k >= 112 && k <= 123) /* F1 - F12 */
			|| (k >= 144 && k <= 145 )) { /* Num Lock, Scroll Lock */
			return false;
		}		
		
		resizeTextarea($(this));
		tagsHints($(this), destination_parent, 'tags-hints');
		if (checkforWordsLimits($(this), 30)==false) {
			$('.tags-warning').removeClass('d-none');
		} else {
			$('.tags-warning').addClass('d-none');
		}
	});
	
	var currtime = null;
	if (data.date_scheduled!=null && data.date_scheduled!=false) {
		currtime = Math.round(new Date(data.date_scheduled).getTime() / 1000);
		scheduled_time = data.date_scheduled;
	} else {
		var currentDate = new Date();
		var day = currentDate.getDate() + 1
		currentDate.setDate(day);
		currentDate.setHours(0);
		currentDate.setMinutes(0);
		currentDate.setSeconds(0);
		currtime = Math.round(currentDate.getTime() / 1000);
		scheduled_time = false;
	}
	
	apiRequest({request: 'get_schedule_for_date', date: currtime}, function(schedule_data){
		var r = rangerTick;
		r.init('#gpi-ranger-tick-'+data.id, schedule_data.opts.message, scheduled_time);
	});
	
	$(destination_parent).on('change', 'input[name="scheduled_date"]', function(){
		var date_array = $(this).val().split('-');
		var midnighttime = new Date();
		midnighttime.setDate(date_array[2]);
		midnighttime.setMonth(date_array[1]-1);
		midnighttime.setYear(date_array[0]);
		midnighttime = Math.round(midnighttime.getTime() / 1000);
		apiRequest({request: 'get_schedule_for_date', date: midnighttime}, function(schedule_data){
			var r = rangerTick;
			r.init('#gpi-ranger-tick-'+data.id, schedule_data.opts.message, false);
		});
	});
	
	
	
}