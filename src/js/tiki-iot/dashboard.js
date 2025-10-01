import GridStack from "gridstack/dist/gridstack-all.js";
import { Gauge, Donut } from "gaugeJS/dist/gauge.min.js";

$(document).ready(function () {
    var gauge_instances = {};
    let app_id = $("[data-grid-stack]").data("app-id-plain");
    let token = $("[data-grid-stack]").data("session-token");
    var socket = tikiOpenWS(`iot-dashboard-notifier?app_id=${app_id}&token=${token}`);
    socket.onopen = function (e) {
        $(".realtime-status").addClass("text-bg-success").removeClass("text-bg-warning").text("connected");
    };
    socket.onmessage = function (e) {
        try {
            const data = JSON.parse(e.data);
            const payload = data.message;
            for (let fieldName in payload) {
                if (fieldName == "state_object") {
                    const state_object = JSON.parse(payload["state_object"]);
                    for (let io in state_object) {
                        let value = state_object[io];
                        $(`div[data-widget-data-source="hardware-io-${io}"]`).html(
                            `<div class='io-state' data-io-state='${value["state"]}'></div>${value["hardware_sync_done"] ? "" : '<div class="pending-dot" title="Waiting hardware to sync this state"></div>'}`
                        ); //pending sync means the hardware have not yet read the value
                        $(`input[data-widget-data-source="hardware-io-${io}"]`).prop("checked", value["state"] == "ON");
                    }
                } else if (fieldName !== "app_flow_logs") {
                    $(`[data-widget-data-source='${fieldName}']`).not("canvas").text(payload[fieldName]); //replace text but not on gauge elements
                    $(`canvas[data-widget-data-source='${fieldName}']`).each(function () {
                        let currentId = $(this).attr("id");
                        gauge_instances[currentId].set(Number(payload[fieldName]));
                    });
                } else {
                    if (!Array.isArray(payload[fieldName])) {
                        $(`[data-widget-data-source='${fieldName}']`).append(`${payload[fieldName]}`);
                    } else {
                        const values = payload[fieldName];
                        if ($(`[data-widget-data-source='${fieldName}'] ul`).length == 0) {
                            $(`[data-widget-data-source='${fieldName}']`).html("<ul></ul>");
                        }
                        (function (fieldName, payload) {
                            const values = payload[fieldName];
                            const container = $(`[data-widget-data-source='${fieldName}']`);

                            if (container.find("ul").length == 0) {
                                container.html("<ul></ul>");
                            }
                            const getCurrentDateTime = () => new Date().toLocaleString();
                            container.find("ul").append(`<li class="log-entry timestamp">${getCurrentDateTime()}</li>`);
                            values.forEach((value, index) => {
                                setTimeout(() => {
                                    container.find("ul").append(`<li class="log-entry">${value}</li>`);
                                    if (index === values.length - 1) {
                                        setTimeout(() => {
                                            container.find("ul").append('<hr class="log-separator">');
                                        }, 100);
                                    }
                                }, 100 * index);
                            });
                        })(fieldName, payload);
                    }
                }
            }
        } catch (error) {
            $.fn.toastNotification({
                title: tr("Error"),
                body: error.message,
                position: "bottom-end",
                classes: "bg-danger text-white",
            });
        }
    };
    socket.onclose = function (event) {
        $.fn.toastNotification({
            title: tr("Error"),
            body: tr("WebSocket is closed now."),
            position: "bottom-end",
            classes: "bg-danger text-white",
        });
        $(".realtime-status").removeClass("text-bg-success").addClass("text-bg-warning").text("disconnected");
    };
    socket.onerror = function (error) {
        $.fn.toastNotification({
            title: tr("Error"),
            body: tr("WebSocket error observed: ") + error,
            position: "bottom-end",
            classes: "bg-danger text-white",
        });
        $(".realtime-status").removeClass("text-bg-success").addClass("text-bg-warning").text("error");
    };
    //initialize gauge widgets

    $(".initialized[data-widget-type='gauge-half']").each(function (index) {
        const target = $(this);
        let opts = {
            angle: 0,
            lineWidth: 0.28,
            radiusScale: 1,
            pointer: {
                length: 0.35,
                strokeWidth: 0.035,
                color: $(target).data("pointer-color"),
            },
            limitMax: 100,
            limitMin: 0,
            colorStart: $(target).data("gauge-bg-color"),
            colorStop: $(target).data("gauge-bg-color"),
            strokeColor: $(target).data("pointer-color"),
            generateGradient: true,
            highDpiSupport: true,
        };
        target.attr("id", `gauge-half-canvas-${index}`);
        gauge_instances[`gauge-half-canvas-${index}`] = new Gauge(target.get(0)).setOptions(opts);
        gauge_instances[`gauge-half-canvas-${index}`].set(0);
    });

    $(".initialized[data-widget-type='gauge']").each(function (index) {
        const target = $(this);
        let opts = {
            angle: 0.5,
            lineWidth: 0.09,
            radiusScale: 1,
            pointer: {
                length: 0.6,
                strokeWidth: 0.035,
                color: $(target).data("pointer-color"),
            },
            limitMax: 100,
            limitMin: 0,
            colorStart: $(target).data("gauge-bg-color"),
            colorStop: $(target).data("gauge-bg-color"),
            strokeColor: $(target).data("pointer-color"),
            generateGradient: true,
            highDpiSupport: true,
        };
        target.attr("id", `gauge-canvas-${index}`);
        gauge_instances[`gauge-half-canvas-${index}`] = new Donut(target.get(0)).setOptions(opts);
        gauge_instances[`gauge-half-canvas-${index}`].set(0);
    });
    $("input[data-widget-data-source]").on("change", function () {
        const state = $(this).prop("checked");
        const inputEl = $(this);
        const source_string = inputEl.data("widget-data-source").split("-");
        const pin = source_string[2];
        inputEl.tikiModal(" ");
        const formData = {
            pin,
            state: state ? "ON" : "OFF",
            app_name: $("[data-grid-stack]").data("app-name-plain"),
            app_uuid: $("[data-grid-stack]").data("app-id-plain"),
        };

        try {
            $.post("tiki-ajax_services.php", {
                controller: "iotapps",
                action: "change_io_state",
                payload: formData,
            })
                .done(function () {
                    $(`div[data-widget-data-source="${inputEl.data("widget-data-source")}"]`).html(
                        `<div class='io-state' data-io-state='${state ? "ON" : "OFF"}'></div><div class="pending-dot"  title="${tr("waiting hardware to sync this state")}"></div>`
                    ); //pending sync means the hardware have not yet read the value
                })
                .fail(function () {
                    $.fn.toastNotification({
                        title: tr("Error"),
                        body: tr("Failed to update IO status, please try again!"),
                        position: "bottom-end",
                        classes: "bg-danger text-white",
                    });
                })
                .always(function () {
                    inputEl.tikiModal("");
                });
        } catch (error) {
            $.fn.toastNotification({
                title: tr("Error"),
                body: error.message,
                position: "bottom-end",
                classes: "bg-danger text-white",
            });
            inputEl.tikiModal("");
        }
    });
});

