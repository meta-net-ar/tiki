{if $prefs.theme_unified_admin_backend eq 'y'}
    {*   <nav class="navbar-{$navbar_color_variant} bg-{$navbar_color_variant} tiki-admin-aside-nav-{$prefs.theme_navbar_color_variant_admin} d-flex align-items-start flex-column{if not empty($smarty.cookies.sidebar_collapsed)} narrow{/if}" role="navigation">
           <div class="admin-menu-collapser navbar-{$navbar_color_variant} bg-{$navbar_color_variant}">
               {if not empty($smarty.cookies.sidebar_collapsed)}
                   {icon name='angle-double-right' class="nav-link float-end pt-1 pe-4" title='{tr}Collapse/expand this sidebar{/tr}'}
               {else}
                   {icon name='angle-double-left' class="nav-link float-end pt-1 pe-4" title='{tr}Collapse/expand this sidebar{/tr}'}
               {/if}
           </div>
           <div class="accordion accordion-flush w-100 border-end" id="admin-menu-accordion"> *}
    <div class="navbar-wrapper fixed-top" style="height: 100vh; max-width: var(--tiki-admin-offcanvas-width); overflow-y: auto; margin-top: 50px">
    <nav class="navbar {*fixed-top *}navbar-expand-lg py-0" {* align-items-startbg-body-tertiary  fixed-top navbar-{$navbar_color_variant} bg-{$navbar_color_variant} tiki-admin-aside-nav-{$prefs.theme_navbar_color_variant_admin}*} role="navigation">

        <div class="tiki-admin-aside-nav-{$prefs.theme_navbar_color_variant_admin}" style="height: 50px">
            {*<a class="navbar-brand" href="#">{tr}Admin menu{/tr}</a>*}
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div class="offcanvas offcanvas-start tiki-admin-aside-nav-{$prefs.theme_navbar_color_variant_admin}" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel" style="{*width: 268px;*} top: 50px; height: calc(100vh - 50px) !important;">
            <div class="offcanvas-header"{* style="width: 268px"*}>
                <h5 class="offcanvas-title nav-link" id="offcanvasNavbarLabel">{tr}Admin menu{/tr}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close" style="background-color: lightgray;"></button>
            </div>
            <div class="offcanvas-body p-0 w-100"{* style="width: 268px;"*}>
                <div class="tiki-admin-aside-nav-{$prefs.theme_navbar_color_variant_admin}">

                <h2 class="text-bg-secondary fs-4 py-2 px-3 mb-0">{icon name="sliders-h"} <span class="ms-1 narrow-hide">Configure</span></h2>

                    {* Preference filters moved from admin_navbar.tpl start *}
                    <form method="post" class="form g-3 align-items-center" role="form"{* style="width: 15rem;"*}> {* Specified width in rem so larger fonts wouldn't cause wrapping -- This width was overridden in the stylesheet so removed (6/8/2023) *}
                        {* <div class="col-auto form-check">
                            {ticket}
                            <input type="checkbox" id="preffilter-toggle-1" class="preffilter-toggle preffilter-toggle-round form-check-input {$pref_filters.advanced.type|escape}" value="advanced"{if !empty($pref_filters.advanced.selected)} checked="checked"{/if}>
                            <label for="preffilter-toggle-1" class="form-check-label"></label>
                        </div> *}
                        <div class="accordion accordion-flush" id="admin-accordion">
                        <div class="was-nav-item accordion-item">
                            <div class="accordion-header" id="flush-heading-preference-filters">
                                <button class="was-nav-link accordion-button collapsed px-4 py-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-preference-filters" aria-expanded="false" aria-controls="flush-collapse-preference-filters">
                                    {icon name="filter"}<span class="narrow-hide"> {tr}Preference Filters{/tr}</span> </button>
                            </div>
                            <div id="flush-collapse-preference-filters" class="accordion-collapse collapse" aria-labelledby="flush-heading-preference-filters" data-bs-parent="#admin-accordion">
                                <div class="accordion-body p-0">
                                    <div class="dropdown-menu show position-relative border-0 rounded-0">
                                        {foreach from=$pref_filters key=name item=info}
                                            <div class="dropdown-item tips right icon">
                                                <div class="form-check justify-content-start form-switch">
                                                    <label {if $name eq 'advanced'} class="tips" title="|{tr}Change your preference filter settings in order to view advanced preferences by default{/tr}"{/if}>
                                                        <input type="checkbox" class="form-check-input preffilter {$info.type|escape} input-pref_filters" name="pref_filters[]" value="{$name|escape}"{if !empty($info.selected)} checked="checked"{/if}{if $name eq basic} disabled="disabled"{/if}>
                                                        {$info.label|escape}
                                                    </label>
                                                </div>
                                            </div>
                                        {/foreach}
                                        <div class="dropdown-item d-none" id="preffilter-loader">
                                            <i class="fa fa-spinner fa-spin text-white"></i>
                                            <span class="text-white">{tr}Changing default preferences...{/tr}</span>
                                        </div>
                                        {if $prefs.connect_feature eq "y"}
                                            {capture name=likeicon}{icon name="thumbs-up"}{/capture}
                                            <div class="dropdown-item tips right icon">
                                                <div class="form-check">
                                                    <label class="form-check-label">
                                                        <input type="checkbox" id="connect_feedback_cbx" class="form-check-input mt-0 me-3"{if !empty($connect_feedback_showing)} checked="checked"{/if}>
                                                        {tr}Provide Feedback{/tr}
                                                        <a href="https://doc.tiki.org/Connect" target="tikihelp" class="tikihelp" title="{tr}Provide Feedback:{/tr}
                                            {tr}Once selected, some icon/s will be shown next to all features so that you can provide some on-site feedback about them{/tr}.
                                            <br/><br/>
                                            <ul>
                                                <li>{tr}Icon for 'Like'{/tr} {$smarty.capture.likeicon|escape}</li>
                                                {* <li>{tr}Icon for 'Fix me'{/tr} <img src=img/icons/connect_fix.png></li>
                                                <li>{tr}Icon for 'What is this for?'{/tr} <img src=img/icons/connect_wtf.png></li>  *}
                                            </ul>
                                            <br>
                                            {tr}Your votes will be sent when you connect with mother.tiki.org (currently only by clicking the 'Connect > <strong>Send Info</strong>' button){/tr}
                                            <br/><br/>
                                            {tr}Click to read more{/tr}
                                        ">
                                                            {icon name="help"}
                                                        </a> </label>
                                                </div>
                                            </div>
                                            {$headerlib->add_jsfile("lib/jquery_tiki/tiki-connect.js")}
                                        {/if}
                                    </div>
                                </div>
                            </div>
                    </form>
                </div>
                {* Preference filters moved from admin navbar.tpl end *}

                <div class="accordion-item pb-2 search-preferences" {* style="background: transparent;"*}>
                    <form method="post" class="d-flex justify-content-center my-md-0 ms-auto" role="form">
                        <div class="my-1 mx-4">
                            <input type="hidden" name="filters">
                            <div class="input-group">
                                <input type="text" role="search" aria-label="{tr}search admin preferences{/tr}" name="lm_criteria" value="{$lm_criteria|escape}" class="form-control form-control-sm" placeholder="{tr}Search preferences{/tr}...">
                                <button type="submit" aria-label="search" class="btn btn-primary btn-sm"{if $indexNeedsRebuilding} class="tips" title="{tr}Configuration search{/tr}|{tr}Note: The search index needs rebuilding, this will take a few minutes.{/tr}"{/if}>{icon name="search"}</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="nav-item was-accordion-item tips right admin-dashboard" title="{tr}Control Panels{/tr}|{tr}Go back to or reload the Control Panels / Administration Dashboard{/tr}">
                    <div class="was-accordion-header px-4 py-2 fs-6 fw-bold">
                        <a href="tiki-admin.php" class="nav-link {if empty($smarty.request.page)} opacity-50{/if}">
                            {icon name='home' iclass='fa-fw'}
                            <span class="narrow-hide">{tr}Admin Dashboard{/tr}</span> </a>
                    </div>
                </div>
                {foreach $admin_icons as $section => $secInfo}
                    <div class="was-nav-item accordion-item tips right" title="{$secInfo.title}|{$secInfo.description}">
                        <div class="accordion-header" id="flush-heading-{$section}">
                            <button class="was-nav-link accordion-button collapsed px-4 py-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-{$section}" aria-expanded="false" aria-controls="flush-collapse-{$section}">
                                {icon name=$secInfo.icon iclass='fa-fw'}
                                <span class="ms-1 narrow-hide">{$secInfo.title}</span> </button>
                        </div>
                        <div id="flush-collapse-{$section}" class="accordion-collapse collapse" aria-labelledby="flush-heading-{$section}" data-bs-parent="#admin-accordion">
                            <div class="accordion-body p-0">
                                <div class="dropdown-menu show position-relative {* {if $prefs.theme_navbar_color_variant_admin eq 'dark'}dropdown-menu-dark{/if} *}border-0 rounded-0">
                                    {foreach $secInfo.children as $page => $info}
                                        <a href="{if not empty($info.url)}{$info.url}{else}tiki-admin.php?page={$page}{/if}"
                                           class="tips right icon dropdown-item{if !empty($info.selected)} active{/if}{if !empty($info.disabled)} opacity-50{/if}"
                                           data-alt="{$info.title} {$info.description}" title="{$info.title}|{$info.description}">
                                            {icon name="admin_$page" iclass='fa-fw'}
                                            <span class="ms-1">{$info.title}</span>
                                        </a>
                                    {/foreach}
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}

                <h2 class="text-bg-secondary fs-4 py-2 px-3 mb-0 tips" title="{tr}Note: The links in the following sections go to pages that use the general, not admin, site navigation and appearance.{/tr}">
                    {icon name="manage"} <span class="ms-1 narrow-hide">Manage</span>
                </h2>
                {* Moved from admin_navbar_menu.tpl start *}
                {* navbar menu for admin_navbar.tpl *}
                <div class="was-nav-item accordion-item tips right" title="{tr}Access{/tr}|{tr}Manage user accounts, group membership, and group access to site features{/tr}">
                    <div class="accordion-header" id="flush-heading-access">
                        <button class="was-nav-link accordion-button collapsed px-4 py-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-access" aria-expanded="true" aria-controls="flush-collapse-access">
                            {icon name="door-open" iclass='fa-fw'}
                            <span class="ms-1 narrow-hide">{tr}Access{/tr}</span> </button>
                    </div>
                    <div id="flush-collapse-access" class="accordion-collapse collapse" aria-labelledby="flush-heading-access" data-bs-parent="#admin-accordion">
                        <div class="accordion-body p-0">
                            <div class="dropdown-menu show position-relative border-0 rounded-0">
                                {if $tiki_p_admin eq "y"}
                                <a href="tiki-admingroups.php" class="tips right icon dropdown-item">
                                    {icon name="users" iclass='fa-fw'}<span class="ms-2">{tr}Groups{/tr}</span>
                                    </a>
                                {/if}
                                {if $tiki_p_admin eq "y" and $tiki_p_admin_users eq "y"}
                                    <a href="tiki-adminusers.php" class="tips right icon dropdown-item">
                                        {icon name="user" iclass='fa-fw'}<span class="ms-1">{tr}Users{/tr}</span> </a>
                                {/if}
                                {if $tiki_p_admin eq "y"}
                                <a href="tiki-objectpermissions.php" class="tips right icon dropdown-item">
                                    {icon name="permission" iclass='fa-fw'}<span class="ms-1">{tr}Permissions{/tr}</span>
                                    </a>
                                {/if}
                                {if $prefs.feature_banning eq "y" and $tiki_p_admin_banning eq "y"}
                                    <div class="dropdown-divider"></div>
                                <a href="tiki-admin_banning.php" class="tips right icon dropdown-item">
                                    {icon name="ban" iclass='fa-fw'}
                                    <span class="ms-1">{tr}Banning{/tr}</span>
                                    </a>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="was-nav-item accordion-item tips right" title="{tr}Content{/tr}|{tr}Create and manage instances of activated media features{/tr}">
                    <div class="accordion-header" id="flush-heading-content">
                        <button class="was-nav-link accordion-button collapsed px-4 py-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-content" aria-expanded="false" aria-controls="flush-collapse-content">
                            {icon name="content" iclass='fa-fw'}
                            <span class="ms-1 narrow-hide">{tr}Content{/tr}</span>
                        </button>
                    </div>
                    <div id="flush-collapse-content" class="accordion-collapse collapse" aria-labelledby="flush-heading-content" data-bs-parent="#admin-accordion">
                        <div class="accordion-body p-0">
                            <div class="dropdown-menu show position-relative border-0 rounded-0">
                                {if $prefs.feature_articles eq "y"}
                                    <a href="tiki-list_articles.php" class="dropdown-item">
                                        {icon name="articles"} <span class="ms-1">{tr}Articles{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_banners eq "y" and $tiki_p_admin_banners eq "y"}
                                    <a class="dropdown-item" href="tiki-list_banners.php">
                                        {icon name="admin_ads"} <span class="ms-1">{tr}Banners{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_blogs eq "y"}
                                    <a class="dropdown-item" href="tiki-list_blogs.php">
                                        {icon name="bold"} <span class="ms-1">{tr}Blogs{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_calendar eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_calendars.php">
                                        {icon name="calendar-alt"} <span class="ms-1">{tr}Calendars{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_categories eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_categories.php">
                                        {icon name="sitemap" rotate="270"} <span class="ms-1">{tr}Categories{/tr}</span> </a>
                                {/if}
                                {if $tiki_p_admin_comments eq "y"}
                                    <a class="dropdown-item" href="tiki-list_comments.php">
                                        {icon name="comments"}<span class="ms-1"> {tr}Comments{/tr}</span> </a>
                                {/if}
                                {if $tiki_p_edit_cookies eq "y"}
                                    <a class="dropdown-item tips right" href="tiki-admin_cookies.php" title="{tr}Not HTTP cookies{/tr}|{tr}Legacy feature: random text presented like fortune cookie messages via a wiki plugin{/tr}">
                                        {icon name="cookie"} <span class="ms-1">{tr}Cookies{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_directory eq "y" and $tiki_p_admin_directory_cats eq "y"}
                                    <a class="dropdown-item" href="tiki-directory_admin.php">
                                        {icon name="directory"} <span class="ms-1">{tr}Directory{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_dynamic_content eq "y" and $tiki_p_admin_dynamic eq "y"}
                                    <a class="dropdown-item" href="tiki-list_contents.php">
                                        {icon name="copy"} <span class="ms-1">{tr}Dynamic Content{/tr}</span> </a>
                                {/if}
                                {if $tiki_p_admin_rssmodules eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_rssmodules.php">
                                        {icon name="rss"} <span class="ms-1">{tr}External Feeds{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_file_galleries eq "y"}
                                    <a class="dropdown-item" href="tiki-list_file_gallery.php">
                                        {icon name="folder-open"} <span class="ms-1">{tr}Files{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_faqs eq "y" and $tiki_p_view_faqs eq "y"}
                                    <a class="dropdown-item" href="tiki-list_faqs.php">
                                        {icon name="faq"} <span class="ms-1">{tr}FAQs{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_forums eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_forums.php">
                                        {icon name="comments"} <span class="ms-1">{tr}Forums{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_html_pages eq "y" and $tiki_p_edit_html_pages eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_html_pages.php">
                                        {icon name="html-pages"} <span class="ms-1">{tr}HTML Pages{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_newsletters eq "y" and $tiki_p_admin_newsletters eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_newsletters.php">
                                        {icon name="newspaper"} <span class="ms-1">{tr}Newsletters{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_polls eq "y" and $tiki_p_admin_polls eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_polls.php">
                                        {icon name="poll"} <span class="ms-1">{tr}Polls{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_quizzes eq "y" and $tiki_p_admin_quizzes eq "y"}
                                    <a class="dropdown-item" href="tiki-edit_quiz.php">
                                        {icon name="quiz"} <span class="ms-1">{tr}Quizzes{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_sheet eq "y" and $tiki_p_view_sheet eq "y"}
                                    <a class="dropdown-item" href="tiki-sheets.php">
                                        {icon name="spreadsheet"} <span class="ms-1">{tr}Spreadsheets{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_surveys eq "y" and $tiki_p_admin_surveys eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_surveys.php">
                                        {icon name="survey"} <span class="ms-1">{tr}Surveys{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_freetags eq "y"}
                                    <a class="dropdown-item" href="tiki-browse_freetags.php">
                                        {icon name="tags"} <span class="ms-1">{tr}Tags{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_trackers eq "y" and $tiki_p_list_trackers eq "y"}
                                    <a class="dropdown-item" href="tiki-list_trackers.php">
                                        {icon name="database"} <span class="ms-1">{tr}Trackers{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_wiki eq "y"}
                                    <a class="dropdown-item" href="tiki-listpages.php">
                                        {icon name="wiki"} <span class="ms-1">{tr}Wiki Pages{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_wiki eq "y" and $prefs.feature_wiki_structure eq "y" and $tiki_p_view eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_structures.php">
                                        <span style="display:inline-block; border: 1px solid var(--bs-body-color);padding: 2px;font-size: 80%;">{icon name="wiki"} {icon name="wiki"}</span>
                                        <span class="ms-1">{tr}Wiki Structures{/tr}</span> </a>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="was-nav-item accordion-item tips right" title="{tr}Website Functions{/tr}|{tr}Administer default and admin-activated features to facilitate site functionality{/tr}">
                    <div class="accordion-header" id="flush-heading-system">
                        <button class="was-nav-link accordion-button collapsed px-4 py-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-system" aria-expanded="false" aria-controls="flush-collapse-system">
                            {icon name="system"}
                            <span class="ms-1 narrow-hide">{tr}Website Functions{/tr}</span>
                        </button>
                    </div>
                    <div id="flush-collapse-system" class="accordion-collapse collapse" aria-labelledby="flush-heading-system" data-bs-parent="#admin-accordion">
                        <div class="accordion-body p-0">
                            <div class="dropdown-menu show position-relative border-0 rounded-0">
                                {if $tiki_p_admin eq "y"}
                                    <a class="dropdown-item" href="{service controller=managestream action=list}">
                                        {icon name=""} <span class="ms-1">{tr}Activity Rules{/tr}</span> </a>
                                {/if}
                                {if ($prefs.feature_wiki_templates eq "y" or $prefs.feature_cms_templates eq "y" or $prefs.feature_file_galleries_templates eq 'y') and $tiki_p_edit_content_templates eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_content_templates.php ">
                                        {icon name="clone"} <span class="ms-1">{tr}Content Templates{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_contribution eq "y" and $tiki_p_admin_contribution eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_contribution.php">
                                        {icon name=""} <span class="ms-1">{tr}Contributions{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_hotwords eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_hotwords.php">
                                        {icon name="fire"} <span class="ms-1">{tr}Hotwords{/tr}</span> </a>
                                {/if}
                                {if $prefs.lang_use_db eq "y" and $tiki_p_edit_languages eq "y"}
                                    <a class="dropdown-item" href="tiki-edit_languages.php">
                                        {icon name="language"} <span class="ms-1">{tr}Languages{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_live_support eq "y" and $tiki_p_live_support_admin eq "y"}
                                    <a class="dropdown-item" href="tiki-live_support_admin.php">
                                        {icon name="headset"} <span class="ms-1">{tr}Live Support{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_mailin eq "y" and $tiki_p_admin_mailin eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_mailin.php">
                                        {icon name="inbox"} <span class="ms-1">{tr}Mail-in{/tr}</span> </a>
                                {/if}
                                {if $tiki_p_admin_notifications eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_notifications.php">
                                        {icon name=""} <span class="ms-1">{tr}Mail Notifications{/tr}</span> </a>
                                {/if}
                                {if $tiki_p_edit_menu eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_menus.php">
                                        {icon name="list-alt"} <span class="ms-1">{tr}Menus{/tr}</span> </a>
                                {/if}
                                {if $tiki_p_admin_modules eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_modules.php">
                                        {icon name="shapes"} <span class="ms-1">{tr}Modules{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_perspective eq "y"}
                                    <a class="dropdown-item" href="tiki-edit_perspective.php">
                                        <span style="display:inline-block; border: 1px solid #343a40;padding: 2px;font-size: 80%;">{icon name="check-square" style="outline"} {icon name="check-square" style="outline"}</span><span class="ms-1">{tr}Perspectives{/tr}</span>
                                    </a>
                                {/if}
                                {if $prefs.feature_shoutbox eq "y" and $tiki_p_admin_shoutbox eq "y"}
                                    <a class="dropdown-item" href="tiki-shoutbox.php">
                                        {icon name=""} <span class="ms-1">{tr}Shoutbox{/tr}</span> </a>
                                {/if}
                                {if $prefs.payment_feature eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_credits.php">
                                        {icon name=""} <span class="ms-1">{icon name="credit-card"} <span class="ms-1">{tr}User Credits{/tr}</span>
                                    </a>
                                {/if}
                                {if $prefs.feature_theme_control eq "y" and $tiki_p_admin eq "y"}
                                    <a class="dropdown-item" href="tiki-theme_control.php">
                                        {icon name=""} <span class="ms-1">{tr}Theme Control{/tr}</span> </a>
                                {/if}
                                {if $tiki_p_admin_toolbars eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_toolbars.php">
                                        {icon name="keyboard"} <span class="ms-1">{tr}Toolbars{/tr}</span> </a>
                                {/if}
                                {if $tiki_p_admin eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_transitions.php">
                                        {icon name=""} <span class="ms-1">{tr}Transitions{/tr}</span> </a>
                                {/if}
                                {if $prefs.workspace_ui eq "y" and $tiki_p_admin eq "y"}
                                    <a class="dropdown-item" href="tiki-ajax_services.php?controller=workspace&action=list_templates">
                                        {icon name="copy"} <span class="ms-1">{tr}Workspace Templates{/tr}</span> </a>
                                {/if}
                                <div class="dropdown-divider"></div>
                                {if $tiki_p_plugin_approve eq "y"}
                                    <a class="dropdown-item" href="tiki-plugins.php">
                                        {icon name="clipboard-check"} <span class="ms-1">{tr}Plugin Approval{/tr}</span> </a>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="was-nav-item accordion-item tips right" title="{tr}Admin Tools{/tr}|{tr}Additional features for monitoring and managing the website{/tr}">
                    <div class="accordion-header" id="flush-heading-system">
                        <button class="was-nav-link accordion-button collapsed px-4 py-2 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-tools" aria-expanded="false" aria-controls="flush-collapse-tools">
                            {icon name="tools"}
                            <span class="ms-1 narrow-hide">{tr}Admin Tools{/tr}</span>
                        </button>
                    </div>
                    <div id="flush-collapse-tools" class="accordion-collapse collapse" aria-labelledby="flush-heading-tools" data-bs-parent="#admin-menu-accordion">
                        <div class="accordion-body p-0">
                            <div class="dropdown-menu show position-relative border-0 rounded-0">
                                {if $prefs.feature_actionlog eq "y" and $tiki_p_view_actionlog}
                                    <a class="dropdown-item" href="tiki-admin_actionlog.php">
                                        {icon name="table"} <span class="ms-1">{tr}Action Log{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_sefurl_routes eq "y" and $tiki_p_admin}
                                    <a class="dropdown-item" href="tiki-admin_routes.php">
                                        {icon name="edit"} <span class="ms-1">{tr}Custom Routes{/tr}</span> </a>
                                {/if}
                                <a class="dropdown-item" href="tiki-admin_dsn.php">
                                    {icon name="edit"} <span class="ms-1">{tr}DSN/Content Authentication{/tr}</span> </a>
                                {if $prefs.feature_editcss eq "y" and $tiki_p_create_css eq "y"}
                                    <a class="dropdown-item" href="tiki-edit_css.php">
                                        {icon name="edit"} <span class="ms-1">{tr}Edit CSS{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_view_tpl eq "y" and $prefs.feature_edit_templates eq "y" and $tiki_p_edit_templates eq "y"}
                                    <a class="dropdown-item" href="tiki-edit_templates.php">
                                        {icon name="edit"} <span class="ms-1">{tr}Edit TPL{/tr}</span> </a>
                                {/if}
                                {if $prefs.cachepages eq "y" and $tiki_p_admin eq "y"}
                                    <a class="dropdown-item" href="tiki-list_cache.php">
                                        {icon name=""} <span class="ms-1">{tr}External Pages Cache{/tr}</span> </a>
                                {/if}
                                <a class="dropdown-item" href="tiki-admin_external_wikis.php">
                                    {icon name=""} <span class="ms-1">{tr}External Wikis{/tr}</span> </a>
                                {if $tiki_p_admin_importer eq "y"}
                                    <a class="dropdown-item" href="tiki-importer.php">
                                        {icon name=""} <span class="ms-1">{tr}Importer{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_integrator eq "y" and $tiki_p_admin_integrator eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_integrator.php">
                                        {icon name=""} <span class="ms-1">{tr}Integrator{/tr}</span> </a>
                                {/if}
                                <a class="dropdown-item" href="tiki-phpinfo.php">
                                    {icon name="php"} <span class="ms-1">{tr}PhpInfo{/tr}</span> </a>
                                {if $prefs.feature_referer_stats eq "y" and $tiki_p_view_referer_stats eq "y"}
                                    <a class="dropdown-item" href="tiki-referer_stats.php">
                                        {icon name=""} <span class="ms-1">{tr}Referer Statistics{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_trackers eq "y" and $tiki_p_admin eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_orphaned.php">
                                        {icon name=""} <span class="ms-1">{tr}Orphaned Field Names{/tr}</span> </a>
                                {/if}
                                {if $prefs.feature_trackers eq "y" and $tiki_p_admin eq "y"}
                                    <a class="dropdown-item" href="tiki-pluginlist_experiment.php">
                                        {icon name=""} <span class="ms-1">{tr}Plugin List Test{/tr}</span> </a>
                                {/if}
                                <a role="link" class="dropdown-item" href="{bootstrap_modal controller=search action=rebuild}">
                                    {icon name=""} <span class="ms-1">{tr}Rebuild Index{/tr}</span> </a>
                                {if $prefs.feature_search_stats eq "y" and $tiki_p_admin eq "y"}
                                    <a class="dropdown-item" href="tiki-search_stats.php">
                                        {icon name=""} <span class="ms-1">{tr}Search Statistics{/tr}</span> </a>
                                {/if}
                                <a class="dropdown-item" href="tiki-admin_security.php">
                                    {icon name=""} <span class="ms-1">{tr}Security Admin{/tr}</span> </a>
                                <a class="dropdown-item" href="tiki-check.php">
                                    {icon name=""} <span class="ms-1">{tr}Server Check{/tr}</span> </a>
                                <a class="dropdown-item" href="tiki-admin_sync.php">
                                    {icon name=""} <span class="ms-1">{tr}Synchronize Dev{/tr}</span> </a>
                                {if $tiki_p_clean_cache eq "y"}
                                    <a class="dropdown-item" href="tiki-admin_system.php">
                                        {icon name=""} <span class="ms-1">{tr}System Cache{/tr}</span> </a>
                                {/if}
                                <a class="dropdown-item" href="tiki-syslog.php">
                                    {icon name=""} <span class="ms-1">{tr}System Logs{/tr}</span> </a>
                                {if $prefs.feature_scheduler eq "y" and $tiki_p_admin}
                                    <a class="dropdown-item" href="tiki-admin_schedulers.php">
                                        {icon name=""} <span class="ms-1">{tr}Scheduler{/tr}</span> </a>
                                {/if}
                                {if $prefs.tiki_monitor_performance eq 'y'}
                                    <a class="dropdown-item" href="tiki-performance_stats.php">
                                        {icon name="admin_performance"} <span class="ms-1">{tr}Performance{/tr}</span> </a>
                                {/if}
                                {if $prefs.sitemap_enable eq "y" and $tiki_p_admin}
                                    <a class="dropdown-item" href="tiki-admin_sitemap.php">
                                        {icon name="sitemap"} <span class="ms-1">{tr}Sitemap{/tr}</span> </a>
                                {/if}
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="tiki-wizard_admin.php">
                                    {icon name="wizards"} <span class="ms-1">{tr}Wizards{/tr}</span> </a> <br><br><br><br><br>
                            </div>
                        </div>
                    </div>
                </div>
                {* Moved from admin_navbar_menu.tpl end *}
                </div>
            </div>
        </div>
        {* </div> close navbar *}
    </nav>
    </div>
{else}
    {foreach from=$admin_icons key=page item=info}
        {if ! $info.disabled}
            <li>
                <a href="{if !empty($info.url)}{$info.url}{else}tiki-admin.php?page={$page}{/if}" data-alt="{$info.title} {$info.description}" class="tips bottom slow icon nav-link" title="{$info.title}|{$info.description}">
                    {icon name="admin_$page"}
                    {$info.title}
                </a>
            </li>
        {/if}
    {/foreach}
{/if}
