{if $sub.type eq '-' or ($sub.type eq 's' and empty($sub.name))}
    <li class="nav-item" role="separator">
        <hr class="dropdown-divider my-2">
    </li>
{elseif not empty($sub.children)}
    <li class="nav-item {$sub.class|escape|default:null} {if !empty($sub.selected)}active{/if}">
        <a class="nav-link collapse-toggle ps-3" data-bs-toggle="collapse" href="#menu_option{$sub.optionId|escape}" aria-expanded="false">
            {if $menu_info.use_items_icons eq "y" && $sub.icon}
                {icon name=$sub.icon}
            {/if}
            <small>
                {tr}{$sub.name}{/tr}
            </small>&nbsp;<small>{icon name="caret-down"}</small>
        </a>
        <ul id="menu_option{$sub.optionId|escape}" class="nav flex-column collapse ms-2 {if !empty($sub.selected)}show{/if}" aria-labelledby="menu_option{$sub.optionId|escape}">
            {foreach from=$sub.children item=inner}
                {include file='bootstrap_menu_vertical_child.tpl' sub=$inner}
            {/foreach}
        </ul>
    </li>
{else}
    <li class="nav-item {$sub.class|escape|default:null} {if !empty($sub.selected)}active{/if}">
        <a class="nav-link ps-3 {if $sub.selected|default:null}active{/if}" href="{$sub.sefurl|escape}">
            <small>
                {if $menu_info.use_items_icons eq "y" && $sub.icon}
                    {icon name=$sub.icon}
                {/if}
                {tr}{$sub.name}{/tr}
            </small>
        </a>
    </li>
{/if}
