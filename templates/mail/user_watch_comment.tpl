{if $objecttype eq 'wiki'}
{tr _0=! empty($prefs['mail_template_custom_text']) ? $prefs.mail_template_custom_text : '' _1=$mail_objectname}The %0Wiki page "%1" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{* Blog comment mail *}
{elseif $objecttype eq 'blog'}
{tr _0=!empty($prefs['mail_template_custom_text']) ? $prefs.mail_template_custom_text : '' _1=$mail_objectname}The %0Blog post "%1" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{elseif $objecttype eq 'article'}
{tr _0=!empty($prefs['mail_template_custom_text']) ? $prefs.mail_template_custom_text : '' _1=$mail_objectname}The %0article "%1" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{elseif $objecttype eq 'trackeritem'}
{tr _0=!empty($prefs['mail_template_custom_text']) ? $prefs.mail_template_custom_text : '' _1=$mail_item_title _2=$mail_objectname}The %0tracker item "%1" of tracker "%2" was commented on by{/tr} {if $mail_user}{$mail_user|username}{else}{tr}an anonymous user{/tr}{/if}.
{/if}

{tr}You can view the comment by following this link:{/tr}
{if $objecttype eq 'wiki'}
{mailurl}{$mail_objectname|sefurl}#threadId={$comment_id}{/mailurl}
{* Blog comment mail *}
{elseif $objecttype eq 'blog'}
{mailurl}{$mail_objectid|sefurl:'blogpost'}#threadId={$comment_id}{/mailurl}
{elseif $objecttype eq 'article'}
{mailurl}{$mail_objectid|sefurl:'article'}#threadId={$comment_id}{/mailurl}
{elseif $objecttype eq 'trackeritem'}
{mailurl}{$mail_objectid|sefurl:'trackeritem'}#threadId={$comment_id}{/mailurl}
{/if}

{tr}Title:{/tr} {$mail_title}
{tr}Comment:{/tr} {$mail_comment}
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}

{if $watchId}
{tr}If you don't want to receive these notifications follow this link:{/tr}
{mailurl}tiki-user_watches.php?id={$watchId}{/mailurl}
{/if}

