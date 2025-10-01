{if $klist && $klist|@count > 0}
    <div class="text-center">
        {wikiplugin _name=kaltura id=$klist[0]->id}{/wikiplugin}
        <div class="navi kaltura">
            <a class="prev"></a>
            <div class="scrollable">
                <div class="items">
                    {foreach from=$klist key=key item=item}
                        <a href="#" onclick="loadMedia('{$item->id}'); return false" role="button"><img class="athumb" src="{$item->thumbnailUrl}" alt="{$item->description}" height="80" width="120"></a>
                    {/foreach}
                </div>
            </div>
            <a class="next"></a>
        </div>
    </div>
{else}
    <p>{tr}No media available{/tr}</p>
{/if}

{jq notonready=true}
    function loadMedia(entryId) {
        $('#kaltura_player1')[0].sendNotification("changeMedia", {entryId:entryId});
    }
{/jq}

{if $tiki_p_list_videos eq 'y'}
    {if $entryType eq "mix"}
        {button _text="{tr}List Media{/tr}" href="tiki-list_kaltura_entries.php" _class="btn-info"}
        {if $prefs.kaltura_legacyremix == 'y'}{button _text="{tr}List Remix Entries{/tr}" href="tiki-list_kaltura_entries.php?list=mix" _class="btn-info"}{/if}
    {else}
        {if $prefs.kaltura_legacyremix == 'y'}{button _text="{tr}List Remix Entries{/tr}" href="tiki-list_kaltura_entries.php?list=mix" _class="btn-info"}{/if}
        {button _text="{tr}List Media{/tr}" href="tiki-list_kaltura_entries.php" _class="btn-info"}
    {/if}
{/if}
