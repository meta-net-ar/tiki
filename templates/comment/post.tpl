{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    {if $threadId}
        <div class="alert alert-success">
            {if $prefs.feature_comments_moderation eq 'y'}
                <p>{tr}Your message has been queued for approval and will be posted after a moderator approves it.{/tr}</p>
            {else}
                <p>{tr}Your comment was posted.{/tr}</p>
            {/if}
            <p>{tr}Go back to:{/tr} {object_link type=$type objectId=$objectId}</p>
        </div>
    {else}
        <form method="post" action="{service controller=comment action=post}">
            <div class="card">
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
                            <input type="text" id="comment-title" name="title" value="{$title|escape}" class="form-control check_character_limit" placeholder="Comment title" maxlength="{$max_comment_title_length}"/>
                        </div>
                    {/if}
                    {capture name=rows}{if $type eq 'forum'}{$prefs.default_rows_textarea_forum}{else}{$prefs.default_rows_textarea_comment}{/if}{/capture}
                    {textarea codemirror='true' name="data" comments="y" maxlength="{$max_comment_data_length}" section=$type objectId=$objectId _wysiwyg="n" rows=$smarty.capture.rows class="form-control wikiedit check_character_limit" placeholder="{tr}Post new comment{/tr}..." _preview=$prefs.ajax_edit_previews}{$data|escape}{/textarea}
                    {if  $user and $prefs.feature_user_watches eq 'y'}
                        <div class="form-check">
                            <input id="watch_thread" type="checkbox" class="form-check-input" name="watch" value="y"{if $smarty.request.watch eq 'y'} checked="checked"{/if}>
                            <label for="watch_thread" class="form-check-label">{tr}Send me an email when someone replies{/tr}</label>
                        </div>
                    {/if}
                </div>
                <div class="card-footer">
                    {if $prefs.feature_antibot eq 'y'}
                        {$showmandatory='y'}
                        {include file='antibot.tpl'}
                    {/if}
                    <input type="hidden"  name="return_url" value="{$return_url|escape}">
                    {if empty($version)}
                        <div class="mb-3 comment-post">
                            <input type="submit" {if $prefs.feature_antibot eq 'y' && $user eq '' && $prefs.recaptcha_enabled eq 'y' && $prefs.recaptcha_version eq '3'}onclick="genToken();"{/if} class="comment-post btn btn-primary" value="{tr}Post{/tr}"/>
                            <div class="btn btn-link">
                                <a href="#" onclick="$(this).closest('.comment-container').reload(); $(this).closest('.ui-dialog').remove(); return false;" role="button">{tr}Cancel{/tr}</a>
                            </div>
                        </div>
                    {else}
                        {if $diffInfo}
                            <div class="card bg-body-tertiary">
                                <div class="card-body">
                                    {foreach $diffInfo as $info}
                                        {if $info.fieldId eq -1}
                                            <label>{tr}Status{/tr}</label>: {$info.value} -> {$info.new}
                                        {else}
                                            <label>{$info.fieldName}</label>
                                            {trackeroutput fieldId=$info.fieldId list_mode='y' history=y process=y oldValue=$info.value value=$info.new diff_style='sidediff'}
                                        {/if}
                                    {/foreach}
                                </div>
                            </div>
                        {/if}
                        <div class="submit">
                            <input type="hidden" name="version" value="{$version|escape}"/>
                            <input type="submit" class="comment-post btn btn-primary" value="{tr}Post{/tr}"/>
                        </div>
                    {/if}
                </div>
            </div>
        </form>
    {/if}
    {if $prefs.feature_syntax_highlighter eq 'y'}
        {jq}
            //Synchronize textarea and codemirror before comment is posted
            $(".comment-form>form, .add-comment-zone>form").on("submit", function(event){
                var $textarea = $(event.target).find("textarea.wikiedit"); //retrieve the text area from the form that is submitted
                if (typeof syntaxHighlighter.sync === 'function') {
                    syntaxHighlighter.sync($textarea);
                }
            });
        {/jq}
    {/if}
{/block}
