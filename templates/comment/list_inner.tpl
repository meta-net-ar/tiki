<ul class="list-unstyled">
    {foreach from=$comments item=comment}
        {* Determine if this is a top-level resolved thread *}
        {assign var="is_top_level" value=(!$level || $level eq 0)}
        {assign var="is_resolved_thread" value=($is_top_level && isset($comment.is_resolved) && $comment.is_resolved eq 'y' && $prefs.comments_resolved_threads eq 'y')}
        <li class="d-flex comment mt-3 mb-4{if $comment.archived eq 'y'} archived{* well well-sm*}{/if} {if $allow_moderate}{if $comment.approved eq 'n'} pending bg-warning{elseif $comment.approved eq 'r'} rejected bg-danger{/if}{/if}{*{if ! $parentId && $prefs.feature_wiki_paragraph_formatting eq 'y'} inline{/if}*}{if $is_resolved_thread} comment-resolved comment-thread-wrapper{/if}" data-comment-thread-id="{$comment.threadId|escape}" id="threadId{$comment.threadId|escape}" {if $is_resolved_thread}data-resolved="true"{/if}>
            <div class="align-self-start me-3">
                <span class="avatar">{$comment.userName|avatarize:'':'img/noavatar.png'}</span>
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="comment-item">
                    <h4 class="mt-0">
                        {if $prefs.comments_notitle neq 'y'}
                            <div class="comment-title">
                                {$comment.title}
                                {if $prefs.comments_heading_links eq 'y'}
                                    <button type="button" class="heading-link copy-comment-link tips btn btn-link p-0" title="|{tr}Click to copy the comment link{/tr}" aria-label="{tr}Heading link{/tr}" data-thread-id="threadId{$comment.threadId|escape}">{icon name="link" iclass="me-1"}</button>
                                {/if}
                            </div>
                        {/if}
                        <div class="comment-info">
                            {if $is_resolved_thread}
                                <span class="badge bg-success me-2">{icon name="check"} {tr}Resolved{/tr}</span>
                            {/if}
                            {tr _0=$comment.userName|userlink}%0{/tr}{if $prefs.comments_threshold_indent neq '0' && $level && $level gte $prefs.comments_threshold_indent}>{tr _0=$repliedTo.userName|userlink}%0{/tr}{/if} <small class="date">{tr _0=$comment.commentDate|tiki_short_datetime}%0{/tr}</small>
                            {if $prefs.comments_heading_links eq 'y' and  $prefs.comments_notitle eq 'y'}
                                <button type="button" class="heading-link copy-comment-link tips btn btn-link p-0" title="|{tr}Click to copy the comment link{/tr}" aria-label="{tr}Heading link{/tr}" data-thread-id="threadId{$comment.threadId|escape}">{icon name="link" iclass="me-1"}</button>
                            {/if}
                        </div>
                    </h4>
                    <div class="comment-body">
                        {if isset($repliedTo) && $repliedTo.parsed && $level >= $prefs.comments_threshold_indent && $prefs.comments_threshold_indent ne 0}
                            <span class="d-flex ps-2 border-start border-5 comment-replied-to">
                                {$parentExcerpt=$repliedTo.parsed|strip_tags|truncate:15}
                                {tr _0="<em class='ms-1'>$parentExcerpt</em>"}In reply to %0{/tr}
                            </span>
                        {/if}
                        {$comment.parsed}
                    </div>
                    <div class="buttons comment-form comment-footer mt-2">
                        {block name="buttons"}
                            {if $allow_post && $comment.locked neq 'y'}
                                <a class='btn btn-primary btn-sm' href="{service controller=comment action=post type=$type objectId=$objectId parentId=$comment.threadId}">{tr}Reply{/tr}</a>
                            {/if}
                            {if !empty($comment.can_edit)}
                                <a class='btn btn-secondary btn-sm' href="{service controller=comment action=edit threadId=$comment.threadId}">{tr}Edit{/tr}</a>
                            {/if}
                            {if $allow_remove}
                                <a class="btn btn-danger btn-sm" href="{service controller=comment action=remove threadId=$comment.threadId}">{tr}Delete{/tr}</a>
                            {/if}
                            {if $allow_archive}
                                {if $comment.archived eq 'y'}
                                    <span class="label label-primary">{tr}Archived{/tr}</span>
                                    <a class="btn btn-info btn-sm" href="{service controller=comment action=archive do=unarchive threadId=$comment.threadId}">{tr}Unarchive{/tr}</a>
                                {else}
                                    <a class="btn btn-warning btn-sm" href="{service controller=comment action=archive do=archive threadId=$comment.threadId}">{tr}Archive{/tr}</a>
                                {/if}
                            {/if}
                            {if $allow_resolve && $is_top_level}
                                {if isset($comment.is_resolved) && $comment.is_resolved eq 'y'}
                                    <button type="button" class="btn btn-outline-success btn-sm resolve-direct" data-url="{service controller=comment action=resolve do=unresolve threadId=$comment.threadId}" title="{tr}Mark as unresolved{/tr}">{icon name="undo"} {tr}Unresolve{/tr}</button>
                                {elseif $comment.replies_info.numReplies gt 0}
                                    <button type="button" class="btn btn-success btn-sm resolve-direct" data-url="{service controller=comment action=resolve do=resolve threadId=$comment.threadId}" title="{tr}Mark as resolved{/tr}">{icon name="check"} {tr}Resolve{/tr}</button>
                                {/if}
                            {/if}
                        {/block}
                        {if $allow_moderate and $comment.approved neq 'y'}
                            {if $comment.approved eq 'n'}
                                <span class="label label-warning">{tr}Pending{/tr}</span>
                            {/if}
                            {if $comment.approved eq 'r'}
                                <span class="label label-danger">{tr}Rejected{/tr}</span>
                            {/if}
                            <a href="{service controller=comment action=moderate do=approve threadId=$comment.threadId}" class="btn btn-primary btn-sm tips" title="{tr}Approve{/tr}">{icon name="ok"}</a>
                            {if $comment.approved eq 'n'}
                                <a href="{service controller=comment action=moderate do=reject threadId=$comment.threadId}" class="btn btn-danger btn-sm tips" title="{tr}Reject{/tr}">{icon name="remove"}</a>
                            {/if}
                        {/if}
                        {if $comment.userName ne $user and $comment.approved eq 'y' and $allow_vote}
                            <div class="commentRating d-inline-block ms-3">
                                <form class="commentRatingForm" method="post">
                                    <fieldset>
                                        <legend class="fs-6">{tr}Rate this comment:{/tr}</legend>
                                        {rating type="comment" id=$comment.threadId}
                                        <input type="hidden" name="id" value="{$comment.threadId}" />
                                        <input type="hidden" name="type" value="comment" />
                                    </fieldset>
                                </form>
                                {jq}
                                    var crf = $('form.commentRatingForm').on("submit", function() {
                                        var vals = $(this).serialize();
                                        $.tikiModal(tr('Loading...'));
                                        $.post($.service('rating', 'vote'), vals, function() {
                                            $.tikiModal();
                                            showMessage(tr('Thanks for rating!'), "success");
                                        });
                                        return false;
                                    });
                                {/jq}
                            </div>
                        {/if}
                        {if $prefs.wiki_comments_simple_ratings eq 'y' && ($tiki_p_ratings_view_results eq 'y' or $tiki_p_admin eq 'y')}
                            {rating_result type="comment" id=$comment.threadId}
                        {/if}
                        {if !empty($comment.diffInfo)}
                            <div class="{*well*}">
                                <h4 class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target=".version{$comment.diffInfo[0].version}" aria-expanded="false" aria-controls="collapseExample">
                                    {tr}Version{/tr} {$comment.diffInfo[0].version}
                                    {icon name='history'}
                                </h4>
                                <div class="collapse table-responsive version{$comment.diffInfo[0].version}">
                                    {foreach $comment.diffInfo as $info}
                                        {if $info.fieldId eq HISTLIB_INVALID_FIELDID_THAT_MEANS_TRACKER_ITEM_STATUS_CHANGE}
                                            <label>{tr}Status{/tr}</label>: {$info.value} -> {$info.new}
                                        {elseif !empty($info.isDeletedField)}
                                            <label>{$info.fieldName}</label>: {$info.value|escape}{if empty($info.newValueUnavailable)} -> {$info.new|escape}{/if}
                                        {else}
                                            <label>{$info.fieldName}</label>
                                            {trackeroutput fieldId=$info.fieldId list_mode='y' history=y process=y oldValue=$info.value value=$info.new diff_style='sidediff'}
                                        {/if}
                                    {/foreach}
                                </div>
                            </div>
                        {/if}
                    </div>{* End of comment-footer *}
                </div>{* End of comment-item *}
                {if ! $level || $prefs.comments_threshold_indent eq '0' || $level lt $prefs.comments_threshold_indent}
                    {if $comment.replies_info.numReplies gt 0}
                        {if $is_resolved_thread}
                            <div class="comment-resolved-header d-flex align-items-center mt-3 mb-2 p-2 rounded bg-light border" role="button" data-bs-toggle="collapse" data-bs-target="#comment-thread-body-{$comment.threadId|escape}" aria-expanded="false" aria-controls="comment-thread-body-{$comment.threadId|escape}">
                                <span class="text-muted small flex-grow-1">
                                    {icon name="comments"} {tr}Replies{/tr}
                                </span>
                                <span class="comment-collapse-icon ms-2">{icon name="chevron-right"}</span>
                            </div>
                            <div class="collapse" id="comment-thread-body-{$comment.threadId|escape}">
                        {/if}

                        {include file='comment/list_inner.tpl' comments=$comment.replies_info.replies count=$comment.replies_info.numReplies parentId=$comment.threadId level=(level) ? $level+1 : 0 repliedTo=$comment}

                        {if $is_resolved_thread}
                            </div>
                        {/if}
                    {/if}
                {/if}
            </div>{* End of flex-grow-1 ms-3 *}
        </li>
        {if $prefs.comments_threshold_indent neq '0' && $level && $level gte $prefs.comments_threshold_indent}
            {if $comment.replies_info.numReplies gt 0}
                {include file='comment/list_inner.tpl' comments=$comment.replies_info.replies count=$comment.replies_info.numReplies parentId=$comment.threadId level=(level) ? $level+1 : 0 repliedTo=$comment}
            {/if}
        {/if}
    {/foreach}
</ul>
{pagination_links count=$count step=$maxRecords offset=$offset offset_jsvar='comment_offset' _onclick=$paginationOnClick}{/pagination_links}
