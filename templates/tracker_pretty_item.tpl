{strip}
{* param item, fields, wiki(wiki:page or tpl:tpl), list_mode, perms, default_group, listfields *}
{if !isset($list_mode)}{$list_mode="n"}{/if}
{foreach from=$fields item=field}
    {if $field.type ne 'x'
        and (empty($listfields) or in_array($field.fieldId, $listfields))
        and ($field.type ne 'p' or $field.options_array[0] ne 'password')}
        {assign var=varname value='value'|cat:'i':$item.itemId:'f':$field.fieldId}
        {capture name=$varname}
            {trackeroutput item=$item field=$field list_mode=$list_mode showlinks=$context.showlinks url=$context.url}
        {/capture}
        {assign var="f_"|cat:$field.fieldId value=$smarty.capture[$varname]}
        {assign var="f_"|cat:$field.permName value=$smarty.capture[$varname]}
    {else}
        {assign var="f_"|cat:$field.fieldId value=''}
        {assign var="f_"|cat:$field.permName value=''}
    {/if}
{/foreach}
{assign var=f_created value=$item.created}
{assign var=f_lastmodif value=$item.lastModif}
{assign var=f_itemId value=$item.itemId}
{assign var=f_status value=$item.status}
{assign var=f_itemUser value=$item.itemUsers|join:', '}
{* ------------------------------------ *}
{if $force_separate_compile eq 'y'}
    {include file="$wiki" item=$item compile_id=$f_itemId}
{else}
    {include file="$wiki" item=$item}
{/if}
{/strip}
