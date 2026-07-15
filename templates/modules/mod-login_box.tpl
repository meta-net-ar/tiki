{$TWO_FA_INCORRECT = TWO_FA_INCORRECT}
{$USER_NOT_FOUND = USER_NOT_FOUND}
{$PASSWORD_INCORRECT = PASSWORD_INCORRECT}
{jq notonready=true}
function capLock(e, el){
    kc = e.keyCode ? e.keyCode : e.which;
    sk = e.shiftKey ? e.shiftKey : (kc == 16 ? true : false);
    if ((kc >= 65 && kc <= 90 && !sk) || (kc >= 97 && kc <= 122 && sk)) {
        $('.divCapson', $(el).parents('div').first()).show();
    } else {
        $('.divCapson', $(el).parents('div').first()).hide();
    }
}
{/jq}
{jq}
var currentLoggedInUser = "{{$user|escape:'javascript'}}";
$(document).ready(function () {
    var twoFAType = "{{$prefs.twoFactorAuthType}}";
    var is2FAEnabled = "{{$prefs.twoFactorAuth}}";
    var moduleLogoInstance = "{{$module_logo_instance}}";
    var login_error = "{{$error_login}}";

    function show2FactorInputElement(btn, event) {
        const btnStep = parseInt(btn.attr('step')) + 1;
        btn.attr('step', btnStep);
        $(event.currentTarget).find('#two_factor_div').siblings().not('#two_factor_div').hide();
        $(event.currentTarget).find('#two_factor_div').show();
        $(btn).parent().show();
    }

    function handleEmail2FA(username, btn, event) {
        $.ajax({
            url: $.service("two_fa_auth", "IsMFARequired"),
            type: 'POST',
            data: { username: username },
            success: async function (res) {
                if (!res) {
                    $(event.currentTarget).off('submit').submit();
                } else {
                    if (twoFAType === '{{$GOOGLE_2FA}}') {
                        show2FactorInputElement(btn, event);
                    } else {
                        generate2FACode(username, btn, event);
                    }
                }
            },
            error: function(req, status, error) {
                displayFeedback("error", error);
            }
        });
    }

    function generate2FACode(username, btn, event) {
        $.ajax({
            url: $.service("two_fa_auth", "GenerateCode"),
            type: 'POST',
            data: { username: username },
            success: function (data) {
                if (data.success) {
                    displayFeedback("success", data.message);
                    show2FactorInputElement(btn, event);
                } else {
                    displayFeedback("error", data.message);
                }
            },
            error: function(req, status, error) {
                displayFeedback("error", error);
            }
        });
    }

    function getTwoFactorSecretGoogle2FA(username) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: $.service("two_fa_auth", "Get2FactorSecret"),
                type: 'POST',
                data: { username: username },
                success: function (data) {
                    if (data.success) {
                        resolve(data.twoFactorSecret);
                    } else {
                        displayFeedback("error", data.message);
                        resolve(null);
                    }
                },
                error: function (req, status, error) {
                    displayFeedback("error", error);
                    reject(error);
                }
            });
        });
    }

    function validateUserCredentials(username, password) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: $.service("user", "ValidateUser"),
                type: 'POST',
                data: { username: username, password: password },
                success: function (res) {
                    resolve(res);
                },
                error: function (req, status, error) {
                    displayFeedback("error", error);
                    reject(error);
                }
            });
        });
    }

    $("#loginbox-{{$module_logo_instance}}").on("submit", async function (event) {
        event.preventDefault();

        var btn = $("button.submit", this);
        const originalBtnHtml = btn.html();

        const spinnerHtml = `
            <div class="spinner-border spinner-border-sm text-light" role="status">
            <span class="sr-only">Loading...</span>
            </div>
        `;

        btn.html(spinnerHtml + tr("Logging in..."));
        btn.prop("disabled", true);

        try {
            const isLoginScreen = parseInt($(event.currentTarget).parent('#login_form_div').length);
            const isNormalLogin = "{{$create2FaCodeNormalLogin}}";

            if (isNormalLogin === 'y') {
                let btnStep = parseInt(btn.attr('step')) + 1;
                btn.attr('step', btnStep);
            }

            const username = $("#login-user_{{$module_logo_instance}}").val();
            const password = $("#login-pass_{{$module_logo_instance}}").val();
            let btnStep = parseInt(btn.attr('step'));

            if (isNormalLogin === 'y') {
                $(this).off('submit').submit();
                return false;
            }

            if ((username && password)) {
                if (is2FAEnabled === 'y') {
                    const twoFASecret = await getTwoFactorSecretGoogle2FA(username);

                    if (btnStep === 1) {
                        const isValidateUser = await validateUserCredentials(username, password);
                        if (!isValidateUser) {
                            displayFeedback("error", tr("Invalid username or password"));
                            return false;
                        }
                    }

                    if (btnStep > 1 || isLoginScreen === 0 || (twoFASecret == 'n' && twoFAType === '{{$GOOGLE_2FA}}')) {
                        $(this).off('submit').submit();
                        return false;
                    }
                    await handleEmail2FA(username, btn, event);

                } else {
                    $(this).off('submit').submit();
                }
            } else {
                $("#login-user_{{$module_logo_instance}}").trigger("focus");
                return false;
            }

        } catch (error) {
            displayFeedback("error", tr("An unexpected error occurred"));
        } finally {
            btn.html(originalBtnHtml);
            btn.prop("disabled", false);
        }
    });
});
function displayFeedback(type, message) {
    const feedbackClass = type === "success" ? "alert-success" : "alert-danger";
    $("#login_feeback_{{$module_logo_instance}}").html(
        `<div class="alert ${feedbackClass} alert-dismissible">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>`
    );
}
if (jqueryTiki.no_cookie) {
    $('.box-login_box input').each(function(){
        $(this).change(function() {
            if (jqueryTiki.no_cookie && ! jqueryTiki.cookie_consent_alerted && $(this).val()) {
                alert(jqueryTiki.cookie_consent_alert);
                jqueryTiki.cookie_consent_alerted = true;
            }
        });
    });
}
$("#switchbox-{{$module_logo_instance}} .submit").click( function () {
    if ($("#login-switchuser_{{$module_logo_instance}}").val()) {
        confirmPopup('{tr}Switch user?{/tr}')
        return true;
    } else {
        $("#login-switchuser_{{$module_logo_instance}}").focus();
        return false;
    }
});
{/jq}
{if !isset($tpl_module_title)}{* Left for performance, since tiki-login_scr.php includes this template directly. *}
    {assign var=tpl_module_title value="{tr}Log in{/tr}"}
    {if !isset($module_params)}{assign var=module_params value=' '}{/if}
    {if isset($nobox)}{$module_params.nobox = $nobox}{/if}
    {if isset($style)}{$module_params.style = $style}{/if}
{/if}
{tikimodule error="{if isset($module_params.error)}{$module_params.error}{/if}" title=$tpl_module_title name="login_box" flip="{if isset($module_params.flip)}{$module_params.flip}{/if}" decorations=$module_params.decorations nobox="{if isset($module_params.nobox)}{$module_params.nobox}{/if}" notitle="{if isset($module_params.notitle)}{$module_params.notitle}{/if}" style=$module_params.style}
    {if $mode eq "header"}<div class="siteloginbar{if $user} logged-in{/if}">{/if}
    {if $user}
        {if empty($mode) or $mode eq "module"}
            <div class="form-group row mx-0">{tr}Logged in as:{/tr} <span class="d-inline-block col-12 text-truncate">&nbsp;{$user|userlink}</span></div>
            <div class="text-center">
                {button href="tiki-logout.php" _text="{tr}Log out{/tr}"}
            </div>
            {if $login_module.can_revert}
                <form action="{$login_module.login_url|escape}" method="post">
                    {ticket}
                    <fieldset>
                        <legend>{tr}Return to Main User{/tr}</legend>
                        <input type="hidden" name="su" value="revert" />
                        <input type="hidden" name="username" value="auto" />
                        <div class="text-center">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                name="actsu"
                                onclick="confirmPopup('{tr}Return to main user?{/tr}')"
                            >
                                {tr}Switch{/tr}
                            </button>
                        </div>
                    </fieldset>
                </form>
            {elseif $tiki_p_admin eq 'y'}
                <form action="{$login_module.login_url|escape}" method="post"{if $prefs.desactive_login_autocomplete eq 'y'} autocomplete="off"{/if} id="switchbox-{$module_logo_instance}">
                    {ticket}
                    <fieldset>
                        <legend>{tr}Switch User{/tr}</legend>
                        <div class="form-group row mx-0">
                            <label class="col-form-label" for="login-switchuser_{$module_logo_instance}">
                                {if $prefs.login_is_email eq 'y'}
                                    {if $prefs.login_is_email_obscure eq 'n'}
                                        {tr}Email:{/tr}
                                    {else}
                                        {tr}Name:{/tr}
                                    {/if}
                                {else}
                                    {if $prefs.login_allow_email eq 'y'}
                                        {tr}Email address or {/tr}
                                    {/if}
                                    {if $prefs.login_autogenerate eq 'y'}
                                        {tr}User account ID:{/tr}
                                    {else}
                                        {tr}Username:{/tr}
                                    {/if}
                                {/if}
                            </label>
                            <input type="hidden" name="su" value="1" class="form-control" />
                            {if $prefs.feature_help eq 'y'}
                                {help url="Switch+User" desc="{tr}Help{/tr}" desc="{tr}Switch User:{/tr}{tr}Enter a username and click 'Switch'.<br>Useful for testing permissions.{/tr}"}
                            {/if}
                            {user_selector groupIds=$module_params.groups id="login-switchuser_"|cat:$module_logo_instance name='username' user='' editable=$tiki_p_admin class='form-control'}
                        </div>
                        <div class="text-center">
                            <button
                                type="submit"
                                class="btn btn-primary submit"
                                name="actsu"
                            >
                                {tr}Switch{/tr}
                            </button>
                        </div>
                    </fieldset>
                </form>
            {/if}
        {elseif $mode eq "header"}
            <span style="white-space: nowrap">{$user|userlink}</span> <a href="tiki-logout.php" title="{tr}Log out{/tr}">{tr}Log out{/tr}</a>
        {elseif $mode eq "popup"}
            <div class="siteloginbar_popup dropdown float-sm-right mr-auto" role="group">
                <button type="button" class="dropdown-toggle login_link btn btn-link" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {if isset($module_params.show_user_avatar) && $module_params.show_user_avatar eq 'y'}{$user|avatarize:n:n:n:n}{/if}
                    {if isset($module_params.show_user_name) && $module_params.show_user_name eq 'y'}{$user|username:n:n:n}{/if}
                    {if (!isset($module_params.show_user_avatar) || $module_params.show_user_avatar neq 'y') and (!isset($module_params.show_user_name) || $module_params.show_user_name neq 'y')}{tr}Log out{/tr}{/if}
                    <span class="sr-only">{tr}Toggle Dropdown{/tr}</span>
                </button>
                {if empty($module_params.menu_id)}
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="tiki-user_information.php" title="{tr}My Account{/tr}">
                            {if isset($module_params.show_user_name) && $module_params.show_user_name eq 'y'}{tr}My Account{/tr}{else}{tr}{$user|username|escape|replace:'&amp;':'&'}{/tr}{/if}
                        </a>
                        <a class="dropdown-item" href="tiki-logout.php" title="{tr}Log out{/tr}">
                            {tr}Log out{/tr}
                        </a>
                    </div>
                {else}
                    {menu id=$module_params.menu_id bootstrap='y' bs_menu_class='dropdown-menu'}
                    {jq}
