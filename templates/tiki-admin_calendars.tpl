{if !empty($calendarId)}
    {title help=Calendar url="tiki-admin_calendars.php?calendarId=$calendarId" admpage="calendar"}{tr}Admin Calendars{/tr}{/title}
{else}
    {title help=Calendar url="tiki-admin_calendars.php" admpage="calendar"}{tr}Admin Calendars{/tr}{/title}
{/if}

<div class="t_navbar mb-4">
    {if !empty($calendarId) && $tiki_p_admin_calendar eq 'y'}
        <a role="link" href="tiki-admin_calendars.php?cookietab=2" class="btn btn-link">
            {icon name="create"} {tr}Create Calendar{/tr}
        </a>
    {/if}
    <a role="link" href="tiki-calendar.php" class="btn btn-link">
        {icon name="view"} {tr}View Calendars{/tr}
    </a>
    {if $tiki_p_admin_calendar eq 'y'}
        <a role="link" href="tiki-calendar_import.php" class="btn btn-link">
            {icon name="import"} {tr}Import{/tr}
        </a>
    {/if}
</div>

{tabset name='tabs_admin_calendars'}
    {tab name="{tr}Calendars{/tr}"}
        <h2>{tr}Calendars{/tr}</h2>

        {include autocomplete="calendarname" file='find.tpl' find_in="<ul><li>{tr}Calendar name{/tr}</li></ul>"|strip_tags}
        <div {if $js}class="table-responsive"{/if}>
        <table class="table table-striped table-hover">
            <tr>
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'calendarId_desc'}calendarId_asc{else}calendarId_desc{/if}">
                        {tr}ID{/tr}
                    </a>
                </th>
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">
                        {tr}Name{/tr}
                    </a>
                </th>
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'customlocations_desc'}customlocations_asc{else}customlocations_desc{/if}">
                        {tr}Location{/tr}
                    </a>
                </th>
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'customparticipants_desc'}customparticipants_asc{else}customparticipants_desc{/if}">
                        {tr}Participants{/tr}
                    </a>
                </th>
{* Table is too wide, causing problems with the action popup, so eliminating some columns
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'customcategories_desc'}customcategories_asc{else}customcategories_desc{/if}">
                        {tr}Classification{/tr}
                    </a>
                </th>
*}
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'customlanguages_desc'}customlanguages_asc{else}customlanguages_desc{/if}">
                        {tr}Language{/tr}
                    </a>
                </th>
{*
                <th>{tr}URL{/tr}</th>
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'custompriorities_desc'}custompriorities_asc{else}custompriorities_desc{/if}">
                        {tr}Priority{/tr}
                    </a>
                </th>
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'customsubscription_desc'}customsubscription_asc{else}customsubscription_desc{/if}">
                        {tr}Subscription{/tr}
                    </a>
                </th>
