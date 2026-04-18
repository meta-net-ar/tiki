<meta charset="utf-8">
<!--Latest IE Compatibility-->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
{if $base_uri and ($dir_level gt 0 or $prefs.feature_html_head_base_tag eq 'y')}
    <base href="{$base_uri|escape}">
{/if}
<meta name="generator" content="Tiki Wiki CMS Groupware - https://tiki.org">
{* --- SocialNetwork:Domain ---*}
<meta name="twitter:domain" content="{$base_url_canonical}"> {* may be obsolete when using twitter:card *}
{* --- Canonical URL --- *}
{include file="canonical.tpl"}

{* --- Blog description --- *}
{if isset($section) and $section eq "blogs"}
    {if not empty($post_info) and not empty($post_info.parsed_excerpt)}
        {$metatag_description = $post_info.parsed_excerpt|strip_tags:false|truncate:150|escape}
    {elseif not empty($post_info) and not empty($post_info.parsed_data|strip_tags)}
        {$metatag_description = $post_info.parsed_data|strip_tags:false|truncate:150|escape}
    {else}
        {if not empty($post_info) and not empty($post_info.title)}
            {$tmp_post_info_title=$post_info.title}
        {else}
            {$tmp_post_info_title=''}
        {/if}
        {if not empty($blog_data) and not empty($blog_data.title)}
            {$tmp_blog_data_title=$blog_data.title}
        {else}
            {$tmp_blog_data_title=''}
        {/if}
        {$metatag_description = $tmp_post_info_title|cat:' - '|cat:$tmp_blog_data_title|escape}
    {/if}
{* --- Article description --- *}
{elseif isset($section) and $section eq "cms"}
    {if not empty($heading)}
        {$metatag_description = $parsed_heading|strip_tags:false|truncate:150|escape}
    {elseif not empty ($body)}
        {$metatag_description = $parsed_body|strip_tags:false|truncate:150|escape}
    {/if}
{* --- File Gallery description --- *}
{elseif isset($section) and $section eq "file_galleries"}
    {if not empty($gal_info.description)}
        {$metatag_description = $gal_info.description|strip_tags:false|truncate:150|escape}
    {/if}
{* --- Page description --- *}
{elseif $prefs.metatag_pagedesc eq 'y' and not empty($description)}
    {$metatag_description = $description|escape}
{elseif not empty($prefs.metatag_description_translated)}
    {$metatag_description = $prefs.metatag_description_translated|escape}
{elseif not empty($prefs.metatag_description)}
    {$metatag_description = $prefs.metatag_description|escape}
{/if}
{if not empty($metatag_description) and not empty($metatag_description|trim)}
    <meta name="description" content="{$metatag_description}">
    <meta property="og:description" content="{$metatag_description}">
    <meta name="twitter:description" content="{$metatag_description}">
{else}
    <meta name="description" content="{if not empty($prefs.browsertitle_translated)}{$prefs.browsertitle_translated|tr_if|escape}{else}{$prefs.browsertitle|tr_if|escape}{/if}{if isset($title)} {$prefs.site_nav_seper} {$title}{/if}">
    <meta property="og:description" content="{if not empty($prefs.browsertitle_translated)}{$prefs.browsertitle_translated|tr_if|escape}{else}{$prefs.browsertitle|tr_if|escape}{/if}{if isset($title)} {$prefs.site_nav_seper} {$title}{/if}">
    <meta name="twitter:description" content="{if not empty($prefs.browsertitle_translated)}{$prefs.browsertitle_translated|tr_if|escape}{else}{$prefs.browsertitle|tr_if|escape}{/if}{if isset($title)} {$prefs.site_nav_seper} {$title}{/if}">
{/if}
{* --- Meta keywords: forum, per-page keywords, and/or tags (when "Include tags" is on) --- *}
{if !empty($forum_info.name) and $prefs.metatag_threadtitle eq 'y'}
    <meta name="keywords" content="{tr}Forum{/tr} {$forum_info.name|escape} {if !empty($thread_info.title)}{$thread_info.title|escape}{/if} {if $prefs.metatag_freetags eq 'y' and isset($tags) and !empty($tags)}{foreach from=$tags item=taginfo}{$taginfo.tag|escape} {/foreach}{/if}">
{elseif !empty($metatag_local_keywords) or ($prefs.metatag_freetags eq 'y' and isset($tags) and !empty($tags))}
    <meta name="keywords" content="{if $prefs.metatag_freetags eq 'y' and isset($tags) and !empty($tags)}{foreach from=$tags item="taginfo"}{$taginfo.tag|escape}, {/foreach}{/if}{if !empty($metatag_local_keywords)}{$metatag_local_keywords|escape}{/if}">
{/if}
{if $prefs.site_google_analytics_site_ownership neq ''}
    <meta name="google-site-verification" content="{$prefs.site_google_analytics_site_ownership|escape}">
{/if}
{if $prefs.metatag_google_notranslate eq 'y'}
    <meta name="google" content="notranslate"> {* Deprecated, but still used by some SEO tools *}
{/if}
{if $prefs.metatag_geoposition neq ''}
    <meta name="geo.position" content="{$prefs.metatag_geoposition|escape}">
    <meta name="ICBM" content="{$prefs.metatag_geoposition|replace:';':','|escape}">
{/if}
{if $prefs.metatag_georegion neq ''}
    <meta name="geo.region" content="{$prefs.metatag_georegion|escape}">
{/if}
{if $prefs.metatag_geoplacename neq ''}
    <meta name="geo.placename" content="{$prefs.metatag_geoplacename|escape}">
{/if}
{if ($prefs.metatag_robotscustom == 'y' and not empty($metatag_robotscustom))}
    {* PRIORITY 1: Page-specific custom robots (highest priority, complete override) *}
    <meta name="robots" content="{$metatag_robotscustom|escape}">
    <meta name="googlebot" content="{$metatag_robotscustom|escape}">
{else}
    {if (isset($prefs.metatag_robots) and $prefs.metatag_robots neq '') and (!isset($metatag_robots) or $metatag_robots eq '')}
        {* Only global preference is set *}
        <meta name="robots" content="{$prefs.metatag_robots|escape}">
        <meta name="googlebot" content="{$prefs.metatag_robots|escape}">
    {/if}
    {if (!isset($prefs.metatag_robots) or $prefs.metatag_robots eq '') and (isset($metatag_robots) and $metatag_robots neq '')}
        {* Only script-specific is set (from getRobots() or individual PHP files) *}
        <meta name="robots" content="{$metatag_robots|escape}">
        <meta name="googlebot" content="{$metatag_robots|escape}">
    {/if}
    {if (isset($prefs.metatag_robots) and $prefs.metatag_robots neq '') and (isset($metatag_robots) and $metatag_robots neq '')}
        {* Both global and script-specific are set *}
        {* Check for conflicts: if either contains NOINDEX/NOFOLLOW, use only that one to avoid contradictions *}
        {$metatag_robots_lower = $metatag_robots|lower}
        {$prefs_robots_lower = $prefs.metatag_robots|lower}
        {$script_has_restrictive = strpos($metatag_robots_lower, 'noindex') !== false or strpos($metatag_robots_lower, 'nofollow') !== false}
        {$prefs_has_restrictive = strpos($prefs_robots_lower, 'noindex') !== false or strpos($prefs_robots_lower, 'nofollow') !== false}
        {if $script_has_restrictive}
            {* Script-specific has restrictive directive - it takes full priority *}
            <meta name="robots" content="{$metatag_robots|escape}">
            <meta name="googlebot" content="{$metatag_robots|escape}">
        {elseif $prefs_has_restrictive}
            {* Global pref has restrictive directive - it takes priority *}
            <meta name="robots" content="{$prefs.metatag_robots|escape}">
            <meta name="googlebot" content="{$prefs.metatag_robots|escape}">
        {else}
            {* No conflict - safe to combine both directives *}
            <meta name="robots" content="{$prefs.metatag_robots|escape}, {$metatag_robots|escape}">
            <meta name="googlebot" content="{$prefs.metatag_robots|escape}, {$metatag_robots|escape}">
        {/if}
    {/if}
{/if}
{* --- SocialNetwork:site_name --- *}
<meta property="og:site_name" content="{if not empty($prefs.socialnetworks_facebook_site_name)}{$prefs.socialnetworks_facebook_site_name}{elseif not empty($prefs.browsertitle_translated)}{$prefs.browsertitle_translated|tr_if|escape}{else}{$prefs.browsertitle|tr_if|escape}{/if}">
<meta name="twitter:site" content="{if not empty($prefs.socialnetworks_twitter_site)}{$prefs.socialnetworks_twitter_site}{elseif not empty($prefs.browsertitle_translated)}{$prefs.browsertitle_translated|tr_if|escape}{else}{$prefs.browsertitle|tr_if|escape}{/if}">
{* --- SocialNetwork: fb:app_id ---*}
{if not empty($prefs.socialnetworks_facebook_application_id)}<meta property="fb:app_id" content="{$prefs.socialnetworks_facebook_application_id}">{/if}

