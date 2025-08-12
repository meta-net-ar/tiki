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

$("#input_pdf_upload").after(`<button class="btn btn-outline-secondary" id="btn_pdf_upload_fgal">Choose from File Galleries</button>`);

export function handlePickFromFileGalleries(options) {
    $("#btn_pdf_upload_fgal").on("click", function (e) {
        e.preventDefault();
        if (window.openElFinderDialog) {
            openElFinderDialog(null, {
                ...options,
                onlyMimes: ["application/pdf"],
                getFileCallback: async (fileRes) => {
                    const fileUrl = fileRes[0].url;
                    const file = await fetch(fileUrl);
                    const blob = await file.blob();
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(new File([blob], fileRes[0].name, { type: fileRes[0].mime }));
                    const input = $("#input_pdf_upload")[0];
                    input.files = dataTransfer.files;
                    input.dispatchEvent(new Event("change", { bubbles: true }));
                    $.closeModal();
                },
            });
        } else {
        }
    });
}
