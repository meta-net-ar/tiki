{* --- This simplified header.tpl file is intended for the Unified Admin Backend (UAB) only! --- *}
{if $base_uri and ($dir_level gt 0 or $prefs.feature_html_head_base_tag eq 'y')}
    <base href="{$base_uri|escape}">
{/if}
{* --- Latest IE Compatibility --- *}
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">

    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="generator" content="Tiki Wiki CMS Groupware - https://tiki.org">

    <meta content="{$base_url_canonical}" name="twitter:domain"> {* may be obsolete when using twitter:card *}
{strip}
{* --- Canonical URL --- *}
    {include file="canonical.tpl"}
{/strip}

{* --- We don't need any SEO or socials for the admin interface so we skip the rest of the related meta tags --- *}

{if ($prefs.metatag_robotscustom == 'y' and not empty($metatag_robotscustom))}
    <meta name="robots" content="{$metatag_robotscustom|escape}">
{else}
    {if (isset($prefs.metatag_robots) and $prefs.metatag_robots neq '') and (!isset($metatag_robots) or $metatag_robots eq '')}
        <meta name="robots" content="{$prefs.metatag_robots|escape}">
    {/if}
    {if (!isset($prefs.metatag_robots) or $prefs.metatag_robots eq '') and (isset($metatag_robots) and $metatag_robots neq '')}
        <meta name="robots" content="{$metatag_robots|escape}">
    {/if}
    {if (isset($prefs.metatag_robots) and $prefs.metatag_robots neq '') and (isset($metatag_robots) and $metatag_robots neq '')}
        <meta name="robots" content="{$prefs.metatag_robots|escape}, {$metatag_robots|escape}">
    {/if}
{/if}
{if $prefs.metatag_revisitafter neq ''}
    <meta name="revisit-after" content="{$prefs.metatag_revisitafter|escape}">
{/if}

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
        {assign var="aliasname" value={$structure_path[ix].page_alias}}
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

{* --- GlitchTip reporting script should be load before any other JS to capture issues/failures on other JS scripts --- *}
{if $prefs.error_tracking_enabled_js eq 'y' and  !empty($prefs.error_tracking_dsn)}
    <script type="text/javascript" src="vendor_bundled/vendor/npm-asset/sentry--browser/build/bundle.min.js"></script>
    <script type="text/javascript">Sentry.init({ dsn: "{$prefs.error_tracking_dsn}", sampleRate: {if isset($prefs.error_tracking_sample_rate) and is_numeric($prefs.error_tracking_sample_rate)}{$prefs.error_tracking_sample_rate}{else}1{/if}});</script>
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
            if (storedTheme) return storedTheme;
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
