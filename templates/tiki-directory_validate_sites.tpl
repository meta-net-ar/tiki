{title help="Directory"}{tr}Validate sites{/tr}{/title}

{* Display the title using parent *}
{include file='tiki-directory_admin_bar.tpl'} <br>
<h2>{tr}Sites{/tr}</h2>

{* Display the list of categories (items) using pagination *}
{* Links to edit, remove, browse the categories *}
<form action="tiki-directory_validate_sites.php" method="post" name="form_validate_sites">
{jq notonready=true}
var CHECKBOX_LIST = [{{section name=user loop=$items}'sites[{$items[user].siteId}]'{if not $smarty.section.user.last},{/if}{/section}}];
{/jq}
    {ticket}
    <br>
    <div class="{if $js}table-responsive{/if}"> {*the table-responsive class cuts off dropdown menus *}
        <table class="table table-striped table-hover">
            <tr>
                <th>
                    {if $items}
                        <input type="checkbox" name="checkall" onclick="checkbox_list_check_all('form_validate_sites',CHECKBOX_LIST,this.checked);">
                    {/if}</th>
                <th><a href="tiki-directory_validate_sites.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}Name{/tr}</a></th>
                <th><a href="tiki-directory_validate_sites.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'url_desc'}url_asc{else}url_desc{/if}">{tr}URL{/tr}</a></th>
                {if $prefs.directory_country_flag eq 'y'}
                    <th><a href="tiki-directory_validate_sites.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'country_desc'}country_asc{else}country_desc{/if}">{tr}country{/tr}</a></th>
                {/if}
                <th><a href="tiki-directory_validate_sites.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'hits_desc'}hits_asc{else}hits_desc{/if}">{tr}Hits{/tr}</a></th>
                <th>{tr}Action{/tr}</th>
            </tr>

            {section name=user loop=$items}
                <tr class="{cycle advance=false}">
                    <td class="checkbox-cell"><input class="form-check-input" aria-label="{tr}Select{/tr}" type="checkbox" name="sites[{$items[user].siteId}]" required></td>
                    <td class="text">{$items[user].name}</td>
                    <td class="text"><a href="{$items[user].url}" target="_blank">{$items[user].url}</a></td>
                    {if $prefs.directory_country_flag eq 'y'}
                        <td class="icon"><img src='img/flags/{$items[user].country}.png' alt='{$items[user].country}'></td>
                    {/if}
                    <td class="integer">{$items[user].hits}</td>
                    <td class="action">
                        {actions}
                            {strip}
                                <action>
                                    <a href="tiki-directory_admin_sites.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;siteId={$items[user].siteId}">
                                        {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <form action="tiki-directory_validate_sites.php" method="post">
                                        {ticket}
                                        <input type="hidden" name="offset" value="{$offset}">
                                        <input type="hidden" name="sort_mode" value="{$sort_mode}">
                                        <input type="hidden" name="remove" value="{$items[user].siteId}">
                                        <button type="submit" class="btn btn-link px-0 pt-0 pb-0" title=":{tr}Delete{/tr}" onclick="confirmPopup('{tr _0=$items[user].name}Are you sure you want to delete %0?{/tr}')">
                                            {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                        </button>
                                    </form>
                                </action>
                            {/strip}
                        {/actions}
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="6"><i>{tr}Directory Categories:{/tr}{assign var=fsfs value=1}
                        {section name=ii loop=$items[user].cats}
                            {if $fsfs}{assign var=fsfs value=0}{else}, {/if}
                                {$items[user].cats[ii].path}
                            {/section}</i>
                    </td>
                </tr>
            {sectionelse}
                {norecords _colspan=6}
            {/section}
        </table>
    </div>

    {if $items}
        <br>
        {tr}Perform action with selected:{/tr}
        <input id="remove_mult" type="submit" class="btn btn-primary btn-sm" name="del" value="{tr}Remove{/tr}">
        <input id="validate_mult" type="submit" class="btn btn-primary btn-sm" name="validate" value="{tr}Validate{/tr}">
    {/if}
</form>
{jq}
    var checkboxes = $('.checkboxes');
    checkboxes.on("change", function(){
        if($('.checkboxes:checked').length > 0) {
            checkboxes.removeAttr('required');
        } else {
            checkboxes.attr('required', 'required');
        }
    });

    $("#remove_mult").on("click", function(){
        if ($('.checkboxes:checked').length > 0) {
            confirmPopup("Are you sure you want to delete the selected items?");
        }
    });

    $("#validate_mult").on("click", function(){
        if ($('.checkboxes:checked').length > 0) {
            confirmPopup("Are you sure you want to validate the selected items?");
        }
    });
{/jq}
{pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
