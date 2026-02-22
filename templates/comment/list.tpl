{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    <h3>{tr}Comments{/tr}
        <span class="lock">
            {if ! $parentId && $allow_lock}
                <a href="{bootstrap_modal controller=comment action=lock type=$type objectId=$objectId}" class="btn btn-link btn-sm tips" title="{tr}Comments unlocked:{/tr}{tr}Lock comments{/tr}" role="button">
                    {icon name="unlock"}
                </a>
            {/if}
            {if ! $parentId && $allow_unlock}
                <a href="{bootstrap_modal controller=comment action=unlock type=$type objectId=$objectId}" class="btn btn-link btn-sm tips" title="{tr}Comments locked:{/tr}{tr}Unlock comments{/tr}" role="button">
                    {icon name="lock"}
                </a>
            {/if}
        </span>
    </h3>
{/block}

{block name="content"}
    {if $allow_post and $prefs.comments_sort_mode eq 'commentDate_desc'}
        <div class="submit">
            <div class="buttons comment-form {if $prefs.wiki_comments_form_displayed_default eq 'y'}autoshow{/if}">
                <a class="btn btn-secondary custom-handling" href="{service controller=comment action=post type=$type objectId=$objectId}" data-target="#add-comment-zone-{$objectId|replace:' ':''|replace:',':''|escape:'attr'}">{tr}Post new comment{/tr}</a>
            </div>
        </div>
        <div id="add-comment-zone-{$objectId|replace:' ':''|replace:',':''|escape:'attr'}" class="add-comment-zone"></div>
    {/if}

    {if $count gt 0}
        {include file="comment/list_inner.tpl"}
        <script type="text/javascript">
            $(function() {
                $('#comment-container').applyColorbox();
            })
        </script>
    {else}
        {if $allow_unlock}
            {remarksbox type=warning}
                {icon name=lock}
                {if $section eq 'forums'}
                    {tr}Replies are locked for this topic.{/tr}
                {else}
                    {tr}Comments are locked for this content.{/tr}
                {/if}
            {/remarksbox}
        {elseif $allow_post}
            {remarksbox type=info}
                {if $section eq 'forums'}
                    {tr}No replies yet — Add your thoughts!{/tr}
                {else}
                    {tr}No comments yet — Add your thoughts!{/tr}
                {/if}
            {/remarksbox}
        {else}
            {remarksbox type=info}
                {if $section eq 'forums'}
                    {tr}No replies yet.{/tr}
                {else}
                    {tr}No comments yet.{/tr}
                {/if}
                {' '}
                <a href="tiki-login.php">{tr}Log in{/tr}</a>
                {if $prefs.allowRegister eq 'y'}
                    {tr}or{/tr} <a href="tiki-register.php">{tr}register{/tr}</a>
                {/if}
                {' '}
                {if $section eq 'forums'}
                    {tr}to reply!{/tr}
                {else}
                    {tr}to add your thoughts!{/tr}
                {/if}
            {/remarksbox}
        {/if}
    {/if}

    {if $allow_post and $prefs.comments_sort_mode neq 'commentDate_desc'}
        <div class="submit">
            <div class="buttons comment-form {if $prefs.wiki_comments_form_displayed_default eq 'y'}autoshow{/if}">
                <a class="btn btn-secondary custom-handling" href="{service controller=comment action=post type=$type objectId=$objectId}" role="button" data-target="#add-comment-zone-{$objectId|replace:' ':''|replace:',':''|escape:'attr'}">{tr}Add a comment{/tr}</a>
            </div>
        </div>
        <div id="add-comment-zone-{$objectId|replace:' ':''|replace:',':''|escape:'attr'}" class="add-comment-zone"></div>
    {/if}

    <script type="text/javascript">
        var ajax_url = '{$base_url}';
        var objectId = '{$objectId|escape:'javascript'}';
    </script>
{/block}
