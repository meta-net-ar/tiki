{if not empty($item.children)}
    <li class="mega-menu--item mega-menu--item-level-{$item.sectionLevel}">
        <a href="{$item.sefurl|escape}" class="" data-bs-toggle="dropdown">
            {if $menu_info.use_items_icons eq "y" && $item.icon}
                {icon name=$item.icon}
            {/if}
            {tr}{$item.name}{/tr}
        </a>
        <ul class="">
            {foreach from=$item.children item=sub}
                {include file='bootstrap_smartmenu_megamenu_children.tpl' item=$sub sub=true}
            {/foreach}
        </ul>
    </li>
{else}
    <li class="mega-menu--item mega-menu--item-level-{$item.sectionLevel}">
        {if !empty($item.block)}
            <div class="block--container">
                {if $menu_info.use_items_icons eq "y" && $item.icon}
                    {icon name=$item.icon}
                {/if}
                {tr}{$item.name}{/tr}
            </div></a> {* </a> added to close the anchor in the menu list item -- g_c-l *}
        {else}
            <a class="" href="{$item.sefurl|escape}">
                {if $menu_info.use_items_icons eq "y" && $item.icon}
                    {icon name=$item.icon}
                {/if}
                {tr}{$item.name}{/tr}
            </a>
        {/if}
    </li>
{/if}
