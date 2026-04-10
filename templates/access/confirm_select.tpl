{extends $global_extend_layout|default:'layout_view.tpl'}
{block name="title"}
    {title}{$title}{/title}
{/block}
{block name="content"}
    {include file='access/include_items.tpl'}
    <form id='confirm-action' class='confirm-action' action="{service controller="$confirmController" action="$confirmAction"}" method="post">
        {include file='access/include_hidden.tpl'}
        <div class="mb-3 row">
            <label for="toId" class="col-form-label">
                <h5>{$toMsg}</h5>
            </label><br><br>
            <div class="col-lg-7">
                <select class="form-select" name="toId">
                    {foreach from=$toList key=id item=name}
                        <option value="{$id|escape}">
                            {$name|escape}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>
        {include file='access/include_submit.tpl'}
    </form>
{/block}
