var video = document.querySelector('video');

if(!navigator.getDisplayMedia && !navigator.mediaDevices.getDisplayMedia) {
	var error = 'Your browser does NOT support the getDisplayMedia API.';
	document.querySelector('h1').innerHTML = error;

	document.querySelector('video').style.display = 'none';
	$('#btn-start-recording').hide();
	$('#btn-stop-recording').hide();
	throw new Error(error);
}

function invokeGetDisplayMedia(success, error) {
	var displaymediastreamconstraints = {
		video: {
			displaySurface: 'monitor', // monitor, window, application, browser
			logicalSurface: true,
			cursor: 'always' // never, always, motion
		}
	};

	// above constraints are NOT supported YET
	// that's why overridnig them
	displaymediastreamconstraints = {
		video: true
	};

	if(navigator.mediaDevices.getDisplayMedia) {
		navigator.mediaDevices.getDisplayMedia(displaymediastreamconstraints).then(success).catch(error);
	}
	else {
		navigator.getDisplayMedia(displaymediastreamconstraints).then(success).catch(error);
	}
}

function captureScreen(callback) {
	invokeGetDisplayMedia(function(screen) {
		addStreamStopListener(screen, function() {
			$('#btn-stop-recording').click();
		});
		callback(screen);
	}, function(error) {
		console.error(error);
		alert('Unable to capture your screen. \n' + error);
		$('#btn-start-recording').show().prop('disabled', false);
	});
}

var listOfFilesUploaded = [];

function uploadToServer(recordRTC, callback) {
	var blob = recordRTC instanceof Blob ? recordRTC : recordRTC.blob;
	var fileType = blob.type.split('/')[0] || 'audio';
	var fileName = moment().format('YYYYMMDDhmmss');
	var upload_url = document.getElementById('record-rtc-url').value;
	var ticket = document.getElementById('record-rtc-ticket').value;
	var customFileName = document.getElementById('record-name').value;

	if (fileType === 'audio') {
		fileName = customFileName ? customFileName : 'audio_record_' + fileName;
		fileName += '.' + (!!navigator.mozGetUserMedia ? 'ogg' : 'wav');
	} else {
		fileName = customFileName ? customFileName : 'video_record_' + fileName;
		fileName += '.webm';
	}

	// create FormData
	var formData = new FormData();
	formData.append(fileType + 'filename', fileName);
	formData.append(fileType + 'blob', blob);
	formData.append('ticket', ticket);

	callback('Uploading ' + fileType + ' recording to server.');

	makeXMLHttpRequest(upload_url, formData, function(progress, response) {
		if (progress !== 'upload-ended') {
			callback(progress);
			return;
		}

		if (!!response) {
			response = JSON.parse(response);
			var fileId = response.fileId;
			var thumbBox = '';
			var fileUrl = '';

			if (fileId) {
				var thumbBox = '{img fileId="' + fileId + '" thumb="box"}';
				var fileUrl = 'tiki-download_file.php?fileId=' + fileId;
			}
		}

		var fileData = {
			'thumbBox': thumbBox,
			'fileUrl': fileUrl,
			'fileName': fileName
		};

		callback('ended', fileData);

		// to make sure we can delete as soon as visitor leaves
		listOfFilesUploaded.push(fileName);
	});
}

function makeXMLHttpRequest(url, data, callback) {
	var request = new XMLHttpRequest();
	request.onreadystatechange = function() {
		if (request.readyState == 4) {
			callback('upload-ended', request.response);
		}
	};

	request.upload.onloadstart = function() {
		callback('Upload started...');
	};

	request.upload.onprogress = function(event) {
		callback('Upload progress ' + Math.round(event.loaded / event.total * 100) + "%");
	};

	request.upload.onload = function() {
		callback('Upload finished');
	};

	request.upload.onerror = function(error) {
		callback('Failed to upload to server');
		console.error('XMLHttpRequest failed', error);
	};

	request.upload.onabort = function(error) {
		callback('Upload aborted.');
		console.error('XMLHttpRequest aborted', error);
	};

	request.open('POST', url);
	request.send(data);
}

function stopRecordingCallback() {
	video.src = video.srcObject = null;
	video.src = URL.createObjectURL(recorder.getBlob());

	recorder.screen.stop();

	var $feedback = $('#upload-feedback').show();
	var autoUpload = $('#record-rtc-auto-upload').is(':checked');

	var html = '<div>';
	html += '<br/><video style="width: 100%; max-width: 500px;" src="' + video.src + '" controls=""></video>';
	html += '</div>';
	$feedback.html(html);

	if (autoUpload == true) {
		startUpload();
	} else {
		$('#btn-upload-recording').show();
	}

	$('#btn-start-recording').show();
	$('#btn-stop-recording').hide();
}

var recorder; // globally accessible

$('#btn-start-recording').on('click', function () {

	$(this).prop('disabled', true);
	$('#upload-feedback').hide();

	var callback = function (screen) {
		video.srcObject = screen;

		recorder = RecordRTC(screen, {
			type: 'video'
		});

		recorder.startRecording();

		// release screen on stopRecording
		recorder.screen = screen;

		$('#btn-start-recording')
			.hide()
			.prop('disabled', false);
		$('#btn-stop-recording').show();
	};

	captureScreen(callback);
});

$('#btn-stop-recording').on('click', function(e) {
	e.preventDefault();
	if (recorder && typeof recorder.stopRecording === "function") {
		recorder.stopRecording(stopRecordingCallback);
	}
});

$('#btn-upload-recording').on('click', function(e) {
	e.preventDefault();
	$('#btn-upload-recording').hide();
	startUpload();
});

function startUpload() {
	if (recorder) {
		var $feedback = $('#upload-feedback').show();
		var autoUpload = $('#record-rtc-auto-upload');
		autoUpload.removeAttr('checked');
		uploadToServer(recorder, function(progress, fileData) {
			if(progress === 'ended' && fileData.fileUrl && fileData.fileName) {
				var html = '<div>';
				html += '<br/><video style="width: 100%; max-width: 500pxx" src="' + fileData.fileUrl + '" controls=""></video>';
				html += '<br/><a target="_blank" href="' + fileData.fileUrl + '">';
				html += '<span>' + fileData.fileName + '</span>';
				html += '</a>';
				if (fileData.thumbBox) {
					html += '<br/><code>' + fileData.thumbBox + '</code>';
				}
				html += '</div>';
				$feedback.html(html);
				return;
			}
			$feedback.html(progress);
		});
		recorder.destroy();
		recorder = null;
	}
}

function addStreamStopListener(stream, callback) {
	stream.addEventListener('ended', function() {
		callback();
		callback = function() {};
	}, false);
	stream.addEventListener('inactive', function() {
		callback();
		callback = function() {};
	}, false);
	stream.getTracks().forEach(function(track) {
		track.addEventListener('ended', function() {
			callback();
			callback = function() {};
		}, false);
		track.addEventListener('inactive', function() {
			callback();
			callback = function() {};
		}, false);
	});
}
