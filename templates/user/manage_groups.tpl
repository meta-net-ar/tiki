{extends $global_extend_layout|default:'layout_view.tpl'}
{block name="title"}
    {title}{$title}{/title}
{/block}
{block name="content"}
    {include file='access/include_items.tpl'}
    <form method="post" id="confirm-action" class="confirm-action" action="{service controller=$confirmController action=$confirmAction}">
        {include file='access/include_hidden.tpl'}
        <div class="mb-3 row mx-0">
            <label for="add_remove" class="col-form-label">
                {tr}Add to or remove from:{/tr}
            </label>
            <div class="radio col-sm-12">
                <label class="col-form-label me-3">
                    <input type="radio" name="add_remove" id="add" value="add" checked="" class="me-1">
                    {tr}Add to{/tr}
                </label>
                <label class="col-form-label">
                    <input type="radio" name="add_remove" id="remove" value="remove" class="me-1">
                    {tr}Remove from{/tr}
                </label>
            </div>
        </div>
        <div class="mb-3 row mx-0">
            <label for="select_groups" class="col-form-label">
                {tr}These groups:{/tr}
            </label>
            <select name="checked_groups[]" multiple="multiple" size="{$countgrps}" class="form-control" id="select_groups" data-usergroups="{$userGroups|escape}">
                {section name=ix loop=$all_groups}
                    {if $all_groups[ix] != 'Anonymous' && $all_groups[ix] != 'Registered'}
                        <option value="{$all_groups[ix]|escape}">{$all_groups[ix]|escape}</option>
                    {/if}
                {/section}
            </select>
            {if $prefs.elementplus_select !== 'y'}
                <div class="form-text">
                    {tr}Use Ctrl+Click or Command+Click to select multiple options{/tr}
                </div>
            {/if}
            {jq}
$("input[name=add_remove]").on("change", function () {
    const userGroups = $("#select_groups").data("usergroups");
    const mode = $("input[name=add_remove]:checked").val() === "add";
    if ($(this).prop("checked") && userGroups) {
        // filter the group list to ones this user is not in
        $("option", "#select_groups").each(function () {
            if ($.inArray($(this).val(), userGroups) > -1) {
                $(this).prop("disabled", mode).css("opacity", mode ? .3 : 1);
            } else {
                $(this).prop("disabled", ! mode).css("opacity", ! mode ? .3 : 1);
            }
        });
    }
}).trigger("change");

$("#select_groups").on("change", function () {
    const mode = $("input[name=add_remove]:checked").val() === "add";
    const $defaultGroup = $("#default_group");

    const userGroups = $("#select_groups").data("usergroups");
    const selectedGroups = $(this).val();
    let setAndSelectedGroups = [];
    if (mode) {
        setAndSelectedGroups = [...userGroups, ...selectedGroups];
    } else {
        setAndSelectedGroups = [...selectedGroups];
    }

    // add or remove selected groups from default group
    setAndSelectedGroups.forEach(function (group) {
        const pattern = group;
        const $matches = $defaultGroup.find("option").filter(function() {
            // groups can contains single and/or double quotes so use regex
            return $(this).val() === pattern;
        });

        if (mode && $matches.length === 0) {
            $defaultGroup.prepend($("<option>").val(group).text(group));
        } else if (! mode && $matches.length > 0) {
            $matches.remove();
        }
    });

    if (mode) {
        $defaultGroup.find("option").each(function () {
            if (setAndSelectedGroups.indexOf($(this).val()) === -1) {
                $(this).remove();
            }
        });
    }
});
            {/jq}
        </div>
        <div class="mb-3 row mx-0" >
            <label for="default_group" class="col-form-label">
                {tr}Set default group:{/tr}
            </label>
            <select name="default_group" size="{$countgrps}" class="form-control" id="default_group">
                {foreach $all_groups as $group}
                    {if $group != 'Anonymous'}
                        {if in_array($group, json_decode($userGroups, true))}
                            <option value="{$group|escape}">{$group|escape}</option>
                        {/if}
                    {/if}
                {/foreach}
            </select>
        </div>
        {include file='access/include_extra_fields.tpl'}
        {include file='access/include_submit.tpl'}
    </form>
{/block}
