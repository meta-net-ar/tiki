const autocompleteObjectTypes = $("#assign_params\\[autocomplete_objecttypes\\]");
const autocompleteExcludeParentIds = $("#assign_params\\[autocomplete_exclude_parent_ids\\]");

updateAutocompleteExcludeParentIdsOptions();

autocompleteObjectTypes.on("change", function () {
    updateAutocompleteExcludeParentIdsOptions();
});

function updateAutocompleteExcludeParentIdsOptions() {
    const selectedObjectTypes = autocompleteObjectTypes.val();
    if (selectedObjectTypes.length === 0) {
        return;
    }

    autocompleteExcludeParentIds.parent().tikiModal(tr("Loading parent objects..."));
    $.ajax($.service("object", "getSemanticParentObjects"), {
        method: "POST",
        data: { objectTypes: selectedObjectTypes },
        success: function (data) {
            autocompleteExcludeParentIds.empty();
            data.forEach(function (object) {
                autocompleteExcludeParentIds.append(new Option(object.title, object.itemId));
            });
        },
        error: function () {
            showMessage(tr("Failed to fetch parent objects for autocomplete exclude options."), "error");
        },
        complete: function () {
            autocompleteExcludeParentIds.parent().tikiModal();
        },
    });
}
