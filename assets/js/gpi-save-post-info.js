function get_object_value(sObj){
	var value = '';
	var name = $(sObj).prop('name');
	var type = '';
	if (typeof $(sObj).val() == 'undefined' || $(sObj).val()=='undefined') {
		if (typeof $(sObj)[0].defaultValue != 'undefined' || $(sObj)[0].defaultValue != 'undefined') {
			type = 'defaultValue';
			value = $(sObj)[0].defaultValue;
		}
	} else {
		type = 'val()';
		value = $(sObj).val();
	};
	//console.log(name, type, value);
	return value;
}

function save_post_info(id){
			
	var fields = {};

	fields.id = id;


	$('#post_id_'+id+' .form-control').each(function(){
		var value = '';
		if ($(this).attr('name')) {
			if ($(this).attr('type')=='checkbox') {
				if ($(this).is(':checked')) {
					value = get_object_value(this);
				} else {
					value = '';
				}
			} else {
				value = get_object_value(this);
			}
			fields[$(this).attr('name')] = value;
		}
	});

	fields.request = 'store';

	// checks
	if (fields.facebook_places_id=='' && fields.location!='') {
		alert('Вы не нашли локацию!\nНажмите "НАЙТИ" и выберите нужную.');
		$('#post_id_'+id+' input[name="location"]').focus();
		return false;
	}

	if (checkforWordsLimits('#post_id_'+id+' textarea[name="tags"]', 30)==false) {
		alert('Привышен лимит на количество тегов!\nКоличество тегов не должно быть более 30!');
		$('#post_id_'+id+' textarea[name="tags"]').focus();
		return false;
	}

	apiRequest(fields, function(data){
		if (data.type!=='success') { alert('Ошибка API!'); return false; }
		if (data.opts.type!=='success') { alert('Ошибка скрипта:\n'+data.opts.message); return false; }
		data.opts = data.opts.message;
		notify(data.type, data.opts);
	});
}