// prevent clicks on menu items with child options from closing the "parent" dropdown
$(".collapse-toggle", ".siteloginbar_popup .dropdown-menu").click(function () {
    $(this).parents(".dropdown").one("hide.bs.dropdown", function () {
        return false;
    });
});
                    {/jq}
                {/if}
            </div>
        {/if}
        {if $prefs.auth_method eq 'openid' and $openid_userlist|@count gt 1}
            <form method="get" action="tiki-login_openid.php">
                <fieldset>
                    <legend>{tr}Switch user{/tr}</legend>
                    <select name="select">
                    {foreach item=username from=$openid_userlist}
                        <option{if $username eq $user} selected="selected"{/if}>{$username}</option>
                    {/foreach}
                    </select>
                    <input type="hidden" name="action" value="select"/>
                    <input type="submit" class="btn btn-primary" value="{tr}Go{/tr}"/>
                </fieldset>
            </form>
        {/if}
    {elseif $prefs.auth_method eq 'cas' && $showloginboxes neq 'y'}
        <b><a class="linkmodule" href="tiki-login.php?cas=y">{tr}Log in through CAS{/tr}</a></b>
        {if $prefs.cas_skip_admin eq 'y'}
            <br><a class="linkmodule" href="tiki-login_scr.php?user=admin">{tr}Log in as admin{/tr}</a>
        {/if}
    {elseif $prefs.auth_method eq 'shib' && $showloginboxes neq 'y'}
        <b><a class="linkmodule" href="tiki-login.php">{tr}Log in through Shibboleth{/tr}</a></b>
        {if $prefs.shib_skip_admin eq 'y'}
            <br><a class="linkmodule" href="tiki-login_scr.php?user=admin">{tr}Log in as admin{/tr}</a>
        {/if}
    {elseif $prefs.auth_method eq 'saml' && $showloginboxes neq 'y'}
        <b><a class="linkmodule" href="tiki-login.php?auth=saml">{tr}
        {if $prefs.saml_option_login_link_text eq ''}
            Log in through SAML2 IdP
        {else}
            {$prefs.saml_option_login_link_text}
        {/if}
        {/tr}</a></b>
        {if $prefs.saml_options_skip_admin eq 'y'}
            <br /><a class="linkmodule" href="tiki-login_scr.php?user=admin">{tr}Log in as admin{/tr}</a>
        {/if}
    {else}
        <div id="login_form_div">
        {assign var='close_tags' value=''}
        {if $mode eq "popup"}
            <div class="siteloginbar_popup dropdown btn-group float-sm-right drop-left">
                <button type="button" class="btn btn-link dropdown-toggle" data-toggle="dropdown">
                    {tr}Log in{/tr}
                </button>
                <div class="siteloginbar_poppedup dropdown-menu dropdown-menu-right float-sm-right modal-sm"><div class="card-body">
                    {capture assign="close_tags"}</div></div></div>{$close_tags}{/capture}
        {/if}

        <form name="loginbox" class="form{if $mode eq "header"} form-inline{/if}" id="loginbox-{$module_logo_instance}" action="{$login_module.login_url|escape}"
                method="post"
                {if $prefs.desactive_login_autocomplete eq 'y'} autocomplete="off"{/if}
                data-normalLogin="{$create2FaCodeNormalLogin}"
        >
        {ticket}
        {capture assign="close_tags"}</form>{$close_tags}{/capture}


        {if !empty($urllogin)}<input type="hidden" name="url" value="{$urllogin|escape}" />{/if}
        {if $module_params.nobox neq 'y'}
            <fieldset>
                {capture assign="close_tags"}</fieldset>{$close_tags}{/capture}
        {/if}
        {if !empty($error_login)}
            {remarksbox type='errors' title="{tr}Error{/tr}"}
                {if $error_login == $PASSWORD_INCORRECT or $error_login == $USER_NOT_FOUND}{tr}Invalid username or password{/tr}
                {elseif $error_login == $TWO_FA_INCORRECT}{tr}Invalid two-factor authenticator code{/tr}
                {else}{$error_login|escape}{/if}
            {/remarksbox}
        {/if}
        <div id="login_feeback_{$module_logo_instance}"></div>
        <div class="user form-group row mx-0 clearfix" style="display: {if $create2FaCodeNormalLogin === 'y'} none; {else} block; {/if}">
            {if !isset($module_logo_instance)}{assign var=module_logo_instance value=' '}{/if}
            <label for="login-user_{$module_logo_instance}">
                {if $prefs.login_is_email eq 'y'}
                    {tr}Email:{/tr}
                {else}
                    {if $prefs.login_allow_email eq 'y'}
                        {tr}Email address or {/tr}
                    {/if}
                    {if $prefs.login_autogenerate eq 'y'}
                        {tr}User account ID:{/tr}
                    {else}
                        {tr}Username:{/tr}
                    {/if}
                {/if}
            </label>
            {if !isset($loginuser) or $loginuser eq ''}
                    <input class="form-control" type="text" name="user" id="login-user_{$module_logo_instance}" {if !empty($error_login)} value="{$error_user|escape}"{elseif !empty($adminuser)} value="{$adminuser|escape}"{/if} {if $prefs.desactive_login_autocomplete neq 'y'}autocomplete="username"{/if}/>
                {jq}if ($('#login-user_{{$module_logo_instance}}:visible').length) {if ($("#login-user_{{$module_logo_instance}}").offset().top < $(window).height()) {$('#login-user_{{$module_logo_instance}}')[0].focus();} }{/jq}
            {else}
                <input class="form-control" type="hidden" name="user" id="login-user_{$module_logo_instance}" value="{$loginuser|escape}" /><b>{$loginuser|escape}</b>
            {/if}
        </div>
        <div class="pass form-group row mx-0 clearfix" style="display: {if $create2FaCodeNormalLogin === 'y'} none; {else} block; {/if}">
            <label for="login-pass_{$module_logo_instance}">{tr}Password:{/tr}</label>
            <input onkeypress="capLock(event, this)" type="password" name="pass" class="form-control" id="login-pass_{$module_logo_instance}" autocomplete="{if $prefs.desactive_login_autocomplete eq 'y'}new-password{else}current-password{/if}">
            {if $module_params.show_forgot eq 'y' && $prefs.forgotPass eq 'y'}
                <br><a class="mt-1" href="tiki-remind_password.php" title="{tr}Click here if you've forgotten your password{/tr}">{tr}I forgot my password{/tr}</a>
            {/if}
            <div class="divCapson" style="display:none;">
                {icon name='error' istyle="vertical-align:middle"} {tr}CapsLock is on.{/tr}
            </div>
        </div>
        <input type="hidden" name="login_mode" value="{$mode}" />
        {if $prefs.twoFactorAuth eq 'y' and ((isset($module_params.show_two_factor_auth) and $module_params.show_two_factor_auth eq 'y') or ! empty($error_login))}
        <div id="two_factor_div" class="pass form-group row mx-0 clearfix" style="display: {if $create2FaCodeNormalLogin === 'y'} block; {else} none; {/if}">
            <label for="login-2fa_{$module_logo_instance}">{tr}Two-factor authentication code:{/tr}</label>
            <input type="text" name="twoFactorAuthCode" autocomplete="off" class="form-control" id="login-2fa_{$module_logo_instance}">
            {if $prefs.twoFactorAuthType eq $EMAIL_2FA}
                <small class="text-muted">{tr}Please type the 6 digit security code sent to your email address{/tr}</small>
                <a class="mt-1 d-block" href="#" onclick="$('#loginbox-{{$module_logo_instance}}').data('normalLogin', '2fa-regen').submit()" title="{tr}Click here if you've not received the code and want to send a new one.{/tr}">{tr}I didn't receive the code{/tr}</a>
            {else}
                <small class="text-muted">{tr}Please type the 6 digit security code (TOTP) provided by your authenticator application{/tr}</small>
            {/if}
        </div>
        {/if}
        {if $prefs.rememberme ne 'disabled' and (empty($module_params.remember) or $module_params.remember neq 'n') and $create2FaCodeNormalLogin ne 'y'}
            {if $prefs.rememberme eq 'always'}
                <input type="hidden" name="rme" id="login-remember-module-input_{$module_logo_instance}" value="on" />
            {else}
                <div class="form-check">
                    <div class="checkbox rme">
                        <label for="login-remember-module_{$module_logo_instance}"><input type="checkbox" class="form-check-input" name="rme" id="login-remember-module_{$module_logo_instance}" value="on" />
                            {tr}Remember me{/tr}
                            ({tr}for{/tr}
                            {if $prefs.remembertime eq 300}
                                5 {tr}minutes{/tr})
                            {elseif $prefs.remembertime eq 900}
                                15 {tr}minutes{/tr})
                            {elseif $prefs.remembertime eq 1800}
                                30 {tr}minutes{/tr})
                            {elseif $prefs.remembertime eq 3600}
                                1 {tr}hour{/tr})
                            {elseif $prefs.remembertime eq 7200}
                                2 {tr}hours{/tr})
                            {elseif $prefs.remembertime eq 36000}
                                10 {tr}hours{/tr})
                            {elseif $prefs.remembertime eq 72000}
                                20 {tr}hours{/tr})
                            {elseif $prefs.remembertime eq 86400}
                                1 {tr}day{/tr})
                            {elseif $prefs.remembertime eq 604800}
                                1 {tr}week{/tr})
                            {elseif $prefs.remembertime eq 2629743}
                                1 {tr}month{/tr})
                            {elseif $prefs.remembertime eq 31556926}
                                1 {tr}year{/tr})
                            {/if}
                        </label>
                    </div>
                </div>
            {/if}
        {/if}

        <div class="form-group text-center">
            <button class="btn btn-primary button submit" type="submit" name="login" step="1">{tr}Log in{/tr}</button>
        </div>
        {if $module_params.show_register eq 'y' or $prefs.twoFactorAuth eq 'y'}
            <div {if $mode eq 'header'}class="text-right" style="display:inline;"{/if}>
                {strip}
                    <div {if $mode eq 'header'}style="display: inline-block"{/if}><ul class="{if $mode neq 'header'}list-unstyled"{else}list-inline"{/if}>
                        {if $module_params.show_register eq 'y' && $prefs.allowRegister eq 'y'}
                            <li class="register{if $mode eq 'popup'} dropdown-item{/if} list-item"><a href="tiki-register.php{if !empty($prefs.registerKey)}?key={$prefs.registerKey|escape:'url'}{/if}" class="dropdown-item" title="{tr}Click here to register{/tr}"{if !empty($prefs.registerKey)} rel="nofollow"{/if}>{tr}Register{/tr}</a></li>
                        {/if}
                        {if $prefs.twoFactorAuth eq 'y' and $module_params.show_two_factor_auth ne 'y'}
                            {if $mode eq 'header' && $module_params.show_forgot eq 'y' && $prefs.forgotPass eq 'y'}
                                &nbsp;|&nbsp;
                            {/if}
                            <li class="pass{if $mode eq 'popup'} dropdown-item{/if} list-item"><a href="tiki-login_scr.php?showTwoFactorForm" title="{tr}Login with two-factor authenticator{/tr}">{if $mode eq 'popup'} {tr}Login with 2FA{/tr}{else}{tr}Login with two-factor authenticator{/tr}{/if}</a></li>
                        {/if}
                    </ul></div>
                {/strip}
            </div>
        {else}
            &nbsp;
        {/if}
        {if $prefs.feature_switch_ssl_mode eq 'y' && ($prefs.https_login eq 'allowed' || $prefs.https_login eq 'encouraged')}
            <div>
                <a class="linkmodule" href="{$base_url_http|escape}{$prefs.login_url|escape}" title="{tr}Click here to log in using the default security protocol{/tr}">{tr}Standard{/tr}</a>
                <a class="linkmodule" href="{$base_url_https|escape}{$prefs.login_url|escape}" title="{tr}Click here to log in using a secure protocol{/tr}">{tr}Secure{/tr}</a>
            </div>
        {/if}
        {if $prefs.feature_show_stay_in_ssl_mode eq 'y' && $show_stay_in_ssl_mode eq 'y'}
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="stay_in_ssl_mode" id="login-stayssl_{$module_logo_instance}" {if $stay_in_ssl_mode eq 'y'}checked="checked"{/if} />
                <label class="form-check-label" for="login-stayssl_{$module_logo_instance}">{tr}Stay in SSL mode{/tr}</label>
            </div>
        {/if}
        {* This is needed as unchecked checkboxes are not sent. The other way of setting hidden field with same name is potentially non-standard *}
        <input type="hidden" name="stay_in_ssl_mode_present" value="y" />
        {if $prefs.feature_show_stay_in_ssl_mode neq 'y' || $show_stay_in_ssl_mode neq 'y'}
            <input type="hidden" name="stay_in_ssl_mode" value="{$stay_in_ssl_mode|escape}" />
        {/if}


        {if isset($use_intertiki_auth) and $use_intertiki_auth eq 'y'}
            <select name='intertiki'>
                <option value="">{tr}local account{/tr}</option>
                <option value="">-----------</option>
                {foreach key=k item=i from=$intertiki}
                    <option value="{$k}">{$k}</option>
                {/foreach}
            </select>
        {/if}
        {if $prefs.auth_method eq 'openid_connect' && isset($openidconnect_redirect_url) && $showloginboxes neq 'y'}
            <div class="form-group text-center" style="margin-top: 1rem">
                <a href="{$openidconnect_redirect_url}" class="btn btn-primary">
                    {tr _0=$prefs.openidconnect_name}Log in via %0{/tr}
                </a>
            </div>
        {/if}
        <div class="social-buttons">
            {foreach from=$prefs.socnets_enabledProviders  key=k  item=pNum}
                {$providerName = $socnetsAll[$pNum]}
                {if $prefs["socnets_`$providerName`_loginEnabled"] eq 'y' and $mode neq "header" and empty($user)}
                {button _icon_name="{$providerName|lower}" _text="{tr}Log in via {/tr}{$providerName}" _class="btn btn-social btn-{$providerName|lower}" _script="tiki-login_hybridauth.php" _auto_args=provider provider="{$providerName}"  _title="{tr}Log in via {/tr}{$providerName}"}
                {/if}
            {/foreach}
        </div>
        {$close_tags}
        </div>
        {if $prefs.auth_method eq 'openid' and !$user and (!isset($registration) || $registration neq 'y')}
            <form method="get" action="tiki-login_openid.php">
                <fieldset>
                    <legend>{tr}OpenID Log in{/tr}</legend>
                    <div class="form-group row mx-0">
                        <div class="input-group input-group-sm">
                            <input class="form-control" type="text" name="openid_url"/>
                            <span class="input-group-append"><button type="submit" class="btn btn-primary" title="{tr}Go{/tr}"><img alt="{tr}OpenID Login{/tr}" class="img-circle" src="img/icons/login-OpenID-bg.gif"></button></span>
                        </div>
                        <span class="form-text"><a class="linkmodule tikihelp" target="_blank" href="http://doc.tiki.org/OpenID">{tr}What is OpenID?{/tr}</a></span>
                    </div>
                </fieldset>
            </form>
        {/if}
    {/if}
    {if $mode eq "header"}</div>{/if}
{/tikimodule}
