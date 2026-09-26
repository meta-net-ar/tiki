const autocompleteObjectTypes = $("#assign_params\\[autocomplete_objecttypes\\]");
const autocompleteExcludeParentIds = $("#assign_params\\[autocomplete_exclude_parent_ids\\]");
const autocompleteOnlyParentIds = $("#assign_params\\[autocomplete_only_parent_ids\\]");

updateAutocompleteFilterParentIdsOptions();

autocompleteObjectTypes.on("change", function () {
    if (!$(this).val().length) {
        autocompleteExcludeParentIds.empty();
        autocompleteOnlyParentIds.empty();
        return;
    }

    updateAutocompleteFilterParentIdsOptions();
});

function updateAutocompleteFilterParentIdsOptions() {
    const selectedObjectTypes = autocompleteObjectTypes.val();
    if (selectedObjectTypes.length === 0) {
        return;
    }

    autocompleteExcludeParentIds.parent().tikiModal(tr("Loading parent objects..."));
    autocompleteOnlyParentIds.parent().tikiModal(tr("Loading parent objects..."));

    $.ajax($.service("object", "getSemanticParentObjects"), {
        method: "POST",
        data: { objectTypes: selectedObjectTypes },
        success: function (data) {
            const excludeValues = autocompleteExcludeParentIds.val();
            const onlyValues = autocompleteOnlyParentIds.val();

            autocompleteExcludeParentIds.empty();
            autocompleteOnlyParentIds.empty();

            data.forEach(function (object) {
                const value = `${object.parent_object_id_field}:${object.object_id}`;
                autocompleteExcludeParentIds.append(new Option(object.title, value, excludeValues.includes(value), excludeValues.includes(value)));
                autocompleteOnlyParentIds.append(new Option(object.title, value, onlyValues.includes(value), onlyValues.includes(value)));
            });
        },
        error: function () {
            showMessage(tr("Failed to fetch parent objects for autocomplete options."), "error");
        },
        complete: function () {
            autocompleteExcludeParentIds.parent().tikiModal();
            autocompleteOnlyParentIds.parent().tikiModal();
        },
    });
}
