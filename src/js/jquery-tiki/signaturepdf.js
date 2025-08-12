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

$("#input_pdf_upload").after(`<button class="btn btn-outline-secondary" id="btn_pdf_upload_fgal">${tr("Choose from File Galleries")}</button>`);
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

// TODO: This should be outsourced to a general helper file from src/js/vue-widgets/element-plus-ui/src/helpers/fileGalUploader/fileToBase64.js
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = (error) => reject(error);
    });
}

downloadButton.next("button#btn_pdf_upload_fgal").on("click", function (e) {
    e.preventDefault();
    const downloadFnOriginal = download;
    download = async function (blob, filename) {
        const base64 = await fileToBase64(blob);
        // TODO: make this work
        const fileId = new URLSearchParams(window.location.search).get("fileId");
        const res = await fetch($.service("file", "upload"), {
            method: "POST",
            body: JSON.stringify({
                data: base64,
                update: true,
                filename: filename,
                type: blob.type,
                fileId: fileId,
            }),
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
                    // TODO: Fix this to properly append the fileId to the URL after the upstream script has update the history
                    history.pushState({}, "", window.location.pathname + `?fileId=${fileRes[0].fileId}`);
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
                        // TODO: Fix this to properly append the fileId to the URL after the upstream script has update the history
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
