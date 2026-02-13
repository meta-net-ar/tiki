<form 
    id="bbbJoinForm_{$meetId|escape}" method="post" action="{service controller=bigbluebutton action=join}"
    class="card shadow-sm p-3 mb-4"
    {if $prefs.bigbluebutton_use_iframe eq 'y'} data-iframe="1" {else} target="_blank" {/if}
>
    <input type="hidden" name="params" value="{$bbb_params|escape}">

    {* Meeting Header *}
    <div class="mb-3">
        <h5 class="card-title mb-3 d-flex justify-content-between align-items-center">
            <i class="fas fa-video text-primary me-2"></i>
            {tr}Meeting ID:{/tr} <span class="fw-bold">{$bbb_meeting|escape}</span>
            <span class="ms-auto" style="font-size:11px !important;">
                {permission type=bigbluebutton object=$bbb_meeting name=tiki_p_assign_perm_bigbluebutton}
                    {permission_link mode=button type=bigbluebutton id=$bbb_meeting title=$bbb_meeting bgColor='grey' fontSize='0.8em'}
                {/permission}
            </span>
        </h5>
    </div>

    <input type="hidden" name="isPublic" id="isPublic" value="{$bbb_meeting_is_public === "true" ? '1' : '0'}">

    {* Join Section *}
    <div class="mb-3">
        {if ! $user}
            <div class="form-group  d-flex flex-row mb-3">
                <label class="col-sm-3 col-form-label">{tr}Your Name:{/tr}</label>
                <input type="text" class="form-control mb-2" name="bbb_name" required placeholder="{tr}Enter your name{/tr}">
            </div>
        {/if}

        {if $bbb_meeting_is_public === "false"}
            <div class="form-group  d-flex flex-row mb-3">
                <label for="meetingPass" class="col-sm-3 col-form-label">{tr}Meeting Passcode:{/tr}</label>
                <input type="password" placeholder="{tr}Enter Meeting Passcode{/tr}" class="form-control" name="meetingPass" value="">
            </div>
        {/if}
        <button type="submit" class="btn btn-primary w-40" id="bbbJoinButton_{$meetId|escape}">
            <i class="fas fa-sign-in-alt me-1"></i> {tr}Join Meeting{/tr}
        </button>
    </div>

    {* Attendees *}
    {if $bbb_show_attendees}
        <div class="mt-3">
            <h6 class="fw-bold">{tr}Current Attendees{/tr}</h6>
            {if $bbb_attendees}
                <ul class="list-group list-group-flush">
                    {foreach from=$bbb_attendees item=att}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{$att.fullName|escape}</span>
                            <span class="badge bg-secondary text-uppercase">{$att.role|escape}</span>
                        </li>
                    {/foreach}
                </ul>
            {else}
                <p class="text-muted fst-italic">{tr}No attendees at this time.{/tr}</p>
            {/if}
        </div>
    {/if}

    {* Recordings *}
    <div class="mt-4">
        {include file="wiki-plugins/wikiplugin_bigbluebutton_view_recordings.tpl"}
    </div>

</form>

<div id="bbbMeetingWrapper_{$meetId|escape}" class="mt-3">
    {* iframe will be injected here *}
</div>
