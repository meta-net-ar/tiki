{tr}Hi,{/tr}

{tr _0=$prefs.mail_template_custom_text}An administrator of the %0 site below has added you as a new user:{/tr}
    {if !empty($prefs.sitetitle)}{$prefs.sitetitle} - {/if}{$mail_site}

{tr}If you want to confirm your membership in this site, click on the following link to login for the first time:{/tr}
    {mailurl}{$mail_link}?user={$mail_user|escape:'url'}&pass={$mail_apass}{/mailurl}

{tr}Welcome to the site!{/tr}

