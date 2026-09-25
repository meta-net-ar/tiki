{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    {include file='object/permissions_origin.tpl' permissions=$permissions}
    {if $permissions.additional}<p>{$permissions.additional}</p>{/if}
    {if $permissions.perms}
        {foreach from=$permissions.perms key=permType item=perms}
            <h2 class="h5">{$permType|escape}</h2>
            <ul class="list-group mb-3">
                {foreach from=$perms key=perm item=permInfo}
                    <li class="list-group-item">
                        <div>{$permInfo.description|escape} <code>{$perm|escape}</code></div>
                        <small class="text-body-secondary">{tr}Groups:{/tr} {$permInfo.groups|join:', '|escape}</small>
                    </li>
                {/foreach}
            </ul>
        {/foreach}
    {else}
        {tr}No permissions to display{/tr}
    {/if}
{/block}
