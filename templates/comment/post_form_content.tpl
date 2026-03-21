{if ! $user or $prefs.feature_comments_post_as_anonymous eq 'y'}
    <div class="card-header">
        {if $user}
            {remarksbox type=warning title="{tr}Anonymous posting{/tr}"}
                {tr}You are currently registered on this site. This section is optional. By filling it, you will not link this post to your account and preserve your anonymity.{/tr}
            {/remarksbox}
        {/if}
        <div class="d-flex flex-row flex-wrap align-items-center">
            <div class="mb-3">
                <label class="clearfix" for="comment-anonymous_name">{tr}Name{/tr}</label>
                <input type="text" name="anonymous_name" id="comment-anonymous_name" value="{$anonymous_name|escape}"/>
            </div>
            <div class="mb-3">
                <label class="clearfix" for="comment-anonymous_email">{tr}Email{/tr}</label>
                <input type="email" id="comment-anonymous_email" name="anonymous_email" value="{$anonymous_email|escape}"/>
            </div>
            <div class="mb-3">
                <label class="clearfix" for="comment-anonymous_website">{tr}Website{/tr}</label>
                <input type="url" id="comment-anonymous_website" name="anonymous_website" value="{$anonymous_website|escape}"/>
            </div>
        </div>
    </div>
{/if}
<div class="card-body">
    <input type="hidden" name="type" value="{$type|escape}"/>
    <input type="hidden" name="objectId" value="{$objectId|escape}"/>
    <input type="hidden" name="parentId" value="{$parentId|escape}"/>
    <input type="hidden" name="post" value="1"/>
    {if $prefs.comments_notitle neq 'y'}
        <div class="mb-3">
            <label for="comment-title" class="clearfix comment-title">{tr}Title{/tr}</label>
            <input type="text" id="comment-title" name="title" value="{$title|escape}" class="form-control" placeholder="{tr}Comment title{/tr}" maxlength="{$MAX_COMMENT_TITLE_LENGTH}">
        </div>
    {/if}
    {capture name=rows}{if $type eq 'forum'}{$prefs.default_rows_textarea_forum}{else}{$prefs.default_rows_textarea_comment}{/if}{/capture}
    {textarea codemirror='true' name="data" comments="y" maxlength="{$MAX_COMMENT_DATA_LENGTH}" section=$type objectId=$objectId _wysiwyg="n" rows=$smarty.capture.rows class="form-control wikiedit" placeholder="{tr}Post new comment{/tr}..." _preview=$prefs.ajax_edit_previews}{$data|escape}{/textarea}
    {if  $user and $prefs.feature_user_watches eq 'y'}
        <div class="form-check">
            <input id="watch_thread" type="checkbox" class="form-check-input" name="watch" value="y"{if $smarty.request.watch eq 'y'} checked="checked"{/if}>
            <label for="watch_thread" class="form-check-label">{tr}Send me an email when someone replies{/tr}</label>
        </div>
    {/if}
</div>
