var gpi_waiter = {
	show: function(lambda){
		$('body').prepend('<div id="wait" class="align-items-center justify-content-center"><i class="fa fa-spinner fa-w-16 fa-spin fa-2x"</i></div>');
		$('#wait').addClass('d-flex').hide().fadeIn(function(){
			if (typeof lambda != 'undefined') lambda();
		});
	},
	hide: function(lambda){
		$('#wait').fadeOut(function(){
			$(this).remove();
			if (typeof lambda != 'undefined') lambda();
		});
	}
};

