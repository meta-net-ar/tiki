{title help="Banners"}{tr}Create or edit banners{/tr}{/title}

<div class="t_navbar mb-4">
    {button href="tiki-list_banners.php" _class="btn btn-link" _type="link" _icon_name="list" _text="{tr}List banners{/tr}"}
</div>

<form action="tiki-edit_banner.php" method="post" enctype="multipart/form-data" class="mb-4">
    {ticket}
    <input type="hidden" name="bannerId" value="{$bannerId|escape}">
    <div class="card mb-2">
        <div class="card-body">
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="url">{tr}URL to link the banner{/tr}</label>
                <div class="col-sm-7 mb-3">
                    <input type="text" name="url" id="url" value="{$url|escape}" class="form-control">
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="client">{tr}Client{/tr}</label>
                <div class="col-sm-7 mb-3">
                    {user_selector user=$client name='client' id='client'}
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="maxImpressions">{tr}Maximum impressions{/tr}</label>
                <div class="col-sm-7">
                    <input type="text" name="maxImpressions" id="maxImpressions" value="{$maxImpressions|escape}" maxlength="7" class="form-control">
                    <div class="form-text">
                        {tr}-1 for unlimited{/tr}
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="maxUserImpressions">{tr}Maximum number of impressions for a user{/tr}</label>
                <div class="col-sm-7">
                    <input type="text" name="maxUserImpressions" id="maxUserImpressions" value="{$maxUserImpressions|escape}" maxlength="7" class="form-control">
                    <div class="form-text">
                        {tr}-1 for unlimited{/tr}
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="maxClicks">{tr}Maximum clicks{/tr}</label>
                <div class="col-sm-7">
                    <input type="text" name="maxClicks" id="maxClicks" value="{$maxClicks|escape}" maxlength="7" class="form-control">
                    <div class="form-text">
                        {tr}-1 for unlimited{/tr}
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="onlyInURIs">{tr}URIs where the banner appears only{/tr}</label>
                <div class="col-sm-7">
                    <input type="text" name="onlyInURIs" id="onlyInURIs" value="{$onlyInURIs|escape}" class="form-control">
                    <div class="form-text">
                        {tr}Type each URI enclosed with the # character. Exemple:#/this_page#/tiki-index.php?page=this_page#{/tr}
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="exceptInURIs">{tr}URIs where the banner will not appear{/tr}</label>
                <div class="col-sm-7">
                    <input type="text" name="exceptInURIs" id="exceptInURIs" value="{$exceptInURIs|escape}" class="form-control">
                    <div class="form-text">
                        {tr}Type each URI enclosed with the # character. Exemple:#/this_page#/tiki-index.php?page=this_page#{/tr}
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="zone">{tr}Zone{/tr}</label>
                <div class="col-sm-7">
                    <select id="zone" name="zone"{if !$zones} disabled="disabled"{/if} class="form-control">
                        {section name=ix loop=$zones}
                            <option value="{$zones[ix].zone|escape}" {if $zone eq $zones[ix].zone}selected="selected"{/if}>{$zones[ix].zone|escape}</option>
                        {sectionelse}
                            <option value="" disabled="disabled" selected="selected">{tr}None{/tr}</option>
                        {/section}
                    </select>
                    <div class="form-text">
                        {tr}Or, create a new zone{/tr}
                    </div>
                </div>
                <label class="col-sm-4 col-form-label" for="zoneName">{tr}New Zone{/tr}</label>
                <div class="col-sm-7">
                    <input type="text" id="zoneName" name="zoneName" maxlength="10" class="form-control">
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label"></label>
                <div class="col-sm-7">
                    <input type="submit" class="btn btn-primary btn-sm" name="create_zone" value="{tr}Create a new Zone{/tr}" onclick="disableRequiredFields()">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <h2 class="h4">{tr}Show the banner only between these dates:{/tr}</h2> {* Here and below, use semantically correct heading size, but display smaller visually *}
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="fromDate">{tr}From date:{/tr}</label>
                <div class="col-sm-7 short">
                    {html_select_date id="fromDate" time=$fromDate prefix="fromDate_" end_year="+2" field_order=$prefs.display_field_order}
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="toDate">{tr}To date:{/tr}</label>
                <div class="col-sm-7 short">
                    {html_select_date id="toDate" time=$toDate prefix="toDate_" start_year="+0" end_year=$prefs.calendar_end_year field_order=$prefs.display_field_order}
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-sm-4 col-form-label">{tr}Use dates:{/tr}</div>
                <div class="col-sm-7">
                    <input class="form-check-input" type="checkbox" id="useDates" name="useDates" {if $useDates eq 'y'}checked='checked'{/if}>
                    <label class="form-check-label" for="useDates"> {tr}Yes{/tr}</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <h2 class="h4">{tr}Show the banner only in these hours:{/tr}</h2>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="fromTime">{tr}from{/tr}</label>
                <div class="col-sm-7 short">
                    {html_select_time id="fromTime" time=$fromTime display_seconds=false prefix='fromTime' use_24_hours=$use_24hr_clock}
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label" for="toTime">{tr}to{/tr}</label>
                <div class="col-sm-7 short">
                    {html_select_time id="toTime" time=$toTime display_seconds=false prefix='toTime' use_24_hours=$use_24hr_clock}
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <h2 class="h4">{tr}Show the banner only on:{/tr}</h2>
            <div class="col-sm-12">
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="Dmon" id="Dmon" {if $Dmon eq 'y'}checked="checked"{/if}>
                        <label class="form-check-label" for="Dmon"> {tr}Monday{/tr}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="Dtue" id="Dtue" {if $Dtue eq 'y'}checked="checked"{/if}>
                        <label class="form-check-label" for="Dtue"> {tr}Tuesday{/tr}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="Dwed" id="Dwed" {if $Dwed eq 'y'}checked="checked"{/if}>
                        <label class="form-check-label" for="Dwed"> {tr}Wednesday{/tr}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="Dthu" id="Dthu" {if $Dthu eq 'y'}checked="checked"{/if}>
                        <label class="form-check-label" for="Dthu"> {tr}Thursday{/tr}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="Dfri" id="Dfri" {if $Dfri eq 'y'}checked="checked"{/if}>
                        <label class="form-check-label" for="Dfri"> {tr}Friday{/tr}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="Dsat" id="Dsat" {if $Dsat eq 'y'}checked="checked"{/if}>
                        <label class="form-check-label" for="Dsat"> {tr}Saturday{/tr}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="Dsun" id="Dsun" {if $Dsun eq 'y'}checked="checked"{/if}>
                        <label class="form-check-label" for="Dsun"> {tr}Sunday{/tr}</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h4">{tr}Select ONE method for the banner:{/tr}</h2>
            <div class="mb-3 row">
                <div class="col-sm-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="use" id="useHTML" value="useHTML" {if $use eq 'useHTML'}checked="checked"{/if} onclick="toggleBannerContent('useHTML')">
                        <label class="form-check-label" for="useHTML"> {tr}Use HTML{/tr}</label>
                    </div>
                </div>
                <div class="col-sm-7" id="htmlContent">
                    <textarea class="form-control" rows="5" name="HTMLData" aria-labelledby="HTMLcode" {if $use neq 'useHTML'}disabled="disabled"{/if} required>{$HTMLData|escape}</textarea>
                    <div class="form-text" id="HTMLcode">
                        {tr}HTML code{/tr}
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-sm-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="use" id="useImage" value="useImage" {if $use eq 'useImage'}checked="checked"{/if} onclick="toggleBannerContent('useImage')">
                        <label class="form-check-label" for="useImage"> {tr}Use Image{/tr}</label>
                    </div>
                </div>
                <div class="col-sm-7" id="imageContent">
                    <input type="hidden" name="imageData" value="{$imageData|escape}">
                    <input type="hidden" name="imageName" value="{$imageName|escape}">
                    <input type="hidden" name="imageType" value="{$imageType|escape}">
                    <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
                    <input name="userfile1" type="file" accept="image/*" class="form-control" aria-label="Browse" {if $use neq 'useImage'}disabled="disabled"{/if} required>
                </div>
            </div>
            <div class="mb-3 row">
                {if $hasImage eq 'y'}
                <div class="col-sm-4">{tr}Current Image{/tr}</div>
                <div class="col-sm-7">
                    {$imageName}: <img class="img-fluid" src="banner_image.php?id={$bannerId}" alt="{tr}Current Image{/tr}">
                </div>
                {/if}
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label"><input type="radio" name="use" value="useFixedURL" {if $use eq 'useFixedURL'}checked="checked"{/if} onclick="toggleBannerContent('useFixedURL')"> {tr}Use Image from URL{/tr}</label>
                <div class="col-sm-7" id="fixedURLContent">
                    <input type="text" name="fixedURLData" value="{$fixedURLData|escape}" class="form-control" {if $use neq 'useFixedURL'}disabled="disabled"{/if} required>
                    <div class="form-text">
                        {tr}(the image will be requested at the URL for each impression){/tr}
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-4 col-form-label"><input type="radio" name="use" value="useText" {if $use eq 'useText'}checked="checked"{/if} onclick="toggleBannerContent('useText')"> {tr}Use Text{/tr}</label>
                <div class="col-sm-7" id="textContent">
                    <textarea class="form-control" rows="5" name="textData" {if $use neq 'useText'}disabled="disabled"{/if} required>{$textData|escape}</textarea>
                </div>
            </div>
        </div>
    </div>
    <input type="submit" class="btn btn-primary" name="save" value="{tr}Save the Banner{/tr}">
