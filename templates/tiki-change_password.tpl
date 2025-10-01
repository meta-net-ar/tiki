{if isset($new_user_validation) && $new_user_validation eq 'y'}
    {title}{tr}Your account has been validated.{/tr}{/title}
    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}{tr}You have to choose a password to use this account.{/tr}{/remarksbox}
{else}
    {$new_user_validation='n'}
{/if}
<div class="row">
    <div class="col-md-10 offset-md-1">
        <form method="post" action="tiki-change_password.php">
            {ticket}
            <div class="card">
                {if !empty($oldpass) and $new_user_validation eq 'y'}
                    <input type="hidden" name="oldpass" value="{$oldpass|escape}">
                {elseif !empty($smarty.request.actpass)}
                    <input type="hidden" name="actpass" value="{$smarty.request.actpass|escape}">
                {/if}
                {if $new_user_validation eq 'y'}
                    <input type="hidden" name="new_user_validation" value="y">
                {/if}
                {if !empty($timestamp)}
                    <input type="hidden" name="apass" value="{$password|escape}">
                    <input type="hidden" name="timestamp" value="{$timestamp|escape}">
                {/if}
                {if !empty($secure_token)}
                    <input type="hidden" name="token" value="{$secure_token|escape}">
                {/if}
                <div class="card-header text-center">
                    {if $new_user_validation neq 'y'}
                        <h3 class="tiki-card-header-title">{tr}Change password{/tr}</h3>
                    {else}
                        <h3 class="tiki-card-header-title">{tr}Set password{/tr}</h3>
                    {/if}
                </div>
                <div class="card-body">
                    <div class="clearfix">
                        {include file='password_jq.tpl'}
                        <div class="text-center" id="divRegCapson" style="display:none;">
                            {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}{tr}CapsLock is on.{/tr}{/remarksbox}
                        </div>
                    </div>
                    {if $prefs.pass_show_rules eq 'y'}
                        <div class="clearfix">
                            <div class="text" id="divPasswordRules" >
                                {remarksbox type="info" title="{tr}Attention{/tr}" close="n"}
                                    {if $prefs.pass_due neq "-1" }
                                        {if $prefs.pass_due >= 2 }
                                            <div class="pass_due">{tr _0=$prefs.pass_due}Password change is forced when password is older than %0 days.{/tr}</div>
                                        {else}
                                            <div class="pass_due">{tr _0=$prefs.pass_due}Password change is forced when password is older than %0 day.{/tr}</tr>
                                        {/if}
                                    {/if}
                                    {if ( $prefs.auth_method != 'cas' || $user == 'admin' ) && $prefs.min_pass_length gt 0 }
                                        <div class="min_pass_length">{tr _0=$prefs.min_pass_length }Password should be at least %0 characters long.{/tr}</div>
                                    {/if}
                                    {if $prefs.pass_chr_case eq "y" }
                                        <div class="pass_chr_case">{tr}Password must contain at least one lowercase alphabetical character like "a" and one uppercase character like "A".{/tr}</div>
                                    {/if}
                                    {if $prefs.pass_repetition eq "y" }
                                        <div class="pass_repetition">{tr}Password must not contain a consecutive repetition of the same character such as "111" or "aab".{/tr}</div>
                                    {/if}
                                    {if $prefs.pass_chr_num eq "y" }
                                        <div class="pass_chr_num">{tr}Password must contain both letters and numbers.{/tr}</div>
                                    {/if}
                                    {if $prefs.pass_chr_special eq "y" }
                                        <div class="pass_chr_special">{tr}Password must contain at least one special character like " / $ % ? & * ( ) _ + ...{/tr}</div>
                                    {/if}
                                    {if $prefs.pass_diff_username eq "y" }
                                        <div class="pass_diff_username">{tr}The password must be different from the user's log-in name.{/tr}</div>
                                    {/if}
                                {/remarksbox}
                            </div>
                        </div>
                    {else}
                        {remarksbox type="warning" title="{tr}Show password rules{/tr}" close="y"}
                            <p>{tr _0='<a href="tiki-admin.php?page=login" class="alert-link">' _1='</a>'}To show the password rules the preference %0 pass_show_rules %1 should be enabled.{/tr}</p>
                        {/remarksbox}
                    {/if}
                    <div class="mb-3 row">
                        <label class="col-md-4 col-form-label" for="user">{tr}Username{/tr}</label>
                        <div class="col-md-8">
                            {if empty($userlogin)}
                                <input type="text" class="form-control" id="user" name="user" autocomplete="username">
                            {else}
                                <input type="hidden" id="user" name="user" value="{$userlogin|escape}">
                                <input type="text" class="form-control" id="user-autocomplete" name="user-autocomplete" disabled="disabled" value="{$userlogin|escape}" autocomplete="username">
                            {/if}
                        </div>
                    </div>
                    {if empty($smarty.request.actpass) and ($new_user_validation neq 'y' or empty($oldpass))}
                        <div class="mb-3 row">
                            <label class="col-md-4 col-form-label" for="oldpass">{tr}Old Password{/tr}</label>
                            <div class="col-md-8">
                                <input type="password" class="form-control" name="oldpass" id="oldpass" placeholder="{tr}Old Password{/tr}" autocomplete="current-password">
                            </div>
                        </div>
                    {/if}
                    <div class="mb-3 row">
                        <label class="col-md-4 col-form-label" for="pass1">{tr}New Password{/tr}</label>
                        <div class="col-md-8">
                            <input required type="password" class="form-control" placeholder="{tr}New Password{/tr}" name="pass" id="pass1" autocomplete="new-password">
                            <div style="margin-left:5px;">
                                <div id="mypassword_text">{icon name='ok' istyle='display:none'}{icon name='error' istyle='display:none' } <span id="mypassword_text_inner"></span></div>
                                <div id="mypassword_bar" style="font-size: 5px; height: 2px; width: 0px;"></div>
                            </div>
                            <div style="margin-top:5px">
                                {include file='password_help.tpl'}
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-md-4 col-form-label" for="pass2">{tr}Repeat Password{/tr}</label>
                        <div class="col-md-8">
                            <input required type="password" class="form-control" name="passAgain" id="pass2" placeholder="{tr}Repeat Password{/tr}" autocomplete="new-password">
                            <div id="mypassword2_text">
                                <div id="match" style="display:none">
                                    {icon name='ok' istyle='color:#0ca908'} {tr}Passwords match{/tr}
                                </div>
                                <div id="nomatch" style="display:none">
                                    {icon name='error' istyle='color:#ff0000'} {tr}Passwords do not match{/tr}
                                </div>
                            </div>
                        </div>
                    </div>
                    {if $prefs.generate_password eq 'y'}
                        <div class="mb-3 row">
                            <div class="col-md-4 offset-md-4">
                                <span id="genPass">{button href="#" _text="{tr}Generate a password{/tr}"}</span>
                            </div>
                            <div class="col-md-4">
                                <input id='genepass' class="form-control" name="genepass" type="text" tabindex="0" style="display:none">
                            </div>
                        </div>
                    {/if}
                    {if empty($email)}
                        <div class="mb-3 row">
                            <label class="col-md-4 col-form-label" for="email">{tr}Email{/tr}</label>
                            <div class="col-md-8">
                                <input required type="email" class="form-control" name="email" id="email" placeholder="{tr}Email{/tr}" value="{if not empty($email)}{$email|escape}{/if}" autocomplete="email">
                            </div>
                        </div>
                    {/if}
                </div>
                <div class="card-footer text-center">
                    <input type="submit" class="btn btn-primary" name="change" onclick="return checkPasswordsMatch('#pass2', '#pass1', '#mypassword2_text');" value="{tr}Apply{/tr}"><span id="validate"></span>
                </div>
            </div>
        </form>
    </div>
</div>
