{if $invalideid}
    <div class="card bg-body-tertiary"><div class="card-body">{tr}The calendarid parameter is invalid or contains an id that does not exist.{/tr}</div></div>
{else}
    <div class="table-responsive">
    <table class="table table-condensed table-bordered" id="events-table">
    <thead>
    <tr><th colspan="2">{tr}Upcoming Events{/tr}</th></tr>
    </thead>

    <tbody id="events-body">
    {foreach from=$events item=event}
        <tr class="{cycle advance=false}">
            <td>
                {if $datetime eq 1}
                    {capture name="start"}{$event.start|tiki_short_datetime}{/capture}
                    {capture name="end"}{$event.end|tiki_short_datetime}{/capture}
                    {$smarty.capture.start}{if $smarty.capture.start ne $smarty.capture.end}<br>{$smarty.capture.end}{/if}
                {else}
                    {capture name="start"}{$event.start|tiki_short_date}{/capture}
                    {capture name="end"}{$event.end|tiki_short_date}{/capture}
                    {$smarty.capture.start}{if $smarty.capture.start ne $smarty.capture.end}<br>{$smarty.capture.end}{/if}
                {/if}
            </td>
            <td style="white-space:normal;">
                <a class="linkmodule" href="tiki-ajax_services.php?controller=calendar&action=view_item&calitemId={$event.calitemId}">{$event.name|escape}</a>
                {if $desc}<br>{$event.parsed}{/if}
            </td>
            {if $attendees eq 'y'}
                <td>
                    {foreach $listAttendees as $attendee}
                        {if $attendee.calitemId eq $event.calitemId}
                            <li>
                                {$attendee.username}<br>
                            </li>
                        {/if}
                    {/foreach}
                </td>
            {/if}
        </tr>{* {cycle} *}
    {/foreach}
    </tbody>
    </table>
    {if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
        <el-infinite-scroll container-id="events-table" target-container-id="events-body" offset="{$offset}" step="{$maxEvents}" total-count="{$count}"></el-infinite-scroll>
    {/if}
    </div>
    {*Pagination *}
    {if !empty($events) && $usePagination ne 'n' && ($prefs.feature_elementplus neq 'y' || $prefs.elementplus_infinite_scroll neq 'y')}
        {pagination_links count=$count step=$maxEvents offset=$offset}{/pagination_links}
    {/if}
{/if}
