{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <ul class="media-list list-unstyled ps-0 mb-0" id="list-gallery">
        {foreach from=$results item=row}
            <li class="d-flex align-items-start border-bottom py-2 px-2">
                <div class="form-check mt-2 me-2">
                    <input class="form-check-input file-select" type="checkbox" name="file[]" value="{$row.object_id|escape}" id="file-{$row.object_id}">
                </div>

                <div class="flex-shrink-0">
                    {if $row.filetype|truncate:6:'' eq 'image/'}
                        <label for="file-{$row.object_id}">
                            <img src="{$row.object_id|sefurl:'thumbnail'}" class="img-thumbnail" style="width: 64px; height: auto;" />
                        </label>
                    {else}
                        {$row.filename|iconify:$row.filetype}
                    {/if}
                </div>

                <div class="flex-grow-1 ms-3 file-click-toggle" data-target="file-{$row.object_id}">
                    <h6 class="media-heading mb-1">{object_link type=$row.object_type id=$row.object_id}</h6>
                    <div class="mb-1">{$row.filename|escape}</div>
                    <div class="mb-1 text-muted small">{$row.description|escape}</div>
                    <div class="small text-muted">
                        {tr _0=$row.modification_date|tiki_short_datetime}Last modification: %0{/tr}
                    </div>
                </div>

            </li>
        {foreachelse}
            <li>
                {tr}No files found.{/tr}
            </li>
        {/foreach}
    </ul>
    {if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
        <el-infinite-scroll container-id="list-gallery" offset="{$results.offset}" step="{$results.maxRecords}" total-count="{$results.count}" load-url="{service controller=file action=list_gallery galleryId=$galleryId plain=1 search=$search type=$typeFilter}"></el-infinite-scroll>
    {else}
        {pagination_links resultset=$results}{service controller=file action=list_gallery galleryId=$galleryId plain=1 search=$search type=$typeFilter}{/pagination_links}
    {/if}
{/block}
