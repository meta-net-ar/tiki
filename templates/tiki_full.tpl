<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if !empty($page_id)} id="page_{$page_id}"{/if}>
<head>
    {include file='header.tpl'}
</head>
<body{if $printpdf ne 'y'}{$html_body_attributes}{/if}>

{* Index we display a wiki page here *}
{if Language::isRTL()}
<div dir="rtl">
{/if}
{if $prefs.feature_ajax eq 'y'}
    {include file='tiki-ajax_header.tpl'}
{/if}
{if $is_slideshow eq 'y'}
    <div class="reveal">
        <div class="slides">
            {$mid_data}
        </div>
    </div>
    {if $printpdf ne 'y'}
        <div id="ss-settings-holder" title="{tr}Click for slideshow operations{/tr}"><span class="fas fa-cogs" style="font-size:1rem;color:#666" id="ss-settings"></span></div>
        <div id="ss-options" class="d-flex flex-row justify-content-around align-content-end flex-wrap">
            <div class="p-2">
                <select id="showtheme" class="form-control">
                    <option value="">{tr}Change Theme{/tr}</option>
                    {$themeOptions}
                </select>
            </div>
            <div class="p-2">
                <select id="showtransition" class="form-control">
                    <option value="">{tr}Change Transition{/tr}</option>
                    <option value="zoom">{tr}Zoom{/tr}</option>
                    <option value="fade">{tr}Fade{/tr}</option>
                    <option value="slide">{tr}Slide{/tr}</option>
                    <option value="convex">{tr}Convex{/tr}</option>
                    <option value="concave">{tr}Concave{/tr}</option>
                    <option value="">{tr}Off{/tr}</option>
                </select>
            </div>
            <div class="p-2" id="reveal-controls"><span class="fas fa-fast-backward me-1"  id="firstSlide" title="{tr}Go to First Slide{/tr}"></span><span class="fas fa-step-backward me-1" id="prevSlide" title="{tr}Go to Previous Slide{/tr}"></span><span class="fas fa-play-circle me-1" id="play"></span><span class="fas fa-undo me-1 icon-inactive" id="loop" title="{tr}Auto-play in loop{/tr}"></span><span class="fas fa-step-forward me-1"  id="nextSlide" title="{tr}Go to Next Slide{/tr}"></span><span class="fas fa-fast-forward"  id="lastSlide" title="{tr}Go to Last Slide{/tr}"></span></div>
            <div class="p-2" id="listSlides"><span class="fas fa-list me-1"   title="{tr}List Slides{/tr}"></span>{tr}List Slides{/tr}</div>

            {if $prefs.feature_slideshow_pdfexport eq 'y'}
                <div class="p-2"><a href="tiki-slideshow.php?page={$page}&print-pdf=1" target="_blank" id="exportPDF"><span class="far fa-file-pdf"></span>{tr}Export PDF{/tr}</a></div>
                <div class="p-2"><a href="tiki-slideshow.php?page={$page}&pdf=1&landscape=1" target="_blank"><span class="fas fa-print"></span>{tr}Handouts{/tr}</a></div>
            {/if}

            <div class="p-2"><a href="tiki-index.php?page={$page}"><span class="fas fa-sign-out-alt"></span>{tr}Exit Slideshow{/tr}</a></div>
        </div>
    {/if}
{else}
    <div id="main">
        <div id="tiki-center">
            <div id="role_main">
                {$mid_data}
            </div>
        </div>
    </div>
{/if}
{if Language::isRTL()}
    </div>
{/if}
{include file='footer.tpl'}
</body>
</html>
