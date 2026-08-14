{strip}
{if isset($close_window) and $close_window eq 'y'}
{jq}
close();
{/jq}
{/if}
{*
 * 404: page does not exist
 * no_redirect_login: error antibot, system...
 * login: error login
 * Usually available variables : $errortitle, $msg, $errortype
 * If $commenttype is 'note' and $msg is set, the $msg will be shown in a nicer non-treatening remarksbox
 *}
{if !isset($errortype)}{$errortype=''}{/if}
{capture assign=mid_data}

    {$errortitle="{tr}Error{/tr}"}

    {if $errortype eq "404" and isset($file_error)}
        {remarksbox type='errors' title="{tr}File error{/tr}"}
            {$file_error|escape}
        {/remarksbox}
    {elseif $errortype eq "404" and isset($page)}
        {remarksbox type='errors' title=$errortitle}
            {tr}Page not found{/tr}<br>{$page|escape}
        {/remarksbox}
        {if $prefs.feature_likePages eq 'y'}
            {if $likepages}
                <p>{tr}Perhaps you are looking for:{/tr}</p>
                <ul>
                    {section name=back loop=$likepages}
                        <li><a href="{$likepages[back]|sefurl:"wiki"}" class="wiki">{$likepages[back]|escape}</a></li>
                    {/section}
                </ul>
            {else}
                {remarksbox type="tip" title="{tr}Information{/tr}"}
                    {tr _0=$page|escape}There are no wiki pages similar to '%0'{/tr}
                {/remarksbox}
            {/if}
        {/if}

        {if $prefs.feature_search eq 'y' && $tiki_p_search eq 'y'}
            {include file='tiki-searchindex_form.tpl' searchNoResults="true" searchStyle="menu" searchOrientation="horiz" words="$page" filter=$filter}
        {/if}
    {elseif $commenttype eq "note" and isset($msg)}
        {remarksbox type='note' title=$title}
            {$msg|safe_html}
        {/remarksbox}
    {else}
        {if isset($token_error)}
            {remarksbox type='errors' title="{tr}Token Error{/tr}"}
                {$token_error|escape}
            {/remarksbox}
        {elseif !isset($user) and $errortype != 'no_redirect_login' and $errortype != 'login' and empty($msg)}
            {remarksbox type='errors' title=$errortitle}
                {tr}You are not logged in.{/tr} <a href="tiki-login_scr.php" class="alert-link">{tr}Go to Log in Page{/tr}</a>
            {/remarksbox}
        {else}
            {remarksbox type='errors' title=$errortitle}
                {$msg|safe_html}
                {if !empty($required_preferences)}
                    {remarksbox type='note' title="{tr}Settings{/tr}" close="n"}
                    <form method="post" action="tiki-admin.php" class="form">
                        {ticket}
                        {foreach from=$required_preferences item=pref}
                            {preference name=$pref visible="always"}
                        {/foreach}
                        <div class="text-center">
                            <input type="submit" class="btn btn-primary" value="{tr}Apply{/tr}">
                        </div>
                        {if isset($gobackto)}
                            <input type="hidden" name="gobackto" value="{$gobackto|escape}">
                        {/if}
                    </form>
                    {/remarksbox}
                {/if}
            {/remarksbox}
        {/if}
    {/if}

    {if isset($extraButton)}
        {remarksbox type='errors' title=$errortitle}
        {$extraButton.comment|safe_html}
        {button href=$extraButton.href _text=$extraButton.text}
        {/remarksbox}
    {/if}

    {if isset($page) and $page and $create eq 'y' and ($tiki_p_admin eq 'y' or $tiki_p_admin_wiki eq 'y' or $tiki_p_edit eq 'y')}
        {button href="tiki-editpage.php?page=$page" _text="{tr}Create this page{/tr}"} <span class="ms-3">{tr}(page will be orphaned){/tr}</span>
        <br><br>
    {/if}

    {* Hide the error navigation on the homepage *}
    {if !isset($page) or $prefs.site_wikiHomePage neq $page}
        <div class="t_navbar mb-3">
        {button href=$prefs.tikiIndex _type="link" _icon_name="home" _text="{tr}Return to home page{/tr}"}
        {button _type="link" _icon_name="arrow-left" _onclick="javascript:history.back();return false;" _text="{tr}Go back{/tr}" _ajax="n"}
        </div>
    {/if}
{/capture}

{include file='tiki.tpl'}
{/strip}
