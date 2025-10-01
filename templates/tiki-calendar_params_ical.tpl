{strip}
{title}{tr}Advanced parameters for iCal export{/tr}{/title}

{if $tiki_p_view_calendar eq 'y'}
    <div class="t_navbar">
        {button href="tiki-calendar.php" _text="{tr}Calendar{/tr}"}
    </div>
{/if}

<div class="wikitext">

    <form action="tiki-calendar_export_ical.php" method="post" name="f" id="editcalitem">
        <input type="hidden" name="export" value="y">
        <table class="formcolor">
            <tr><td colspan="2">{tr}Calendars to be exported{/tr}</td></tr>
            <tr>
                <td>{tr}Calendars{/tr}</td>
                <td>
                    {foreach item=k from=$listcals}
                        <input type="checkbox" name="calendarIds[]" value="{$k.calendarId|escape}" id="groupcal_{$k.calendarId}" {if $k.calendarId}checked="checked"{/if}>
                        <label for="groupcal_{$k.calendarId}" class="calId{$k.calendarId}">{$k.displayName|escape}</label><br>
                    {/foreach}
                </td>
            </tr>
            <tr>
                <td>{tr}Start{/tr}</td>
                <td>
                    <input type="hidden" name="tstart">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="border:0;padding-top:2px;vertical-align:middle">
                                {if $prefs.feature_jscalendar neq 'y'}
                                    <a href="#" onclick="document.f.tstart.selectedIndex=(document.f.tstart.selectedIndex+1);" role="button">{icon name='add' align='left'}</a>
                                {/if}
                            </td>
                            <td style="border:0;padding-top:2px;vertical-align:middle">

                                {if $prefs.feature_jscalendar eq 'y'}
                                    {jscalendar date=$startTime fieldname="tstart" showtime='n'}
                                {else}
                                    {html_select_date prefix="start_date_" time=$startTime field_order=$prefs.display_field_order start_year=$prefs.calendar_start_year end_year=$prefs.calendar_end_year}
                                    {jscalendar date=$stopTime fieldname="tstop" showtime='n'}
                                {/if}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>{tr}End{/tr}</td>
                <td>
                    <table cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="border:0;padding-top:2px;vertical-align:middle">
                            {if $prefs.feature_jscalendar neq 'y'}
                                    <a href="#" onclick="document.f.tstop.selectedIndex=(document.f.tstop.selectedIndex+1);" role="button">{icon name='add' align='left'}</a>
                            {/if}
                            </td>
                            <td style="border:0;padding-top:2px;vertical-align:middle">
                            {if $prefs.feature_jscalendar eq 'y'}
                            {jscalendar date=$stopTime fieldname="tstop" showtime='n'}
                            {else}
                            {html_select_date prefix="stop_date_" time=$stopTime field_order=$prefs.display_field_order start_year=$prefs.calendar_start_year end_year=$prefs.calendar_end_year}
                            {/if}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td><input type="submit" class="btn btn-primary btn-sm" name="ical" value="{tr}Export calendars iCal{/tr}"></td>
                <td><input type="submit" class="btn btn-primary btn-sm" name="csv" value="{tr}Export calendars CSV{/tr}"></td>
            </tr>
        </table>
    </form>

</div>
{/strip}
{if $prefs.feature_jscalendar eq 'y'}
    {js_insert_icon type="jscalendar"}
{/if}
