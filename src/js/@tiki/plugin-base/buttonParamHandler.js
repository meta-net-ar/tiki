function submitNewButton(field, element) {
    const fieldContainer = $(`#${field}`);
    const buttonContainer = $(".btn-container .buttons", fieldContainer);
    const label = $("input[name='label']", fieldContainer).val();
    const classNames = $("input[name='classnames']", fieldContainer).val();
    const action = $("input[name='actions']", fieldContainer).val();

    const insertButton = createButton(label, classNames, action);
    const updateFields = insertButton(buttonContainer);
    updateFields(fieldContainer);

    const collapse = $(element).closest(".collapse").get(0);
    bootstrap.Collapse.getInstance(collapse).hide();

    $(".field", fieldContainer).val("");
}

function createButton(label, classNames, action) {
    const button = $("<div></div>").text(label).addClass(classNames);
    return (container) => {
        const buttonWrapper = $("<div></div>").addClass("flex-shrink-0 flex-grow-0 d-flex gap-2 p-2 border rounded shadow-sm btn-wrapper");
        buttonWrapper.append(button);
        buttonWrapper.append($(".delete-icon").first().clone().removeClass("d-none"));
        $(".delete-icon", buttonWrapper).click(function () {
            deleteButton($(this));
        });
        container.append(buttonWrapper);

        return (fieldContainer) => {
            const labelInput = $("input#label", fieldContainer);
            const classNamesInput = $("input#classNames", fieldContainer);
            const actionInput = $("input#action", fieldContainer);
            if (!label) return;
            const labelValue = labelInput.val();
            const newLabelValue = labelValue ? `${labelValue},${label}` : label;
            labelInput.val(newLabelValue);

            const insertAtIndex = newLabelValue.split(",").length - 1;

            const classNamesValue = classNamesInput.val().split(",");
            while (classNamesValue.length < insertAtIndex) {
                classNamesValue.push("");
            }
            classNamesValue[insertAtIndex] = classNames;
            classNamesInput.val(classNamesValue.join(","));

            const actionValue = actionInput.val().split(",");
            while (actionValue.length < insertAtIndex) {
                actionValue.push("");
            }
            actionValue[insertAtIndex] = action;
            actionInput.val(actionValue.join(","));
        };
    };
}

function deleteButton(deleteElement) {
    // find the delete button index
    const deleteIndex = $(".btn-wrapper .delete-icon").index(deleteElement);

    $("input#label, input#classNames, input#action").each(function () {
        const values = $(this).val().split(",");
        values.splice(deleteIndex, 1);
        $(this).val(values.join(","));
    });

    deleteElement.closest(".btn-wrapper").remove();
}

function generateButtonsFromInput(field) {
    const label = $("input#label").val().split(",");
    const classNames = $("input#classNames").val().split(",");
    const action = $("input#action").val().split(",");

    const buttonContainer = $(`#${field}`).find(".btn-container .buttons");
    label.forEach((label, i) => {
        if (!label) {
            return;
        }
        const insertButton = createButton(label, classNames[i], action[i]);
        insertButton(buttonContainer);
    });
}

export { submitNewButton, generateButtonsFromInput };
