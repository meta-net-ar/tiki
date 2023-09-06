<!DOCTYPE html>
{if ! $plain}
    {block name=title}{/block}
{/if}
{block name=content}{/block}
{* Add JS when it is not an action confirm prompt to avoid adding JS twice.
This is managed by $confirmation_action variable initiated in lib/tikiaccesslib.php *}
{if $headerlib && empty($confirmation_action) and $confirmation_action ne 'y'}
    {$headerlib->output_js_config()}
    {$headerlib->output_js_files()}
    {$headerlib->output_js()}
{/if}
{if $prefs.feature_debug_console eq 'y' and not empty($smarty.request.show_smarty_debug)}
    {debug}
{/if}
