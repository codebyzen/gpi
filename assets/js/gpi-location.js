
/* location fetcher start */

function request_insta_locations(location_root_obj){
	$(location_root_obj + ' .location_results').slideDown();
	$(location_root_obj + ' .location_results').html('<div class="text-center mt-4 mb-4 loading_spinner"><i class="fas fa-spinner text-muted fa-3x fa-pulse"></i></div>');
	var location_source = $($(location_root_obj + ' input[name="location_source"]:checked')[0]).val()+'_location';
	apiRequest({request: location_source, query: $(location_root_obj + ' input[name="location"]').val()}, function(data){
		
		if (data.type!=='success') { alert('Some error with API!'); return false; }
		if (data.opts.type!=='success') { alert('Some error with API engine:\n'+data.opts.message); return false; }
		data.opts = data.opts.message;
		
		$(location_root_obj + ' .location_results').html('');
		for (var i in data.opts) {
			var name = data.opts[i].name;
			var facebook_places_id = data.opts[i].facebook_places_id;
			var lat = data.opts[i].lat;
			var lng = data.opts[i].lng;
			$(location_root_obj + ' .location_results').append('<div class="location_result_item" data-facebook-places-id="'+facebook_places_id+'" data-lat="'+lat+'" data-lng="'+lng+'">'+name+'</div>');
		}
	});
}

function location_init(location_root_obj){
	var gpi_location_selector_obj = $(location_root_obj + ' div.location_results');
	var gpi_location_input_obj = $(location_root_obj + ' input[name="location"]');

	// clear facebook_places_id if input changed
	$(location_root_obj).on('keyup','input[name=location]', function(){
		$(location_root_obj + ' [name="facebook_places_id"]').val('');
	});

	// highlight if facebook_places_id not present and we make some changes in input
	$(location_root_obj).on('blur','input[name=location]', function(){
		if (typeof $(location_root_obj + ' [name="facebook_places_id"]').val()=='undefined' || $(location_root_obj + ' [name="facebook_places_id"]').val()=='') {
			$(gpi_location_input_obj).css({'border':'1px solid red'});
		}
	});

	// on lick on location item
	$(location_root_obj).on('click','.location_result_item', function(){
		var lat = $(this).data('lat');
		var lng = $(this).data('lng');
		var facebook_places_id = $(this).data('facebook-places-id');
		console.log(facebook_places_id);
		$(gpi_location_input_obj).val($(this).html());
		$(location_root_obj + ' [name="facebook_places_id"]').val(facebook_places_id);
		$(gpi_location_selector_obj).slideUp(function(){ $(this).html(''); });
		$(gpi_location_input_obj).css({'border':'1px solid #ced4da'});
	});

	$(location_root_obj).on('click', '.clear_location', function(){
		$(gpi_location_input_obj).val('');
		$(location_root_obj + ' [name="facebook_places_id"]').val('');
		$(gpi_location_selector_obj).slideUp(function(){ $(this).html(''); });
		$(gpi_location_input_obj).css({'border':'1px solid #ced4da'});
	});		
	
	
	$(location_root_obj).on('click', '.btn-location-request', function(){
		request_insta_locations(location_root_obj);
	});

}
/* location fetcher end */


