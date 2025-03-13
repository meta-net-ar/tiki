<a name="listexecute_{$iListExecute}"></a>
<form method="post" action="#listexecute_{$iListExecute}" class="d-flex flex-row flex-wrap align-items-center list-executable" id="listexecute-{$iListExecute}" data-id="{$id}">
    <input type="hidden" name="plugin" value="{$fingerprint}">
    <input type="hidden" name="iListExecute" value="{$iListExecute}">
    <input type="checkbox" class="form-check-input listexecute-select-all" aria-label="{tr}Select{/tr}" name="selectall" value="">
    <input type="hidden" name="objects{$iListExecute}[]" value="" class="listexecute-all">
    {ticket}
    {tr}Select All{/tr}
    <ol>
        {foreach from=$results item=entry}
            <li>
                <input type="checkbox" class="checkbox_objects form-check-input" aria-label="{tr}Select{/tr}" name="objects{$iListExecute}[]" value="{$entry.object_type|escape}:{$entry.object_id|escape}">
                {if isset($entry.report_status) && $entry.report_status eq 'success'}
                    {icon name='ok'}
                {elseif isset($entry.report_status) && $entry.report_status eq 'error'}
                    {icon name='error'}
                {/if}
                {object_link type=$entry.object_type id=$entry.object_id backuptitle=$entry.title}
            </li>
        {/foreach}
    </ol>
    <select name="list_action" class="form-select check_submit_select" id="check_submit_select_{$id}">
        <option></option>
        {foreach from=$actions item=action}
            <option value="{$action->getName()|escape}" data-input="{$action->requiresInput()}" data-inputtype="{$action->inputtype()}"{if $action->getDefault()} selected{/if}>
                {$action->getName()|escape}
            </option>
        {/foreach}
    </select>
    <div class="list_input_container" id="list_input_container_{$id}">
    </div>
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
    <input type="submit" class="btn btn-primary btn-sm list_execute_submit" title="{tr}Apply Changes{/tr}" id="submit_form_{$id}" disabled value="{tr}Apply{/tr}">
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
