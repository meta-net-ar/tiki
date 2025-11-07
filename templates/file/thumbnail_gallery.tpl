{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <div class="row" id="thumbnail-gallery">
        {foreach $results as $key => $row}
            {if $key % 3 == 0 && $key > 0}
                </div>
                <div class="row">
            {/if}
            <div class="col-md-4 text-center">
                <div class="card">
                    <div class="card-header">
                        {$row.title|escape}
                    </div>
                    <div class="card-body">
                        <p>
                            <a href="{$row.object_id|sefurl:'file'}" data-type="file" data-object="{$row.object_id|escape}"><img src="{$row.object_id|sefurl:'thumbnail'}"/><span class="sr-only">{$row.title|escape}</span></a>
                        </p>
                        <p class="small">
                            {$row.modification_date|tiki_short_datetime}
                        </p>
                    </div>
                </div>
            </div>
        {foreachelse}
            <div class="col-md-12">
                {tr}No files found.{/tr}
            </div>
        {/foreach}
    </div>
    {if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
        <el-infinite-scroll container-id="thumbnail-gallery" offset="{$results.offset}" step="{$results.maxRecords}" total-count="{$results.count}" load-url="{service controller=file action=thumbnail_gallery galleryId=$galleryId plain=1 search=$search type=$typeFilter}"></el-infinite-scroll>
    {else}
        {pagination_links resultset=$results}{service controller=file action=thumbnail_gallery galleryId=$galleryId plain=1 search=$search type=$typeFilter}{/pagination_links}
    {/if}
{/block}
