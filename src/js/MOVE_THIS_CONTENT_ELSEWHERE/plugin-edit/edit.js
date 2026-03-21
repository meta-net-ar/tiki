function registerFieldDependency(fieldId, dependencyName, dependencyValue) {
    const field = $(`#${fieldId}`);
    const fieldContainer = field.closest(".field-container");
    const dependency = $(`[name='params[${dependencyName}]']`);

    const hideField = function () {
        field.val("");
        fieldContainer.hide();
        const isRequired = field.rules()?.required;
        if (isRequired) {
            field.rules("remove", "required");
            field.attr("data-required", "true");
        }
    };

    if (dependency.val() === dependencyValue) {
        fieldContainer.show();
    } else {
        hideField();
    }

    dependency.on("change", function () {
        if ($(this).val() === dependencyValue) {
            fieldContainer.show();
            if (field.attr("data-required")) {
                field.rules("add", "required");
                field.removeAttr("data-required");
            }
        } else {
            hideField();
        }
    });
}

export { registerFieldDependency };
