{title}{tr}Rename page:{/tr} {$page}{/title}

<div class="navbar" role="navigation">
    {assign var=thispage value=$page|escape:url}
    {button href="tiki-index.php?page=$thispage" _icon_name="file" _class="btn navbar-btn" _text="{tr}View page{/tr}"}
</div>

<form action="tiki-rename_page.php" method="post" role="form" class="d-flex flex-row flex-wrap align-items-center mt-3">
    {ticket}
    <input type="hidden" name="page" value="{$page|escape}">
    {if isset($page_badchars_display)}
        {if $prefs.wiki_badchar_prevent eq 'y'}
            {remarksbox type=errors title="{tr}Error{/tr}"}
                {tr _0=$page_badchars_display|escape}The page name specified contains unallowed characters. It will not be possible to save the page until those are removed: <strong>%0</strong>{/tr}
            {/remarksbox}
        {else}
            {remarksbox type=tip title="{tr}Tip{/tr}"}
                {tr _0=$page_badchars_display|escape}The page name specified contains characters that may render the page hard to access. You may want to consider removing those: <strong>%0</strong>{/tr}
            {/remarksbox}
            <input type="hidden" name="badname" value="{$newname|escape}">
            <input type="submit" class="btn btn-primary btn-sm" name="confirm" value="{tr}Use this name anyway{/tr}">
        {/if}
    {/if}
    <label for="newpage" class="col-form-label me-2">{tr}New name{/tr}</label>
        <input type='text' id='newpage' name='newpage' class="form-control me-3" maxlength="158" value='{$newname|escape}'>
            {if $prefs.feature_wiki_pagealias eq 'y'}
                <input type='checkbox' id='semantic_alias' name='semantic_alias' value='y' class="me-2"> {tr}Redirect original page{/tr}
                <a tabindex="0" target="_blank" data-bs-toggle="popover" data-bs-trigger="hover" title="{tr}301 Redirect - 'moved permanently' HTTP response status code{/tr}" data-bs-content="{tr}Create an SEO-friendly, automatic redirect from old page name to new page name (ex.: for search engines or users that may have bookmarked the page){/tr}">
                    {icon name='information'}
                </a>
            {/if}

        <input type="submit" class="btn btn-primary ms-3" name="rename" value="{tr}Rename{/tr}">

</form>

{jq}
    $("input[name=newpage]").keyup(function () {
        var length = $(this).val().length;
        if(length > 158) {
            alert(tr("You have exceeded the number of characters allowed (158 max) for the page name field"));
        }
    });
{/jq}
