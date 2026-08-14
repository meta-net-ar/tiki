{if $sub.type eq '-' or ($sub.type eq 's' and empty($sub.name))}
    <li role="separator"><hr class="dropdown-divider my-1"></li>
{elseif not empty($sub.children)}
    {assign var=accId value="menu_acc_{$sub.optionId}"}
    {assign var=accOpen value=false}
    {if !empty($sub.selected) || !empty($sub.selectedAscendant)}{assign var=accOpen value=true}{/if}
    <li class="tiki-menu-submenu-accordion">
        <button type="button"
            class="dropdown-item d-flex align-items-center gap-2 text-start w-100 border-0{if !$accOpen} collapsed{/if} {$sub.class|escape}"
            data-bs-toggle="collapse"
            data-bs-target="#{$accId|escape}"
            aria-expanded="{if $accOpen}true{else}false{/if}"
            aria-controls="{$accId|escape}">
            {if $menu_info.use_items_icons eq "y" && $sub.icon}
                {icon name=$sub.icon}
            {/if}
            <span class="flex-grow-1 text-truncate">{tr}{$sub.name}{/tr}</span>
            <span class="tiki-menu-accordion-caret flex-shrink-0 opacity-75" aria-hidden="true">{icon name="caret-down"}</span>
        </button>
        <ul id="{$accId|escape}" class="tiki-menu-submenu-panel list-unstyled mb-0{if $accOpen} collapse show{else} collapse{/if}">
            {foreach from=$sub.children item=inner}
                {include file='bootstrap_menu_horizontal_child.tpl' sub=$inner}
            {/foreach}
        </ul>
    </li>
{else}
    <li>
        <a class="dropdown-item {$sub.class|escape} {if $sub.selected|default:null}active{/if}" href="{$sub.sefurl|escape}">
            {if $menu_info.use_items_icons eq "y" && $sub.icon}
                {icon name=$sub.icon}
            {/if}
            {tr}{$sub.name}{/tr}
        </a>
    </li>
{/if}
