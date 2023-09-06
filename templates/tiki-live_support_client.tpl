<!DOCTYPE html>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        {* <link rel="StyleSheet" href="styles/{$prefs.style}" type="text/css"> *}
        <title>{tr}Live support:User window{/tr}</title>
        {literal}
            <script type="text/javascript" src="lib/live_support/live-support.js">
            </script>
        {/literal}
        {$headerlib->output_headers()}
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body onUnload="client_close();">


        <div class="w-100 vh-100 d-flex justify-content-center mt-5">

            <div class="container w-100">
                <div class="row justify-content-md-center">
                    <div class="col-12 col-lg-5 col-md-6">

                        <div id='request_chat' class="card">
                            <div class="card-body">
                                <input type="hidden" id="reqId">
                                <input type="hidden" id="tiki_user" value="{$user|escape}">

                                <h2 class="card-title p-3">{tr}Request live support{/tr}</h2>

                                {if $user}
                                    <div class="row tiki-form-group">
                                        <label for="username" class="col-sm-4 col-form-label"><strong>{tr}User{/tr}</strong></label>
                                        <div class="col-sm-8">
                                            <input type="text" readonly class="form-control-plaintext" id="username" value="{$user|escape}">
                                        </div>
                                    </div>
                                    <div class="row tiki-form-group">
                                        <label for="emailaddress" class="col-sm-4 col-form-label"><strong>{tr}Email{/tr}</strong></label>
                                        <div class="col-sm-8">
                                            <input type="text" readonly class="form-control-plaintext" id="emailaddress" value="{$user_email|escape}">
                                        </div>
                                    </div>
                                {else}
                                    <div class="row tiki-form-group">
                                        <label for="username" class="col-sm-4 col-form-label"><strong>{tr}User{/tr}</strong></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="username" placeholder="">
                                        </div>
                                    </div>
                                    <div class="row tiki-form-group">
                                        <label for="emailaddress" class="col-sm-4 col-form-label"><strong>{tr}Email{/tr}</strong></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="emailaddress" placeholder="">
                                        </div>
                                    </div>
                                {/if}

                                <div class="row tiki-form-group">
                                    <label for="reason" class="col-sm-4 col-form-label"><strong>{tr}Reason{/tr}</strong></label>
                                    <div class="col-sm-8">
                                        <textarea class="form-control" id="reason"></textarea>
                                    </div>
                                </div>

                                <div class="row tiki-form-group">
                                    <span class="col-sm-4 col-form-label"></span>
                                    <div class="col-sm-8">
                                        <input class="btn btn-primary" onClick="request_chat(document.getElementById('username').value,document.getElementById('tiki_user').value,document.getElementById('emailaddress').value,document.getElementById('reason').value);" type="button" value="{tr}Request support{/tr}">
                                        <a class="btn btn-outline-danger" href="tiki-index.php">{tr}Close{/tr}</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card" id='requesting_chat' style='display: none'>
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center">
                                    <b>{tr}Your request is being processed{/tr}....</b>
                                    <br>
                                    <a class="btn btn-outline-danger" href="javascript:client_close();window.close();" class="link">{tr}cancel request and exit{/tr}</a><br>
                                    {*<a href="tiki-live_support_message.php" class="link">{tr}cancel request and leave a message{/tr}</a><br>*}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </body>
</html>
