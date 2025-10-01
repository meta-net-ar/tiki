{title help="Webauthn" url="tiki-webauthn.php"}{tr}Registered Authenticators{/tr}{/title}
{if $prefs.auth_webauthn_enabled eq 'y'}
    <div class="row">
        <div class="col-md-4 mb-3">
            <button class="btn btn-primary" id="registerWebAuthDevice" type="button">{tr}Register Device{/tr}</button>
        </div>
    </div>
{/if}
{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}
<div id="webauthn-div">
    <div class="table-responsive webauthn">
        <table id="webauthn_table" class="table normal table-striped table-hover" data-count="{$devices|count}">
            <thead>
                <th>{tr}User{/tr}</th>
                <th>{tr}Authenticator{/tr}</th>
                <th>{tr}Device{/tr}</th>
                <th>{tr}Sign Count{/tr}</th>
                <th>{tr}Created{/tr}</th>
                <th>{tr}Last SignIn{/tr}</th>
                <th>{tr}Action{/tr}</th>
            </thead>
            <tbody>
                {section name=device loop=$devices}
                    <tr>
                        <td> {$devices[device].user} </td>
                        <td> {$devices[device].authenticator_name} </td>
                        <td> {$devices[device].device_name} </td>
                        <td> {$devices[device].sign_count} </td>
                        <td> {$devices[device].created_at} </td>
                        <td> {$devices[device].last_signin} </td>
                        <td class="action">
                            {actions}
                                <action>
                                    <a href="tiki-webauthn.php?authenticator_id={$devices[device].id}&amp;action=remove" onclick="confirmPopup('{tr}Delete authenticator?{/tr}', '{ticket mode=get}')">
                                        {icon name='delete' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                    </a>
                                </action>
                            {/actions}
                        </td>
                    </tr>
                {/section}
            </tbody>
        </table>
    </div>
</div>
{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}
{jq}
    $('#registerWebAuthDevice').on('click', async function(event) {
        const registeredUser = "{{$user}}";
        try {
            if (!window.PublicKeyCredential) {
                throw new Error(tr("Your browser does not support password less login feature."));
            }
            if (!window.isSecureContext) {
                throw new Error(tr("Passkey feature requires a secure (HTTPS) connection. Please ensure you are using a secure website."));
            }
            const result = await $.fn.registerWebAuth.createCredentials(event, registeredUser);
            if (result) {
                const feedbackClass = result.status === "success" ? "alert-success" : "alert-danger";
                $("#tikifeedback").html(
                    `<div class="alert ${feedbackClass} alert-dismissible">
                        ${result.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`
                );

                if (result.code === 'AUTHENTICATOR_REGISTRED') {
                    window.location.reload();
                }
            }
        } catch (error) {
            $("#tikifeedback").html(
                `<div class="alert alert-danger alert-dismissible">
                    ${error}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`
            );
        }
    });
{/jq}
