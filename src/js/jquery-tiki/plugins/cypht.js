$("a:not(.inline-cypht a)").each(function () {
    const href = $(this).attr("href");
    this.href = "#";
    $(this).on("click", () => {
        if ($(this).attr("target") === "_blank") {
            window.open(href, "_blank");
        } else {
            window.location.href = href;
        }
    });
});
