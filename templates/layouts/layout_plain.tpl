{capture assign=modzonetop}
    {modulelist zone=top class='top_modules d-flex justify-content-between'}
{/capture}
{capture assign=modzonetopbar}
    {modulelist zone=topbar}
{/capture}
{capture assign=modzonebottom}
    {modulelist zone=bottom}
{/capture}
<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if !empty($page_id)} id="page_{$page_id}"{/if}{if Language::isRTL()} dir="rtl"{/if}>
    <head>
        {include file='header.tpl'}
    </head>
    <body{html_body_attributes}>
        {$cookie_consent_html}

        {include file="layout_fullscreen_check.tpl"}

        {if $prefs.feature_ajax eq 'y'}
            {include file='tiki-ajax_header.tpl'}
        {/if}

        <div class="container{if isset($smarty.session.fullscreen) && $smarty.session.fullscreen eq 'y'}-fluid{/if} container-std middle" id="middle">
{if !isset($smarty.session.fullscreen) || $smarty.session.fullscreen ne 'y'}
            <div class="row">
                <header class="page-header w-100" id="page-header">
                    {$modzonetop}
                </header>
            </div>
{/if}
            <div class="row">
                <div class="col-md-12">
                    {$modzonetopbar}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12" id="col1">
                    {block name=title}{/block}
                    {block name=navigation}{/block}
                    {feedback}
                    {queued_tasks_banner}
                    {block name=content}{/block}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 well">
                    {$modzonebottom}
                </div>
            </div>
        </div>

        {include file='footer.tpl'}
    </body>
</html>
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
    {debug}
{/if}