{capture assign='header_title'}{strip}
{if !empty($sswindowtitle)}
    {if $sswindowtitle eq 'none'}
        &nbsp;
    {else}
        {$sswindowtitle|escape}
    {/if}
{else}
    {if $prefs.site_title_location eq 'before'}{if not empty($prefs.browsertitle_translated)}{$prefs.browsertitle_translated|tr_if|escape}{else}{$prefs.browsertitle|tr_if|escape}{/if} {$prefs.site_nav_seper} {/if}
    {capture assign="page_description_title"}
        {if ($prefs.feature_breadcrumbs eq 'y' or $prefs.site_title_breadcrumb eq "desc") && isset($trail)}
            {breadcrumbs type=$prefs.site_title_breadcrumb loc="head" crumbs=$trail}
        {/if}
    {/capture}
    {if isset($structure) and $structure eq 'y'} {* get the alias name if item is a wiki page and it is in a structure *}
        {section loop=$structure_path name=ix}
        {$aliasname={$structure_path[ix].page_alias}}
        {/section}
    {/if}
    {if $prefs.site_title_location eq 'only'}
        {if not empty($prefs.browsertitle_translated)}{$prefs.browsertitle_translated|tr_if|escape}{else}{$prefs.browsertitle|tr_if|escape}{/if}
    {else}
        {if !empty($page_description_title)}
            {$page_description_title}
        {else}
            {if !empty($tracker_item_main_value)}
                {$tracker_item_main_value|truncate:255|escape}
            {elseif !empty($tagTitle)}
                {$tagTitle|escape}
            {elseif !empty($title) and !is_array($title)}
                {$title|escape}
            {elseif !empty($aliasname)}
                {$aliasname|escape}
            {elseif !empty($arttitle)}
                {$arttitle|escape}
            {elseif !empty($thread_info.title)}
                {$thread_info.title|escape}
            {elseif !empty($forum_info.name)}
                {$forum_info.name|escape}
            {elseif !empty($categ_info.name)}
                {$categ_info.name|escape}
            {elseif !empty($userinfo.login)}
                {$userinfo.login|username}
            {elseif !empty($tracker_info.pagetitle)}
                {$tracker_info.pagetitle|escape}
            {elseif !empty($tracker_info.name)}
                {$tracker_info.name|escape}
            {elseif !empty($page) && $prefs.site_title_breadcrumb eq "pagetitle"}
                {$page|escape}
            {elseif !empty($description)}
                {$description|escape}{* use description if nothing else is found but this is likely to contain tiki markup *}
                {* add $description|escape if you want to put the description + update breadcrumb_build replace return $crumbs->title; with return empty($crumbs->description)? $crumbs->title: $crumbs->description; *}
            {elseif !empty($page)}
                {$page|escape} {* Must stay after description as it is unlikely to be empty if wiki pages *}
            {elseif !empty($headtitle)}
                {$headtitle|stringfix:"&nbsp;"|escape}{* use $headtitle last if feature specific title not found - Must stay the last one as failback *}
            {/if}
        {/if}
    {/if}
    {if $prefs.site_title_location eq 'after'} {$prefs.site_nav_seper} {if not empty($prefs.browsertitle_translated)}{$prefs.browsertitle_translated|tr_if|escape}{else}{$prefs.browsertitle|tr_if|escape}{/if}{/if}
{/if}
{/strip}{/capture}
{* --- tiki block --- *}
<title>{$header_title}</title>
{* --- SocialNetwork:title --- *}
{* Facebook *}
<meta property="og:title" content="{$header_title}">
{* Twitter *}
<meta name="twitter:title" content="{$header_title}">
{* --- SocialNetwork:type --- *}
{if $prefs.feature_canonical_url eq 'y' and isset($mid)}
    {if $mid eq 'tiki-view_blog.tpl' or $mid eq 'tiki-view_blog_post.tpl' or $mid eq 'tiki-read_article.tpl'}
        <meta property="og:type" content="article">
    {else}
        <meta property="og:type" content="website">
    {/if}
{/if}
{* To be added someday when using cart feature: product, product.group, product.item *}
{* May be usefull too : profile *}
<meta name="twitter:card" content="summary">
{* --- SocialNetwork:image --- *}
{* first we check if there is a featured image to use it *}
{if not empty($header_featured_images)}
    {foreach $header_featured_images as $header_featured_image}
        <meta property="og:image" content="{$header_featured_image|escape}">
        <meta name="twitter:image" content="{$header_featured_image|escape}">
    {/foreach}
{elseif $prefs.feature_canonical_url eq 'y' and isset($mid)}
    {if $mid eq 'tiki-view_blog.tpl'}
    {elseif $mid eq 'tiki-view_blog_post.tpl'}
    {* --- Article --- *}
    {* If there is no featured image we check if an article image or a topic image exist to use it *}
    {elseif ($mid eq 'tiki-read_article.tpl') and ($hasImage eq 'y') or (not empty ($topics.image_name))}
        <meta property="og:image" content="{$base_url_canonical}{if $hasImage eq 'y'}article_image.php?image_type=article&id={$articleId}{elseif not empty ($topics.image_name)}article_image.php?image_type=topic&id={$topicId}{/if}">
        <meta name="twitter:image" content="{$base_url_canonical}{if $hasImage eq 'y'}article_image.php?image_type=article&id={$articleId}{elseif not empty ($topics.image_name)}article_image.php?image_type=topic&id={$topicId}{/if}">
    {* We use the social network image as failsafe - control panel social network *}
    {else}
        {if !empty($prefs.socialnetworks_facebook_site_image)}<meta property="og:image" content="{$prefs.socialnetworks_facebook_site_image}">{/if}
        {if !empty($prefs.socialnetworks_twitter_site_image)}<meta name="twitter:image" content="{$prefs.socialnetworks_twitter_site_image}">{/if}
    {/if}
{/if}
{if $prefs.metatag_nositelinkssearchbox eq 'y'}
    <meta name="google" content="nositelinkssearchbox">
{/if}
{* --- universaleditbutton.org --- *}
{if (isset($editable) and $editable) and ($tiki_p_edit eq 'y' or $page|lower eq 'sandbox' or $tiki_p_admin_wiki eq 'y')}
    <link rel="alternate" type="application/x-wiki" title="{tr}Edit this page!{/tr}" href="tiki-editpage.php?page={$page|escape:url}">
{/if}
{* --- Firefox RSS icons --- *}
{if $prefs.feature_wiki eq 'y' and $prefs.feed_wiki eq 'y' and $tiki_p_view eq 'y'}
    <link rel="alternate" type="application/rss+xml" title='{$prefs.feed_wiki_title|escape|default:"{tr}RSS Wiki{/tr}"}' href="tiki-wiki_rss.php?ver={$prefs.feed_default_version|escape:'url'}">
{/if}
{if $prefs.feature_blogs eq 'y' and $prefs.feed_blogs eq 'y' and $tiki_p_read_blog eq 'y'}
    <link rel="alternate" type="application/rss+xml" title='{$prefs.feed_blogs_title|escape|default:"{tr}RSS Blogs{/tr}"}' href="tiki-blogs_rss.php?ver={$prefs.feed_default_version|escape:'url'}">
{/if}
{if $prefs.feature_articles eq 'y' and $prefs.feed_articles eq 'y' and $tiki_p_read_article eq 'y'}
    <link rel="alternate" type="application/rss+xml" title='{$prefs.feed_articles_title|escape|default:"{tr}RSS Articles{/tr}"}' href="tiki-articles_rss.php?ver={$prefs.feed_default_version|escape:'url'}">
{/if}
{if $prefs.feature_file_galleries eq 'y' and $prefs.feed_file_galleries eq 'y' and $tiki_p_view_file_gallery eq 'y'}
    <link rel="alternate" type="application/rss+xml" title='{$prefs.feed_file_galleries_title|escape|default:"{tr}RSS File Galleries{/tr}"}' href="tiki-file_galleries_rss.php?ver={$prefs.feed_default_version|escape:'url'}">
{/if}
{if $prefs.feature_forums eq 'y' and $prefs.feed_forums eq 'y' and $tiki_p_forum_read eq 'y'}
    <link rel="alternate" type="application/rss+xml" title='{$prefs.feed_forums_title|escape|default:"{tr}RSS Forums{/tr}"}' href="tiki-forums_rss.php?ver={$prefs.feed_default_version|escape:'url'}">
{/if}
{if $prefs.feature_directory eq 'y' and $prefs.feed_directories eq 'y' and $tiki_p_view_directory eq 'y'}
    <link rel="alternate" type="application/rss+xml" title='{$prefs.feed_directories_title|escape|default:"{tr}RSS Directories{/tr}"}' href="tiki-directories_rss.php?ver={$prefs.feed_default_version|escape:'url'}">
{/if}
{if $prefs.feature_calendar eq 'y' and $prefs.feed_calendar eq 'y' and $tiki_p_view_calendar eq 'y'}
    <link rel="alternate" type="application/rss+xml" title='{$prefs.feed_calendar_title|escape|default:"{tr}RSS Calendars{/tr}"}' href="tiki-calendars_rss.php?ver={$prefs.feed_default_version|escape:'url'}">
{/if}
{if $prefs.feature_trackers eq 'y' and $prefs.feed_tracker eq 'y'}
    {foreach from=$rsslist_trackers item="tracker"}
        <link rel="alternate" type="application/rss+xml"
            title='{$prefs.feed_tracker_title|cat:" - "|cat:$tracker.name|escape|default:"{tr}RSS Tracker{/tr}"}'
            href="tiki-tracker_rss.php?ver={$prefs.feed_default_version|escape:'url'}&trackerId={$tracker.trackerId}">
    {/foreach}
{/if}

