{tr _0=$prefs.mail_template_custom_text}Someone tried to subscribe this email address at our %0site:{/tr} {$server_name}
{tr}To the newsletter:{/tr} {$info.name}

{tr}Description:{/tr}
{$info.description}

{tr}Please access the following URL to confirm your subscription:{/tr}

{mailurl}tiki-newsletters.php?confirm_subscription={$code}{/mailurl}
