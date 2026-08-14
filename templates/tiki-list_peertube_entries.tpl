{* Title and help link for the page *}
{title help="PeerTube" admpage="video"}{tr}List Videos{/tr}{/title}

{if isset($errors) and $errors|@count}
  <div class="alert alert-danger">
    <h2>{tr}Errors detected{/tr}</h2>
    {section name=ix loop=$errors}
      {$errors[ix]}<br>
    {/section}
  </div>
{/if}

<div class="row mb-3 row">
    <form method="get" class="col-md-12 d-flex flex-row flex-wrap align-items-center">
        <label class="col-form-label col-sm-2" for="find">{tr}Search{/tr}</label>
        <div class="input-group col-sm-8">
            <input type="text" name="find" class="form-control" id="find" value="{$find|escape}" placeholder="Search videos...">
            <input type="hidden" name="list" value="videos">
        </div>
        <div class="col-sm-2">
            <input type="submit" class="btn btn-info btn-sm" name="search" value="{tr}Search{/tr}">
        </div>
    </form>
</div>

<div class="navbar justify-content-end mb-2">
    {if $tiki_p_upload_videos eq 'y'}
        {button class="btn btn-info btn-sm" _text="{tr}Upload Video{/tr}" href="tiki-peertube_upload.php"}
    {/if}
</div>

{if $count > 0}
    {if $paginationBar}
        <div class="pagination">{$paginationBar}</div>
    {/if}

    <div class="{if $js}table-responsive{/if}"> 
        <table class="table table-striped">
            <thead>
                <tr>
                    <th></th>
                    <th>
                        <a href="tiki-list_peertube_entries.php?offset={$offset}&sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}Title{/tr}</a>
                    </th>
                    <th>
                        <a href="tiki-list_peertube_entries.php?offset={$offset}&sort_mode={if $sort_mode eq 'privacy_desc'}privacy_asc{else}privacy_desc{/if}">
                            {tr}Privacy{/tr}
                        </a>
                    </th>
                    <th>
                        <a href="tiki-list_peertube_entries.php?offset={$offset}&sort_mode={if $sort_mode eq 'created_desc'}created_asc{else}created_desc{/if}">{tr}Published{/tr}</a>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {section name=ix loop=$klist}
                    {capture assign=pt_content}
                        <div class="text-start" style="max-width:380px">
                        <div><strong>{tr}Title:{/tr}</strong> {$klist[ix]->name|escape}</div>
                        <div>
                            <strong>{tr}Duration:{/tr}</strong>
                            {assign var=seconds value=$klist[ix]->duration|default:0}
                            {math equation="floor(x/3600)" x=$seconds assign=hours}
                            {math equation="floor((x%3600)/60)" x=$seconds assign=minutes}
                            {math equation="x%60" x=$seconds assign=remainingSeconds}
                            {if $hours > 0}{$hours}h {/if}
                            {if $minutes > 0 || $hours > 0}{$minutes|string_format:"%02d"}min {/if}
                            {if $hours == 0 && $minutes == 0}{$remainingSeconds}s{/if}
                        </div>
                        {if !empty($klist[ix]->views)}
                            <div><strong>{tr}Views:{/tr}</strong> {$klist[ix]->views|escape}</div>
                        {/if}
                        <div><strong>{tr}Video ID:{/tr}</strong> <code>{$klist[ix]->uuid|escape}</code></div>
                        {if !empty($klist[ix]->description)}
                            <div class="mt-1"><strong>{tr}Description:{/tr}</strong> {$klist[ix]->description|escape}</div>
                        {/if}
                        </div>
                    {/capture}

                    <tr>
                        <td class="align-middle">
                            <a class="pt-popover"
                                href="tiki-peertube_video.php?videoId={$klist[ix]->shortUUID|escape}"
                                title="{$klist[ix]->name|escape}"
                                data-pt-content="{$pt_content|escape:'html'}">
                                {if $klist[ix]->thumbUrl}
                                <img src="{$klist[ix]->thumbUrl|escape}"
                                    alt="{$klist[ix]->name|escape}"
                                    width="120" height="80"
                                    class="img-thumbnail">
                                {else}
                                    <span class="text-muted">—</span>
                                {/if}
                            </a>
                        </td>

                        <td class="align-middle">
                            <a class="pt-popover text-decoration-none"
                                href="tiki-peertube_video.php?videoId={$klist[ix]->shortUUID|escape}"
                                title="{$klist[ix]->name|escape}"
                                data-pt-content="{$pt_content|escape:'html'}">
                                {$klist[ix]->name|escape}
                            </a>
                        </td>

                        <td class="align-middle">
                            {if $klist[ix]->privacyLabel}
                                {assign var=_pl value=$klist[ix]->privacyLabel}
                                <span class="badge
                                {if $_pl eq 'Public'}bg-success
                                {elseif $_pl eq 'Unlisted'}bg-secondary
                                {elseif $_pl eq 'Private'}bg-danger
                                {else}bg-warning{/if}">
                                {$klist[ix]->privacyLabel|escape}
                                </span>
                            {else}
                                {tr}Unknown{/tr}
                            {/if}
                        </td>

                        <td class="align-middle">{$klist[ix]->publishedAt|tiki_long_date}</td>
                        <td class="action">
                            {actions}
                                {strip}
                                <action>
                                    <a href="tiki-peertube_video.php?videoId={$klist[ix]->shortUUID|escape}">
                                    {icon name='view' _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
                                    </a>
                                </action>
                                {if $tiki_p_download_videos eq 'y'}
                                    <action>
                                    <a href="tiki-peertube_video.php?videoId={$klist[ix]->shortUUID|escape}#download">
                                        {icon name='download' _menu_text='y' _menu_icon='y' alt="{tr}Download{/tr}"}
                                    </a>
                                    </action>
                                {/if}
                                {if $tiki_p_edit_videos eq 'y'}
                                    <action>
                                    <a href="tiki-peertube_upload.php?videoId={$klist[ix]->uuid|escape}">
                                        {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                    </a>
                                    </action>
                                {/if}
                                {if $tiki_p_delete_videos eq 'y'}
                                    <action>
                                    <form action="tiki-list_peertube_entries.php" method="post" class="m-0">
                                        {ticket}
                                        <input type="hidden" name="action" value="Delete">
                                        <input type="hidden" name="videoId[]" value="{$klist[ix]->uuid|escape}">
                                        <button type="submit" class="btn btn-link px-0 pt-0 pb-0"
                                                onclick="confirmPopup('{tr}Are you sure you want to delete this video?{/tr}')"
                                                aria-label="{tr}Remove{/tr}">
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                        </button>
                                    </form>
                                    </action>
                                {/if}
                                {/strip}
                            {/actions}
                        </td>
                    </tr>
                {/section}
            </tbody>
        </table>
    </div>

    {if $paginationBar}
        <div class="pagination">{$paginationBar}</div>
    {/if}
{else}
    <p>{tr}No videos found.{/tr}</p>
{/if}

{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}

{jq}
    $('.pt-popover').each(function () {
        var $el = $(this);
        var content = $el.attr('data-pt-content');
        $el.popover({
        html: true,
        trigger: 'hover focus',
        placement: 'auto',
        container: 'body',
        content: content,
        sanitize: false
        });
    });
{/jq}
