function isMobileDevice() {
    const mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Tablet/i;

    const isTouchDevice = navigator.maxTouchPoints > 0 || 'ontouchstart' in window;
    const isSmallScreen = window.screen.width <= 768 || window.innerWidth <= 768;

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) ||
                  (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    return mobileRegex.test(navigator.userAgent) ||
           isIOS ||
           (isTouchDevice && isSmallScreen);
}



var video = document.querySelector('video');
var audio = document.querySelector('audio');
var isRecording = false;

let audioContexts = {};
let analysers = {};
let animationIds = {};
let recordingStartTime;
let recordingTimerInterval;
let videoPreviewElements = {};


function createVideoPreview(stream, fieldId) {
    const containerId = `video-preview-${fieldId}`;
    let container = document.getElementById(containerId);

    if (!container) {
        container = document.createElement('div');
        container.id = containerId;
        container.className = 'video-preview-container';

        const waveformCanvas = document.getElementById(`waveform-canvas-${fieldId}`);
        if (waveformCanvas && waveformCanvas.parentElement) {
            waveformCanvas.parentElement.insertBefore(container, waveformCanvas);
        }
    }

    const video = document.createElement('video');
    video.id = `live-video-${fieldId}`;
    video.autoplay = true;
    video.muted = true;
    video.playsInline = true;
    video.srcObject = stream;

    container.innerHTML = '';
    container.appendChild(video);
    container.style.display = 'block';

    videoPreviewElements[fieldId] = { container, video };
}

function removeVideoPreview(fieldId) {
    const preview = videoPreviewElements[fieldId];
    if (preview) {
        if (preview.video && preview.video.srcObject) {
            preview.video.srcObject.getTracks().forEach(track => track.stop());
            preview.video.srcObject = null;
        }
        if (preview.container) {
            preview.container.style.display = 'none';
            preview.container.innerHTML = '';
        }
        delete videoPreviewElements[fieldId];
    }
}

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
    if (animationIds[fieldId]) {
        cancelAnimationFrame(animationIds[fieldId]);
        delete animationIds[fieldId];
    }
    if (audioContexts[fieldId]) {
        audioContexts[fieldId].close();
        delete audioContexts[fieldId];
    }
    delete analysers[fieldId];

    removeVideoPreview(fieldId);
}


if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
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
/**
 * @type {RecordBase}
 */
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

