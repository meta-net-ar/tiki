{* Note: if you edit this file, make sure to make corresponding edits on tiki-edit_submission.tpl*}
{include file='tiki-articles-js.tpl'}
{title help="Articles" admpage="articles"}
    {if $articleId}
        {tr}Edit:{/tr} {$arttitle}
    {else}
        {tr}Edit article{/tr}
    {/if}
{/title}
<div class="t_navbar mb-4">
    {button href="tiki-list_articles.php" _type="link" class="btn btn-link" _icon_name="list" _text="{tr}List Articles{/tr}"}
    {button href="tiki-view_articles.php" _type="link" class="btn btn-link" _icon_name="articles" _text="{tr}View Articles{/tr}"}
</div>
{if $preview}
    <h2>{tr}Preview{/tr}</h2>
    {include file='article.tpl'}
{/if}
<form enctype="multipart/form-data" method="post" action="tiki-edit_article.php" id='editpageform'>
    {ticket}
    <input type="hidden" name="articleId" value="{$articleId|escape}">
    <input type="hidden" name="previewId" value="{$previewId|escape}">
    <input type="hidden" name="imageIsChanged" value="{$imageIsChanged|escape}">
    <input type="hidden" name="image_data" value="{$image_data|escape}">
    <input type="hidden" name="useImage" value="{$useImage|escape}">
    <input type="hidden" name="image_type" value="{$image_type|escape}">
    <input type="hidden" name="image_name" value="{$image_name|escape}">
    <input type="hidden" name="image_size" value="{$image_size|escape}">
    {if !empty($translationOf)}
        <input type="hidden" name="translationOf" value="{$translationOf|escape}">
    {/if}
    {tabset}
        {tab name="{tr}Content{/tr}"}
            <h2>{tr}Content{/tr}</h2>
            <div class="mb-3 mx-0">
                <label for="title">{tr}Title{/tr}</label>
                <input type="text" name="title" id="title" value="{$arttitle|escape}" maxlength="255" class="form-control">
            </div>
            <div class="mb-3 mx-0">
                <label for="subheading">{tr}Heading{/tr}</label>
                {if $types.$type.heading_only eq 'y'}
                    {textarea name="heading" rows="5" class="form-control" id="subheading"}{$heading}{/textarea}
                {else}
                    {textarea _simple="y" name="heading" class="form-control" rows="5" id="subheading" comments="y"}{$heading}{/textarea}
                {/if}
            </div>
            <div id='heading_only' class="mb-3 mx-0"{if $types.$type.heading_only eq 'y'}style="display: none;"{/if}>
                <label for="body">{tr}Body{/tr}</label>
                {textarea name="body" id="body" _preview=$prefs.ajax_edit_previews}{$body}{/textarea}
            </div>
            {if $tiki_p_use_HTML eq 'y'}
                {if $smarty.session.wysiwyg neq 'y'}
                    <div class="mb-3 mx-0">
                        <div class="form-check">
                            <label class="form-check-label">
                                <input class="form-check-input" type="checkbox" name="allowhtml"{if $allowhtml eq 'y'}checked="checked"{/if}>
                                {tr}Allow full HTML{/tr} <em>({tr}Keep any HTML tag.{/tr})</em>
                            </label>
                            <div class="form-text">{tr}If not enabled, Tiki will retain some HTML tags (a, p, pre, img, hr, b, i){/tr}.</div>
                        </div>
                    </div>
                {else}
                    <input type="hidden" name="allowhtml" value="{if $allowhtml eq 'y'}on{/if}">
                {/if}
            {/if}
            {if $prefs.feature_multilingual eq 'y'}
                <div class="mb-3 row">
                    <label class="col-md-4 col-form-label" for="lang">{tr}Language{/tr}</label>
                    <div class="col-md-8">
                        <select name="lang" id="lang" class="form-control">
                            <option value="">{tr}All{/tr}</option>
                            {section name=ix loop=$languages}
                                <option value="{$languages[ix].value|escape}"{if $lang eq $languages[ix].value} selected="selected"{/if}>{$languages[ix].name}</option>
                            {/section}
                        </select>
                        {if $articleId != 0}
                            <span class="form-text">
                                {tr _0="tiki-edit_article.php?translationOf=$articleId"}To translate, do not change the language and the content. Instead, <a class="alert-link" href="%0">create a new translation</a> in the new language.{/tr}
                            </span>
                            {if not empty($translations[1].objId)}
                                {remarksbox type=tip title="{tr}Translations{/tr}"}
                                    <ul>
                                        <li>
                                            {section loop=$translations name=t}
                                                {if $articleId != $translations[t].objId}
                                                    {$translations[t].lang|escape}: <a href="tiki-edit_article.php?articleId={$translations[t].objId|escape}" class="alert-link">{$translations[t].objName|escape}</a><br>
                                                {/if}
                                            {/section}
                                        </li>
                                    </ul>
                                {/remarksbox}
                            {/if}
                        {/if}
                    </div>
                </div>
            {/if}
        {/tab}
        {tab name="{tr}Classification{/tr}"}
            <h2>{tr}Classification{/tr}</h2>
            <div class="mb-3 row clearfix">
                <label for="topicId" class="col-form-label col-md-4">{tr}Topic{/tr}</label>
                <div class="col-md-6">
                    <select name="topicId" id="topicId" class="form-control">
                        {foreach $topics as $topic}
                            <option value="{$topic.topicId|escape}"{if $topicId eq $topic.topicId}selected="selected"{/if}>{$topic.name|escape}</option>
                        {/foreach}
                        <option value=""{if $topicId eq 0}selected="selected"{/if}>{tr}None{/tr}</option>
                    </select>
                </div>
                {if $tiki_p_admin_cms eq 'y'}
                    <span class="col-md-2">
                        <a href="tiki-admin_topics.php" class="btn btn-link">
                            {icon name="flag"} {tr}Article Topics{/tr}
                        </a>
                    </span>
                {/if}
            </div>
            <div class="mb-3 row clearfix">
                <label for="articletype" class="col-form-label col-md-4">{tr}Type{/tr}</label>
                <div class="col-md-6">
                    <select id='articletype' name="type" onchange='javascript:chgArtType();' class="form-control">
                        {foreach $types as $typei => $prop}
                            <option value="{$typei|escape}"{if $type eq $typei}selected="selected"{/if}>{tr}{$typei|escape}{/tr}</option>
                        {/foreach}
                    </select>
                </div>
                {if $tiki_p_admin_cms eq 'y'}
                    <span class="col-md-2">
                        <a href="tiki-article_types.php" class="btn btn-link">
                            {icon name="structure"} {tr}Article Types{/tr}
                        </a>
                    </span>
                {/if}
            </div>
            <div class="clearfix">
                <div class="col-md-8 offset-md-4">
                    {remarksbox type="info" title="{tr}Hint{/tr}"}
                        {tr}Click "Preview" after selecting article type to have appropriate edit form fields.{/tr}
                    {/remarksbox}
                </div>
            </div>
            {include file='categorize.tpl'}
            <div class="mb-3 row clearfix">
                {include file='freetag.tpl'}
            </div>
        {/tab}
        {tab name="{tr}Publication{/tr}"}
            <h2>{tr}Publication{/tr}</h2>
            <div class="mb-3 row clearfix">
                <div class="col-md-4 offset-md-4">
                    <div class="form-check well well-sm">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="ispublished"{if $ispublished eq 'y' || !$articleId}checked="checked"{/if}>
                            <strong>{tr}Published{/tr}</strong>
                        </label>
                    </div>
                    <div class="form-text">
                        {tr}If checked, the article is published.{/tr}
                    </div>
                </div>
            </div>
            <div class="mb-3 row clearfix">
                <label for="authorName" class="col-form-label col-md-4">{tr}Author name (as displayed){/tr}</label>
                <div class="col-md-4">
                    <input type="text" name="authorName" id="authorName" value="{$authorName|escape}" class="form-control">
                </div>
            </div>
            <div class="mb-3 row{if $tiki_p_edit_article_user neq 'y'}hidden{/if} clearfix">
                <label for="author" class="col-form-label col-md-4">{tr}User (article owner){/tr}</label>
                <div class="col-md-4">
                    <input id="author" type="text" name="author" value="{$author|escape}" class="form-control">
                    {autocomplete element='#author' type='username'}
                </div>
            </div>
            <div class="mb-3 row clearfix">
                <label class="col-form-label col-md-4">{tr}Publish Date{/tr}</label>
                <div class="col-md-8">
                    {jscalendar date=$publishDate fieldname="publishDate" showtime='y' timezone=$displayTimezone}
                </div>
            </div>
            <div class="mb-3 row clearfix">
                <label class="col-form-label col-md-4">{tr}Expiration Date{/tr}</label>
                <div class="col-md-8">
                    {jscalendar date=$expireDate fieldname="expireDate" showtime='y' timezone=$displayTimezone}
                </div>
            </div>
        {/tab}
        {tab name="{tr}Image{/tr}"}
            <h2>{tr}Image{/tr}</h2>
            <div class="mb-3{if $types.$type.show_image neq 'y'}hidden{/if}">
                <input type="hidden" name="MAX_FILE_SIZE" value="{$prefs.article_image_file_size_max}">
                <label for="userfile1" class="col-form-label col-md-4">{tr}Own Image{/tr}</label>
                <div class="col-md-8">
                    <input class="form-control" name="userfile1" id="userfile1" type="file" accept="image/*" onchange="document.getElementById('useImage').checked = true;">
                    <span class="form-text">{tr}If not the topic image{/tr} - {tr}Max file size : {$prefs.article_image_file_size_max/1000} KB{/tr}</span>
                </div>
            </div>
            {if $hasImage eq 'y'}
                <div class="mb-3 row">
                    <label class="col-md-4 col-form-label">{tr}Current Image{/tr}</label>
                    <div class="thumbnail col-md-8">
                        {if $imageIsChanged eq 'y'}
                            <img class="img-fluid" alt="{tr}Article image{/tr}" src="article_image.php?image_type=preview&amp;id={$previewId}">
                        {else}
                            <img class="img-fluid" alt="{tr}Article image{/tr}" src="article_image.php?image_type=article&amp;id={$articleId}">
                        {/if}
                    </div>
                </div>
            {/if}
            <div class="mb-3{if $types.$type.show_image_caption neq 'y'}hidden{/if}">
                <label class="col-md-4 col-form-label" for="image_caption">{tr}Image caption{/tr}</label>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="image_caption" id="image_caption" value="{$image_caption|escape}" >
                    <div class="form-text">{tr}Default will use the topic name{/tr}</div>
                </div>
            </div>
            <div class="form-check{if $types.$type.show_image neq 'y'}hidden{/if} offset-md-4">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="useImage" id="useImage"{if $useImage eq 'y'}checked='checked'{/if} >
                    {tr}Use own image{/tr}
                </label>
            </div>
            <div class="form-check{if $types.$type.show_image neq 'y'}hidden{/if} offset-md-4">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="isfloat"{if $isfloat eq 'y'}checked='checked'{/if}>
                    {tr}Float text around image{/tr}
                </label>
            </div>
            <fieldset class="{if $types.$type.show_image neq 'y'}hidden{/if}">
                <legend>{tr}Read Article{/tr}</legend>
                <span class="form-text">{tr}Maximum dimensions of custom image in view mode{/tr}</span>
                <div class="mb-3 row">
                    <label for="image_x" class="col-form-label col-md-4">{tr}Width{/tr}</label>
                    <div class="input-group col-sm-3">
                        <input type="text" class="form-control" name="image_x" id="image_x"{if $image_x > 0} value="{$image_x|escape}"{/if}>
                        <span class="input-group-text">{tr}pixels{/tr}</span>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="image_y" class="col-form-label col-md-4">{tr}Height{/tr}</label>
                    <div class="input-group col-sm-3">
                        <input type="text" class="form-control" name="image_y" id="image_y"{if $image_y > 0} value="{$image_y|escape}"{/if}>
                        <span class="input-group-text">{tr}pixels{/tr}</span>
                    </div>
                </div>
            </fieldset>
            <fieldset class="{if $types.$type.show_image neq 'y'}hidden{/if}">
                <legend>{tr}View Articles{/tr}</legend>
                <span class="form-text">{tr}Maximum dimensions of custom image in list mode{/tr}</span>
                <div class="mb-3 row">
                    <label for="list_image_x" class="col-form-label col-sm-4">{tr}Width{/tr}</label>
                    <div class="input-group col-sm-3">
                        <input type="text" class="form-control" name="list_image_x" id="list_image_x"{if $list_image_x > 0} value="{$list_image_x|escape}"{/if}>
                        <span class="input-group-text">{tr}pixels{/tr}</span>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="list_image_y" class="col-form-label col-sm-4">{tr}Height{/tr}</label>
                    <div class="input-group col-sm-3">
                        <input type="text" class="form-control" name="list_image_y" id="list_image_y"{if $list_image_y > 0} value="{$list_image_y|escape}"{/if}>
                        <span class="input-group-text">{tr}pixels{/tr}</span>
                    </div>
                </div>
            </fieldset>
        {/tab}
        {tab name="{tr}Advanced{/tr}"}
            <h2>{tr}Advanced{/tr}</h2>
            {if $prefs.feature_multilingual eq 'y' and empty($translationOf)}
                <div class="mb-3 row clearfix">
                    <label for="translationOf" class="col-form-label col-md-8">
                        {tr}Attach existing article ID as translation{/tr}
                    </label>
                    <div class="col-md-8">
                        <select id='translationOf' name="translationOf" class="form-control">
                            <option value="">{tr}Select Article{/tr}</option>
                            {foreach $articles as $row}
                                {if $row['articleId'] neq $articleId}
                                    <option value="{$row['articleId']|escape}"{if not empty($translations[1].objId ) and $translations[1].objId eq $row['articleId']}selected="selected"{/if}>{tr}{$row['title']|escape|truncate:70:"..."}{/tr}</option>
                                {/if}
                            {/foreach}
                        </select>
                    </div>
                </div>
            {/if}
            <div class="mb-3{if $types.$type.show_topline neq 'y'}hidden{/if}">
                <label for="topline" class="col-form-label col-md-4">{tr}Topline{/tr}</label>
                <div class="col-md-8">
                    <input type="text" name="topline" id="topline" value="{$topline|escape}" class="form-control">
                </div>
            </div>
            <div class="mb-3{if $types.$type.show_subtitle neq 'y'}hidden{/if}">
                <label for="subtitle" class="col-form-label col-md-4">{tr}Subtitle{/tr}</label>
                <div class="col-md-8">
                    <input type="text" name="subtitle" id="subtitle" value="{$subtitle|escape}" class="form-control">
                </div>
            </div>
            <div class="mb-3{if $types.$type.show_linkto neq 'y'}hidden{/if}">
                <label for="linkto" class="col-form-label col-md-4">{tr}Source{/tr}</label>
                <div class="col-md-8">
                    <input type="url" name="linkto" id="linkto" value="{$linkto|escape}" class="form-control" placeholder="https://...">
                    {if $linkto neq ''}
                        <div class="form-text">
                            {tr}Test your link: {/tr}
                            <a href="{$linkto|escape}" target="_blank">{tr}View{/tr}</a>
                        </div>
                    {/if}
                </div>
            </div>
            <div class="mb-3{if $types.$type.use_ratings neq 'y'}hidden{/if}">
                <label for="rating" class="col-md-4 col-form-label">{tr}Author rating{/tr}</label>
                <div class="col-md-4">
                    <select name="rating" id="rating" class="form-control">
                        {foreach from=[10, 9.5, 9, 8.5, 8, 7.5, 7, 6.5, 6, 5.5, 5, 4.5, 4, 3.5, 3, 2.5, 2, 1.5, 1, 0.5, 0] item=option}
                            <option value="{$option}"{if $rating == $option}selected="selected"{/if}>{$option}</option>
                        {/foreach}
                    </select>

                </div>
            </div>
           {if $prefs.geo_locate_article eq 'y'}
                <div class="mb-3 row clearfix">
                    <label class="col-form-label col-md-4">{tr}Location{/tr}</label>
                    <div class="col-md-8">
                        <div class="map-container" data-geo-center="{defaultmapcenter}" data-target-field="geolocation"></div>
                        <input type="hidden" name="geolocation" value="{$geolocation_string|escape}">
                    </div>
                </div>
            {/if}
            {if $prefs.feature_cms_templates eq 'y' and $tiki_p_use_content_templates eq 'y' and $templates|@count ne 0}
                <div class="mb-3 row clearfix">
                    <label for="templateId" class="col-form-label col-md-4">{tr}Apply content template{/tr}</label>
                    <div class="col-md-8">
                        <select class="form-select" name="templateId" id="templateId" onchange="javascript:document.getElementById('editpageform').submit();">
                            <option value="0">{tr}none{/tr}</option>
                            {foreach $templates as $template}
                                <option value="{$template.templateId|escape}">{tr}{$template.name|escape}{/tr}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            {/if}
            {if $prefs.feature_cms_emails eq 'y'}
                <div class="mb-3 row">
                    <label for="emails" class="col-md-4">{tr}Email{/tr}</label>
                    <div class="col-md-8">
                        <input type="text" name="emails" id="emails" value="{$emails|escape}" class="form-control">
                        <span class="form-text">{tr}Email addresses to be sent notifications (comma-separated){/tr}</span>
                        {if !empty($userEmail) and $userEmail neq $prefs.sender_email}
                            {tr}From:{/tr}
                            <label>
                                <input type="radio" name="from" value="{$userEmail|escape}"{if empty($from) or $from eq $userEmail} checked="checked"{/if}>
                                {$userEmail|escape}
                            </label>
                            <label>
                                <input type="radio" name="from" value="{$prefs.sender_email|escape}"{if $from eq $prefs.sender_email} checked="checked"{/if}>
                                {$prefs.sender_email|escape}
                            </label>
                        {/if}
                    </div>
                </div>
            {/if}
            {if ! empty($all_attributes)}
                <fieldset>
                    <legend>{tr}Attributes{/tr}</legend>
                    {foreach from=$all_attributes item=att key=attname}
                        {assign var='attid' value=$att.itemId|replace:'.':'_'}
                        {assign var='attfullname' value=$att.itemId}
                        <div class="mb-3 row" id={$attid}{if $types.$type.$attid eq 'y'}style="display:;"{else}style="display:none;"{/if}>
                            <label class="col-form-label col-md-4" for="{$attfullname|escape}">{$attname|escape}</label>
                            <div class="col-md-8">
                                <input type="text" name="{$attfullname|escape}" value="{$article_attributes.$attfullname|escape}" maxlength="255" class="form-control">
                            </div>
                        </div>
                    {/foreach}
                </fieldset>
            {/if}
        {/tab}
    {/tabset}
    <div class="mb-3 clearfix text-center">
        <input type="submit" class="wikiaction btn btn-secondary" name="preview" value="{tr}Preview{/tr}" onclick="needToConfirm=false;">
        <input type="submit" class="wikiaction btn btn-primary" name="save" value="{tr}Save{/tr}" onclick="this.form.saving=true;needToConfirm=false;">
        {if $articleId}<input type="submit" class="wikiaction tips btn btn-link" title="{tr}Cancel{/tr}|{tr}Cancel the edit (changes will be lost).{/tr}" name="cancel_edit" value="{tr}Cancel Edit{/tr}" onclick="needToConfirm=false;">{/if}
    </div>
    {if $smarty.session.wysiwyg neq 'y'}
        {jq}
$("#editpageform").on("submit", function(evt) {
    var isHtml = false;
    if (this.saving && !$("input[name=allowhtml]:checked").length) {
        $("textarea", this).each(function(){
            if ($(this).val().match(/<([A-Z][A-Z0-9]*)\b[^>]*>(.*?)<\/\1>/i)) {
                isHtml = true;
            }
        });
        if (isHtml) {
            this.saving = false;
            return confirm(tr('You appear to be using HTML in your article but have not selected "Allow full HTML".\nThis will result in HTML tags being removed.\nDo you want to save your edits anyway?'));
        }
    }
    return true;
}).attr('saving', false);
        {/jq}
    {/if}
</form>
