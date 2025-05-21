<a name="listexecute_{$iListExecute}"></a>
<form method="post" action="#listexecute_{$iListExecute}" class="d-flex flex-column flex-wrap list-executable" id="listexecute-{$iListExecute}" data-id="{$id}">
    <input type="hidden" name="plugin" value="{$fingerprint}">
    <input type="hidden" name="objects{$iListExecute}[]" value="" class="listexecute-all">
    {ticket}
    <div class="form-check align-self-center me-2">
        <input type="checkbox" class="form-check-input listexecute-select-all" id="sa_listexecute-{$iListExecute}" aria-label="{tr}Select{/tr}" name="selectall" value="">
        <label class="form-check-label" for="sa_listexecute-{$iListExecute}">{tr}Select All{/tr}</label>
    </div>
    <ol class="list list-group list-group-flush mb-2">
        {foreach from=$results item=entry}
            <li class="list-group-item list-group-item-action">
                <input type="checkbox" class="checkbox_objects form-check-input me-1" aria-label="{tr}Select{/tr}" id="{$entry.object_type|replace:" ":"-"|escape}_{$entry.object_id|escape}" name="objects{$iListExecute}[]" value="{$entry.object_type|escape}:{$entry.object_id|escape}">
                {if isset($entry.report_status) && $entry.report_status eq 'success'}
                    {icon name='ok'}
                {elseif isset($entry.report_status) && $entry.report_status eq 'error'}
                    {icon name='error'}
                {/if}
                <label class="form-check-label stretched-link" for="{$entry.object_type|replace:" ":"-"|escape}_{$entry.object_id|escape}">{object_link type=$entry.object_type id=$entry.object_id backuptitle=$entry.title|escape}</label>
            </li>
        {/foreach}
    </ol>
    <select name="list_action" class="form-select check_submit_select mb-2" id="check_submit_select_{$id}">
        <option></option>
        {foreach from=$actions item=action}
            <option value="{$action->getName()|escape}" data-input="{$action->requiresInput()}" data-inputtype="{$action->inputtype()}"{if $action->getDefault()} selected{/if}>
                {$action->getName()|tra|escape}
            </option>
        {/foreach}
    </select>
    <div class="list_input_container mb-2" id="list_input_container_{$id}" style="display:none"></div>
    <input type="text" name="list_input" value="" class="form-control mb-2" style="display:none">
    {* category_tree *}
    {if $prefs.feature_categories eq 'y' and $tiki_p_modify_object_categories eq 'y' and count($categories) gt 0}
        <div class="col-sm-12 mb-2 multiselect form-select cat_tree" style="display:none">
            {if is_array($categories) and count($categories) gt 0}
                {$cat_tree}
                <input type="hidden" name="cat_categorize" value="on">
                <div class="clearfix">
                    {if $tiki_p_admin_categories eq 'y'}
                        <div class="float-sm-end">
                            <a class="btn btn-link btn-sm tips" role="button" href="tiki-admin_categories.php" title=":{tr}Admin Categories{/tr}">
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
                            <a class="btn btn-link" role="button" href="tiki-admin_categories.php" title=":{tr}Admin Categories{/tr}">
                                {icon name="cog"} {tr}Categories{/tr}
                            </a>
                        </div>
                    {/if}
                </div> {* end .clear *}
                {tr}No categories defined{/tr}
            {/if}
        </div> {* end #multiselect *}
    {/if}
    <input type="submit" class="btn btn-primary btn-sm list_execute_submit mb-2 ms-auto mt-1" title="{tr}Apply Changes{/tr}" id="submit_form_{$id}" disabled value="{if !empty($label)}{tr}{$label|escape}{/tr}{else}{tr}Apply{/tr}{/if}">
    {if isset($smarty.get.page) && isset($schedulers_amount)}
        <div class="ms-3">
            {if $schedulers_amount eq 0}
                {$console_command="list:execute {$smarty.get.page} <action>"|urlencode}
                <a href="tiki-admin_schedulers.php?task=ConsoleCommandTask&console_command={$console_command}&add=1">{tr}Create scheduler{/tr}</a>
            {elseif $schedulers_amount eq 1}
                <a href="tiki-admin_schedulers.php?scheduler={$scheduler_id}">{tr}View scheduler{/tr}</a>
            {else}
                <a href="tiki-admin_schedulers.php">{tr}Multiple schedulers{/tr}</a>
            {/if}
        </div>
    {/if}
</form>
