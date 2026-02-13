<h5 class="card-title mb-3 d-flex justify-content-between align-items-center">
    {tr}Meeting ID:{/tr} {$bbb_meeting|escape}
    {permission type=bigbluebutton object=$bbb_meeting name=tiki_p_assign_perm_bigbluebutton}
        {permission_link mode=button type=bigbluebutton id=$bbb_meeting title=$bbb_meeting}
    {/permission}
</h5>

<p id="bbbRoomNotExistMessage_{$meetId|escape}">{tr}Last time we checked, the room you requested did not exist.{/tr}</p>

{permission name=bigbluebutton_create type=bigbluebutton object=$bbb_meeting}
<form 
id="bbbJoinForm_{$meetId|escape}" method="post" action="{service controller=bigbluebutton action=create}" class="form"
{if $prefs.bigbluebutton_use_iframe eq 'y'} data-iframe="1" {else} target="_blank" {/if}
>
<h3 class="mb-3">{tr}Start Meeting{/tr}</h3>
    <input type="hidden" name="params" value="{$bbb_params|escape}">

    <div class="form-group d-flex flex-row mb-3 align-items-center">
        <label class="form-label col-sm-3 mb-0">{tr}Meeting Type:{/tr}</label>
        <div class="btn-group" role="group" aria-label="Meeting type">
            <button type="button" class="btn meeting-type-btn active" id="btnPublic">
                {tr}Public (no passwords){/tr}
            </button>
            <button type="button" class="btn meeting-type-btn" id="btnPrivate">
                {tr}Private (requires passwords){/tr}
            </button>
        </div>
        <input type="hidden" name="isPublic" id="isPublic" value="1">
    </div>

    <div id="privateFields"></div>

    <button type="submit" id="bbbJoinButton_{$meetId|escape}" class="btn btn-primary" >
        <i class="fas fa-video me-1"></i> {tr}Start{/tr}
    </button>
    <style>
        .meeting-type-btn {
            border: 1px solid #ccc;
            color: #666;
            background-color: #fff;
            transition: all 0.2s ease-in-out;
        }

        .meeting-type-btn:hover {
            border-color: var(--bs-primary);
            color: var(--bs-primary);
        }
        .btn-group .btn.active {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #fff;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const bbb_form = document.getElementById("bbbJoinForm_{$meetId|escape}");
            const input = bbb_form.querySelector("#isPublic");
            const btnPublic = bbb_form.querySelector("#btnPublic");
            const btnPrivate = bbb_form.querySelector("#btnPrivate");
            const privateFields = bbb_form.querySelector("#privateFields");

            btnPublic.addEventListener("click", function() {
                input.value = "1";
                btnPublic.classList.add("active");
                btnPrivate.classList.remove("active");
                privateFields.innerHTML = '';
            });

            btnPrivate.addEventListener("click", function() {
                input.value = "0";
                btnPrivate.classList.add("active");
                btnPublic.classList.remove("active");
                privateFields.innerHTML = `
                <div class="form-group  d-flex flex-row mb-3">
                    <label class="col-sm-3 col-form-label">{tr}Admin Password:{/tr}</label>
                    <input type="password" class="form-control" name="adminPW" value="">
                </div>
                <div class="form-group d-flex flex-row mb-3">
                    <label class="col-sm-3 col-form-label">{tr}Attendee Password:{/tr}</label>
                    <input type="password" class="form-control" name="attendeePW" value="">
                </div>`
            });
        });
    </script>
</form>

<div id="bbbMeetingWrapper_{$meetId|escape}" class="mt-3">
    {* iframe will be injected here *}
</div>

{include file="wiki-plugins/wikiplugin_bigbluebutton_view_recordings.tpl"}
{/permission}
