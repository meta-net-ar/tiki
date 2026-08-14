{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
{rating type=$type id=$id}
{if $tiki_p_ratings_view_results eq 'y' or $tiki_p_admin eq 'y'}
    {rating_result type=$type id=$id}
{/if}
{/block}
