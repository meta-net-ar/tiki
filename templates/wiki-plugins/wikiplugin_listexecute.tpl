<a name="listexecute_{$iListExecute}"></a>
<form method="post" action="#listexecute_{$iListExecute}" class="d-flex flex-row flex-wrap align-items-center" id="listexecute-{$iListExecute}">
    <button class="listexecute-select-all btn btn-primary btn-sm">{tr}Select All{/tr}</button>
    <ol>
        {foreach from=$results item=entry}
            <li>
                <input type="checkbox" name="objects{$iListExecute}[]" value="{$entry.object_type|escape}:{$entry.object_id|escape}">
                {if $entry.report_status eq 'success'}
                    {icon name='ok'}
                {elseif $entry.report_status eq 'error'}
                    {icon name='error'}
                {/if}
                {object_link type=$entry.object_type id=$entry.object_id backuptitle=$entry.title}
            </li>
        {/foreach}
    </ol>
    <select name="list_action" class="form-select">
        <option></option>
        {foreach from=$actions item=action}
            <option value="{$action->getName()|escape}" data-input="{$action->requiresInput()}" data-inputtype="{$action->inputtype()}"{if $action->getDefault()} selected{/if}>
                {$action->getName()|escape}
            </option>
        {/foreach}
    </select>
    <input type="text" name="list_input" value="" class="form-control" style="display:none">
    {* category_tree *}
    {if $prefs.feature_categories eq 'y' and $tiki_p_modify_object_categories eq 'y' and count($categories) gt 0}
        <div class="multiselect form-select cat_tree" style="display:none;">
            {if is_array($categories) and count($categories) gt 0}
                {$cat_tree}
                <input type="hidden" name="cat_categorize" value="on">
                <div class="clearfix">
                    {if $tiki_p_admin_categories eq 'y'}
                        <div class="float-sm-end">
                            <a class="btn btn-link btn-sm tips" href="tiki-admin_categories.php" title=":{tr}Admin Categories{/tr}">
                                {icon name="cog"} {tr}Categories{/tr}
                            </a>
                        </div>
                    {/if}
                    {select_all checkbox_names='cat_categories[]' label="{tr}Select/deselect all categories{/tr}"}
                </div> {* end .clear *}
            {else}
                <div class="clearfix">
                    {if $tiki_p_admin_categories eq 'y'}
                        <div class="float-sm-end">
                            <a class="btn btn-link" href="tiki-admin_categories.php" title=":{tr}Admin Categories{/tr}">
                                {icon name="cog"} {tr}Categories{/tr}
                            </a>
                        </div>
                    {/if}
                </div> {* end .clear *}
                {tr}No categories defined{/tr}
            {/if}
        </div> {* end #multiselect *}
    {/if}
    <input type="submit" class="btn btn-primary btn-sm" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}">
    {if isset($smarty.get.page) && isset($schedulers_amount)}
        <div class="ms-3">
            {if $schedulers_amount eq 0}
                {assign var="console_command" value="list:execute {$smarty.get.page} <action>"|urlencode}
                <a href="tiki-admin_schedulers.php?task=ConsoleCommandTask&console_command={$console_command}&add=1">{tr}Create scheduler{/tr}</a>
            {elseif $schedulers_amount eq 1}
                <a href="tiki-admin_schedulers.php?scheduler={$scheduler_id}">{tr}View scheduler{/tr}</a>
            {else}
                <a href="tiki-admin_schedulers.php">{tr}Multiple schedulers{/tr}</a>
            {/if}
        </div>
    {/if}
</form>
{jq}
$('.listexecute-select-all').removeClass('listexecute-select-all').on('click', function (e) {
    $(this).closest('form').find(':checkbox:not(:checked):not(:disabled)').prop('checked', true);
    e.preventDefault();
});
$('#listexecute-{{$iListExecute}}').find('select[name=list_action]').on('change', function() {
    var inputType = $(this).find('option:selected').data('inputtype');
    if( $(this).find('option:selected').data('input') ) {
        //if it is 'text', show the text input, else it is 'category_tree', show the category tree.
        if (inputType === "text") {
            $(this).siblings('input[name=list_input]').show();
        } else {
            $(".cat_tree").show();
        }
    } else {
        $(this).siblings('input[name=list_input]').hide();
        $(".cat_tree").hide();
    }
});
$('#listexecute-{{$iListExecute}}').submit(function(){
    var filters = $('#list_filter{{$iListExecute|replace:'wplistexecute-':''}} form').serializeArray();
    for(var i = 0, l = filters.length; i < l; i++) {
        var inp = $('<input type="hidden">');
        inp.attr('name', filters[i].name);
        inp.val(filters[i].value);
        $('#listexecute-{{$iListExecute}}').append(inp);
    }
});
{/jq}
