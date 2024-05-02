<form action="tiki-admin.php?page=look" id="look" name="look" class="labelColumns admin" method="post">
    {ticket}
    <div class="t_navbar mb-4 clearfix">
        {if $prefs.feature_theme_control eq y}
            {button _text="{tr}Theme Control{/tr}" href="tiki-theme_control.php" _class="btn-sm btn-link tikihelp" _icon_name="file-image-o"}
        {/if}
        {if $prefs.feature_editcss eq 'y' and $tiki_p_create_css eq 'y'}
            {button _text="{tr}Edit CSS{/tr}" _class="btn-sm" href="tiki-edit_css.php"}
        {/if}
        {include file='admin/include_apply_top.tpl'}
    </div>
    {tabset name="admin_look"}
        {tab name="{tr}Theme{/tr}"}
            <br>
            <legend class="h3">{tr}Main theme{/tr}</legend>
            <div class="row">
                <div class="col-md-8 adminoptionbox">
                    {preference name=theme}
                    <div class="adminoptionbox theme_childcontainer custom_url">
                        {preference name=theme_custom_url}
                    </div>
                    {preference name=theme_option}
                    {preference name=theme_option_includes_main}
                    {preference name=theme_navbar_color_variant}
                    {preference name=change_theme}
                    <div class="adminoptionboxchild" id="change_theme_childcontainer">
                        {preference name=available_themes}
                    </div>
                    {preference name=useGroupTheme}
                </div>
                <div class="col-md-4">
                    <div class="card me-lg-4">
                        <div class="card-body text-center">
                        {if $thumbfile}
                            <img src="{$thumbfile}" class="img-fluid" alt="{tr}Theme Screenshot{/tr}" id="theme_thumb">
                        {else}
                            <span>{icon name="image"}</span>
                        {/if}
                        </div>
                    </div>
                </div>
            </div>

            {* Fixed width is the first decision to make, so it needs to be visibly on the first tab. But its logical place is in the "layout" tab.
                    So we we put it twice. But its not possible to "just" put it twice, hence following hack.
                    If this hack is required in more places, we can add an opton to "preference" plugin
                    -- This checkbox commented out 10/2022 for the reason that it makes more sense for items to be where they logically belong.
                    Admins will need to go to the other tabs anyway.
            *}
          {*  <div class="adminoptionbox preference clearfix basic feature_fixed_width all" style="">
                <div class="adminoption mb-3 row">
                    <label class="col-sm-4">
                        Fixed width
                    </label>
                    <div class="col-sm-8">
                        <div class="form-check">
                            <input id="dummy_pref-25" class="form-check-input" type="checkbox" name="dummy_feature_fixed_width" {if $prefs.feature_fixed_width eq 'y'} checked="checked"{/if} >
                            <a class="tikihelp text-info" title="Fixed width:Restrict the width of the site content area, in contrast to a liquid (full-width) layout." >
                                <span class="icon icon-information fas fa-info-circle "></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            {jq}
                $('input[name=dummy_feature_fixed_width]').on("click", function(){
                    /* get value of dummy_feature_fixed_width */
                    var dummy = $(this).is(":checked");
                    /* Synchronize with dummy checkbox and trigger display of child option */
                    $('input[name=feature_fixed_width]').prop('checked', dummy).trigger("change");
                });

                $('input[name=feature_fixed_width]').on("click", function(){
                    /* get value of feature_fixed_width */
                    var real = $(this).is(":checked");
                    /* Synchronize with dummy checkbox display */
                    $('input[name=dummy_feature_fixed_width]').prop('checked', real);
                });
            {/jq} *}

            <hr>

            <legend class="h3">{tr}Admin theme{/tr}</legend>
            <div class="adminoptionbox">
                {preference name=theme_unified_admin_backend}
                {preference name=theme_admin}
                {preference name=theme_option_admin}
                {preference name=theme_navbar_color_variant_admin}
                {preference name=sitelogo_icon}
            </div>
            <hr>

            <legend class="h3">{tr}Other{/tr}</legend>
            {preference name=theme_iconset}
            {if $prefs.javascript_enabled eq 'n' or $prefs.feature_jquery eq 'n'}
                {* TODO I don't see where this is used in in admin/include_look.php *}
                <input type="submit" class="btn btn-primary btn-sm" name="changestyle" value="{tr}Go{/tr}">
            {/if}
            <div class="adminoptionbox">
                {if $prefs.feature_jquery_ui eq 'y'}
                    {preference name=feature_jquery_ui_theme}
                {/if}
            </div>
            {preference name=feature_theme_control}
            <div class="adminoptionboxchild" id="feature_theme_control_childcontainer">
                {preference name=feature_theme_control_savesession}
                {preference name=feature_theme_control_parentcategory}
                {preference name=feature_theme_control_autocategorize}
            </div>
            <hr>
            <legend class="h3">{tr}Theme Preferences{/tr}</legend>
            <div class="adminoptionboxchild">
                {foreach key=theme item=preferences from=$themePrefs}
                    <fieldset>
                        <legend class="h3">{tr}{{$theme|escape|ucfirst}}{/tr}</legend>
                        {foreach $preferences as $pref}
                            {preference name="{$pref|escape}"}
                        {/foreach}
                    </fieldset>
                {foreachelse}
                    {tr}No theme preferences found.{/tr}
                {/foreach}
            </div>
            <hr>
        {/tab}
        {tab name="{tr}Layout{/tr}"}
            <br>
            <legend class="h3">{tr}General layout{/tr}</legend>
            {preference name=feature_fixed_width}
            <div class="adminoptionboxchild" id="feature_fixed_width_childcontainer">
                {preference name=layout_fixed_width}
            </div>
            {preference name=site_layout}
            {preference name=site_layout_per_object}
            {preference name=theme_navbar_fixed_topbar_offset}
            {preference name=theme_header_and_address_bar_color}

            <legend class="h3">{tr}Admin pages layout{/tr} (<small>{tr}Admin theme must be selected first{/tr}</small>)</legend>
            {preference name=site_layout_admin}

            {* <legend class="h3">{tr}Fixed vs full width layout{/tr}</legend> *}
            <hr>

            <legend class="h3">{tr}Logo and Title{/tr}</legend>
            {preference name=feature_sitelogo}
            <div class="adminoptionboxchild" id="feature_sitelogo_childcontainer">
                <fieldset>
                    <legend class="h3">{tr}Logo{/tr}</legend>
                    {preference name=sitelogo_src}
                    {preference name=sitelogo_title}
                    {preference name=sitelogo_alt}
                    {preference name=sitelogo_upload_icon}
                </fieldset>
                <fieldset>
                    <legend class="h3">{tr}Title{/tr}</legend>
                    {preference name=sitetitle}
                    {preference name=sitesubtitle}
                </fieldset>
            </div>
            <hr>

            <div class="adminoptionbox">
                <fieldset>
                    <legend class="h3">{tr}Module zone visibility{/tr}</legend>
                    {preference name=module_zones_top}
                    {preference name=module_zones_topbar}
                    {preference name=module_zones_pagetop}
                    {preference name=feature_left_column}
                    {preference name=feature_right_column}
                    {preference name=module_zones_pagebottom}
                    {preference name=module_zones_bottom}
                    <br>
                    {preference name=module_file}
                    {preference name=module_zone_available_extra}
                </fieldset>
            </div>
            <hr>

            <div class="adminoptionbox">
                <fieldset>
                    <legend class="h3">{tr}Site report bar{/tr}</legend>
                    {preference name=feature_site_report}
                    {preference name=feature_site_report_email}
                </fieldset>
            </div>
            <hr>
        {/tab}
        {if $prefs.site_layout eq 'classic'}
            {tab name="{tr}Shadow layer{/tr}"}
                <br>
                <legend class="h3">{tr}Shadow layer{/tr}</legend>
                {preference name=feature_layoutshadows}
                <div class="adminoptionboxchild" id="feature_layoutshadows_childcontainer">
                    {preference name=main_shadow_start}
                    {preference name=main_shadow_end}
                    {preference name=header_shadow_start}
                    {preference name=header_shadow_end}
                    {preference name=middle_shadow_start}
                    {preference name=middle_shadow_end}
                    {preference name=center_shadow_start}
                    {preference name=center_shadow_end}
                    {preference name=footer_shadow_start}
                    {preference name=footer_shadow_end}
                    {preference name=box_shadow_start}
                    {preference name=box_shadow_end}
                </div>
                <hr>
            {/tab}
        {/if}
        {tab name="{tr}Pagination{/tr}"}
            <br>
            <legend class="h3">{tr}Pagination{/tr}</legend>
            {preference name=nextprev_pagination}
            {preference name=direct_pagination}
            <div class="adminoptionboxchild" id="direct_pagination_childcontainer">
                {preference name=direct_pagination_max_middle_links}
                {preference name=direct_pagination_max_ending_links}
            </div>
            {preference name=pagination_firstlast}
            {preference name=pagination_fastmove_links}
            {preference name=pagination_hide_if_one_page}
            {preference name=pagination_icons}

            <legend class="h3">{tr}Limits{/tr}</legend>
            {preference name=user_selector_threshold}
            {preference name=maxRecords}
            {preference name=tiki_object_selector_threshold}
            {preference name=tiki_object_selector_searchfield}
            {preference name=comments_per_page}
            <hr>
        {/tab}
        {tab name="{tr}UI Effects{/tr}"}
            <br>
            <div class="adminoptionbox">
                <fieldset class="mb-3 w-100">
                    <legend class="h3">{tr}Standard UI effects{/tr}</legend>
                    {preference name=jquery_effect}
                    {preference name=jquery_effect_speed}
                    {preference name=jquery_effect_direction}
                </fieldset>
            </div>
            <div class="adminoptionbox">
                <fieldset class="mb-3 w-100">
                    <legend class="h3">{tr}Tab UI effects{/tr}</legend>
                    {preference name=jquery_effect_tabs}
                    {preference name=jquery_effect_tabs_speed}
                    {preference name=jquery_effect_tabs_direction}
                </fieldset>
            </div>
            <hr>

            <fieldset>
                <legend class="h3">{tr}Other{/tr}</legend>
                <div class="admin featurelist">
                    {preference name=feature_shadowbox}
                    {preference name=allowImageLazyLoad}
                    <div class="adminoptionboxchild" id="feature_shadowbox_childcontainer">
                        {preference name=jquery_colorbox_theme}
                    </div>
                    {preference name=feature_jscalendar}
                    {preference name=wiki_heading_links}
                    {preference name=feature_equal_height_rows_js}
                    {preference name=feature_conditional_formatting}
                    {preference name=jquery_ui_modals_draggable}
                    {preference name=jquery_ui_modals_resizable}
                </div>
            </fieldset>
            <hr>
        {/tab}
        {tab name="{tr}Customization{/tr}"}
            {if !$color_mode_error && $prefs.switch_color_module_assigned eq 'y'}
                {*-- off canvas --*}
                {*  TODO add a custom picker with rgba mode and add variable that must be defined in RGB  
                    Missing variables
                    --bs-body-color-rgb: 173, 181, 189;
                    --bs-body-bg-rgb: 33, 37, 41;
                    --bs-emphasis-color-rgb: 255, 255, 255;
                    --bs-secondary-color: rgba(173, 181, 189, 0.75);
                    --bs-secondary-color-rgb: 173, 181, 189;
                    --bs-secondary-bg-rgb: 52, 58, 64;
                    --bs-tertiary-color: rgba(173, 181, 189, 0.5);
                    --bs-tertiary-color-rgb: 173, 181, 189;
                    --bs-tertiary-bg-rgb: 43, 48, 53;
                    --bs-link-color-rgb: 110, 168, 254;
                    --bs-link-hover-color-rgb: 139, 185, 254;
                    --bs-border-color-translucent: rgba(255, 255, 255, 0.15);
                *}
                <style>
                    #cm-action-off-canvas{
                        position:fixed;
                        top:0;
                        right:-100vw;
                        width : 40vw;
                        height: 100vh;
                        overflow-y: auto;
                        padding: 20px;
                        z-index: 100000;
                        color : var(--bs-body-color);
                        background-color: var(--bs-body-bg);
                    }
                    .close-cm-canvas{
                        position: absolute;
                        right : 5px;
                        top: 5px;
                        height: 30px;
                        width: 30px;
                        cursor: pointer;
                        font-weight: bolder;
                        font-size: 20px;
                    }
                    @media screen and (max-width:1024px){
                        #cm-action-off-canvas{
                            width : 50vw;
                        }
                    }
                    @media screen and (max-width:767px){
                        #cm-action-off-canvas{
                            width : 70vw;
                        }
                    }
                    @media screen and (max-width:400px){
                        #cm-action-off-canvas{
                            width : 100vw;
                        }
                    }
                </style>
                <div id="cm-action-off-canvas" class="border border-light">
                    <div class="close-cm-canvas"><span>x</span></div>
                    <div class="my-2 p-1 p-md-5" id="cm-modal-content">
                        <input type="hidden" name="operation" value="create"/>
                        <input type="hidden" name="id" value=""/>
                        <div class="mb-3">
                            <label class="form-label" for="color-mode">Color mode name</label>
                            <input
                                type="text"
                                value=""
                                name="mode"
                                id="color-mode"
                                class="form-control"
                                placeholder="dark blue"
                                />
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="mode-icon">Mode icon name</label>
                            <input
                                type="text"
                                value=""
                                name="icon"
                                id="mode-icon"
                                class="form-control"
                                placeholder="icon name"
                                />
                        </div>
                        <div class="p-3 d-flex gap-2 flex-column rounded border border-light mb-2">
                            <span class="fw-bolder">Body</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-body-color" onclick="toggle_css_variable(this,'--bs-body-color')">--bs-body-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-body-bg" onclick="toggle_css_variable(this,'--bs-body-bg')">--bs-body-bg</span>
                            </div>

                            <span class="fw-bolder">Emphasis</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-emphasis-color" onclick="toggle_css_variable(this,'--bs-emphasis-color')">--bs-emphasis-color</span>
                            </div>

                            <span class="fw-bolder">Secondary</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-secondary-bg" onclick="toggle_css_variable(this,'--bs-secondary-bg')">--bs-secondary-bg</span>
                            </div>

                            <span class="fw-bolder">Tertiary</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-tertiary-bg" onclick="toggle_css_variable(this,'--bs-tertiary-bg')">--bs-tertiary-bg</span>
                            </div>

                            <span class="fw-bolder">Text Emphasis</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-primary-text-emphasis" onclick="toggle_css_variable(this,'--bs-primary-text-emphasis')">--bs-primary-text-emphasis</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-secondary-text-emphasis" onclick="toggle_css_variable(this,'--bs-secondary-text-emphasis')">--bs-secondary-text-emphasis</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-success-text-emphasis" onclick="toggle_css_variable(this,'--bs-success-text-emphasis')">--bs-success-text-emphasis</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-info-text-emphasis" onclick="toggle_css_variable(this,'--bs-info-text-emphasis')">--bs-info-text-emphasis</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-warning-text-emphasis" onclick="toggle_css_variable(this,'--bs-warning-text-emphasis')">--bs-warning-text-emphasis</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-danger-text-emphasis" onclick="toggle_css_variable(this,'--bs-danger-text-emphasis')">--bs-danger-text-emphasis</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-light-text-emphasis" onclick="toggle_css_variable(this,'--bs-light-text-emphasis')">--bs-light-text-emphasis</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-dark-text-emphasis" onclick="toggle_css_variable(this,'--bs-dark-text-emphasis')">--bs-dark-text-emphasis</span>
                            </div>

                            <span class="fw-bolder">Background Subtle</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-primary-bg-subtle" onclick="toggle_css_variable(this,'--bs-primary-bg-subtle')">--bs-primary-bg-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-secondary-bg-subtle" onclick="toggle_css_variable(this,'--bs-secondary-bg-subtle')">--bs-secondary-bg-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-success-bg-subtle" onclick="toggle_css_variable(this,'--bs-success-bg-subtle')">--bs-success-bg-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-info-bg-subtle" onclick="toggle_css_variable(this,'--bs-info-bg-subtle')">--bs-info-bg-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-warning-bg-subtle" onclick="toggle_css_variable(this,'--bs-warning-bg-subtle')">--bs-warning-bg-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-danger-bg-subtle" onclick="toggle_css_variable(this,'--bs-danger-bg-subtle')">--bs-danger-bg-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-light-bg-subtle" onclick="toggle_css_variable(this,'--bs-light-bg-subtle')">--bs-light-bg-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-dark-bg-subtle" onclick="toggle_css_variable(this,'--bs-dark-bg-subtle')">--bs-dark-bg-subtle</span>
                            </div>

                            <span class="fw-bolder">Border Subtle</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-primary-border-subtle" onclick="toggle_css_variable(this,'--bs-primary-border-subtle')">--bs-primary-border-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-secondary-border-subtle" onclick="toggle_css_variable(this,'--bs-secondary-border-subtle')">--bs-secondary-border-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-success-border-subtle" onclick="toggle_css_variable(this,'--bs-success-border-subtle')">--bs-success-border-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-info-border-subtle" onclick="toggle_css_variable(this,'--bs-info-border-subtle')">--bs-info-border-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-warning-border-subtle" onclick="toggle_css_variable(this,'--bs-warning-border-subtle')">--bs-warning-border-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-danger-border-subtle" onclick="toggle_css_variable(this,'--bs-danger-border-subtle')">--bs-danger-border-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-light-border-subtle" onclick="toggle_css_variable(this,'--bs-light-border-subtle')">--bs-light-border-subtle</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-dark-border-subtle" onclick="toggle_css_variable(this,'--bs-dark-border-subtle')">--bs-dark-border-subtle</span>
                            </div>

                            <span class="fw-bolder">Navbars</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-top-bg" onclick="toggle_css_variable(this,'--tiki-top-bg')">--tiki-top-bg</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-top-color" onclick="toggle_css_variable(this,'--tiki-top-color')">--tiki-top-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-top-hover-color" onclick="toggle_css_variable(this,'--tiki-top-hover-color')">--tiki-top-hover-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-top-hover-bg" onclick="toggle_css_variable(this,'--tiki-top-hover-bg')">--tiki-top-hover-bg</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-top-border" onclick="toggle_css_variable(this,'--tiki-top-border')">--tiki-top-border</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-topbar-bg" onclick="toggle_css_variable(this,'--tiki-topbar-bg')">--tiki-topbar-bg</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-topbar-color" onclick="toggle_css_variable(this,'--tiki-topbar-color')">--tiki-topbar-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-topbar-hover-color" onclick="toggle_css_variable(this,'--tiki-topbar-hover-color')">--tiki-topbar-hover-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-topbar-hover-bg" onclick="toggle_css_variable(this,'--tiki-topbar-hover-bg')">--tiki-topbar-hover-bg</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-topbar-border" onclick="toggle_css_variable(this,'--tiki-topbar-border')">--tiki-topbar-border</span>
                            </div>

                            <span class="fw-bolder">Site Title</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-site-title-color" onclick="toggle_css_variable(this,'--tiki-site-title-color')">--tiki-site-title-color</span>
                            </div>

                            <span class="fw-bolder">Unified Admin Backend</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-top-nav-bg" onclick="toggle_css_variable(this,'--tiki-admin-top-nav-bg')">--tiki-admin-top-nav-bg</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-top-nav-color" onclick="toggle_css_variable(this,'--tiki-admin-top-nav-color')">--tiki-admin-top-nav-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-top-nav-hover-color" onclick="toggle_css_variable(this,'--tiki-admin-top-nav-hover-color')">--tiki-admin-top-nav-hover-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-top-nav-hover-bg" onclick="toggle_css_variable(this,'--tiki-admin-top-nav-hover-bg')">--tiki-admin-top-nav-hover-bg</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-aside-nav-bg" onclick="toggle_css_variable(this,'--tiki-admin-aside-nav-bg')">--tiki-admin-aside-nav-bg</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-aside-nav-color" onclick="toggle_css_variable(this,'--tiki-admin-aside-nav-color')">--tiki-admin-aside-nav-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-aside-nav-hover-color" onclick="toggle_css_variable(this,'--tiki-admin-aside-nav-hover-color')">--tiki-admin-aside-nav-hover-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-dropdown-bg" onclick="toggle_css_variable(this,'--tiki-admin-dropdown-bg')">--tiki-admin-dropdown-bg</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-dropdown-link-color" onclick="toggle_css_variable(this,'--tiki-admin-dropdown-link-color')">--tiki-admin-dropdown-link-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-dropdown-link-hover-color" onclick="toggle_css_variable(this,'--tiki-admin-dropdown-link-hover-color')">--tiki-admin-dropdown-link-hover-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--tiki-admin-dropdown-link-hover-bg" onclick="toggle_css_variable(this,'--tiki-admin-dropdown-link-hover-bg')">--tiki-admin-dropdown-link-hover-bg</span>
                            </div>

                            <span class="fw-bolder">Other</span>
                            <div class="d-flex flex-wrap gap-2">
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-heading-color" onclick="toggle_css_variable(this,'--bs-heading-color')">--bs-heading-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-link-color" onclick="toggle_css_variable(this,'--bs-link-color')">--bs-link-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-navbar-color" onclick="toggle_css_variable(this,'--bs-navbar-color')">--bs-navbar-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-navbar-hover-color" onclick="toggle_css_variable(this,'--bs-navbar-hover-color')">--bs-navbar-hover-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-link-hover-color" onclick="toggle_css_variable(this,'--bs-link-hover-color')">--bs-link-hover-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-code-color" onclick="toggle_css_variable(this,'--bs-code-color')">--bs-code-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-border-color" onclick="toggle_css_variable(this,'--bs-border-color')">--bs-border-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-form-valid-color" onclick="toggle_css_variable(this,'--bs-form-valid-color')">--bs-form-valid-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-form-valid-border-color" onclick="toggle_css_variable(this,'--bs-form-valid-border-color')">--bs-form-valid-border-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-form-invalid-color" onclick="toggle_css_variable(this,'--bs-form-invalid-color')">--bs-form-invalid-color</span>
                                <span style="cursor:pointer;" class="badge rounded-pill text-bg-primary" data-badge-for="--bs-form-invalid-border-color" onclick="toggle_css_variable(this,'--bs-form-invalid-border-color')">--bs-form-invalid-border-color</span>
                            </div>
                        </div>
                        <div class="css_colors"></div>
                        <button class="btn btn-primary mt-5g px-5 mt-4" onclick="return handle_mode_create_edit(this)">{tr}Save{/tr}</button>
                    </div>
                </div>
                {* end off-canvas *}
                <fieldset>
                    <legend class="h3">{tr}Color mode settings{/tr}</legend>
                    {*
                    <p>
                        {tr}Default modes{/tr}
                        <br>
                        <small class="fw-lighter">{tr}you can only change their icons{/tr}</small>
                    </p>
                    {foreach $default_modes item=mode}
                        <div class="input-group mb-3 w-100">
                            <button class="btn btn-outline-secondary" style="cursor:inherit" type="button">Mode</button>
                            <span style="cursor:not-allowed" class="input-group-text">{$mode.name}</span>
                            <button class="btn btn-outline-secondary" style="cursor:inherit" style="cursor:text;" type="button">Icon</button>
                            <input data-icon-name-for='{$mode.name}' onkeydown="return cancel_submit(event)" type="text" onchange="sync_color_mode_state(this.value,'{$mode.name}')" value="{$mode.icon}" class="form-control" placeholder="icon" style="max-width : 180px;">
                            <button class="btn btn-outline-secondary" style="cursor:inherit" type="button">{icon name=$mode.icon}</button>
                        </div>
                    {/foreach}
                    <button class="btn btn-primary" id='cm-save-default' onclick="return save_default_color_mode_icons(this)">Save</button>
                    *}
                    <p>
                        {tr}Custom color modes{/tr}
                    </p>
                    <div>
                        {foreach $custom_modes item=mode}
                            <div class="input-group mb-3 w-100" data-mode-name='{$mode.name}' data-mode-icon='{$mode.icon}'>
                                <code class="d-none">{$mode.css_variables}</code>
                                <button class="btn btn-primary rounded" style="cursor:default" type="button">{icon name=$mode.icon} {$mode.name} <span style="cursor: pointer;" onclick="edit_custom_mode(this,'{$mode.id}','{$mode.name}','{$mode.icon}')">{icon name='edit'}</span> <span style="cursor: pointer;" class="text-danger" onclick="delete_custom_mode(this,'{$mode.id}','{$mode.name}')">{icon name='trash'}</span></button>
                            </div>
                        {/foreach}
                    </div>
                    <button onclick="return  add_custom_mode(this)" class="btn btn-info mb-3">{tr}Set up a new color mode{/tr}</button>
                </fieldset>
            {/if}
            <hr>
            <fieldset>
                <legend class="h3">{tr}Custom code{/tr}</legend>
                {preference name="header_custom_css" syntax="css"}
                {preference name=feature_custom_html_head_content syntax="htmlmixed"}
                {preference name=feature_endbody_code syntax="tiki"}
                {preference name=site_google_analytics_account}
                {preference name="header_custom_js" syntax="javascript"}
                {preference name="layout_add_body_group_class"}
                {preference name=categories_add_class_to_body_tag}
            </fieldset>
            <hr>
            <fieldset>
                <legend class="h3">{tr}Editing{/tr}</legend>
                {preference name=theme_customizer}
                {preference name=feature_editcss}
                {preference name=feature_view_tpl}
                {if $prefs.feature_view_tpl eq 'y'}
                    <div class="adminoptionboxchild">
                        {button href="tiki-edit_templates.php" _text="{tr}View Templates{/tr}"}
                    </div>
                {/if}
                {preference name=feature_edit_templates}
                {if $prefs.feature_edit_templates eq 'y'}
                    <div class="adminoptionboxchild">
                        {button href="tiki-edit_templates.php" _text="{tr}Edit Templates{/tr}"}
                    </div>
                {/if}
                {preference name="theme_iconeditable"}
            </fieldset>
            <hr>
        {/tab}
        {tab name="{tr}Miscellaneous{/tr}"}
            <br>
            <fieldset class="adminoptionbox">
                <legend class="h3">{tr}Tabs{/tr}</legend>
                {preference name=feature_tabs}
                <div class="adminoptionboxchild" id="feature_tabs_childcontainer">
                    {preference name=layout_tabs_optional}
                </div>
            </fieldset>
            <hr>

            <fieldset class="adminoptionbox">
                <legend class="h3">{tr}Favicons{/tr}</legend>
                {preference name=site_favicon_enable}
            </fieldset>
            <hr>

            <fieldset class="adminoptionbox">
                <legend class="h3">{tr}Responsive images{/tr}</legend>
                {preference name=image_responsive_class}
            </fieldset>
            <hr>

            <div class="adminoptionbox">
                <fieldset class="mb-3 w-100">
                    <legend class="h3">{tr}Context menus{/tr} (<small>{tr}currently used in file galleries only{/tr}</small>)</legend>
                    {preference name=use_context_menu_icon}
                    {preference name=use_context_menu_text}
                </fieldset>
            </div>
            <hr>

            <fieldset>
                <legend class="h3">{tr}Separators{/tr}</legend>
                {preference name=site_crumb_seper}
                {preference name=site_nav_seper}
            </fieldset>
            <hr>

            <legend class="h3">{tr}Smarty templates (TPL files){/tr}</legend>
            {preference name=log_tpl}
            {preference name=smarty_compilation}
            {preference name=smarty_cache_perms}
            {preference name=categories_used_in_tpl}
            {preference name=feature_html_head_base_tag}
            <hr>
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
