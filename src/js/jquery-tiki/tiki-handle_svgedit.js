export async function handleDraw(fileId, galleryId, name, imgParams) {
    const loadingIndicator = $($.IMPORT_LOADER_MARKUP);
    $("#tiki_draw").append(loadingIndicator);
    try {
        await import("@jquery-tiki/tiki-svgedit_draw");
    } catch (error) {
        const errorElement = $(`
            <div class="alert alert-danger" role="alert">
                An error occurred while loading the SVG editor.
            </div>
        `);
        $("#tiki_draw").append(errorElement);
        return;
    } finally {
        loadingIndicator.remove();
    }
    $("#tiki_draw")
        .loadDraw({
            fileId: fileId,
            galleryId: galleryId,
            name: name,
            imgParams: imgParams,
            data: $("#tiki_draw #fileData").val(),
        })
        .on("renamedDraw", function (e, name) {
            $("#fileName").val(name);
            $(".pagetitle").text(name);
        })
        .on("submit", function (e) {
            e.preventDefault();
            $(this).saveDraw();
        })
        .on("savedDraw", function (e, o) {
            const draw = $(this);
            draw.data("drawLoaded", false);

            let img = $(".pluginImg" + draw.data("fileid")).show();

            if (!img.length) return;

            const w = img.width();
            const h = img.height();

            if (img.hasClass("regImage")) {
                const replacement = $("<div />")
                    .attr("class", img.attr("class"))
                    .attr("style", img.attr("style"))
                    .attr("id", img.attr("id"))
                    .insertAfter(img);

                img.remove();
                img = replacement;
            }

            const src = draw.data("src");

            $('<div class="svgImage" />').load(src ? src : "tiki-download_file.php?fileId=" + o.fileId + "&display", function () {
                $(this).css("position", "absolute").fadeTo(0, 0.01).prependTo("body").find("img,svg").scaleImg({
                    width: w,
                    height: h,
                });

                img.html($(this).children());

                $(this).remove();
            });

            draw.data("fileid", o.fileId); // replace fileId on edit button
            if (o.imgParams && o.imgParams.fileId) {
                o.imgParams.fileId = o.fileId;
                draw.data("imgparams", o.imgParams);
            }
        });

    $("#drawBack").on("click", function () {
        window.history.back();
    });
}
