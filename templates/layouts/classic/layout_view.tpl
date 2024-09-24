<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if $prefs.feature_bidi eq 'y'} dir="rtl"{/if}{if !empty($page_id)} id="page_{$page_id}"{/if}>
<head>
    {include file='header.tpl'}
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body{html_body_attributes}>
{$cookie_consent_html}

{include file="layout_fullscreen_check.tpl"}

{if $prefs.feature_ajax eq 'y'}
    {include file='tiki-ajax_header.tpl'}
{/if}
<a class="btn btn-info btn-lg skipnav" href="#col1">{tr}Skip to main content{/tr}</a>
{if $prefs.feature_layoutshadows eq 'y'}
<div id="main-shadow">{eval var=$prefs.main_shadow_start}{/if}

    {if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
    {if $prefs.feature_layoutshadows eq 'y'}
    <div id="header-shadow">{eval var=$prefs.header_shadow_start}{/if}
        <div class="header_outer bg-{$navbar_color_variant}-parent navbar-{$navbar_color_variant} tiki-top-nav-{$navbar_color_variant}" id="header_outer">
            <div class="header_container">
                <div class="container{* {if $smarty.session.fullscreen eq 'y'}*}-fluid{*{/if}*} container-std">
                    <header class="header page-header row" id="page-header" role="banner">
                        {modulelist zone=top class="top_modules d-flex justify-content-between w-100 navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent tiki-top-nav-{$navbar_color_variant}" heading_text='{tr}Site identity, navigation, etc.{/tr}'}
                    </header>
                </div>
            </div>
        </div>
        {if $prefs.feature_layoutshadows eq 'y'}{eval var=$prefs.header_shadow_end}</div>{/if}
{/if}

    <div class="middle_outer" id="middle_outer" >
        <div class="topbar-wrapper navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent tiki-topbar-nav-{$navbar_color_variant}">
            <div class="topbar container{if $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent tiki-topbar-nav-{$navbar_color_variant}" id="topbar">
                {modulelist zone=topbar class="topbar_modules d-flex align-content-center justify-content-between w-100 navbar-{$navbar_color_variant} bg-{$navbar_color_variant} tiki-topbar-nav-{$navbar_color_variant}" heading_text='{tr}Navigation and related functionality and content{/tr}'}
            </div>
        </div>
        <div class="container{if $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std middle" id="middle">
            <div class="page-content-top-margin"  style="height: var(--tiki-page-content-top-margin)"></div>
            <div class="row row-middle" id="row-middle">
                {if (zone_is_empty('left') or $prefs.feature_left_column eq 'n') and (zone_is_empty('right') or $prefs.feature_right_column eq 'n')}
                    <div class="col col1 col-md-12 pb-4" id="col1">
                        {if $prefs.feature_layoutshadows eq 'y'}<div id="tiki-center-shadow">{eval var=$prefs.center_shadow_start}{/if}
                        {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                            {modulelist zone=pagetop heading_text='{tr}Related content{/tr}' role=complementary}
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
                                {modulelist zone=pagebottom class='mt-3' heading_text='{tr}Related content{/tr}' role=complementary}
                            {/if}
                            {if $prefs.feature_layoutshadows eq 'y'}{eval var=$prefs.center_shadow_end}</div>{/if}
                        </div>
                {elseif zone_is_empty('left') or $prefs.feature_left_column eq 'n'}
                    <div class="col col1 col-md-12 col-lg-9 {if $prefs.feature_fixed_width neq 'y'}col-xl-10{/if} pb-4" id="col1">
                        {if $prefs.feature_layoutshadows eq 'y'}<div id="tiki-center-shadow">{eval var=$prefs.center_shadow_start}{/if}
                        <div id="col1top-outer-wrapper" class="col1top-outer-wrapper d-flex justify-content-between">
                            <div class="col1top-inner-wrapper flex-grow-1 mx-2">
                                {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                                    {modulelist zone=pagetop heading_text='{tr}Related content{/tr}' role=complementary}
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
                            {modulelist zone=pagebottom class='mt-3' heading_text='{tr}Related content{/tr}' role=complementary}
                        {/if}
                        {if $prefs.feature_layoutshadows eq 'y'}{eval var=$prefs.center_shadow_end}</div>{/if}
                    </div>
                    <div class="col col3 col-12 col-md-6 col-lg-3 {if $prefs.feature_fixed_width neq 'y'}col-xl-2{/if}" id="col3">
                        {modulelist zone=right class="right-aside" heading_text='{tr}More content and functionality (right side){/tr}'}
                    </div>
                {elseif zone_is_empty('right') or $prefs.feature_right_column eq 'n'}
                    <div class="col col1 col-md-12 col-lg-9 {if $prefs.feature_fixed_width neq 'y'}col-xl-10{/if} order-md-1 order-lg-2 pb-4" id="col1">
                        {if $prefs.feature_layoutshadows eq 'y'}
                            <div id="tiki-center-shadow">{eval var=$prefs.center_shadow_start}{/if}
                            <div id="col1top-outer-wrapper" class="col1top-outer-wrapper d-flex justify-content-between">
                                <div class="d-none d-lg-flex">
                                   {if $prefs.feature_left_column eq 'user'}
                                        <div class="side-col-toggle-container d-none d-lg-block"> {* This div seems redundant but is necessary to prevent the button from being the height of the row. *}
                                            {$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'toggle-right' : 'toggle-left'}
                                            {icon name=$icon_name class='toggle_zone left btn btn-xs btn-secondary' href='#' title='{tr}Toggle left modules{/tr}'}
                                        </div>
                                   {/if}
                                </div>
                                <div class="col1top-inner-wrapper flex-grow-1 mx-2">
                                    {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                                        {modulelist zone=pagetop heading_text='{tr}Related content{/tr}' role=complementary}
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
                                {modulelist zone=pagebottom heading_text='{tr}Related content{/tr}' role=complementary}
                            {/if}
                    </div>
                    {if $prefs.feature_layoutshadows eq 'y'}{eval var=$prefs.center_shadow_end}</div>{/if}
                    <div class="col col2 col-md-6 col-lg-3 {if $prefs.feature_fixed_width neq 'y'}col-xl-2{/if} order-sm-2 order-md-2 order-lg-1" id="col2">
                        {modulelist zone=left class="left-aside" heading_text='{tr}More content and functionality (left side){/tr}'}
                    </div>
                {else}
                    <div class="col col1 col-sm-12 col-lg-8 order-xs-1 order-lg-2 pb-4" id="col1">
                        {if $prefs.feature_layoutshadows eq 'y'}<div id="tiki-center-shadow">{eval var=$prefs.center_shadow_start}{/if}
                            <div id="col1top-outer-wrapper" class="col1top-outer-wrapper d-flex justify-content-between">
                                <div class="d-none d-lg-block">
                                    {if $prefs.feature_left_column eq 'user'}
                                        <div class="side-col-toggle" style="margin-left: -10px;">
                                            {$icon_name = (not empty($smarty.cookies.hide_zone_left)) ? 'toggle-right' : 'toggle-left'}
                                            {icon name=$icon_name class='toggle_zone left btn btn-xs btn-secondary' href='#' title='{tr}Toggle left modules{/tr}'}
                                        </div>
                                    {/if}
                                </div>
                                <div class="col1top-inner-wrapper flex-grow-1 mx-2">
                                    {if $prefs.module_zones_pagetop eq 'fixed' or ($prefs.module_zones_pagetop ne 'n' && ! zone_is_empty('pagetop'))}
                                        {modulelist zone=pagetop heading_text='{tr}Related content{/tr}' role=complementary}
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
                                {modulelist zone=pagebottom class='mt-3' heading_text='{tr}Related content{/tr}' role=complementary}
                            {/if}
                            {if $prefs.feature_layoutshadows eq 'y'}{eval var=$prefs.center_shadow_end}</div>{/if}
                    </div>
                    <div class="col col2 col-12 col-md-6 col-lg-2 order-md-2 order-lg-1" id="col2">
                        {modulelist zone=left class="left-aside" heading_text='{tr}More content and functionality (left side){/tr}'}
                    </div>
                    <div class="col col3 col-12 col-md-6 col-lg-2 order-md-3" id="col3">
                        {modulelist zone=right class="right-aside" heading_text='{tr}More content and functionality (right side){/tr}'}
                    </div>
                {/if}
            </div>
        </div>

    {if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
    {if $prefs.feature_layoutshadows eq 'y'}
    <div id="footer-shadow">{eval var=$prefs.footer_shadow_start}{/if}
        <footer class="footer main-footer" id="footer">
            <div class="footer_liner">
                <div class="container{if $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std">
                    {modulelist zone=bottom class='bottom_modules p-3 mx-n2point5' heading_text='{tr}Site information, links, etc.{/tr}' role=contentinfo}
                </div>
            </div>
        </footer>
        {if $prefs.feature_layoutshadows eq 'y'}{eval var=$prefs.footer_shadow_end}</div>{/if}
{/if}

    {if $prefs.feature_layoutshadows eq 'y'}{eval var=$prefs.main_shadow_end}</div>{/if}

{include file='footer.tpl'}
</body>
</html>
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
    {debug}
{/if}
