{tr _0=$prefs.mail_template_custom_text _1=$mail_title _2=$mail_post_title _3=$mail_user|username _4=$mail_date|tiki_short_datetime:"":"n"}New %0blog post: %1, "%2", by %3 at %4{/tr}
{if $mail_contributions}

{tr}Contribution:{/tr} {$mail_contributions}{/if}

{tr}View the blog at:{/tr}
{mailurl}{$mail_postid|sefurl:blogpost}{/mailurl}

{tr}If you don't want to receive these notifications follow this link:{/tr}
{mailurl}tiki-user_watches.php?id={$watchId}&hash={$watchUnsubscribeHash}{/mailurl}
