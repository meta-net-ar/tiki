{if $mail_action eq 'new'}{tr _0=$prefs.mail_template_custom_text _1=$mail_page _2=$mail_user|username _3=$mail_date|tiki_short_datetime:"":"n"}The %0page "%1" was created by %2 at %3{/tr}
{elseif $mail_action eq 'delete'}{tr _0=$prefs.mail_template_custom_text _1=$mail_page _2=$mail_user|username _3=$mail_date|tiki_short_datetime:"":"n"}The %0page "%1" was deleted by %2 at %3{/tr}
{elseif $mail_action eq 'attach'}{tr _0=$prefs.mail_template_custom_text _1=$mail_page}A file was attached to %0"%1"{/tr}
{else}{tr _0=$prefs.mail_template_custom_text _1=$mail_page _2=$mail_user|username _3=$mail_date|tiki_short_datetime:"":"n"}The %0page "%1" was changed by %2 at %3{/tr}
{/if}

{if $mail_comment}{tr}Comment:{/tr} {$mail_comment}
{/if}
{if not empty($mail_contributions)}{tr}Contribution:{/tr} {$mail_contributions}
{/if}

{if $mail_action eq 'delete'}{tr _0=$prefs.mail_template_custom_text _1=$mail_page}The page %0"%1" was deleted but used to be here:{/tr}
{else}{tr}You can view the page by following this link:{/tr}
{/if}
{mailurl}{$mail_page|sefurl}{/mailurl}

{if $mail_action eq 'edit'}{tr}You can view a diff back to the previous version by following this link:{/tr} {* Using the full diff syntax so the links are still valid, even after a new version has been made. -rlpowell *}
{mailurl}tiki-pagehistory.php?page={$mail_page|escape:"url"}&compare=1&oldver={$mail_oldver}&newver={$mail_newver}{/mailurl}
{elseif $mail_action eq 'attach'}{$mail_data} : {mailurl}tiki-download_wiki_attachment.php?attId={$mail_attId}{/mailurl}
{/if}

{if $watchId}
    {tr}If you don't want to receive these notifications follow this link:{/tr}
    {mailurl}tiki-user_watches.php?id={$watchId}{/mailurl}
{/if}

***********************************************************
{if $mail_diffdata}{tr}The changes in this version follow below, followed after by the current full page text.{/tr}
{if $has_md5_content_diagrams}
{tr}Diagram plugin content was replaced with MD5 hash for version comparison.{/tr}
{/if}
***********************************************************

{section name=ix loop=$mail_diffdata}
{if $mail_diffdata[ix].type == "diffheader"}
{$oldd=$mail_diffdata[ix].old}
{$newd=$mail_diffdata[ix].new}

+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
@@ {tr _0=$oldd _1=$newd}-Lines: %0 changed to +Lines: %1{/tr} @@
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
{elseif $mail_diffdata[ix].type == "diffdeleted"}
{section name=iy loop=$mail_diffdata[ix].data}
- {$mail_diffdata[ix].data[iy]|strip_tags:false|htmldecode}
{/section}
{elseif $mail_diffdata[ix].type == "diffadded"}
{section name=iy loop=$mail_diffdata[ix].data}
+ {$mail_diffdata[ix].data[iy]|strip_tags:false|htmldecode}
{/section}
{elseif $mail_diffdata[ix].type == "diffbody"}
{section name=iy loop=$mail_diffdata[ix].data}
{$mail_diffdata[ix].data[iy]|strip_tags:false|htmldecode}
{/section}
{/if}
{/section}

{* if $mail_diffdata *}
{/if}


***********************************************************
{if $mail_action eq 'delete'}{tr}The old page content follows below.{/tr}
{else}{tr}The new page content follows below.{/tr}
{/if}
***********************************************************

{$mail_pagedata}
