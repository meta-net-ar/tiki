{title}{tr}Calendar import{/tr}{/title}

<div class="t_navbar">
    {button class="btn btn-link" _type="text" _icon_name="calendar" _text="{tr}View Calendars{/tr}" href="tiki-calendar.php"}
    {button class="btn btn-link" _type="text" _icon_name="cog" _text="{tr}Admin Calendars{/tr}" href="tiki-admin_calendars.php"}
</div>

{if isset($updated) and $updated eq 'y'}
    {tr}Calendar has been updated{/tr}
{/if}

{if isset($error) and $error eq 'y'}
    {tr}Invalid file format. Only CSV and ICS files are accepted.{/tr}
{/if}

<form method="post" action="tiki-calendar_import.php" enctype="multipart/form-data">
    <br>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label">{tr}Calendar{/tr}</label>
        <div class="col-sm-7">
            <select name="calendarId" class="form-control">
                {foreach item=lc from=$calendars}
                    <option value="{$lc.calendarId}">{$lc.name|escape}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label">{tr}CSV or ICS File{/tr}
            {capture name=help}
                <strong>{tr}CSV format{/tr}</strong><br>
                {tr}Column names on the first line:{/tr}<br>
                name,description,start&nbsp;date,start&nbsp;time,end&nbsp;date,end&nbsp;time,status,lang,categoryId,locationId,priority,url,categoryId<br>
                <i>{tr _0=subject _1=name}%0 column name can be used instead of %1{/tr}</i><br>
                {tr}Date format:{/tr} {tr}See:{/tr} http://php.net/strtotime
                <br><br>
                <strong>{tr}ICS format{/tr}</strong><br>
                {tr}Standard iCalendar (.ics) files are supported, including recurring events.{/tr}
            {/capture}
            <a title="{tr}Help{/tr}" {popup text=$smarty.capture.help}>{icon name='help'}</a>
        </label>
        <div class="col-sm-7">
            <input type="file" accept=".csv,.ics" name="fileImport" size="50" class="form-control">
        </div>
    </div>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label"></label>
        <div class="col-sm-7">
            <input type="submit" class="btn btn-primary btn-sm" name="import" value="{tr}import{/tr}">
        </div>
    </div>
</form>
