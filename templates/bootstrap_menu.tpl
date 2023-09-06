{if $prefs.jquery_smartmenus_enable eq 'y'}
    {* Smartmenu megamenu navigation *}
    <ul class="{if $bs_menu_class}{$bs_menu_class}{else} navbar-nav me-auto nav{/if} {if $module_params.type|default:null eq 'vert'}sm-vertical flex-column{/if}">
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
                            {if $prefs.menus_items_icons eq "y"}
                                <span 
                                    data-preset="icon-picker" 
                                    tabindex="0" 
                                    role="button" 
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="focus" 
                                    title="Pick an icon" 
                                    data-icon-for="{$item.optionId}" 
                                    data-icon-editable="{if $prefs.theme_iconeditable eq "y" AND $tiki_p_admin eq 'y'}yes{/if}"
                                >
                                {* here we display the icon html passed constructed from function.menu.php *}
                                {* TODO Add a way to modify the look: eg. the size,color,position etc*}
                                {* Refer to the style section in this file to know how this is done manually*}
                                {$menu_icons_html[$item.optionId]}
                                </span>
                            {/if}
                            {tr}{$item.name}{/tr}
                        </a>
                        <div class="dropdown-menu {if !empty($item.selected)}show{/if}" aria-labelledby="menu_option{$item.optionId|escape}">
                            {foreach from=$item.children item=sub}
                                <a class="{*nav-item *}dropdown-item {$sub.class|escape} {if $sub.selected|default:null}active{/if}" href="{$sub.sefurl|escape}">
                                    {if $prefs.menus_items_icons eq "y"}
                                        <span 
                                            data-preset="icon-picker" 
                                            tabindex="0" 
                                            role="button" 
                                            data-bs-toggle="popover" 
                                            data-bs-trigger="focus" 
                                            title="Pick an icon"   
                                            data-icon-for="{$sub.optionId}" data-icon-editable="{if $prefs.theme_iconeditable eq "y" AND $tiki_p_admin eq 'y'}yes{/if}"
                                        >
                                        {* here we display the icon html passed constructed from function.menu.php *}
                                        {* TODO Add a way to modify the look: eg. the size,color,position etc*}
                                        {* Refer to the style section in this file to know how this is done manually*}
                                        {$menu_icons_html[$sub.optionId]}
                                        </span>
                                    {/if}
                                    {tr}{$sub.name}{/tr}
                                </a>
                            {/foreach}
                        </div>
                    </li>
                {else}
                    <li class="nav-item {$item.class|escape|default:null} {if !empty($item.selected)}active{/if}">
                        <a class="nav-link collapse-toggle" data-bs-toggle="collapse" href="#menu_option{$item.optionId|escape}" aria-expanded="false">
                            {if $prefs.menus_items_icons eq "y"}
                                <span 
                                    data-preset="icon-picker" 
                                    tabindex="0" 
                                    role="button" 
                                    data-bs-toggle="popover" 
                                    data-bs-trigger="focus" 
                                    title="Pick an icon" 
                                    data-icon-for="{$item.optionId}" 
                                    data-icon-editable="{if $prefs.theme_iconeditable eq "y" AND $tiki_p_admin eq 'y'}yes{/if}"
                                >
                                {* here we display the icon html passed constructed from function.menu.php *}
                                {* TODO Add a way to modify the look: eg. the size,color,position etc*}
                                {* Refer to the style section in this file to know how this is done manually*}
                                {$menu_icons_html[$item.optionId]}
                                </span>
                            {/if}
                            {tr}{$item.name}{/tr}&nbsp;<small>{icon name="caret-down"}</small>
                        </a>
                        <ul id="menu_option{$item.optionId|escape}" class="nav flex-column collapse {if !empty($item.selected)}show{/if}" aria-labelledby="#menu_option{$item.optionId|escape}">
                            {foreach from=$item.children item=sub}
                                <li class="nav-item {$sub.class|escape|default:null} {if !empty($sub.selected)}active{/if}">
                                    <a class="nav-link {$sub.class|escape} {if $sub.selected|default:null}active{/if}" href="{$sub.sefurl|escape}">
                                        {if $prefs.menus_items_icons eq "y"}
                                            <span 
                                                data-preset="icon-picker" 
                                                tabindex="0" 
                                                role="button" 
                                                data-bs-toggle="popover" 
                                                data-bs-trigger="focus" 
                                                title="Pick an icon" 
                                                data-icon-for="{$sub.optionId}" 
                                                data-icon-editable="{if $prefs.theme_iconeditable eq "y" AND $tiki_p_admin eq 'y'}yes{/if}"
                                            >
                                            {* here we display the icon html passed constructed from function.menu.php *}
                                            {* TODO Add a way to modify the look: eg. the size,color,position etc*}
                                            {* Refer to the style section in this file to know how this is done manually*}
                                            {$menu_icons_html[$sub.optionId]}
                                            </span>
                                        {/if}
                                        <small>{tr}{$sub.name}{/tr}</small>
                                    </a>
                                </li>
                            {/foreach}
                        </ul>
                    </li>
                {/if}
            {else}
                <li class="nav-item {$item.class|escape|default:null} {if !empty($item.selected)}active{/if}">
                    <a class="nav-link" href="{$item.sefurl|escape}">
                        {if $prefs.menus_items_icons eq "y"}
                            <span
                                data-preset="icon-picker" 
                                tabindex="0" 
                                role="button" 
                                data-bs-toggle="popover" 
                                data-bs-trigger="focus" 
                                title="Pick an icon" 
                                data-icon-for="{$item.optionId}" 
                                data-icon-editable="{if $prefs.theme_iconeditable eq "y" AND $tiki_p_admin eq 'y'}yes{/if}"
                            >
                            {* here we display the icon html passed constructed from function.menu.php *}
                            {* TODO Add a way to modify the look: eg. the size,color,position etc*}
                            {* Refer to the style section in this file to know how this is done manually*}
                            {$menu_icons_html[$item.optionId]}
                            </span>
                        {/if}{tr}{$item.name}{/tr}
                    </a>
                </li>
            {/if}
        {/foreach}
    </ul>
{/if}