const $container = $("#iot_dashboard");
const $fullscreenButton = $("#fullscreen-button");

const fullscreenMethods = [
    "requestFullscreen",
    "mozRequestFullScreen", // Firefox
    "webkitRequestFullscreen", // Chrome, Safari, Opera
    "msRequestFullscreen", // IE/Edge
];

$fullscreenButton.on("click", function () {
    const element = $container.get(0);
    for (const method of fullscreenMethods) {
        if (element[method]) {
            element[method]();
            break;
        }
    }
});

// Event Listeners for Fullscreen Change
$(document).on("fullscreenchange mozfullscreenchange webkitfullscreenchange msfullscreenchange", handleFullscreenChange);

function handleFullscreenChange() {
    if (document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
        $fullscreenButton.hide();
        $container.css("height", "100svh");
    } else {
        $fullscreenButton.show();
        $container.css("height", "unset");
    }
}

$(function () {
    $("span[data-icon-name]").each(function () {
        $(this).setIcon($(this).data("icon-name"));
    });
});

var grids = {};
let options = {
    column: 6,
    minRow: 6, // don't collapse when empty
    cellHeight: 80,
    float: true,
    disableResize: $("[data-app-id-plain]").length > 0 ? true : false, //[data-app-id-plain] exist only in the final view dashboard template
    disableDrag: $("[data-app-id-plain]").length > 0 ? true : false,
};

$("[data-grid-stack]").each(function () {
    grids[$(this).attr("id")] = GridStack.init(options, $(this).get(0));
});
