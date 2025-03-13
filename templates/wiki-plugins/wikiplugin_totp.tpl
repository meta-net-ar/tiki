<div id="totp_{$id}">
    <input hidden type="text" value="get_code" name="action">
    <div class="panel panel-primary">
        <div class="panel-heading"> <h3 class="panel-title">{$issuer}</h3> </div>
        <div class="panel-body"> <h1>{tr _0='<strong class="code">--</strong>' _1='<span class="time">--</span>'}Code: %0 Expires in: %1 seconds.{/tr}</h1>
            {if $sourcePerm}<a class="twoFactorAuthShow" href="#">{tr}Show QRCode{/tr}</a>{/if}</div>
        {if $sourcePerm}
        <div class="col-md-12 card twoFactorAuthCard" style="display: none">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">{$tfaSecretQR}</div>
                    <div class="col-md-6 align-content-center">
                        <div class="d-flex align-items-center" style="height: 100%">
                            <ol>
                                <li>{tr}Install an authenticator app on your device and open it{/tr}.</li>
                                <li>{tr}Tap “Scan a barcode” or a similar option in the app{/tr}.</li>
                                <li>{tr}Scan the QR code that is open in your browser{/tr}.</li>
                                <li>{tr}Done, the authenticator app is now generating codes{/tr}.</li>
                            </ol>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        {/if}
    </div>
</div>
{jq}

    $("#totp_{{$id}}").initTOTP();

{/jq}
