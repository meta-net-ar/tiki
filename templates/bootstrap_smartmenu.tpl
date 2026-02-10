{if $item.type eq '-'}
    <li class="sm-nav-item nav-item">
        <hr class="menu-separator my-2">
    </li>
{elseif not empty($item.children)}
    <li class="sm-nav-item nav-item{if $item.selected|default:null} active{/if} {$item.class|escape} {if $module_params.megamenu eq 'y' and $module_params.megamenu_static eq 'y' }static{/if}">
        <a href="#sm_menu_{$item.optionId|escape}" class="sm-nav-link nav-link sm-sub-toggler" data-bs-toggle="collapse" aria-expanded="false">
            {if $menu_info.use_items_icons eq "y" && $item.icon}
                <span class="me-2">{icon name=$item.icon}</span>
            {/if}
            <span class="mwnu-item-label me-auto">{tr}{$item.name}{/tr}</span>&nbsp;<small>{icon name="caret-down"}</small>
        </a>
        {if $item.sectionLevel eq 0 and $module_params.megamenu eq 'y'}
            <ul class="sm-sub sm-sub--mega">
                <li class="mega-menu--inner-container row mx-0 sm-nav-item--has-mega">
                    <ul class="mega-menu--item-container {if $module_params.megamenu_images eq 'y' and $item.image} col-sm-9{else} col-sm-12{/if} pd-0">
                        {foreach from=$item.children item=sub}
                            {include file='bootstrap_smartmenu_megamenu_children.tpl' item=$sub sub=true}
                        {/foreach}
                    </ul>
                    {if $module_params.megamenu_images eq 'y' and $item.image}
                        <div class="mega-menu-image col-sm-3 pe-0">
                            {* Test image link - https://picsum.photos/300/300 *}
                            <img src="{$item.image}" alt="Megamenu image" />
                        </div>
                    {/if}
                </li>
            </ul>
        {else}
            <ul id="sm_menu_{$item.optionId|escape}" class="sm-sub dropdown-menu collapse">
                {foreach from=$item.children item=sub}
                    {include file='bootstrap_smartmenu_children.tpl' item=$sub sub=true}
                {/foreach}
            </ul>
        {/if}
    </li>
{else}
    <li class="sm-nav-item nav-item{$item.class|escape}{if $item.selected|default:null} active{/if}">
        <a class="sm-nav-link nav-link" href="{$item.sefurl|escape}">
            {if $menu_info.use_items_icons eq "y" && $item.icon}
                <span class="me-2">{icon name=$item.icon}</span>
            {/if}
            <span class="mwnu-item-label me-auto">{tr}{$item.name}{/tr}</span>
        </a>
    </li>
{/if}
