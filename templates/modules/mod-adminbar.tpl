{tikimodule error=$module_params.error title=$tpl_module_title name="adminbar" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

{if $tiki_p_admin == "y"}
{$main_admin_icons = [
    "general" => [
    'title' => tra('General'),
    'description' => tra('Global site configuration, date formats, etc.'),
    'help' => 'General-Admin'
    ],
    "features" => [
    'title' => tra('Features'),
    'description' => tra('Switches for major features'),
    'help' => 'Features-Admin'
    ],
    "login" => [
    'title' => tra('Log in'),
    'description' => tra('User registration, remember me cookie settings and authentication methods'),
    'help' => 'Login-Config'
    ],
    "user" => [
    'title' => tra('User Settings'),
    'description' => tra('User related preferences like info and picture, features, messages and notification, files, etc'),
    'help' => 'User-Settings'
    ],
    "profiles" => [
    'title' => tra('Profiles'),
    'description' => tra('Repository configuration, browse and apply profiles'),
    'help' => 'Profiles'
    ],
    "look" => [
    'title' => tra('Look & Feel'),
    'description' => tra('Theme selection, layout settings and UI effect controls'),
    'help' => 'Look-and-Feel'
    ],
    "textarea" => [
    'title' => tra('Editing & Plugins'),
    'description' => tra('Text editing settings applicable to many areas. Plugin activation and plugin alias management'),
    'help' => 'Text-area'
    ],
    "module" => [
    'title' => tra('Modules'),
    'description' => tra('Module appearance settings'),
    'help' => 'Module'
    ],
    "performance" => [
    'title' => tra('Performance'),
    'description' => tra('Server performance settings'),
    'help' => 'Performance'
    ],
    "security" => [
    'title' => tra('Security'),
    'description' => tra('Site security settings'),
    'help' => 'Security'
    ],
    "print" => [
    'title' => tra('Print Settings'),
    'description' => tra('Settings and features for print versions and pdf generation'),
    'help' => 'Print-Setting-Admin'
    ],
    "packages" => [
    'title' => tra('Packages'),
    'description' => tra('External packages installation and management'),
    'help' => 'Packages'
    ]
]}
    <a class="js-admin-bar link-admin-bar  me-auto btn btn-link" aria-label="Admin bar"
       title="Admin bar" role="button">{icon name='cog'}</a>
    <div class="sliding-panel-admin-bar invisible card rounded-0">
        <div class="container-md container-xs container-sm py-3 card-body">
            <div class="row d-flex flex-row g-2 align-items-center">
                <div class="col-md-4 align-self-start mb-2">
                    <div class="d-flex justify-content-start mb-2">
                        <form method="post" action="tiki-admin.php" target="_blank">
                            <label for="lm_criteria" class="form-label">{tr}Admin Features{/tr}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="lm_criteria" value="{$smarty.request.lm_criteria|escape}"  id="lm_criteria" aria-describedby="lm_criteria" placeholder="{tr}Search preferences...{/tr}">
                                <button class="btn btn-primary btn-sm" type="submit" aria-label="{tr}Search{/tr}"
                                        id="button-search">  <span class="icon icon-search fas fa-search fa-fw "></span></button>
                            </div>
                        </form>
                    </div>
                    <div id="adminbar" class="d-flex justify-content-start">
                        <div class="btn-group">
                            <a class="btn btn-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
                                {icon name="history"} {tr}Recent Actions{/tr} </a>
                            <ul class="dropdown-menu" role="menu">
                                {foreach $recent_prefs as $p}
                                    <li class="dropdown-item">
                                        <a href="tiki-admin.php?lm_criteria={$p|escape}&amp;exact">{$p|stringfix}</a>
                                    </li>
                                    {foreachelse}
                                    <li class="dropdown-item">{tr}None{/tr}</li>
                                {/foreach}
                            </ul>
                        </div>
                        <div class="btn-group">
                            <a class="btn btn-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
                                {icon name='menu-extra'} Quick Links </a>
                            <ul class="dropdown-menu" role="menu">
                                <li class="dropdown-item">
                                    <a href="tiki-wizard_admin.php?stepNr=0&amp;url=index.php">
                                        {icon name="wizard"} {tr}Wizards{/tr}
                                    </a>
                                </li>
                                <li class="dropdown-item">
                                    <a href="tiki-admin.php">
                                        {icon name="cog"} {tr}Control panels{/tr}
                                    </a>
                                </li>
                                <li class="dropdown-item">
                                    <a href="tiki-admin.php?page=look">
                                        {icon name="image"} {tr}Themes{/tr}
                                    </a>
                                </li>
                                <li class="dropdown-item">
                                    <a href="tiki-adminusers.php">
                                        {icon name="user"} {tr}Users{/tr}
                                    </a>
                                </li>
                                <li class="dropdown-item">
                                    <a href="tiki-admingroups.php">
                                        {icon name="group"} {tr}Groups{/tr}
                                    </a>
                                </li>

                                <li class="dropdown-item">
                                    {permission_link mode=text}
                                </li>

                                <li class="dropdown-item">
                                    <a href="tiki-admin_menus.php">
                                        {icon name="menu"} {tr}Menus{/tr}
                                    </a>
                                </li>

                                {if $prefs.lang_use_db eq "y"}
                                    {if isset($smarty.session.interactive_translation_mode) && $smarty.session.interactive_translation_mode eq "on"}
                                        <li class="dropdown-item">
                                            <a href="tiki-interactive_trans.php?interactive_translation_mode=off">
                                                {icon name="translate"} {tr}Turn off interactive translation{/tr}
                                            </a>
                                        </li>
                                    {else}
                                        <li class="dropdown-item">
                                            <a href="tiki-interactive_trans.php?interactive_translation_mode=on">
                                                {icon name="translate"} {tr}Turn on interactive translation{/tr}
                                            </a>
                                        </li>
                                    {/if}
                                {/if}

                                {if $prefs.feature_comments_moderation eq "y"}
                                    <li class="dropdown-item">
                                        <a href="tiki-list_comments.php">
                                            {icon name="comments"} {tr}Comment moderation{/tr}
                                        </a>
                                    </li>
                                {/if}

                                <li class="dropdown-item">
                                    <a href="tiki-admin_system.php?do=all">
                                        {icon name="trash"} {tr}Clear all caches{/tr}
                                    </a>
                                </li>
                                <li class="dropdown-item">
                                    <a href="{bootstrap_modal controller=search action=rebuild}">
                                        {icon name="index"} {tr}Rebuild search index{/tr}
                                    </a>
                                </li>
                                <li class="dropdown-item">
                                    <a href="tiki-plugins.php">
                                        {icon name="plugin"} {tr}Plugin approval{/tr}
                                    </a>
                                </li>
                                <li class="dropdown-item">
                                    <a href="tiki-syslog.php">
                                        {icon name="log"} {tr}Logs{/tr}
                                    </a>
                                </li>
                                <li class="dropdown-item">
                                    <a href="tiki-admin_modules.php">
                                        {icon name="module"} {tr}Modules{/tr}
                                    </a>
                                </li>

                                {if $prefs.feature_scheduler eq "y"}
                                    <li class="dropdown-item">
                                        <a href="tiki-admin_schedulers.php">
                                            {icon name="calendar"} {tr}Scheduler{/tr}
                                        </a>
                                    </li>
                                {/if}
                                {if $prefs.feature_queued_tasks eq "y"}
                                    <li class="dropdown-item">
                                        <a href="tiki-admin_queued_tasks.php">
                                            {icon name="clock-o"} {tr}Queued Tasks{/tr}
                                        </a>
                                    </li>
                                {/if}
                                {if $prefs.feature_sefurl_routes eq "y"}
                                    <li class="dropdown-item">
                                        <a href="tiki-admin_routes.php">
                                            {icon name="random"} {tr}Custom Routes{/tr}
                                        </a>
                                    </li>
                                {/if}
                                {if $prefs.feature_debug_console eq 'y'}
                                    <li class="dropdown-item">
                                        <a href="{query _type='relative' show_smarty_debug=1}">
                                            {icon name="bug"} {tr}Smarty debug window{/tr}
                                        </a>
                                    </li>
                                {/if}
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-7 align-self-start">
                    <ul class="d-flex align-items-center admin-bar-grid flex-wrap justify-content-start gap-3 list-unstyled mb-0">
                        {foreach from=$main_admin_icons key=page item=info}
                            <li class="admin-icon-item">
                                <a href="{if !empty($info.url)}{$info.url}{else}tiki-admin.php?page={$page}{/if}"
                                   class="d-flex gap-2 align-items-center text-decoration-none {if !empty($info.disabled)}disabled-clickable text-muted{/if}"
                                   title="{$info.title|escape} | {$info.description}">
                                    <span class="admin-icon-wrapper">{icon name="admin_$page"}</span>
                                    <span class="title fw-medium">{$info.title|escape}</span>
                                </a>
                            </li>
                        {/foreach}
                    </ul>
                </div>
                <div class="col-md-1 align-self-start justify-content-end">
                    <a class="js-admin-bar link-admin-bar me-auto btn btn-link" aria-label="Admin bar"
                       title="Admin bar" role="button">{icon name='cog'}</a>
                </div>
            </div>
        </div>
    </div>


{literal}
    <style>
        .top_modules .module:nth-child(3) {
            order: 2;
        }

        body.tiki {
            transition: transform 0.2s ease-in-out;
            backface-visibility: hidden;
        }

        .sliding-panel-admin-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 2000;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transform: translateY(-100%);
            transition: transform 0.2s ease-in-out;
            visibility: hidden;
        }

        .sliding-panel-admin-bar.open {
            transform: translateY(0);
            visibility: visible;
        }

        /*adding safe colors for nav bar dark*/
        .navbar-dark #adminbar a {
            color:#222 !important;
        }

        @media (min-width: 768px) {
            .sliding-panel-admin-bar .btn-group > .dropdown-menu {
                display: none;
                margin-top: 0;
            }

            .sliding-panel-admin-bar .btn-group:hover > .dropdown-menu {
                display: block;
            }
        }
        @media (max-width: 768px) {
            .top_modules .module:nth-child(2) {
                order: 1;
            }
        }
    </style>
{/literal}
    {jq}
        $(".js-admin-bar").on("click", function(e) {
        e.preventDefault();
        $(".sliding-panel-admin-bar").toggleClass("open invisible");
        });
    {/jq}
{/if}
{/tikimodule}
