{title help="PeerTube" admpage="video"}
    {if $pmode eq 'view'}{tr}View:{/tr} {$videoInfo->name}
    {else}{tr}PeerTube Video{/tr}{/if}
{/title}

<div class="navbar justify-content-end mb-2">
    {if $tiki_p_list_videos eq 'y'}
        {button class="btn btn-info btn-sm" _text="{tr}List Videos{/tr}" href="tiki-list_peertube_entries.php"}
    {/if}
</div>

<div>
    <div class="fgal_file">
        <div class="text-center mb-3">
            {if $videoUrl}
                {wikiplugin _name=peertube url=$videoUrl}{/wikiplugin}
            {else}
                <p class="alert alert-warning">{tr}Video URL is required to display the video{/tr}</p>
            {/if}
        </div>

        {if $videoInfo}
            <div class="card border-0 shadow-sm p-4 position-relative">

                {* Actions menu ⋮ *}
                <div class="dropdown position-absolute end-0 top-0 m-3">
                    <button class="btn btn-light border-0" type="button" id="videoActionMenu" data-bs-toggle="dropdown" aria-expanded="false" title="{tr}More actions{/tr}">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="videoActionMenu">
                        <li>
                            {if $peertube_download_url}
                            <a class="dropdown-item" href="{$peertube_download_url|escape}">
                                <i class="fas fa-download me-2"></i> {tr}Download{/tr}
                            </a>
                            {/if}
                        </li>
                        <li>
                            <a class="dropdown-item" href="tiki-peertube_video.php?videoId={$videoInfo->uuid|escape}&action=edit">
                                <i class="fas fa-cogs me-2"></i> {tr}Manage{/tr}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                            href="tiki-peertube_video.php?videoId={$videoInfo->id}&action=delete"
                            onclick="return confirm('{tr}Are you sure you want to delete this video?{/tr}')">
                                <i class="fas fa-trash me-2"></i> {tr}Delete{/tr}
                            </a>
                        </li>
                    </ul>
                </div>

                {* Title and info *}
                <h2 class="fw-bold mb-3">{$videoInfo->name|escape}</h2>
                <p class="text-muted">
                    {tr}Published:{/tr} {$videoInfo->publishedAt|tiki_short_datetime} • 
                    {tr}Views:{/tr} {$videoInfo->views|default:0}
                </p>

                <hr>

                <table class="table table-borderless w-auto table-striped">
                    <tr>
                        <td class="fw-bold">{tr}Privacy{/tr}</td>
                        <td>{$peertube_privacy_label|default:{tr}Unknown{/tr}|escape}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">{tr}Category{/tr}</td>
                        <td>{$peertube_category_label|default:{tr}Unknown{/tr}|escape}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">{tr}Licence{/tr}</td>
                        <td>{$peertube_licence_label|default:{tr}Unknown{/tr}|escape}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">{tr}Language{/tr}</td>
                        <td>{$peertube_language_label|default:{tr}Unknown{/tr}|escape}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">{tr}Tags{/tr}</td>
                        <td>
                            {if $videoInfo->tags|@count}
                                {foreach from=$videoInfo->tags item=t name=tags}
                                    {$t|escape}{if not $smarty.foreach.tags.last}, {/if}
                                {/foreach}
                            {else}
                                <em>{tr}None{/tr}</em>
                            {/if}
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-bold">{tr}Duration{/tr}</td>
                        <td>
                            {assign var=seconds value=$videoInfo->duration}
                            {math equation="floor(x/3600)" x=$seconds assign=hours}
                            {math equation="floor((x%3600)/60)" x=$seconds assign=minutes}
                            {math equation="x%60" x=$seconds assign=remainingSeconds}
                            
                            {if $hours > 0}{$hours}h {/if}
                            {if $minutes > 0 || $hours > 0}{$minutes|string_format:"%02d"}min{/if}
                            {if $hours == 0 && $minutes == 0}{$remainingSeconds}s{/if}
                        </td>
                    </tr>
                </table>

                <hr class="my-4">

                <div class="mb-3">
                    <label class="form-label fw-bold">{tr}Embed code:{/tr}</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-light" readonly value='{ldelim}peertube url="{$videoUrl}"{rdelim}' id="embedCodeInput">
                        <button class="btn btn-outline-secondary" type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('embedCodeInput').value)">
                            <i class="fas fa-copy"></i> {tr}Copy{/tr}
                        </button>
                    </div>
                </div>

                {if $videoUrl}
                    <a href="{$videoUrl|escape}" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt"></i> {tr}View on PeerTube{/tr}
                    </a>
                {/if}
            </div>
        {/if}
    </div>
</div>
