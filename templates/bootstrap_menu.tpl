{if $prefs.jquery_smartmenus_enable eq 'y'}
    {* Smartmenu megamenu navigation *}
    <ul class="{if $bs_menu_class}{$bs_menu_class}{else}sm-nav navbar-nav me-auto nav{/if} {if $module_params.type|default:null eq 'vert'}{*sm-navbar--vertical*} flex-column{else}sm-navbar--horizontal{/if}">
        {foreach from=$list item=item}
            {include file='bootstrap_smartmenu.tpl' item=$item}
        {/foreach}
    </ul>
{else}
    {* Bootstrap 4 navigation *}
    <ul class="{if $bs_menu_class}{$bs_menu_class}{else} navbar-nav me-auto{/if} {if $module_params.type|default:null eq 'vert'}bs-vertical flex-column{/if}">
        {foreach from=$list item=item}
            {if not empty($item.children)}
                {if $module_params.type|default:null eq 'horiz'}
                    <li class="nav-item dropdown {$item.class|escape|default:null} {if !empty($item.selected)}active{/if}">
                        <a class="nav-link dropdown-toggle" id="menu_option{$item.optionId|escape}" data-bs-toggle="dropdown" role="button" aria-expanded="false" aria-haspopup="true">
                        {* <a class="nav-link collapse-toggle" data-bs-toggle="collapse" href="#menu_option{$item.optionId|escape}" aria-expanded="false"> *}
                            {if $menu_info.use_items_icons eq "y" && $item.icon}
                                {icon name=$item.icon}
                            {/if}
                            {tr}{$item.name}{/tr}
                        </a>
                        <div class="dropdown-menu {if !empty($item.selected)}show{/if}" aria-labelledby="menu_option{$item.optionId|escape}">
                            {foreach from=$item.children item=sub}
                                <a class="dropdown-item {$sub.class|escape} {if $sub.selected|default:null}active{/if}" href="{$sub.sefurl|escape}">
                                    {if $menu_info.use_items_icons eq "y" && $sub.icon}
                                        {icon name=$sub.icon}
                                    {/if}
                                    {tr}{$sub.name}{/tr}
                                </a>
                            {/foreach}
                        </div>
                    </li>
                {else}
                    <li class="nav-item {$item.class|escape|default:null} {if !empty($item.selected)}active{/if}">
                        <a class="nav-link collapse-toggle" data-bs-toggle="collapse" href="#menu_option{$item.optionId|escape}" aria-expanded="false">
                        {if $menu_info.use_items_icons eq "y" && $item.icon}
                            {icon name=$item.icon}
                        {/if}
                        {tr}{$item.name}{/tr}&nbsp;<small>{icon name="caret-down"}</small>
                        </a>
                        <ul id="menu_option{$item.optionId|escape}" class="nav flex-column collapse {if !empty($item.selected)}show{/if}" aria-labelledby="#menu_option{$item.optionId|escape}">
                            {foreach from=$item.children item=sub}
                                <li class="nav-item {$sub.class|escape|default:null} {if !empty($sub.selected)}active{/if}">
                                    <a class="nav-link {$sub.class|escape} {if $sub.selected|default:null}active{/if}" href="{$sub.sefurl|escape}">
                                        <small>
                                            {if $menu_info.use_items_icons eq "y" && $sub.icon}
                                                {icon name=$sub.icon}
                                            {/if}
                                            {tr}{$sub.name}{/tr}
                                        </small>
                                    </a>
                                </li>
                            {/foreach}
                        </ul>
                    </li>
                {/if}
            {else}
                <li class="nav-item {$item.class|escape|default:null} {if !empty($item.selected)}active{/if}">
                    <a class="nav-link" href="{$item.sefurl|escape}">
                        {if $menu_info.use_items_icons eq "y" && $item.icon}
                            {icon name=$item.icon}
                        {/if}{tr}{$item.name}{/tr}
                    </a>
                </li>
            {/if}
        {/foreach}
    </ul>
{/if}
