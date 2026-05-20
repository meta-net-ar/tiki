{title admpage="wiki"}{tr}Wiki Recycle Bin{/tr}{/title}

<div class="t_navbar mb-4 clearfix">
    {button _icon_name='file-alt' _text="{tr}Pages{/tr}" _type="link" _class='btn btn-link' _script='tiki-listpages.php' _title="{tr}List wiki pages{/tr}"}
</div>

<div class="clearfix mb-3">
    <form method="get" action="tiki-wiki_recycle_bin.php" class="d-flex gap-2">
        <input class="form-control" type="text" name="find" value="{$find|escape}" placeholder="{tr}Search by page name{/tr}">
        <button class="btn btn-sm btn-secondary" type="submit">{tr}Search{/tr}</button>
        {if $find}
            <a class="btn btn-outline-secondary" href="tiki-wiki_recycle_bin.php">{tr}Clear{/tr}</a>
        {/if}
    </form>
</div>

{if $recycle_pages}
    <form id="recycle_bin_form" method="post" action="tiki-wiki_recycle_bin.php">
        <table class="table normal table-striped table-hover">
            <thead>
                <tr>
                    <th id="checkbox">
                        {select_all checkbox_names='checked[]'}
                    </th>
                    <th id="pagename">
                        {self_link _sort_arg='sort_mode' _sort_field='pageName'}{tr}Page{/tr}{/self_link}
                    </th>
                    <th id="deletedat">
                        {self_link _sort_arg='sort_mode' _sort_field='deletedAt'}{tr}Deleted on{/tr}{/self_link}
                    </th>
                    <th id="deletedby">
                        {self_link _sort_arg='sort_mode' _sort_field='deletedBy'}{tr}Deleted by{/tr}{/self_link}
                    </th>
                    <th id="comment">{tr}Comment{/tr}</th>
                    <th id="actions">{tr}Actions{/tr}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$recycle_pages item=p}
                    <tr>
                        <td>
                            <input type="checkbox" name="checked[]" value="{$p.pageName|escape}">
                        </td>
                        <td>{$p.pageName|escape}</td>
                        <td>{$p.deletedAt|tiki_short_datetime}</td>
                        <td>{$p.deletedBy|escape}</td>
                        <td>{$p.comment|escape}</td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary"
                               href="{bootstrap_modal controller=wiki action=undelete_pages checked=$p.pageName}">
                                {icon name='undo'} {tr}Restore{/tr}
                            </a>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>

        <div class="d-flex gap-2 mb-3">
            <button
                type="submit"
                form="recycle_bin_form"
                formaction="{bootstrap_modal controller=wiki action=undelete_pages}"
                class="btn btn-primary"
                onclick="confirmPopup()"
            >
                {icon name='undo'} {tr}Restore selected{/tr}
            </button>
        </div>
    </form>

    {pagination_links cant=$total step=$maxRecords offset=$offset}{/pagination_links}
{else}
    {remarksbox type="info" title="{tr}Empty{/tr}"}
        {tr}The wiki recycle bin is empty. Deleted pages will appear here and can be restored.{/tr}
    {/remarksbox}
{/if}
