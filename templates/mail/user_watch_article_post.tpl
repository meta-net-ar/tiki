{if $mail_action eq 'New'}{tr _0=$prefs.mail_template_custom_text}New %0article post:{/tr}{/if}{if $mail_action eq 'Edit'}{tr _0=$prefs.mail_template_custom_text}Edit %0article post:{/tr}{/if}{if $mail_action eq 'Delete'}{tr _0=$prefs.mail_template_custom_text}Delete %0article post:{/tr}{/if} {tr _0=$mail_title _1=$mail_user|username}%0 by %1 at{/tr} {$mail_date|tiki_short_datetime:"":"n"}

{if $mail_action neq 'Delete'}{tr}View the article at:{/tr} {mailurl}{$mail_postid|sefurl:article}{/mailurl}{/if}


{$mail_title}


{$mail_current_data}


-----------------------------------------------------------
{tr}Publish Date:{/tr} {$mail_current_publish_date|tiki_short_datetime:"":"n"}

{if !empty($watchId)}{tr}If you don't want to receive these notifications follow this link:{/tr}
{mailurl}tiki-user_watches.php?id={$watchId}&hash={$watchUnsubscribeHash}{/mailurl}{/if}

{if isset($mail_old_data)}

***********************************************************
{tr}The old article follows.{/tr}
***********************************************************
{tr}Title:{/tr} {$mail_old_title}

{tr}Publish Date:{/tr} {$mail_old_publish_date|tiki_short_datetime:"":"n"}
***********************************************************
{tr}Content{/tr}
***********************************************************
{$mail_old_data}
{/if}
