{if $objecttype eq 'wiki'}
{tr}The {if ! empty($prefs.mail_template_custom_text)}{$prefs.mail_template_custom_text}{/if}Wiki page "{$mail_objectname}" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{* Blog comment mail *}
{elseif $objecttype eq 'blog'}
{tr}The {if ! empty($prefs.mail_template_custom_text)}{$prefs.mail_template_custom_text}{/if}Blog post "{$mail_objectname}" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.

{elseif $objecttype eq 'article'}
{tr}The {if ! empty($prefs.mail_template_custom_text)}{$prefs.mail_template_custom_text}{/if}article "{$mail_objectname}" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{elseif $objecttype eq 'trackeritem'}
{tr}The {if ! empty($prefs.mail_template_custom_text)}{$prefs.mail_template_custom_text}{/if}tracker item "{$mail_item_title}" of tracker "{$mail_objectname}" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{/if}