*}
                {if $tiki_p_admin_calendar eq 'y'}
                    <th>
                        <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'personal_desc'}personal_asc{else}personal_desc{/if}">
                            {tr}Personal{/tr}
                        </a>
                    </th>
                    <th>
                        <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'private_desc'}private_asc{else}private_desc{/if}">
                            {tr}Private{/tr}
                        </a>
                    </th>
                {/if}
                <th></th>
            </tr>

            {foreach key=id item=cal from=$calendars}
                <tr>
                    <td class="id">{$id}</td>
                    <td class="text">
            <a class="tablename" href="tiki-admin_calendars.php?calendarId={$id}&cookietab=2" title="{tr}Edit{/tr}">{$cal.displayName|escape}</a>
                        {if $cal.show_calname eq 'y'} {icon name="list-alt" class="tips" title="{tr}Name:{/tr}{tr}Field will show in popup{/tr}"}{/if}
                    </td>
                    <td class="text">
                        {$cal.customlocations|yesno}{if $cal.show_location eq 'y'}{icon name="list-alt" class="tips" title="{tr}Custom location:{/tr}{tr}Field will show in popup{/tr}"}{/if}
                    </td>
                    <td class="text">
                        {$cal.customparticipants|yesno}{if $cal.show_participants eq 'y'}{icon name="list-alt" class="tips" title="{tr}Custom participants:{/tr}{tr}Field will show in popup{/tr}"}{/if}
                    </td>
                    <td class="text">
                        {$cal.customlanguages|yesno}{if $cal.show_language eq 'y'}{icon name="list-alt" class="tips" title="{tr}Custom languages:{/tr}{tr}Field will show in popup{/tr}"}{/if}
                    </td>
                    {if $tiki_p_admin_calendar eq 'y'}
                        <td class="text">{$cal.personal|yesno}</td>
                        <td class="text">{$cal.private|yesno}</td>
                    {/if}
                    <td class="action">
                        {actions}
                            {strip}
                                <action>
                                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;calendarId={$id}&cookietab=2">
                                        {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="tiki-calendar.php?calIds[]={$id}">
                                        {icon name='view' _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="{bootstrap_modal controller='calendar' action='edit_item' size='modal-lg' calendarId=$id}">
                                        {icon name='create' _menu_text='y' _menu_icon='y' alt="{tr}Add event{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="tiki-caldav.php/calendars/{$cal.user}/calendar-{$id}">
                                        {icon name='sync' _menu_text='y' _menu_icon='y' alt="{tr}Sync via CalDAV{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="tiki-caldav.php/calendars/{$cal.user}/calendar-{$id}?export">
                                        {icon name='export' _menu_text='y' _menu_icon='y' alt="{tr}Export as .ics{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    {permission_link mode=text type=calendar id=$id title=$cal.name}
                                </action>
                                <action>
                                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;drop={$id}&amp;calendarId={$id}"
                                        onclick="confirmPopup('{tr}Delete calendar?{/tr}', '{ticket mode=get}')"
                                    >
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                    </a>
                                </action>
                            {/strip}
                        {/actions}
                    </td>
                </tr>
            {foreachelse}
                {norecords _colspan=12}
            {/foreach}
        </table>
        </div>
        {pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}

        <h2>{tr}Calendar Subscriptions{/tr}</h2>

        <div {if $js}class="table-responsive"{/if}>
        <table class="table table-striped table-hover">
            <tr>
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'subscriptionId_desc'}subscriptionId_asc{else}subscriptionId_desc{/if}">
                        {tr}ID{/tr}
                    </a>
                </th>
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">
                        {tr}Name{/tr}
                    </a>
                </th>
                <th>
                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'source_desc'}source_asc{else}source_desc{/if}">
                        {tr}Source URL{/tr}
                    </a>
                </th>
                <th></th>
            </tr>

            {foreach key=id item=sub from=$subscriptions.data}
                <tr>
                    <td class="id">{$sub.subscriptionId}</td>
                    <td class="text">
                        <a class="tablename" href="tiki-admin_calendars.php?subscriptionId={$sub.subscriptionId}&cookietab=3" title="{tr}Edit{/tr}">{$sub.name|escape}</a>
                    </td>
                    <td class="text">
                        <a href="{$sub.source}">{$sub.source}</a>
                    </td>
                    <td class="action">
                        {actions}
                            {strip}
                                <action>
                                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;subscriptionId={$sub.subscriptionId}&cookietab=3">
                                        {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="tiki-calendar.php?subIds[]={$sub.subscriptionId}">
                                        {icon name='view' _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;sync_subscription={$sub.subscriptionId}">
                                        {icon name='sync' _menu_text='y' _menu_icon='y' alt="{tr}Synchronize now{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="tiki-caldav.php/calendars/{$sub.user}/{$sub.uri}">
                                        {icon name='sync' _menu_text='y' _menu_icon='y' alt="{tr}Sync via CalDAV{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="tiki-caldav.php/calendars/{$sub.user}/{$sub.uri}?export">
                                        {icon name='export' _menu_text='y' _menu_icon='y' alt="{tr}Export as .ics{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="tiki-admin_calendars.php?offset={$offset}&amp;sort_mode={$sort_mode}&amp;remove_subscription={$sub.subscriptionId}"
                                        onclick="confirmPopup('{tr}Delete calendar subscription?{/tr}', '{ticket mode=get}')"
                                    >
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                    </a>
                                </action>
                            {/strip}
                        {/actions}
                    </td>
                </tr>
            {foreachelse}
                {norecords _colspan=4}
            {/foreach}
        </table>
        </div>
        {pagination_links cant=$subscriptions.count step=$maxRecords offset=$offset}{/pagination_links}
    {/tab}

    {if $calendarId gt 0}
        {assign var="edtab" value="{tr}Edit Calendar{/tr}"}
    {else}
        {assign var="edtab" value="{tr}Create Calendar{/tr}"}
    {/if}
    {tab name=$edtab}
        <h2>{$edtab}</h2>

        <form action="tiki-admin_calendars.php" method="post">
            {ticket}
            <fieldset>
            <input type="hidden" name="calendarId" value="{$calendarId|escape}">
            {if $tiki_p_modify_object_categories eq 'y'}
                {include file='categorize.tpl'}
            {/if}
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="calendarName">
                    {tr}Name{/tr}
                </label>
                <div class="col-sm-5">
                    <input type="text" class="form-control" name="name" id="calendarName" value="{$name|escape}">
                </div>
                <div class="checkbox col-sm-3">
                    <input type="checkbox" name="show[calname]" id="showCalnamePopup" class="form-check-input" value="on"{if $show_calname eq 'y'} checked="checked"{/if}>
                    <label for="showCalnamePopup">
                        {tr}Show in popup box{/tr}
                    </label>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="calendarDescription">
                    {tr}Description{/tr}
                </label>
                <div class="col-sm-5">
                    <textarea name="description" rows="5" wrap="virtual" class="form-control" id="calendarDescription">{$description|escape}</textarea>
                </div>
                <div class="checkbox col-sm-3">
                    <input type="checkbox" id="showCalDescriptionPopup" class="form-check-input" name="show[description]" value="on"{if $show_description eq 'y'} checked="checked"{/if}>
                    <label for="showCalDescriptionPopup" class="form-check-label">
                        {tr}Show in popup box{/tr}
                    </label>
                </div>
            </div>
            <div class="mb-3 row align-items-center">
                <label class="col-sm-4 col-form-label" for="customlocations">
                    {tr}Custom location{/tr}
                </label>
                <div class="col-sm-2">
                    <select name="customlocations" id="customlocations" class="form-select">
                        <option value='y' {if $customlocations eq 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                        <option value='n' {if $customlocations eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                    </select>
                </div>
                <div class="checkbox col-sm-3">
                    <input type="checkbox" name="show[location]" id="showCustomLocationsPopup" class="form-check-input" value="on"{if $show_location eq 'y'} checked="checked"{/if}>
                    <label>
                        {tr}Show in popup box{/tr}
                    </label>
                </div>
            </div>
            <div class="mb-3 row align-items-center">
                <label class="col-sm-4 col-form-label" for="customparticipants">
                    {tr}Custom participants{/tr}
                </label>
                <div class="col-sm-2">
                    <select name="customparticipants" id="customparticipants" class="form-select">
                        <option value='y' {if $customparticipants eq 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                        <option value='n' {if $customparticipants eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                    </select>
                </div>
                <div class="checkbox col-sm-3">
                    <input type="checkbox" class="form-check-input" name="show[participants]" value="on"{if $show_participants eq 'y'} checked="checked"{/if}>
                    <label>
                        {tr}Show in popup box{/tr}
                    </label>
                </div>
            </div>
            <div class="mb-3 row align-items-center">
                <label class="col-sm-4 col-form-label" for="customcategories">
                    {tr}Custom classification{/tr}
                </label>
                <div class="col-sm-2">
                    <select name="customcategories" id="customcategories" class="form-select">
                        <option value='y' {if $customcategories eq 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                        <option value='n' {if $customcategories eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                    </select>
                </div>
                <div class="checkbox col-sm-3">
                    <input type="checkbox" class="form-check-input" name="show[category]" value="on"{if $show_category eq 'y'} checked="checked"{/if}>
                    <label>
                        {tr}Show in popup box{/tr}
                    </label>
                </div>
            </div>
            <div class="mb-3 row align-items-center">
                <label class="col-sm-4 col-form-label" for="customlanguages">
                    {tr}Custom language{/tr}
                </label>
                <div class="col-sm-2">
                    <select name="customlanguages" id="customlanguages" class="form-select">
                        <option value='y' {if $customlanguages eq 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                        <option value='n' {if $customlanguages eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                    </select>
                </div>
                <div class="checkbox col-sm-3">
                    <input type="checkbox" class="form-check-input" name="show[language]" id="showlanguagepopup" value="on"{if $show_language eq 'y'} checked="checked"{/if}>
                    <label for="showlanguagepopup">
                        {tr}Show in popup box{/tr}
                    </label>
                </div>
            </div>
            <div class="mb-3 row align-items-center">
                <label class="col-sm-4 col-form-label" for="customurl">
                    {tr}Custom URL{/tr}
                </label>
                <div class="col-sm-2">
                    <select name="options[customurl]" id="customurl" class="form-select">
                        <option value='y' {if $customurl eq 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                        <option value='n' {if $customurl eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                    </select>
                </div>
                <div class="checkbox col-sm-3">
                    <input type="checkbox" class="form-check-input" id="showurlpopup" name="show[url]" value="on"{if $show_url eq 'y'} checked="checked"{/if}>
                    <label for="showurlpopup" class="form-check-label">
                        {tr}Show in popup box{/tr}
                    </label>
                </div>
            </div>
            {if $prefs.feature_newsletters eq 'y'}
                <div class="mb-3 row">
                    <label class="col-sm-4 col-form-label" for="customsubscription">
                        {tr}Custom subscription list{/tr}
                    </label>
                    <div class="col-sm-2">
                        <select name="customsubscription" id="customsubscription" class="form-select">
                            <option value='y' {if $customsubscription eq 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                            <option value='n' {if $customsubscription eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                        </select>
                    </div>
                </div>
            {/if}
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="custompriorities">
                    {tr}Custom priority{/tr}
                </label>
                <div class="col-sm-2">
                    <select name="custompriorities" id="custompriorities" class="form-select">
                        <option value='y' {if $custompriorities eq 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                        <option value='n' {if $custompriorities eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                    </select>
                </div>
            </div>
            {if $tiki_p_admin_calendar eq 'y'}
                <div class="mb-3 row">
                    <label class="col-sm-4 col-form-label" for="personal">
                        {tr}Personal Calendar{/tr}
                        <a class="tikihelp text-info" title="{tr}Personal Calendar:{/tr} {tr}Events will be visible only to users creating them.{/tr}"><span class="icon icon-help fas fa-question-circle fa-fw "></span></a>
                    </label>
                    <div class="col-sm-2">
                        <select name="personal" id="personal" class="form-select">
                            <option value='y' {if $personal eq 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                            <option value='n' {if $personal eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label class="col-sm-4 col-form-label" for="private">
                        {tr}Private Calendar{/tr}
                        <a class="tikihelp text-info" title="{tr}Private Calendar:{/tr} {tr}Calendar will be accessible only for your user.{/tr}"><span class="icon icon-help fas fa-question-circle fa-fw "></span></a>
                    </label>
                    <div class="col-sm-2">
                        <select name="private" id="private" class="form-select">
                            <option value='y' {if $private eq 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                            <option value='n' {if $private eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                        </select>
                    </div>
                </div>
            {/if}
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="customcategories">
                    {tr}Start of day{/tr}
                </label>
                <div class="col-sm-2">
                    {html_select_time prefix="startday_" time=$info.startday display_minutes=false display_seconds=false use_24_hours=$use_24hr_clock}
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="customcategories">
                    {tr}End of day{/tr}
                </label>
                <div class="col-sm-2">
                    {html_select_time prefix="endday_" time=$info.endday display_minutes=false display_seconds=false use_24_hours=$use_24hr_clock}
                </div>
            </div>
            <div class="mb-3 row">
            <label class="col-sm-4 col-form-label" for="customcategories">
                {tr}Days to display{/tr}
            </label>
            <div class="col-sm-8">
                <div>
                    <input type="checkbox" class="form-check-input" id="select-all-days">
                    <label class="form-check-label me-3" for="select-all-days">
                        {tr}Select all{/tr}
                    </label>
                    <input type="checkbox" id="select-working-days" class="form-check-input">
                    <label class="form-check-label" for="select-working-days">
                        {tr}Select working days{/tr}
                    </label>
                </div>
                {section name="viewdays" start=0 loop=7}
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="viewdays[]" value="{$smarty.section.viewdays.index}" {if !empty($info.viewdays) && in_array($smarty.section.viewdays.index,$info.viewdays)} checked="checked" {/if}>
                    <label class="form-check-label">
                        {$days_names[$smarty.section.viewdays.index]}
                    </label>
                </div>
                {/section}
            </div>
        </div>
        <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="customcategories">
                    {tr}Standard color{/tr}
                </label>
                <div class="col-sm-3">
                    <select class="form-select" id="customcategories" name="options[customcolors]" onChange="javascript:document.getElementById('fgColorField').disabled=(this.options[this.selectedIndex].value != 0);document.getElementById('bgColorField').disabled=(this.options[this.selectedIndex].value != 0);">
                        <option value="" />
                        <option value="008400-99fa99" style="background-color:#99fa99;color:#008400" {if ($customColors) eq '008400-99fa99'}selected{/if}>{tr}Green{/tr}</option>
                        <option value="3333ff-aaccff" style="background-color:#aaccff;color:#3333ff" {if ($customColors) eq '3333ff-aaccff'}selected{/if}>{tr}Blue{/tr}</option>
                        <option value="996699-c2a6d2" style="background-color:#e0cae5;color:#996699" {if ($customColors) eq '996699-c2a6d2'}selected{/if}>{tr}Purple{/tr}</option>
                        <option value="cc0000-ff9966" style="background-color:#ff9966;color:#cc0000" {if ($customColors) eq 'cc0000-ff9966'}selected{/if}>{tr}Red{/tr}</option>
                        <option value="996600-ffcc66" style="background-color:#ffcc66;color:#996600" {if ($customColors) eq '996600-ffcc66'}selected{/if}>{tr}Orange{/tr}</option>
                        <option value="666600-ffff00" style="background-color:#ffff00;color:#666600" {if ($customColors) eq '666600-ffff00'}selected{/if}>{tr}Yellow{/tr}</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="fgColorField">
                    {tr}Custom foreground color{/tr}
                </label>
                <div class="col-sm-3">
                    <input id="fgColorField" class="form-control" type="text" name="options[customfgcolor]" value="{$customfgcolor}" size="6"> <i>{tr}Example:{/tr} FFFFFF</i>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="bgColorField">
                    {tr}Custom background color{/tr}
                </label>
                <div class="col-sm-3">
                    <input id="bgColorField" class="form-control" type="text" name="options[custombgcolor]" value="{$custombgcolor}" size="6"> <i>{tr}Example:{/tr} 000000</i>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="customstatus">
                    {tr}Status{/tr}
                </label>
                <div class="col-sm-2">
                    <select name="customstatus" id="customstatus" class="form-select">
                        <option value='y' {if $info.customstatus ne 'n'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                        <option value='n' {if $info.customstatus eq 'n'}selected="selected"{/if}>{tr}No{/tr}</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label">
                    {tr}Default event status:{/tr}
                </label>
                <div class="col-sm-4">
                    {html_options class="form-control" name='options[defaulteventstatus]' output=$eventstatusoutput values=$eventstatus selected=$defaulteventstatus}
                    <div class="input-group my-2">
                        <span class="input-group-text">{tr}New Status:{/tr}</span>
                        <input type="text" class="form-control" name="newstatus">
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="checkbox col-sm-4 offset-sm-4">
                    <input type="checkbox" class="form-check-input" name="show[status]" value="on"{if $info.show_status eq 'y'} checked="checked"{/if}>
                    <label class="form-check-label">
                        {tr}Show in popup view{/tr}
                    </label>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="checkbox col-sm-4 offset-sm-4">
                    <input type="checkbox" class="form-check-input" name="show[status_calview]" value="on"{if $info.show_status_calview ne 'n'} checked="checked"{/if}>
                    <label class="form-check-label">
                        {tr}Show in calendar view{/tr}
                    </label>
                </div>
            </div>
            {if $prefs.feature_groupalert eq 'y'}
                <div class="mb-3 row">
                    <label class="col-sm-4 col-form-label" for="groupforAlert">
                        {tr}Group of users alerted when calendar event is modified{/tr}
                    </label>
                    <div class="col-sm-2">
                        <select id="groupforAlert" name="groupforAlert" class="form-select">
                            <option value=""></option>
                            {foreach key=k item=i from=$groupforAlertList}
                                <option value="{$k|escape}" {$i}>{$k|escape}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label class="col-sm-4 form-check-label" for="showeachuser">
                        {tr}Allows each user to be selected for small groups{/tr}
                    </label>
                    <div class="col-sm-2">
                        <input type="checkbox" class="form-check-input" name="showeachuser" id="showeachuser" {if $showeachuser eq 'y'}checked="checked"{/if}>
                    </div>
                </div>
            {/if}
            <div class="mb-3 row">
                <label class="col-sm-4 form-check-label" for="allday">
                    {tr}Default length of events is all day{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="checkbox" class="form-check-input" id="allday" name="allday"{if $info.allday eq 'y'} checked="checked"{/if}>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 form-check-label" for="nameoneachday">
                    {tr}Event name on each day in calendar view{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="checkbox" class="form-check-input" name="nameoneachday"{if $info.nameoneachday eq 'y'} checked="checked"{/if}>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="copybuttononeachevent">
                    {tr}Show copy button of event link in each event in calendar view{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="checkbox" class="form-check-input" name="copybuttononeachevent"{if $info.copybuttononeachevent eq 'y'} checked="checked"{/if}>
                </div>
            </div>
            <div class="mb-3 text-center">
                <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
            </div>
            </fieldset>
            <fieldset>
                <legend>{tr}Delete old events{/tr}</legend>
                <div class="mb-3 row">
                    <label class="col-sm-4 col-form-label" for="days">
                        {tr}Delete events older than:{/tr}
                    </label>
                    <div class="col-sm-3">
                        <div class="input-group">
                            <input type="text" name="days" id="days" value="0" class="form-control">
                            <span class="input-group-text">
                                {tr}days{/tr}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mb-3 text-center">
                    <input
                        type="submit"
                        class="btn btn-danger"
                        name="clean"
                        value="{tr}Delete{/tr}"
                        onclick="confirmPopup('{tr}Delete old events?{/tr}')"
                    >
                </div>
            </fieldset>
        </form>
    {/tab}

    {if $subscription.subscriptionId gt 0}
        {assign var="subtab" value="{tr}Edit Subscription{/tr}"}
    {else}
        {assign var="subtab" value="{tr}Create Subscription{/tr}"}
    {/if}
    {tab name=$subtab}
        <h2>{$subtab}</h2>

        <form action="tiki-admin_calendars.php" method="post">
            {ticket}
            <fieldset>
            <input type="hidden" name="subscription[subscriptionId]" value="{$subscription.subscriptionId|escape}">
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="subscriptionName">
                    {tr}Name{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="subscription[name]" id="subscriptionName" value="{$subscription.name|escape}" required>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="source">
                    {tr}Source URL{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="url" class="form-control" name="subscription[source]" id="source" value="{$subscription.source|escape}" required>
                    <span class="form-text">
                        {tr _0="<a href='tiki-admin_dsn.php'>" _1="</a>"}This can be an address of a CalDAV server or export of .ics calendar URL. If the URL needs authentication, specify it in %0Admin->Content Authentication%1.{/tr}
                    </span>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="refreshRate">
                    {tr}Refresh rate{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" maxlength="10" name="subscription[refresh_rate]" id="refreshRate" value="{$subscription.refresh_rate|escape}">
                    <span class="form-text">
                        {tr}How often will the calendar contents get refreshed. Example format: P1W once a week or P1H every hour{/tr}
                    </span>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="order">
                    {tr}Order{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="number" class="form-control" name="subscription[order]" id="order" value="{$subscription.order|escape}">
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="color">
                    {tr}Color{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" maxlength="10" name="subscription[color]" id="color" value="{$subscription.color|escape}">
                    <span class="form-text">
                        {tr}Enter an hexadecimal color (Example: #99fa99, #008400){/tr}
                    </span>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 form-check-label" for="stripTodos">
                    {tr}Ignore Todos{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="checkbox" class="form-check-input" name="subscription[strip_todos]" id="stripTodos" value="1"{if $subscription.strip_todos} checked="checked"{/if}>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 form-check-label" for="stripAlarms">
                    {tr}Ignore Alarms{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="checkbox" class="form-check-input" name="subscription[strip_alarms]" id="stripAlarms" value="1"{if $subscription.strip_alarms} checked="checked"{/if}>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 form-check-label" for="stripAttachments">
                    {tr}Ignore Attachments{/tr}
                </label>
                <div class="col-sm-8">
                    <input type="checkbox" class="form-check-input" name="subscription[strip_attachments]" id="stripAttachments" value="1"{if $subscription.strip_attachments} checked="checked"{/if}>
                </div>
            </div>
            <div class="mb-3 text-center">
                <input type="submit" class="btn btn-primary" name="savesub" value="{tr}Save{/tr}">
            </div>
        </form>
    {/tab}
{/tabset}
