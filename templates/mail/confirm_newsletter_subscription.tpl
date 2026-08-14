{tr noparse=n _0=$prefs.mail_template_custom_text}Someone tried to subscribe this email address at our %0site:{/tr} {$server_name}
{tr noparse=n}To the newsletter:{/tr} {$info.name}

{tr noparse=n}Description:{/tr}
{$info.description}

{tr noparse=n}Please access the following URL to confirm your subscription:{/tr}

{mailurl}tiki-newsletters.php?confirm_subscription={$code}{/mailurl}
