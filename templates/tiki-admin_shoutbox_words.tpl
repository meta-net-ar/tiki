{title help="Shoutbox"}{tr}Admin Shoutbox Words{/tr}{/title}

<h2>{tr}Add Banned Word{/tr}</h2>

<div class="t_navbar mb-4">
    <a role="link" href="tiki-shoutbox.php" class="btn btn-link" title="{tr}Shoutbox{/tr}">
        {icon name="comments"} {tr}Shoutbox{/tr}
    </a>
</div>

<form method="post" action="tiki-admin_shoutbox_words.php">
    {ticket}
    <div class="mb-3 row">
        <label class="col-form-label col-md-2" for="word">{tr}Word{/tr}</label>
        <div class="col-md-9">
            <input type="text" name="word" id="word" class="form-control">
        </div>
    </div>
    <div class="text-center mb-5">
        <input type="submit" class="btn btn-primary btn-sm" name="add" value="{tr}Add{/tr}">
    </div>
</form>

{include file='find.tpl'}

<div class="table-responsive">
<table class="table table-striped table-hover">
    <tr>
        <th>
            <a href="tiki-admin_shoutbox_words.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'word_desc'}word_asc{else}word_desc{/if}">{tr}Word{/tr}</a>
        </th>
        <th></th>
    </tr>

    {section name=user loop=$words}
        <tr>
            <td class="text">{$words[user].word|escape}</td>
            <td class="action">
                <form action="tiki-admin_shoutbox_words.php" method="post">
                    {ticket}
                    <input type="hidden" name="offset" value="{$offset}">
                    <input type="hidden" name="sort_mode" value="{$sort_mode}">
                    <input type="hidden" name="remove" value="{$words[user].word}">
                    <button type="submit" class="btn btn-link px-0 pt-0 pb-0 tips" title=":{tr}Delete{/tr}" onclick="confirmPopup('{tr}Are you sure you want to delete this word?{/tr}')" aria-label="{tr}Delete{/tr}">
                        {icon name='remove'}
                    </button>
                </form>
            </td>
        </tr>
    {sectionelse}
        {norecords _colspan=2}
    {/section}
</table>
</div>

{pagination_links count=$pages_count step=$prefs.maxRecords offset=$offset}{/pagination_links}
