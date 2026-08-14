{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    <div class="navbar d-inline-flex">
        {permission name=admin_trackers}
            <a class="btn btn-link" href="{service controller=tabular action=create}" role="button">{icon name=create} {tr}New{/tr}</a>
            <a class="btn btn-link" href="{service controller=tabular action=manage}" role="button">{icon name=list} {tr}Manage{/tr}</a>
        {/permission}
    </div>
{/block}

{block name="content"}
    {if $completed}
        {remarksbox type=confirm title="{tr}Import Completed{/tr}"}
            {tr}Your import was completed successfully.{/tr}
        {/remarksbox}
    {else}
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
            {function name=createFileInput format="" label="" fileInputId="" fileAccept=""}
                <div class="input-group mb-3">
                    <span class="input-group-text" id="inputGroupText">{tr}{$label}{/tr}</span>
                    <input type="text" class="form-control" id="{$fileInputId}Name" aria-describedby="inputGroupText" placeholder="{tr}Choose file{/tr}" readonly onclick="document.getElementById('{$fileInputId}').click();" style="cursor: pointer;">
                    <input type="file" class="d-none" id="{$fileInputId}" name="file" accept="{$fileAccept}" onchange="updateFileName(this)">
                    <label class="input-group-text" for="{$fileInputId}" style="cursor: pointer;">{tr}Browse{/tr}</label>
                </div>
            {/function}           
            {elseif $format eq 'json'}
                {createFileInput format='json' label='JSON File' fileInputId='jsonInputFile' fileAccept='application/json'}
            {elseif $format eq 'ndjson'}
                {createFileInput format='ndjson' label='NDJSON File' fileInputId='ndjsonInputFile' fileAccept='application/x-ndjson'}
            {elseif $format eq 'ical'}
                {createFileInput format='ical' label='iCal File' fileInputId='icalInputFile' fileAccept='text/calendar'}
            {else}
                {createFileInput format='csv' label='CSV File' fileInputId='inputFile' fileAccept='text/csv, .tsv'}
                <div class="form-group">
                    <label for="separator">{tr}Select separator:{/tr}</label>
                    <select name="separator" class="form-select" aria-label="CSV File delimiter">
                        <option value="," selected>{tr}Comma{/tr}(,)</option>
                        <option value=";">{tr}Semicolon{/tr}(;)</option>
                        <option value="tab">{tr}Tab{/tr}(\t)</option>
                    </select>
                </div>
            {/if}     
            <div class="submit">
                <input class="btn btn-primary" type="submit" value="{tr}Import{/tr}">
            </div>
        </form>
    {/if}
{/block}
