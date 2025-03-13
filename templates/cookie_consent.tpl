{strip}
    {if $prefs.cookie_consent_mode eq 'dialog'}
        <div class="modal" tabindex="-1" role="dialog" id="{$prefs.cookie_consent_dom_id}">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{tr}Cookie Consent{/tr}</h5>
                    </div>
                    <div class="modal-body">
            {else}
        <div id="{$prefs.cookie_consent_dom_id}" class="alert alert-primary col-md-6 mx-auto my-4" role="alert"
            {if not empty($prefs.cookie_consent_mode)}
                style="display:none;" class="{$prefs.cookie_consent_mode}"
            {/if}
        >
    {/if}
        <form method="POST">
            <div class="description mb-3">
                {wiki}{tr}{$prefs.cookie_consent_description}{/tr}{/wiki}
            </div>
            <div class="row mx-0">
                {if !empty($prefs.cookie_consent_question)}
                    <div class="col-sm-9">
                        <div class="form-check">
                            {if ($prefs.cookie_consent_analytics eq 'y' and $smarty.cookies[$prefs.cookie_consent_name|cat:'_analytics'] eq 'y') or
                                    ($prefs.cookie_consent_analytics eq 'n' and $smarty.cookies[$prefs.cookie_consent_name] eq 'y')}
                                {$consent_checked = true}
                            {/if}
                            <input class="form-check-input" type="checkbox" name="cookie_consent_checkbox" id="cookie_consent_checkbox"{if $consent_checked} checked="checked"{/if}>
                            <label class="form-check-label question" for="cookie_consent_checkbox">
                                {wiki}{tr}{$prefs.cookie_consent_question}{/tr}{/wiki}
                            </label>
                        </div>
                    </div>
                {else}
                    <input type="hidden" name="cookie_consent_checkbox" value="1">
                {/if}
                <div class="col-sm-3">
                    <input type="submit" class="btn btn-success" id="cookie_consent_button" name="cookie_consent_button" value="{tr}{$prefs.cookie_consent_button}{/tr}">
                </div>
            </div>
        </form>
    {if $prefs.cookie_consent_mode eq 'dialog'}
        </div></div></div>
    {/if}
    </div>
    {jq}
        function setConsentCookies() {
            let exp = new Date();
            exp.setTime(exp.getTime()+(24*60*60*1000*{{$prefs.cookie_consent_expires}}));
            jqueryTiki.no_cookie = false;
            setCookieBrowser("{{$prefs.cookie_consent_name}}", exp.getTime(), "", exp);    // set to cookie value to the expiry time
            if (jqueryTiki.cookie_consent_analytics) {
                if ($("#cookie_consent_checkbox").prop("checked")) {
                    setCookieBrowser("{{$prefs.cookie_consent_name}}_analytics", exp.getTime(), "", exp);    // set to cookie value to the expiry time
                } else {
                    deleteCookie("{{$prefs.cookie_consent_name}}_analytics");   // reset cookie
                }
            }
            $(document).trigger("cookies.consent.agree");
        }
        $("#cookie_consent_button").on("click", function(){
            if ($("input[name=cookie_consent_checkbox]:checked").length || $("input[name=cookie_consent_checkbox]:hidden").val() || jqueryTiki.cookie_consent_analytics) {
                setConsentCookies();
                {{if $prefs.cookie_consent_mode eq 'dialog'}}
                    $("#{{$prefs.cookie_consent_dom_id}}").modal("hide");
                {{else}}
                    $("#{{$prefs.cookie_consent_dom_id}}").fadeOut("fast");
                {{/if}}
                if (location.search.match(/[\?&]cookie_consent/)) {
                    location.href = location.href.replace(/[\?&]cookie_consent/, "");
                }
            } else {
                $("input[name=cookie_consent_checkbox]").parent().animate({
                    backgroundColor: "#ff8"
                }, 250, function () {
                    $("input[name=cookie_consent_checkbox]").parent().animate({
                        backgroundColor: ""
                    }, 1000);
                });
            }
            return false;
        });
        {{if $prefs.cookie_consent_disable eq 'y'}}
            setConsentCookies();
        {{/if}}
    {/jq}
    {if $prefs.cookie_consent_mode eq 'banner'}
        {jq}
            setTimeout(function () {$("#{{$prefs.cookie_consent_dom_id}}").slideDown("slow");}, 500);
        {/jq}
    {elseif $prefs.cookie_consent_mode eq 'dialog'}
        {jq}
            setTimeout(function () {$("#{{$prefs.cookie_consent_dom_id}}").modal({{if $prefs.cookie_consent_disable neq 'y'}}{backdrop: "static",keyboard:false,}{{/if}});}, 500);
        {/jq}
    {/if}
{/strip}
