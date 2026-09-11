{if $prefs.feature_canonical_url eq 'y' and isset($mid)}
    {if $mid eq 'tiki-show_page.tpl' or $mid eq 'tiki-index_p.tpl' or $mid eq 'tiki-show_page_raw.tpl' or $mid eq 'tiki-all_languages.tpl' or $mid eq 'tiki-show_content.tpl' or $mid eq 'extends:layouts/internal/layout_view.tpl|tiki-show_page.tpl'}
        {assign var=canonical_url value=$base_url_canonical|cat:($page|sefurl)}
        {if not empty($prefs.wikiHomePage) and $page eq $prefs.wikiHomePage}
            {assign var=canonical_url value=$base_url_canonical}
        {/if}
        <link rel="canonical" href="{$canonical_url}{if not empty($canonical_ending)}{$canonical_ending}{/if}">
        <meta content="{$canonical_url}{if not empty($canonical_ending)}{$canonical_ending}{/if}" property="og:url">
    {elseif $mid eq 'tiki-view_tracker_item.tpl'}
        <link rel="canonical" href="{$base_url_canonical}{$itemId|sefurl:trackeritem:null:null:'n'}">
        <meta content="{$base_url_canonical}{$itemId|sefurl:trackeritem:null:null:'n'}" property="og:url">
    {elseif $mid eq 'tiki-view_forum.tpl'}
        <link rel="canonical" href="{$base_url_canonical}{$forumId|sefurl:forum}">
        <meta content="{$base_url_canonical}{$forumId|sefurl:forum}" property="og:url">
    {elseif $mid eq 'tiki-view_forum_thread.tpl'}
        <link rel="canonical" href="{$base_url_canonical}{$comments_parentId|sefurl:forumthread}">
        <meta content="{$base_url_canonical}{$comments_parentId|sefurl:forumthread}" property="og:url">
    {elseif $mid eq 'tiki-print_forum_thread.tpl'}
        <link rel="canonical" href="{$base_url_canonical}{$comments_parentId|sefurl:forumthread}">
        <meta content="{$base_url_canonical}{$comments_parentId|sefurl:forumthread}" property="og:url">
    {elseif $mid eq 'tiki-view_blog.tpl'}
        <link rel="canonical" href="{$base_url_canonical}{$blogId|sefurl:blog:null:null:'n'}">
        <meta content="{$base_url_canonical}{$blogId|sefurl:blog:null:null:'n'}" property="og:url">
    {elseif $mid eq 'tiki-view_blog_post.tpl'}
        <link rel="canonical" href="{$base_url_canonical}{$postId|sefurl:blogpost:null:null:'n'}">
        <meta content="{$base_url_canonical}{$postId|sefurl:blogpost:null:null:'n'}" property="og:url">
    {elseif $mid eq 'tiki-read_article.tpl'}
        <link rel="canonical" href="{$base_url_canonical}{$articleId|sefurl:article:null:null:'n'}">
        <meta content="{$base_url_canonical}{$articleId|sefurl:article:null:null:'n'}" property="og:url">
    {elseif $mid eq 'tiki-browse_categories.tpl'}
        <link rel="canonical" href="{$base_url_canonical}{$parentId|sefurl:category}">
        <meta content="{$base_url_canonical}{$parentId|sefurl:category}" property="og:url">
    {elseif $mid eq 'tiki-list_file_gallery.tpl'}
        <link rel="canonical" href="{$base_url_canonical}file{$galleryId}">
        <meta content="{$base_url_canonical}file{$galleryId}" property="og:url">
    {/if}
{/if}
