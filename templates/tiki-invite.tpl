{title url="tiki-invite.php"}{tr}Invitation{/tr}{/title}
{assign var=invite_action value="{$base_url}tiki-invite.php"}

<div class="t_navbar mb-4">
    {button href="tiki-list_invite.php" _type="link" _icon_name="list" _text="{tr}Invitations List{/tr}"}
</div>

{if $sentresult}
    <div class="highlight">{tr}The mail has been sent to: {/tr}</div>
    <ul>
        {foreach from=$emails item=mail}
            <li>{$mail.email|escape}</li>
        {/foreach}
    </ul>
    <form method='POST' action="{$invite_action}">
        <input type='submit' name='return' value="{tr}Ok{/tr}" class="btn btn-primary">
    </form>
{elseif $smarty.request.send && !$smarty.request.confirm && !$smarty.request.back}
    <div class="highlight mt-2">{tr}You are about to send an invitation to theses people, please confirm :{/tr}</div>
    <ul>
        {foreach from=$emails item=mail}
            <li>{$mail.email|escape}</li>
        {/foreach}
    </ul>
    <form method='POST' action="{$invite_action}">
        <input type='hidden' name='emailslist' value='{$smarty.request.emailslist|escape}'>
        <input type='hidden' name='emailslist_format' value='{$smarty.request.emailslist_format|escape}'>
        <input type='hidden' name='emailsubject' value='{$smarty.request.emailsubject|escape}'>
        <input type='hidden' name='emailcontent' value='{$smarty.request.emailcontent|escape}'>
        <input type='hidden' name='wikicontent' value='{$smarty.request.wikicontent|escape}'>
        <input type='hidden' name='wikipageafter' value='{$smarty.request.wikipageafter|escape}'>
        <input type='hidden' name='send' value='{$smarty.request.send|escape}'>
        {foreach from=$smarty.request.invitegroups item=g}
            <input type='hidden' name='invitegroups[]' value='{$g|escape}'>
        {/foreach}

        <input type='submit' name='back' value="{tr}Go back{/tr}" class="btn btn-secondary">
        <input type='submit' name='confirm' value="{tr}Ok{/tr}" class="btn btn-primary">
    </form>

{else}
    <hr>
    <form method='POST' action="{$invite_action}">
        <div class="mb-3 row">
            <label class="col-form-label col-sm-5" for="loadprevious">{tr}Load a previous invitation settings{/tr}</label>
            <div class="col-sm-7">
                <select name='loadprevious' onchange='this.form.submit()' class="form-select">
                    <option value=''>-</option>
                    {foreach from=$previous item=prev}
                        <option value='{$prev.id}'>{$prev.datetime|escape}, {tr}by{/tr} {$prev.inviter|escape}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-form-label col-sm-5" for="emailslist">{tr}Fill this box with the list of emails you want to invite{/tr}</label>
            <div class="col-sm-7">
                <textarea name='emailslist' class="form-control" style='width: 100%; height: 150px;'>{$smarty.request.emailslist|escape}</textarea>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-form-label col-sm-5" for="emailslist_format">{tr}Format of the list above{/tr}</label>
            <div class="col-sm-7">
                <div class="form-check">
                    <input class="form-check-input" type='radio' name='emailslist_format' value='csv' {if (! $smarty.request.emailslist_format) || $smarty.request.emailslist_format == 'csv'}checked{/if} >
                    <label class="form-check-label">{tr}CSV Style: One line per invitation, with the format: lastname,firstname,email{/tr}</label>
                </div>
                <br>
                <div class="form-check">
                    <input class="form-check-input" type='radio' name='emailslist_format' value='all' {if $smarty.request.emailslist_format == 'all'}checked{/if}>
                    <label class="form-check-label">{tr}Everything that appear as an email in the text will be detected and used (in that case, \{literal}{firstname} and {lastname}{/literal} will be ignored in the email content){/tr}</label>
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-form-label col-sm-5" for="emailsubject">{tr}Type here the email subject you'll want to be sent to them{/tr}</label>
            <div class="col-sm-7">
                <input name='emailsubject' class="form-control" value='{if isset($smarty.request.emailsubject)}{$smarty.request.emailsubject|escape}{else}Invitation{/if}'>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-form-label col-sm-5" for="emailcontent">{tr}Type here the email content you'll want to be sent to them (and let the \{literal}{link}{/literal} word, it will be replaced with the good link for registering){/tr}</label>
            <div class="col-sm-7">
                <textarea name='emailcontent' class="form-control" style='width: 100%; height: 150px;'>{tr}{if isset($smarty.request.emailcontent)}{$smarty.request.emailcontent|escape}{else}Hi \{literal}{firstname} {lastname}{/literal},

We would like to invite you to register on our web site
To register, just follow this link:

{literal}{link}{/literal}

Kind regards
{/if}{/tr}</textarea>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-form-label col-sm-5" for="wikicontent">{tr}Type here the content that the user will see when he'll click on the link from the mail{/tr}</label>
            <div class="col-sm-7">
                <textarea name='wikicontent' class="form-control" style='width: 100%; height: 150px;'>{tr}{if isset($smarty.request.emailcontent)}{$smarty.request.wikicontent|escape}{else}Hi \{literal}{firstname} {lastname}{/literal},

You are here because you have just clicked on the link from my invitation email.

{/if}{/tr}</textarea>
            </div>
        </div>
        {if count($invitegroups) > 0 && count($usergroups) > 0}
            <div class="mb-3 row">
                <label class="col-form-label col-sm-5" for="invitegroups">{tr}Choose one or more groups that you want these subscriptions to be in. Don't choose any if you don't want anything special{/tr}</label>
                <div class="col-sm-7">
                    <select multiple="multiple" name='invitegroups[]' id='invitegroups' class="form-select">
                        {foreach from=$usergroups item=ug}
                            <option value='{$ug|escape}' {if is_array($smarty.request.invitegroups) && in_array($ug,$smarty.request.invitegroups)}selected{/if}>{$ug|escape}{if !empty($invitegroups[$ug])} ({$invitegroups[$ug]|escape}){/if}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {else}
            <input type='hidden' name='invitegroups' value=''>
        {/if}
        <div class="mb-3 row">
            <label class="col-form-label col-sm-5" for="wikipageafter">{tr}Redirect to this wiki page after invitation acceptance (leave it blank if unwanted){/tr}</label>
            <div class="col-sm-7">
                <input type='text' class="form-control" name='wikipageafter' value='{$smarty.request.wikipageafter|escape}'>
            </div>
        </div>

        <div class="mb-3 clearfix text-center">
            <input type='submit' name='send' value="{tr}Send{/tr}" class="btn btn-primary">
        </div>
    </form>
{/if}
