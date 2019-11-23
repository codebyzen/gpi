<!DOCTYPE html>
<html>
	<head>
		<title>Day queue</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<script src="../../assets/jQuery/jquery-3.4.1.min.js"></script>
		
		<script src="../../assets/jQuery/jquery-ui.min.js"></script>
		<link href="../../assets/jQuery/jquery-ui.min.css" rel="stylesheet">
	
		
		<script src="../../assets/js/popper.min.js"></script>
		
		<script src="../../assets/bootstrap-4.3.1-dist/js/bootstrap.min.js"></script>
		
		<link href="../../assets/bootstrap-4.3.1-dist/css/bootstrap.min.css" rel="stylesheet">
		
		
		<link href="../../assets/fontawesome-free-5.5.0-web/css/all.css" rel="stylesheet">
		<script src="../../assets/fontawesome-free-5.5.0-web/js/fontawesome.min.js"></script>
		
		<style>
			/*(xs) Extra small devices (portrait phones, less than 576px)*/
			@media (max-width: 575px) { .card-columns { column-count: 1; } }

			/*(sm) Small devices (landscape phones, 576px and up)*/
			@media (min-width: 576px) and (max-width: 767px) { .card-columns { column-count: 1; } }

			/*(md) Medium devices (tablets, 768px and up)*/
			@media (min-width: 768px) and (max-width: 991px) { .card-columns { column-count: 2; } }

			/*(lg) Large devices (desktops, 992px and up)*/
			@media (min-width: 992px) and (max-width: 1199px) { .card-columns { column-count: 3; } }

			/*(xl) Extra large devices (large desktops, 1200px and up)*/
			@media (min-width: 1200px) { .card-columns { column-count: 3; } }
			
			.card-image-header {
				background-size: cover;
				background-repeat: no-repeat;
				background-position: 50% 50%;
				width: 100%;
				height: 220px;
			}
			
			
		</style>

		
		
		<script>
			
			function randomID(prefix){
				if (typeof prefix == "undefined") prefix = 'gpi_';
				if (typeof window.gpi_random_id == "undefined") window.gpi_random_id = [];
				
				let successFlag = false;
				let iterationCount = 0;
				while (successFlag!=true || iterationCount<10000) {
					let randomnum = prefix+Math.random().toString().replace("0.","");
					if (window.gpi_random_id.indexOf(randomnum)==-1) {
						successFlag = true;
						window.gpi_random_id.push(randomnum);
						break;
					}
					iterationCount++;
				}

				return window.gpi_random_id[window.gpi_random_id.length-1];
			}
			
			
			function easeInOutCosx(t) { 
				return (t-=.5) < 0 ? (Math.cos(Math.PI * t - Math.PI * 2)) * 0.5 : (2 + Math.sin(Math.PI * t - Math.PI / 2)) / 2 
			};
			
			
			function createCanvas(){
				let graph = $('<div></div>');
				$(graph).attr({id:'ctx'});
				$(graph).css({width:'100%',height:'200px',display:'block',position:'relative',backgroundColor:'rgba(255,0,0,0.1)'});
				$('body').append(graph);
				return graph;
			}
			
			function drawPoint(ctx,x,y,w,h,c,t){
				let graphItem = $('<div></div>');		
//				$(graphItem).html(t);
//				w=2;h=2;
				x='100px';
//				y='100px';
				$(graphItem).css({width:w+'px',height:h+'px',left:x,bottom:y,display:'block',position:'absolute', backgroundColor:c});
				$(ctx).append(graphItem);
			}


			function easeInOutCosy(t) { 
				t-=0.5;
				if (t<0) {
					return (Math.cos(Math.PI*1.3 * t - Math.PI * 2)) * 0.5;
				} else {
					return (2 + Math.sin(Math.PI * t - Math.PI / 2.5)) / 2;
				}
			};
			
			function easeInOutCos(t) { 
				t-=0.5;
				if (t<0) {
					return (Math.cos(Math.PI * t - Math.PI * 2)) * 1;
				} else {
					return (2.3 + Math.sin(Math.PI * t - Math.PI / 2.55)) / 1.3;
				}
			};

			function gaussTicks(){
				var ctx = createCanvas()
				
				var postsCount = 10;

				var timeValues = [];
				for(var i = 0 ; i < postsCount ; i++){
					x = i/postsCount;

					var y = easeInOutCos(x)*2;
					
					var cX = Math.round(x*100);
					var cY = Math.round(y*100);
				
					drawPoint(ctx, cX, cY, 2,2,'rgba(255,0,0,1)',i);
					
					timeValues.push(y);
				}
				
				var timeStartPosting = 405; // 6h 45m
				var timeEndPosting = 1365; // 22h 45m
				var timeForPosting = timeEndPosting-timeStartPosting; // 16h
				
				var rTimeValues = [];
				var rTimeValuesWidth = timeValues[timeValues.length-1]/timeForPosting;
				for (let i=0;i<=timeValues.length-1;i++) {
					var grounded = timeValues[i]-timeValues[0];
					grounded = grounded/rTimeValuesWidth;
					rTimeValues.push(Math.round(grounded+timeStartPosting));
				}
				
				console.log($('#range_day').width()-40);
				var i=0;
				$('#range_day .tick').each(function(){
					let pow = ($(this).parent().width()-40)/1440;
					let leftOffset = rTimeValues[i]*pow;
					console.log(rTimeValues[i],leftOffset,pow);
					$(this).css({left:leftOffset});
					i++;
				});
				console.log(rTimeValues);

				
				
				
			}
			
			function make_tick(){
				/* wrapper */
				let w = $('<div></div>');
				$(w).attr({id:randomID(),'class':'tick'});
				$(w).css({width:'40px',height:'55px',position:'absolute',bottom:0, cursor: 'pointer'});
				
				/* image */
				let i = $('<img>');
				$(i).attr({src:"./images/"+ (1+Math.round(Math.random()*9)) +".jpg"});
				$(i).css({maxHeight:25,maxWidth:40,position:'relative'});
				$(w).append(i);
				
				/* pointer */
				let p = $('<div></div>');
				$(p).addClass('pointer');
				$(p).css({width:'2px',height:'28px',backgroundColor:'#000',position:'absolute',left:20,top:27});
				$(w).append(p);
				
				/* time tips */
				let t = $('<div></div>');
				$(t).addClass('timeTip');
				$(t).css({width:'100%',height:'1em',backgroundColor:'#fff',position:'absolute',margin:'0 auto',top:'-1.4em',fontSize:'0.875em',textAlign:'center'});
				$(w).prepend(t);
				
				$(w).draggable({
					containment: '#range_day', 
					scroll: false, 
					axis: 'x',
					start: function() {
						$(this).css({cursor:'grabbing'});
						window.gpi_random_id.forEach(item => {
							$('#'+item).css({zIndex:0});
						});
						$(this).css({zIndex:999});
					},
					drag: function(){
						let item = $(this)[0];
						let parent = $(item).parent();
						
						$($(item).find('.pointer')[0]).css({backgroundColor:'#f00'});
						
						let timeValue = $(item)[0].offsetLeft/(($(parent).width()-40)/1440);
						let realTimeHours = Math.floor(timeValue / 60);
						let realTimeMinutes = Math.floor(timeValue % 60);
						if (realTimeHours==24) {
							realTimeHours=23;
							realTimeMinutes=59;
						}
						if (realTimeHours.toString().length<2) realTimeHours='0'+realTimeHours;
						if (realTimeMinutes.toString().length<2) realTimeMinutes='0'+realTimeMinutes;
						
						$($(item).find('.timeTip')[0]).html(realTimeHours+':'+realTimeMinutes);
						
//						console.log('time', realTimeHours, realTimeMinutes);
					},
					stop: function(e){
						$(this).css({cursor:'pointer'});
						$($(this).find('.pointer')[0]).css({backgroundColor:'#000'});
					},
				});
				
				
				$(w).on('click', function(){
					console.log($(this).attr('id'));
					window.gpi_random_id.forEach(item => {
						$('#'+item).css({zIndex:0});
						$($('#'+item).find('.pointer')[0]).css({backgroundColor:'#000'});
					});
					$(this).css({zIndex:999});
					$($(this).find('.pointer')[0]).css({backgroundColor:'#0f0'});
					window.gpi_tick_current_active = this;
					$('#post_prewiew').attr({'src':$(i).attr('src')});
				});
				return w;
			}
			
			$(function () {
				$('[data-toggle="tooltip"]').tooltip();
				
				for(let i=0;i<10;i++) {
					$('#range_day').append(make_tick());
				}
				
				$(window).on('resize', function(){
					console.log('resize');
				});
				
				gaussTicks();
				
			});
		</script>
		
	</head>
	<body>
		
		<div class="container">
			
			<div class="row">
				<div class="col">
					<input class="form-control" type="date">
					<button>< previous day</button> <button>next day ></button>
				</div>
			</div>
			
			<div class="row">
				<div class="col-12 col-md-4">
					<img src="./images/11.jpg" style="width:100%;" id="post_prewiew">
				</div>
				<div class="col-12 col-md-8">
					Title: qweqweqw<br>
					<input type="date">
					<input type="time"><br>
					<button>Edit post</button>
					<button>Delete from queue</button>
				</div>
			</div>
			
			<div class="row">
				<div class="col mt-5 mb-5">
					<div class="mt-5" style="position: relative; background-color: rgba(255,0,0,0.1);width: 100%; height: 53px;" id="range_day">
					</div>
					
					<button onclick="gaussTicks('#range_day');">graph</button>
				
					
				</div>
			</div>
			
			
		</div>
		
		
	</body>
</html>
