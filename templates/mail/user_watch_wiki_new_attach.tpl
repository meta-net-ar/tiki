{tr _0=$prefs.mail_template_custom_text}A new file has been attached to %0page{/tr} {$mail_page|sefurl} {tr}by{/tr} {$mail_user|username}.
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}

{tr}File name:{/tr} {$mail_att_name}
{tr}Type:{/tr} {$mail_att_type}
{tr}Size:{/tr} {$mail_att_size}
{tr}Comment:{/tr} {$mail_att_comment}

{mailurl}{$mail_page|sefurl}{/mailurl}

{tr}If you don't want to receive these notifications follow this link:{/tr}
{mailurl}tiki-user_watches.php?id={$watchId}&hash={$watchUnsubscribeHash}{/mailurl}
