{if empty($user)}

    {include file='password_jq.tpl'}
    <div class="alert alert-warning" id="divRegCapson" style="display: none;">{icon name='error' style="vertical-align:middle"} {tr}CapsLock is on.{/tr}</div>
    {if $allowRegister eq 'y'}
        <div class="row">
            <div class="col-sm-12">
                {if $userTrackerData}
                    {$userTrackerData}
                {else}
                    <form action="tiki-register.php{if !empty($prefs.registerKey)}?key={$prefs.registerKey|escape:'url'}{/if}" method="post" name="RegForm">
                        {ticket}
                        {if !empty($smarty.request.invite)}<input type='hidden' name='invite' value='{$smarty.request.invite|escape}'>{/if}
                        {include file="register-form.tpl"}
                        {if $merged_prefs.feature_antibot eq 'y'}{include file='antibot.tpl' form='register'}{/if}
                        <div class="row mb-4">
                            {if $prefs.auth_webauthn_enabled eq 'y'}{include file='webauthn.tpl' form='register'}{/if}
                            <div class="col-sm-8 offset-sm-4">
                              <input type="hidden" name="register" value="1">
                              <button class="btn btn-primary registerSubmit submit" id="registerSubmit" name="register" type="submit">{tr}Register{/tr} {*<i class="fa fa-check"></i>*}</button>
                            </div>
                        </div>
                    </form>
                {/if}
            </div>
        </div>
        <div class="col-sm-8 offset-sm-4">
            {remarksbox type="note" title="{tr}Note{/tr}"}
                {if $prefs.feature_wiki_protect_email eq 'y'}
                    {$sender_email=$prefs.sender_email|default:"this domain"|escape:'hexentity'}
                {else}
                    {$sender_email=$prefs.sender_email|default:"this domain"|escape}
                {/if}
                {tr _0="$sender_email"}If you use an email filter, be sure to add %0 to your accepted list{/tr}
            {/remarksbox}
        </div>
        {jq}
            $('form[name="RegForm"]').on("submit", async function (event) {
                event.preventDefault();
                var isWebauthnEnabled = "{{$prefs.auth_webauthn_enabled}}";
                var isWebAuthnIsChoosen = $("#webauthn_checkbox_register").is(':checked') && isWebauthnEnabled === 'y';
                var regUser = $(this).find('#name').val();
                if (isWebAuthnIsChoosen) {
                    try {
                        if (!window.PublicKeyCredential) {
                            throw new Error(tr("Your browser does not support password less login feature."));
                        }
                        if (!window.isSecureContext) {
                            throw new Error(tr("Passkey feature requires a secure (HTTPS) connection. Please ensure you are using a secure website."));
                        }
                        const result = await $.fn.registerWebAuth.createCredentials(event, regUser);
                        if (result && result.code === 'AUTHENTICATOR_EXIST') {
                            $("#tikifeedback").html(
                                `<div class="alert alert-danger alert-dismissible">
                                    ${result.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>`
                            );
                            return;
                        }
                        this.submit();
                    } catch (error) {
                        $("#tikifeedback").html(
                            `<div class="alert alert-danger alert-dismissible">
                                ${error}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>`
                        );
                        return;
                    }
                } else {
                    this.submit();
                }
            });
        {/jq}
    {/if}
{else}
    {include file='modules/mod-login_box.tpl' nobox='y'}
{/if}
