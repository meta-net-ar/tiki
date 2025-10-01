{if empty($groups)}
    &mdash;
{else}
    <ul>
    {foreach from=$groups item=group}
        <li>
        {if $params.linkhome eq 'y' && !empty($group.groupHome)}
            <a href="{$group.groupHome|sefurl:wiki}">
            {$link='y'}
        {/if}{$group.groupName|escape}{if !empty($link)}</a>{/if}{if $params.description eq 'y'} - {$group.groupDesc}{/if}
        </li>
    {/foreach}
    </ul>
{/if}
