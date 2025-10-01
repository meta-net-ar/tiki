{if $comments_count gt 0}
    {$thisbuttonclass='highlight'}
{else}
    {$thisbuttonclass=''}
{/if}
{if $comments_count == 0 or ($tiki_p_read_comments == 'n' and $tiki_p_post_comments == 'y')}
    {$thistext="{tr}Add Comment{/tr}"}
{elseif $comments_count == 1}
    {$thistext="{tr}1 comment{/tr}"}
{else}
    {$thistext="$comments_count&nbsp;{tr}Comments{/tr}"}
{/if}
{if isset($pagemd5)}
    {$thisflipid="comzone$pagemd5"}
{else}
    {$thisflipid="comzone"}
{/if}
{if $comments_show eq 'y' or $show_comzone eq 'y'}
    {$flip_open='y'}
<noscript>
    {button comzone="hide" _anchor="comments" _auto_args="comzone,*" _class=$thisbuttonclass _text=$thistext _flip_hide_text='y' _flip_default_open=$flip_open}
</noscript>
{elseif $comments_show eq 'n'}
    {$flip_open='n'}
<noscript>
    {button comzone="show" _anchor="comments" _auto_args="comzone,*" _class=$thisbuttonclass _text=$thistext _flip_hide_text='n' _flip_default_open=$flip_open}
</noscript>
{else}
    {$flip_open=$prefs.wiki_comments_displayed_default}
<noscript>
    {button comzone="show" _anchor="comments" _auto_args="comzone,*" _class=$thisbuttonclass _text=$thistext _flip_hide_text='n' _flip_default_open=$flip_open}
</noscript>
{/if}
{button href="#comments" _auto_args="*" _flip_id=$thisflipid _class=$thisbuttonclass _text=$thistext _flip_default_open=$flip_open}
