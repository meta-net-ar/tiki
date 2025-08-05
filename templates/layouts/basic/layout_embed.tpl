<!DOCTYPE html>
<html lang="{if !empty($pageLang)}{$pageLang}{else}{$prefs.language}{/if}"{if Language::isRTL()} dir="rtl"{/if}{if !empty($page_id)} id="page_{$page_id}"{/if}>
<head>
    {include file='header.tpl'}
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="container container-std">
    {feedback}
    {block name=title}{/block}
    {block name=navigation}{/block}
    {block name=content}{/block}
</div>
{include file='footer.tpl'}
</body>
</html>
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
    {debug}
{/if}
