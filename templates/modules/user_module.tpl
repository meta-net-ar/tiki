{tikimodule error=$user_module_params.error title=$user_module_params.title|default:$user_title name=$user_module_name flip=$user_module_params.flip decorations=$user_module_params.decorations overflow=$user_module_params.overflow nobox=$user_module_params.nobox notitle=$user_module_params.notitle type=$module_type}
{* This will be nested 'box-data' div... *}
<div id="{$user_module_name|stringfix:' ':'_'}" {if (isset($smarty.cookies.$user_module_name) && $smarty.cookies.$user_module_name ne 'c') || !isset($smarty.cookies.$user_module_name)}style="display:block;"{else}style="display:none;"{/if}>
{eval var=$user_data}
</div>
{/tikimodule}
