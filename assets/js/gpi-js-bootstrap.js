function notify(type, message) {
	toastr.options = {
		"closeButton": true,
		"debug": false,
		"newestOnTop": false,
		"progressBar": true,
		"positionClass": "toast-top-right",
		"preventDuplicates": false,
		"onclick": null,
		"showDuration": "300",
		"hideDuration": "1000",
		"timeOut": "5000",
		"extendedTimeOut": "1000",
		"showEasing": "swing",
		"hideEasing": "linear",
		"showMethod": "fadeIn",
		"hideMethod": "fadeOut"
	}
	toastr[type](message);
}

function apiRequest(request, callback) {
	$.ajax({
		url: window.gpi_url + '/app/api.php',
		data: request,
		method: "POST",
		dataType: "JSON",
		cache: false,
		timeout: 300000,
		statusCode: {
			500: function () {
				callback({message: v2, type: "error", opts: 'Server on maintence.<br>Try do it later.'});
			},
			404: function () {
				callback({message: v2, type: "error", opts: 'API error 404!.<br> Try refresh page!'});
			}
		},
		success: function (data) {
			callback(data);
		},
		error: function (v1, v2, v3) {
			callback({message: v2, type: "error", opts: false});
		}
	});
}

function resizeTextarea(field){
	// Reset field height

	if (typeof field == 'object') {
		field = $(field)[0];
	}

	if (typeof field == 'string') {
		field = $(field)[0];
	}
	
	field.style.height = 'inherit';

	// Get the computed styles for the element
	var computed = window.getComputedStyle(field);

	// Calculate the height
	var height = parseInt(computed.getPropertyValue('border-top-width'), 10)
	             + parseInt(computed.getPropertyValue('padding-top'), 10)
	             + field.scrollHeight
	             + parseInt(computed.getPropertyValue('padding-bottom'), 10)
	             + parseInt(computed.getPropertyValue('border-bottom-width'), 10);

	field.style.height = height + 'px';
}

//XXX: unused
function resizeTextarea_old(textarea) {
	var lines = $(textarea).val().split('\n');
	var width = $(textarea).attr('cols');
	var height = 1;
	for (var i = 0; i < lines.length; i++) {
		var linelength = lines[i].length;
		if (linelength >= width) {
			height += Math.ceil(linelength / width);
		}
	}
	height += lines.length;
	$(textarea).attr('rows',height+1);
}

function checkforWordsLimits(sObj, wlimit){
	var words = $(sObj).val().split(' ');
	var wlib = [];
	for (i in words) {
		if (words[i].trim()!=='') {
			wlib.push(words[i])
		}
	}
	if (wlib.length>wlimit) {
		return false;
	} else {
		return true;
	}
}

function getCursorPos(input) {
    if ("selectionStart" in input && document.activeElement == input) {
        return {
            start: input.selectionStart,
            end: input.selectionEnd
        };
    }
    else if (input.createTextRange) {
        var sel = document.selection.createRange();
        if (sel.parentElement() === input) {
            var rng = input.createTextRange();
            rng.moveToBookmark(sel.getBookmark());
            for (var len = 0;
                     rng.compareEndPoints("EndToStart", rng) > 0;
                     rng.moveEnd("character", -1)) {
                len++;
            }
            rng.setEndPoint("StartToStart", input.createTextRange());
            for (var pos = { start: 0, end: len };
                     rng.compareEndPoints("EndToStart", rng) > 0;
                     rng.moveEnd("character", -1)) {
                pos.start++;
                pos.end++;
            }
            return pos;
        }
    }
    return -1;
}

function setCursorPos(input, start, end) {
    if (arguments.length < 3) end = start;
    if ("selectionStart" in input) {
        setTimeout(function() {
            input.selectionStart = start;
            input.selectionEnd = end;
        }, 1);
    }
    else if (input.createTextRange) {
        var rng = input.createTextRange();
        rng.moveStart("character", start);
        rng.collapse();
        rng.moveEnd("character", end - start);
        rng.select();
    }
}