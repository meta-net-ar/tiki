import { attachChangeEventHandler, observeSelectElementMutations, syncSelectOptions } from "../helpers/select/applySelect";

export default function applySelect() {
    transformContainerSelects(document.body);
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            transformContainerSelects(mutation.target);
        });
    }).observe(document.body, { childList: true, subtree: true });
}

function transformContainerSelects(containerElement) {
    const selects = $(containerElement).find("select:not([element-plus-ref])");
    if (selects.length) {
        selects.each(function () {
            const elementUniqueId = "el-" + Math.random().toString(36).substring(7);
            const elementPlusUi = $("<el-select></el-select>");
            elementPlusUi.attr("placeholder", $(this).attr("placeholder"));
            if ($(this).prop("multiple")) {
                elementPlusUi.attr("multiple", "multiple");
            } else {
                elementPlusUi.removeAttr("multiple");
            }
            elementPlusUi.attr("id", elementUniqueId);
            elementPlusUi.attr("max", $(this).attr("data-max"));
            if (this.hasAttribute("disabled")) {
                elementPlusUi.css("pointer-events", "none");
            }
            // In respect to bootstrap form-control sizes
            if (this.classList.contains("form-control-sm")) {
                elementPlusUi.attr("size", "small");
            }

            // Attributes set by preferences
            const selectPreferences = window.elementPlus.select;
            elementPlusUi.attr("clearable", String(selectPreferences.clearable));
            elementPlusUi.attr("collapse-tags", String(selectPreferences.collapseTags));
            elementPlusUi.attr("max-collapse-tags", selectPreferences.maxCollapseTags);
            elementPlusUi.attr("filterable", String(selectPreferences.filterable));
            // A field can opt into free-text entry via data-allow-create, overriding
            // the global preference for that select only.
            const allowCreate = $(this).data("allow-create") ?? selectPreferences.allowCreate;
            elementPlusUi.attr("allow-create", String(allowCreate));
            // This web component expects JSON text. Passing the boolean false
            // to jQuery.attr triggers a jQuery Migrate warning and removes the
            // attribute instead of conveying the configured value.
            elementPlusUi.attr("ordering", String(selectPreferences.ordering));

            if ($(this).data("remote-source-url")) {
                $(elementPlusUi).attr("remote-source-url", $(this).data("remote-source-url"));
            }

            syncSelectOptions(elementPlusUi.get(0), this);

            $(this).attr("element-plus-ref", elementUniqueId);
            $(this).after(elementPlusUi);
            $(this).hide();

            attachChangeEventHandler(elementPlusUi.get(0), this);

            observeSelectElementMutations(this, elementPlusUi.get(0));
        });
    }
}
