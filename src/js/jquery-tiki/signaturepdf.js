$("a:not(.inline-signaturepdf a)").each(function () {
    // Prevent routing Tiki links under /tiki-signaturepdf.php when rendering a subroute of the Signature PDF app
    $(this).attr("href", function (_, href) {
        if (!href) return;
        href = href.replace("tiki-signaturepdf.php/", "");
        if (!href.startsWith("http") && !href.startsWith("#") && !href.startsWith(window.tikiroot)) {
            return window.tikiroot + href;
        }
        return href;
    });
});