</form>

<script>
    function toggleBannerContent(selectedType) {
        const contentTypes = {
            'useHTML': 'htmlContent',
            'useImage': 'imageContent',
            'useFixedURL': 'fixedURLContent',
            'useText': 'textContent'
        };

        for (const [type, id] of Object.entries(contentTypes)) {
            document.getElementById(id).querySelector(type === 'useImage' ? 'input[type="file"]' : 'textarea, input').disabled = selectedType !== type;
        }
    }

    function disableRequiredFields() {
        const fields = {
            'htmlContent': 'textarea',
            'imageContent': 'input[type="file"]',
            'fixedURLContent': 'input',
            'textContent': 'textarea'
        };
        for (const [id, selector] of Object.entries(fields)) {
            document.getElementById(id).querySelector(selector).removeAttribute('required');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const selectedType = document.querySelector('input[name="use"]:checked').value;
        toggleBannerContent(selectedType);
    });
</script>

{if $zones}
    <div align="left" class="card">
        <div class="card-body">
            <h2 class="h4">{tr}Remove zones (info entered for any banner in the zones will be lost){/tr}</h2>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <tr>
                        <th>{tr}Name{/tr}</th>
                        <th></th>
                    </tr>

                    {section name=ix loop=$zones}
                        <tr>
                            <td class="text">{$zones[ix].zone|escape}</td>
                            <td class="action">
                                <form action="tiki-edit_banner.php" method="post">
                                    {ticket}
                                    <input type="hidden" name="removeZone" value="{$zones[ix].zone}">
                                    <button type="submit" class="btn btn-link px-0 pt-0 pb-0 tips" title=":{tr}Remove{/tr}" onclick="confirmPopup('{tr}Do you want to delete this zone{/tr} ?')">
                                        {icon name='remove' _menu_icon='y' }
                                    </button>
                                </form>
                            </td>
                        </tr>
                    {/section}
                </table>
            </div>
        </div>
    </div>
{/if}
