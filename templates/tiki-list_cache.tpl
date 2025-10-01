{title help="Cache"}{tr}External Pages Cache{/tr}{/title}

{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}The cache is used by:{/tr} <a href="tiki-admin.php?page=textarea" class="alert-link">{tr}Cache external pages{/tr}</a>
{/remarksbox}

{include file='find.tpl'}

<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
    <table class="table">
        <tr>
            <th>
                <a href="tiki-list_cache.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'url_desc'}url_asc{else}url_desc{/if}">{tr}URL{/tr}</a>
            </th>
            <th>
                <a href="tiki-list_cache.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'refresh_desc'}refresh_asc{else}refresh_desc{/if}">{tr}Last updated{/tr}</a>
            </th>
            <th></th>
        </tr>
        {section name=changes loop=$listpages}
            <tr>
                <td class="text">
                    <a class="link" href="{$listpages[changes].url}">{$listpages[changes].url}</a>
                </td>
                <td class="date">
                    {$listpages[changes].refresh|tiki_short_datetime}
                </td>
                <td class="action">
                    {actions}
                        {strip}
                            <action>
                                <a target="_blank" href="tiki-view_cache.php?cacheId={$listpages[changes].cacheId}">
                                    {icon name="view" _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
                                </a>
                            </action>
                            <action>
                                <form action="tiki-list_cache.php" method="post" >
                                    {ticket}
                                    <input type="hidden" name="sort_mode" value={$sort_mode}>
                                    <input type="hidden" name="refresh" value={$listpages[changes].cacheId}>
                                    <button type="submit" name="offset" value={$offset} class="tips btn btn-link btn-sm px-0 pt-0 pb-0" aria-label="{tr}Refresh{/tr}">
                                        {icon name="refresh" _menu_text='y' _menu_icon='y' alt="{tr}Refresh{/tr}"}
                                    </button>
                                </form>
                            </action>
                            <action>
                                <form action="tiki-list_cache.php" method="post">
                                    {ticket}
                                    <input type="hidden" name="offset" value="{$offset}">
                                    <input type="hidden" name="sort_mode" value="{$sort_mode}">
                                    <input type="hidden" name="remove" value="{$listpages[changes].cacheId}">
                                    <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup()" aria-label="{tr}Delete{/tr}">
                                        {icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                    </button>
                                </form>
                            </action>
                        {/strip}
                    {/actions}
                </td>
            </tr>
        {sectionelse}
            {norecords _colspan=3}
        {/section}
    </table>
</div>

{pagination_links count=$pages_count step=$prefs.maxRecords offset=$offset}{/pagination_links}
