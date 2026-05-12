{if $mail_action eq 'New'}{tr _0=$prefs.mail_template_custom_text}New %0article post at{/tr}{/if}{if $mail_action eq 'Edit'}{tr _0=$prefs.mail_template_custom_text}Edited %0article post at{/tr}{/if}{if $mail_action eq 'Delete'}{tr _0=$prefs.mail_template_custom_text}Deleted %0article post at{/tr}{/if} %s {*get_string {tr}New article post at{/tr} *}
{*get_string {tr}Edited article post at{/tr} *}
{*get_string {tr}Deleted article post at{/tr} *}
