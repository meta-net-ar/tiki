{capture assign=modzonetop}
    {modulelist zone=top class="top_modules navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent  tiki-top-nav-{$navbar_color_variant}" heading_text='{tr}Site identity, navigation, etc.{/tr}' role=banner}
{/capture}
{capture assign=modzonetopbar}
    {modulelist zone=topbar class="topbar_modules topbar navbar-{$navbar_color_variant} bg-{$navbar_color_variant} tiki-topbar-nav-{$navbar_color_variant} w-100 mb-sm" heading_text='{tr}Navigation and related functionality and content{/tr}'}
{/capture}
{capture assign=modzonepagetop}
    {modulelist zone=pagetop heading_text='{tr}Related content{/tr}' role=complementary}
{/capture}
{capture assign=modzonepagebottom}
    {modulelist zone=pagebottom class='mt-3' heading_text='{tr}Related content{/tr}' role=complementary}
{/capture}
{capture assign=modzoneright}
    {modulelist zone=right class="right-aside" heading_text='{tr}More content and functionality (right side){/tr}' role=complementary}
{/capture}
{capture assign=modzoneleft}
    {modulelist zone=left class="left-aside" heading_text='{tr}More content and functionality (left side){/tr}' role=complementary}
{/capture}
{capture assign=modzonebottom}
    {modulelist zone=bottom class='bottom_modules p-3 mx-0' heading_text='{tr}Site information, links, etc.{/tr}' role=contentinfo}
{/capture}
<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if Language::isRTL()} dir="rtl"{/if}{if !empty($page_id)} id="page_{$page_id}"{/if}>
<head>
    {include file='header.tpl'}
</head>
<body{html_body_attributes}>
{$cookie_consent_html}

{include file="layout_fullscreen_check.tpl"}

{if $prefs.feature_ajax eq 'y'}
    {include file='tiki-ajax_header.tpl'}
{/if}

{block name=module_header}{/block}
{if $section !== 'admin'}
    {include file='tiki-maintenance_banner.tpl'}
{/if}
<a class="btn btn-info btn-lg skipnav" href="#col1" role="button">{tr}Skip to main content{/tr}</a>
<div class="container{if isset($smarty.session.fullscreen) && $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std d-flex min-vh-100 flex-column my-0">
    {if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
        <div class="row">
            <header class="page-header w-100 navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent tiki-top-nav-{$navbar_color_variant}" id="page-header" role=banner>
                {$modzonetop}
            </header>
        </div>
    {/if}
    <div class="row row-middle" id="row-middle">
        {if $section === 'admin'}
            {include file='tiki-maintenance_banner.tpl'}
        {/if}
        {$modzonetopbar}
        <div class="page-content-top-margin"  style="height: var(--tiki-page-content-top-margin)"></div>
        {if (zone_is_empty('left') or $prefs.feature_left_column eq 'n') and (zone_is_empty('right') or $prefs.feature_right_column eq 'n')}
            <div class="col col1 col-md-12 pb-4" id="col1">
                {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                    {$modzonepagetop}
                {/if}
                <div id="feedback" role="alert">
                    {feedback}
                </div>
                {block name=quicknav}{/block}
                {block name=title}{/block}
                {block name=navigation}{/block}
                <main>
                    {block name=content}{/block}
                </main>
                {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                    {$modzonepagebottom}
                {/if}
            </div>
        {elseif zone_is_empty('left') or $prefs.feature_left_column eq 'n'}
        <div class="d-flex w-100 flex-row row flex-wrap gx-4">
            <div class="col col1 col-md-12 col-lg-9 {if $prefs.feature_fixed_width neq 'y'}col-xl-10{/if} pb-4" id="col1">
                <div id="col1top-outer-wrapper" class="col1top-outer-wrapper d-flex justify-content-between">
                    <div class="col1top-inner-wrapper flex-grow-1 ">
                    {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                        {$modzonepagetop}
                    {/if}
                    <div id="feedback" role="alert">
                        {feedback}
                    </div>
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
                    {$modzonepagebottom}
                {/if}
            </div>
            <div class="col col3 col-12 col-md-6 col-lg-3 {if $prefs.feature_fixed_width neq 'y'}col-xl-2{/if}" id="col3">
                {if $prefs.module_sidebar_toggle_small_screen eq 'y'}
                    {include file="modules/mod-side_col_toggle_small_screen.tpl" zone='right'}
                {/if}
                {$modzoneright}
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
                            {$modzonepagetop}
                        {/if}
                        <div id="feedback" role="alert">
                            {feedback}
                        </div>
                        {block name=quicknav}{/block}
                    </div>
                </div>
                {block name=title}{/block}
                {block name=navigation}{/block}
                <main>
                    {block name=content}{/block}
                </main>
                {if $prefs.module_zones_pagebottom eq 'fixed' or ($prefs.module_zones_pagebottom ne 'n' && ! zone_is_empty('pagebottom'))}
                    {$modzonepagebottom}
                {/if}
            </div>
            <div class="col col2 col-12 col-md-6 col-lg-3 {if $prefs.feature_fixed_width neq 'y'}col-xl-2{/if} order-sm-2 order-md-2 order-lg-1" id="col2">
                {if $prefs.module_sidebar_toggle_small_screen eq 'y'}
                    {include file="modules/mod-side_col_toggle_small_screen.tpl" zone='left'}
                {/if}
                {$modzoneleft}
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
                            {$modzonepagetop}
                        {/if}
                        <div id="feedback" role="alert">
                            {feedback}
                        </div>
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
                    {$modzonepagebottom}
                {/if}
            </div>
            <div class="col col2 col-12 col-md-6 col-lg-2 order-md-2 order-lg-1" id="col2">
                {if $prefs.module_sidebar_toggle_small_screen eq 'y'}
                    {include file="modules/mod-side_col_toggle_small_screen.tpl" zone='left'}
                {/if}
                {$modzoneleft}
            </div>
            <div class="col col3 col-12 col-md-6 col-lg-2 order-md-3" id="col3">
                {if $prefs.module_sidebar_toggle_small_screen eq 'y'}
                    {include file="modules/mod-side_col_toggle_small_screen.tpl" zone='right'}
                {/if}
                {$modzoneright}
            </div>
        {/if}
    </div>

{if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
    <footer class="row footer main-footer mt-auto" id="footer">
        <div class="footer_liner w-100">
            {$modzonebottom}
        </div>
    </footer>
{/if}
</div>
{include file='footer.tpl'}
</body>
</html>
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
    {debug}
{/if}
