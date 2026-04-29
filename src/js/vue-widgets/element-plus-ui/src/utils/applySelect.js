import { attachChangeEventHandler, observeSelectElementMutations, syncSelectOptions } from "../helpers/select/applySelect";

const OPT_IN_SELECTOR = "select.tiki-elementplus-select:not([element-plus-ref]), select[data-elementplus-select='true']:not([element-plus-ref])";

export default function applySelect() {
    transformContainerSelects(document.body);
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    transformContainerSelects(node);
                }
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
}

export function transformContainerSelects(containerElement) {
    const container = $(containerElement);
    const selects = container.find(OPT_IN_SELECTOR).add(container.filter(OPT_IN_SELECTOR));

    if (selects.length) {
        selects.each(function () {
            const elementUniqueId = "el-" + Math.random().toString(36).substring(7);
            const elementPlusUi = $("<el-select></el-select>");
            elementPlusUi.attr("placeholder", $(this).attr("placeholder"));
            elementPlusUi.attr("multiple", $(this).prop("multiple"));
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
            elementPlusUi.attr("clearable", selectPreferences.clearable);
            elementPlusUi.attr("collapse-tags", selectPreferences.collapseTags);
            elementPlusUi.attr("max-collapse-tags", selectPreferences.maxCollapseTags);
            elementPlusUi.attr("filterable", selectPreferences.filterable);
            elementPlusUi.attr("allow-create", selectPreferences.allowCreate);
            elementPlusUi.attr("ordering", selectPreferences.ordering);

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
