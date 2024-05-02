{if !empty($p.helpurl)}
    {$icon = "help"}
{elseif $p.description}
    {$icon = "information"}
{/if}
{if isset($icon)}
    <a {if !empty($p.helpurl)} href="{$p.helpurl|escape}" target="tikihelp"{/if}
            class="tikihelp text-info tikihelp-prefs" title="{$p.name|escape}"  aria-label="{tr}Help{/tr}" data-bs-original-title="{$p.name|escape}" data-bs-content="{$p.description|escape} <p class='text-muted pt-2 small'>{tr _0="<code>`$p.preference`</code>"}Preference name: %0{/tr}</p>{if $p.separator && $p.type neq 'multiselector'}<br>{tr _0=$p.separator}Use &quot;%0&quot; to separate values.{/tr}{/if}">
        {icon name=$icon}
    </a>
{/if}
