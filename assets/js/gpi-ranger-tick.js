/*
 * Ranger Tick
 * http://dsda.ru
 */

var rangerTick = {
	dObj: null,
	getTimeCurrent: function () {
		var currtime = new Date();
		return currtime.getTime() / 1000;
	},
	getTimeForHours: function (h, i, s) {
		var midnighttime = new Date();
		midnighttime.setHours(h, i, s, 0);
		return midnighttime.getTime() / 1000;
	},
	getTimeFromDayStart: function () {
		return this.getTimeCurrent() - this.getTimeForHours(0, 0, 0);
	},
	getTimeFromTimestamp: function (stamp) {
		var a = new Date(stamp * 1000);
		var hours = "0" + a.getHours();
		var minutes = "0" + a.getMinutes();
		var seconds = "0" + a.getSeconds();
		var formattedTime = hours.substr(-2) + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
		return formattedTime;
	},
	time_to_val: function(dateString){
		var reggie = /(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/;
		var dateArray = reggie.exec(dateString); 
		var dateObject = new Date(
			(+dateArray[1]),
			(+dateArray[2])-1, // Careful, month starts at 0!
			(+dateArray[3]),
			(+dateArray[4]),
			(+dateArray[5]),
			(+dateArray[6])
		);
		var mntime = new Date((+dateArray[1]),
			(+dateArray[2])-1, // Careful, month starts at 0!
			(+dateArray[3]),0,0,0);
		var day_post_tick = (dateObject.getTime() - mntime.getTime())/1000;
		return Math.round(day_post_tick/60/5);
	},
	init_range_ticks: function (rangeSlider, ticksParent, scheduled_posts) {
		$(ticksParent).html('');
		for (var i in scheduled_posts) {
			var post_time = scheduled_posts[i].split(":");
			var a = new Date(this.getTimeForHours(post_time[0], post_time[1], 0));
			var day_post_tick = ((a.getTime()) - this.getTimeForHours(0, 0, 0)) / 60;
			$(ticksParent).width($(rangeSlider).width() - 24);
			$(ticksParent).css('margin-left', '12px');

			var tick_left = (($(rangeSlider).width() - 24) / 24 / 60 * day_post_tick) - 7 + 2 + 'px';
			var tick = '<div class="tick" data-tick-time="' + day_post_tick + '" style="position:absolute;top:0;left:' + tick_left + '" title="' + scheduled_posts[i] + '"><i class="fa fa-arrow-up"></i></div>';
			$(ticksParent).append(tick);
		}
	},
	init: function (destination_parent, scheduled_posts, current_time) {
		
		console.log('ranger tick INIT');
		
		this.dObj = destination_parent;

		this.init_range_ticks(this.dObj  + ' .gpi-time-range', this.dObj  + ' .gpi-time-range-ticks', scheduled_posts);


		var thisdaycurtime = this.getTimeFromDayStart();
		thisdaycurtime = Math.round(thisdaycurtime / 60 / 5);

		if (current_time!=false) {
			var cst = new Date(current_time);
			$(this.dObj  + ' .gpi-time-range').val(this.time_to_val(current_time));
			$(this.dObj  + ' .gpi-time-time-test').val(cst.getHours()+':'+cst.getMinutes());
		} else {
			$(this.dObj  + ' .gpi-time-range').val(thisdaycurtime);
			$(this.dObj  + ' .gpi-time-time-test').val(this.getTimeFromTimestamp(this.getTimeForHours(0, 0, 0) + thisdaycurtime * 60 * 5));
		}
		var thisObject = this;

		$(this.dObj).on('change input', '.gpi-time-range', function () {
			var currentValue = $(this).val() * 60 * 5;
			var midnightTime = thisObject.getTimeForHours(0, 0, 0);
			var currentTimestamp = (midnightTime + currentValue);
			var formatedTimeString = thisObject.getTimeFromTimestamp(currentTimestamp);
			$(thisObject.dObj  + ' .gpi-time-time-test').val(formatedTimeString);
		});
		
		$(window).on('resize', function () {
			thisObject.init_range_ticks(thisObject.dObj + ' .gpi-time-range', thisObject.dObj + ' .gpi-time-range-ticks', scheduled_posts);
		});

	}

};
