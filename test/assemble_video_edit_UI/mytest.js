//TODO: задавать размер с учетом медиа
//TODO: размер текста редактировать
//TODO: выравнивание текста



window.gpi_ve = {
	ids: {objects:{},},
	opts: {
		wrapper:null, /* canvas wrapper */
		fullDim:[0,0]  /* full (result) canvas dimention */
	},
	createCanvas: function(dim,wrapper){
		this.opts.fullDim = dim;
		
		this.opts.wrapper = wrapper;
		// очищаем канавс
		this.opts.wrapper.innerHTML = '';
		
		// рисуем канвас
		this.ids.stage = new Konva.Stage({
			container: 'canvasWrapper',
			id: 'stageObject',
			width: parseInt(this.opts.fullDim[0]),
			height: parseInt(this.opts.fullDim[1])
		});


		this.ids.stage.content.crossOrigin="anonymous";

		this.ids.layer = new Konva.Layer({
			id: 'layerObject'
		});
		this.ids.stage.add(this.ids.layer);
		
		this.ids.stage.userDefinedAnim = new Konva.Animation(function() {
			// do nothing, animation just need to update the layer
		}, this.ids.layer);

		this.fitCanvas();

		dlog('createCanvas - compleate');
	},
	drawCanvas: function(){
		// отрисовываем
		this.ids.stage.getLayers()[0].draw();
		// обновим слой, нужно для того чтобы прогрузился кадр из видео
		this.ids.stage.userDefinedAnim.layers[0].drawScene();
		window.gpi_ve.ids.stage.draw();
	},
	mediaAdd: function(file,type,dim,callbackFunc){
		if (type==='video') {
			var mediaObject = document.createElement('video');
			mediaObject.src = file;
		} else {
			var mediaObject = new Image();
			mediaObject.src = file;
		};

		let optSize = fixSizes(dim[0],dim[1],this.opts.fullDim[0],this.opts.fullDim[1],"optSize - media object");

		var konvaMediaObject = new Konva.Image({
			objectType: 'object',
			image: mediaObject,
			width: optSize[0],
			height: optSize[1],
			draggable: true,
			y: this.opts.fullDim[1]/2-optSize[1]/2,
			id: "mediaObject"
			//dragBoundFunc: function(pos) { return dragBoundFuncHelper(pos, this); }
		});


		this.ids.stage.getLayers()[0].add(konvaMediaObject);
		
		

		// update Konva.Image size when meta is loaded
		mediaObject.addEventListener('loadedmetadata', function() {
			konvaMediaObject.width(optSize[0]);
			konvaMediaObject.height(optSize[1]);
			dlog('mediaObject.addEventListener:loadedmetadata - compleate');
		});

		var mediaTransformer = new Konva.Transformer({
			objectType: 'transformer',
			node: konvaMediaObject,
			id: "mediaTransformer",
			keepRatio: true,
			width: optSize[0],
			height: optSize[1],
			enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right']
			// boundBoxFunc: function(oldBoundBox, newBoundBox) { return boundBoxFuncHelper(oldBoundBox, newBoundBox, this); }
		});
		this.ids.stage.getLayers()[0].add(mediaTransformer);
		let currentID = Math.round(Math.random()*2e+10);
		this.ids.objects[currentID] = {type:'media',object:mediaObject,transformer:mediaTransformer,visible:true};
		dlog('mediaAdd - compleate');
		if (typeof callbackFunc!==null && typeof callbackFunc !== 'undefined') callbackFunc(currentID);
		return currentID;
	},
	textAdd: function(text, callbackFunc){
		var mediaSize = [this.ids.stage.find('#mediaObject')[0].getWidth(),this.ids.stage.find('#mediaObject')[0].getHeight()];
		var fontSize = 2;
		var textObject = new Konva.Text({
			objectType: 'object',
			fontSize: fontSize,
			text: text,
			draggable: true,
			id: 'textObject',
			fillEnabled: true
			//dragBoundFunc: function(pos) { return dragBoundFuncHelper(pos, this); },
		});
		this.ids.stage.getLayers()[0].add(textObject);


		let delim = postTypeSelector.opts.selectedMediaType[0]/textObject.textWidth;
		textObject.attrs.fontSize=textObject.attrs.fontSize*delim;
		textObject.align('center'); // fucking magic with transformer width...
		textObject.textWidth = textObject.textArr[0].width;
		textObject.setAttrs({
			x: postTypeSelector.opts.selectedMediaType[0]/2 - textObject.textWidth/2, 
			y: postTypeSelector.opts.selectedMediaType[1]/2 - mediaSize[1]/2 - textObject.attrs.fontSize
		});


		var textTransformer = new Konva.Transformer({
			objectType: 'transformer',
			node: textObject,
			id: 'textTransformer',
			keepRatio: true,
			enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'], // все 4 угла и меняем именно размер
			//enabledAnchors: ['middle-left', 'middle-right'], // только ширину поля ввода
			//boundBoxFunc: function(oldBox, newBox) { return boundBoxFuncHelper(oldBox, newBox, this); }
		});

		this.ids.stage.getLayers()[0].add(textTransformer);
		textTransformer.forceUpdate();
		let currentID = Math.round(Math.random()*2e+10);
		this.ids.objects[currentID] = {type:'text',object:textObject,transformer:textTransformer,visible:true};
		dlog('textAdd - compleate');
		
		if (typeof callbackFunc!==null && typeof callbackFunc !== 'undefined') callbackFunc(currentID);
		
		return currentID;
	},
	objectToggleByID(id, callbackFunc) {
		if(this.ids.objects[id].visible==true) {
			this.ids.objects[id].visible=false;
			this.ids.objects[id].object.hide();
			this.ids.objects[id].transformer.hide();
		} else {
			this.ids.objects[id].visible=true;
			this.ids.objects[id].object.show();
			this.ids.objects[id].transformer.show();
		}
		this.ids.stage.draw();
		if (typeof callbackFunc!==null && typeof callbackFunc !== 'undefined') callbackFunc();
	},
	fitCanvas: function(){
		console.log('!!!');
		// now we need to fit stage into parent
		var containerWidth = this.opts.wrapper.offsetWidth;
		// to do this we need to scale the stage
		var scale = containerWidth / this.opts.fullDim[0];

		this.ids.stage.width(this.opts.fullDim[0] * scale);
		this.ids.stage.height(this.opts.fullDim[1] * scale);
		this.ids.stage.scale({ x: scale, y: scale });
		
		for (let i=0;i<this.ids.stage.getLayers()[0].children.length;i++) {
			if (this.ids.stage.getLayers()[0].children[i].attrs.objectType==='transformer') {
				this.ids.stage.getLayers()[0].children[i].forceUpdate();
			}
		}

		this.drawCanvas();
	}
};

	
	
	function redrawAll(wrapper, mediaObject, dim){
		window.gpi_ve.createCanvas(dim, wrapper);
		window.gpi_ve.mediaAdd(mediaObject.path, mediaObject.type, mediaObject.size);
		window.gpi_ve.textAdd(mediaObject.text, function(currID){ document.getElementsByClassName('btnTextToggle')[0].dataset.id = currID; });
		window.gpi_ve.drawCanvas();
		window.gpi_ve.fitCanvas();
	}
	
	
	
	function dlog(msg) {
		console.log(msg);
	}

	var postTypeSelector = {
		postTypesInfo: {
			TIMELINE: {
				name: 'TimeLine',
				items: [
					{name:'square min',dim:[640,640]},
					{name:'square max',dim:[1080,1080]},

					{name:'portrait min',dim:[640,750]},
					{name:'portrait max',dim:[1350,1080]},

					{name:'landscape min',dim:[640,315]},
					{name:'landscape max',dim:[1080,608]}

				]
			},
			IGTV: {
				name: 'IGTV',
				items: [
					{name:'landscape',dim:[1280,720]},
					{name:'portrait',dim:[720,1280]}
				]
			},
			STORY: {
				name: 'Story',
				items: [
					{name:'max',dim:[1080,1920]},
					{name:'middle',dim:[720,1280]},
					{name:'min',dim:[450,800]}
				]
			}
		},
		opts: {
			postTypeSelector: 'undefined',
			mediaTypeSelector: 'undefined',
			selectedPostType: 'undefined',
			selectedMediaType: 'undefined' /* 0 - width, 1 - height */
		},
		init: function(opts){
			// this.opts = Object.assign(targetObject, 1thObject, 2dtObject);

			// если не задано умолчание то берем первый
			if (typeof opts.postTypeDefault === 'undefined') {
				let _ptd = false;
				for(var i in this.postTypesInfo) { _ptd = this.postTypesInfo[i]; break; }
				this.opts.selectedPostType = _ptd;
			} else {
				this.opts.selectedPostType = opts.postTypeDefault;
			}
			this.opts.postTypeSelector = document.getElementById(opts.postTypeDOMObject);
			this.buildPostTypeSelector();


			if (typeof opts.mediaTypeDefault === 'undefined') {
				this.opts.selectedMediaType = this.postTypesInfo[this.selectedPostType].items[0];
			} else {
				this.opts.selectedMediaType = opts.mediaTypeDefault;
			}
			this.opts.mediaTypeSelector = document.getElementById(opts.mediaTypeDOMObject);
			this.buildMediaTypeSelector();

			if (typeof opts.callbackFunc === 'function') {
				this.onChangeMedia = opts.callbackFunc;
			}


			this.placeListeners();
		},
		buildPostTypeSelector: function(){
			this.opts.postTypeSelector.innerHTML = '';
			for(var i in this.postTypesInfo) {
				var opt = document.createElement('option');
				opt.value = i;
				opt.innerHTML = this.postTypesInfo[i].name;
				if (this.postTypesInfo[i].name===this.opts.selectedPostType) opt.selected = true;
				this.opts.postTypeSelector.appendChild(opt);
			}
		},
		buildMediaTypeSelector: function(){
			this.opts.mediaTypeSelector.innerHTML = '';
			let selectedPostType = this.opts.selectedPostType;
			for (var i in this.postTypesInfo[this.opts.selectedPostType].items) {
				var opt = document.createElement('option');
				opt.value = this.postTypesInfo[selectedPostType].items[i].dim[0]+'x'+this.postTypesInfo[selectedPostType].items[i].dim[1];
				opt.setAttribute('data-width',this.postTypesInfo[selectedPostType].items[i].dim[0]);
				opt.setAttribute('data-height',this.postTypesInfo[selectedPostType].items[i].dim[1]);
				opt.setAttribute('data-name',this.postTypesInfo[selectedPostType].items[i].name);
				opt.innerHTML = this.postTypesInfo[selectedPostType].items[i].name+' ['+this.postTypesInfo[selectedPostType].items[i].dim[0]+'x'+this.postTypesInfo[selectedPostType].items[i].dim[1]+']';
				if (this.opts.selectedMediaType===this.postTypesInfo[selectedPostType].items[i].name) opt.selected = true;
				this.opts.mediaTypeSelector.appendChild(opt);
			}
			this.opts.selectedMediaType = [parseInt(this.opts.mediaTypeSelector.selectedOptions[0].dataset['width']),parseInt(this.opts.mediaTypeSelector.selectedOptions[0].dataset['height']), this.opts.mediaTypeSelector.selectedOptions[0].dataset['name']];
		},
		placeListeners: function(){
			this.opts.postTypeSelector.addEventListener('change', function(){
				dlog('Post Type selected: '+this.value);
				postTypeSelector.opts.selectedPostType = this.value;
				postTypeSelector.buildPostTypeSelector();
				postTypeSelector.buildMediaTypeSelector();
				postTypeSelector.onChangeMedia();
			});
			this.opts.mediaTypeSelector.addEventListener('change', function(){
				postTypeSelector.opts.selectedMediaType = [
					this.selectedOptions[0].dataset['width'],
					this.selectedOptions[0].dataset['height'],
					this.selectedOptions[0].dataset['name']
				];
				postTypeSelector.onChangeMedia();
			});
		}


	};

	function buttonsListeners(mediaType){

		// сбросить координаты всех элементов в центр
		var allToCenter = document.getElementById('allToCenter');
		allToCenter.addEventListener('click', function(){
			var stage = window.gpi_ve.ids.stage;
			stage.getLayers()[0].children.forEach(element=>{
				if (element.attrs.objectType==='object') {
					let realKonvaDim = [(stage.getWidth()/stage.scaleX()), (stage.getHeight()/stage.scaleY())];
					element.setX(realKonvaDim[0]/2-element.getWidth()/2);
					element.setY(realKonvaDim[1]/2-element.getHeight()/2);
				}
			});
			stage.getLayers()[0].draw();
			// обновим слой, нужно для того чтобы прогрузился кадр из видео
			stage.userDefinedAnim.layers[0].drawScene();
		});

		// при смене цвета фона
		var bgColorObject = document.getElementById('bgColor');
		bgColorObject.oninput = function() {
			//TODO: менять цвет фона канваса
//			document.getElementById('canvasWrapper').style.backgroundColor = bgColorObject.value;
			window.gpi_ve.ids.stage.setAttrs({
				fill: bgColorObject.value
			});
			window.gpi_ve.drawCanvas();
		};

		// при смене цвета текста
		var textColor = document.getElementById('textColor');
		textColor.oninput = function() {
			window.gpi_ve.ids.stage.find('#textObject')[0].fill(textColor.value);
			window.gpi_ve.ids.stage.find('#layerObject')[0].draw();
			window.gpi_ve.drawCanvas();
		};

		// при изменении текста
		var textTitle = document.getElementById('textTitle');
		textTitle.onkeyup = function() {
			var tO = window.gpi_ve.ids.stage.find('#textObject')[0];
			var tL = window.gpi_ve.ids.stage.find('#layerObject')[0];
			tO.setAttrs({text: textTitle.value, align: 'center'});
			let realTextWidth = window.gpi_ve.ids.stage.find('#textObject')[0].getWidth()*window.gpi_ve.ids.stage.scale().x;
			tO.setAttrs({x: window.gpi_ve.ids.stage.getWidth()/2-realTextWidth/2});
			tL.draw();
		};


		// при нажатии кнопки "в очередь"
		var goButton = document.getElementById('btnToQuery');
		goButton.addEventListener('click', function () {
			var stage = window.gpi_ve.ids.stage;
			stage.find('#textTransformer')[0].hide();
			stage.find('#mediaTransformer')[0].hide();
			stage.find('#mediaObject')[0].hide();
			stage.draw();

			let oldValues = {width: stage.getWidth(), height: stage.getHeight(), scaleX:stage.scaleX(), scaleY:stage.scaleY()};
			var scaleX = stage.getWidth()/stage.scaleX();
			var scaleY = stage.getHeight()/stage.scaleY();
			stage.width(scaleX);
			stage.height(scaleY);
			stage.scale({ x: 1, y: 1 });
			window.gpi_ve.drawCanvas();


			var cfg = {
				memeType: "image/jpeg",
//				x: 0,
//				y: 0,
//				width: postTypeSelector.opts.selectedMediaType[0],
//				height: postTypeSelector.opts.selectedMediaType[1],
//				quality: 1,
//				pixelRatio: 1
			};

			let url = stage.toDataURL(cfg);

			let params = {
				text: url,
				textX: stage.find('#textObject')[0].getX(),
				textY: stage.find('#textObject')[0].getY(),
				imageWidth: stage.find('#mediaObject')[0].getWidth()*stage.find('#mediaObject')[0].attrs.scaleX,
				imageHeight: stage.find('#mediaObject')[0].attrs.height*stage.find('#mediaObject')[0].attrs.scaleY,
				imageX: stage.find('#mediaObject')[0].getX(),
				imageY: stage.find('#mediaObject')[0].getY(),
				canvasWidth: stage.getWidth(),
				canvasHeight: stage.getHeight()
			};
			console.log(params);

			stage.width(oldValues.width);
			stage.height(oldValues.height);
			stage.scale({ x: oldValues.scaleX, y: oldValues.scaleY });

			stage.find('#textTransformer')[0].show();
			stage.find('#mediaTransformer')[0].show();
			stage.find('#mediaObject')[0].show();
			window.gpi_ve.drawCanvas();

		});

		var textToggle = document.getElementById('btnTextToggle');
		textToggle.addEventListener('click', function(){
			let id=this.dataset.id;
			window.gpi_ve.objectToggleByID(id, function(){
				let ctrls = window.document.getElementById('textControllers');
				if (ctrls.style.display == 'none') { ctrls.style.display='block'; } else { ctrls.style.display='none'; }; 
			});
		});

		// если тип НЕ видео то скроем элементы навигащции по видео
		if (mediaType!=='video') {
			document.getElementById('videoControls').style.display='none';
			return false;
		}
		// КОНТРОЛЬ ВИДЕО
		document.getElementById('play').addEventListener('click', function() {
			window.gpi_ve.ids.stage.find('#mediaObject')[0].attrs.image.play();
			window.gpi_ve.ids.stage.userDefinedAnim.start();
		});
		document.getElementById('pause').addEventListener('click', function() {
			window.gpi_ve.ids.stage.find('#mediaObject')[0].attrs.image.pause();
			window.gpi_ve.ids.stage.userDefinedAnim.stop();
		});



		document.getElementById('videoTimestamp').addEventListener('mousedown',function(){
			window.gpi_ve.ids.stage.userDefinedAnim.start();
			console.log('mousedown');
		});
		document.getElementById('videoTimestamp').addEventListener('mousemove',function(){
			window.gpi_ve.ids.stage.find('#mediaObject')[0].attrs.image.currentTime=this.value;
		});
		document.getElementById('videoTimestamp').addEventListener('mouseup',function(){
			window.gpi_ve.ids.stage.userDefinedAnim.stop();
			console.log('mouseup');
		});
		document.getElementById('btnSetThumbnail').addEventListener('click', function(){
			console.log(window.gpi_ve.ids.stage.find('#mediaObject')[0].attrs.image.currentTime);
			
			var stage = window.gpi_ve.ids.stage;
			stage.find('#textTransformer')[0].hide();
			stage.find('#mediaTransformer')[0].hide();
			
			let oldValues = {width: stage.getWidth(), height: stage.getHeight(), scaleX:stage.scaleX(), scaleY:stage.scaleY()};
			var scaleX = stage.getWidth()/stage.scaleX();
			var scaleY = stage.getHeight()/stage.scaleY();
			stage.width(scaleX);
			stage.height(scaleY);
			stage.scale({ x: 1, y: 1 });
			window.gpi_ve.drawCanvas();

			var cfg = {
				mimeType: "image/jpeg",
			};

			let url = stage.toDataURL(cfg);

			let params = {
				text: url,
				textX: stage.find('#textObject')[0].getX(),
				textY: stage.find('#textObject')[0].getY(),
				imageWidth: stage.find('#mediaObject')[0].getWidth()*stage.find('#mediaObject')[0].attrs.scaleX,
				imageHeight: stage.find('#mediaObject')[0].attrs.height*stage.find('#mediaObject')[0].attrs.scaleY,
				imageX: stage.find('#mediaObject')[0].getX(),
				imageY: stage.find('#mediaObject')[0].getY(),
				canvasWidth: stage.getWidth(),
				canvasHeight: stage.getHeight()
			};
			console.log(params);

			stage.width(oldValues.width);
			stage.height(oldValues.height);
			stage.scale({ x: oldValues.scaleX, y: oldValues.scaleY });
			
			stage.find('#textTransformer')[0].show();
			stage.find('#mediaTransformer')[0].show();
			window.gpi_ve.drawCanvas();			
			
			
		});
		// КОНТРОЛЬ ВИДЕО КОНЕЦ
		window.addEventListener('resize', function() { window.gpi_ve.fitCanvas(); });	
	};



	// подгон размеров до заданных оберткой
	function fixSizes(w,h,dw,dh,comment=false) {
		//console.log([w,h],[dw,dh]);
		if (w>dw) {
			let aspect = dw/w;
			h = Math.round(h*aspect);
			w = dw;
		}
		//console.log(["after w>dw",w,h,comment]);

		if (h>dh) {
			// console.log("h>dh");
			let aspect = dh/h;
			w = Math.round(w*aspect);
			h = dh;
		}
		//console.log(["after h>dh",w,h,comment]);


		return [parseFloat(w),parseFloat(h)];
	}

	// изменение положения объекта
	function dragBoundFuncHelper(pos,cobj) {
		var newY = pos.y;
		var newX = pos.x;

		var objWidth = 0;
		if (typeof cobj.attrs.width === "undefined") { objWidth = cobj.textWidth; } else { objWidth = cobj.attrs.width; }
		var objHeight = 0;
		if (typeof cobj.attrs.height === "undefined") { objHeight = cobj.textHeight;  } else { objHeight = cobj.attrs.height; }

		var actualWidth = objWidth*cobj.attrs.scaleY;
		var actualHeight = objHeight*cobj.attrs.scaleX;
		var canvasHeight = cobj.parent.hitCanvas.height;
		var canvasWidth = cobj.parent.hitCanvas.width;

		if (pos.y < 0) { newY = 0; }
		if (pos.y > canvasHeight-actualHeight) { newY = canvasHeight-actualHeight; }
		if (newY<0) newY = 0;

		if (pos.x < 0) { 
			newX = 0; 
		}
		if (pos.x > canvasWidth-actualWidth) { newX = Math.round(canvasWidth-actualWidth); }
		if (newX<0) newX = 0;

		console.log([canvasWidth,canvasHeight],pos,[newX,newY]);

		return {
			x: newX,
			y: newY
		};
	}

	// изменение размеров объекта
	function boundBoxFuncHelper(oldBoundBox, newBoundBox, cobj){
		var canvasWidth = cobj.parent.hitCanvas.width;

		if (newBoundBox.width+newBoundBox.x > canvasWidth) {
			//console.log("newBoundBox.width+newBoundBox.x > canvasWidth");
			newBoundBox.width = canvasWidth-newBoundBox.x;
			newBoundBox.y = oldBoundBox.y;
			newBoundBox.height = newBoundBox.width*(oldBoundBox.height/oldBoundBox.width);
		}
		if (newBoundBox.x < 0) {
			//console.log("newBoundBox.x < 0");
			newBoundBox.x = 0;
			newBoundBox.y = oldBoundBox.y;
			newBoundBox.width = oldBoundBox.width;
			newBoundBox.height = newBoundBox.width*(oldBoundBox.height/oldBoundBox.width);
			return newBoundBox;
		}
		if (newBoundBox.y < 0) {
			//console.log("newBoundBox.y < 0");
			newBoundBox.y = 0;
			newBoundBox.height = oldBoundBox.height;
			newBoundBox.width = newBoundBox.height*(oldBoundBox.width/oldBoundBox.height);
		}

		return newBoundBox;
	}



	/* fake ajax request and responce */
	function getFileInfo(){
		//return {size: [2048,1403], text: "\u{1F601} КЛИКБЕЙТНЫЙ ЗАГОЛОВОК \u{1F601}".trim(), path: './assets/darth-vader.jpg', type: 'image', postType: 'igtv'};
//		return {size: [320,180], text: "\u{1F601} КЛИКБЕЙТНЫЙ ЗАГОЛОВОК \u{1F601}".trim(), path: './assets/BigBuckBunny_320x180.mp4', type: 'video', postType: 'igtv'};
		return {size: [1920,1080], text: "\u{1F601} КЛИКБЕЙТНЫЙ ЗАГОЛОВОК \u{1F601}".trim(), path: './assets/Bohdan Lukin - Web Application Firewall bypass techniques Workshop.mp4', type: 'video', postType: 'IGTV', postLayout: 'layout'};
	}
	
	
	
	window.onload = function() {
		
		
		// запрашиваем инфу о файле
		var mediaObject = getFileInfo();
		
		// инициализируем селекторы разрешения медиа и делаем колбек на рендер канваса
		postTypeSelector.init({
			postTypeDOMObject: 'postTypeSelector', 
			mediaTypeDOMObject:'mediaTypeSelector',
			postTypeDefault: mediaObject.postType.toUpperCase(),
			mediaTypeDefault: mediaObject.postLayout.toLowerCase(),
			callbackFunc: function(){
				redrawAll(document.getElementById('canvasWrapper'), mediaObject, postTypeSelector.opts.selectedMediaType);
			}
		});
		
		redrawAll(document.getElementById('canvasWrapper'), mediaObject, postTypeSelector.opts.selectedMediaType);
		
		// ставим слушателей на кнопки
		buttonsListeners(mediaObject.type);

		setTimeout(function(){console.log('Konva might be loaded');window.gpi_ve.drawCanvas();}, 500);
		

		dlog('window.onload - compleate');
		
	};
	
	//window.gpi_ve.drawCanvas();