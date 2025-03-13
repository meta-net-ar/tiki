export function observeSelectElementMutations(select, elementPlusUi) {
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.addedNodes.length) {
                syncSelectOptions(elementPlusUi, select);
            }

            // jquery-validation error highlighting
            if (mutation.attributeName === "class") {
                if (mutation.target.classList.contains("is-invalid")) {
                    $(elementPlusUi).attr("is-invalid", true);
                } else {
                    $(elementPlusUi).removeAttr("is-invalid");
                }
            }

            // Allow to limit the maximum number of selectable items
            if (mutation.attributeName === "data-max") {
                $(elementPlusUi).attr("max", mutation.target.getAttribute("data-max"));
            }
        });
    }).observe(select, { childList: true, attributes: true });
}

export function syncSelectOptions(elementPlusSelect, select) {
    const options = $(select)
        .find("option")
        .map(function () {
            return {
                value: $(this).val(),
                label: $(this).text(),
                disabled: $(this).prop("disabled"),
                group: $(this).parent("optgroup").attr("label"),
            };
        })
        .get();
    $(elementPlusSelect).attr("options", JSON.stringify(options));
    $(elementPlusSelect).attr("value", JSON.stringify($(select).val()));
    if (options.find((option) => option.group)) {
        $(elementPlusSelect).attr("group", true);
    }
}

export function attachChangeEventHandler(elementPlusSelect, select) {
    $(elementPlusSelect).on("select-change", function (event) {
        const selectedValues = event.detail[0].value;

        // Adding new items to the select list
        if (Array.isArray(selectedValues)) {
            selectedValues.forEach((selectedValue) => {
                if (!$(select).find(`option[value="${selectedValue}"]`).length) {
                    const option = $("<option></option>").val(selectedValue).text(selectedValue);
                    $(select).append(option);
                }
            });
        }
        $(select).val(selectedValues);
        $(select).trigger("change");
    });
}
