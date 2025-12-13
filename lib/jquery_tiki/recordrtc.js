var video = document.querySelector('video');
var audio = document.querySelector('audio');
var isRecording = false;

let audioContexts = {};
let analysers = {};
let animationIds = {};
let recordingStartTime;
let recordingTimerInterval;


function createWaveBars(stream, fieldId) {
    const container = document.getElementById(`waveform-canvas-${fieldId}`);
    const canvas = document.getElementById(`wave-canvas-${fieldId}`);

    if (!container || !canvas) return;

    container.style.display = 'block';
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const source = audioContext.createMediaStreamSource(stream);
    const analyser = audioContext.createAnalyser();
    analyser.fftSize = 256;
    const bufferLength = analyser.frequencyBinCount;
    const dataArray = new Uint8Array(bufferLength);

    source.connect(analyser);

    audioContexts[fieldId] = audioContext;
    analysers[fieldId] = analyser;

    function draw() {
        animationIds[fieldId] = requestAnimationFrame(draw);
        analyser.getByteFrequencyData(dataArray);

        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const barWidth = (canvas.width / bufferLength) * 2.5;
        let x = 0;

        for (let i = 0; i < bufferLength; i++) {
            const barHeight = dataArray[i] / 2;
            ctx.fillStyle = '#007bff';
            ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
            x += barWidth + 1;
        }
    }

    draw();
}

function startRecordingTimer() {
    recordingStartTime = Date.now();
    const $timer = $('#recording-timer');
    $timer.show();

    recordingTimerInterval = setInterval(() => {
        const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
        const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
        const seconds = String(elapsed % 60).padStart(2, '0');
        $timer.text(`${minutes}:${seconds}`);
    }, 1000);
}


function removeWaveBars(fieldId) {
    const container = document.getElementById(`waveform-canvas-${fieldId}`);
    if (container) {
        container.style.display = 'none';
    }
    if (animationIds[fieldId]) {
        cancelAnimationFrame(animationIds[fieldId]);
        delete animationIds[fieldId];
    }
    if (audioContexts[fieldId]) {
        audioContexts[fieldId].close();
        delete audioContexts[fieldId];
    }
    delete analysers[fieldId];
}


if(!navigator.getDisplayMedia && !navigator.mediaDevices.getDisplayMedia) {
    var error = tr('Recording WebRTC not supported in this browser.');
    $('.box-recordrtc .card-body').html(error);

    throw new Error(error);
}

var isEdge = navigator.userAgent.indexOf('Edge') !== -1 && (!!navigator.msSaveOrOpenBlob || !!navigator.msSaveBlob);
var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

const VIDEO_STREAM_CONSTRAINTS = {
    video: true,
};

const AUDIO_STREAM_CONSTRAINTS = {
    audio: isEdge ? true : {
        echoCancellation: false
    }
};

const VIDEO_AND_AUDIO_STREAM_CONSTRAINTS = {
    ...AUDIO_STREAM_CONSTRAINTS,
    ...VIDEO_STREAM_CONSTRAINTS
};

// globally accessible
var recorder;
var microphone;
var recordingInstance;

var listOfFilesUploaded = [];

function uploadToServer(recordRTC, inputs, callback) {
    const blob = recordRTC instanceof Blob ? recordRTC : recordRTC.blob;
    const fileType = blob.type.split('/')[0] || 'audio';
    let fileName = moment().format('YYYYMMDDhmmss');
    const upload_url = $.service("recordrtc", "upload");
    const ticket = inputs.ticket;
    const galleryId = inputs.galleryId;
    const customFileName = inputs.customFileName;

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
    formData.append('galleryId', galleryId);

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
            var nextTicket = '';

            if (fileId) {
                thumbBox = '{mediaplayer src="display' + fileId + '"}';
                fileUrl = 'tiki-download_file.php?fileId=' + fileId;
                nextTicket = response.nextTicket;
            }
        }

        var fileData = {
            'thumbBox': thumbBox,
            'fileUrl': fileUrl,
            'fileName': fileName,
            'ticket': nextTicket,
            'fileId': fileId,
            'fileType': fileType
        };

        callback('ended', fileData, nextTicket);

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

