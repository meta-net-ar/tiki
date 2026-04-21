{* navbar buttons at the top of the tracker pages *}
<div class="btn-group">

{if $tiki_p_admin_trackers eq 'y' and !empty($trackerId)}
    <a class="btn btn-info tracker-properties tips" title=":{tr _0="{$tracker_info.name|escape}"}Edit the configuration properties of tracker “%0”{/tr}" href="{bootstrap_modal controller=tracker action=replace trackerId=$trackerId}">
        {icon name="settings"} {tr}Properties{/tr}
    </a>
    <a class="btn btn-info tips" title=":{tr _0="{$tracker_info.name|escape}"}Configure the data fields for tracker “%0”{/tr}" href="{$trackerId|sefurl:'trackerfields'}">
        {icon name="th-list"} {tr}Fields{/tr}
    </a>
{/if}

</div>
<div class="btn-group">
{if $tiki_p_list_trackers eq 'y'}
    <a class="btn btn-info tips" title=":{tr}List all trackers (you have the permission to see){/tr}" href="{if $prefs.feature_sefurl eq 'y'}trackers{else}tiki-list_trackers.php{/if}">
        {icon name="trackers"} {tr}Trackers{/tr}
    </a>
{/if}

{if !empty($trackerId) and $tiki_p_view_trackers eq 'y' && (empty($showitems) || $showitems !== 'n')}
    <a class="btn btn-info tips" title=":{tr _0="{$tracker_info.name|escape}"}Go back to the list of items for “%0”{/tr}" href="{$trackerId|sefurl:"tracker"}">
        {icon name="list"} {tr}Items{/tr}
    </a>
{/if}

</div>
