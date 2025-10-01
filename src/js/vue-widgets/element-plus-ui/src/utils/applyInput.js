import { handleAffixes, handleFileInput } from "../helpers/input/applyInput";

export default function applyInput() {
    transformContainerInputs(document.body);
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => transformContainerInputs(mutation.target));
    }).observe(document.body, { childList: true, subtree: true });
}

/**
 * Replaces native inputs with Element Plus components, ensuring state and event synchronization.
 * @param {HTMLElement} containerElement - The element to scan for inputs.
 */
function transformContainerInputs(containerElement) {
    // Filter for viable, untransformed inputs, excluding known compatibility conflicts.
    const inputs = $(containerElement)
        .find("input")
        .filter(function () {
            if ($(this).attr("element-plus-ref") || $(this).css("display") === "none" || $(this).closest(".cypht-layout, .tiki-webmail").length) {
                return false;
            }
            return [undefined, "text", "number", "email", "password", "search", "url", "tel", "file"].includes($(this).attr("type"));
        });

    inputs.each(function () {
        if ($(this).attr("type") === "file") {
            return handleFileInput(this);
        }

        const originalInput = $(this);
        const elementUniqueId = "el-" + Math.random().toString(36).substring(7);
        const elementPlusUi = $("<el-input></el-input>");

        // Mirror essential properties from the original input to the Vue component.
        elementPlusUi.attr({
            id: elementUniqueId,
            placeholder: originalInput.attr("placeholder"),
            value: originalInput.val(),
            type: originalInput.attr("type"),
            autocomplete: originalInput.attr("autocomplete"),
            name: originalInput.attr("name"),
            style: originalInput.attr("style"),
            role: originalInput.attr("role"),
            "show-password": originalInput.is('[type="password"]'),
            disabled: originalInput.prop("disabled"),
            autofocus: originalInput.prop("autofocus"),
        });

        handleAffixes(this, elementPlusUi);

        originalInput.attr("element-plus-ref", elementUniqueId).hide();
        originalInput.after(elementPlusUi);

        // --- Event Synchronization ---

        elementPlusUi.on("change", (e) => originalInput.val(e.detail[0]).trigger("change"));
        elementPlusUi.on("input", (e) => originalInput.val(e.detail?.[0]).trigger("input"));

        ["blur", "focus", "keyup", "keydown"].forEach((event) => {
            elementPlusUi.on(event, () => {
                // CRITICAL: Sync value *before* dispatching the event to prevent race conditions.
                originalInput.val(elementPlusUi.val()).trigger(event);
            });
        });

        elementPlusUi.on("enter", () => {
            const $form = originalInput.closest("form");
            if ($form.length) {
                originalInput.val(elementPlusUi.val());
                $form.trigger("submit");
            }
        });

        const $form = originalInput.closest("form");
        if ($form.length && !$form.data("ep-sync-added")) {
            // Failsafe: Ensure data integrity on form submission for all transformed inputs.
            $form
                .on("submit.ep-sync", () => {
                    $form.find("input[element-plus-ref]").each(function () {
                        const $input = $(this);
                        if ($input.is('[type="file"]')) {
                            return;
                        }
                        const $el = $("#" + $input.attr("element-plus-ref"));
                        if ($el.length && !$el.is("el-autocomplete")) $input.val($el.val());
                    });
                })
                .data("ep-sync-added", true);
        }
    });
}
