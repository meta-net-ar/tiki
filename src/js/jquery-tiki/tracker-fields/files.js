import moment from "moment";

$(".play-audio").on("click", async function () {
    $(this).addClass("loading");
    $(this).prop("disabled", true);

    try {
        const audio = await getPlayableAudio.call(this);

        $(this).removeClass("loading");
        $(this).prop("disabled", false);

        if (!audio) {
            showMessage(tr("Failed to load audio file."), "error");
            return;
        }

        await audio.play();

        $(this).addClass("d-none");
        $(this).siblings(".pause-audio").removeClass("d-none").data("audio", audio);
        $(this).siblings(".stop-audio").removeClass("d-none").data("audio", audio);

        const audioTimer = $(this).siblings(".audio-timer");
        audioTimer.addClass("show");
        audioTimer.find(".time").removeClass("d-none");
        $(this).siblings(".label").hide();

        audio.addEventListener("timeupdate", () => {
            const currentTime = Math.floor(audio.currentTime);
            const duration = Math.floor(audio.duration);
            const remainder = duration - currentTime;

            const now = moment().minute(0).second(0).unix();
            const endTime = now + remainder;

            const minutes = Math.floor(remainder / 60);
            let timeLabel = "";
            if (minutes > 60) {
                timeLabel = moment.unix(endTime).format("HH:mm:ss");
            } else {
                timeLabel = moment.unix(endTime).format("mm:ss");
            }

            audioTimer.find(".time").text(timeLabel);

            const percentage = (currentTime / duration) * 100;
            audioTimer.find(".progress-bar").css("width", `${percentage}%`);
        });
        audio.addEventListener("ended", () => {
            audioTimer.removeClass("show");
            $(this).siblings(".label").show();
            audioTimer.find(".time").addClass("d-none");
            audioTimer.find(".progress-bar").css("width", "0%");

            $(this).removeClass("d-none");
            $(this).siblings(".pause-audio").addClass("d-none");
            $(this).siblings(".stop-audio").addClass("d-none");
        });
    } catch (event) {
        const errorCode = event.target?.error?.code;
        if ([MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED, MediaError.MEDIA_ERR_DECODE].includes(errorCode)) {
            showMessage(tr("Failed to play audio, the format is unsupported."), "error");
        } else {
            showMessage(tr("Failed to play audio: ") + event.target?.error?.message, "error");
        }
        $(this).removeClass("loading");
        $(this).prop("disabled", false);
    }
});

$(".pause-audio").on("click", function () {
    const audio = $(this).data("audio");
    if (audio) {
        audio.pause();
        $(this).addClass("d-none");
        $(this).siblings(".play-audio").removeClass("d-none").data("audio", audio);
    }
});

$(".stop-audio").on("click", function () {
    const audio = $(this).data("audio");
    if (audio) {
        audio.pause();
        audio.currentTime = 0;

        audio.dispatchEvent(new Event("ended"));
    }
});

function getPlayableAudio() {
    return new Promise(async (resolve, reject) => {
        const audio = $(this).data("audio");
        if (audio) {
            resolve(audio);
        } else {
            const url = $(this).data("src");
            fetch(url)
                .then(async (response) => {
                    if (!response.ok) {
                        resolve(null);
                    }
                    const blob = await response.blob();
                    const audio = new Audio(URL.createObjectURL(blob));
                    loadMetadata(audio)
                        .then(() => resolve(audio))
                        .catch(reject);
                })
                .catch(() => resolve(null));
        }
    });
}

function loadMetadata(audioElement) {
    return new Promise((resolve, reject) => {
        audioElement.addEventListener("loadedmetadata", () => {
            if (audioElement.duration === Infinity) {
                audioElement.currentTime = 1e101; // Set a large value to force metadata loading

                audioElement.ontimeupdate = () => {
                    audioElement.ontimeupdate = null;
                    audioElement.currentTime = 0;
                    resolve();
                };
            } else {
                resolve();
            }
        });
        audioElement.addEventListener("error", (e) => {
            reject(e);
        });
    });
}
