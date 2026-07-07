{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}

<form method="post" action="{service controller=tracker action=replace}">
    {accordion}
        {accordion_group title="{tr}General{/tr}" accordion_pos=$accordion_pos}
            <div class="mb-3 mx-0">
                <label for="name">{tr}Name{/tr}</label>
                <input class="form-control" type="text" name="name" id="name" value="{$info.name|escape}" maxlength="{$MAX_TRACKER_NAME_LENGTH}" required="required">
            </div>
            <div class="mb-3 mx-0">
                <label for="description">{tr}Description{/tr}</label>
                {* if the _syntax param is empty, {textarea} will work out the best one, "none" forces a plain <textarea> to be created *}
                {$descriptionSyntax = $info.descriptionIsParsed eq 'y' ? '' : 'none'}
                {textarea class="form-control" name="description" id="description" cols="40" _syntax=$descriptionSyntax}{$info.description|escape}{/textarea}
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="descriptionIsParsed" id="descriptionIsParsed" {if $info.descriptionIsParsed eq 'y'}checked="checked"{/if} value="1">
                <label class="form-check-label" for="descriptionIsParsed">
                    {tr}Description is wiki-parsed{/tr}
                </label>
            </div>
            <div class="mb-3 mx-0">
                <label for="fieldPrefix">{tr}Field Prefix{/tr}</label>
                <input class="form-control" type="text" name="fieldPrefix" id="fieldPrefix" value="{$info.fieldPrefix|escape}">
                {tr}Short string prepended by default to all fields in this tracker.{/tr}
            </div>
            <div class="mb-3 mx-0">
                <label for="permName">{tr}Permanent Name{/tr}</label>
                <input class="form-control" type="text" name="permName" id="permName" value="{$info.permName|escape}">
                {tr}Required for Advanced Shopping Cart and some other tracker features, do not change this unless you are sure.{/tr}
            </div>
            {jq}
                $("#name").on("change", function() {
                    if ($("#name").val()) {
                        const prefix = $("#fieldPrefix")
                        if (! prefix.val()) {
                            prefix.val($("#name").val().replace(/s$/, "").replace(/\W/g, "").toLowerCase());
                        }
                        const permName = $("#permName")
                        if (! permName.val()) {
                            permName.val($("#name").val().replace(/s$/, "").replace(/\W/g, "").toLowerCase());
                        }
                    }
                });
            {/jq}
        {/accordion_group}
        {accordion_group title="{tr}Features{/tr}" accordion_pos=$accordion_pos}
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="allowOffline" value="1"
                    {if $info.allowOffline eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="allowOffline">
                    {tr}Allow offline usage{/tr}
                </label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="useRatings" value="1"
                    {if $info.useRatings eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="useRatings">
                    {tr}Allow ratings (deprecated, use rating field){/tr}
                </label>
            </div>
            <div class="mb-3 mx-0 depends" data-on="useRatings">
                <label for="ratingOptions">{tr}Rating options{/tr}</label>
                <input class="form-controls" type="text" name="ratingOptions" id="ratingOptions" value="{$info.ratingOptions|default:'-2,-1,0,1,2'|escape}">
            </div>
            <div class="form-check depends" data-on="useRatings">
                <input type="checkbox" class="form-check-input" name="showRatings" id="showRatings" value="1"
                        {if $info.showRatings eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" id="showRatings">
                    {tr}Show ratings in listing{/tr}
                </label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="useComments" id="useComments" value="1"
                        {if $info.useComments eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="useComments">
                    {tr}Allow comments{/tr}
                </label>
            </div>
            <div class="form-check depends" data-on="useComments">
                <input type="checkbox" class="form-check-input" name="showComments" id="showComments" value="1"
                        {if $info.showComments eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showComments">
                    {tr}Show comments in listing{/tr}
                </label>
            </div>
            <div class="form-check depends" data-on="useComments">
                <input type="checkbox" class="form-check-input" name="showLastComment" id="showLastComment" value="1"
                        {if $info.showLastComment eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showLastComment">
                    {tr}Display last comment author and date{/tr}
                </label>
            </div>
            <div class="form-check depends" data-on="useComments">
                <input type="checkbox" class="form-check-input" name="saveAndComment" id="saveAndComment" value="1"
                        {if $info.saveAndComment eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="saveAndComment">

                    {tr}Save and Comment{/tr}
                </label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="useAttachments" id="useAttachments" value="1"
                        {if $info.useAttachments eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="useAttachments">
                    {tr}Allow attachments (Deprecated. Will be removed after Tiki 29.x. Use files field instead.){/tr}
                </label>
            </div>
            <div class="form-check depends" data-on="useAttachments">
                <input type="checkbox" class="form-check-input" name="showAttachments" id="showAttachments" value="1"
                        {if $info.showAttachments eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showAttachments">
                    {tr}Display attachments in listing{/tr}
                </label>
            </div>
            <fieldset class="depends sortable" data-on="useAttachments" data-selector="div.checkbox">
                <legend>{tr}Attachment attributes (sortable){/tr}</legend>
                {foreach from=$attachmentAttributes key=name item=att}
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="orderAttachments[]" id="orderAttachments[]" value="{$name|escape}" {if !empty($att.selected)} checked="checked"{/if}>
                        <label class="form-check-label" for="orderAttachments[]">
                            {$att.label|escape}
                        </label>
                    </div>
                {/foreach}
            </fieldset>
            {if $prefs.ajax_inline_edit eq 'y'}
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="allowInlineEditing" id="allowInlineEditing" value="1"
                        {if $info.allowInlineEditing eq 'y' or (empty($info.allowInlineEditing) and $prefs.ajax_inline_edit_trackerlist eq 'y')} checked="checked"{/if}>
                    <label class="form-check-label" for="allowInlineEditing">
                        {tr}Allow inline editing{/tr}
                    </label>
                </div>
            {/if}
        {/accordion_group}
        {accordion_group title="{tr}Duplication{/tr}" accordion_pos=$accordion_pos}
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="enableDuplicate" id="enableDuplicate" value="1"
                    {if $info.enableDuplicate eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="enableDuplicate">
                    {tr}Enable Duplicate Action{/tr}
                </label>
            </div>
            <div class="mb-3 mx-0 depends" data-on="enableDuplicate">
                <label class="form-label">{tr}Field Duplication Rules{/tr}</label>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>{tr}Field{/tr}</th>
                                <th>{tr}Action{/tr}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$fields item=field}
                                <tr>
                                    <td>{$field.name|escape}</td>
                                    <td>
                                        <select name="duplicateRules[{$field.fieldId}]" class="form-select">
                                            <option value="copy" {if empty($info.val_duplicateRules[$field.fieldId]) or $info.val_duplicateRules[$field.fieldId] eq 'copy'}selected{/if}>{tr}Copy{/tr}</option>
                                            <option value="clear" {if $info.val_duplicateRules[$field.fieldId] eq 'clear'}selected{/if}>{tr}Clear/Empty{/tr}</option>
                                            <option value="default" {if $info.val_duplicateRules[$field.fieldId] eq 'default'}selected{/if}>{tr}Set to Default{/tr}</option>
                                        </select>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="form-text">
                    {tr}Define how each field should be handled when duplicating an item.{/tr}
                </div>
            </div>
        {/accordion_group}
        {accordion_group title="{tr}Display{/tr}" accordion_pos=$accordion_pos}
            <div class="mb-3 mx-0">
                <label class="col-form-label" for="logo">{tr}Logo{/tr}</label>
                <input class="form-control" type="text" name="logo" id="logo" value="{$info.logo|escape}">
                <div class="form-text">
                    {tr}Recommended size: 64x64px.{/tr}
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="showStatus" id="showStatus" value="1"
                        {if $info.showStatus eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showStatus">
                    {tr}Show status{/tr}
                </label>
            </div>
            <div class="form-check depends" data-on="showStatus">
                <input type="checkbox" class="form-check-input" name="showStatusAdminOnly" id="showStatusAdminOnly" value="1"
                        {if $info.showStatusAdminOnly eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showStatusAdminOnly">
                    {tr}Show status to tracker administrator only{/tr}
                </label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="showCreated" id="showCreated" value="1"
                        {if $info.showCreated eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showCreated">
                    {tr}Show creation date when listing items{/tr}
                </label>
            </div>
            <div class="mb-3 mx-0 depends" data-on="showCreated">
                <label for="showCreatedFormat">{tr}Creation date format{/tr}</label>
                <input type="text" name="showCreatedFormat" id="showCreatedFormat" value="{$info.showCreatedFormat|escape}">
                <div class="form-text">
                    <a rel="external" class="link" target="_blank" href="https://doc.tiki.org/Date-and-Time-Features">{tr}Date and Time Format Help{/tr}</a>
                </div>
            </div>
            <div class="form-check depends" data-on="showCreated">
                <input type="checkbox" class="form-check-input" name="showCreatedBy" id="showCreatedBy" value="1"
                        {if $info.showCreatedBy eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showCreatedBy">
                    {tr}Show item creator{/tr}
                </label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="showCreatedView" id="showCreatedView" value="1"
                        {if $info.showCreatedView eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showCreatedView">
                    {tr}Show creation date when viewing items{/tr}
                </label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="showLastModif" id="showLastModif" value="1"
                        {if $info.showLastModif eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showLastModif">
                    {tr}Show last modification date when listing items{/tr}
                </label>
            </div>
            <div class="form-check depends" data-on="showLastModif">
                <input type="checkbox" class="form-check-input" name="showLastModifBy" id="showLastModifBy" value="1"
                        {if $info.showLastModifBy eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showLastModifBy">
                    {tr}Show item last modifier{/tr}
                </label>
            </div>
            <div class="mb-3 mx-0 depends" data-on="showLastModif">
                <label for="showLastModifFormat">{tr}Modification date format{/tr}</label>
                <input class="form-control" type="text" name="showLastModifFormat" id="showLastModifFormat" value="{$info.showLastModifFormat|escape}">
                <div class="form-text">
                    <a class="link" target="_blank" href="https://doc.tiki.org/Date-and-Time-Features">{tr}Date and Time Format Help{/tr}</a>
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="showLastModifView" id="showLastModifView" value="1"
                        {if $info.showLastModifView eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="showLastModifView">
                    {tr}Show last modification date when viewing items{/tr}
                </label>
            </div>
            <div class="mb-3 mx-0">
                <label for="defaultOrderKey">{tr}Default sort order{/tr}</label>
                <select name="defaultOrderKey" id="defaultOrderKey" class="form-select">
                    {foreach from=$sortFields key=k item=label}
                        <option value="{$k|escape}" {if $k eq $info.defaultOrderKey} selected="selected"{/if}>{$label|truncate:42:'...'|escape}</option>
                    {/foreach}
                </select>
            </div>
            <div class="mb-3 mx-0">
                <label for="defaultOrderDir">{tr}Default sort direction{/tr}</label>
                <select name="defaultOrderDir" id="defaultOrderDir" class="form-select">
                    <option value="asc" {if $info.defaultOrderDir eq 'asc'}selected="selected"{/if}>{tr}ascending{/tr}</option>
                    <option value="desc" {if $info.defaultOrderDir eq 'desc'}selected="selected"{/if}>{tr}descending{/tr}</option>
                </select>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="doNotShowEmptyField" id="doNotShowEmptyField" value="1"
                        {if $info.doNotShowEmptyField eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="doNotShowEmptyField">
                    {tr}Hide empty fields from item view{/tr}
                </label>
            </div>
            <div class="mb-3 mx-0" id="fieldsDetails">
                <label for="showPopup">{tr}List detail popup{/tr}</label>
                {object_selector_multi type=trackerfield tracker_id=$info.trackerId _simplevalue=$info.showPopup _separator="," _simplename="showPopup"}
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="allowChooseFieldsToDisplay" value="1"
                    {if $info.allowChooseFieldsToDisplay eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="allowChooseFieldsToDisplay">
                    {tr}Allow user to choose fields to display{/tr}
                </label>
            </div>
        {/accordion_group}
    {accordion_group title="{tr}Section Format{/tr}" accordion_pos=$accordion_pos}
                <div class="mb-3 mx-0">
                    <label for="sectionFormat">{tr}Section format{/tr}</label>
                    <select name="sectionFormat" id="sectionFormat" class="form-select">
                        {foreach $sectionFormats as $format => $label}
                            <option value="{$format|escape}"{if $info.sectionFormat eq $format} selected="selected"{/if}>{$label|escape}</option>
                        {/foreach}
                    </select>
                    <div class="form-text">
                        <p>{tr}Determines how headers will be rendered when using header fields as form section dividers.{/tr}</p>
                        <p>{tr}Set to <em>Configured</em> to use the four following fields.{/tr}</p>
                    </div>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="useFormClasses" id="useFormClasses" value="1" {if $info.useFormClasses eq 'y'} checked="checked"{/if}>
                    <label class="form-check-label" for="useFormClasses">
                        {tr}Use Form Classes{/tr}
                    </label>
                </div>
                <div class="mb-3 mx-0">
                    <label for="formClasses">{tr}Input Form Classes{/tr}</label>
                    <input class="form-control" type="text" name="formClasses" id="formClasses" value="{$info.formClasses|escape}">
                    <div class="form-text">
                        <p>{tr}Sets classes for form to be used in Tracker Plugin (e.g., col-md-9).{/tr}</p>
                    </div>
                </div>
                <div class="mb-3 mx-0">
                    <label for="viewItemPretty">{tr}Template to display an item{/tr}</label>
                    <input class="form-control" type="text" name="viewItemPretty" id="viewItemPretty" value="{$info.viewItemPretty|escape}">
                    <div class="form-text">
                        {tr}wiki:pageName for a wiki page or tpl:tplName for a template{/tr}
                    </div>
                </div>
                <div class="mb-3 mx-0">
                    <label for="editItemPretty">{tr}Template to edit an item{/tr}</label>
                    <input class="form-control" type="text" name="editItemPretty" id="editItemPretty" value="{$info.editItemPretty|escape}">
                    <div class="form-text">
                        {tr}wiki:pageName for a wiki page or tpl:tplName for a template{/tr}
                    </div>
                </div>
                <div class="form-text">
                    <p>{tr _0="<code>{ldelim}DIV(class=&quot;tracker-field-group&quot;){rdelim}...{ldelim}DIV{rdelim}</code>"}When using field rules in a configured wiki template, wrap each label and field in the field rules parent selector. Example: %0{/tr}</p>
                </div>
    {/accordion_group}
        {accordion_group title="{tr}Status{/tr}" accordion_pos=$accordion_pos}
            <div class="mb-3 mx-0">
                <label for="newItemStatus">{tr}New item status{/tr}</label>
                <select name="newItemStatus" id="newItemStatus" class="form-select">
                    {foreach key=st item=stdata from=$statusTypes}
                        <option value="{$st|escape}"
                            {if $st eq $info.newItemStatus} selected="selected"{/if}>
                            {$stdata.label|escape}
                        </option>
                    {/foreach}
                </select>
            </div>
            <div class="mb-3 mx-0">
                <div class="mb-3">
                    <label for="altOpenStatus">{tr}Open alternative label{/tr}</label>
                    <input class="form-control" type="text" name="altOpenStatus" id="altOpenStatus" value="{$info.altOpenStatus|escape}">
                </div>
                <div class="mb-3">
                    <label for="altPendingStatus">{tr}Pending alternative label{/tr}</label>
                    <input class="form-control" type="text" name="altPendingStatus" id="altPendingStatus" value="{$info.altPendingStatus|escape}">
                </div>
                <div>
                    <label for="altClosedStatus">{tr}Closed alternative label{/tr}</label>
                    <input class="form-control" type="text" name="altClosedStatus" id="altClosedStatus" value="{$info.altClosedStatus|escape}">
                </div>
            </div>
            <div class="mb-3 mx-0">
                <label for="modItemStatus">{tr}Modified item status{/tr}</label>
                <select name="modItemStatus" id="modItemStatus" class="form-control">
                    <option value="">{tr}No change{/tr}</option>
                    {foreach key=st item=stdata from=$statusTypes}
                        <option value="{$st|escape}"
                            {if $st eq $info.modItemStatus} selected="selected"{/if}>
                            {$stdata.label|escape}
                        </option>
                    {/foreach}
                </select>
            </div>
            <div class="mb-3 mx-0">
                <label>{tr}Default status displayed in list mode{/tr}</label>

                    {foreach key=st item=stdata from=$statusTypes}
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="defaultStatus[]" id="defaultStatus-{$stdata.label|escape}" value="{$st|escape}"{if in_array($st, $statusList)} checked="checked"{/if}>
                        <label class="form-check-label" for="defaultStatus-{$stdata.label|escape}">
                            {$stdata.label|escape}
                        </label>
                </div>
                    {/foreach}

            </div>
        {/accordion_group}
        {accordion_group title="{tr}Notifications{/tr}" accordion_pos=$accordion_pos}
            <div class="mb-3 mx-0">
                <label for="outboundEmail">{tr}Copy activity to email{/tr}</label>
                <input name="outboundEmail" id="outboundEmail" value="{$info.outboundEmail|escape}" class="email_multi form-control" size="60">
                <div class="form-text">
                    {tr}You can add several email addresses by separating them with commas.{/tr}
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="simpleEmail" id="simpleEmail" value="1"
                        {if $info.simpleEmail eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="simpleEmail">
                    {tr}Use simplified email format{/tr}
                </label>
                <div class="description form-text mb-4">
                    {tr}The tracker will use the text field named Subject if any as subject and will use the user email or for anonymous the email field if any as sender{/tr}
                </div>
            </div>
            <div class="mb-3 mx-0">
                <label for="notifyOn">{tr}Copy activity to email only on{/tr}</label>
                <select name="notifyOn" id="notifyOn" class="form-select">
            <option value="both" {if $info.notifyOn eq 'both'}selected="selected"{/if}>{tr}Creation and Update (default){/tr}</option>
                    <option value="creation" {if $info.notifyOn eq 'creation'}selected="selected"{/if}>{tr}Item creation{/tr}</option>
                    <option value="update" {if $info.notifyOn eq 'update'}selected="selected"{/if}>{tr}Item update{/tr}</option>
                </select>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="publishRSS" id="publishRSS" value="1"
                                           {if $prefs.feed_tracker neq 'y'}disabled="disabled"{/if}
                        {if $info.publishRSS eq 'y'}checked="checked"{/if}>
                <label class="form-check-label" for="publishRSS">
                    {tr}Publish RSS feed for this tracker{/tr}
                </label>
                <div class="description form-text">
                    {tr}Requires "RSS per tracker" to be set in Admin/RSS{/tr}
                    {if $prefs.feed_tracker eq 'y'}
                        {tr}(Currently set){/tr}
                    {else}
                        {tr}(Currently not set){/tr}
                    {/if}
                </div>
            </div>

            {if $prefs.feature_groupalert eq 'y'}
                <div class="mb-3 mx-0">
                    <label class="col-form-label" for="groupforAlert">{tr}Group alerted on item modification{/tr}</label>
                    <select name="groupforAlert" id="groupforAlert" class="form-control">
                        <option value=""></option>
                        {foreach from=$groupList item=g}
                            <option value="{$g|escape}" {if $g eq $groupforAlert}selected="selected"{/if}>{$g|escape}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="showeachuser" id="showeachuser" value="1"
                        {if $showeachuser eq 'y'}checked="checked"{/if}>
                    <label for="showeachuser">{tr}Allow user selection for small groups{/tr}</label>
                </div>
            {/if}
        {/accordion_group}
        {accordion_group title="{tr}Permissions{/tr}" accordion_pos=$accordion_pos}
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="userCanSeeOwn" id="userCanSeeOwn" value="1"
                                           {if $info.userCanSeeOwn eq 'y'}checked="checked"{/if}>
                <label class="form-check-label" for="userCanSeeOwn">
                    {tr}User can see his own items{/tr}
                </label>
                <div class="description form-text mb-4">
                    {tr}The tracker needs a user field with the item-owner activated{/tr}.
                    {tr}No extra permission is needed at the tracker permissions level to allow a user to see just his own items through Plugin TrackerList with the param view=user{/tr}
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="groupCanSeeOwn" id="groupCanSeeOwn" value="1"
                                           {if $info.groupCanSeeOwn eq 'y'}checked="checked"{/if}>
                <label class="form-check-label" for="groupCanSeeOwn">
                    {tr}Group can see their own items{/tr}
                </label>
                <div class="description form-text mb-4">
                    {tr}The tracker needs a group field with the item-owner activated{/tr}.
                    {tr}No extra permission is needed at the tracker permissions level to allow a group of users to see just their own items through Plugin TrackerList with the param view=group{/tr}
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="writerCanModify" id="writerCanModify" value="1"
                                           {if $info.writerCanModify eq 'y'}checked="checked"{/if}>
                <label class="form-check-label" for="writerCanModify">
                    {tr}Item creator can modify his items{/tr}
                </label>
                <div class="description form-text mb-4">
                    {tr}The tracker needs a user field with the auto-assign activated{/tr}
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="writerCanRemove" id="writerCanRemove" value="1"
                                           {if $info.writerCanRemove eq 'y'}checked="checked"{/if}>
                <label class="form-check-label" for="writerCanRemove">
                    {tr}Item creator can remove his items{/tr}
                </label>
                <div class="description form-text mb-4">
                    {tr}The tracker needs a user field with the auto-assign activated{/tr}
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="userCanTakeOwnership" id="userCanTakeOwnership" value="1"
                                           {if $info.userCanTakeOwnership eq 'y'}checked="checked"{/if}>
                <label class="form-check-label" for="userCanTakeOwnership">
                    {tr}User can take ownership of item created by anonymous{/tr}
                </label>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="oneUserItem" id="oneUserItem" value="1"
                                           {if $info.oneUserItem eq 'y'}checked="checked"{/if}>
                <label class="form-check-label" for="oneUserItem">
                    {tr}Only one item per user or IP{/tr}
                </label>
                <div class="description form-text mb-4">
                    {tr}The tracker needs a user or IP address field with the auto-assign set to Creator{/tr}
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="writerGroupCanModify" id="writerGroupCanModify" value="1"
                                           {if $info.writerGroupCanModify eq 'y'}checked="checked"{/if}>
                <label class="form-check-label" for="writerGroupCanModify">
                    {tr}Members of the creator group can modify items{/tr}
                </label>
                <div class="description form-text mb-4">
                    {tr}The tracker needs a group field with the auto-assign activated{/tr}
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="writerGroupCanRemove" id="writerGroupCanRemove" value="1"
                                           {if $info.writerGroupCanRemove eq 'y'}checked="checked"{/if}>
                <label class="form-check-label" for="writerGroupCanRemove">
                    {tr}Members of the creator group can remove items{/tr}
                </label>
                <div class="description form-text mb-4">
                    {tr}The tracker needs a group field with the auto-assign activated{/tr}
                </div>
            </div>
            <div class="form-check">
                {$adminOnlyViewEditItem_is_checked = 'n'}
                {if $info.adminOnlyViewEditItem eq 'y'|| (empty($info.adminOnlyViewEditItem) && $prefs.tracker_adminonlyviewedititem_by_default eq 'y')} {* No value in $info.adminOnlyViewEditItem means tracker creation *}
                    {$adminOnlyViewEditItem_is_checked = 'y'}
                {/if}
                <input type="checkbox" class="form-check-input" name="adminOnlyViewEditItem" id="adminOnlyViewEditItem" value="1"
                    {if $adminOnlyViewEditItem_is_checked eq 'y'} checked="checked"{/if}>
                <label class="form-check-label" for="adminOnlyViewEditItem">
                    {tr}Restrict non admins to wiki page access only{/tr}
                </label>
                <div class="description form-text mb-4">
                    {tr}Only users with admin tracker permission (tiki_p_admin_trackers) can use the built-in tracker interfaces (tiki-view_tracker.php and tiki-view_tracker_item.php). This is useful if you want the users of these trackers to only access them via wiki pages, where you can use the various tracker plugins to embed forms and reports.{/tr}
                </div>
            </div>
            <fieldset>
                <legend>{tr}Creation date constraint{/tr}</legend>
                <div class="description form-text mb-4">
                    {tr}The tracker will be <strong>open</strong> for non-admin users through wiki pages with PluginTracker <strong>only</strong> during the period 'After' the start date and/or 'Before' the end date set below{/tr}.
                </div>
                <div class="mb-3 mx-0 depends" data-on="start">
                    <label for="startDate">{tr}Date{/tr}</label>
                    <input type="date" name="startDate" id="startDate" value="{$startDate|escape}" class="form-control">
                </div>
                <div class="mb-3 mx-0 depends" data-on="start">
                    <label for="startTime">{tr}Time{/tr}</label>
                    <input type="time" name="startTime" id="startTime" value="{$startTime|default:'00:00'|escape}" class="form-control">
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="end" id="end" value="1"
                                               {if !empty($info.end)}checked="checked"{/if}>
                    <label class="form-check-label" for="end">
                        {tr}Before{/tr}
                    </label>
                </div>
                <div class="mb-3 mx-0 depends" data-on="end">
                    <label for="endDate">{tr}Date{/tr}</label>
                    <input type="date" name="endDate" id="endDate" value="{$endDate|escape}" class="form-control">
                </div>
                <div class="mb-3 mx-0 depends" data-on="end">
                    <label for="endTime">{tr}Time{/tr}</label>
                    <input type="time" name="endTime" id="endTime" value="{$endTime|default:'00:00'|escape}" class="form-control">
                </div>
            </fieldset>
        {/accordion_group}
        {if $prefs.feature_categories eq 'y'}
            {accordion_group title="{tr}Categories{/tr}" accordion_pos=$accordion_pos}
                <div class="mb-3 mx-0">
                    {include file='categorize.tpl' notable=y auto=y}
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="autoCreateCategories" id="autoCreateCategories" value="1"
                                               {if $info.autoCreateCategories eq 'y'}checked="checked"{/if}>
                    <label class="form-check-label" for="autoCreateCategories">
                        {tr}Auto-create corresponding categories{/tr}
                    </label>
                </div>
            {/accordion_group}
        {/if}
        {if $prefs.groupTracker eq 'y'}
            {accordion_group title="{tr}Groups{/tr}"}
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="autoCreateGroup" id="autoCreateGroup" value="1"
                            {if $info.autoCreateGroup eq 'y'} checked="checked"{/if}>
                    <label class="form-check-label" for="autoCreateGroup">
                        {tr}Create a group for each item{/tr}
                    </label>
                </div>
                <div class="mb-3 mx-0 depends" data-on="autoCreateGroup">
                    <label for="autoCreateGroupInc">{tr}Groups will include{/tr}</label>
                    <select name="autoCreateGroupInc" id="autoCreateGroupInc" class="form-control">
                        <option value=""></option>
                        {foreach from=$groupList item=g}
                            <option value="{$g|escape}" {if $g eq $info.autoCreateGroupInc}selected="selected"{/if}>{$g|escape}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-check depends" data-on="autoCreateGroup">
                    <input type="checkbox" class="form-check-input" name="autoAssignCreatorGroup" id="autoAssignCreatorGroup" value="1"
                            {if $info.autoAssignCreatorGroup eq 'y'} checked="checked"{/if}>
                    <label class="form-check-label" for="autoAssignCreatorGroup">
                        {tr}Creator is assigned to the group{/tr}
                    </label>
                </div>
                <div class="form-check depends" data-on="autoCreateGroup">
                    <input type="checkbox" class="form-check-input" name="autoAssignCreatorGroupDefault" id="autoAssignCreatorGroupDefault" value="1"
                            {if $info.autoAssignCreatorGroupDefault eq 'y'} checked="checked"{/if}>
                    <label class="form-check-label" for="autoAssignCreatorGroupDefault">
                        {tr}Will become the creator's default group{/tr}
                    </label>
                </div>
                <div class="form-check depends" data-on="autoCreateGroup">
                    <input type="checkbox" class="form-check-input" name="autoAssignGroupItem" id="autoAssignGroupItem" value="1"
                            {if $info.autoAssignGroupItem eq 'y'} checked="checked"{/if}>
                    <label class="form-check-label" for="autoAssignGroupItem">
                        {tr}Will become the new item's group creator{/tr}
                    </label>
                </div>
                <div class="form-check depends" data-on="autoCreateGroup">
                    <input type="checkbox" class="form-check-input" name="autoCopyGroup" id="autoCopyGroup" value="1"
                            {if $info.autoCopyGroup eq 'y'} checked="checked"{/if}>
                    <label class="form-check-label" for="autoCopyGroup">
                        {tr}Copy the default group in the field ID before updating the group{/tr}
                    </label>
                </div>
            {/accordion_group}
        {/if}
        {if $prefs.tracker_tabular_enabled eq 'y' and $remoteTabulars}
            {accordion_group title="{tr}Remote synchronization{/tr}" accordion_pos=$accordion_pos}
                <div class="mb-3 mx-0">
                    <label for="tabularSync">{tr}Choose import-export format(s){/tr}</label>
                    <select name="tabularSync" id="tabularSync" class="form-control" multiple="multiple">
                        <option value="">{tr}None{/tr}</option>
                        {assign var="tabularSyncArray" value=","|explode:$info.tabularSync}
                        {foreach item=tabular from=$remoteTabulars}
                            <option value="{$tabular.tabularId|escape}"
                                {if in_array($tabular.tabularId, $tabularSyncArray)} selected="selected"{/if}>
                                {$tabular.name|escape}
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="mb-3 depends" data-on="tabularSync">
                    <label for="tabularSyncModifiedField">
                        {tr}Last revision/modification field{/tr}
                        <a class="tikihelp text-info" title="{tr}Field selection:{/tr} {tr}Choose one of the tracker fields if remote items update its value every time a change happens. This will ensure only updated items get synchronized when importing from remote source.{/tr}">
                            {icon name=information}
                        </a>
                    </label>
                    <div style="width: 100%">
                        {object_selector type=trackerfield tracker_id=$info.trackerId _simplevalue=$info.tabularSyncModifiedField _simplename="tabularSyncModifiedField"}
                    </div>
                </div>
                <div class="mb-3 depends" data-on="tabularSyncModifiedField">
                    <label for="tabularSyncLastImport">
                        {tr}Last import time{/tr}
                        <a class="tikihelp text-info" title="{tr}Time entry:{/tr} {tr}This tracks the last date/time when this tracker was synchronized with remote source. Subsequent import-export imports will only fetch content newer than this date. Reset to something in the past if you want to re-import.{/tr}">
                            {icon name=information}
                        </a>
                    </label>
                    <div style="width: 100%">
                        {if empty($info.tabularSyncLastImport)}{$tabularSyncLastImport="0"}{else}{$tabularSyncLastImport=$info.tabularSyncLastImport}{/if}
                        {jscalendar date=$tabularSyncLastImport fieldname="tabularSyncLastImport" showtime='y' timezone=$displayTimezone}
                    </div>
                </div>
                <div class="mb-3 form-check depends" data-on="tabularSyncModifiedField">
                    <input type="checkbox" class="form-check-input" name="tabularSyncLastImportSkipUpdate" id="tabularSyncLastImportSkipUpdate" value="1" {if !empty($info.tabularSyncLastImportSkipUpdate) && $info.tabularSyncLastImportSkipUpdate eq 'y'} checked="checked"{/if}>
                    <label for="tabularSyncLastImportSkipUpdate">
                        {tr}Skip last import time update{/tr}
                        <a class="tikihelp text-info" title="{tr}Choice:{/tr} {tr}By default, last import time gets reset every time an import happens. Check this option if you want the last import time to be static and kept what is entered above.{/tr}">
                            {icon name=information}
                        </a>
                    </label>
                </div>
            {/accordion_group}
        {/if}
        {accordion_group title="{tr}Enable special behaviours{/tr}"}
            <div class="mb-3 mx-0">
                <label for="relationshipBehaviour">{tr}This tracker can be used as relationship metadata with behaviour{/tr}</label>
                <select name="relationshipBehaviour" id="relationshipBehaviour" class="form-control">
                    <option value="">{tr}None{/tr}</option>
                    {foreach item=key from=$relationshipBehaviourList}
                        <option value="{$key|escape}"
                            {if $key eq $info.relationshipBehaviour} selected="selected"{/if}>
                            {$key|escape}
                        </option>
                    {/foreach}
                </select>
            </div>
        {/accordion_group}
    {/accordion}
    <div class="mb-3 mx-0 submit">
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="trackerId" value="{$trackerId|escape}">
        <input type="submit" class="btn btn-primary" value="{tr}Save{/tr}">
    </div>
</form>
    {/block}