{if ($prefs.feature_blogs eq 'y' and $prefs.feature_blog_sharethis eq 'y') or ($prefs.feature_articles eq 'y' and $prefs.feature_cms_sharethis eq 'y') or ($prefs.feature_wiki eq 'y' and $prefs.feature_wiki_sharethis eq 'y')}
    {if $prefs.blog_sharethis_publisher neq "" and $prefs.article_sharethis_publisher neq ""}
        <script type="text/javascript" src="https://ws.sharethis.com/button/sharethis.js#publisher={$prefs.blog_sharethis_publisher}&amp;type=website&amp;buttonText=&amp;onmouseover=false&amp;send_services=aim"></script>
    {elseif $prefs.blog_sharethis_publisher neq "" and $prefs.article_sharethis_publisher eq ""}
        <script type="text/javascript" src="https://ws.sharethis.com/button/sharethis.js#publisher={$prefs.blog_sharethis_publisher}&amp;type=website&amp;buttonText=&amp;onmouseover=false&amp;send_services=aim"></script>
    {elseif $prefs.blog_sharethis_publisher eq "" and $prefs.article_sharethis_publisher neq ""}
        <script type="text/javascript" src="https://ws.sharethis.com/button/sharethis.js#publisher={$prefs.article_sharethis_publisher}&amp;type=website&amp;buttonText=&amp;onmouseover=false&amp;send_services=aim"></script>
    {elseif $prefs.blog_sharethis_publisher eq "" and $prefs.article_sharethis_publisher eq ""}
        <script type="text/javascript" src="https://ws.sharethis.com/button/sharethis.js#type=website&amp;buttonText=&amp;onmouseover=false&amp;send_services=aim"></script>
    {/if}
{/if}

