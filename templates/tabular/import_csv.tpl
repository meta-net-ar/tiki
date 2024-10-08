{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    <div class="navbar d-inline-flex">
        {permission name=admin_trackers}
            <a class="btn btn-link" href="{service controller=tabular action=create}">{icon name=create} {tr}New{/tr}</a>
            <a class="btn btn-link" href="{service controller=tabular action=manage}">{icon name=list} {tr}Manage{/tr}</a>
        {/permission}
    </div>
{/block}

{block name="content"}
    {if $completed}
        {remarksbox type=confirm title="{tr}Import Completed{/tr}"}
            {tr}Your import was completed successfully.{/tr}
        {/remarksbox}
    {else}
        
        {literal}
            <script type="text/javascript">
                function updateFileName(inputFileId, fileNameFieldId) {
                    const inputFile = document.getElementById(inputFileId);
                    const fileNameField = document.getElementById(fileNameFieldId);
                    fileNameField.value = inputFile.files[0] ? inputFile.files[0].name : 'Choose file';
                }
            </script>
        {/literal}

        <form class="no-ajax" method="post" action="{service controller=tabular action=import_csv tabularId=$tabularId}" enctype="multipart/form-data">
            {if $odbc}
            <p>{tr}Import from remote ODBC source.{/tr}</p>
            {elseif $api}
            <p>{tr}Import from remote API source.{/tr}</p>
                {foreach $placeholders as $field}
                    <div class="input-group mb-3">
                        <span class="input-group-text">{$field}</span>
                        <div>
                            <input type="text" name="placeholders[{$field}]" value="" class="form-control">
                        </div>
                    </div>
                {/foreach}
            {elseif $format eq 'json'}
            <div class="input-group mb-3">
                <span class="input-group-text" id="inputGroupText">{tr}JSON File{/tr}</span>
                <input type="text" class="form-control" id="jsonFileName" aria-describedby="inputGroupText" placeholder="Choose file" readonly onclick="document.getElementById('jsonInputFile').click();" style="cursor: pointer;">
                <input type="file" class="d-none" id="jsonInputFile" name="file" accept="application/json" onchange="updateFileName('jsonInputFile', 'jsonFileName')">
                <label class="input-group-text" for="jsonInputFile" style="cursor: pointer;">Browse</label>
            </div>
            {elseif $format eq 'ndjson'}
            <div class="input-group mb-3">
                <span class="input-group-text" id="inputGroupText">{tr}NDJSON File{/tr}</span>
                <input type="text" class="form-control" id="ndjsonFileName" aria-describedby="inputGroupText" placeholder="Choose file" readonly onclick="document.getElementById('ndjsonInputFile').click();" style="cursor: pointer;">
                <input type="file" class="d-none" id="ndjsonInputFile" name="file" accept="application/x-ndjson" onchange="updateFileName('ndjsonInputFile', 'ndjsonFileName')">
                <label class="input-group-text" for="ndjsonInputFile" style="cursor: pointer;">Browse</label>
            </div>
            {elseif $format eq 'ical'}
            <div class="input-group mb-3">
                <span class="input-group-text" id="inputGroupText">{tr}iCal File{/tr}</span>
                <input type="text" class="form-control" id="icalFileName" aria-describedby="inputGroupText" placeholder="Choose file" readonly onclick="document.getElementById('icalInputFile').click();" style="cursor: pointer;">
                <input type="file" class="d-none" id="icalInputFile" name="file" accept="text/calendar" onchange="updateFileName('icalInputFile', 'icalFileName')">
                <label class="input-group-text" for="icalInputFile" style="cursor: pointer;">Browse</label>
            </div>
            {else}
            <div class="input-group mb-3">
                <span class="input-group-text" id="inputGroupText">{tr}CSV File{/tr}</span>
                <input type="text" class="form-control" id="fileName" aria-describedby="inputGroupText" placeholder="Choose file" readonly onclick="document.getElementById('inputFile').click();" style="cursor: pointer;">
                <input type="file" class="d-none" id="inputFile" name="file" accept="text/csv" onchange="updateFileName('inputFile', 'fileName')">
                <label class="input-group-text" for="inputFile" style="cursor: pointer;">Browse</label>
            </div>
            {/if}
            <div class="submit">
                <input class="btn btn-primary" type="submit" value="{tr}Import{/tr}">
            </div>
        </form>
    {/if}
{/block}