<script lang="js">
    var iconset = {$icon_picker_set}
</script>

<style>
    /* You can use the following rule to change the menu icon look*/
    /*
        img.menu-icon{
            height:12px !important;
        }

        i.menu-icon{
            color:red;
        }
    */
    span[data-preset='icon-picker'][data-icon-editable='yes']{
        cursor: zoom-in;
    }
    .icon-picker-container{
        word-break: break-all;
        background-color: transparent;
        border: none;
        text-decoration: none;
        cursor: pointer;
    }
    .icon-picker-container i,.icon-picker-container img{
        transition: transform .2s;
    }

    .icon-picker-container i{
        font-size: 20px;
    }
    .icon-picker-container img{
        height: auto;
        width: 20px;
    }
    .icon-picker-container i:hover{
        font-size: 25px;
    }
    .icon-picker-container img:hover{
        height: auto;
        width: 25px;
    }
    .popover-container{ /* custom class passed during the popover building in javascript*/
        width:300px;
        z-index: 999999 !important;
    }

    .popover-container .popover-body{
        max-height: 300px;
        overflow-y: auto;
    }

    .popover-container .popover-body .icons{
        display: grid;
        grid-template-columns: 20px 20px 20px 20px 20px 20px 20px 20px;
        grid-gap: 10px;
    }

    .filter-icon-set{
        padding:10px;
    }
</style>

<script>
    var filter_out_icons = function (el){
        var filter = $.trim($(el).val());
        $('.icons a.icon-picker-container').each(function(indx,el){
            let att = $(this).attr('data-icon-name');
            if(att.toLowerCase().indexOf(filter.toLowerCase())===-1) {
                $(this).hide();
            }
            else {
                $(this).show();
            }
        })
    }

    var change_menu_icon = function (el,menu_option_id,icon_name){
        $(el).tikiModal(" ");
        $.post("tiki-ajax_services.php",{
            'controller':'iconpicker',
            'action':'change_menu_icon',
            'menu_option_id':menu_option_id,
            'icon_name':icon_name
        }).done(function(){
            location.reload();
        }).fail(function(){
            alert("{tr}Failed to update the icon, please try again later{/tr}");
        }).always(function(){
            $(el).tikiModal();
        })
    }
</script>

{jq}

    jQuery(document).ready(function(){
        $("span[data-preset='icon-picker'][data-icon-editable='yes']").each(function(index,el){
        $(this).on('click',function(event){
                event.stopPropagation();
                event.preventDefault();
        })
        var action_menu_option_id = $(this).attr("data-icon-for");
        var iconset_card = iconset.map(function(el){
            return `<a onclick="change_menu_icon(this,'${action_menu_option_id}','${el[0]}')" class="btn-xl m-2 icon-picker-container" data-icon-name="${el[0]}" data-menu-option-id="${action_menu_option_id}">${el[1]}</a>`;
        });

        var html =`
            <div class="filter-icon-set">
                <div class="input-group mb-3">
                    <span class="input-group-text"><img src="/img/icons/search.png" style="height:20px;"></span>
                    <input type="text" class="form-control" placeholder="{tr}search{/tr}..." oninput="filter_out_icons(this)">
                </div>
            </div>
            <div class="icons">${iconset_card.join("")}</div>
        `;
        var popover = new bootstrap.Popover(this, {
                trigger: 'focus',
                html : true,
                placement : 'bottom',
                content: html,
                container:'body',
                customClass : 'popover-container',
                sanitizeFn: function (content) {
                    return content //for now we are not sanitizing but we can do so in future
                  }
            })
        })
    })
{/jq}
