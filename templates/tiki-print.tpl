<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if !empty($page_id)} id="print_page_{$page_id}"{/if}{if Language::isRTL()} dir="rtl"{/if}>
    <head>
{include file='header.tpl'}
    </head>
    <body{html_body_attributes}>

        <div id="tiki-clean">
            <div id="tiki-mid">
{$mid_data}
            </div>
        </div>

{if empty($skip_footer) or $skip_footer ne 'y'}
    {include file='footer.tpl'}
{/if}
    </body>
</html>
