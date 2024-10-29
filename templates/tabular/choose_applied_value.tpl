{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form method="post" action="{service controller=tabular action=choose_applied_value tabularId=$tabularId filterIndex=$filterIndex}">
        {if $filter}
        <div class="mb-3 row">
            <label class="col-form-label" for="{$filter->getControl()->getId()|escape}">{$filter->getLabel()|escape}</label>
            {$filter->getControl()}
        </div>
        {else}
        <div class="mb-3">{tr}Filter not found.{/tr}</div>
        {/if}
        <div class="submit">
            <input class="btn btn-primary" type="submit" value="{tr}Save{/tr}">
        </div>
    </form>
{/block}
