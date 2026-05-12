{$mail_body|truncate:$mail_truncate:"..."}

---
{tr _0=$prefs.mail_template_custom_text}A new %0message was posted to you.{/tr} {tr}Click here to read the full message and / or reply:{/tr}
{mailurl}messu-mailbox.php?msgId={$messageid}{/mailurl}

{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}
