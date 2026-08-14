{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
<select class="form-select" name="categories[]">
    {foreach from=$categories item=categ key=categId}
        <option value="{$categId|escape}" {if !empty($categ.selected)} selected="selected" {/if}>{$categ.name|escape}</option>
    {/foreach}
</select>
{/block}