function startUpload(trackerInput) {
    if (recorder) {
        recordingInstance.toggleUploadingState(true);
        if (trackerInput) {
            uploadToServer(recorder, trackerInput, (progress, fileData) => {
                if (progress === 'ended') {
                    showMessage(tr('File uploaded'), 'success');
                    window[`addFile_${trackerInput.fieldId}`](fileData.fileId, fileData.fileType, fileData.fileName);
                    recordingInstance.toggleUploadingState(false);
                }
            });
        } else {
            var $feedback = $('#upload-feedback span').text('Uploading... Please wait.').show();
            var autoUpload = $('#record-rtc-auto-upload');
            autoUpload.removeAttr('checked');

            const inputs = {
                ticket: document.getElementById('record-rtc-ticket').value,
                customFileName: document.getElementById('record-name').value,
                galleryId: document.getElementById('record-rtc-gallery-id').value
            };

            uploadToServer(recorder, inputs, function(progress, fileData, ticket) {
                if(progress === 'ended') {
                    if (fileData.fileUrl && fileData.fileName) {
                        let extension = fileData.fileName.split('.').pop();
                        $('#record-download a').attr('href', fileData.fileUrl).text(fileData.fileName);
                        $('#record-download span').text('Upload finished ');

                        if (fileData.thumbBox) {
                            $('#record-download').append('<br/><code>' + fileData.thumbBox + '</code>');
                        }

                        if (ticket) {
                            document.getElementById('record-rtc-ticket').value = ticket;
                        }

                        recordingInstance.toggleUploadingState(false);

                        recorder.destroy();
                        recorder = null;
                    } else {
                        $('#btn-upload-recording').show();
                        $('#record-download span').html('<p>Something went wrong when uploading this file. Please try again.</p>');
                    }
                }
            });
        }
    }
}

/**
 * Request permissions for specific constraints related with the display.
 * @param constraints
 * @param onSuccess
 * @param onFailure
 */
function getDisplayMedia(constraints, onSuccess, onFailure) {
    if(navigator.mediaDevices.getDisplayMedia) {
        navigator.mediaDevices.getDisplayMedia(constraints).then(onSuccess).catch(onFailure);
    }
    else {
        navigator.getDisplayMedia(constraints).then(onSuccess).catch(onFailure);
    }
}

/**
 * Request permissions for specific constraints associated with the media input.
 * @param constraints
 * @param onSuccess
 * @param onFailure
 */
function getUserMedia(constraints, onSuccess, onFailure) {
    if(navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia(constraints).then(onSuccess).catch(onFailure);
    }
    else {
        navigator.getUserMedia(constraints).then(onSuccess).catch(onFailure);
    }
}

/**
 * Show recording feedback based on currently recorded blob type
 * @return void
 */
function showRecordingFeedback(recordingType) {
    const blob = recorder.getBlob();
    const fileType = blob.type.split('/')[0] || 'audio';
    const src = URL.createObjectURL(blob);

    var html = '<div>';

    if (fileType === 'audio') {
        html += '<br/><audio style="width: 100%; max-width: 500px" src="' + src + '" controls=""></audio>';
    } else {
        html += '<br/><video style="width: 100%; max-width: 500px" src="' + src + '" controls=""></video>';
    }

    html += '<br/><div id="record-download">' +
        '<span></span>' +
        '<a target="_blank" href=""></a>' +
        '</div>';

    html += '</div>';

    var $feedback = $('#upload-feedback').show();
    $feedback.html(html);
}

