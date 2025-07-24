$("body").on("click", ".recording-type__item", function () {
    const recordingType = $(this).data("type");
    $(".recording-type__item").removeClass("border-success");
    $(".recording-type__item").addClass("border-white");

    $(this).removeClass("border-white");
    $(this).addClass("border-success");

    $(this).closest(".modal").find(".start-recording").data("type", recordingType);
});
