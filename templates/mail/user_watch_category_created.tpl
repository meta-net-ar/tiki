{tr _0=$prefs.mail_template_custom_text}A new %0category was created in:{/tr} {$parentName}

{tr}Created by:{/tr} {$author|username}
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}
{tr}Name:{/tr} {$categoryName}
{tr}Path:{/tr} {$categoryPath}
{tr}Description:{/tr} {$description}

{mailurl}{$categoryId|sefurl:category}{/mailurl}