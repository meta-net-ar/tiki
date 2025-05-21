{title url="tiki-iot_dashboard.php" help="Iot"}{tr}Tiki IoT{/tr}{/title}
{remarksbox type="tip" title="{tr}Tip{/tr}"}
{tr}Go fullscreen to enhance your experience while designing{/tr}
{/remarksbox}

<div id="iot_dashboard" class="h-100">
    <div class="row">
        <div class="col-md-3 h-100 px-4 py-3 app-tab">
            <h2 class="mb-5">{icon name="dashboard"}&nbsp;{tr}Apps{/tr}</h2>
            <div class="list-of-iot-apps">
                {foreach from=$iot_apps item=app}
                    <button type="button" data-app-raw-info='{$app.app_raw_info|json_encode|escape}'
                        drawflow-editor-action="initFlowIfFirstTime" data-editor-id="{$app.editor_id}" data-app-name="{$app.app_name}"
                        data-button-open-app="{$app.app_uuid}"
                        class="btn btn-primary {if $app.app_active eq 'y'}active-app{else}inactive-app{/if} iot-app-name text-center mt-2 my-3 d-block w-100">
                        <span class="app-name pe-1">{$app.app_name} </span><span
                            class="edit_app action">{icon name="pencil"}</span> <span
                            class="text-danger action delete_app">{icon name="trash"}</span>
                    </button>
                {/foreach}
                <button type="button" class="btn btn-link mt-2 my-3 d-block w-100 create_app"> {icon name="create"}&nbsp;{tr}New app{/tr}</button>
            </div>
        </div>
        <div class="col-md-9 h-100 px-4 py-3 designer-tab">
            <div class="non-app-selected-warning">
                <div class="alert alert-primary d-flex align-items-center" role="alert">
                    <div>
                         {icon name="alert"}&nbsp;{tr}Please select an app to start designing{/tr}
                    </div>
                </div>
            </div>

            {foreach from=$iot_apps item=app}
                <div class="app-entry d-none" id="{$app.app_uuid}">

                    <div class="form-check form-switch">
                        <label class="form-check-label text-secondary"
                            for="switch{$app.app_uuid}">{if $app.app_active eq 'y'}{tr}Active{/tr}{else}{tr}Inactive{/tr}{/if}</label>
                        <input class="form-check-input" data-app-name="{$app.app_name}" data-app-control="{$app.app_uuid}"
                            type="checkbox" role="switch" id="switch{$app.app_uuid}"
                            {if $app.app_active eq 'y'}checked{/if}>
                    </div>
                    
                    {if $app.app_active eq 'y'}
                        <a href="tiki-iot_dashboard.php?mode=view&app_id={$app.app_uuid}" class="btn btn-link p-0" target="_blank">{tr}View the dashboard{/tr} {icon name="external_link"}</a>
                    {/if}

                    <div class="mb-5 p-3 my-2 border rounded">
                        <h2 class="fs-4 py-1">{tr}Control your physical Devices Authentication{/tr}</h2>
                        <div>
                            <div class="mb-3">
                                <label for="app-name" class="form-label">{tr}App ID{/tr}</label>
                                <input class="form-control" type="text" value="{$app.app_uuid}" readonly="" disabled="">
                            </div>
                        </div>
                        <div>
                            {if $app.iot_bridge_access_token != null }
                                <div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" style="min-width: 150px;" value="{$app.iot_bridge_access_token}" id="existing-access-token-{$app.app_uuid}" readonly>
                                        {if $app.iot_bridge_access_token_expire_at eq null }
                                            <button type="button" class="btn btn-success">{tr}No expiration{/tr}</button>
                                        {else}
                                            <button type="button" class="btn btn-primary">{tr}Expires on{/tr} {$app.iot_bridge_access_token_expire_at}</button>
                                        {/if}
                                    </div>
                                    <button class="btn btn-danger mt-2 mb-1"  drawflow-editor-action="revoqueAccessToken" data-app-uuid="{$app.app_uuid}" data-app-name="{$app.app_name}">{tr}Revoke{/tr}</button>
                                </div>
                            {else}
                                <div class="mb-1">
                                    {remarksbox type="warning" title="{tr}Attention{/tr}"}
                                        {tr}This app doesn't currently have any access token{/tr}
                                    {/remarksbox}
                                    <button class="btn btn-primary py-1" drawflow-editor-action="showAccessTokenForm" data-selector="#iot-accesstoken-form-{$app.app_uuid}">{tr}Generate a new one{/tr}</button>
                                </div>
                            {/if}
                        </div>
                        <form id="iot-accesstoken-form-{$app.app_uuid}" class="d-none mb-2 mt-3 needs-validation" novalidate>
                            <input type="hidden" name="app_uuid" value="{$app.app_uuid}">
                            <input type="hidden" name="app_name" value="{$app.app_name}">
                            <div class="mb-3">
                                <label for="access-token" style="min-width: 150px;" class="form-label">{tr}Access Token{/tr}</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="access_token" minlength="24" required>
                                    <button type="button" class="btn btn-primary rounded-end" drawflow-editor-action="generateToken">{tr}Generate Token{/tr}</button>
                                    <div class="invalid-feedback">{tr}Please generate a token or provide a valid one{/tr}</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="token-expire-at" class="form-label">{tr}Token Expiration Date{/tr}</label>
                                <input type="datetime-local" class="form-control" name="token_expire_at">
                                <div class="form-text text-warning">{tr}Leave blank for no expiration,{/tr}&nbsp;<span class="fw-bold">{tr}not recommended!{/tr}</span></div>
                            </div>
                            <button type="submit" class="btn btn-primary">{tr}Save{/tr}</button>
                        </form>
                    </div>

                    <ul class="nav nav-tabs" id="tabs-{$app.app_uuid}" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="editor-tab-{$app.app_uuid}" data-bs-toggle="tab"
                                data-bs-target="#editor-tab-pane-{$app.app_uuid}" type="button" role="tab"
                                aria-controls="editor-tab-pane" aria-selected="true">{tr}Flow design{/tr}</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dashboard-tab-{$app.app_uuid}" data-bs-toggle="tab"
                                data-bs-target="#dashboard-tab-pane-{$app.app_uuid}" type="button" role="tab"
                                aria-controls="dashboard-tab-pane" aria-selected="false">{tr}Dashboard UI design{/tr}</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="tab-content-{$app.app_uuid}">
                        <div class="tab-pane fade show active" id="editor-tab-pane-{$app.app_uuid}" role="tabpanel"
                            aria-labelledby="profile-tab" tabindex="0">
                            <div class="scenario-canvas" id="scenario{$app.app_uuid}">
                                {$app.app_editor}
                            </div>
                        </div>
                        <div class="tab-pane fade p-4" id="dashboard-tab-pane-{$app.app_uuid}" role="tabpanel"
                            aria-labelledby="home-tab" tabindex="0">
                            <h2 class="py-2 px-2">{tr}Dashboard UI Builder{/tr}</h2>
                            <div class="container my-4">
                                <div class="row">
                                    <div class="col-md-12" new-widget-config>
                                        <div class="mb-3">
                                            <div class="d-flex gap-5 flex-wrap">
                                                <div>
                                                    <select class="form-select mb-3" name="widget-select">
                                                        <option selected>{tr}Please select the widget{/tr}</option>
                                                        <option value="sensor-input">{tr}Sensor Input{/tr}</option>
                                                        <option value="led">{tr}Led (IO state){/tr}</option>
                                                        <option value="switch-button">{tr}Switch button{/tr}</option>
                                                        <option value="gauge">{tr}Gauge{/tr}</option>
                                                        <option value="gauge-half">{tr}Half gauge{/tr}</option>
                                                        <option value="map">{tr}Map{/tr}</option>
                                                        <option value="chart">{tr}Chart{/tr}</option>
                                                        <option value="flow-console">{tr}App flow console{/tr}</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <select class="form-select mb-3" name="data-source-select">
                                                        <option selected>{tr}Please select the data source{/tr}</option>
                                                        {foreach from=$app.tracker_fields.data item=field}
                                                            <option value="{$field.permName}">{$field.name}</option>
                                                        {/foreach}
                                                        <option value="app_flow_logs">{tr}App flow logs{/tr}</option>
                                                        {foreach from=$app.hardware_io item=io_state key=io_pin}
                                                            <option value="hardware-io-{$io_pin}">{tr}Hardware I/O{/tr} ID {$io_pin}</option>
                                                        {/foreach}
                                                    </select>
                                                </div>
                                                <div>
                                                    <select class="form-select mb-3" name="widget-icon-select">
                                                        <option selected>{tr}Please specify the widget icon{/tr}</option>
                                                        {foreach from=$iconset item=data}
                                                            <option value='{icon name=$data.id}'>{$data.id}</option>
                                                        {/foreach}
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex flex-wrap gap-3">
                                                <div>
                                                    <input type="color" class="form-control form-control-color" name="bgcolor"
                                                        name="w-bg-color" value="#ffffff" />
                                                    <label for="bgcolor" class="form-label">{tr}Widget background color{/tr}</label>
                                                </div>
                                                <div>
                                                    <input type="color" class="form-control form-control-color"
                                                        name="textcolor" name="w-text-color" value="#000000" />
                                                    <label for="textcolor" class="form-label">{tr}Widget Text color{/tr}</label>
                                                </div>
                                                <div>
                                                    <input type="color" class="form-control form-control-color"
                                                        name="bordercolor" name="w-border-color" value="#efefef" />
                                                    <label for="bordercolor" class="form-label">{tr}Widget border color{/tr}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="dataUnit" class="form-label">{tr}Data Unit{/tr}</label>
                                            <input type="text" class="form-control" name="dataUnit">
                                        </div>
                                        <div class="mb-3">
                                            <label for="widgetLabel" class="form-label">{tr}Widget label{/tr}</label>
                                            <input type="text" class="form-control" name="widgetLabel">
                                        </div>
                                        <button data-grid-stack-id="dasboard-{$app.app_uuid}" class="btn btn-primary" add-widget-button>{tr}Add widget{/tr}</button>
                                    </div>
                                </div>
                            </div>
                            {*<textarea data-grid-items-for="dasboard-{$app.app_uuid}">{$app.dashboard_config}</textarea>*}
                            {*<div style="display: none;" data-grid-items-for="dasboard-{$app.app_uuid}">{$app.dashboard_config}</div>*}
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3 mt-5">
                                            <div>
                                                <div class="pb-2">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" checked drawflow-editor-form-action="toggleFloat" data-grid-stack-id="dasboard-{$app.app_uuid}"  type="checkbox" role="switch" id="float-grid-{$app.app_uuid}">
                                                        <label class="form-check-label" for="float-grid-{$app.app_uuid}">{tr}Float Grid{/tr}</label>
                                                    </div>
                                                    <p class="form-text text-secondary">{icon name="info-circle"} {tr}A float grid allows you to place widgets anywhere in the design area{/tr}</p>
                                                </div>
                                                <div class="pb-2">
                                                    <button drawflow-editor-action="compact" data-grid-stack-id="dasboard-{$app.app_uuid}" class="btn btn-primary">{tr}Compact Layout{/tr}</button>
                                                    <p class="form-text text-secondary">{icon name="info-circle"} {tr}Compact layout to remove all possible gaps between widgets{/tr}</p>
                                                </div>
                                            </div>
                                            {remarksbox type="info" title="{tr}Tip{/tr}"}
                                                {tr}Double click on a widget to remove it from the UI{/tr}
                                            {/remarksbox}
                                        </div>
                                        <div class="grid-stack" data-grid-stack id="dasboard-{$app.app_uuid}">{if !empty($app.dashboard_config)}{$app.dashboard_config}{/if}</div>
                                    </div>
                                    <div class="col-md-12">
                                    <button data-grid-stack-id="dasboard-{$app.app_uuid}" class="btn btn-primary px-5 mt-5" drawflow-editor-action="saveDashboardUi" data-app-name="{$app.app_name}" data-app-uuid="{$app.app_uuid}">{tr}Save{/tr}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
    <button class="btn btn-primary position-absolute" id="fullscreen-button">{icon name="fullscreen"}&nbsp;{tr}Go Fullscreen{/tr}</button>
</div>
