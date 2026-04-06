{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
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
                {include file="comment/post_form_content.tpl"}
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
