import { attachChangeEventHandler, observeSelectElementMutations, syncSelectOptions } from "../helpers/select/applySelect";

export default function applySelect() {
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if ($(mutation.target).find("select:not([element-plus-ref])").length) {
                const selects = $(mutation.target).find("select:not([element-plus-ref])");
                selects.each(function () {
                    const elementUniqueId = "el-" + Math.random().toString(36).substring(7);
                    const elementPlusUi = $("<el-select></el-select>");
                    elementPlusUi.attr("placeholder", $(this).attr("placeholder"));
                    elementPlusUi.attr("multiple", $(this).prop("multiple"));
                    elementPlusUi.attr("id", elementUniqueId);
                    elementPlusUi.attr("max", $(this).attr("data-max"));

                    // Attributes set by preferences
                    const selectPreferences = window.elementPlus.select;
                    elementPlusUi.attr("clearable", selectPreferences.clearable);
                    elementPlusUi.attr("collapse-tags", selectPreferences.collapseTags);
                    elementPlusUi.attr("max-collapse-tags", selectPreferences.maxCollapseTags);
                    elementPlusUi.attr("filterable", selectPreferences.filterable);
                    elementPlusUi.attr("allow-create", selectPreferences.allowCreate);
                    elementPlusUi.attr("ordering", selectPreferences.ordering);

                    syncSelectOptions(elementPlusUi.get(0), this);

                    $(this).attr("element-plus-ref", elementUniqueId);
                    $(this).after(elementPlusUi);
                    $(this).hide();

                    attachChangeEventHandler(elementPlusUi.get(0), this);

                    observeSelectElementMutations(this, elementPlusUi.get(0));
                });
            }
        });
    }).observe(document.body, { childList: true, subtree: true });
}