{if $headerlib} 
    {$headerlib->output_headers()}
{/if}

{if !empty($prefs.feature_custom_html_head_content)}
    {eval var=$prefs.feature_custom_html_head_content}
{/if}


{if $prefs.switch_color_module_assigned eq 'y'}
    <script>
        const getStoredTheme = () => localStorage.getItem("theme");
        const setStoredTheme = (theme) => localStorage.setItem("theme", theme);
        const getPreferredTheme = () => {
            const storedTheme = getStoredTheme();
            if (storedTheme)  return storedTheme;
            return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        };
        const setTheme = (theme) => {
            if (theme === "auto" && window.matchMedia("(prefers-color-scheme: dark)").matches)  document.documentElement.setAttribute("data-bs-theme", "dark"); 
            else document.documentElement.setAttribute("data-bs-theme", theme);
        };
        setTheme(getPreferredTheme());
    </script>
    <style>
        {foreach from=$prefs['custom_color_mode'] item=mode}
            {if null !== $mode['css_variables']}
                {$mode['css_variables']}
            {/if}
        {/foreach}
    </style>
{/if}

{* Include mautic snipet code with mautic *}
{if $prefs.site_mautic_enable eq 'y' && $prefs.wikiplugin_mautic eq 'y' && $prefs.site_mautic_tracking_script_location eq 'head'}
    {wikiplugin _name=mautic type="inclusion"}{/wikiplugin}
{/if}
{* END of html head content *}
