{if $mail_action eq 'deleted'}{tr noparse=n _0=$mail_itemId _1=$mail_trackerName _2=$mail_user|username _3=$mail_date|tiki_short_datetime:"":"n"}Tracker item %0 was deleted in the tracker %1 by %2 on %3{/tr}
{elseif $mail_action eq 'status'}{tr noparse=n _0=$mail_itemId _1=$mail_item_desc _2=$prefs.mail_template_custom_text _3=$mail_trackerName}New status for ItemID %0 %1 for the %2tracker %3:{/tr} {if $status eq 'o'}{tr noparse=n}open{/tr}{elseif $status eq 'p'}{tr noparse=n}pending{/tr}{elseif $status eq 'c'}{tr noparse=n}closed{/tr}{/if}
{else}{$mail_action}

{tr noparse=n _0=$prefs.mail_template_custom_text}View the %0tracker item at:{/tr}
    {mailurl}{$mail_itemId|sefurl:'trackeritem'}{/mailurl}
{/if}

{if $mail_action eq 'deleted'}
{if $mail_fields}
{tr noparse=n}The last content before deletion was as follows:{/tr}

Status: {$mail_field_status}
{foreach from=$mail_fields key=id item=item}
{if !empty($item.value)}
----------
[{tr noparse=n}{$item.name}{/tr}]:
{$item.value}
{/if}
{/foreach}
----------
{/if}
{else}
{tr noparse=n}Author:{/tr} {$mail_user|username}
{tr noparse=n}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}
{/if}

{$mail_data|replace:'-[':''|replace:']-':''}{* TODO: translate these -[...]- marked strings in $mail_data by watcher language *}
{* {$mail_data|replace:"\n\n":"\n"|replace:":\n":": "} to reduce the number of line *}

{if isset($mail_attId)}
    {tr noparse=n}Download the file at:{/tr} {mailurl}tiki-download_item_attachment.php?attId={$mail_attId}{/mailurl}
{/if}
