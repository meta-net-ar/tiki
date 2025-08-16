export function handRecordingTypeInputsState() {
    $(".recording-type input").on("change", function () {
        if ($(".recording-type #recordCamera").is(":checked")) {
            $(".recording-type #recordScreen").prop("disabled", true);
        } else {
            $(".recording-type #recordScreen").prop("disabled", false);
        }

        if ($(".recording-type #recordScreen").is(":checked")) {
            $(".recording-type #recordCamera").prop("disabled", true);
        } else {
            $(".recording-type #recordCamera").prop("disabled", false);
        }

        const recordingTypes = [];
        $(".recording-type input:checked").each(function () {
            recordingTypes.push($(this).attr("name"));
        });

        const recordingType = recordingTypes.join(",");
        setCookie("recordingType", recordingType, "", "session", window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL);

        $(this).closest(".modal").find(".start-recording").data("type", recordingType);
    });

    const recordingType = getCookie("recordingType");
    $(".recording-type input").each(function () {
        if (recordingType && recordingType.includes($(this).attr("name"))) {
            $(this).prop("checked", true);
        } else {
            $(this).prop("checked", false);
        }
        $(this).trigger("change");
    });
}
