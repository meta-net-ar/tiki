{extends $global_extend_layout|default:'layout_view.tpl'}
{block name="title"}
    {title}{$title|escape}{/title}
{/block}
{block name="content"}
    <form id='confirm-action' class='confirm-action' action="{service controller="category" action="$confirmAction"}" method="post">
        <div class="mb-3 row mx-0">
            <h5 class="w-100">{$customMsg|escape}</h5>
            {include file="access/render_list.tpl" list=$objects}
            <h5 class="w-100">{tr}In these categories{/tr}</h5>
            <div class="w-100">
                <select name="categIds[]" class="form-select" multiple>
                    {foreach $categories as $cat}}
                        <option value="{$cat.name|escape}-{$cat.id|escape}" selected>
                            {$cat.name|escape}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>
        {include file='access/include_hidden.tpl'}
        {include file='access/include_submit.tpl'}
    </form>
{/block}
