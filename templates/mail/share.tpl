{if !empty($comment)}
{$prefs.mail_template_custom_text}{$comment}
{else}
{tr _0=$prefs.mail_template_custom_text}Look at this %0link: {/tr}
{/if}
{$url_for_friend|replace:' ':'+'}
-
{$name|username}
{$email}
