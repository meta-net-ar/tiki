export function handleFileInput(input) {
    const elementUniqueId = "el-" + Math.random().toString(36).substring(7);
    const elementPlusUi = $("<el-file-input></el-file-input>");

    elementPlusUi.attr("id", elementUniqueId);

    if ($(input).prop("multiple")) {
        elementPlusUi.attr("multiple", true);
    }

    if ($(input).attr("accept")) {
        elementPlusUi.attr("accept", $(input).attr("accept"));
    }

    $(input).attr("element-plus-ref", elementUniqueId);
    $(input).after(elementPlusUi);
    $(input).hide();

    elementPlusUi.on("change", (event) => {
        const value = event.detail[0];
        const dataTransfer = new DataTransfer();

        if (Array.isArray(value)) {
            value.forEach((file) => {
                dataTransfer.items.add(file.raw);
            });
        } else {
            dataTransfer.items.add(value.raw);
        }

        input.files = dataTransfer.files;

        $(input).trigger("change");
    });
}

export function handleAffixes(input, elementPlusUi) {
    const nextSibling = input.nextElementSibling;
    if ($(nextSibling).hasClass("input-group-text")) {
        elementPlusUi.attr("append-text", nextSibling.innerText);
        nextSibling.remove();
    }

    const prevSibling = input.previousElementSibling;
    if ($(prevSibling).hasClass("input-group-text")) {
        elementPlusUi.attr("prepend-text", prevSibling.innerText);
        prevSibling.remove();
    }
}
