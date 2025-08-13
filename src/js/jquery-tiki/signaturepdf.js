import fileToBase64 from "../helpers/fileToBase64";

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

export function handleSaveToFileGalleries() {
    const downloadButton = $("form#form_pdf button#save")
        .wrap(`<div class="btn-group mt-2" role="group"></div>`)
        .after(`<button class="btn btn-outline-primary" id="btn_pdf_upload_fgal" disabled="disabled">${tr("Save to file galleries")}</button>`);
    downloadButton.removeClass("mt-2");

    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            const isDisabled = $(mutation.target).is(":disabled");
            $(mutation.target).next("button#btn_pdf_upload_fgal").prop("disabled", isDisabled);
        });
    }).observe(downloadButton[0], {
        attributes: true,
        attributeFilter: ["disabled"],
    });

    downloadButton.next("button#btn_pdf_upload_fgal").on("click", function (e) {
        e.preventDefault();
        const downloadFnOriginal = download;
        download = async function (blob, filename) {
            const base64 = await fileToBase64(blob).catch(() => {
                showMessage(tr("Error converting file to Base64"), "error");
                return null;
            });
            const formData = new FormData();
            formData.append("data", base64.replace(/^data:.*?;base64,/, ""));
            formData.append("name", decodeURIComponent(filename));
            formData.append("type", blob.type);
            formData.append("size", blob.size);
            formData.append("ticket", $("input[name=ticket]").val());
            const res = await fetch($.service("file", "upload"), {
                method: "POST",
                body: formData,
            });

            if (!res.ok) {
                showMessage(tr("Error saving file to File Galleries"), "error");
            } else {
                showMessage(tr("File saved to File Galleries"), "success");
            }

            download = downloadFnOriginal;
        };
        downloadButton[0].dispatchEvent(new Event("click", { bubbles: true }));
    });
}

export function handlePickFromFileGalleries(options) {
    $("#input_pdf_upload").after(`<button class="btn btn-outline-secondary" id="btn_pdf_upload_fgal">${tr("Choose from File Galleries")}</button>`);
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
            $.clickModal(
                {
                    size: "modal-lg",
                    success: async function ({ files }) {
                        const file = files[0];
                        const fileRes = await fetch(window.tikiroot + "tiki-download_file.php?fileId=" + file.fileId);
                        const blob = await fileRes.blob();
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(new File([blob], file.name, { type: file.mime }));
                        const input = $("#input_pdf_upload")[0];
                        input.files = dataTransfer.files;
                        input.dispatchEvent(new Event("change", { bubbles: true }));
                        $.closeModal();
                        history.pushState({}, "", window.location.pathname + `?fileId=${file.fileId}`);
                    },
                },
                $.service("file", "browse", {
                    galleryId: options.defaultGalleryId,
                    type: "pdf",
                    limit: 1,
                })
            ).call(this, e);
        }
    });
}
