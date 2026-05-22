{$object_link="<a href='{$objectId|sefurl:$type}'>{$objectId|escape}</a>"}
{tr _0=$plugin_name _1=$object_link}Plugin %0 is pending approval on %1.{/tr}

{tr _0=$prefs.mail_template_custom_text _1="<a href='{$base_url|escape}tiki-plugins.php'>" _2="</a>"}See all the %0pending plugins in the %1plugin approval page%2.{/tr}

{if !empty($arguments)}
    <b>{tr}Plugin arguments:{/tr}</b>
    {foreach $arguments as $key => $value}
        * {$key}: {$value}
    {/foreach}
{/if}

{if !empty($body)}
    <b>{tr}Plugin body:{/tr}</b>
    {$body|escape}
{/if}