function showTrackerFileRecordingFeedback() {
    const blob = recorder.getBlob();
    if (!blob) {
        showMessage('No recording blob found.', 'error');
        return;
    }
    const videoUrl = URL.createObjectURL(recorder.getBlob());

    $('#record-preview').remove();

    const $preview = $('<div>', {
        id: 'record-preview',
        class: 'record-preview',
    }).appendTo('body');

    $preview.html(`
        <video src="${videoUrl}" controls autoplay width="640" style="display:block; border-radius:4px;"></video>
        <button id="close-preview" style="margin-top: 10px; padding: 8px 16px; background: #ff5c5c; color: white; border: none; border-radius: 4px; cursor: pointer;">${tr('Cancel')}</button>
    `);

    $('#close-preview').on('click', function () {
        $('#record-preview').remove();
    });
    $('#record-preview').html(`
        <video src="${videoUrl}" controls autoplay width="640" style="display:block; border-radius:4px;"></video>
        <button id="close-preview" style="
            margin-top: 10px;
            padding: 8px 16px;
            background: #ff5c5c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        ">${tr('Cancel')}</button>
    `);

    if (!$('#record-rtc-auto-upload').is(':checked')) {
        $('#btn-upload-recording').show();
    }
    $preview.html(`
        <video src="${videoUrl}" controls autoplay width="640" style="display:block; border-radius:4px;"></video>
        <div style="margin-top: 10px;">
            <button id="btn-upload-now" class="btn btn-primary" style="margin-right: 10px; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">${tr('Upload')}</button>
            <button id="close-preview" class="btn btn-secondary" style="padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">${tr('Cancel')}</button>
        </div>
    `);

    $('#btn-upload-now').on('click', function () {
        $('#record-preview').remove();
            startUpload({
                galleryId: $(recordingInstance.startButton).data('gallery-id'),
                ticket: $("input[name='ticket']").val(),
                fieldId: $(recordingInstance.startButton).data('field-id'),
            });
    });

    $('#close-preview').on('click', function () {
        $('#record-preview').remove();
    });
}

class RecordBase {
    constructor(triggerElement) {
        this.startButton = triggerElement;
        this.stopButton = $(triggerElement).next(".stop-recording")[0];

        const fieldId = $(triggerElement).data('field-id');
        this.timerElement = $(`.recording-timer[data-field-id="${fieldId}"]`);

        this.startButtonHtml = $(triggerElement).html();
        this.uploadingStateHtml = $.BUTTON_LOADER_MARKUP + " " + tr("Uploading...");
    }

    startTimer() {
        this.recordingStartTime = Date.now();
        this.timerElement.show();

        this.recordingTimerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.recordingStartTime) / 1000);
            const minutes = String(Math.floor(elapsed / 60)).padStart(2, '0');
            const seconds = String(elapsed % 60).padStart(2, '0');
            this.timerElement.text(`${minutes}:${seconds}`);
        }, 1000);
         $('.btn-close[data-bs-dismiss="modal"]').hide();
    }

    stopTimer() {
        clearInterval(this.recordingTimerInterval);
        this.timerElement.hide().text('00:00');
         $('.btn-close[data-bs-dismiss="modal"]').show();
    }

    stopRecording() {
        this.stopTimer();

        recorder.stopRecording(() => {
            $(this.startButton).removeClass("d-none");
            $(this.stopButton).addClass("d-none");

            if ($(this.startButton).data('tracker-files')) {
                showTrackerFileRecordingFeedback();
                return;
            }

            showRecordingFeedback();

            if ($('#record-rtc-auto-upload').is(':checked')) {
                startUpload();
            } else {
                $('#btn-upload-recording').show();
            }
        });
    }

    requestErrorHandler(error) {
        showMessage('Unable to start the recording.', 'error');
        $(this.startButton).removeClass("d-none").prop('disabled', false);
    }

    toggleUploadingState(isUploading) {
        if (isUploading) {
            $(this.startButton).html(this.uploadingStateHtml);
            $(this.startButton).prop('disabled', true);
        } else {
            $(this.startButton).html(this.startButtonHtml);
            $(this.startButton).prop('disabled', false);
        }
    }
}

class RecordMicrophone extends RecordBase {
    constructor(triggerElement) {
        super(triggerElement);

        this.requestPermissions((mic) => {
            microphone = mic;

            if(isSafari) {
                replaceAudio();

                alert('Please click startRecording button again. First time we tried to access your microphone. Now we will record it.');
            }

            var options = {
                type: 'audio',
                numberOfAudioChannels: isEdge ? 1 : 2,
                checkForInactiveTracks: true,
                bufferSize: 16384
            };

            if(isSafari || isEdge) {
                options.recorderType = StereoAudioRecorder;
            }

            if(navigator.platform && navigator.platform.toString().toLowerCase().indexOf('win') === -1) {
                options.sampleRate = 48000; // or 44100 or remove this line for default
            }

            if(isSafari) {
                options.sampleRate = 44100;
                options.bufferSize = 4096;
                options.numberOfAudioChannels = 2;
            }

            if(recorder) {
                recorder.destroy();
                recorder = null;
            }

            recorder = RecordRTC(microphone, options);
            recorder.startRecording();
            this.startTimer();
            const fieldId = $(triggerElement).data('field-id');
            createWaveBars(microphone, fieldId);

            $(triggerElement).addClass("d-none").prop('disabled', false);
            $(this.stopButton).removeClass("d-none");
        });
    }

