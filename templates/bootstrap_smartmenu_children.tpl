{if $item.type eq '-' or ($item.type eq 's' and empty($item.name)) or (empty($item.name) and empty($item.sefurl) and empty($item.children) and empty($item.block))}
    <li role="separator" class="px-3"><hr class="dropdown-divider my-2"></li>
{elseif not empty($item.children)}
    <li class="sm-sub-item {if $item.selected|default:null} active{/if} {$item.class|escape}">
        <a href="#sm_submenu_{$item.optionId|escape}" class="sm-sub-link dropdown-item sm-sub-toggler" data-bs-toggle="collapse" aria-expanded="false">
            {if $menu_info.use_items_icons eq "y" && $item.icon}
                <span class="me-2">{icon name=$item.icon}</span>
            {/if}
            <span class="menu-item-label me-auto">{tr}{$item.name}{/tr}</span>&nbsp;<small>{icon name="caret-down"}</small>
        </a>
        <ul id="sm_submenu_{$item.optionId|escape}" class="sm-sub dropdown-menu collapse">
            {* {if $sub}
                <li class="dropdown-header">{tr}{$item.name}{/tr}</li>
                <li class="dropdown-divider"></li>
            {/if} *}
            {foreach from=$item.children item=sub}
                {include file='bootstrap_smartmenu_children.tpl' item=$sub sub=true}
            {/foreach}
        </ul>
    </li>
{else}
    <li class="sm-sub-item {$item.class|escape}{if $item.selected|default:null} active{/if}">
        {if !empty($item.block)}
            {* mega-menu class prevents error (TypeError: Cannot read property 'parentNode' of null - jquery.smartmenus.js:line 664) when block items contains <ul> elements  *}
            <ul class="sm-sub mega-menu block--container">
                {if $menu_info.use_items_icons eq "y" && $item.icon}
                    <span class="me-2">{icon name=$item.icon}</span>
                {/if}
                <span class="menu-item-label me-auto">{tr}{$item.name}{/tr}</span>
            </ul>
        {else}
            <a class="sm-sub-link dropdown-item" href="{$item.sefurl|escape}">
                {if $menu_info.use_items_icons eq "y" && $item.icon}
                    <span class="me-2">{icon name=$item.icon}</span>
                {/if}
                <span class="menu-item-label me-auto">{tr}{$item.name}{/tr}</span>
            </a>
        {/if}
    </li>
{/if}
