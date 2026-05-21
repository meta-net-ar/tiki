{if $objecttype eq 'wiki'}
{tr _0=! empty($prefs['mail_template_custom_text']) ? $prefs.mail_template_custom_text : '' _1=$mail_objectname}The %0Wiki page "%1" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{* Blog comment mail *}
{elseif $objecttype eq 'blog'}
{tr _0=! empty($prefs['mail_template_custom_text']) ? $prefs.mail_template_custom_text : '' _1=$mail_objectname}The %0Blog post "%1" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.

{elseif $objecttype eq 'article'}
{tr _0=! empty($prefs['mail_template_custom_text']) ? $prefs.mail_template_custom_text : '' _1=$mail_objectname}The %0article "%1" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{elseif $objecttype eq 'trackeritem'}
{tr _0=! empty($prefs['mail_template_custom_text']) ? $prefs.mail_template_custom_text : '' _1=$mail_item_title _2=$mail_objectname}The %0tracker item "%1" of tracker "%2" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{/if}