    stopRecording() {

        const fieldId = $(this.startButton).data('field-id');
        microphone.stop();
        removeWaveBars(fieldId);

        super.stopRecording();
    }

    requestPermissions(callback) {
        getUserMedia(AUDIO_STREAM_CONSTRAINTS, function(mic) {
            callback(mic);
        }, this.requestErrorHandler);
    }
}

class RecordCameraAndAudio extends RecordBase {
    constructor(triggerElement) {
        super(triggerElement);

        getUserMedia(VIDEO_AND_AUDIO_STREAM_CONSTRAINTS, (camera) => {
            recorder = RecordRTC(camera, {
                type: 'video'
            });

            recorder.startRecording();
            this.startTimer();
            createWaveBars(this.stopButton);
            recorder.camera = camera;

            $(this.startButton).addClass("d-none").prop('disabled', false);
            $(this.stopButton).removeClass("d-none");
        }, this.requestErrorHandler);
    }

    stopRecording() {
        recorder.camera.stop();
        super.stopRecording();
    }
}

class RecordScreen extends RecordBase {
    constructor(triggerElement) {
        super(triggerElement);
        this.requestPermissions((screen) => {
            recorder = RecordRTC(screen, {
                type: 'video'
            });

            recorder.startRecording();

            // release screen on stopRecording
            recorder.screen = screen;

            $(triggerElement).addClass("d-none").prop('disabled', false);
            $(this.stopButton).removeClass("d-none");
            this.startTimer();
        });
    }

    stopRecording() {
        recorder.screen.stop();
        super.stopRecording();
    }

    requestPermissions(callback) {
        getDisplayMedia(VIDEO_STREAM_CONSTRAINTS, (screen) => {
            addStreamStopListener(screen, () => {
                $(this.stopButton).trigger("click");
            });

            callback(screen);
        }, this.requestErrorHandler);
    }
}

class RecordScreenAndMicrophone extends RecordScreen {
    requestPermissions(callback) {
        getDisplayMedia(VIDEO_STREAM_CONSTRAINTS, (screen) => {
            getUserMedia(AUDIO_STREAM_CONSTRAINTS, (mic) => {
                screen.addTrack(mic.getTracks()[0]);

                addStreamStopListener(screen, () => {
                    $(this.stopButton).trigger("click");
                });

                callback(screen);
            });
        }, this.requestErrorHandler);
    }
}

$('body').on('click', '.start-recording', function () {
    $(this).prop('disabled', true);
    $('#upload-feedback').hide();

    switch ($(this).data('type') || $('#mod_record_rtc_recording_type').val()) {
        case 'screen':
            recordingInstance = new RecordScreen(this);
            break;
        case 'microphone':
        case 'audio':
            recordingInstance = new RecordMicrophone(this);
            break;
        case 'screen,microphone':
        case 'screenandaudio':
            recordingInstance = new RecordScreenAndMicrophone(this);
            break;
        case 'camera,microphone':
        case 'cameraandaudio':
            recordingInstance = new RecordCameraAndAudio(this);
            break;
        default:
            console.error('Recording type not implemented.');
    }
});

$('body').on('click', '.stop-recording', function(e) {
    e.preventDefault();

    recordingInstance.stopRecording(this);
    removeWaveBars(fieldId);

});

$('#btn-upload-recording').on('click', function(e) {
    e.preventDefault();
    $('#btn-upload-recording').hide();

    startUpload();
});

$('#mod_record_rtc_recording_type').on('change', function(e) {
    $('#btn-start-recording').prop('disabled', !$(this).val());
});
