{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}Please see the <a class='alert-link' target='tikihelp' href='http://doc.tiki.org/Features'>evaluation of each feature</a> on Tiki's developer site.{/tr}
{/remarksbox}

<form class="admin" id="features" name="features" action="tiki-admin.php?page=features" method="post">
    {ticket}
    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>


    {tabset name="admin_features"}
        {*
        * The following section is typically for features that act like Tiki
        * sections and add a configuration icon to the sections list
        *}
        {* ---------- Main features ------------ *}
        {tab name="{tr}Global features{/tr}" key=global}
            <br>

            <fieldset>
                <legend class="h3"><h4 class="showhide_heading" id="Main_features">  {tr}Main features{/tr}<a href="#Main_features" class="heading-link" aria-label="{tr}Main features{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4> </legend>
                <div class="admin clearfix mb-3 featurelist">
                    {preference name=feature_wiki}
                    {preference name=feature_file_galleries}
                    {preference name=feature_blogs}
                    {preference name=feature_articles}
                    {preference name=feature_forums}
                    {preference name=feature_trackers}
                    {preference name=feature_calendar}
                    {preference name=feature_search}
                </div>
            </fieldset>

            <fieldset>
                <legend class="h3"><h4 class="showhide_heading" id="Secondary_features">{tr}Secondary features{/tr} <a href="#Secondary_features" class="heading-link" aria-label="{tr}Secondary features{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                <div class="admin clearfix mb-3 featurelist">
                    {preference name=feature_categories}
                    {preference name=feature_freetags}
                    {preference name=feature_polls}
                    {preference name=feature_quizzes}
                    {preference name=feature_surveys}
                    {preference name=feature_newsletters}
                    {preference name=feature_shoutbox}
                    {preference name=feature_minichat}
                    {preference name=feature_live_support}
                    {preference name=feature_machine_learning}
                    {preference name=feature_tiki_manager}
                    <div class="adminoptionboxchild" id="feature_tiki_manager_childcontainer">
                        {preference name=tikimanager_storage_path}
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="h3"><h4 class="showhide_heading" id="Administrative_features">{tr}Administrative features{/tr} <a href="#Administrative_features" class="heading-link" aria-label="{tr}Administrative features{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                <div class="admin clearfix mb-3 featurelist">
                    {preference name=feature_stats}
                    {preference name=feature_actionlog}
                    {preference name=feature_scheduler}
                    {preference name=feature_banners}
                    {preference name=feature_contribution}
                    {preference name=feature_copyright}
                    {preference name=feature_comm}
                    {preference name=feature_dynamic_content}
                    {preference name=feature_perspective}
                    {preference name=feature_sefurl}
                    {preference name=feature_html_pages}
                    {preference name=feature_htmlfeed}
                    {preference name=feature_areas}
                    {preference name=feature_system_suggestions}
                    {preference name=feature_templated_groups}
                    {preference name=site_mautic_enable}
                    <legend class="h3"><h4 class="showhide_heading" id="Watches">{tr}Watches{/tr} <a href="#Watches" class="heading-link" aria-label="{tr}Watches{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                    <div class="adminoptionboxchild">
                        <fieldset>
                            {preference name=feature_user_watches}
                            {preference name=feature_group_watches}
                            {preference name=feature_daily_report_watches}
                            <div class="adminoptionboxchild" id="feature_daily_report_watches_childcontainer">
                                {preference name=dailyreports_enabled_for_new_users}
                            </div>
                            {preference name=feature_user_watches_translations}
                            {preference name=feature_user_watches_languages}
                            {preference name=feature_groupalert}
                        </fieldset>
                    </div>
                    <legend class="h3"><h4 class="showhide_heading" id="Object_Maintainers_and_Freshness">{tr}Object maintainers and freshness{/tr} <a href="#Object_maintainers_and_freshness" class="heading-link" aria-label="{tr}Object maintainers and freshness{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                    <div class="adminoptionboxchild">
                        <fieldset>
                            {preference name=object_maintainers_enable}
                            <div class="adminoptionboxchild" id="object_maintainers_enable_childcontainer">
                                {preference name=object_maintainers_default_update_frequency}
                            </div>
                        </fieldset>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="h3"><h4 class="showhide_heading" id="Additional_features">{tr}Additional features{/tr} <a href="#Additional_features" class="heading-link" aria-label="{tr}Additional features{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                <div class="admin clearfix mb-3 featurelist">
                    {preference name=feature_sheet}
                    {preference name=feature_directory}
                    {preference name=feature_draw}
                    <div class="adminoptionboxchild" id="feature_draw_childcontainer">
                        {preference name=feature_draw_hide_buttons}
                        {preference name=feature_draw_separate_base_image}
                        <div class="adminoptionboxchild" id="feature_draw_separate_base_image_childcontainer">
                            {preference name=feature_draw_in_userfiles}
                        </div>
                    </div>
                    <div class="adminoptionboxchild">
                        <fieldset>
                            <legend class="h3"><h4 class="showhide_heading" id="Payment_and_accounting">{tr}Payment and accounting{/tr} <a href="#Payment_and_accounting" class="heading-link" aria-label="{tr}Payment and accounting{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                            {preference name=feature_credits}
                            {preference name=feature_accounting}
                            {preference name=payment_feature}
                        </fieldset>
                    </div>
                    <div class="adminoptionboxchild">
                        <fieldset>
                            <legend class="h3"><h4 class="showhide_heading" id="Mail_and_sharing">{tr}Mail and sharing{/tr} <a href="#Mail_and_sharing" class="heading-link" aria-label="{tr}Mail and sharing{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                            {preference name=feature_socialnetworks}
                            {preference name=feature_share}
                            {preference name=feature_webmail}
                            {preference name=feature_mailin}
                        </fieldset>
                    </div>
                    {preference name=feature_docs}
                    {preference name=feature_slideshow}
                    {preference name=feature_slideshow_pdfexport}
                    {preference name=feature_faqs}
                </div>
                <fieldset class="mb-3 w-100 clearfix featurelist">
                    <legend class="h3"><h4 class="showhide_heading" id="Progressive_web_app"> {tr}Progressive web app{/tr}  <a href="#Progressive_web_app" class="heading-link" aria-label="{tr}Progressive web app{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                    {preference name=pwa_feature}
                    {preference name=pwa_cache_links}
                    <div class="adminoptionboxchild" id="pwa_feature_childcontainer">
                        {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                            {if $prefs.feature_sefurl eq 'y'}
                                {tr}Search Engine Friendly URL feature needs to be disabled.{/tr}<br/>
                            {/if}
                            {tr}PWA feature requires bypassing CSRF Tokens, this represents a security issue.{/tr}
                        {/remarksbox}
                    </div>
                </fieldset>
            </fieldset>

            <fieldset>
                <legend class="h3"><h4 class="showhide_heading" id="Interaction_with_online_services_or_other_software">{tr}Interaction with online services or other software{/tr} <a href="#Interaction_with_online_services_or_other_software" class="heading-link" aria-label="{tr}Interaction with online services or other software{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                <div class="admin clearfix featurelist">
                    {preference name=connect_feature}
                    {preference name=feature_kaltura}
                    {preference name=zotero_enabled}
                    <div class="adminoptionboxchild" id="zotero_enabled_childcontainer">
                        {if $prefs.zotero_client_key and $prefs.zotero_client_secret and $prefs.zotero_group_id}
                            {remarksbox type=info title="{tr}Configuration completed{/tr}"}<a href="{service controller=oauth action=request provider=zotero}">{tr}Authenticate with Zotero{/tr}</a>{/remarksbox}
                        {/if}
                        {preference name=zotero_client_key}
                        {preference name=zotero_client_secret}
                        {preference name=zotero_group_id}
                        {preference name=zotero_style}
                    </div>
                    {preference name=webmonetization_enabled}
                    <div class="adminoptionboxchild" id="webmonetization_enabled_childcontainer">
                        {preference name=webmonetization_default_payment_pointer}
                        {preference name=webmonetization_all_website}
                        {preference name=webmonetization_always_default}
                        {preference name=webmonetization_default_paywall_text}
                    </div>
                </div>
            </fieldset>
        {/tab}

        {tab name="{tr}Interface{/tr}" key=interface}
            <br>
            <fieldset class="mb-3 w-100 clearfix featurelist">
                <legend class="h3"><h4 class="showhide_heading" id="Ajax"> {tr}Ajax{/tr}  <a href="#Ajax" class="heading-link" aria-label="{tr}Ajax{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                {preference name=feature_ajax}
                <div class="adminoptionboxchild" id="feature_ajax_childcontainer">
                    {preference name=ajax_autosave}
                </div>
            </fieldset>
            <fieldset class="mb-3 w-100 clearfix featurelist">
                <legend class="h3"><h4 class="showhide_heading" id="jQuery_plugins_and_add-ons"> {tr}jQuery plugins and add-ons{/tr}  <a href="#jQuery_plugins_and_add" class="heading-link" aria-label="{tr}jQuery {/tr}">{icon name="link"}</a></h4></legend>
                {preference name=feature_jquery_autocomplete}
                {preference name=feature_jquery_reflection}
                <div class="adminoptionbox">
                    {preference name=jquery_smartmenus_enable}
                    <div class="adminoptionboxchild" id="jquery_smartmenus_enable_childcontainer">
                        {preference name=jquery_smartmenus_collapsible_behavior}
                        {preference name=jquery_smartmenus_open_close_click}
                    </div>
                </div>
                {preference name=feature_jquery_tooltips}
                {preference name=feature_jquery_tagcanvas}
                {preference name=feature_jquery_ui_theme}
                {preference name=feature_jquery_ui}
                {preference name=feature_jquery_validation}
                {preference name=feature_jquery_zoom}
                <div class="adminoptionbox">
                    {preference name=jquery_select2}
                    <div class="adminoptionboxchild">
                        {preference name=jquery_select2_sortable label="{tr}Select2 sortable multiselect{/tr}"}
                    </div>
                </div>
                {preference name=jquery_fitvidjs}
                <div class="adminoptionboxchild" id="jquery_fitvidjs_childcontainer">
                    {preference name=jquery_fitvidjs_additional_domains}
                </div>
                {preference name=jquery_timeago}
                {preference name=jquery_jqdoublescroll}
                {preference name=allowImageLazyLoad}
                {preference name=tiki_prefix_css}
                <div class="adminoptionboxchild">
                    <fieldset>
                        <legend class="h3"><h4 class="showhide_heading" id="Experimental">{tr}Experimental{/tr} <a href="#Experimental" class="heading-link" aria-label="{tr}Experimental{/tr}"><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                        {preference name=feature_jquery_carousel}
                        {preference name=feature_jquery_tablesorter}
                        {preference name=vuejs_enable}
                        <div class="adminoptionboxchild" id="vuejs_enable_childcontainer">
                            {preference name=vuejs_always_load}
                            {preference name=vuejs_build_mode}
                            {preference name=tracker_field_rules}
                            {preference name=vuejs_toolbar_dialogs}
                        </div>
                    </fieldset>
                </div>
            </fieldset>
        {/tab}

        {tab name="{tr}Programmer{/tr}" key=programmer}
            <br>
            <div class="admin clearfix featurelist">
                {preference name=feature_integrator}
                {preference name=feature_xmlrpc}
                {preference name=feature_debug_console}
                {preference name=feature_tikitests}
                {preference name=disableJavascript}
                {preference name=javascript_assume_enabled}
                {preference name=smarty_compilation}
                {preference name=feature_webservices}
                {preference name=feature_dummy}
            </div>

            <div class="admin clearfix featurelist">
                <fieldset>
                    <legend class="h3"><h4 class="showhide_heading" id="Logging_and_Reporting">{tr}Logging and reporting{/tr} <a href="#Logging_and_reporting" class="heading-link" aria-label="{tr}Logging and reporting{/tr}><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                    <div class="adminoptionbox">
                        {preference name=error_reporting_level}
                        <div class="adminoptionboxchild">
                            {preference name=error_reporting_adminonly label="{tr}Visible to admin only{/tr}"}
                            {preference name=smarty_notice_reporting label="{tr}Include Smarty notices{/tr}"}
                        </div>
                    </div>

                    {preference name=log_mail}
                    {preference name=log_sql}
                    <div class="adminoptionboxchild" id="log_sql_childcontainer">
                        {preference name=log_sql_perf_min}
                    </div>
                    {preference name=log_tpl}
                </fieldset>
            </div>

            <div class="table">
                <fieldset>
                    <legend class="h3"><h4 class="showhide_heading" id="Custom_Code">{tr}Custom code{/tr} <a href="#Custom_code" class="heading-link" aria-label="{tr}Custom code{/tr}><span class="icon icon-link fas fa-link "></span></a></h4></legend>
                    {preference name="header_custom_js"}
                    {preference name=smarty_security}
                </fieldset>
            </div>
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
