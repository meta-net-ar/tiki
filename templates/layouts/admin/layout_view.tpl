<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if Language::isRTL()} dir="rtl"{/if}>
    <head>
    {include file='header.tpl'}
    </head>
    <body{html_body_attributes class="tiki-admin"}>
{$cookie_consent_html}

{include file="layout_fullscreen_check.tpl"}

{if $prefs.feature_ajax eq 'y'}
    {include file='tiki-ajax_header.tpl'}
{/if}
        <a class="btn btn-info btn-lg skipnav" href="#col1" role="button">{tr}Skip to main content{/tr}</a>
        <div class="container{if isset($smarty.session.fullscreen) && $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std">
    {if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
            <div class="row">
                <header class="page-header w-100 navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent tiki-top-nav-{$navbar_color_variant}" id="page-header" role=banner>
                {modulelist zone=top class="top_modules navbar-{$navbar_color_variant}-parent bg-{$navbar_color_variant}-parent  tiki-top-nav-{$navbar_color_variant}" heading_text='{tr}Site identity, navigation, etc.{/tr}'}
               </header>
            </div>
    {/if}
            <div class="row row-middle" id="row-middle">
        {modulelist zone=topbar class="topbar_modules topbar navbar-{$navbar_color_variant} bg-{$navbar_color_variant} tiki-topbar-nav-{$navbar_color_variant} w-100 mb-sm" heading_text='{tr}Navigation and related functionality and content{/tr}'}
                <div class="page-content-top-margin"  style="height: var(--tiki-page-content-top-margin)"></div>
                <div class="col col1 col-md-12 pb-4" id="col1">
                    <div id="feedback" role="alert">
                        {feedback}
                    </div>
                    {queued_tasks_banner}
                    {block name=quicknav}{/block}
                    {block name=title}{/block}
                    {block name=navigation}{/block}
                    <main>
                        <div class="admin-wrapper highlightable">
                            <aside class="admin-nav">
                                {include file='admin/include_anchors.tpl'}
                            </aside>
                            <div class="admin-content w-100 mx-3-lg">
                                {include file="admin/admin_navbar.tpl"}
                                {block name=content}{/block}
                            </div>
                        </div>
                    </main>
                </div>
            </div>

{if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
            <footer class="row footer main-footer" id="footer">
                <div class="footer_liner w-100">
            {modulelist zone=bottom class='bottom_modules p-3 mx-0' heading_text='{tr}Site information, links, etc.{/tr}' role=contentinfo}
                </div>
            </footer>
{/if}
        </div>{* CONTAINER END *}
{include file='footer.tpl'}
    </body>
</html>
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
    {debug}
{/if}
