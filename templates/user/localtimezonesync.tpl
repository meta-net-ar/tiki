<div id="timezone-sync-box" style="display: none;">
    {remarksbox close="" title=""}
        <div class="d-flex justify-content-between">
            <form method="post" id="timezone-form" action="{service controller=user action=localtimezonesync}">
                {ticket}
                <p class="mb-3">
                    {tr _0='<strong>"<span class="detected-tz-name"></span>"</strong>' _1='<strong>"<span class="current-tz-name"></span>"</strong>'}Your browser claims to be using the %0 timezone, but your user account on this website is set to use the %1 timezone. Would you like to set it to match your browser settings?{/tr}
                </p>
                <input type="hidden" name="client_timezone" id="client-timezone" value=""/>
                <input type="hidden" name="prefered_timezone" id="prefered-timezone" value=""/>
                <div class="d-grid gap-2">
                    <button
                        title="{tr}Details: {/tr}{tr}This will permanently change your profile timezone.{/tr}"
                        class="btn btn-primary btn-sm tips tz-switch-button"
                        name="timezone_action" value="switch" type="submit">
                        {icon name="sync-alt"} {tr _0='<span class="detected-tz-name"></span>'}Switch my default to %0{/tr}
                    </button>
                    <button
                        title="{tr}Details: {/tr}{tr}Use your browser timezone for this session without changing your user account timezone.{/tr}"
                        class="btn btn-secondary btn-sm tips tz-temporary-button"
                        name="timezone_action" value="temporary" type="submit">
                        {icon name="clock"} {tr _0='<span class="detected-tz-name"></span>'}Use my browser timezone ("%0") for this session{/tr}
                    </button>

                    <button
                    title="{tr}Details: {/tr}{tr}Your timezone setting will not be changed and this notification will be disabled.{/tr}"
                        class="btn btn-outline-danger btn-sm tips"
                        name="timezone_action" value="never" type="submit">
                        {icon name="times-circle"} {tr}Keep my current setting and stop asking{/tr}
                    </button>
                </div>
            </form>
        </div>
    {/remarksbox}
</div>
{jq}
    $(document).ready(function () {
        const clientTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        $.post('tiki-ajax_services.php', {
            controller: 'user',
            action: 'LocalTimezoneSync',
            client_timezone: clientTimeZone
        }, function (response) {
            if (response && response.different === true) {
                $('#timezone-sync-box').show();
                $('#prefered-timezone').val(response.preferedTimezone);
                $('#client-timezone').val(response.clientTimezone);
                $('.detected-tz-name').text(response.clientTimezone);
                $('.current-tz-name').text(response.preferedTimezone);
            }
        }, 'json');
    });
{/jq}
