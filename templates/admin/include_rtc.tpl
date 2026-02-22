<form action="tiki-admin.php?page=rtc" method="post" class="admin">
    {ticket}
    <div class="t_navbar mb-4 clearfix">
        {button href="tiki-admingroups.php" _class="btn-link tips" _type="text" _icon_name="group" _text="{tr}Groups{/tr}" _title=":{tr}Group Administration{/tr}"}
        {button href="tiki-adminusers.php" _class="btn-link tips" _type="text" _icon_name="user" _text="{tr}Users{/tr}" _title=":{tr}User Administration{/tr}"}
        {permission_link addclass="btn btn-link" _type="text" mode=text label="{tr}Permissions{/tr}"}
        <a href="{service controller=managestream action=list}" class="btn btn-link tips">{tr}Activity Rules{/tr}</a>
        {include file='admin/include_apply_top.tpl'}
    </div>
    {tabset name="admin_rtc"}
        {tab name="{tr}BigBlueButton{/tr}"}
            <br>
            {preference name=bigbluebutton_feature}
            <div class="adminoptionboxchild" id="bigbluebutton_feature_childcontainer">
                {preference name=bigbluebutton_server_location}
                {preference name=bigbluebutton_shared_secret}
                {preference name=bigbluebutton_use_iframe}
                {preference name=bigbluebutton_recording_max_duration}
                {preference name=bigbluebutton_dynamic_configuration}
                {preference name=wikiplugin_bigbluebutton}
            </div>
        {/tab}
        {tab name="XMPP"}
            <h2>XMPP</h2>
            {preference name=xmpp_feature}
            <div class="adminoptionboxchild" id="xmpp_feature_childcontainer">

                <fieldset>
                    <legend class="h3">{tr}Openfire or Prosody (Common){/tr}</legend>
                    {preference name=xmpp_server_host}
                    {preference name=xmpp_client_port}
                    {preference name=xmpp_auth_method}
                    {preference name=xmpp_muc_component_domain}
                    {preference name=xmpp_server_http_bind}
                </fieldset>

                <hr>

                <fieldset>
                    <legend class="h3">{tr}Openfire{/tr}</legend>
                    {preference name=xmpp_openfire_rest_api}
                    {preference name=xmpp_openfire_rest_api_username}
                    {preference name=xmpp_openfire_rest_api_password}
                    {preference name=xmpp_openfire_allow_anonymous}
                </fieldset>

                <hr>

                <fieldset>
                    <legend class="h3">{tr}Prosody (HTTP Auth){/tr}</legend>
                    {preference name=xmpp_ws_url}
                    {preference name=xmpp_domain_users}
                    {preference name=xmpp_domain_guest}
                    {preference name=xmpp_anonymous_mode}
                    {preference name=xmpp_anonymous_room}
                    {preference name=xmpp_anonymous_support_room}
                    {preference name=xmpp_registered_room}
                    {preference name=xmpp_group_room_map}
                    {preference name=xmpp_auto_join_strategy}
                    {preference name=xmpp_shared_secret}
                    {preference name=xmpp_cors_allowed_origins}

                    <legend class="h3">{tr}XMPP Admin{/tr}</legend>
                    {preference name=xmpp_admin_jid}
                    {preference name=xmpp_admin_password}
                </fieldset>

                <hr>

                <fieldset>
                    <legend class="h3">{tr}ConverseJS options (common){/tr}</legend>
                    {preference name=xmpp_conversejs_always_load}
                    {preference name=xmpp_conversejs_debug}
                    {preference name=xmpp_conversejs_init_json}
                </fieldset>
            </div>
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
