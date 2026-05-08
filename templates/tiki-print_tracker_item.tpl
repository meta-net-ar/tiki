<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if Language::isRTL()} dir="rtl"{/if}>
    <head>
{include file='header.tpl'}
    </head>
    <body{html_body_attributes}>
        <div id="tiki-clean">
            <div id="tiki-mid">
{$mid_data}
            </div>
        </div>
    </body>
</html>