function startUpload(customInputs = {}) {
    if (recorder) {
        recordingInstance.toggleUploadingState(true);
        if (customInputs.fieldId) {
            uploadToServer(recorder, customInputs, (progress, fileData) => {
                if (progress === 'ended') {
                    showMessage(tr('File uploaded'), 'success');
                    window[`addFile_${customInputs.fieldId}`](fileData.fileId, fileData.fileType, fileData.fileName);
                    recordingInstance.toggleUploadingState(false);
                }
            });
        } else {
            var $feedback = $('#upload-feedback span').text('Uploading... Please wait.').show();
            var autoUpload = $('#record-rtc-auto-upload');
            autoUpload.removeAttr('checked');


            uploadToServer(recorder, recordingInstance.isCommentRecording ? customInputs : {
                ticket: document.getElementById('record-rtc-ticket').value,
                customFileName: document.getElementById('record-name').value,
                galleryId: document.getElementById('record-rtc-gallery-id').value
            }, function(progress, fileData, ticket) {
                if(progress === 'ended') {
                    if (fileData.fileUrl && fileData.fileName) {
                        if (recordingInstance.isCommentRecording) {
                            $.closeModal();
                            recordingInstance.handleCommentAttachment(fileData.fileId);
                        } else {
                            let extension = fileData.fileName.split('.').pop();
                            $('#record-download a').attr('href', fileData.fileUrl).text(fileData.fileName);
                            $('#record-download span').text('Upload finished ');

                            if (fileData.thumbBox) {
                                $('#record-download').append('<br/><code>' + fileData.thumbBox + '</code>');
                            }

                            if (ticket) {
                                document.getElementById('record-rtc-ticket').value = ticket;
                            }
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
    // Check if screen capture API is available
    if(!navigator.mediaDevices || (!navigator.mediaDevices.getDisplayMedia && !navigator.getDisplayMedia)) {
        if(onFailure) {
            onFailure(new Error(tr('Screen recording is not supported on this device/browser.')));
        }
        return;
    }

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
function showRecordingFeedback() {
    if (!recorder) {
        showMessage(tr('No recording available.'), 'error');
        return;
    }

    const blob = recorder.getBlob();
    const fileType = blob.type.split('/')[0] || 'audio';
    const src = URL.createObjectURL(blob);

    $.ajax({
        url: $.serviceUrl({
            controller: 'recordrtc',
            action: fileType === 'audio' ? 'render_audio_preview' : 'render_video_preview'
        }),
        type: 'POST',
        data: {
            src: src
        },
        success: function(response) {
            var $feedback = $('#upload-feedback').show();
            $feedback.html(response.html);
        },
        error: function() {
            showMessage(tr('Failed to render preview.'), 'error');
        }
    });
}

function showTextareaOrTrackerFileRecordingFeedback() {
    const blob = recorder.getBlob();
    if (!blob) {
        showMessage(tr('No recording blob found.'), 'error');
        return;
    }

    const fileType = blob.type.split('/')[0] || 'video';
    const mediaUrl = URL.createObjectURL(blob);

    $('#record-preview').remove();

    const $preview = $('<div>', {
        id: 'record-preview',
        class: 'record-preview',
    }).appendTo('body');

    // Render template via AJAX
    $.ajax({
        url: $.serviceUrl({
            controller: 'recordrtc',
            action: 'render_modal_preview'
        }),
        type: 'POST',
        data: {
            filetype: fileType,
            mediaurl: mediaUrl
        },
        success: function(response) {
            $preview.html(response.html);

            $('#btn-upload-now').on('click', function () {
                $('#record-preview').remove();
                const inputs = {
                    galleryId: $(recordingInstance.startButton).data('gallery-id'),
                    ticket: $("input[name='ticket']").val(),
                };

                if (recordingInstance.isTrackerFileRecording) {
                    inputs.fieldId = $(recordingInstance.startButton).data('field-id');
                }

                startUpload(inputs);
            });

            $('#close-preview').on('click', function () {
                $('#record-preview').remove();
            });
        },
        error: function() {
            showMessage(tr('Failed to load modal preview template'), 'error');
        }
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

        this.isCommentRecording = !!$(triggerElement).data('textarea-filerecording');
        this.isTrackerFileRecording = !!$(triggerElement).data('tracker-files');

        this.commentAreaId = $(triggerElement).data('area-id');
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

            if (this.isCommentRecording || this.isTrackerFileRecording) {
                showTextareaOrTrackerFileRecordingFeedback();
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
        let errorMessage = tr('Unable to start the recording.');

        if (error && error.name === 'NotAllowedError') {
            errorMessage = tr('Permission denied. Please allow access to your microphone/camera.');
        } else if (error && error.name === 'NotFoundError') {
            errorMessage = tr('No recording device found. Please connect a microphone or camera.');
        } else if (error && error.message) {
            errorMessage = error.message;
        }

        showMessage(errorMessage, 'error');
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

    /**
     * Attach the uploaded file to the comment form, so that a relation with the comment can be created from it upon comment submission.
     * @param {Number} uploadedFileId - The ID returned from the server after the file has been uploaded.
     */
    handleCommentAttachment(uploadedFileId) {
        if (!this.isCommentRecording) return;

        insertAt(this.commentAreaId, `((${uploadedFileId}|file))`);
        const $textarea = $(`#${this.commentAreaId}`);
        $textarea.after(`<input type="hidden" name="related_files[]" value="${uploadedFileId}">`);
    }
}

class RecordMicrophone extends RecordBase {
    constructor(triggerElement) {
        super(triggerElement);

        this.requestPermissions((mic) => {
            microphone = mic;

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
        getUserMedia(AUDIO_STREAM_CONSTRAINTS, (mic) => {
            callback(mic);
        }, this.requestErrorHandler.bind(this));
    }
}

class RecordCameraAndAudio extends RecordBase {
    constructor(triggerElement) {
        super(triggerElement);

        getUserMedia(VIDEO_AND_AUDIO_STREAM_CONSTRAINTS, (camera) => {
            const fieldId = $(triggerElement).data('field-id');

            recorder = RecordRTC(camera, {
                type: 'video'
            });

            recorder.startRecording();
            this.startTimer();

            createVideoPreview(camera, fieldId);

            createWaveBars(new MediaStream(camera.getAudioTracks()), fieldId);

            recorder.camera = camera;

            $(this.startButton).addClass("d-none").prop('disabled', false);
            $(this.stopButton).removeClass("d-none");
        }, this.requestErrorHandler.bind(this));
    }

    stopRecording() {
        const fieldId = $(this.startButton).data('field-id');
        recorder.camera.stop();
        removeWaveBars(fieldId);
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
        }, this.requestErrorHandler.bind(this));
    }
}

class RecordScreenAndMicrophone extends RecordScreen {
    requestPermissions(callback) {
        getDisplayMedia(VIDEO_STREAM_CONSTRAINTS, (screen) => {
            getUserMedia(AUDIO_STREAM_CONSTRAINTS, (mic) => {
                screen.addTrack(mic.getTracks()[0]);
                createWaveBars(mic, $(this.startButton).data('field-id'));

                addStreamStopListener(screen, () => {
                    $(this.stopButton).trigger("click");
                });

                callback(screen);
            }, (error) => {
                this.requestErrorHandler(error);
            });
        }, this.requestErrorHandler.bind(this));
    }
}

$('body').on('click', '.start-recording', function (e) {
    const recordingType = $(this).data('type') || $('#mod_record_rtc_recording_type').val();

    if (isMobileDevice() && (recordingType === 'screen' || recordingType === 'screen,microphone' || recordingType === 'screenandaudio')) {
        e.preventDefault();
        e.stopPropagation();
        showMessage(tr('Screen recording is not supported on mobile devices. Please use audio or video recording instead.'), 'warning');
        return false;
    }

    $(this).prop('disabled', true);
    $('#upload-feedback').hide();

    switch (recordingType) {
        case 'screen':
            recordingInstance = new RecordScreen(this);
            break;
        case 'microphone':
        case 'audio':
            recordingInstance = new RecordMicrophone(this);
            break;
        case 'screen,microphone':
        case 'microphone,screen':
        case 'screenandaudio':
            recordingInstance = new RecordScreenAndMicrophone(this);
            break;
        case 'camera,microphone':
        case 'microphone,camera':
        case 'cameraandaudio':
            recordingInstance = new RecordCameraAndAudio(this);
            break;
        case 'camera':
            showMessage(tr('Camera-only recording is not supported. Please select camera and microphone recording to use the camera.'), 'warning');
            $(this).prop('disabled', false);
            break;
        default:
            $(this).prop('disabled', false);
            console.error('Recording type not implemented.');
    }
});

$('body').on('click', '.stop-recording', function(e) {
    e.preventDefault();
    recordingInstance.stopRecording(this);
});

$('#btn-upload-recording').on('click', function(e) {
    e.preventDefault();
    $('#btn-upload-recording').hide();

    startUpload();
});

$('#mod_record_rtc_recording_type').on('change', function(e) {
    $('#btn-start-recording').prop('disabled', !$(this).val());
});
