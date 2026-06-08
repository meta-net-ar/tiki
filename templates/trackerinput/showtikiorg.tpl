<div id="testingstatus" style="display:none">{$field.status|escape}</div>
{$myId = $field.fieldId|escape|cat:'_'|cat:$item.itemId|escape}
<h5 id="showtikiorg{$myId}{if isset($context.list_mode)}_view{/if}" class="showactive{$myId}" {if $field.status neq 'ACTIV'}style="display: none;"{/if}>
    {tr _0=$field.options_map.domain|escape}This bug has been demonstrated on %0{/tr}
</h5>
<h5 class="shownone{$myId}" {if $field.status neq 'NONE'}style="display: none;"{/if}>
    {tr _0=$field.options_map.domain|escape}Please demonstrate your bug on %0{/tr}
</h5>
{if !$field.id}
    {remarksbox type="info" title="{tr}Bug needs to be created first{/tr}" close="n"}
        <p>{tr _0=$field.options_map.domain|escape}You will be able to demonstrate your bug on a %0 instance once it has been created.{/tr}</p>
    {/remarksbox}
{else}
    <div class="showsnapshot{$myId}" style="display: none;">
        {remarksbox type="error" title="{tr}Show.tiki.org snapshot creation is in progress{/tr}" close="n"}
            <p>{tr _0="<a class=\"snapshoturl{$myId}\" href=\"http://{$field.snapshoturl|escape}\" target=\"_blank\">http://{$field.snapshoturl|escape}</a>"}Show.tiki.org snapshot creation is in progress... Please monitor %0 for progress.{/tr}
                <strong>{tr}Note that if you get a popup asking for a username/password, please just enter "show" and "show".{/tr}</strong>
            </p>
        {/remarksbox}
    </div>
    <div class="showresetok{$myId}" style="display: none;">
        {remarksbox type="info" title="{tr}Password reset{/tr}" close="n"}
            <p>{tr}Password reset was successful{/tr}</p>
        {/remarksbox}
    </div>
    <div class="showresetnok{$myId}" style="display: none;">
        {remarksbox type="error" title="{tr}Password reset{/tr}" close="n"}
            <p>{tr}Password reset failed{/tr}</p>
        {/remarksbox}
    </div>
    <div class="showdestroy{$myId}" style="display: none;">
        {remarksbox type="error" title="{tr}Show.tiki.org instance destruction in progress{/tr}" close="n"}
            <p>{tr}Show.tiki.org instance destruction is in progress... Please wait...{/tr}</p>
        {/remarksbox}
    </div>
    <div class="showinvalidkeys{$myId}" {if $field.status neq 'INVKEYS'}style="display: none;"{/if}>
        {remarksbox type="error" title="{tr}Show.tiki.org is not configured properly{/tr}" close="n"}
            <p>{tr _0=$field.options_map.domain|escape}The public/private keys configured to connect to %0 were not accepted. Please make sure you are using RSA keys. Thanks.{/tr}</p>
        {/remarksbox}
    </div>
    <div class="showdisconnected{$myId}" {if $field.status neq 'DISCO'}style="display: none;"{/if}>
        {remarksbox type="error" title="{tr}Show.tiki.org is currently unavailable{/tr}" close="n"}
            <p>{tr _0=$field.options_map.domain|escape}Unable to connect to %0. Please let us know of the problem so that we can do something about it. Thanks.{/tr}</p>
        {/remarksbox}
    </div>
    <div class="showmaint{$myId}" {if $field.status neq 'MAINT'}style="display: none;"{/if}>
        {remarksbox type="error" title="{tr}Show.tiki.org is under maintenance{/tr}" close="n"}
            <p>{tr}Show.tiki.org is currently under maintenance. Sorry for the inconvenience.{/tr} {$field.maintreason|escape}</p>
        {/remarksbox}
    </div>
    <div class="showfail{$myId}" {if $field.status neq 'FAIL'}style="display: none;"{/if}>
        {remarksbox type="error" title="{tr _0=$field.options_map.domain|escape}Unable to get information from %0{/tr}" close="n"}
            <p>{tr _0=$field.options_map.domain|escape}Unable to get information from %0. Please let us know of the problem so that we can do something about it. Thanks.{/tr}</p>
        {/remarksbox}
    </div>
    <div class="showbuilding{$myId}" {if $field.status neq 'BUILD'}style="display: none;"{/if}>
        {remarksbox type="error" title="{tr}Instance is being created{/tr}" close="n"}
            <p>{tr}Show.tiki.org is in the progress of creating the new instance. Please continue waiting for a minute or two. If this continues on for more than 10 minutes, please let us know of the problem so that we can do something about it. Thanks.{/tr}</p>
        {/remarksbox}
    </div>
    <div class="shownone{$myId}" {if $field.status neq 'NONE' or not $user}style="display: none;"{/if}>
        {remarksbox type="info" title="{tr _0=$field.options_map.domain|escape}About %0{/tr}" close="n"}
            <p>{tr _0=$field.options_map.domain|escape}To help developers solve the bug, we kindly request that you demonstrate your bug on a %0 instance. To start, simply select a version and click on "Create %0 instance". Once the instance is ready (in a minute or two), as indicated in the status window below, you can then access that instance, login (the initial admin username/password is "admin") and configure the Tiki to demonstrate your bug. Priority will be given to bugs that have been demonstrated on %0.{/tr}</p>
        {/remarksbox}
        {tr}Version:{/tr}
        <select name="gitbranch" class="form-control">
            {foreach $field.versions as $version}
                <option{if $field.version eq $version} selected="selected"{/if}>{$version|escape}</option>
            {/foreach}
        </select>
        {button href="#showtikiorg{$myId}{if isset($context.list_mode)}_view{/if}" _onclick="showtikiorg_process{$myId}('create');" _text="{tr _0=$field.options_map.domain|escape}Create %0 instance{/tr}"}
    </div>
    <div class="showactive{$myId}" {if $field.status neq 'ACTIV'}style="display: none;"{/if}>
        {remarksbox type="info" title="{tr}Accessing the Tiki instance that demonstrates this bug{/tr}" close="n"}
            <p>{tr _0="<a class=\"showurl{$myId}\" href=\"http://{$field.showurl|escape}\" target=\"_blank\">http://{$field.showurl|escape}</a>" _1=$field.options_map.domain|escape}The URL for the %1 instance that demonstrates this bug is at: %0.{/tr}
                <strong>{tr}Note that if you get a popup asking for a username/password, please just enter "show" and "show". This is different from the initial login and password for a new Tiki which is "admin" and "admin".{/tr}</strong>
            </p>
            <p>{tr _0="<a class=\"showlogurl{$myId}\" href=\"http://{$field.showlogurl|escape}\" target=\"_blank\">http://{$field.showlogurl|escape}</a>"}For the install log, see %0{/tr}</p>
            <p>
                <strong>{tr}Note that if you see PHP errors or a Tiki claiming to be missing third party software, the instance creation is probably not finished. Please wait a couple minutes and reload.{/tr}</strong>
            </p>
        {/remarksbox}
        {remarksbox type="info" title="{tr}Snapshots{/tr}" close="n"}
            <p>{tr _0=$field.options_map.domain|escape}Snapshots are database dumps of the configuration that developers can download for debugging. Once you have reproduced your bug on the %0 instance, create a snapshot that can then be downloaded by developers for further investigation.{/tr}</p>
            <p>{tr _0="<a class=\"snapshoturl{$myId}\" href=\"http://{$field.snapshoturl|escape}\" target=\"_blank\">http://{$field.snapshoturl|escape}</a>"}Snapshots can be accessed at: %0.{/tr}
                <strong>{tr}Note that if you get a popup asking for a username/password, please just enter "show" and "show".{/tr}</strong>
            </p>
        {button href="#showtikiorg{$myId}{if isset($context.list_mode)}_view{/if}" _onclick="showtikiorg_process{$myId}('snapshot');" _text="{tr}Create new snapshot{/tr}"}
        {/remarksbox}
        <div class="hstack btn-group btn-group-sm" role="group" aria-label="Instance actions">
            {if !empty($field.canDestroy)}
                {button href="#showtikiorg{$myId}{if isset($context.list_mode)}_view{/if}" _icon_name="trash" _onclick="showtikiorg_process{$myId}('destroy');" _type="outline-danger" _text="{tr}Destroy the instance{/tr}"}
                {button href="#showtikiorg{$myId}{if isset($context.list_mode)}_view{/if}" _icon_name="rotate_right" _onclick="showtikiorg_process{$myId}('reset');" _type="outline-primary" _text="{tr}Reset password to 12345{/tr}"}
            {/if}
            <button class="buttonupdate{$myId} btn btn-outline-primary"{if not in_array($field.version, $field.versions)} style="display: none;"{/if} onclick="showtikiorg_process{$myId}('update');">
                {icon name="cloud-download"}
                {tr}GIT update{/tr}
            </button>
            <button href="#showtikiorg{$myId}{if isset($context.list_mode)}_view{/if}" id="clone{$myId}" onclick="showtikiorg_process{$myId}('clone');" class="btn btn-outline-primary" {if $field.cloneExist} style="display: none;"{/if}>
                {icon name="copy"}
                {tr}Clone the instance{/tr}
            </button>
        </div>
        <div id="clone-actions{$myId}" style="{if not $field.cloneExist}display: none;{/if}">
            <hr>
            <div class="hstack btn-group btn-group-sm mt-1" role="group" aria-label="Instance actions">
                {if !empty($field.canDestroy)}
                    {button href="#showtikiorg{$myId}{if isset($context.list_mode)}_view{/if}" _icon_name="trash" _onclick="showtikiorg_process{$myId}('destroyclone');" _type="outline-danger" _text="{tr}Destroy the clone{/tr}"}
                {/if}
                {button href="#showtikiorg{$myId}{if isset($context.list_mode)}_view{/if}" _icon_name="cloud-download" _onclick="showtikiorg_process{$myId}('updateclone');" _type="outline-primary" _text="{tr}Update the clone{/tr}"}
                {button href="http://{$field.cloneUrl}" _id="clone{$myId}-link" _target="_blank" _icon_name="arrow_up_right_from_square" _type="outline-primary" _text="{tr}Access the clone{/tr}"}
            </div>
        </div>
    </div>
    {if !empty($field.options_map.debugMode)}
        {remarksbox type="info" title="{tr}Debug Mode Information{/tr}" close="n"}
            <div class="showdebugoutput{$myId}">-{$field.status|escape}
                - {$field.debugoutput|escape}</div>
        {button href="#showtikiorg{$myId}{if isset($context.list_mode)}_view{/if}" _onclick="showtikiorg_process{$myId}('info');" _text="{tr}Get instance information and refresh cache{/tr}"}
        {/remarksbox}
    {/if}
    {jq notonready=true}
    function showtikiorg_process{{$field.fieldId|escape}}_{{$item.itemId|escape}}(action) {
        var request = {
            id: {{$field.id}},
            userid: {{$field.userid}},
            username: '{{$field.username}}',
            fieldId: {{$field.fieldId}},
            command: action,
            gitbranch: $("select[name='gitbranch']").val()

        };
        $.tikiModal(tr('Please wait...'));
        $.ajax({
            url: $.service('showtikiorg', 'process'),
            data: request,
            dataType: 'json',
            type: 'POST',
            success: function(data) {
                var debugoutput = data.debugoutput;
                //$('#testingstatus').html(data.status);
                $('.showdebugoutput{{$myId}}').html(data.debugoutput);
                if ($.inArray(data.version, {{$field.versions|json_encode}}) > -1) {
                    $('.buttonupdate{{$myId}}').show();
                }
                if (data.status == 'DISCO') {
                    $('.showdisconnected{{$myId}}').show();
                    $('.showinvalidkeys{{$myId}}').hide();
                    $('.showmaint{{$myId}}').hide();
                    $('.showfail{{$myId}}').hide();
                    $('.showsnapshot{{$myId}}').hide();
                    $('.showdestroy{{$myId}}').hide();
                    $.tikiModal();
                } else if (data.status == 'INVKEYS') {
                    $('.showinvalidkeys{{$myId}}').show();
                    $('.showdisconnected{{$myId}}').hide();
                    $('.showmaint{{$myId}}').hide();
                    $('.showfail{{$myId}}').hide();
                    $('.showsnapshot{{$myId}}').hide();
                    $('.showdestroy{{$myId}}').hide();
                    $.tikiModal();
                } else if (data.status == 'MAINT') {
                    $('.showmaint{{$myId}}').show();
                    $('.showdisconnected{{$myId}}').hide();
                    $('.showinvalidkeys{{$myId}}').hide();
                    $('.showfail{{$myId}}').hide();
                    $('.showsnapshot{{$myId}}').hide();
                    $('.showdestroy{{$myId}}').hide();
                    $.tikiModal();
                } else if (data.status == 'FAIL') {
                    $('.showfail{{$myId}}').show();
                    $('.showmaint{{$myId}}').hide();
                    $('.showdisconnected{{$myId}}').hide();
                    $('.showinvalidkeys{{$myId}}').hide();
                    $('.showsnapshot{{$myId}}').hide();
                    $('.showdestroy{{$myId}}').hide();
                    $.tikiModal();
                } else if (data.status == 'BUILD') {
                    $('.showbuilding{{$myId}}').show();
                    $('.shownone{{$myId}}').hide();
                    $('.showactive{{$myId}}').hide();
                    $('.showfail{{$myId}}').hide();
                    $('.showdisconnected{{$myId}}').hide();
                    $('.showinvalidkeys{{$myId}}').hide();
                    $('.showmaint{{$myId}}').hide();
                    $('.showsnapshot{{$myId}}').hide();
                    $('.showdestroy{{$myId}}').hide();
                    setTimeout("showtikiorg_process{{$myId}}('info')",5000);
                    $.tikiModal(tr('The instance is being built. This might take a minute or two, please wait...'));
                } else if (data.status == 'NONE') {
                    $('.shownone{{$myId}}').show();
                    $('.showactive{{$myId}}').hide();
                    $('.showbuilding{{$myId}}').hide();
                    $('.showfail{{$myId}}').hide();
                    $('.showdisconnected{{$myId}}').hide();
                    $('.showinvalidkeys{{$myId}}').hide();
                    $('.showmaint{{$myId}}').hide();
                    $('.showsnapshot{{$myId}}').hide();
                    $('.showdestroy{{$myId}}').hide();
                    $.tikiModal();
                } else if (data.status == 'ACTIV') {
                    $('.showactive{{$myId}}').show();
                    $('.showbuilding{{$myId}}').hide();
                    $('.shownone{{$myId}}').hide();
                    $('.showfail{{$myId}}').hide();
                    $('.showdisconnected{{$myId}}').hide();
                    $('.showinvalidkeys{{$myId}}').hide();
                    $('.showmaint{{$myId}}').hide();
                    $('.showsnapshot{{$myId}}').hide();
                    $('.showdestroy{{$myId}}').hide();
                    $('.showurl{{$myId}}').attr("href", "http://" + data.showurl).html("http://" + data.showurl);
                    $('.showlogurl{{$myId}}').attr("href", "http://" + data.showlogurl).html("http://" + data.showlogurl);
                    $('.snapshoturl{{$myId}}').attr("href", "http://" + data.snapshoturl).html("http://" + data.snapshoturl);
                    $('#clone{{$myId}}-link').attr('href', 'http://' + data.cloneUrl);
                    $.tikiModal();
                } else if (data.status == 'SNAPS') {
                    $('.showactive{{$myId}}').show();
                    $('.showbuilding{{$myId}}').hide();
                    $('.shownone{{$myId}}').hide();
                    $('.showfail{{$myId}}').hide();
                    $('.showdisconnected{{$myId}}').hide();
                    $('.showinvalidkeys{{$myId}}').hide();
                    $('.showmaint{{$myId}}').hide();
                    $('.showsnapshot{{$myId}}').show();
                    $('.showdestroy{{$myId}}').hide();
                    $('.showresetok{{$myId}}').hide();
                    $('.showresetnok{{$myId}}').hide();
                } else if (data.status == 'RESOK') {
                    $('.showresetok{{$myId}}').show();
                    $('.showresetnok{{$myId}}').hide();
                } else if (data.status == 'RENOK') {
                    $('.showresetnok{{$myId}}').show();
                    $('.showresetok{{$myId}}').hide();
                } else if (data.status == 'DESTR') {
                    $('.showactive{{$myId}}').hide();
                    $('.showbuilding{{$myId}}').hide();
                    $('.shownone{{$myId}}').hide();
                    $('.showfail{{$myId}}').hide();
                    $('.showdisconnected{{$myId}}').hide();
                    $('.showinvalidkeys{{$myId}}').hide();
                    $('.showmaint{{$myId}}').hide();
                    $('.showsnapshot{{$myId}}').hide();
                    $('.showresetnok{{$myId}}').hide();
                    $('.showresetok{{$myId}}').hide();
                    $('.showdestroy{{$myId}}').show();
                    setTimeout("showtikiorg_process{{$myId}}('info')",5000);
                    $.tikiModal(tr('Instance is being destroyed... Please wait...'));
                } else if (data.status == "CLONE") {
                    showtikiorg_process{{$myId}}('info');
                    $('#clone-actions{{$myId}}').show();
                    $('#clone{{$myId}}').hide();
                } else if (data.status == 'DESTROY_CLONE') {
                    $('#clone-actions{{$myId}}').hide();
                    $('#clone{{$myId}}').show();
                    showtikiorg_process{{$myId}}('info');
                } else if (data.status == 'UPDATE_CLONE') {
                    $.tikiModal();
                }
            }
        });
        return false;
    }
    {/jq}
{/if}
