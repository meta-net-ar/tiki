const autocompleteObjectTypes = $("#assign_params\\[autocomplete_objecttypes\\]");
const autocompleteExcludeParentIds = $("#assign_params\\[autocomplete_exclude_parent_ids\\]");

updateAutocompleteExcludeParentIdsOptions();

autocompleteObjectTypes.on("change", function () {
    autocompleteExcludeParentIds.empty();
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
            const values = autocompleteExcludeParentIds.val();
            autocompleteExcludeParentIds.empty();
            data.forEach(function (object) {
                const value = `${object.parent_object_id_field}:${object.object_id}`;
                autocompleteExcludeParentIds.append(new Option(object.title, value, values.includes(value), values.includes(value)));
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
