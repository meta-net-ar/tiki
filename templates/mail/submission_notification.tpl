{tr _0=$prefs.mail_template_custom_text _1=$mail_user|username _2=$mail_site _3=$mail_date|tiki_short_datetime:"":"n"}A new %0article was submitted by %1 to %2 at %3{/tr}

{tr}You can edit the submission following this link:{/tr} {mailurl}tiki-edit_submission.php?subId={$mail_subId}{/mailurl}

{tr}Title:{/tr} {$mail_title}

{tr}Heading:{/tr}
{$mail_heading}

{tr}Body:{/tr}
{$mail_body}
