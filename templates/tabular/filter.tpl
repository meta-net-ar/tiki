{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
<form class="no-ajax" method="{$method|escape}" action="{service controller="tabular" action=$action}">
    {foreach $filters as $filter}
        <div class="mb-3 row">
            <label class="col-form-label" for="{$filter.id|escape}">{$filter.label|escape}</label>
            {$filter.control}
        </div>
    {/foreach}
    <div class="submit">
        <input type="hidden" name="tabularId" value="{$tabularId|escape}"/>
        <input type="hidden" name="controller" value="tabular"/>
        <input type="hidden" name="action" value="{$action|escape}"/>
        <input type="submit" class="btn btn-primary" value="{$label|escape}">
    </div>
</form>
{/block}
