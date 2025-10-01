<div class="row">
    <div class="col-sm">
        {tr}Filter by group:{/tr}
        <select id="user_group_selector_{$field.fieldId}" multiple="multiple" class="form-select">
            {section name=ix loop=$data.groups}
                <option value="{$data.groups[ix]|escape}" {if (in_array($data.groups[ix], $data.selected_groups))}selected{/if}>{$data.groups[ix]}</option>
            {/section}
        </select>
    </div>
    {if $field.options_map.inputtype === 't'}
        <div class="mt-2" />
        {jstransfer_list data=[] defaultSelected=$data.selected_users fieldName="{$field.html_name}" filterable=$field.options_map.filterable filterPlaceholder=$field.options_map.filterPlaceholder sourceListTitle=$field.options_map.sourceListTitle targetListTitle=$field.options_map.targetListTitle ordering=$field.options_map.ordering cardinalityParam=$field.validationParam validationMessage=$field.validationMessage}
    {else}
        <div class="col-sm-6">
            {tr}Select user(s):{/tr}
            <select name="{$field.html_name}" id="user_selector_{$field.fieldId}" multiple="multiple" class="form-select" {if $field.options_map.lazyload}data-remote-source-url="{$data.remote_url}"{/if}>
                {section name=ix loop=$data.selected_users}
                    <option value="{$data.selected_users[ix]}" selected>{if ($field.showRealname == 'y')}{$data.selected_users[ix]|username}{else}{$data.selected_users[ix]}{/if}</option>
                {/section}
            </select>
            <input type="hidden" name="{$field.html_name}" id="hidden_{$field.fieldId}" value="{$data.selected_users|implode:','}">
        </div>
    {/if}
</div>
{jq}
    var users{{$field.fieldId}} = {{$data.users|json_encode}};
    $("#user_group_selector_{{$field.fieldId}}").on("change", function() {
        var $selector = $('#user_selector_{{$field.fieldId}}'),
            selected = $selector.val(),
            group_users = {};

        if ($selector.data('remote-source-url')) {
            const url = new URL($selector.data('remote-source-url'));
            url.searchParams.set('groups', $(this).val().join(','));
            $selector.attr('data-remote-source-url', url.toString());
        } else {
            $.map($(this).val(), function(group) {
                $.extend(group_users, users{{$field.fieldId}}[group] || {});
            });
            var all_users = Object.keys(group_users);

            var $group_selector = $("#user_group_selector_{{$field.fieldId}}"),
                group_selected = $group_selector.val();
            if (all_users.length > 0) {
                $selector.removeClass("disabled").prop("disabled", false);
                $("#info").addClass("d-none");
            } else {
                $("#info").text("{{'You need first to select a group'|tra}}");
                if (group_selected.length > 0) {
                    $("#info").text("{{'No user found in the group(s).'|tra}}");
                }
                $selector.addClass("disabled").prop("disabled", true);
                $("#info").removeClass("d-none");
            }


            var to_remove = $.map(selected, function(user) {
                return $.inArray(user, all_users) < 0 ? user : null;
            });
            if (to_remove.length > 0 && ! confirm(tr("Please confirm if you want to remove the following users:")+" "+to_remove.join(', '))) {
                return;
            }
            $selector.empty();
            $.map(all_users, function(user){
                return {value: user, label: group_users[user]};
            }
            ).sort(function(u1, u2) {
                u1 = u1.label.toUpperCase();
                u2 = u2.label.toUpperCase();
                return u1 < u2 ? -1 : ( u1 > u2 ? 1 : 0 );
            }).map(function(opt) {
                $('<option>')
                    .attr('value', opt.value)
                    .text(opt.label)
                    .appendTo($('#user_selector_{{$field.fieldId}}'));
            });
            $selector.val(selected);
            const fieldName = "{{$field.html_name}}";
            const elementPlusTransfer = document.querySelector("el-transfer[field-name=\'" + fieldName + "\']");
            if (elementPlusTransfer?.shadowRoot) {
                const selectedOptions = elementPlusTransfer.shadowRoot.querySelector("select[name=\'" + fieldName + "\']").selectedOptions;
                const elementPlusTransferCopy = elementPlusTransfer.cloneNode(true);
                elementPlusTransferCopy.setAttribute("data", JSON.stringify(group_users));
                elementPlusTransferCopy.setAttribute("default-value", JSON.stringify([...selectedOptions].map(option => option.value).filter(value => group_users[value])));
                elementPlusTransfer.replaceWith(elementPlusTransferCopy);
            } else if (elementPlusTransfer) { // when the inner content hasn't been rendered yet by the scipt
                elementPlusTransfer.setAttribute("data", JSON.stringify(group_users));
            }
        }
    }).trigger('change');

    $("#user_selector_{{$field.fieldId}}").on("change", function() {
        var selectedUsers = $(this).val() || [];
        $("#hidden_{{$field.fieldId}}").val(selectedUsers.join(","));
    }).trigger('change');
{/jq}
