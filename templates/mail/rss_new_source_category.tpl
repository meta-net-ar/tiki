{tr _0=$prefs.mail_template_custom_text}A new %0source category was detected by the RSS article creator at{/tr} {$mail_date|tiki_short_datetime:"":"n"}

{tr}Feed:{/tr} {$title|escape}

{tr}New categories:{/tr}
{foreach $newcats as $cat}
{$cat|escape}
{/foreach}

{tr}Configure settings for the new category at:{/tr} {mailurl}tiki-admin_rssmodules.php?article={$rssId|escape}{/mailurl}

