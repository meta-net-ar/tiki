<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if Language::isRTL()} dir="rtl"{/if}{if !empty($page_id)} id="page_{$page_id}"{/if}>
<head>
    {include file='header.tpl'}
    {* The following style block makes sense to be used only with this fixed top bar layout so lets put it here only *}
    {if $prefs.theme_navbar_fixed_topbar_offset ne ''}<style>
    {literal}
    /* Prevent target anchors from being covered by fixed top navbar */
    h1:target:before,
    h2:target:before,
    h3:target:before,
    h4:target:before,
    h5:target:before,
    h6:target:before {
        content: "";
        display: block;
        height: {/literal}{$prefs.theme_navbar_fixed_topbar_offset}{literal}px; /* fixed header height*/
        margin: -{/literal}{$prefs.theme_navbar_fixed_topbar_offset}{literal}px 0 0; /* negative fixed header height */
    }
    #middle_outer {
        margin-top: {/literal}{$prefs.theme_navbar_fixed_topbar_offset}{literal}px;
    }
    {/literal}
    </style>{/if}
</head>
<body{html_body_attributes class="navbar-padding"}>
    {$cookie_consent_html}

    {include file="layout_fullscreen_check.tpl"}

    {if $prefs.feature_ajax eq 'y'}
        {include file='tiki-ajax_header.tpl'}
    {/if}
    <a class="btn btn-info btn-lg skipnav" href="#col1" role="button">{tr}Skip to main content{/tr}</a>
    {block name=module_header}{/block}
    {if !isset($smarty.session.fullscreen) || isset($smarty.session.fullscreen) && $smarty.session.fullscreen ne 'y'}
        {if $prefs.theme_unified_admin_backend neq 'y' or $smarty.server.SCRIPT_NAME|strpos:'tiki-admin.php' === false}            
            <header
                class="navbar navbar-expand-md tiki-top-nav-{$navbar_color_variant} navbar-{$navbar_color_variant} bg-{$navbar_color_variant} tiki-header-top fixed-top p-0">
                <div class="container-fluid">
                    {modulelist zone=top class="top_modules w-100 tiki-top-nav-{$navbar_color_variant} navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent" heading_text='{tr}Site identity, navigation, etc.{/tr}' role=banner}
                </div>
            </header>
        {/if}
    {/if}
    <div class="middle_outer" id="middle_outer" style="margin-top: 70px;">
        {if !isset($smarty.session.fullscreen) && $smarty.session.fullscreen ne 'y'}
            {if $prefs.theme_unified_admin_backend eq 'y' && $smarty.server.SCRIPT_NAME eq $url_path|cat:'tiki-admin.php'}
                {modulelist zone=top class="top_modules uab top navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent tiki-top-nav-{$navbar_color_variant} w-100 mb-sm" heading_text='{tr}Site identity, navigation, etc.{/tr}' role=banner}
            {/if}
        {/if}
        <div class="topbar-wrapper navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent tiki-topbar-nav-{$navbar_color_variant}">
            <div class="topbar container{if isset($smarty.session.fullscreen) && $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std navbar-{$navbar_color_variant} bg-{$navbar_color_variant} tiki-topbar-nav-{$navbar_color_variant}" id="topbar">
                {modulelist zone=topbar class='topbar_modules w-100' heading_text='{tr}Navigation and related functionality and content{/tr}'}
            </div>
        </div>
        <div class="middle-wrapper">
            <div class="page-content-top-margin"  style="height: var(--tiki-page-content-top-margin)"></div>
        <div class="container{if isset($smarty.session.fullscreen) && $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std middle" id="middle">
            <div class="row row-middle" id="row-middle">
                {if (zone_is_empty('left') or $prefs.feature_left_column eq 'n') and (zone_is_empty('right') or $prefs.feature_right_column eq 'n')}
                    <div class="col col1 col-md-12 pb-4" id="col1">
                        {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                            {modulelist zone=pagetop heading_text='{tr}Related content{/tr}' role=complementary}
                        {/if}
                        <div id="feedback" role="alert">
                            {feedback}
                        </div>
                        {queued_tasks_banner}
                        {block name=quicknav}{/block}
                        {block name=title}{/block}
                        {block name=navigation}{/block}
                        <main>
                            {block name=content}{/block}
                        </main>
                        {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                            {modulelist zone=pagebottom class='mt-3' heading_text='{tr}Pagebottom heading{/tr}' role=complementary}
                        {/if}
                    </div>
                {elseif zone_is_empty('left') or $prefs.feature_left_column eq 'n'}
                    <div class="col col1 col-md-12 col-lg-9 {if $prefs.feature_fixed_width neq 'y'}col-xl-10{/if} pb-4" id="col1">
                        <div id="col1top-outer-wrapper" class="col1top-outer-wrapper d-flex justify-content-between">
                            <div class="col1top-inner-wrapper flex-grow-1 ">
                                {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                                    {modulelist zone=pagetop heading_text='{tr}Related content{/tr}' role=complementary}
                                {/if}
                                <div id="feedback" role="alert">
                                    {feedback}
                                </div>
                                {queued_tasks_banner}
                                {block name=quicknav}{/block}
                            </div>
                            <div class="d-none d-lg-flex">
                                {if $prefs.feature_right_column eq 'user'}
                                    <div class="side-col-toggle-container d-none d-lg-block">
                                        {$icon_name = (not empty($smarty.cookies.hide_zone_right)) ? 'toggle-left' : 'toggle-right'}
                                        {icon name=$icon_name class='toggle_zone right btn btn-xs btn-secondary' href='#' title='{tr}Toggle right modules{/tr}'}
                                    </div>
                                {/if}
                            </div>
                        </div>
                        {block name=title}{/block}
                        {block name=navigation}{/block}
                        <main>
                            {block name=content}{/block}
                        </main>
                        {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                            {modulelist zone=pagebottom class='mt-3' heading_text='{tr}Related content{/tr}' role=complementary}
                        {/if}
                    </div>
                    <div class="col col3 col-12 col-md-6 col-lg-3 {if $prefs.feature_fixed_width neq 'y'}col-xl-2{/if}" id="col3">
                        <div class="p-1 bg-light">
                            {if $prefs.module_sidebar_toggle_small_screen eq 'y'}
                                {include file="modules/mod-side_col_toggle_small_screen.tpl" zone='right'}
                            {/if}
                            {modulelist zone=right class="right-aside" heading_text='{tr}More content and functionality (right side){/tr}' role=complementary}
                        </div>
                    </div>
                </div>
                    {elseif zone_is_empty('right') or $prefs.feature_right_column eq 'n'}
                    <div class="col col1 col-md-12 col-lg-9 {if $prefs.feature_fixed_width neq 'y'}col-xl-10{/if} order-md-1 order-lg-2 pb-4" id="col1">
                        <div id="col1top-outer-wrapper" class="col1top-outer-wrapper d-flex justify-content-between">
                            <div class="d-none d-lg-flex">
                                {if $prefs.feature_left_column eq 'user'}
                                    <div class="side-col-toggle-container d-none d-lg-block"> {* This div seems redundant but is necessary to prevent the button from being the height of the row. *}
                                        {$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'toggle-right' : 'toggle-left'}
                                        {icon name=$icon_name class='toggle_zone left btn btn-xs btn-secondary' href='#' title='{tr}Toggle left modules{/tr}'}
                                    </div>
                                {/if}
                            </div>
                            <div class="col1top-inner-wrapper flex-grow-1 ">
                                {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                                    {modulelist zone=pagetop heading_text='{tr}Related content{/tr}' role=complementary }
                                {/if}
                                <div id="feedback" role="alert">
                                    {feedback}
                                </div>
                                {queued_tasks_banner}
                                {block name=quicknav}{/block}
                            </div>
                        </div>
                        {block name=title}{/block}
                        {block name=navigation}{/block}
                        <main>
                            {block name=content}{/block}
                        </main>
                        {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                            {modulelist zone=pagebottom class='mt-3' heading_text='{tr}Related content{/tr}' role=complementary}
                        {/if}
                    </div>
                    <div class="col col2 col-12 col-md-6 col-lg-3 {if $prefs.feature_fixed_width neq 'y'}col-xl-2{/if} order-sm-2 order-md-2 order-lg-1" id="col2">
                        <div class="p-1 bg-light">
                            {if $prefs.module_sidebar_toggle_small_screen eq 'y'}
                                {include file="modules/mod-side_col_toggle_small_screen.tpl" zone='left'}
                            {/if}
                            {modulelist zone=left class="left-aside" heading_text='{tr}More content and functionality (left side){/tr}' role=complementary}
                        </div>
                    </div>
                {else}
            <div class="col col1 col-sm-12 col-lg-8 order-xs-1 order-lg-2 pb-4" id="col1">
                <div id="col1top-outer-wrapper" class="col1top-outer-wrapper d-flex justify-content-between">
                    <div class="d-none d-lg-block">
                        {if $prefs.feature_left_column eq 'user'}
                            <div class="side-col-toggle" style="margin-left: -10px;">
                                {$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'toggle-right' : 'toggle-left'}
                                {icon name=$icon_name class='toggle_zone left btn btn-xs btn-secondary' href='#' title='{tr}Toggle left modules{/tr}'}
                            </div>
                        {/if}
                    </div>
                    <div class="col1top-inner-wrapper flex-grow-1 ">
                        {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                            {modulelist zone=pagetop heading_text='{tr}Related content{/tr}' role=complementary}
                        {/if}
                        <div id="feedback" role="alert">
                            {feedback}
                        </div>
                        {queued_tasks_banner}
                        {block name=quicknav}{/block}
                    </div>
                    <div class="d-none d-lg-block">
                        {if $prefs.feature_right_column eq 'user'}
                            <div class="side-col-toggle" style="margin-right: -10px;">
                                {$icon_name = (not empty($smarty.cookies.hide_zone_right)) ? 'toggle-left' : 'toggle-right'}
                                {icon name=$icon_name class='toggle_zone right btn btn-xs btn-secondary' href='#' title='{tr}Toggle right modules{/tr}'}
                            </div>
                        {/if}
                    </div>
                </div>
                {block name=title}{/block}
                {block name=navigation}{/block}
                <main>
                    {block name=content}{/block}
                </main>
                {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                    {modulelist zone=pagebottom class='mt-3' heading_text='{tr}Related content{/tr}' role=complementary}
                {/if}
            </div>
            <div class="col col2 col-12 col-md-6 col-lg-2 order-md-2 order-lg-1" id="col2">
                <div class="p-1 bg-light">
                    {if $prefs.module_sidebar_toggle_small_screen eq 'y'}
                        {include file="modules/mod-side_col_toggle_small_screen.tpl" zone='left'}
                    {/if}
                    {modulelist zone=left class="left-aside" heading_text='{tr}More content and functionality (left side){/tr}' role=complementary}
                </div>
            </div>
            <div class="col col3 col-12 col-md-6 col-lg-2 order-md-3" id="col3">
                <div class="p-1 bg-light">
                    {if $prefs.module_sidebar_toggle_small_screen eq 'y'}
                        {include file="modules/mod-side_col_toggle_small_screen.tpl" zone='right'}
                    {/if}
                    {modulelist zone=right class="right-aside" heading_text='{tr}More content and functionality (right side){/tr}' role=complementary}
                </div>
            </div>
                {/if}
            </div> {* row *}
        </div> {* container *}
    </div> {* middle-wrapper *}
    </div> {* middle_outer *}
    {if !isset($smarty.session.fullscreen) || isset($smarty.session.fullscreen) && $smarty.session.fullscreen ne 'y'}
        <footer class="footer main-footer mt-auto" id="footer">
            <div class="footer_liner">
                <div class="container container-std">
                    {modulelist zone=bottom class='bottom_modules p-3 mx-n2point5' heading_text='{tr}Site information, links, etc.{/tr}' role=contentinfo} {* div.modules *}
                </div>
            </div>
        </footer>
    {/if}

    {* Manage top margin of middle_outer dynamically in case header height changes due to content changes (ex. Module added on Top position) or responsive design *}
    {jq}
        (function ($) {
            var $header = $('header.tiki-header-top');
            var $middle = $('#middle_outer');

            function adjustMargin() {
                if ($header.length && $middle.length) {
                    $middle.css('margin-top', $header.outerHeight() + 'px');
                }
            }

            // Initial adjust
            adjustMargin();

            // Update on window resize
            $(window).on('resize', adjustMargin);

            // Update if header size changes
            if (typeof ResizeObserver !== 'undefined') {
                var ro = new ResizeObserver(adjustMargin);
                $header.each(function () { ro.observe(this); });
            }
        })(jQuery);
    {/jq}

    {include file='footer.tpl'}
</body>
</html>
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
    {debug}
{/if}
