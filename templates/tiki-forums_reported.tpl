{title help="Forums" admpage="forums"}{$forum_info.name}{/title}
<h4>
    {tr}Reported messages{/tr}
    <span class="badge bg-secondary">{$count}</span>
    {icon name="refresh" href="tiki-forums_reported.php?forumId=$forumId" class="btn btn-link tips" title=":{tr}Refresh list{/tr}"}
</h4>

{* FILTERING FORM *}
{if $items or ($find ne '')}
    <form action="tiki-forums_reported.php" method="post" class="form">
        <div class="mb-3 row">
            <input type="hidden" name="forumId" value="{$forumId|escape}">
            <input type="hidden" name="offset" value="{$offset|escape}">
            <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
            <div class="input-group">
                <input type="text" class="form-control" name="find" value="{$find|escape}" placeholder="{tr}Find{/tr}...">
                <button type="submit" class="btn btn-primary" name="filter">{tr}Filter{/tr}</button>
            </div>
        </div>
    </form>
{/if}
{*END OF FILTERING FORM *}

{*LISTING*}
<form action="tiki-forums_reported.php" method="post">
    {ticket}
    <input type="hidden" name="forumId" value="{$forumId|escape}">
    <input type="hidden" name="offset" value="{$offset|escape}">
    <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
    <input type="hidden" name="find" value="{$find|escape}">
    <table class="table table-hover" id="forums-reported-table">
        <thead>
        <tr>
            {if $items}
                <th></th>
            {/if}
            <th>{tr}Message{/tr}</th>
            <th>{tr}Reported by{/tr}</th>
        </tr>
        </thead>

        <tbody id="forums-reported-body">
        {section name=ix loop=$items}
            <tr>
                <td class="checkbox-cell">
                    <input type="checkbox" name="msg[{$items[ix].threadId}]" class="form-check-input" aria-label="{tr}Select{/tr}">
                </td>
                <td class="text">
                    {object_link id=$items[ix].threadId type=forumpost} (thread {$items[ix].threadId})
                </td>
                <td style="text-align:left;">
                    {$items[ix].user|username}
                </td>
            </tr>
        {sectionelse}
            {norecords _colspan=2}
        {/section}
        </tbody>
    </table>
    {if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
        <el-infinite-scroll container-id="forums-reported-table" target-container-id="forums-reported-body" offset="{$offset}" step="{$prefs.maxRecords}" total-count="{$pages_count}"></el-infinite-scroll>
    {/if}
    {if $items}
        {tr}Perfom action with checked:{/tr} <input type="submit" class="btn btn-warning btn-sm" name="del" value=" {tr}Un-report{/tr} ">
    {/if}

</form>
{* END OF LISTING *}

{if $prefs.feature_elementplus neq 'y' || $prefs.elementplus_infinite_scroll neq 'y'}
    {pagination_links count=$pages_count step=$prefs.maxRecords offset=$offset}{/pagination_links}
{/if}
