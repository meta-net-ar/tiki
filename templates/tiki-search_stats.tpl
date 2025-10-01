{title help="SearchStats"}{tr}Search statistics{/tr}{/title}

<div class="t_navbar">
    <form action="tiki-search_stats.php" method="post" >
        {ticket}
        <button type="submit" name="clear" value=1 class=" btn btn-primary">
            {tr}Clear Stats{/tr}
        </button>
    </form>
</div>

{include file='find.tpl'}

<div class="table-responsive">
    <table class="table">
        <tr>
            {* term *}
            <th><a href="tiki-search_stats.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'term_desc'}term_asc{else}term_desc{/if}">{tr}Word{/tr}</a></th>

            {* searched *}
            <th>
            <a href="tiki-search_stats.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'hits_desc'}hits_asc{else}hits_desc{/if}">{tr}Searched{/tr}</a></th>

            {* How can we increase the number of items displayed on a page? *}

        </tr>

        {section name=user loop=$channels}
            <tr>
                <td class="text">{$channels[user].term}</td>
                <td class="integer">{$channels[user].hits}</td>
            </tr>
        {/section}
    </table>
</div>

{pagination_links count=$pages_count step=$prefs.maxRecords offset=$offset}{/pagination_links}
