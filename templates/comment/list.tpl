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

                {if $prefs.comments_resolved_threads eq 'y'}
                    function initResolvedThreads() {
                        var hash = window.location.hash;
                        $('.comment-thread-wrapper[data-resolved="true"]').each(function() {
                            var $wrapper = $(this);
                            var threadId = $wrapper.data('comment-thread-id');
                            var $collapse = $wrapper.find('#comment-thread-body-' + threadId);
                            if ($collapse.length) {
                                var shouldExpand = false;
                                if (hash && hash.match(/^#threadId=?\d+/)) {
                                    var targetSelector = hash.replace('=', '');
                                    if ($wrapper.is(targetSelector) || $wrapper.find(targetSelector).length > 0) {
                                        shouldExpand = true;
                                    }
                                }
                                
                                try {
                                    if (shouldExpand) {
                                        $collapse.addClass('show');
                                        $wrapper.find('.comment-resolved-header').attr('aria-expanded', 'true');
                                        $wrapper.find('.comment-collapse-icon').addClass('comment-collapse-icon-open');
                                        setTimeout(function() {
                                            var targetId = hash.replace('=', '');
                                            var $target = $(targetId);
                                            if ($target.length) {
                                                $target[0].scrollIntoView({
                                                    behavior: 'smooth',
                                                    block: 'start'
                                                });
                                                $target.addClass('comment-highlight');
                                                setTimeout(function() { $target.removeClass('comment-highlight'); }, 3000);
                                            }
                                        }, 700);
                                    } else {
                                        $collapse.removeClass('show');
                                        $wrapper.find('.comment-resolved-header').attr('aria-expanded', 'false');
                                        $wrapper.find('.comment-collapse-icon').removeClass('comment-collapse-icon-open');
                                    }
                                } catch (e) {
                                    // Ignore errors during scroll/expand initialization
                                }
                            }
                        });
                    }
                    initResolvedThreads();
                    $(window).on('hashchange', function() {
                        initResolvedThreads();
                    });
                    $(document).off('click.resolved').on('click.resolved', '.comment-resolved-header', function() {
                        // Toggle handled by Bootstrap collapse
                    });
                    $(document).on('shown.bs.collapse', '.comment-thread-wrapper .collapse[id^="comment-thread-body-"]', function () {
                        var $wrapper = $(this).closest('.comment-thread-wrapper');
                        $wrapper.find('.comment-collapse-icon').addClass('comment-collapse-icon-open');
                        $wrapper.find('.comment-resolved-header').attr('aria-expanded', 'true');
                    });
                    $(document).on('hidden.bs.collapse', '.comment-thread-wrapper .collapse[id^="comment-thread-body-"]', function () {
                        var $wrapper = $(this).closest('.comment-thread-wrapper');
                        $wrapper.find('.comment-collapse-icon').removeClass('comment-collapse-icon-open');
                        $wrapper.find('.comment-resolved-header').attr('aria-expanded', 'false');
                    });
                    $(document).on('tiki.ajax.redraw', function() {
                        initResolvedThreads();
                    });

                    $(document).on('click', '.resolve-direct', function(e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        var $btn = $(this);
                        var url = $btn.data('url');
                        
                        $btn.prop('disabled', true).find('i, .tikiicon').addClass('fa-spin-fast');
                        
                        $.post(url, function(data) {
                            if (data.status === 'DONE') {
                                var $container = $('#comments, #comment-container, .comment-container').filter(function() {
                                    return typeof $(this).comment_load === 'function';
                                }).first();

                                if ($container.length) {
                                    $container.comment_load($.service('comment', 'list', {
                                        objectId: objectId,
                                        type: objectType,
                                        modal: 1
                                    }));
                                } else {
                                    window.location.reload();
                                }
                            } else if (data.errors) {
                                alert(data.errors.join("\n"));
                                $btn.prop('disabled', false).find('i, .tikiicon').removeClass('fa-spin-fast');
                                if (typeof $.tikiModal === 'function') $.tikiModal();
                            }
                        }, 'json').fail(function() {
                            // Ensure the spinner is removed on error
                            $btn.prop('disabled', false).find('i, .tikiicon').removeClass('fa-spin-fast');
                            if (typeof $.tikiModal === 'function') $.tikiModal();
                            // Optionally show a brief error
                            if (typeof showMessage === 'function') showMessage(tr('An error occurred while processing the request'), 'error');
                        });
                    });
                {/if}
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
        var objectType = '{$type|escape:'javascript'}';
    </script>
    
    {if $prefs.comments_resolved_threads eq 'y'}
    <style>
        {* Hide modal chrome when loaded inside the comment container *}
        #comments .modal-header, 
        #comments .modal-footer, 
        #comments .btn-close, 
        #comments .modal-content > .btn.btn-link,
        .comment-container .modal-header,
        .comment-container .modal-footer,
        .comment-container .btn-close,
        .comment-container .modal-content > .btn.btn-link {
            display: none !important;
        }
        
        .comment-resolved-header {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            border-left: 4px solid var(--bs-success) !important;
        }
        .comment-resolved-header:hover {
            background-color: rgba(var(--bs-success-rgb), 0.05) !important;
        }
        .comment-collapse-icon {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--bs-success);
        }
        .comment-collapse-icon-open {
            transform: rotate(90deg);
        }
        .comment-resolved {
            border-left: 2px solid var(--bs-success-bg-subtle);
            padding-left: 1rem;
            opacity: 0.85;
        }
        .comment-highlight {
            animation: commentHighlightPulse 2s ease-in-out infinite;
        }
        @keyframes commentHighlightPulse {
            0%, 100% { background-color: transparent; }
            50% { background-color: rgba(var(--bs-warning-rgb), 0.15); }
        }
    </style>
    {/if}
{/block}
