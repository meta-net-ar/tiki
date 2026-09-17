<div id="tiki-installer">
    <input type="checkbox" id="tki-sidebar-toggle" class="tki-sidebar-toggle" tabindex="-1">
    <label for="tki-sidebar-toggle" class="tki-sidebar-backdrop" aria-hidden="true"></label>

    <div class="container-fluid">
        <div class="installer-content">
            <aside class="installer-sidebar-container">
                <div class="installer-sidebar-brand">
                    <a class="tki-nav-brand" href="https://tiki.org" target="_blank">
                        <img alt="{tr}Tiki Wiki CMS Groupware{/tr}" src="img/tiki/tikilogo.png" width="64" height="64">
                    </a>
                </div>
                <div class="installer-sidebar">
                    <form class="installer-menu menu" action="tiki-install.php" method="post">
                        {if $multi}<input type="hidden" name="multi" value="{$multi}">{/if}
                        {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                        {* <h4>{tr}Installation{/tr}</h4> *}
                        <ol class="nav flex-column {if $lang eq 'he' or $lang eq 'ar'}px-4{/if}">

                            {* step item base *}
                            {function name="printStepItem" step=0 active=false disabled=false completed=false title="" desc=""}
                                <li class="nav-item {if $active}active{/if} {if $completed}completed{/if}">
                                    <button class="btn-link nav-link" name="install_step" value="{$step}"
                                        {if $disabled}disabled="disabled" {/if}>
                                        <span class="step-marker"></span>
                                        <span class="step-info">
                                            <span class="step-title">{$title}</span>
                                            {if $desc}<span class="step-desc">{$desc}</span>{/if}
                                        </span>
                                    </button>
                                </li>
                            {/function}

                            {* step 0 *}
                            {call name="printStepItem"
                        step="0"
                        title="{tr}Welcome{/tr}"
                            desc="{tr}Get started with the Tiki installer{/tr}"
                            completed=$install_step>0
                            disabled=$install_step==0
                            active=$install_step==0}

                            {* step 1 *}
                            {call name="printStepItem"
                        step="1"
                        title="{tr}License{/tr}"
                            desc="{tr}Review the LGPL license terms{/tr}"
                            completed=$install_step>1
                            disabled=$install_step==1
                            active=$install_step==1}

                            {* step 2 *}
                            {call name="printStepItem"
                        step="2"
                        title="{tr}Review the System Requirements{/tr}"
                            desc="{tr}Check your server configuration{/tr}"
                            completed=$install_step>2
                            disabled=$install_step <= 2 && $dbcon !='y' active=$install_step==2} {* step 3 *} {call name="printStepItem"
                        step="3"
                        title={{$dbcon eq 'y'}|ternary:"{tr}Reset the Database Connection{/tr}":"{tr}Database
                                Connection{/tr}"} desc="{tr}Configure your database settings{/tr}"
                                completed=$install_step>3
                                disabled=$install_step <=3 && $dbcon !='y' active=$install_step==3} {* step 4 *} {call name="printStepItem"
                        step="4"
                        title={$tikidb_created|ternary:"{tr}Install/Upgrade{/tr}":"{tr}Install{/tr}"}
                                    desc="{tr}Install or upgrade your database{/tr}" completed=$install_step>4
                                    disabled=$install_step <=4 && $dbcon !='y' || !isset($smarty.post.scratch) ||
                                        isset($smarty.post.update) active=$install_step==4} {* step 5 *} {call name="printStepItem"
                        step="5"
                        title={isset($smarty.post.update)|ternary:"{tr}Review the Upgrade{/tr}":"{tr}Review the
                                        Installation{/tr}"} desc="{tr}Confirm your installation{/tr}"
                                        completed=$install_step>5
                                        disabled=$install_step <=5 && !$tikidb_is20 active=$install_step==5}
                                            {* step 6 *} {call name="printStepItem"
                        step="6"
                        title="{tr}Configure the General Settings{/tr}" desc="{tr}Set up your site preferences{/tr}"
                                            completed=$install_step>6
                                            disabled=$install_step <=6 && !$tikidb_is20 || isset($smarty.post.update)
                                                active=$install_step==6} {* step 7 *} {call name="printStepItem"
                        step="7"
                        title="{tr}Last Notes{/tr}" desc="{tr}Final setup notes{/tr}" completed=$install_step>7
                                                disabled=$install_step <=7 && !$tikidb_is20 active=$install_step==7}
                                                    {* step 8 *} {call name="printStepItem"
                        step="8"
                        title="{tr}Enter Your Tiki{/tr}" desc="{tr}Start using your new Tiki{/tr}"
                                                    disabled=$install_step <=8 && !$tikidb_is20 active=$install_step==8}
                                                    </ol>
                    </form>{* End of install-menu *}
                </div>
            </aside>

            <div class="installer-stage">
                <div class="installer-top-bar">
                    <div class="installer-top-bar-content">
                        <label for="tki-sidebar-toggle" class="tki-icon-btn tki-nav-hamburger">
                            <span class="visually-hidden">{tr}Open installation steps{/tr}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                viewBox="0 0 16 16" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
                            </svg>
                        </label>
                        <div class="installer-actions-content">
                            <div class="dropdown tki-help-menu">
                                <button type="button" class="tki-icon-btn tki-help-menu-toggle"
                                    data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true"
                                    aria-expanded="false" aria-label="{tr}Help and resources{/tr}"
                                    title="{tr}Help and resources{/tr}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        viewBox="0 0 16 16" aria-hidden="true">
                                        <path
                                            d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                        <path
                                            d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94" />
                                    </svg>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end tki-help-popover">
                                    <a class="tki-install-help-btn" href="https://tiki.org/forums" target="_blank"><svg
                                            xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-question-square" viewBox="0 0 16 16">
                                            <path
                                                d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                            <path
                                                d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94" />
                                        </svg> {tr}Do you need help?{/tr}</a>
                                    <span class="tki-separator tki-separator--inline"></span>
                                    <ul class="installer-help-links">
                                        <li class="nav-item">
                                            <a class="nav-link" href="https://tiki.org" target="_blank">
                                                {tr}Official Site{/tr}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="https://doc.tiki.org" target="_blank">
                                                {tr}Docs{/tr}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <span class="tki-separator tki-separator--actions"></span>
                            <button type="button" class="tki-theme-toggle" id="tki-theme-toggle"
                                aria-label="{tr}Toggle color theme{/tr}" title="{tr}Toggle color theme{/tr}"
                                data-label-light="{tr}Switch to light theme{/tr}"
                                data-label-dark="{tr}Switch to dark theme{/tr}">
                                <svg class="tki-theme-icon tki-theme-icon--moon" xmlns="http://www.w3.org/2000/svg"
                                    width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path
                                        d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278" />
                                </svg>
                                <svg class="tki-theme-icon tki-theme-icon--sun" xmlns="http://www.w3.org/2000/svg"
                                    width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path
                                        d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708" />
                                </svg>
                            </button>
                            <script type="text/javascript">
                                {literal}
                                    (function() {
                                        var key = 'tki-theme';
                                        var root = document.getElementById('tiki-installer');
                                        var btn = document.getElementById('tki-theme-toggle');
                                        if (!btn) { return; }

                                        function currentTheme() {
                                            return document.documentElement.getAttribute('data-tki-theme') === 'dark' ?
                                                'dark' : 'light';
                                        }

                                        function applyTheme(theme, animate) {
                                            if (animate) {
                                                document.documentElement.classList.add('tki-theme-switching');
                                            }
                                            document.documentElement.setAttribute('data-tki-theme', theme);
                                            document.documentElement.setAttribute('data-bs-theme', theme);
                                            if (root) {
                                                root.setAttribute('data-tki-theme', theme);
                                            }
                                            var next = theme === 'dark' ? btn.getAttribute('data-label-light') : btn
                                                .getAttribute('data-label-dark');
                                            btn.setAttribute('aria-label', next);
                                            btn.setAttribute('title', next);
                                            if (animate) {
                                                window.setTimeout(function() {
                                                    document.documentElement.classList.remove(
                                                        'tki-theme-switching');
                                                }, 320);
                                            }
                                        }

                                        applyTheme(currentTheme(), false);
                                        btn.addEventListener('click', function() {
                                            var next = currentTheme() === 'dark' ? 'light' : 'dark';
                                            try { localStorage.setItem(key, next); } catch (e) {}
                                            applyTheme(next, true);
                                        });
                                    })();
                                {/literal}
                            </script>
                        </div>
                    </div>
                </div>

                <main class="installer-body">
                    {* Server messages → floating toasts *}
                    <div id="tki-toast-host" class="tki-toast-host">
                        {if !empty($installer_toasts)}
                            {foreach $installer_toasts as $item}
                                {toast type=$item.type|default:'info' title=$item.title|default:'' description=$item.description|default:'' close=true duration=12}{/toast}
                            {/foreach}
                        {/if}
                    </div>

                    {if $install_step eq '0' or !$install_step}
                        <div class="install-step0 installer-step">
                            <div class="installer-step-content">
                                <div class="installer-step-header">
                                    <h2 class="installer-step-title tki-type-page-title">
                                        {tr}Let|\s set up your Tiki in just a few steps{/tr}</h2>
                                    <p class="installer-step-desc tki-type-body">
                                        {tr _0=$tiki_version_display}We|\ll guide you through installing or upgrading
                                        <strong>Tiki %0</strong> on this server{/tr}<br>{tr} from requirements to your first
                                        login.
                                        {/tr}
                                    </p>
                                </div>
                                <div class="installer-step-body">
                                    <div class="step-0-content">
                                        <div class="toast-cad toast-info mb-2">
                                            <div class="toast-card-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                                    <path
                                                        d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                    <path
                                                        d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                </svg>
                                            </div>
                                            <div class="toast-card-body">
                                                <span>
                                                    {tr}On this screen, choose your language and browse the links below if
                                                    you need them. When you continue, we|\ll walk through the license,
                                                    server
                                                    checks, database setup, and your admin account.
                                                    {/tr}</span>
                                            </div>
                                        </div>

                                        <div class="welcome-cards">
                                            {* Card 1 — release notes for this version *}
                                            <a class="welcome-card"
                                                href="https://doc.tiki.org/Tiki{$tiki_version_short|urlencode}"
                                                target="_blank">
                                                <span class="welcome-card-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5" />
                                                        <path
                                                            d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2" />
                                                        <path
                                                            d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1z" />
                                                    </svg>
                                                </span>
                                                <span class="welcome-card-body">
                                                    <span class="welcome-card-title tki-type-head-title">{tr}Release
                                                        notes{/tr}</span>
                                                    <span class="welcome-card-desc tki-type-body">
                                                        {tr}Read the latest
                                                        information about this
                                                        release.{/tr}</span>
                                                </span>
                                            </a>
                                            {* Card 2 — full documentation *}
                                            <a class="welcome-card" href="https://doc.tiki.org" target="_blank">
                                                <span class="welcome-card-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783" />
                                                    </svg>
                                                </span>
                                                <span class="welcome-card-body">
                                                    <span
                                                        class="welcome-card-title tki-type-head-title">{tr}Documentation{/tr}</span>
                                                    <span class="welcome-card-desc tki-type-body">{tr}Browse the complete
                                                        documentation at
                                                        doc.tiki.org.{/tr}</span>
                                                </span>
                                            </a>
                                            {* Card 3 — language picker (no title needed, the prompt + select speak for themselves) *}
                                            <div class="welcome-card welcome-card-lang">
                                                <span class="welcome-card-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M4.545 6.714 4.11 8H3l1.862-5h1.284L8 8H6.833l-.435-1.286zm1.634-.736L5.5 3.956h-.049l-.679 2.022z" />
                                                        <path
                                                            d="M0 2a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v3h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-3H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zm7.138 9.995q.289.451.63.846c-.748.575-1.673 1.001-2.768 1.292.178.217.451.635.555.867 1.125-.359 2.08-.844 2.886-1.494.777.665 1.739 1.165 2.93 1.472.133-.254.414-.673.629-.89-1.125-.253-2.057-.694-2.82-1.284.681-.747 1.222-1.651 1.621-2.757H14V8h-3v1.047h.765c-.318.844-.74 1.546-1.272 2.13a6 6 0 0 1-.415-.492 2 2 0 0 1-.94.31" />
                                                    </svg>
                                                </span>
                                                <span class="welcome-card-body">
                                                    <span class="welcome-card-desc tki-type-body">{tr}Select your
                                                        language{/tr}</span>
                                                    <form action="tiki-install.php" method="post"
                                                        class="welcome-card-lang-form">
                                                        <select name="lang" id="general-lang" class="form-select"
                                                            title="{tr}Select your language{/tr}"
                                                            onchange="$('.install-steps').tikiModal(tr('Loading...')); $('input[name=lang]:hidden').val($(this).val()); this.form.submit();">{section name=ix loop=$languages}
                                                                <option value="{$languages[ix].value|escape}"
                                                                    {if $prefs.site_language eq $languages[ix].value}
                                                                    selected="selected" {/if}>{$languages[ix].name}</option>
                                                            {/section}
                                                        </select>
                                                        <input type="hidden" name="install_step" value="0">{if $multi}<input
                                                            type="hidden" name="multi" value="{$multi}">{/if}
                                                    </form>
                                                </span>
                                            </div>

                                            {* Card 4 — the project site *}
                                            <a class="welcome-card" href="https://tiki.org" target="_blank">
                                                <span class="welcome-card-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m7.5-6.923c-.67.204-1.335.82-1.887 1.855A8 8 0 0 0 5.145 4H7.5zM4.09 4a9.3 9.3 0 0 1 .64-1.539 7 7 0 0 1 .597-.933A7.03 7.03 0 0 0 2.255 4zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a7 7 0 0 0-.656 2.5zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5zM8.5 5v2.5h2.99a12.5 12.5 0 0 0-.337-2.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5zM5.145 12q.208.58.468 1.068c.552 1.035 1.218 1.65 1.887 1.855V12zm.182 2.472a7 7 0 0 1-.597-.933A9.3 9.3 0 0 1 4.09 12H2.255a7 7 0 0 0 3.072 2.472M3.82 11a13.7 13.7 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5zm6.853 3.472A7 7 0 0 0 13.745 12H11.91a9.3 9.3 0 0 1-.64 1.539 7 7 0 0 1-.597.933M8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855q.26-.487.468-1.068zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.7 13.7 0 0 1-.312 2.5m2.802-3.5a7 7 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7 7 0 0 0-3.072-2.472c.218.284.418.598.597.933M10.855 4a8 8 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4z" />
                                                    </svg>
                                                </span>
                                                <span class="welcome-card-body">
                                                    <span class="welcome-card-title tki-type-head-title">{tr}About
                                                        Tik{/tr}</span>
                                                    <span class="welcome-card-desc tki-type-body">{tr}Learn more about the
                                                        project at tiki.org.{/tr}</span>
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="installer-step-footer">
                                <form action="tiki-install.php" method="post" class="step-footer-action">
                                    {if $multi}<input type="hidden" name="multi" value="{$multi}"> {/if}
                                    {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                                    <input type="hidden" name="install_step" value="1">
                                    <button type="submit" class="tiki-btn tiki-btn-primary">{tr}Next{/tr}
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                                        </svg>
                                    </button>
                                </form>
                            </div>{* End of installer-step-footer *}
                        </div>

                    {elseif $install_step eq '1'}
                        <div class="install-step1 installer-step">
                            <button type="button" class="license-scroll-fab tiki-btn tiki-btn-secondary"
                                id="license-scroll-bottom" aria-controls="license_content"
                                aria-label="{tr}Skip to the end{/tr}" title="{tr}Skip to the end{/tr}" hidden>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    viewBox="0 0 16 16" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708" />
                                </svg>
                            </button>
                            <div class="installer-step-content">
                                <div class="installer-step-header">
                                    <h2 class="installer-step-title tki-type-page-title">{tr}License Agreement{/tr}</h2>
                                    <p class="installer-step-desc tki-type-body">{tr}Please review the license terms
                                        governing the use, modification, and distribution of Tiki software.{/tr}
                                    </p>
                                </div>
                                <div class="installer-step-body">
                                    <div class="step-1-content">
                                        <pre id="license_content" class="ms-2"></pre>
                                        <div id="license-scroll-end" class="license-scroll-end" tabindex="-1"
                                            aria-hidden="true"></div>
                                    </div>
                                </div>
                            </div>


                            <div class="installer-step-footer">
                                <form action="tiki-install.php" method="post">
                                    {if $multi}<input type="hidden" name="multi" value="{$multi}">{/if}
                                    {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                                    <button type="submit" name="install_step" value="0" class="tiki-btn tiki-btn-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="bi bi-chevron-compact-left" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M9.224 1.553a.5.5 0 0 1 .223.67L6.56 8l2.888 5.776a.5.5 0 1 1-.894.448l-3-6a.5.5 0 0 1 0-.448l3-6a.5.5 0 0 1 .67-.223" />
                                        </svg>{tr}Previous{/tr}</button>
                                </form>
                                <form action="tiki-install.php" method="post">
                                    {if $multi}<input type="hidden" name="multi" value="{$multi}">{/if}
                                    {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                                    <input type="hidden" name="install_step" value="2">
                                    <button type="submit" class="tiki-btn tiki-btn-primary">{tr}Next{/tr}
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>{* End of install-step1 *}
                        <script type="text/javascript">
                            (function() {
                                fetch('license.txt')
                                    .then(function(response) { return response.text(); })
                                    .then(function(text) {
                                        document.getElementById('license_content').textContent = text;
                                    });

                                var btn = document.getElementById('license-scroll-bottom');
                                var end = document.getElementById('license-scroll-end');
                                var scrollRoot = document.querySelector('#tiki-installer .installer-body');
                                if (!btn || !end) {
                                    return;
                                }

                                var scrollThreshold = 72;
                                var bottomOffset = 120;

                                function updateFab() {
                                    if (!scrollRoot) {
                                        return;
                                    }
                                    var scrolled = scrollRoot.scrollTop > scrollThreshold;
                                    var nearEnd = scrollRoot.scrollHeight - scrollRoot.scrollTop - scrollRoot
                                        .clientHeight <
                                        bottomOffset;
                                    var show = scrolled && !nearEnd;
                                    btn.classList.toggle('is-visible', show);
                                    btn.hidden = !show;
                                }

                                if (scrollRoot) {
                                    scrollRoot.addEventListener('scroll', updateFab, { passive: true });
                                    updateFab();
                                }

                                btn.addEventListener('click', function() {
                                    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                                    if (scrollRoot) {
                                        var top = end.getBoundingClientRect().top - scrollRoot
                                            .getBoundingClientRect().top + scrollRoot.scrollTop;
                                        scrollRoot.scrollTo({
                                            top: top,
                                            behavior: reduce ? 'auto' : 'smooth'
                                        });
                                    } else {
                                        end.scrollIntoView({
                                            behavior: reduce ? 'auto' : 'smooth',
                                            block: 'end'
                                        });
                                    }
                                });
                            })();
                        </script>
                    {elseif $install_step eq '2'}
                        <div class="install-step2 installer-step">
                            <div class="installer-step-content">
                                <div class="installer-step-header">
                                    <h2 class="installer-step-title tki-type-page-title">{tr}Review the System
                                        Requirements{/tr}
                                    </h2>
                                    <p class="installer-step-desc tki-type-body">{tr}Tiki checks that your server is ready
                                        to
                                        run smoothly. Review each item below anything that needs your attention is clearly
                                        flagged.
                                        You can change these settings later.{/tr}</p>
                                </div>

                                <div class="installer-step-body">
                                    <div class="mb-4 step-card-info">
                                        <div class="toast-cad toast-info">
                                            <div class="toast-card-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                                    <path
                                                        d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                    <path
                                                        d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                </svg>
                                            </div>
                                            <div class="toast-card-body">
                                                <span class="simple-text-content">{tr}Before installing Tiki, <a
                                                        href="https://doc.tiki.org/Requirements" target="_blank"
                                                        class="alert-link">review the documentation</a> and confirm
                                                    that
                                                    your system meets the minimum requirements.{/tr}</span>
                                            </div>
                                        </div>
                                        <span class="step-card-info-text">{tr}This installer will perform some basic
                                            checks
                                            automatically.
                                            {/tr} {tr}Please see{/tr}
                                            <a href="tiki-check.php" target="_blank">{tr}a detailed report about your
                                                server.{/tr}</a>
                                        </span>
                                    </div>
                                    {* 1 *}
                                    {if $php_properties_missing}
                                        <div class="step-group-card">
                                            <div class="grp-head">
                                                <h5 class="grp-head-title">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                        viewBox="0 0 16 16">
                                                        <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                        <path fill="currentColor"
                                                            d="M2 4a2 2 0 0 1 2-2h5a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-1H4a2 2 0 0 1-2-2zm8 0a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1V7a2 2 0 0 1 2-2h3zM6 9.707V10h1.293L10 7.293V6h-.293zM8.293 6H7a1 1 0 0 0-1 1v1.293zM11 9a2 2 0 0 1-.04.403A2 2 0 0 1 9 11H6v1a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-1zm-2.293 1H9a1 1 0 0 0 1-1v-.293z" />
                                                    </svg>
                                                    {tr}Required extensions{/tr}
                                                </h5>
                                                <p class="grp-head-desc">
                                                    {tr}The PHP extensions listed below are required to continue the
                                                    installation.{/tr}
                                                </p>
                                            </div>
                                            <div class="grp-body">
                                                <span class="grp-strong-text">{tr}Missing extensions{/tr}</span>
                                                <ul class="install-requirements-list">
                                                    <li>Php extension 1</li>
                                                    <li>Php extension 2</li>
                                                    <li>Php extension 3</li>
                                                    <li>Php extension 4</li>
                                                    <li>required extensions</li>
                                                    {foreach from=$php_properties_missing item=missing}
                                                    <li>{$missing}</li>{/foreach}
                                                </ul>
                                            </div>
                                            <div class="grp-footer">
                                                <span class="grp-footer-text grp-footer-text-danger">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                        viewBox="0 0 12 12">
                                                        <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                        <path fill="currentColor"
                                                            d="M5.5 4.5a.5.5 0 0 1 1 0v1a.5.5 0 0 1-1 0zM6 6.75a.75.75 0 1 0 0 1.5a.75.75 0 0 0 0-1.5m-.786-5.291a.903.903 0 0 1 1.572 0l4.092 7.169c.348.61-.089 1.372-.787 1.372H1.91c-.698 0-1.135-.762-.787-1.372zm.786.66L2.083 8.983h7.834z" />
                                                    </svg>
                                                    {tr}Please enable them on your
                                                    server before proceeding.{/tr}</span>
                                            </div>
                                        </div>
                                    {/if}
                                    {* 2 *}
                                    <div class="step-group-card">
                                        <div class="grp-head">
                                            <h5 class="grp-head-title">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                    viewBox="0 0 24 24">
                                                    <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                    <path fill="currentColor"
                                                        d="M15.25 2a.75.75 0 0 1 .743.648L16 2.75v2.325A3.75 3.75 0 0 1 18.926 8h2.325a.75.75 0 0 1 .102 1.493l-.102.007H19v1.75h2.25a.75.75 0 0 1 .743.648L22 12a.75.75 0 0 1-.648.743l-.102.007H19v1.75h2.25a.75.75 0 0 1 .743.648l.007.102a.75.75 0 0 1-.648.743L21.25 16h-2.325A3.75 3.75 0 0 1 16 18.926v2.325a.75.75 0 0 1-1.493.102l-.007-.102V19h-1.751l.001 2.251a.75.75 0 0 1-.648.743L12 22a.75.75 0 0 1-.743-.648l-.007-.102l-.001-2.251H9.5v2.251a.75.75 0 0 1-.648.743L8.75 22a.75.75 0 0 1-.743-.648L8 21.25v-2.325A3.75 3.75 0 0 1 5.075 16H2.75a.75.75 0 0 1-.102-1.493l.102-.007L5 14.499v-1.75l-2.25.001a.75.75 0 0 1-.743-.648L2 12a.75.75 0 0 1 .648-.743l.102-.007L5 11.249v-1.75L2.75 9.5a.75.75 0 0 1-.743-.648L2 8.75a.75.75 0 0 1 .648-.743L2.75 8l2.325-.001A3.75 3.75 0 0 1 8 5.075V2.75a.75.75 0 0 1 1.493-.102l.007.102v2.249h1.749l.001-2.249a.75.75 0 0 1 .648-.743L12 2a.75.75 0 0 1 .743.648l.007.102l-.001 2.249H14.5V2.75a.75.75 0 0 1 .55-.723l.098-.02zm0 4.5h-6.5A2.25 2.25 0 0 0 6.5 8.75v6.5a2.25 2.25 0 0 0 2.25 2.25h6.5a2.25 2.25 0 0 0 2.25-2.25v-6.5a2.25 2.25 0 0 0-2.25-2.25m-3.245 2.505a3 3 0 1 1 0 6a3 3 0 0 1 0-6m0 1.5a1.5 1.5 0 1 0 0 3a1.5 1.5 0 0 0 0-3" />
                                                </svg>
                                                {tr}Server resources{/tr}
                                            </h5>
                                            <p class="grp-head-desc">
                                                {tr}Tiki uses PHP memory to handle requests, files, and upgrades. Too little
                                                memory may cause blank pages or failed operations.
                                                {/tr}
                                            </p>
                                        </div>
                                        <div class="grp-body">
                                            {if $php_memory_limit <= 0}
                                                <div class="notice-summary-card notice-summary-card--flush-bottom">
                                                    <div class="notice-summary-card__header">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 12 12">
                                                            <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                            <path fill="currentColor"
                                                                d="M7.904 6.332a.7.7 0 0 1 1.193 0l.051.102l.255.62a1 1 0 0 0 .544.545l.62.254a.7.7 0 0 1 0 1.295l-.62.255a1 1 0 0 0-.544.545l-.255.62a.7.7 0 0 1-1.295 0l-.254-.62a1 1 0 0 0-.545-.545l-.62-.255a.7.7 0 0 1 0-1.295l.62-.254a1 1 0 0 0 .545-.545l.254-.62zM3.818 1.379c.328-.542 1.166-.503 1.421.116l.501 1.219c.102.247.298.443.545.545l1.219.5a.8.8 0 0 1 0 1.48l-1.219.501a1 1 0 0 0-.545.545l-.5 1.219a.8.8 0 0 1-1.48 0l-.501-1.219a1 1 0 0 0-.545-.545l-1.219-.5a.8.8 0 0 1 0-1.48l1.219-.501c.247-.102.443-.298.545-.545l.5-1.219z" />
                                                        </svg>
                                                        <span class="notice-summary-card__title">{tr}PHP Memory
                                                            Limit{/tr}</span>
                                                    </div>
                                                    <p class="notice-summary-card__body">{tr}Tiki could not read a PHP <code
                                                            class="code_label">memory_limit</code> on this server usually it
                                                        is unset or <code class="code_value">-1</code>
                                                        (unlimited). {/tr}{tr}Scripts then run without a fixed ceiling, which is
                                                        fine for
                                                        installing Tiki.{/tr}{tr}To cap it, add a <code
                                                            class="code_label">memory_limit</code> line
                                                        to your <code class="code_label">php.ini</code> and restart your web
                                                        server.{/tr}</p>
                                                </div>
                                            {elseif $php_memory_limit < 128 * 1024 * 1024}
                                                {assign var=php_memory_required value=134217728}{assign var=php_memory_scale_max value=268435456}
                                                {if $php_memory_limit > 0}
                                                    {math equation="round((x/y)*100)" x=$php_memory_limit y=$php_memory_scale_max assign=php_memory_space_pct}
                                                    {math equation="round((x/y)*100)" x=$php_memory_required y=$php_memory_limit assign=php_memory_required_pct}
                                                    {if $php_memory_space_pct > 100}
                                                        {assign var=php_memory_space_pct value=100}

                                                    {/if}
                                                    {if $php_memory_required_pct > 100}
                                                        {assign var=php_memory_required_pct value=100}
                                                    {/if}
                                                {else}
                                                    {assign var=php_memory_space_pct value=0}
                                                    {assign var=php_memory_required_pct value=100}
                                                {/if}
                                                <div class="tki-insight-card">
                                                    <div class="tki-insight-card__grid">
                                                        <div class="tki-insight-card__metric tki-insight-card__metric--chart">
                                                            <span class="tki-insight-card__kicker">{tr}Memory space{/tr}</span>
                                                            <div class="tki-memory-bar"
                                                                style="--tki-memory-required: {$php_memory_required_pct};"
                                                                role="img"
                                                                aria-label="{tr}Current PHP memory limit:{/tr} {$php_memory_limit|kbsize:true:0}; {tr}required by Tiki:{/tr} {$php_memory_required|kbsize:true:0}">
                                                                <span class="tki-memory-bar__track">
                                                                    <span
                                                                        class="tki-memory-bar__fill tki-memory-bar__fill--required"></span>
                                                                    <span
                                                                        class="tki-memory-bar__fill tki-memory-bar__fill--current"></span>
                                                                </span>
                                                                <div class="tki-memory-bar__legend">
                                                                    <span
                                                                        class="tki-memory-bar__label tki-memory-bar__label--required">
                                                                        {tr}Required{/tr}
                                                                        <strong>{$php_memory_required|kbsize:true:0}</strong>
                                                                    </span>
                                                                    <span
                                                                        class="tki-memory-bar__label tki-memory-bar__label--current">
                                                                        {tr}Available{/tr}
                                                                        <strong>{$php_memory_limit|kbsize:true:0}</strong>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="tki-insight-card__metric">
                                                            <span class="tki-insight-card__kicker">
                                                                {tr}Current Limit{/tr}</span>
                                                            <span class="tki-insight-card__value">
                                                                {$php_memory_limit|kbsize:true:0}</span>
                                                        </div>

                                                        <div class="tki-insight-card__metric">
                                                            <span class="tki-insight-card__kicker">
                                                                {tr}Required{/tr}</span>
                                                            <span class="tki-insight-card__value">
                                                                {tr}128 MB minimum {/tr}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="notice-summary-card notice-summary-card--danger">
                                                    <div class="notice-summary-card__header">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                            fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                                                            <path
                                                                d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                                            <path
                                                                d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                                                        </svg>
                                                        <span class="notice-summary-card__title">
                                                            {tr}Memory limit too low{/tr}
                                                        </span>
                                                    </div>
                                                    <div class="notice-summary-card__content">
                                                        <p class="notice-summary-card__body">
                                                            {tr}Your server reports a PHP <code
                                                                class="code_label">memory_limit</code> of {/tr}
                                                            <code class="code_value">{$php_memory_limit|kbsize:true:0}</code>.
                                                            {tr} Tiki requires at least 128 MB. Set {/tr}<code
                                                                class="code_label">memory_limit</code>{tr} to {/tr}
                                                            <code class="code_value">128M</code>{tr} in {/tr}
                                                            <code class="code_label">php.ini</code>{tr}, then restart your
                                                            web
                                                            server.{/tr}
                                                        </p>
                                                    </div>
                                                </div>
                                            {else}
                                                <div class="notice-summary-card notice-summary-card--flush-bottom">
                                                    <div class="notice-summary-card__header">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 12 12">
                                                            <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                            <path fill="currentColor"
                                                                d="M7.904 6.332a.7.7 0 0 1 1.193 0l.051.102l.255.62a1 1 0 0 0 .544.545l.62.254a.7.7 0 0 1 0 1.295l-.62.255a1 1 0 0 0-.544.545l-.255.62a.7.7 0 0 1-1.295 0l-.254-.62a1 1 0 0 0-.545-.545l-.62-.255a.7.7 0 0 1 0-1.295l.62-.254a1 1 0 0 0 .545-.545l.254-.62zM3.818 1.379c.328-.542 1.166-.503 1.421.116l.501 1.219c.102.247.298.443.545.545l1.219.5a.8.8 0 0 1 0 1.48l-1.219.501a1 1 0 0 0-.545.545l-.5 1.219a.8.8 0 0 1-1.48 0l-.501-1.219a1 1 0 0 0-.545-.545l-1.219-.5a.8.8 0 0 1 0-1.48l1.219-.501c.247-.102.443-.298.545-.545l.5-1.219z" />
                                                        </svg>
                                                        <span class="notice-summary-card__title">
                                                            {tr}PHP Memory Limit{/tr}
                                                        </span>
                                                    </div>
                                                    <p class="notice-summary-card__body">{tr}Tiki detected a PHP <code
                                                            class="code_label">memory_limit</code> of {/tr}
                                                        <code class="code_value">{$php_memory_limit|kbsize:true:0}</code>{tr} on
                                                        your server, which meets Tiki|\s 128 MB minimum. You can continue the
                                                        installation.
                                                        {/tr}

                                                        {tr}Raise it later in your <code class="code_label">php.ini</code> if
                                                        you enable memory-heavy features.
                                                        {/tr}
                                                    </p>
                                                </div>
                                            {/if}
                                        </div>
                                    </div>
                                    {* 3 *}
                                    <div class="step-group-card">
                                        <div class="grp-head">
                                            <h5 class="grp-head-title">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                    viewBox="0 0 16 16">
                                                    <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                    <path fill="currentColor"
                                                        d="M4.5 2A2.5 2.5 0 0 0 2 4.5v6A2.5 2.5 0 0 0 4.5 13H5v-1h-.5A1.5 1.5 0 0 1 3 10.5v-6A1.5 1.5 0 0 1 4.5 3h6A1.5 1.5 0 0 1 12 4.5V7h1V4.5A2.5 2.5 0 0 0 10.5 2zm3 6A1.5 1.5 0 0 0 6 9.5v4A1.5 1.5 0 0 0 7.5 15h6a1.5 1.5 0 0 0 1.5-1.5v-4A1.5 1.5 0 0 0 13.5 8zM7 9.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v.484l-3.5 1.944L7 9.984zm0 1.627l3.257 1.81a.5.5 0 0 0 .486 0L14 11.127V13.5a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5zM5 5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1z" />
                                                </svg>
                                                {tr}Delivery Test{/tr}
                                            </h5>
                                            <p class="grp-head-desc">
                                                {tr}Tiki uses PHP/|s <code class="code_label">mail</code> function to send
                                                account notifications,
                                                password resets, and watch messages.
                                                {/tr}
                                                {tr}Send yourself a test email
                                                below to confirm your server can deliver mail before finishing the
                                                install.
                                                {/tr}
                                            </p>
                                        </div>
                                        <div class="grp-body">
                                            {if $mail_test_performed ne 'y'}
                                                <form action="tiki-install.php#mail" id="tki-mail-test-form" class="grp-form"
                                                    method="post">
                                                    <div class="grp-form-header">
                                                        <label class="grp-form-label" for="email_test_to">
                                                            {tr}Send a test email{/tr}
                                                        </label>
                                                        <p id="email_test_to_desc" class="grp-form-label-desc">
                                                            {tr}Tiki delivers a short message to this address so you can
                                                            confirm outgoing email works.
                                                            {/tr}
                                                        </p>
                                                    </div>

                                                    <div class="combo-input-container">
                                                        <div class="combo-input w-75">
                                                            <div class="combo-input-icon">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                                    viewBox="0 0 32 32">
                                                                    <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                    <path fill="currentColor"
                                                                        d="M6.5 5A4.5 4.5 0 0 0 2 9.5v13A4.5 4.5 0 0 0 6.5 27h19a4.5 4.5 0 0 0 4.5-4.5v-13A4.5 4.5 0 0 0 25.5 5zM28 10.403l-12 6.461l-12-6.461V9.5A2.5 2.5 0 0 1 6.5 7h19A2.5 2.5 0 0 1 28 9.5zM4 12.674l11.526 6.207a1 1 0 0 0 .948 0L28 12.674V22.5a2.5 2.5 0 0 1-2.5 2.5h-19A2.5 2.5 0 0 1 4 22.5z" />
                                                                </svg>
                                                            </div>
                                                            <input type="email" class="combo-input-field delivery-test-input"
                                                                name="email_test_to" id="email_test_to"
                                                                value="{if isset($email_test_to)}{$email_test_to|escape}{/if}"
                                                                placeholder="{tr}you@example.com{/tr}" autocomplete="email"
                                                                inputmode="email" aria-describedby="email_test_to_desc"
                                                                required>
                                                        </div>

                                                        <button type="submit" id="tki-mail-test-btn" class="combo-action"
                                                            aria-label="{tr}Send test email{/tr}">
                                                            <span class="tiki-btn-label">
                                                                <span>{tr}Send mail{/tr}</span>
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                    fill="currentColor" class="bi bi-send-fill"
                                                                    aria-hidden="true" viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471z" />
                                                                </svg>
                                                            </span>
                                                            <span class="tiki-btn-loading" role="status">
                                                                <span class="spinner-border spinner-border-sm"
                                                                    aria-hidden="true"></span>
                                                            </span>
                                                        </button>
                                                    </div>


                                                    <input type="hidden" name="install_step" value="2">
                                                    <input type="hidden" name="perform_mail_test" value="y">
                                                    {if $multi}<input type="hidden" name="multi" value="{$multi}">{/if}
                                                    {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                                                </form>
                                                {* Disable button when submitting *}
                                                <script type="text/javascript">
                                                    {literal}
                                                        (function() {
                                                            var form = document.getElementById('tki-mail-test-form');
                                                            var btn = document.getElementById('tki-mail-test-btn');
                                                            if (!form || !btn) { return; }

                                                            form.addEventListener('submit', function(e) {
                                                                if (form.dataset.submitting === '1') {
                                                                    e.preventDefault();
                                                                    return;
                                                                }
                                                                form.dataset.submitting = '1';
                                                                btn.classList.add('is-loading');
                                                                btn.setAttribute('aria-busy', 'true');
                                                                // Keep the button enabled until the browser has
                                                                // serialised the form, otherwise Safari drops it.
                                                                window.setTimeout(function() {
                                                                    btn.disabled = true;
                                                                }, 0);
                                                            });
                                                        })();
                                                    {/literal}
                                                </script>
                                            {else}
                                                {if $mail_test eq 'y'}
                                                    <div class="toast-cad toast-success d-inline-flex align-self-start">
                                                        <div class="toast-card-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                fill="currentColor" class="bi bi-chat-square-quote"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                                <path
                                                                    d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                            </svg>
                                                        </div>
                                                        <div class="toast-card-body mt-1">
                                                            <span>
                                                                {tr}Tiki delivered a test email to
                                                                {/tr} <span class="fw-bold">{$email_test_to|escape}</span></span>
                                                        </div>
                                                    </div>
                                                {else}
                                                    <div class="toast-cad toast-error d-inline-flex">
                                                        <div class="toast-card-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                fill="currentColor" class="bi bi-chat-square-quote"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                                <path
                                                                    d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                            </svg>
                                                        </div>
                                                        <div class="toast-card-body fw-medium">
                                                            <span>
                                                                {tr}Tiki couldn|\t send the test email. Check your mail log for
                                                                details.
                                                                {/tr}</span>
                                                            <span>

                                                                {tr}Review the mail settings in your <code
                                                                    class="code_label">php.ini</code>, for example, confirm that
                                                                <code class="code_label">sendmail_path</code> is correct.
                                                                {/tr}
                                                                <br>
                                                                {tr}If your host requires SMTP authentication, extra
                                                                configuration
                                                                may be needed.
                                                                {/tr}
                                                            </span>
                                                        </div>
                                                    </div>
                                                {/if}
                                            {/if}
                                        </div>
                                    </div>
                                    {* 4 *}
                                    <div class="step-group-card">
                                        <div class="grp-head">
                                            <h5 class="grp-head-title">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                    viewBox="0 0 24 24">
                                                    <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                    <path fill="currentColor"
                                                        d="M16.25 7.75a1.25 1.25 0 1 1-2.5 0a1.25 1.25 0 0 1 2.5 0m.667 9.242a2 2 0 0 1-.167.008h-1a.75.75 0 0 0 0 1.5h1c.966 0 1.75.784 1.75 1.75V21a.75.75 0 0 0 1.5 0v-.75a3.24 3.24 0 0 0-1.173-2.5A3.24 3.24 0 0 0 20 15.25v-9A3.25 3.25 0 0 0 16.75 3h-9.5A3.25 3.25 0 0 0 4 6.25v9c0 1.005.456 1.904 1.173 2.5A3.24 3.24 0 0 0 4 20.25V21a.75.75 0 0 0 1.5 0v-.75c0-.966.784-1.75 1.75-1.75h1a.75.75 0 0 0 0-1.5h-1q-.086 0-.168-.008l4.394-4.284a.75.75 0 0 1 1.047 0zM7.25 4.5h9.5c.966 0 1.75.784 1.75 1.75v9c0 .342-.098.66-.267.93l-4.663-4.546a2.25 2.25 0 0 0-3.14 0l-4.663 4.545a1.74 1.74 0 0 1-.267-.929v-9c0-.966.784-1.75 1.75-1.75m4 12.5a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5z" />
                                                </svg>
                                                {tr}Media library{/tr}
                                            </h5>
                                            <p class="grp-head-desc">
                                                {tr}Review and configure the image
                                                processing
                                                options used for resizing,
                                                thumbnail creation, and media optimization.
                                                {/tr}
                                            </p>
                                        </div>

                                        <div class="grp-body">
                                            <div class="timeline">
                                                <div class="timeline__header">
                                                    <span class="timeline__header-icon" aria-hidden="true">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 24 24">
                                                            <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                            <path fill="currentColor"
                                                                d="m8.471 7.237l3.056 12.992c.233.993 1.63 1.04 1.929.065l2.945-9.58l.384 1.527a1 1 0 0 0 .97.756H21a1 1 0 1 0 0-2h-2.466l-1.069-4.241c-.247-.981-1.628-1.017-1.925-.05l-2.912 9.472L9.475 2.771c-.24-1.02-1.688-1.03-1.943-.014l-2.065 8.24H3a1 1 0 0 0 0 2h3.247a1 1 0 0 0 .97-.757z" />
                                                        </svg>
                                                    </span>
                                                    <span class="grp-strong-text m-0">Processing</span>

                                                    {if $sample_image eq 'y'}
                                                        <span class="timeline__badge timeline__badge--success">
                                                            {tr}Ready
                                                            {/tr}</span>

                                                    {else}
                                                        <span class="timeline__badge timeline__badge--error">
                                                            {tr}Action
                                                            needed
                                                            {/tr}</span>

                                                    {/if}
                                                </div>

                                                <ul class="timeline__list">
                                                    <li class="timeline__item timeline__item--success">
                                                        <div class="timeline__rail" aria-hidden="true">
                                                            <span class="timeline__marker"></span>
                                                        </div>
                                                        <div class="timeline__card">
                                                            <p class="timeline__card-desc">
                                                                {tr}Tiki detected the
                                                                {/tr}
                                                                <strong>GD

                                                                    {if $lang eq 'he' or $lang eq 'ar'}{$gd_info|regex_replace:"/[()]/":""}

                                                                    {else}{$gd_info}

                                                                    {/if}
                                                                </strong>
                                                                {tr}image library, which it will use to process
                                                                and resize images.
                                                                {/tr}
                                                            </p>
                                                        </div>
                                                    </li>


                                                    {if $sample_image eq 'y'}
                                                        <li class="timeline__item timeline__item--success">
                                                            <div class="timeline__rail" aria-hidden="true">
                                                                <span class="timeline__marker"></span>
                                                            </div>
                                                            <div class="timeline__card">
                                                                <p class="timeline__card-desc">
                                                                    {tr}Image processing is working.
                                                                    Tiki generated a test image with GD, so galleries and
                                                                    thumbnails will render correctly.
                                                                    {/tr}</p>
                                                            </div>
                                                        </li>

                                                    {else}
                                                        <li class="timeline__item timeline__item--error">
                                                            <div class="timeline__rail" aria-hidden="true">
                                                                <span class="timeline__marker"></span>
                                                            </div>
                                                            <div class="timeline__card">
                                                                <p class="timeline__card-desc">
                                                                    {tr}Tiki could not generate a
                                                                    test image. Check that the GD library is installed and
                                                                    enabled in your PHP configuration, otherwise galleries
                                                                    and
                                                                    thumbnails may not render.
                                                                    {/tr}</p>
                                                            </div>
                                                        </li>

                                                    {/if}
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="grp-footer">
                                            <div class="grp-footer-text grp-footer-text-warning">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                    viewBox="0 0 12 12">
                                                    <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                    <path fill="currentColor"
                                                        d="M5.5 4.5a.5.5 0 0 1 1 0v1a.5.5 0 0 1-1 0zM6 6.75a.75.75 0 1 0 0 1.5a.75.75 0 0 0 0-1.5m-.786-5.291a.903.903 0 0 1 1.572 0l4.092 7.169c.348.61-.089 1.372-.787 1.372H1.91c-.698 0-1.135-.762-.787-1.372zm.786.66L2.083 8.983h7.834z" />
                                                </svg>
                                                <span>
                                                    {tr}Tiki uses the GD
                                                    library
                                                    to process images
                                                    for
                                                    the File Gallery and
                                                    CAPTCHA support.
                                                    {/tr}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>{* End of installer-step-body *}
                            </div>{* End of installer-step-content *}

                            <div class="installer-step-footer">
                                <form action="tiki-install.php" method="post">

                                    {if $multi}<input type="hidden" name="multi" value="{$multi}">
                                    {/if}

                                    {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                    {/if}
                                    <button type="submit" name="install_step" value="1" class="tiki-btn tiki-btn-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="bi bi-chevron-compact-left" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M9.224 1.553a.5.5 0 0 1 .223.67L6.56 8l2.888 5.776a.5.5 0 1 1-.894.448l-3-6a.5.5 0 0 1 0-.448l3-6a.5.5 0 0 1 .67-.223" />
                                        </svg>
                                        {tr}Previous
                                        {/tr}</button>
                                </form>
                                <form action="tiki-install.php" method="post">

                                    {if $multi}<input type="hidden" name="multi" value="{$multi}">
                                    {/if}

                                    {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                    {/if}
                                    <input type="hidden" name="install_step" value="3">
                                    <button type="submit" class="tiki-btn tiki-btn-primary">
                                        {tr}Next
                                        {/tr}
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                                        </svg>
                                    </button>
                                </form>
                            </div>{* End of installer-step-footer *}
                        </div>{* End of install-step2 *}

                    {elseif $install_step eq '3' or ($dbcon eq 'n' or $resetdb eq 'y')}
                        <div class="install-step3 installer-step">
                            <div class="installer-step-content">
                                <div class="installer-step-header">
                                    <h2 class="installer-step-title tki-type-page-title">
                                        {tr}Set the Database Connection{/tr}
                                    </h2>
                                    <span class="installer-step-desc tki-type-body">
                                        {tr}Tiki requires an active database
                                        connection.
                                        {/tr}
                                        {tr}You must create the database and
                                        user <strong>before</strong> completing this page, unless your database
                                        user has
                                        also permissions to create new databases and not just use them.
                                        {/tr}
                                    </span>
                                </div>

                                {* *}
                                <div class="installer-step-body">
                                    {if $dbcon ne 'y'}
                                        <div>
                                            <div class="toast-cad toast-warning">
                                                <div class="toast-card-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                                        <path
                                                            d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                        <path
                                                            d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                    </svg>
                                                </div>
                                                <div class="toast-card-body">
                                                    <span>
                                                        {tr}Tiki cannot find a database connection.
                                                        {/tr}
                                                        {tr}This is
                                                        normal for a new installation.
                                                        {/tr}</span>
                                                </div>
                                            </div>
                                            <div class="step-group-card mt-3">
                                                <div class="grp-head">
                                                    <h5 class="grp-head-title">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 32 32">
                                                            <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                            <path fill="currentColor"
                                                                d="M13.454 6.526a3.25 3.25 0 0 0 0 4.597l7.426 7.424a3.25 3.25 0 0 0 4.596 0l.036-.037a8.5 8.5 0 0 0 .666-11.272l3.53-3.53a1 1 0 0 0-1.415-1.414l-3.53 3.53a8.5 8.5 0 0 0-11.272.666zm1.415 3.182a1.25 1.25 0 0 1 0-1.768l.036-.036a6.5 6.5 0 1 1 9.193 9.192l-.036.036a1.25 1.25 0 0 1-1.768 0zm3.68 15.765a3.25 3.25 0 0 0 0-4.596l-7.425-7.424a3.25 3.25 0 0 0-4.596 0l-.036.036a8.5 8.5 0 0 0-.666 11.273l-3.53 3.53a1 1 0 1 0 1.415 1.413l3.53-3.529a8.5 8.5 0 0 0 11.272-.666zm-1.414-3.182a1.25 1.25 0 0 1 0 1.768l-.036.037a6.5 6.5 0 0 1-9.193-9.192l.036-.036a1.25 1.25 0 0 1 1.768 0z" />
                                                        </svg>
                                                        {tr}Connect Tiki to a database{/tr}
                                                    </h5>
                                                    <p class="grp-head-desc">
                                                        {tr}What you need to connect Tiki to a database. Gather
                                                        these three things before you continue, the next screen will ask for
                                                        them and Tiki cannot create its tables without them.{/tr}
                                                    </p>
                                                </div>
                                                <div class="grp-body">
                                                    {* Prerequisites | what the user needs before continuing *}
                                                    <div class="new-db-requirements px-2">
                                                        <ul class="new-db-checklist">
                                                            <li class="new-db-checklist-item">
                                                                <div class="new-db-checklist-head">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32"
                                                                        height="32" viewBox="0 0 32 32">
                                                                        <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                        <path fill="currentColor"
                                                                            d="M7 6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8.53c.696.044 1.366.132 2 .258V6a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v20a4 4 0 0 0 4 4h7.384c-.433-.46-.72-.966-.832-1.5H15.5V28H9a2 2 0 0 1-2-2h8.5v-2H7zm3.75 0A1.75 1.75 0 0 0 9 7.75v2.5c0 .966.784 1.75 1.75 1.75h10.5A1.75 1.75 0 0 0 23 10.25v-2.5A1.75 1.75 0 0 0 21.25 6zm.25 4V8h10v2zm6 18c0 .546.34 1.059.936 1.5c1.21.897 3.473 1.5 6.064 1.5c3.866 0 7-1.343 7-3v-6.327a7 7 0 0 1-1.46.827l-.04.017c-.6.254-1.274.463-2 .622a16.5 16.5 0 0 1-3.5.361c-2.086 0-4.046-.36-5.54-1a7 7 0 0 1-1.46-.827zm10.5-6.401c.772-.192 1.45-.445 2-.743c.94-.51 1.5-1.155 1.5-1.856c0-.7-.56-1.345-1.5-1.856c-.55-.298-1.228-.551-2-.743A14.7 14.7 0 0 0 24 16c-3.866 0-7 1.343-7 3s3.134 3 7 3c1.275 0 2.47-.146 3.5-.401" />
                                                                    </svg>
                                                                    <span class="new-db-checklist-title fw-semibold">
                                                                        {tr}An existing
                                                                        database
                                                                        {/tr}</span>
                                                                </div>
                                                                <p class="new-db-checklist-desc">
                                                                    {tr}Create an empty MySQL or MariaDB
                                                                    database on your server. Tiki will fill it with its
                                                                    own tables
                                                                    during
                                                                    the install.
                                                                    {/tr}</p>
                                                            </li>
                                                            <li class="new-db-checklist-item">
                                                                <div class="new-db-checklist-head">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32"
                                                                        height="32" viewBox="0 0 24 24">
                                                                        <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                        <path fill="currentColor"
                                                                            d="M4 6.5c0-.69.315-1.293.774-1.78c.455-.482 1.079-.883 1.793-1.202C7.996 2.877 9.917 2.5 12 2.5s4.004.377 5.433 1.018c.714.32 1.338.72 1.793 1.202c.459.487.774 1.09.774 1.78v5.337a3.5 3.5 0 0 0-1.5-.337V8.892c-.32.22-.68.417-1.067.59c-1.429.641-3.35 1.018-5.433 1.018s-4.004-.377-5.433-1.018a7 7 0 0 1-1.067-.59V18.5c0 .207.09.46.365.75c.279.296.717.596 1.315.864c1.195.535 2.9.886 4.82.886q.536 0 1.046-.035c.08.508.267 1.005.567 1.458q-.785.076-1.613.077c-2.083 0-4.004-.377-5.433-1.017c-.714-.32-1.338-.72-1.793-1.203C4.315 19.793 4 19.19 4 18.5zm1.5 0c0 .207.09.46.365.75c.279.296.717.596 1.315.864C8.375 8.649 10.08 9 12 9s3.625-.35 4.82-.886c.598-.269 1.036-.568 1.315-.864c.275-.29.365-.543.365-.75s-.09-.46-.365-.75c-.279-.296-.717-.596-1.315-.864C15.625 4.351 13.92 4 12 4s-3.625.35-4.82.886c-.598.268-1.036.568-1.315.864c-.275.29-.365.543-.365.75M21 15a2.5 2.5 0 1 1-5 0a2.5 2.5 0 0 1 5 0m2 5.375c0 1.556-1.286 3.125-4.5 3.125S14 21.937 14 20.375v-.103c0-.98.794-1.772 1.773-1.772h5.454c.98 0 1.773.793 1.773 1.772z" />
                                                                    </svg>
                                                                    <span class="new-db-checklist-title fw-semibold">
                                                                        {tr}A database user
                                                                        {/tr}</span>
                                                                </div>
                                                                <p class="new-db-checklist-desc">
                                                                    {tr}A user with full rights on that
                                                                    database, or one allowed to create databases so Tiki
                                                                    can set it
                                                                    up
                                                                    for you.
                                                                    {/tr}</p>
                                                            </li>
                                                            <li class="new-db-checklist-item">
                                                                <div class="new-db-checklist-head">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32"
                                                                        height="32" viewBox="0 0 24 24">
                                                                        <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                        <path fill="currentColor"
                                                                            d="M12 2q.465 0 .908.082a6.5 6.5 0 0 0-1.582 1.483A3.5 3.5 0 0 0 8.62 7.91a.75.75 0 0 1-.194.725L3.5 13.561V16.5h2v-1.75a.75.75 0 0 1 .75-.75H8.5v-1.25a.75.75 0 0 1 .22-.53l1.915-1.915q.336.7.823 1.298L10 13.06v1.689a.75.75 0 0 1-.75.75H7v1.75a.75.75 0 0 1-.75.75h-3.5a.75.75 0 0 1-.75-.75v-4a.75.75 0 0 1 .22-.53l4.855-4.855A5 5 0 0 1 12 2m4.5 1.5a4 4 0 0 0-1.894 7.524a.75.75 0 0 1 .394.66v7.734l2.017 1.834l2.123-1.799l-.921-.924a.75.75 0 0 1 0-1.058l.967-.971l-.967-.97A.75.75 0 0 1 18 15v-3.316a.75.75 0 0 1 .394-.66A4 4 0 0 0 16.5 3.5m-5.5 4a5.5 5.5 0 1 1 8.5 4.61v2.58l1.276 1.28a.75.75 0 0 1 0 1.06l-.967.97l.967.97a.75.75 0 0 1-.046 1.102l-3.245 2.75a.75.75 0 0 1-.99-.017l-2.75-2.5a.75.75 0 0 1-.245-.555v-7.64A5.5 5.5 0 0 1 11 7.5m6.5-1a1 1 0 1 1-2 0a1 1 0 0 1 2 0" />
                                                                    </svg>
                                                                    <span class="new-db-checklist-title fw-semibold">
                                                                        {tr}The connection
                                                                        details
                                                                        {/tr}</span>
                                                                </div>
                                                                <p class="new-db-checklist-desc"> {tr}Host, database name,
                                                                    username
                                                                    and password. Ask your hosting provider if you are not
                                                                    sure where to find them.
                                                                    {/tr}</p>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="tki-db-launcher combo-input-container">
                                                        <button type="button" class="combo-action" data-bs-toggle="offcanvas"
                                                            data-bs-target="#installer_3_new_db_form"
                                                            aria-controls="installer_3_new_db_form">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                                viewBox="0 0 20 20" aria-hidden="true">
                                                                <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                <path fill="currentColor"
                                                                    d="M6 2a2 2 0 0 0-2 2v3.126a9 9 0 0 1 1-.112V4a1 1 0 0 1 1-1h4v3.5A1.5 1.5 0 0 0 11.5 8H15v8a1 1 0 0 1-1 1h-3.051a2.6 2.6 0 0 1-.449 1H14a2 2 0 0 0 2-2V7.414a1.5 1.5 0 0 0-.44-1.06l-3.914-3.915A1.5 1.5 0 0 0 10.586 2zm8.793 5H11.5a.5.5 0 0 1-.5-.5V3.207zM6.879 8.12a6.5 6.5 0 0 1 1.12.3C9.207 8.87 10 9.634 10 10.5c0 1.38-2.015 2.5-4.5 2.5S1 11.88 1 10.5S3.015 8 5.5 8c.481 0 .945.042 1.38.12M5.5 14c1.38 0 2.679-.309 3.668-.858c.293-.163.578-.36.833-.59L10 16.5c0 1.38-2.015 2.5-4.5 2.5S1 17.88 1 16.5v-3.945c.255.23.54.425.832.588c.99.55 2.288.858 3.668.858" />
                                                            </svg>
                                                            {tr}Set new database connection{/tr}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    {else}
                                        <div>
                                            <div class="toast-cad toast-info w-100">
                                                <div class="toast-card-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                                        <path
                                                            d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                        <path
                                                            d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                    </svg>
                                                </div>
                                                <div class="toast-card-body">
                                                    <span>
                                                        {if $dbname}
                                                            {tr}Tiki found an existing database connection in your
                                                            local.php
                                                            file.
                                                            {/tr}
                                                            {tr}Database name{/tr}
                                                            <strong>
                                                                <span class="tki-db-name">{$dbname|escape}</span>
                                                            </strong>

                                                        {else}
                                                            {tr}Tiki found an automatic database connection for your
                                                            environment.
                                                            {/tr}

                                                        {/if}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="step-group-card mt-3">
                                                <div class="grp-head">
                                                    <h5 class="grp-head-title">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 24 24">
                                                            <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                            <path fill="currentColor"
                                                                d="M19.49 5.57a5.97 5.97 0 0 1 1.26 3.68c0 .84-.173 1.64-.485 2.366a6.5 6.5 0 0 0-1.429-.479a4.5 4.5 0 0 0-7.67-4.61c-.265.349-.185.833.126 1.143l3.793 3.794a6.5 6.5 0 0 0-1.37.751l-3.86-3.86c-.522-.522-.738-1.303-.388-1.952A6 6 0 0 1 18.43 4.51l2.29-2.29a.75.75 0 1 1 1.061 1.06zM8.354 9.855l3.861 3.861a6.5 6.5 0 0 0-.751 1.37L7.67 11.292c-.31-.31-.794-.391-1.143-.125a4.5 4.5 0 0 0 4.61 7.67q.16.752.479 1.428a6 6 0 0 1-2.366.485a5.97 5.97 0 0 1-3.68-1.26l-2.29 2.29a.75.75 0 0 1-1.06-1.06l2.29-2.29a6 6 0 0 1 1.892-8.962c.65-.351 1.43-.135 1.952.386m4.483 6.618a2 2 0 0 0 1.441-2.497l-.198-.686a5.3 5.3 0 0 1 1.483-.912l.499.524a2 2 0 0 0 2.899 0l.493-.518a5.3 5.3 0 0 1 1.484.922l-.186.63a2 2 0 0 0 1.45 2.511l.539.13a5.7 5.7 0 0 1 .006 1.808l-.584.144a2 2 0 0 0-1.44 2.496l.197.686a5.3 5.3 0 0 1-1.483.913l-.498-.525a2 2 0 0 0-2.9 0l-.493.518a5.3 5.3 0 0 1-1.484-.921l.187-.631a2 2 0 0 0-1.45-2.51l-.54-.13a5.7 5.7 0 0 1-.006-1.808zM16.05 17.5c0 .828.65 1.5 1.45 1.5s1.45-.672 1.45-1.5c0-.829-.65-1.5-1.45-1.5s-1.45.671-1.45 1.5" />
                                                        </svg>
                                                        {tr}How would you like to connect to your database?{/tr}
                                                    </h5>
                                                    <p class="grp-head-desc">
                                                        {tr}Tiki already detected a working database connection. Continue with
                                                        it or
                                                        set up {/tr}<br>{tr}a different one. You can change this
                                                        later.{/tr}
                                                    </p>
                                                </div>
                                                <div class="grp-body">
                                                    <div class="connection-choice-list">
                                                        {* Option 1 — keep the detected connection (recommended, the fast path) *}
                                                        <form action="tiki-install.php" method="post"
                                                            class="connection-card connection-card-existing is-recommended">
                                                            <input type="hidden" name="install_step" value="4">

                                                            {if $multi}<input type="hidden" name="multi" value="{$multi}">
                                                            {/if}

                                                            {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                                            {/if}
                                                            <button type="submit" class="connection-card-inner">
                                                                <span class="connection-card-icon" aria-hidden="true">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="28"
                                                                        height="28" fill="currentColor" viewBox="0 0 16 16">
                                                                        <path
                                                                            d="M4.318 2.687C5.234 2.271 6.536 2 8 2s2.766.27 3.682.687C12.644 3.125 13 3.627 13 4c0 .374-.356.875-1.318 1.313C10.766 5.729 9.464 6 8 6s-2.766-.27-3.682-.687C3.356 4.875 3 4.373 3 4c0-.374.356-.875 1.318-1.313M13 5.698V7c0 .374-.356.875-1.318 1.313C10.766 8.729 9.464 9 8 9s-2.766-.27-3.682-.687C3.356 7.875 3 7.373 3 7V5.698c.271.202.58.378.904.525C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777A5 5 0 0 0 13 5.698M14 4c0-1.007-.875-1.755-1.904-2.223C11.022 1.289 9.573 1 8 1s-3.022.289-4.096.777C2.875 2.245 2 2.993 2 4v9c0 1.007.875 1.755 1.904 2.223C4.978 15.711 6.427 16 8 16s3.022-.289 4.096-.777C13.125 14.755 14 14.007 14 13zm-1 4.698V10c0 .374-.356.875-1.318 1.313C10.766 11.729 9.464 12 8 12s-2.766-.27-3.682-.687C3.356 10.875 3 10.373 3 10V8.698c.271.202.58.378.904.525C4.978 9.711 6.427 10 8 10s3.022-.289 4.096-.777A5 5 0 0 0 13 8.698m0 3V13c0 .374-.356.875-1.318 1.313C10.766 14.729 9.464 15 8 15s-2.766-.27-3.682-.687C3.356 13.875 3 13.373 3 13v-1.302c.271.202.58.378.904.525C4.978 12.711 6.427 13 8 13s3.022-.289 4.096-.777c.324-.147.633-.323.904-.525" />
                                                                    </svg>
                                                                </span>
                                                                <span class="connection-card-body">
                                                                    <span class="connection-card-title">{tr}Use the existing
                                                                        connection{/tr}</span>
                                                                    <span class="connection-card-subtitle">
                                                                        {if $dbname}
                                                                            {tr}Continue with the database{/tr}
                                                                            <code class="code_label">{$dbname|escape}</code>
                                                                            {tr}that Tiki already found. This is the quickest way to
                                                                            finish the setup.{/tr}
                                                                        {else}
                                                                            {tr}Continue with the database Tiki already found. This
                                                                            is
                                                                            the quickest way to finish the setup.{/tr}
                                                                        {/if}
                                                                    </span>
                                                                </span>
                                                            </button>
                                                        </form>

                                                        {* Option 2 — enter a different connection (reveals the form below) *}
                                                        <a href="#installer_3_new_db_form" data-bs-toggle="offcanvas"
                                                            data-bs-target="#installer_3_new_db_form"
                                                            aria-controls="installer_3_new_db_form"
                                                            class="connection-card connection-card-modify" role="button">
                                                            <span class="connection-card-inner">
                                                                <span class="connection-card-icon" aria-hidden="true">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="28"
                                                                        height="28" fill="currentColor" viewBox="0 0 16 16">
                                                                        <path fill-rule="evenodd"
                                                                            d="M11.5 2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3M9.05 3a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0V3zM4.5 7a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3M2.05 8a2.5 2.5 0 0 1 4.9 0H16v1H6.95a2.5 2.5 0 0 1-4.9 0H0V8zm9.45 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3m-2.45 1a2.5 2.5 0 0 1 4.9 0H16v1h-2.05a2.5 2.5 0 0 1-4.9 0H0v-1z" />
                                                                    </svg>
                                                                </span>
                                                                <span class="connection-card-body">
                                                                    <span class="connection-card-title">{tr}Set up a
                                                                        different connection{/tr}</span>
                                                                    <span class="connection-card-subtitle">
                                                                        {tr}Enter your own host, database name and credentials
                                                                        to
                                                                        connect to another database.{/tr}
                                                                    </span>
                                                                </span>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    {/if}

                                    {* -:: New database connection panel — Bootstrap offcanvas *}
                                    <div id="installer_3_new_db_form" class="offcanvas offcanvas-end tki-db-panel"
                                        tabindex="-1" aria-labelledby="tki-db-panel-title" data-bs-scroll="false">
                                        <form id="inst-step-3-db-form" class="tki-db-panel-form" action="tiki-install.php"
                                            method="post">
                                            <input type="hidden" name="install_step" value="4">

                                            {if $multi}<input type="hidden" name="multi" value="{$multi}">
                                            {/if}

                                            {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                            {/if}

                                            {* Top bar | close on the left, context on the right *}
                                            <div class="offcanvas-header tki-db-panel-topbar">
                                                <button type="button" class="tki-db-panel-close" data-bs-dismiss="offcanvas"
                                                    aria-label="
                                            {tr}Close
                                            {/tr}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                                                        <path
                                                            d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z" />
                                                    </svg>
                                                </button>
                                                <span class="tki-db-panel-topbar-sep" aria-hidden="true"></span>
                                                <span class="tki-db-panel-topbar-meta ms-1">
                                                    {tr}Set up a database
                                                    connection
                                                    {/tr}</span>

                                            </div>

                                            {* Body *}
                                            <div class="offcanvas-body tki-db-panel-body">
                                                {* Heading | chips, title, lead — as in the reference panel *}
                                                <p class="tki-db-hint mb-1 tki-db-hint-line">
                                                    {tr}Enter your
                                                    host,
                                                    database
                                                    name and credentials or use the
                                                    {/tr}
                                                    <a href="https://doc.tiki.org/Manual-Installation" target="_blank">
                                                        <br>
                                                        {tr}manual installation
                                                        process</a>.
                                                    {/tr}
                                                </p>
                                                <fieldset class="mt-3">
                                                    <legend>
                                                        <span class="tki-db-legend-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                                viewBox="0 0 20 20">
                                                                <path fill="currentColor"
                                                                    d="M4 5c0-1.007.875-1.755 1.9-2.223A10.2 10.2 0 0 1 10 2a10.2 10.2 0 0 1 4.1.777C15.125 3.245 16 3.993 16 5v5.109a3 3 0 0 0-1 .258V6.7q-.421.31-.9.525A10.2 10.2 0 0 1 10 8a10.2 10.2 0 0 1-4.1-.777A5 5 0 0 1 5 6.7V15c0 .374.356.875 1.318 1.313A9.2 9.2 0 0 0 10 17h.021q.018.081.039.161l-.76.764q-.03.03-.058.061a10.2 10.2 0 0 1-3.342-.763C4.875 16.755 4 16.007 4 15zm1 0c0 .374.356.875 1.318 1.313A9.2 9.2 0 0 0 10 7a9.2 9.2 0 0 0 3.682-.687C14.644 5.875 15 5.373 15 5c0-.374-.356-.875-1.318-1.313A9.2 9.2 0 0 0 10 3a9.2 9.2 0 0 0-3.682.687C5.356 4.125 5 4.626 5 5m14.198 5.854a.5.5 0 1 0-.707-.708l-1.186 1.186l-.035.053a2 2 0 0 0-2.436.3l-.6.6a.98.98 0 0 0 0 1.386l1.442 1.439a.98.98 0 0 0 1.387 0l.6-.6a2 2 0 0 0 .3-2.437a.4.4 0 0 0 .053-.035zm-5.671 3.521a.98.98 0 0 0-1.386 0l-.6.6a2 2 0 0 0-.3 2.437a.4.4 0 0 0-.053.035l-1.182 1.185a.5.5 0 0 0 .707.707l1.185-1.186l.036-.053a2 2 0 0 0 2.436-.3l.6-.6a.98.98 0 0 0 0-1.387z" />
                                                            </svg>
                                                        </span>

                                                        {tr}Database information
                                                        {/tr}
                                                    </legend>
                                                    <p>
                                                        {tr}Enter your database connection information.
                                                        {/tr}</p>
                                                    <div class="tki-db-field">
                                                        <label class="tki-db-label" for="host">
                                                            {tr}Hostname
                                                            {/tr}
                                                            <a class="tki-db-help-toggle" href="javascript:void(0)"
                                                                onclick="flip('host_help');" title="
                                            {tr}Help
                                            {/tr}">

                                                                {icon name="help"}</a>
                                                        </label>
                                                        <input type="text" class="form-control" name="host" id="host"
                                                            value="{if isset($smarty.request.host)}{$smarty.request.host|escape:'html'}{elseif isset($preconfighost)}{$preconfighost|escape:'html'}{else}localhost{/if}" />
                                                        <span class="tki-db-hint">
                                                            {tr}Enter the host name or IP for your
                                                            database.
                                                            {/tr}</span>
                                                        <div class="tki-db-help" id="host_help" style="display:none;">
                                                            <p>
                                                                {tr}Use <strong>localhost</strong> if the
                                                                database is
                                                                running on the same machine as Tiki.
                                                                {/tr}</p>
                                                            <p>

                                                                {tr}For non-default port number use
                                                                <strong>example.com;port=3307</strong>.
                                                                {/tr}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="tki-db-field">
                                                        <label class="tki-db-label" for="name">
                                                            {tr}Database name
                                                            {/tr}
                                                            <a class="tki-db-help-toggle" href="javascript:void(0)"
                                                                onclick="flip('name_help');" title="
                                            {tr}Help
                                            {/tr}">

                                                                {icon name="help"}</a>
                                                        </label>
                                                        <input type="text" class="form-control" id="name" name="name"
                                                            value="{if isset($smarty.request.name)}{$smarty.request.name|escape:'html'}{elseif isset($preconfigname)}{$preconfigname|escape:'html'}{/if}"
                                                            placeholder="{tr}Database name{/tr}" pattern="[A-Za-z0-9_$-]+"
                                                            title="{tr}Use only letters, numbers, dollar signs, underscores, or hyphens.{/tr}" />
                                                        <span class="tki-db-hint">
                                                            {tr}Name of the database to be used. This
                                                            database will be created if it does not exist and permissions
                                                            allow creation.
                                                            {/tr}</span>
                                                        <div class="tki-db-help" id="name_help" style="display:none;">
                                                            <p>
                                                                {tr}You can create the database using Adminer,
                                                                MySQL
                                                                Workbench, phpMyAdmin, cPanel, or ask your
                                                                hosting
                                                                provider. If the database doesn/t exist and the
                                                                supplied
                                                                username has permissions, it will be
                                                                created.
                                                                {/tr}
                                                            </p>
                                                            <p>
                                                                {tr}If you are using a database which is already
                                                                being
                                                                used for something else (not recommended), check
                                                                db/tiki.sql to make sure the table names used by
                                                                Tiki
                                                                are not already used.
                                                                {/tr}</p>
                                                        </div>
                                                    </div>
                                                </fieldset>

                                                <fieldset>
                                                    <legend>
                                                        <span class="tki-db-legend-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                                viewBox="0 0 16 16">
                                                                <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                <path fill="currentColor"
                                                                    d="M7 1.502a.5.5 0 0 0-.697-.46L1.909 2.925A1.5 1.5 0 0 0 1 4.304V8.5a.5.5 0 0 0 .697.46L2 8.83v1.084A1.5 1.5 0 0 1 0 8.5V4.304a2.5 2.5 0 0 1 1.515-2.298L5.91.123a1.5 1.5 0 0 1 2.073 1.142l-.03.013l-.952.44zm3 2a.5.5 0 0 0-.697-.46l-5 2.143a.5.5 0 0 0-.303.46v4.852a.5.5 0 0 0 .697.46l.303-.13v1.084a1.5 1.5 0 0 1-2-1.414V5.644c0-.6.358-1.142.91-1.378l5-2.143a1.5 1.5 0 0 1 2.088 1.302L10 3.853zm2.303 1.54a.5.5 0 0 1 .697.46v.54c.359.06.696.184 1 .36v-.9a1.5 1.5 0 0 0-2.09-1.38l-5 2.144A1.5 1.5 0 0 0 6 7.645v4.851a1.5 1.5 0 0 0 2.025 1.406A3 3 0 0 1 8 13.5q.001-.377.106-.721l-.407.176A.5.5 0 0 1 7 12.496V7.645c0-.2.12-.381.303-.46zM14.5 9a2 2 0 1 1-4 0a2 2 0 0 1 4 0m1.5 4.5c0 1.245-1 2.5-3.5 2.5S9 14.75 9 13.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5" />
                                                            </svg>
                                                        </span>

                                                        {tr}Database user
                                                        {/tr}
                                                    </legend>
                                                    <p>
                                                        {tr}Enter a database user with administrator permission
                                                        for
                                                        the
                                                        Tiki database.
                                                        {/tr}</p>
                                                    <div class="tki-db-field">
                                                        <label class="tki-db-label" for="user">
                                                            {tr}User
                                                            name
                                                            {/tr}</label>
                                                        <input type="text" class="form-control" id="user" name="user"
                                                            value="{if isset($smarty.request.user)}{$smarty.request.user|escape:'html'}{elseif isset($preconfiguser)}{$preconfiguser|escape:'html'}{/if}"
                                                            maxlength="80" placeholder="{tr}Database username{/tr}"
                                                            pattern="[A-Za-z0-9_$-]+"
                                                            title="{tr}Use only letters, numbers, dollar signs, underscores, or hyphens.{/tr}">
                                                    </div>
                                                    <div class="tki-db-field">
                                                        <label class="tki-db-label" for="pass">
                                                            {tr}Password
                                                            {/tr}</label>

                                                        {if isset($preconfigpass)}
                                                            <input type="text" class="form-control" id="pass" name="pass"
                                                                value="{$preconfigpass|escape:'html'}"
                                                                autocomplete="new-password">

                                                        {else}
                                                            <input type="password" class="form-control" id="pass" name="pass"
                                                                autocomplete="new-password">

                                                        {/if}
                                                    </div>
                                                    {if $allow_create_new_user}
                                                        <label class="tki-db-check">
                                                            <input type="checkbox" id="create-new-user" name="create_new_user" />
                                                            <span>
                                                                {tr}Create the above database user just for this
                                                                Tiki
                                                                database.
                                                                {/tr}</span>
                                                        </label>
                                                    {else}
                                                        <p class="tki-db-hint">
                                                            {tr}Creating a new database user is only available during the
                                                            initial installation.{/tr}
                                                        </p>
                                                    {/if}
                                                </fieldset>

                                                {if $allow_create_new_user}
                                                    <fieldset id="new-user-fieldset" style="display: none;">
                                                        <legend>
                                                            <span class="tki-db-legend-icon">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                                    viewBox="0 0 20 20">
                                                                    <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                    <path fill="currentColor"
                                                                        d="M3 7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1.401a3 3 0 0 0-1-.36V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h8.264l.009.01c-.724.065-1.36.44-1.773.99H5a2 2 0 0 1-2-2zm8 8.5q0-.257.05-.5H2.5a.5.5 0 0 0 0 1h8.538a3 3 0 0 1-.038-.5m6.5-4.5a2 2 0 1 1-4 0a2 2 0 0 1 4 0m1.5 4.5c0 1.245-1 2.5-3.5 2.5S12 16.75 12 15.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5" />
                                                                </svg>
                                                            </span>

                                                            {tr}Administrative database user
                                                            {/tr}
                                                        </legend>
                                                        <p>
                                                            {tr}Enter database administrator user name and
                                                            password.
                                                            {/tr}
                                                            <em>
                                                                {tr}This is a DB admin user which has permission to
                                                                create
                                                                new databases and new users.
                                                                {/tr}</em>
                                                        </p>
                                                        <div class="tki-db-field">
                                                            <label class="tki-db-label" for="root_user">
                                                                {tr}DB admin
                                                                user
                                                                name
                                                                {/tr}</label>
                                                            <input type="text" class="form-control" id="root_user"
                                                                name="root_user"
                                                                value="{if isset($smarty.request.root_user)}{$smarty.request.root_user|escape:'html'}{elseif isset($preconfiguser)}{$preconfiguser|escape:'html'}{/if}"
                                                                placeholder="{tr}DB admin user name{/tr}">
                                                        </div>
                                                        <div class="tki-db-field">
                                                            <label class="tki-db-label" for="root_pass">
                                                                {tr}DB admin
                                                                password
                                                                {/tr}</label>
                                                            <input type="password" class="form-control" id="root_pass"
                                                                name="root_pass"
                                                                value="{if isset($smarty.request.root_pass)}{$smarty.request.root_pass|escape:'html'}{/if}"
                                                                autocomplete="new-password">
                                                        </div>
                                                    </fieldset>
                                                {/if}
                                                <input type="hidden" name="resetdb" value="y">
                                                <fieldset>
                                                    <legend>
                                                        <span class="tki-db-legend-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                                viewBox="0 0 16 16">
                                                                <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                <path fill="currentColor"
                                                                    d="M13.5 3.25a.75.75 0 0 1-.75.75h-2a.75.75 0 0 1-.252-1.457a6 6 0 0 0-1.833-.506A.75.75 0 0 1 8.83.546A7.46 7.46 0 0 1 12 1.655V1.25a.75.75 0 0 1 1.5 0zM5.501 13.457A.75.75 0 0 0 5.25 12h-2a.75.75 0 0 0-.75.75v2a.75.75 0 0 0 1.5 0v-.404a7.46 7.46 0 0 0 3.17 1.109a.75.75 0 1 0 .164-1.491a6 6 0 0 1-1.833-.507m5.406-8.191a.75.75 0 0 1 .58.888q-.03.14-.056.275a7 7 0 0 0 1.299-.368a.75.75 0 0 1 .542 1.399a8.4 8.4 0 0 1-2.08.52q-.028.274-.043.539a6 6 0 0 1 1.14.012a.75.75 0 0 1 1.466.133v.007l.002.008v.018q.003.019.003.047q.003.054 0 .14v.018c1.016.464 1.717 1.342 1.794 2.39c.097 1.334-.832 2.544-2.54 3.191a.75.75 0 0 1-.532-1.402c1.327-.503 1.609-1.231 1.576-1.68c-.022-.306-.2-.654-.555-.922q-.09.371-.227.834c-.237.795-.838 1.403-1.527 1.8a.75.75 0 0 1-1.27.483c-.523.105-1.086.107-1.596-.066c-.58-.197-1.078-.622-1.297-1.3c-.425-1.313.364-2.432 1.4-3.047q.302-.18.648-.316q.01-.41.047-.838a16 16 0 0 1-1.003-.08a.75.75 0 0 1 .16-1.492c.345.037.692.068 1.047.078q.06-.334.134-.689a.75.75 0 0 1 .888-.58M9.71 10.499c-.672.415-.817.893-.696 1.27c.056.172.163.276.352.34c.16.055.379.076.633.046a15 15 0 0 1-.29-1.656m1.687 1.03c.22-.197.376-.42.442-.644c.1-.334.175-.622.234-.869a4.5 4.5 0 0 0-.903.008c.041.49.118.982.227 1.505M3.12 4.21c.325-.18 1.006-.301 1.585-.162c.274.066.47.178.592.311a.7.7 0 0 1 .143.234l-.234-.027a6.6 6.6 0 0 0-1.112-.025c-.377.027-.8.101-1.173.288c-.604.302-1.052.704-1.29 1.245c-.231.531-.208 1.08-.11 1.573a2.3 2.3 0 0 0 1.5 1.723c.683.24 1.517.197 2.507-.187a.75.75 0 0 0 1.478-.184V5c0-.669-.215-1.23-.604-1.655c-.377-.411-.87-.64-1.345-.755c-.921-.222-1.99-.065-2.665.309A.75.75 0 0 0 3.12 4.21m2.386 1.907v1.435c-1.075.54-1.687.509-1.989.403a.8.8 0 0 1-.525-.602c-.068-.34-.045-.541.014-.677c.055-.126.19-.307.586-.505c.126-.063.329-.114.609-.134c.271-.02.57-.007.856.022c.16.016.313.037.449.058" />
                                                            </svg>
                                                        </span>

                                                        {tr}Character set
                                                        {/tr}
                                                    </legend>
                                                    <p>
                                                        {tr}Highly recommended for new installations. However, if
                                                        you
                                                        are
                                                        upgrading or migrating a previous tiki database, you are
                                                        recommended to uncheck this box
                                                        {/tr}.</p>
                                                    <label class="tki-db-check">
                                                        <input type="checkbox" name="force_utf8" id="force_utf8" value="y"
                                                            checked="checked">
                                                        <span>
                                                            {tr}Always force connection to use
                                                            UTF-8
                                                            {/tr}</span>
                                                    </label>
                                                    <p class="tki-db-hint"><a
                                                            href="https://doc.tiki.org/Understanding-Encoding"
                                                            onclick="window.open(this.href); return false;">
                                                            {tr}More
                                                            information
                                                            {/tr}</a></p>
                                                </fieldset>

                                            </div>{* /offcanvas-body *}

                                            {* footer *}
                                            {* dbinfo as hidden so the submit button can be disabled while loading
                                                           without dropping the POST field (disabled controls are omitted). *}
                                            <input type="hidden" name="dbinfo" value="y">
                                            <div class="tki-db-panel-footer">
                                                <button type="button" class="tiki-btn tiki-btn-tertiary tki-db-cancel-btn"
                                                    data-bs-dismiss="offcanvas">
                                                    {tr}Cancel
                                                    {/tr}</button>
                                                <button type="submit" id="tki-db-connect-btn"
                                                    class="tiki-btn tiki-btn-primary" data-loading-msg="
                                            {tr}Connecting…
                                            {/tr}">
                                                    <span class="tiki-btn-label">

                                                        {tr}Connect and continue
                                                        {/tr}
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                            fill="currentColor" class="bi bi-arrow-right-short"
                                                            viewBox="0 0 16 16" aria-hidden="true">
                                                            <path fill-rule="evenodd"
                                                                d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8" />
                                                        </svg>
                                                    </span>
                                                    <span class="tiki-btn-loading" hidden aria-hidden="true">
                                                        <span class="tiki-btn-spinner" aria-hidden="true"></span>

                                                        {tr}Connecting…
                                                        {/tr}
                                                    </span>
                                                </button>
                                            </div>

                                        </form>
                                    </div>{* /tki-db-panel *}

                                    <script type="text/javascript">
                                        {literal}
                                            (function() {
                                                var panel = document.getElementById('installer_3_new_db_form');
                                                if (!panel) { return; }

                                                // Reveal the administrative DB user fields.
                                                var createNewUser = document.getElementById('create-new-user');
                                                var adminFieldset = document.getElementById('new-user-fieldset');
                                                if (createNewUser && adminFieldset) {
                                                    var syncAdminFieldset = function() {
                                                        adminFieldset.style.display = createNewUser.checked ?
                                                            'block' :
                                                            'none';
                                                    };
                                                    syncAdminFieldset();
                                                    createNewUser.addEventListener('change', syncAdminFieldset);
                                                }

                                                // Submit loading: button spinner + panel overlay (blocks double submit).
                                                var form = panel.querySelector('form.tki-db-panel-form') || panel
                                                    .querySelector('form');
                                                var connectBtn = document.getElementById('tki-db-connect-btn');
                                                var cancelBtn = panel.querySelector('.tki-db-cancel-btn');
                                                if (form) {
                                                    form.addEventListener('submit', function(e) {
                                                        if (form.dataset.submitting === '1') {
                                                            e.preventDefault();
                                                            return;
                                                        }

                                                        // Trim text fields so trailing spaces never hit the server.
                                                        ['host', 'name', 'user', 'root_user'].forEach(function(id) {
                                                            var el = form.querySelector('#' + id);
                                                            if (el && typeof el.value === 'string') {
                                                                el.value = el.value.trim();
                                                            }
                                                        });

                                                        form.dataset.submitting = '1';
                                                        panel.classList.add('is-loading');

                                                        if (connectBtn) {
                                                            connectBtn.classList.add('is-loading');
                                                            connectBtn.setAttribute('aria-busy', 'true');
                                                            connectBtn.disabled = true;
                                                            var idle = connectBtn.querySelector('.tiki-btn-label');
                                                            var loading = connectBtn.querySelector(
                                                                '.tiki-btn-loading');
                                                            if (idle) {
                                                                idle.hidden = true;
                                                            }
                                                            if (loading) {
                                                                loading.hidden = false;
                                                                loading.removeAttribute('aria-hidden');
                                                            }
                                                        }
                                                        if (cancelBtn) {
                                                            cancelBtn.disabled = true;
                                                        }

                                                        var msg = (connectBtn && connectBtn.getAttribute(
                                                            'data-loading-msg')) || 'Connecting…';
                                                        if (window.jQuery && jQuery.fn.tikiModal) {
                                                            jQuery(panel).tikiModal(msg);
                                                        }
                                                    });
                                                }

                                                window.openInstallerDbPanel = function() {
                                                    var tries = 0;
                                                    (function attempt() {
                                                        if (window.bootstrap && window.bootstrap.Offcanvas) {
                                                            window.bootstrap.Offcanvas
                                                                .getOrCreateInstance(panel).show();
                                                            return;
                                                        }
                                                        if (tries++ < 60) {
                                                            setTimeout(attempt, 50);
                                                        }
                                                    })();
                                                };
                                            })();

                                        {/literal}

                                        {* Pre-opened on a fresh install (no connection detected yet). *}

                                        {if $dbcon ne 'y'}
                                            openInstallerDbPanel();

                                        {/if}
                                    </script>
                                </div>{* End of installer-step-body *}
                            </div>{* End of installer-step-content *}

                            {* footer step-3 *}
                            <div class="installer-step-footer">
                                <form action="tiki-install.php" method="post">

                                    {if $multi}<input type="hidden" name="multi" value="{$multi}">
                                    {/if}

                                    {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                    {/if}
                                    <button type="submit" name="install_step" value="2" class="tiki-btn tiki-btn-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="bi bi-chevron-compact-left" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M9.224 1.553a.5.5 0 0 1 .223.67L6.56 8l2.888 5.776a.5.5 0 1 1-.894.448l-3-6a.5.5 0 0 1 0-.448l3-6a.5.5 0 0 1 .67-.223" />
                                        </svg>

                                        {tr}Previous
                                        {/tr}</button>
                                </form>
                                <button class="tiki-btn tiki-btn-primary disabled">

                                    {tr}Next
                                    {/tr}
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd"
                                            d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                                    </svg>
                                </button>
                            </div>{* End of installer-step-footer *}
                        </div>{* End of install-step3 *}

                    {elseif $install_step eq '4'}
                        <div class="install-step4 installer-step">
                            <div class="installer-step-content">
                                <div class="installer-step-header">
                                    <h2 class="installer-step-title tki-type-page-title">
                                        {if $tikidb_created}
                                            {tr}Install & Upgrade{/tr}
                                        {else}
                                            {tr}Install{/tr}
                                        {/if}
                                    </h2>
                                    <span class="installer-step-desc tki-type-body">
                                        {tr}Complete these fields to configure common, general settings for your
                                        site.{/tr}{tr}The information you enter here can be changed later.{/tr}
                                    </span>
                                </div>

                                <div class="installer-step-body">
                                    {if $max_exec_set_failed eq 'y'}
                                        <div class="toast-cad toast-warning">
                                            <div class="toast-card-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                                    <path
                                                        d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                    <path
                                                        d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                </svg>
                                            </div>
                                            <div class="toast-card-body">
                                                <span>
                                                    {tr}Failed to set max_execution_time for PHP. You may
                                                    experience problems when creating/upgrading the database using this
                                                    installer on
                                                    a slow system. This can manifest itself by a blank page.{/tr}</span>
                                            </div>
                                        </div>
                                    {/if}

                                    {* Section | what this step does *}
                                    <div class="step-group-card">
                                        <div class="grp-head">
                                            <h5 class="grp-head-title">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                    viewBox="0 0 20 20">
                                                    <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                    <path fill="currentColor"
                                                        d="M4 5c0-1.007.875-1.755 1.9-2.223A10.2 10.2 0 0 1 10 2a10.2 10.2 0 0 1 4.1.777C15.125 3.245 16 3.993 16 5v5.109a3 3 0 0 0-1 .258V6.7q-.421.31-.9.525A10.2 10.2 0 0 1 10 8a10.2 10.2 0 0 1-4.1-.777A5 5 0 0 1 5 6.7V15c0 .374.356.875 1.318 1.313A9.2 9.2 0 0 0 10 17h.021q.018.081.039.161l-.76.764q-.03.03-.058.061a10.2 10.2 0 0 1-3.342-.763C4.875 16.755 4 16.007 4 15zm1 0c0 .374.356.875 1.318 1.313A9.2 9.2 0 0 0 10 7a9.2 9.2 0 0 0 3.682-.687C14.644 5.875 15 5.373 15 5c0-.374-.356-.875-1.318-1.313A9.2 9.2 0 0 0 10 3a9.2 9.2 0 0 0-3.682.687C5.356 4.125 5 4.626 5 5m14.198 5.854a.5.5 0 1 0-.707-.708l-1.186 1.186l-.035.053a2 2 0 0 0-2.436.3l-.6.6a.98.98 0 0 0 0 1.386l1.442 1.439a.98.98 0 0 0 1.387 0l.6-.6a2 2 0 0 0 .3-2.437a.4.4 0 0 0 .053-.035zm-5.671 3.521a.98.98 0 0 0-1.386 0l-.6.6a2 2 0 0 0-.3 2.437a.4.4 0 0 0-.053.035l-1.182 1.185a.5.5 0 0 0 .707.707l1.185-1.186l.036-.053a2 2 0 0 0 2.436-.3l.6-.6a.98.98 0 0 0 0-1.387z" />
                                                </svg>
                                                {if $tikidb_created}
                                                    {tr}Prepare the database{/tr}
                                                {else}
                                                    {tr}Populate the database{/tr}
                                                {/if}
                                            </h5>
                                            <p class="grp-head-desc">
                                                {if $tikidb_created}
                                                    {tr}This step will populate or upgrade the database
                                                    connected to Tiki.
                                                    Review the notes below before you choose Install or Upgrade.
                                                    {/tr}
                                                {else}
                                                    {tr}This step creates the Tiki tables in your database. You
                                                    can change site settings later from the control panel.
                                                    {/tr}
                                                {/if}
                                            </p>
                                        </div>
                                        <div class="grp-body">
                                            <div class="toast-cad toast-info w-100">
                                                <div class="toast-card-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-chat-square-quote"
                                                        viewBox="0 0 16 16">
                                                        <path
                                                            d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                        <path
                                                            d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                    </svg>
                                                </div>
                                                <div class="toast-card-body">
                                                    <span>
                                                        {if $tikidb_created}
                                                            {tr}If you want to upgrade from a previous Tiki release,
                                                            ensure that you have read and understood the <a
                                                                href="https://doc.tiki.org/Upgrade" target="_blank"
                                                                class="alert-link">Upgrade instructions</a>.
                                                            {/tr}
                                                        {else}
                                                            {tr}A new install will populate the database.{/tr}
                                                        {/if}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {* Section | character encoding *}
                                    <div class="step-group-card">
                                        <div class="grp-head">
                                            <h5 class="grp-head-title">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                    viewBox="0 0 16 16">
                                                    <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                    <path fill="currentColor"
                                                        d="M13.5 3.25a.75.75 0 0 1-.75.75h-2a.75.75 0 0 1-.252-1.457a6 6 0 0 0-1.833-.506A.75.75 0 0 1 8.83.546A7.46 7.46 0 0 1 12 1.655V1.25a.75.75 0 0 1 1.5 0zM5.501 13.457A.75.75 0 0 0 5.25 12h-2a.75.75 0 0 0-.75.75v2a.75.75 0 0 0 1.5 0v-.404a7.46 7.46 0 0 0 3.17 1.109a.75.75 0 1 0 .164-1.491a6 6 0 0 1-1.833-.507m5.406-8.191a.75.75 0 0 1 .58.888q-.03.14-.056.275a7 7 0 0 0 1.299-.368a.75.75 0 0 1 .542 1.399a8.4 8.4 0 0 1-2.08.52q-.028.274-.043.539a6 6 0 0 1 1.14.012a.75.75 0 0 1 1.466.133v.007l.002.008v.018q.003.019.003.047q.003.054 0 .14v.018c1.016.464 1.717 1.342 1.794 2.39c.097 1.334-.832 2.544-2.54 3.191a.75.75 0 0 1-.532-1.402c1.327-.503 1.609-1.231 1.576-1.68c-.022-.306-.2-.654-.555-.922q-.09.371-.227.834c-.237.795-.838 1.403-1.527 1.8a.75.75 0 0 1-1.27.483c-.523.105-1.086.107-1.596-.066c-.58-.197-1.078-.622-1.297-1.3c-.425-1.313.364-2.432 1.4-3.047q.302-.18.648-.316q.01-.41.047-.838a16 16 0 0 1-1.003-.08a.75.75 0 0 1 .16-1.492c.345.037.692.068 1.047.078q.06-.334.134-.689a.75.75 0 0 1 .888-.58M9.71 10.499c-.672.415-.817.893-.696 1.27c.056.172.163.276.352.34c.16.055.379.076.633.046a15 15 0 0 1-.29-1.656m1.687 1.03c.22-.197.376-.42.442-.644c.1-.334.175-.622.234-.869a4.5 4.5 0 0 0-.903.008c.041.49.118.982.227 1.505M3.12 4.21c.325-.18 1.006-.301 1.585-.162c.274.066.47.178.592.311a.7.7 0 0 1 .143.234l-.234-.027a6.6 6.6 0 0 0-1.112-.025c-.377.027-.8.101-1.173.288c-.604.302-1.052.704-1.29 1.245c-.231.531-.208 1.08-.11 1.573a2.3 2.3 0 0 0 1.5 1.723c.683.24 1.517.197 2.507-.187a.75.75 0 0 0 1.478-.184V5c0-.669-.215-1.23-.604-1.655c-.377-.411-.87-.64-1.345-.755c-.921-.222-1.99-.065-2.665.309A.75.75 0 0 0 3.12 4.21m2.386 1.907v1.435c-1.075.54-1.687.509-1.989.403a.8.8 0 0 1-.525-.602c-.068-.34-.045-.541.014-.677c.055-.126.19-.307.586-.505c.126-.063.329-.114.609-.134c.271-.02.57-.007.856.022c.16.016.313.037.449.058" />
                                                </svg>
                                                {tr}Character encoding
                                                {/tr}
                                            </h5>
                                            <p class="grp-head-desc">
                                                {tr}UTF-8mb4 is recommended so all languages and symbols can be stored
                                                correctly. {/tr}</p>
                                        </div>
                                        <div class="grp-body">
                                            {if (($database_charset neq 'utf8mb4' and $database_charset neq 'utf8') or isset($legacy_collation)) and $tikidb_created}
                                                <div class="install-card install-card-error">
                                                    <div class="install-card-header">
                                                        <span class="install-card-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                fill="currentColor" class="bi bi-exclamation-octagon-fill"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353zM8 4c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995A.905.905 0 0 1 8 4m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2" />
                                                            </svg>
                                                        </span>
                                                        <h3 class="install-card-title">
                                                            {tr}Encoding Issue
                                                            {/tr}</h3>
                                                    </div>
                                                    <div class="install-card-body">
                                                        {if isset($legacy_collation)}
                                                            <p class="install-card-text">
                                                                <strong>{tr}Something is wrong with the database
                                                                    encoding.{/tr}</strong>
                                                                {tr _0=$legacy_collation}The database has UTF-8 as default encoding
                                                                but some tables in the database have a different collation,
                                                                %0. Converting to UTF-8 may solve this but may also make matters
                                                                worse.
                                                                You should investigate what happened or only proceed with backups.
                                                                {/tr}
                                                            </p>
                                                        {else}
                                                            {tr _0=$database_charset}<p class="install-card-text">Your
                                                                database encoding is <strong>not</strong> in UTF-8mb4.</p>
                                                            <p class="install-card-text">Current encoding is
                                                                <em>%0</em>. The languages that will be available for content on the
                                                                site will be limited. If you
                                                                plan on using languages not covered by the character set, you should
                                                                re-create or alter the database so the default encoding is
                                                                <em>utf8mb4</em>.
                                                            </p>
                                                            {/tr}
                                                        {/if}

                                                        <p class="install-card-link">
                                                            <a href="https://doc.tiki.org/Understanding-Encoding"
                                                                target="_blank" class="alert-link">{tr}More information{/tr}</a>
                                                        </p>
                                                        <form method="post" action="" class="install-card-action">
                                                            <span class="install-card-action-title">
                                                                {tr}Character Set
                                                                Conversion
                                                                {/tr}</span>
                                                            <p class="install-card-text">
                                                                {tr}Use at your own risk. If the data in
                                                                the database currently contains improperly converted
                                                                data, this
                                                                may
                                                                make matters worse. Suitable for new installations.
                                                                Requires
                                                                ALTER
                                                                privilege on the database.
                                                                {/tr}</p>
                                                            <div class="install-card-action-buttons">
                                                                <button type="submit" name="convert_to_utf8" value="1"
                                                                    class="tiki-btn tiki-btn-warning">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                        height="16" fill="currentColor"
                                                                        class="bi bi-arrow-repeat" viewBox="0 0 16 16">
                                                                        <path
                                                                            d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41m-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9" />
                                                                        <path fill-rule="evenodd"
                                                                            d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3M3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z" />
                                                                    </svg>

                                                                    {tr}Convert database and tables to UTF-8
                                                                    {/tr}
                                                                </button>
                                                                <input type="hidden" name="install_step" value="4">

                                                                {if $multi}<input type="hidden" name="multi" value="{$multi}">
                                                                {/if}

                                                                {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                                                {/if}
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            {else}
                                                <div class="toast-cad simple-toast">
                                                    <div class="toast-card-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                            fill="currentColor" class="bi bi-database-check"
                                                            viewBox="0 0 16 16">
                                                            <path
                                                                d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514" />
                                                            <path
                                                                d="M12.096 6.223A5 5 0 0 0 13 5.698V7c0 .289-.213.654-.753 1.007a4.5 4.5 0 0 1 1.753.25V4c0-1.007-.875-1.755-1.904-2.223C11.022 1.289 9.573 1 8 1s-3.022.289-4.096.777C2.875 2.245 2 2.993 2 4v9c0 1.007.875 1.755 1.904 2.223C4.978 15.71 6.427 16 8 16c.536 0 1.058-.034 1.555-.097a4.5 4.5 0 0 1-.813-.927Q8.378 15 8 15c-1.464 0-2.766-.27-3.682-.687C3.356 13.875 3 13.373 3 13v-1.302c.271.202.58.378.904.525C4.978 12.71 6.427 13 8 13h.027a4.6 4.6 0 0 1 0-1H8c-1.464 0-2.766-.27-3.682-.687C3.356 10.875 3 10.373 3 10V8.698c.271.202.58.378.904.525C4.978 9.71 6.427 10 8 10q.393 0 .774-.024a4.5 4.5 0 0 1 1.102-1.132C9.298 8.944 8.666 9 8 9c-1.464 0-2.766-.27-3.682-.687C3.356 7.875 3 7.373 3 7V5.698c.271.202.58.378.904.525C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777M3 4c0-.374.356-.875 1.318-1.313C5.234 2.271 6.536 2 8 2s2.766.27 3.682.687C12.644 3.125 13 3.627 13 4c0 .374-.356.875-1.318 1.313C10.766 5.729 9.464 6 8 6s-2.766-.27-3.682-.687C3.356 4.875 3 4.373 3 4" />
                                                        </svg>
                                                    </div>
                                                    <div class="toast-card-body">
                                                        <span>
                                                            {tr}You are using a correct database encoding and
                                                            allowed legacy
                                                            collation as required {/tr}<br>{tr}to complete the installation.
                                                            {/tr}</span>
                                                    </div>
                                                </div>
                                            {/if}
                                        </div>
                                    </div>

                                    {* Section | install / upgrade choice (or admin login) *}
                                    {* {if $dbdone eq 'n'} *}
                                        <div class="step-group-card">
                                            <div class="grp-head">
                                                {if $logged eq 'y'}
                                                    <h5 class="grp-head-title">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 24 24">
                                                            <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                            <path fill="currentColor"
                                                                d="M16.06 12.901a2 2 0 0 0 2.9.001l.493-.518a5.3 5.3 0 0 1 1.485.922l-.187.63a2 2 0 0 0 1.45 2.511l.54.13a5.7 5.7 0 0 1 .005 1.807l-.584.144a2 2 0 0 0-1.44 2.496l.197.687a5.3 5.3 0 0 1-1.483.912l-.498-.524a2 2 0 0 0-2.899 0l-.493.518a5.3 5.3 0 0 1-1.484-.922l.186-.63a2 2 0 0 0-1.45-2.511l-.54-.13a5.7 5.7 0 0 1-.006-1.808l.584-.144a2 2 0 0 0 1.441-2.496l-.198-.686c.44-.382.94-.693 1.483-.913zM12 2c2.083 0 4.005.377 5.434 1.018c.713.32 1.336.72 1.792 1.203C19.684 4.707 20 5.31 20 6v5.498a6.5 6.5 0 0 0-1.5-.422V8.393c-.32.22-.68.416-1.066.59C16.004 9.622 14.083 10 12 10s-4.005-.377-5.434-1.018a7 7 0 0 1-1.066-.59V18c0 .207.09.459.365.75c.279.295.717.595 1.316.863c1.139.51 2.74.853 4.55.884a6.5 6.5 0 0 0 1.062 1.483Q12.401 22 12 22c-2.083 0-4.005-.377-5.434-1.018c-.713-.32-1.336-.72-1.792-1.203C4.316 19.293 4 18.691 4 18V6c0-.69.316-1.293.774-1.78c.456-.482 1.079-.883 1.792-1.202C7.996 2.377 9.917 2 12 2m5.499 14c-.8 0-1.449.672-1.45 1.5S16.7 19 17.5 19s1.45-.672 1.45-1.5S18.3 16 17.5 16M12 3.5c-1.921 0-3.624.351-4.82.887c-.598.268-1.036.568-1.315.863c-.274.291-.365.543-.365.75s.09.459.365.75c.279.295.717.595 1.316.863C8.376 8.15 10.079 8.5 12 8.5s3.624-.351 4.82-.887c.598-.268 1.036-.568 1.315-.863c.274-.291.365-.543.365-.75s-.09-.459-.365-.75c-.279-.295-.717-.595-1.316-.863C15.624 3.85 13.921 3.5 12 3.5" />
                                                        </svg>
                                                        {tr}Choose how to set up your database
                                                        {/tr}
                                                    </h5>
                                                    <p class="grp-head-desc">
                                                        {tr}Start with a fresh installation
                                                        or upgrade
                                                        your existing Tiki database. Only the option you select
                                                        below will
                                                        change
                                                        your data.
                                                        {/tr}</p>
                                                {else}
                                                    <h5 class="grp-head-title">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 24 24">
                                                            <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                            <path fill="currentColor"
                                                                d="M11.495 3.547a.75.75 0 0 1 1.005.705v.72l1.5-.543v-.177a2.25 2.25 0 0 0-3.016-2.116l-6.84 2.475A3.25 3.25 0 0 0 2 7.667v6.082a2.25 2.25 0 0 0 3 2.121v-1.595l-.495.18a.75.75 0 0 1-1.005-.706V7.667a1.75 1.75 0 0 1 1.155-1.645zm4 3a.75.75 0 0 1 1.005.705v.72l1.5-.543v-.177a2.25 2.25 0 0 0-3.016-2.116l-7.17 2.595A2.75 2.75 0 0 0 6 10.317v6.432a2.25 2.25 0 0 0 3 2.122v-1.596l-.495.18a.75.75 0 0 1-1.005-.706v-6.432c0-.527.33-.997.825-1.176zm5.005 3.705a.75.75 0 0 0-1.005-.705l-7.5 2.714a.75.75 0 0 0-.495.705v6.783a.75.75 0 0 0 1.005.705l.515-.186c.052.494.2.98.455 1.43l-.46.167A2.25 2.25 0 0 1 10 19.749v-6.783a2.25 2.25 0 0 1 1.484-2.116l7.5-2.714A2.25 2.25 0 0 1 22 10.252V14.5c0-1.19-.593-2.24-1.5-2.873zM21 14.5a2.5 2.5 0 1 1-5 0a2.5 2.5 0 0 1 5 0m2 5.375C23 21.431 21.714 23 18.5 23S14 21.437 14 19.875v-.103c0-.98.794-1.772 1.773-1.772h5.454c.98 0 1.773.793 1.773 1.772z" />
                                                        </svg>
                                                        {tr}Administrator sign-in required
                                                        {/tr}
                                                    </h5>
                                                    <p class="grp-head-desc">
                                                        {tr}An admin account was detected.
                                                        Enter the admin password to continue with Install or Upgrade.
                                                        {/tr}</p>
                                                {/if}
                                            </div>
                                            <div class="grp-body">
                                                <div class="install-upgrade">
                                                    {if $logged eq 'y'}
                                                        <form method="post" action="tiki-install.php" class="db-choice">
                                                            <input type="hidden" name="install_step" value="5">
                                                            {if $multi}<input type="hidden" name="multi" value="{$multi}">
                                                            {/if}
                                                            {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                                            {/if}

                                                            <div class="install-upgrade-layout">
                                                                <ul class="tki-choice-list tki-choice-list--grid install-upgrade-cards"
                                                                    role="radiogroup">
                                                                    {if $tikidb_created}
                                                                        <li class="entry-card db-choice-upgrade">
                                                                            <div class="form-check">
                                                                                <input class="form-check-input" type="radio"
                                                                                    name="db_setup_choice" id="db-choice-upgrade"
                                                                                    value="upgrade" checked>
                                                                                <label class="form-check-label entry-card-inner"
                                                                                    for="db-choice-upgrade">
                                                                                    <div class="entry-card-header">
                                                                                        <span class="entry-card-title-group">
                                                                                            <span class="icon" aria-hidden="true">
                                                                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 16 16">
                                                                                                    <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                                                    <path fill="currentColor" d="M4 12.5V5.021A7.4 7.4 0 0 0 8 6a7.4 7.4 0 0 0 4-.979v1.002a5.5 5.5 0 0 1 1 .185V3.5C13 2.075 10.851 1 8 1S3 2.075 3 3.5v9c0 1.295 1.775 2.301 4.239 2.474a5.6 5.6 0 0 1-.694-1.087C4.956 13.628 4 12.979 4 12.5M8 2c2.441 0 4 .888 4 1.5S10.441 5 8 5s-4-.888-4-1.5S5.558 2 8 2m3.5 5a4.5 4.5 0 1 0 0 9a4.5 4.5 0 0 0 0-9m2.354 4.854a.5.5 0 0 1-.707 0l-1.146-1.146v2.793a.5.5 0 0 1-1 0v-2.793l-1.146 1.146a.5.5 0 0 1-.707-.707l2-2l.003-.003A.5.5 0 0 1 11.499 9h.006a.5.5 0 0 1 .35.147l2 2a.5.5 0 0 1-.001.707"/>
                                                                                                </svg>
                                                                                            </span>
                                                                                            {tr}Upgrade{/tr}
                                                                                        </span>
                                                                                    </div>
                                                                                    <div class="entry-card-body">
                                                                                        <span class="desc">
                                                                                            {tr}Keep your content, users and
                                                                                            settings. Update the schema to
                                                                                            {/tr}
                                                                                            {$tiki_version_name}.</span>
                                                                                    </div>
                                                                                </label>
                                                                            </div>
                                                                        </li>
                                                                    {/if}
                                                                    <li
                                                                        class="entry-card db-choice-install {if $tikidb_created} is-destructive{/if}">
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="radio"
                                                                                name="db_setup_choice" id="db-choice-install"
                                                                                value="install" {if !$tikidb_created} checked {/if}>
                                                                            <label class="form-check-label entry-card-inner"
                                                                                for="db-choice-install">
                                                                                <div class="entry-card-header">
                                                                                    <span class="entry-card-title-group">
                                                                                        <span class="icon" aria-hidden="true">
                                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 20 20">
                                                                                                <!-- Icon from Fluent UI System Icons by Microsoft Corporation - https://github.com/microsoft/fluentui-system-icons/blob/main/LICENSE -->
                                                                                                <path fill="currentColor" d="M4 5c0-1.007.875-1.755 1.9-2.223A10.2 10.2 0 0 1 10 2a10.2 10.2 0 0 1 4.1.777C15.125 3.245 16 3.993 16 5v4h-1V6.7q-.421.31-.9.525A10.2 10.2 0 0 1 10 8a10.2 10.2 0 0 1-4.1-.777A5 5 0 0 1 5 6.7V15c0 .374.356.875 1.318 1.313A9.2 9.2 0 0 0 10 17q.748.015 1.486-.09q.24.088.516.09h.469l-.197.787c-.746.156-1.508.228-2.274.213a10.2 10.2 0 0 1-4.1-.777C4.875 16.755 4 16.007 4 15zm1 0c0 .374.356.875 1.318 1.313A9.2 9.2 0 0 0 10 7a9.2 9.2 0 0 0 3.682-.687C14.644 5.875 15 5.373 15 5c0-.374-.356-.875-1.318-1.313A9.2 9.2 0 0 0 10 3a9.2 9.2 0 0 0-3.682.687C5.356 4.125 5 4.626 5 5m8.112 11a.5.5 0 0 1 .485.621l-.434 1.738c-.121.483.462.827.826.487l4.872-4.548A.75.75 0 0 0 18.35 13h-.404a.5.5 0 0 1-.474-.658l.561-1.684A.5.5 0 0 0 17.56 10h-3.473a.5.5 0 0 0-.462.308l-2.083 5a.5.5 0 0 0 .461.692z"/>
                                                                                            </svg>
                                                                                        </span>
                                                                                        {if $tikidb_created}
                                                                                            {tr}Reinstall{/tr}
                                                                                        {else}
                                                                                            {tr}Install{/tr}
                                                                                        {/if}
                                                                                    </span>
                                                                                </div>
                                                                                <div class="entry-card-body">
                                                                                    {if $tikidb_created}
                                                                                        <span class="desc">
                                                                                            {tr}Wipe database{/tr}
                                                                                            <code
                                                                                                class="code_label">{$dbname|escape}</code>
                                                                                            {tr}and create a fresh Tiki install.
                                                                                            Existing data will be lost.{/tr}</span>
                                                                                    {else}
                                                                                        <span class="desc">
                                                                                            {tr}Create a brand-new Tiki database
                                                                                            from scratch. Use this for a
                                                                                            first-time
                                                                                            installation.
                                                                                            {/tr}</span>
                                                                                    {/if}
                                                                                </div>
                                                                            </label>
                                                                        </div>
                                                                    </li>
                                                                </ul>

                                                                <div class="db-choice-detail" aria-live="polite">
                                                                    {if $tikidb_created}
                                                                        <div class="db-choice-detail-panel" data-choice-panel="upgrade">
                                                                            <div class="db-choice-detail-row">
                                                                                <div class="db-choice-detail-copy">
                                                                                    <span class="db-choice-detail-kicker">
                                                                                        {tr}Confirm{/tr}
                                                                                    </span>
                                                                                    <p class="db-choice-detail-desc">
                                                                                        {tr _0=$tiki_version_name}Ready to
                                                                                        apply the schema update to %0? Your pages, users
                                                                                        and
                                                                                        files will stay in place.{/tr}
                                                                                    </p>
                                                                                </div>
                                                                                <div class="db-choice-detail-foot">
                                                                                    <button type="submit" name="update"
                                                                                        value="{if $unsupported_mail_queue_entries}{tr}Delete and Upgrade{/tr}{else}{tr}Upgrade{/tr}{/if}"
                                                                                        id="update-button"
                                                                                        class="tiki-btn tki-btn-compact-action db-choice-cta"
                                                                                        {if $unsupported_mail_queue_entries}
                                                                                        disabled="disabled" {/if}>

                                                                                        {if $unsupported_mail_queue_entries}
                                                                                            {tr}Delete and Upgrade{/tr}
                                                                                        {else}
                                                                                            {tr}Upgrade{/tr}
                                                                                        {/if}
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                            {if $unsupported_mail_queue_entries}
                                                                                <div class="db-choice-detail-extra">
                                                                                    <p class="db-choice-note">

                                                                                        {tr _0=$unsupported_mail_queue_entries}%0
                                                                                        unsupported
                                                                                        queued mail messages will be removed
                                                                                        during the
                                                                                        upgrade.
                                                                                        {/tr}</p>
                                                                                    <div class="form-check db-choice-ack">
                                                                                        <input class="form-check-input" type="checkbox"
                                                                                            id="unsupported-mail-queue-ack"
                                                                                            name="unsupported_mail_queue_ack" value="1">
                                                                                        <label class="form-check-label"
                                                                                            for="unsupported-mail-queue-ack">
                                                                                            {tr}I understand
                                                                                            these queued messages will be
                                                                                            permanently
                                                                                            deleted.
                                                                                            {/tr}</label>
                                                                                    </div>
                                                                                </div>
                                                                            {/if}
                                                                        </div>

                                                                    {/if}
                                                                    <div class="db-choice-detail-panel{if $tikidb_created} is-destructive{/if}"
                                                                        data-choice-panel="install">
                                                                        <div class="db-choice-detail-row">
                                                                            <div class="db-choice-detail-copy">
                                                                                {if $tikidb_created}
                                                                                    <span class="db-choice-detail-kicker">
                                                                                        {tr}Warning{/tr}
                                                                                    </span>
                                                                                    <p class="db-choice-detail-desc">
                                                                                        {tr}<strong>Destructive
                                                                                            action.</strong>
                                                                                        Reinstalling will
                                                                                        <strong>permanently erase all
                                                                                            data</strong>
                                                                                        in{/tr}
                                                                                        <code class="code_label">{$dbname|escape}</code>
                                                                                        {tr}(pages, users, files,
                                                                                        trackers everything).
                                                                                        This cannot be undone. Take a full
                                                                                        backup before
                                                                                        you
                                                                                        continue.{/tr}
                                                                                    </p>
                                                                                {else}
                                                                                    <span class="db-choice-detail-kicker">
                                                                                        {tr}Confirm{/tr}
                                                                                    </span>
                                                                                    <p class="db-choice-detail-desc">
                                                                                        {tr}Review the storage engine below,
                                                                                        then
                                                                                        confirm to
                                                                                        create your new Tiki database.{/tr}
                                                                                    </p>
                                                                                {/if}
                                                                            </div>
                                                                        </div>

                                                                        <div class="db-choice-detail-extra">
                                                                            {if $hasInnoDB}
                                                                                <div class="db-choice-field">
                                                                                    <label class="db-choice-label" for="dbEnginge">
                                                                                        {tr}Database engine{/tr}
                                                                                    </label>
                                                                                    <div class="db-choice-field-control">
                                                                                        <select name="useInnoDB" id="dbEnginge"
                                                                                            class="form-select">
                                                                                            <option value="0">
                                                                                                {tr}MyISAM{/tr}</option>
                                                                                            <option selected="selected" value="1">
                                                                                                {tr}InnoDB{/tr}</option>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                            {else}
                                                                                <input type="hidden" name="useInnoDB" value="0">
                                                                            {/if}
                                                                            <div class="db-choice-detail-foot">
                                                                                <button type="submit" name="scratch"
                                                                                    value="{if $tikidb_created}{tr}Reinstall{/tr}{else}{tr}Install{/tr}{/if}"
                                                                                    class="tiki-btn tki-btn-compact-action db-choice-cta">
                                                                                    {if $tikidb_created}
                                                                                        {tr}Reinstall{/tr}
                                                                                    {else}
                                                                                        {tr}Install{/tr}
                                                                                    {/if}
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            {if $unsupported_mail_queue_entries}
                                                                <script type="text/javascript">
                                                                    {literal}
                                                                        document.addEventListener('DOMContentLoaded',
                                                                            function() {
                                                                                var checkbox = document.getElementById(
                                                                                    'unsupported-mail-queue-ack');
                                                                                var button = document.getElementById(
                                                                                    'update-button');
                                                                                if (checkbox && button) {
                                                                                    checkbox.addEventListener('change',
                                                                                        function() {
                                                                                            button.disabled = !checkbox
                                                                                                .checked;
                                                                                        });
                                                                                }
                                                                            });

                                                                    {/literal}
                                                                </script>
                                                            {/if}
                                                        </form>
                                                        {* modal login *}
                                                    {else}
                                                        <div class="tki-modal-container">
                                                            <div class="tki-login">
                                                                <div class="tki-login-header">
                                                                    <span class="tki-login-badge">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                            height="16" fill="currentColor"
                                                                            class="bi bi-person-fill-lock" viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0m-9 8c0 1 1 1 1 1h5v-1a2 2 0 0 1 .01-.2 4.49 4.49 0 0 1 1.534-3.693Q8.844 9.002 8 9c-5 0-6 3-6 4m7 0a1 1 0 0 1 1-1v-1a2 2 0 1 1 4 0v1a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1zm3-3a1 1 0 0 0-1 1v1h2v-1a1 1 0 0 0-1-1" />
                                                                        </svg>
                                                                    </span>

                                                                    <h2 class="tki-login-title">
                                                                        {tr}Administrator sign-in
                                                                        required
                                                                        {/tr}</h2>
                                                                    <p class="tki-login-lead">
                                                                        {tr}Enter the password for{/tr}
                                                                        <code>admin</code>
                                                                        {tr}to continue with Install or Upgrade{/tr}{if $dbname}
                                                                            {tr}on{/tr}
                                                                            <code>{$dbname|escape}</code>
                                                                        {/if}.
                                                                    </p>
                                                                </div>

                                                                {* ---- Sign-in form ---- *}
                                                                <div class="tki-login-body">
                                                                    <form name="loginbox" action="tiki-install.php" method="post"
                                                                        class="tki-login-form">
                                                                        <input type="hidden" name="login" value="admin">

                                                                        {if $multi}<input type="hidden" name="multi"
                                                                                value="{$multi}">
                                                                        {/if}

                                                                        {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                                                        {/if}
                                                                        <div class="tki-login-field">
                                                                            <label class="tki-login-label" for="install-admin-pass">
                                                                                {tr}Admin password
                                                                                {/tr}</label>
                                                                            <div class="input-group flex-nowrap tki-login-password">
                                                                                <span class="input-group-text"
                                                                                    id="install-admin-pass-addon">
                                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                                        width="16" height="16" fill="currentColor"
                                                                                        class="bi bi-lock-fill" viewBox="0 0 16 16"
                                                                                        aria-hidden="true">
                                                                                        <path
                                                                                            d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2" />
                                                                                    </svg>
                                                                                </span>
                                                                                <input id="install-admin-pass"
                                                                                    class="form-control h-100" type="password"
                                                                                    name="pass" autocomplete="current-password"
                                                                                    placeholder="{tr}Enter the admin password{/tr}"
                                                                                    aria-describedby="install-admin-pass-addon"
                                                                                    autofocus required>
                                                                                <button type="button" class="btn tki-login-reveal"
                                                                                    data-target="install-admin-pass"
                                                                                    data-label-show="{tr}Show password{/tr}"
                                                                                    data-label-hide="{tr}Hide password{/tr}"
                                                                                    aria-label="{tr}Show password{/tr}">
                                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                                        width="16" height="16" fill="currentColor"
                                                                                        class="bi bi-eye-fill icon-show"
                                                                                        viewBox="0 0 16 16">
                                                                                        <path
                                                                                            d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0" />
                                                                                        <path
                                                                                            d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7" />
                                                                                    </svg>
                                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                                        width="16" height="16" fill="currentColor"
                                                                                        class="bi bi-eye-slash-fill icon-hide"
                                                                                        viewBox="0 0 16 16">
                                                                                        <path
                                                                                            d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7 7 0 0 0 2.79-.588M5.21 3.088A7 7 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474z" />
                                                                                        <path
                                                                                            d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829zm4.95.708-2.83-2.829a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12z" />
                                                                                    </svg>
                                                                                </button>
                                                                            </div>
                                                                        </div>

                                                                        <div class="tki-login-actions">
                                                                            <button type="submit" form="tki-login-cancel"
                                                                                name="install_step" value="3"
                                                                                class="tiki-btn tiki-btn-tertiary">
                                                                                {tr}Cancel
                                                                                {/tr}</button>
                                                                            <button type="submit" name="login" value="1"
                                                                                class="tiki-btn tiki-btn-primary tki-login-submit">
                                                                                {tr}Continue
                                                                                {/tr}
                                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                                    height="16" fill="currentColor"
                                                                                    class="bi bi-arrow-right-short"
                                                                                    viewBox="0 0 16 16">
                                                                                    <path fill-rule="evenodd"
                                                                                        d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8" />
                                                                                </svg>
                                                                            </button>
                                                                        </div>
                                                                    </form>

                                                                    {* Cancel returns to the previous step (system requirements). *}
                                                                    <form id="tki-login-cancel" action="tiki-install.php"
                                                                        method="post" class="tki-login-cancel">

                                                                        {if $multi}<input type="hidden" name="multi"
                                                                                value="{$multi}">
                                                                        {/if}

                                                                        {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                                                        {/if}
                                                                    </form>
                                                                </div>{* /tki-login-body *}
                                                            </div>{* /tki-login *}
                                                        </div>{* /tki-modal-container *}

                                                        <script type="text/javascript">
                                                            {literal}
                                                                (function() {
                                                                    var toggles = document.querySelectorAll(
                                                                        '.tki-login-reveal');
                                                                    for (var i = 0; i < toggles.length; i++) {
                                                                        toggles[i].addEventListener('click',
                                                                            function() {
                                                                                var input = document.getElementById(
                                                                                    this.getAttribute(
                                                                                        'data-target'));
                                                                                if (!input) { return; }
                                                                                var reveal = input.type ===
                                                                                    'password';
                                                                                input.type = reveal ? 'text' :
                                                                                    'password';
                                                                                this.classList.toggle('is-revealed',
                                                                                    reveal);
                                                                                var label = reveal ? this
                                                                                    .getAttribute(
                                                                                        'data-label-hide') :
                                                                                    this.getAttribute(
                                                                                        'data-label-show');
                                                                                if (label) {
                                                                                    this.setAttribute('aria-label',
                                                                                        label);
                                                                                }
                                                                            });
                                                                    }
                                                                })();

                                                            {/literal}
                                                        </script>
                                                    {/if}
                                                </div>{* /install-upgrade *}
                                            </div>{* /grp-body *}
                                        </div>{* /step-group-card *}
                                    {* {/if} *}
                                </div>{* /installer-step-body *}
                            </div>{* /installer-step-content *}

                            {* Step 4 Footer *}
                            <div class="installer-step-footer">
                                <form action="tiki-install.php" method="post">

                                    {if $multi}<input type="hidden" name="multi" value="{$multi}">
                                    {/if}

                                    {if $lang}<input type="hidden" name="lang" value="{$lang}">
                                    {/if}
                                    <button type="submit" name="install_step" value="3" class="tiki-btn tiki-btn-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="bi bi-chevron-compact-left" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M9.224 1.553a.5.5 0 0 1 .223.67L6.56 8l2.888 5.776a.5.5 0 1 1-.894.448l-3-6a.5.5 0 0 1 0-.448l3-6a.5.5 0 0 1 .67-.223" />
                                        </svg>

                                        {tr}Previous
                                        {/tr}</button>
                                </form>

                                <button class="tiki-btn tiki-btn-primary disabled">

                                    {tr}Next
                                    {/tr}
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd"
                                            d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                                    </svg>
                                </button>
                            </div>{* End of installer-step-footer *}
                        </div>{* End of install-step4 *}

                    {elseif $install_step eq '5' or ($dbdone ne 'n')}
                        <div class="install-step5 installer-step">
                            <div class="installer-step-content">
                                <div class="installer-step-header">
                                    <h2 class="installer-step-title tki-type-page-title">
                                        {if isset($smarty.post.update)}
                                            {tr}Review the Upgrade{/tr}
                                        {else}
                                            {tr}Review the Installation{/tr}
                                        {/if}
                                    </h2>
                                    <span class="installer-step-desc tki-type-body">
                                        {if isset($smarty.post.update)}
                                            {tr}Review the database result of this upgrade. Continue when the operations look
                                            correct.{/tr}
                                        {else}
                                            {tr}Review the database result of this installation. Continue when the operations
                                            look
                                            correct.{/tr}
                                        {/if}
                                    </span>
                                </div>

                                <div class="installer-step-body">
                                    {if $unsupported_mail_queue_cleaned}
                                        <div class="toast-cad toast-info">
                                            <div class="toast-card-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                                    <path
                                                        d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                    <path
                                                        d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                </svg>
                                            </div>
                                            <div class="toast-card-body">
                                                <span>
                                                    {tr _0=$unsupported_mail_queue_cleaned}%0 unsupported queued email entries
                                                    have
                                                    been successfully removed during upgrade.{/tr}
                                                </span>
                                            </div>
                                        </div>
                                    {/if}

                                    <form id="installStep5Form" action="tiki-install.php" method="post" class="form-step-5">
                                        {* Section | complete *}
                                        {* <div class="step-group-card">
                                        <div class="grp-head">
                                            <h5 class="grp-head-title">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                    viewBox="0 0 20 20">
                                                    <path fill="currentColor"
                                                        d="M10 2a8 8 0 1 1 0 16a8 8 0 0 1 0-16m3.358 5.646a.5.5 0 0 0-.707.008l-3.172 3.29l-1.146-1.147a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .716-.001l3.525-3.658a.5.5 0 0 0-.008-.7" />
                                                </svg>
                                                {if isset($smarty.post.update)}
                                                    {tr}Upgrade complete{/tr}
                                                {else}
                                                    {tr}Installation complete{/tr}
                                                {/if}
                                            </h5>
                                            <p class="grp-head-desc">
                                                {tr}Your database has been configured and Tiki is ready to run.{/tr}
                                            </p>
                                        </div>
                                        <div class="grp-body"> *}
                                        <div class="toast-cad simple-toast w-100">
                                            <div class="toast-card-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                                    <path
                                                        d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                    <path
                                                        d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                </svg>
                                            </div>
                                            <div class="toast-card-body">
                                                <span>
                                                    {tr}
                                                    Your database has been configured and Tiki is ready to run. You can now
                                                    log
                                                    in into {/tr}<br>
                                                    {tr}
                                                    Tiki as user <strong>admin</strong> and
                                                    start configuring the application.{/tr}
                                                    {if isset($smarty.post.scratch, $defaultpass)}
                                                        {tr _0="<strong>{$defaultpass}</strong>"}A temporary default
                                                        password, %0, has been created for the admin account. Please replace
                                                        it with a password of your choice.{/tr}
                                                    {/if}
                                                </span>
                                            </div>
                                        </div>
                                        {* </div>
                                    </div> *}

                                        {* Section | database operations *}
                                        <div class="step-group-card">
                                            <div class="grp-head">
                                                <h5 class="grp-head-title">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                        viewBox="0 0 20 20">
                                                        <path fill="currentColor"
                                                            d="M4 5c0-1.007.875-1.755 1.9-2.223A10.2 10.2 0 0 1 10 2a10.2 10.2 0 0 1 4.1.777C15.125 3.245 16 3.993 16 5v5.109a3 3 0 0 0-1 .258V6.7q-.421.31-.9.525A10.2 10.2 0 0 1 10 8a10.2 10.2 0 0 1-4.1-.777A5 5 0 0 1 5 6.7V15c0 .374.356.875 1.318 1.313A9.2 9.2 0 0 0 10 17h.021q.018.081.039.161l-.76.764q-.03.03-.058.061a10.2 10.2 0 0 1-3.342-.763C4.875 16.755 4 16.007 4 15zm1 0c0 .374.356.875 1.318 1.313A9.2 9.2 0 0 0 10 7a9.2 9.2 0 0 0 3.682-.687C14.644 5.875 15 5.373 15 5c0-.374-.356-.875-1.318-1.313A9.2 9.2 0 0 0 10 3a9.2 9.2 0 0 0-3.682.687C5.356 4.125 5 4.626 5 5m14.198 5.854a.5.5 0 1 0-.707-.708l-1.186 1.186l-.035.053a2 2 0 0 0-2.436.3l-.6.6a.98.98 0 0 0 0 1.386l1.442 1.439a.98.98 0 0 0 1.387 0l.6-.6a2 2 0 0 0 .3-2.437a.4.4 0 0 0 .053-.035zm-5.671 3.521a.98.98 0 0 0-1.386 0l-.6.6a2 2 0 0 0-.3 2.437a.4.4 0 0 0-.053.035l-1.182 1.185a.5.5 0 0 0 .707.707l1.185-1.186l.036-.053a2 2 0 0 0 2.436-.3l.6-.6a.98.98 0 0 0 0-1.387z" />
                                                    </svg>
                                                    {tr}Database operations{/tr}
                                                </h5>
                                                <p class="grp-head-desc">
                                                    {tr}SQL queries that ran while the database was prepared.{/tr}
                                                </p>
                                            </div>
                                            <div class="grp-body">
                                                {if $installer->queries.successful|@count gt 0}
                                                    <div class="line-card">
                                                        <div class="line-card-header">
                                                            <div class="line-card-icon-wrapper">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                    fill="currentColor" class="bi bi-ui-checks"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M7 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5zM2 1a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm0 8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2zm.854-3.646a.5.5 0 0 1-.708 0l-1-1a.5.5 0 1 1 .708-.708l.646.647 1.646-1.647a.5.5 0 1 1 .708.708zm0 8a.5.5 0 0 1-.708 0l-1-1a.5.5 0 0 1 .708-.708l.646.647 1.646-1.647a.5.5 0 0 1 .708.708zM7 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5zm0-5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 8a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5" />
                                                                </svg>
                                                            </div>
                                                            <div class="line-card-header-text">
                                                                <span class="line-card-title">
                                                                    {if isset($smarty.post.update)}
                                                                        {tr}Upgrade operations executed
                                                                        successfully{/tr}
                                                                    {else}
                                                                        {tr}Installation operations executed{/tr}
                                                                    {/if}
                                                                </span>
                                                                <span class="line-card-subtitle">
                                                                    {$installer->queries.successful|@count} {tr}SQL queries
                                                                    executed{/tr}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="line-card-content">
                                                            <span class="line-card-description">
                                                                {if isset($smarty.post.update)}
                                                                    {tr}All required database upgrade operations were completed
                                                                    successfully. No further action is required for these
                                                                    queries.{/tr}
                                                                {else}
                                                                    {tr}All required installation operations were completed
                                                                    successfully. No further action is required for these
                                                                    queries.{/tr}
                                                                {/if}
                                                            </span>
                                                        </div>
                                                    </div>
                                                {else}
                                                    <div class="line-card line-card-warning">
                                                        <div class="line-card-header">
                                                            <div class="line-card-icon-wrapper">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                    fill="currentColor" class="bi bi-ui-checks"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M7 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5zM2 1a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm0 8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2zm.854-3.646a.5.5 0 0 1-.708 0l-1-1a.5.5 0 1 1 .708-.708l.646.647 1.646-1.647a.5.5 0 1 1 .708.708zm0 8a.5.5 0 0 1-.708 0l-1-1a.5.5 0 0 1 .708-.708l.646.647 1.646-1.647a.5.5 0 0 1 .708.708zM7 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5zm0-5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 8a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5" />
                                                                </svg>
                                                            </div>
                                                            <div class="line-card-header-text">
                                                                <span class="line-card-title">{tr}Database was left
                                                                    unchanged.{/tr}</span>
                                                                <span class="line-card-subtitle">{tr}No SQL queries were
                                                                    executed{/tr}</span>
                                                            </div>
                                                        </div>
                                                        <div class="line-card-content">
                                                            <span class="line-card-description">
                                                                {tr}The database schema was already in the expected state, so no
                                                                further queries were applied.{/tr}
                                                            </span>
                                                        </div>
                                                    </div>
                                                {/if}
                                            </div>
                                        </div>

                                        {if $installer->queries.failed|@count > 0}
                                            {* Section | failed patches *}
                                            <div class="step-group-card">
                                                <div class="grp-head">
                                                    <h5 class="grp-head-title">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 20 20">
                                                            <path fill="currentColor"
                                                                d="M8.46 2.607a1.75 1.75 0 0 1 3.08 0l6.857 12.242A1.75 1.75 0 0 1 16.857 17.5H3.143a1.75 1.75 0 0 1-1.54-2.651zM10 6.25a.75.75 0 0 0-.75.75v4.25a.75.75 0 0 0 1.5 0V7a.75.75 0 0 0-.75-.75m0 8.5a.875.875 0 1 0 0-1.75a.875.875 0 0 0 0 1.75" />
                                                        </svg>
                                                        {tr}Review database updates{/tr}
                                                    </h5>
                                                    <p class="grp-head-desc">
                                                        {$installer->queries.failed|@count} {tr}SQL patches require
                                                        attention.{/tr}
                                                    </p>
                                                </div>
                                                <div class="grp-body">
                                                    <div class="toast-cad toast-warning w-100">
                                                        <div class="toast-card-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                fill="currentColor" class="bi bi-chat-square-quote"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                                <path
                                                                    d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                            </svg>
                                                        </div>
                                                        <div class="toast-card-body">
                                                            <span>
                                                                {tr}During an upgrade, it is normal to have SQL failures
                                                                resulting
                                                                with <strong>Table already exists</strong> messages.{/tr}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <p class="grp-text mb-0">
                                                        {tr}If you think that the errors of a patch can be ignored, select the
                                                        corresponding patches individually or click{/tr}
                                                        <a id="check_sql_all_link" href="#"
                                                            data-check="{tr}Check all errors{/tr}"
                                                            data-uncheck="{tr}Uncheck all errors{/tr}"
                                                            onclick="tkiCheckAllSqlErrors();return false;">{tr}Check all
                                                            errors{/tr}</a>
                                                        {tr}before continuing.{/tr}
                                                    </p>
                                                    <div id="sql_failed_log">
                                                        <div class="sql-failed-list">
                                                            {$patch=''}
                                                            {foreach from=$installer->queries.failed item=item}
                                                                {if $patch ne $item[2]}
                                                                    {if $patch ne ''}
                                                                        </textarea>
                                                                    </div>
                                                                </div>
                                                            {/if}
                                                            <div class="sql-failed-item">
                                                                <div class="sql-failed-content">
                                                                    <div class="sql-failed-header">
                                                                        <input type="checkbox" name="validPatches[]"
                                                                            value="{$item[2]|escape}" id="ignore_{$item[2]|escape}">
                                                                        <label for="ignore_{$item[2]|escape}">{$item[2]|escape}</label>
                                                                    </div>
                                                                    {$patch=$item[2]}
                                                                    <textarea rows="5" readonly>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                {/if}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    {$item[0]}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    {$item[1]}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                {/foreach}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                {if $patch ne ''}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </textarea>
                                                            </div>
                                                        </div>
                                                    {/if}
                                                </div>
                                            </div>
                                            <script type="text/javascript">
                                                {literal}
                                                    function tkiCheckAllSqlErrors() {
                                                        const checkboxes = document.querySelectorAll(
                                                            '#installStep5Form input[name="validPatches[]"]');
                                                        const link = document.getElementById('check_sql_all_link');
                                                        const allChecked = Array.from(checkboxes).every((checkbox) => checkbox
                                                            .checked);
                                                        checkboxes.forEach((checkbox) => {
                                                            checkbox.checked = !allChecked;
                                                        });
                                                        if (link) {
                                                            link.textContent = allChecked ? link.dataset.check : link.dataset
                                                                .uncheck;
                                                        }
                                                    }
                                                {/literal}
                                            </script>
                                    </div>
                                </div>
                            {/if}

                            <input type="hidden" name="install_step" value="6">
                            <input type="hidden" name="install_type" value="{$install_type|escape}">
                            {if isset($smarty.post.scratch)}
                                {$default_password_field}
                            {/if}
                            {if $multi}<input type="hidden" name="multi" value="{$multi}">{/if}
                            {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                            </form>
                        </div>{* End of installer-step-body *}
                </div>{* End of installer-step-content *}

                <div class="installer-step-footer">
                    <form action="tiki-install.php" method="post">
                        {if $multi}<input type="hidden" name="multi" value="{$multi}">
                        {/if}
                        {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                        <button type="submit" name="install_step" value="4" class="tiki-btn tiki-btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-chevron-compact-left" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M9.224 1.553a.5.5 0 0 1 .223.67L6.56 8l2.888 5.776a.5.5 0 1 1-.894.448l-3-6a.5.5 0 0 1 0-.448l3-6a.5.5 0 0 1 .67-.223" />
                            </svg>
                            {tr}Previous{/tr}</button>
                    </form>
                    <button type="submit" form="installStep5Form" class="tiki-btn tiki-btn-primary">

                        {tr}Next
                        {/tr}
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd"
                                d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                        </svg>
                    </button>
                </div>{* End of installer-step-footer *}
            </div>{* End of install-step5 *}

        {elseif $install_step eq '6'}
            <div class="install-step6 installer-step">
                <div class="installer-step-content">

                    {* Content header *}
                    <div class="installer-step-header">
                        <h2 class="installer-step-title tki-type-page-title">
                            {tr}Configure General Settings{/tr}
                        </h2>
                        <span class="installer-step-desc tki-type-body">
                            {tr}Complete these fields to configure common, general settings for your
                            site.{/tr}<br>{tr}The information you enter here can be changed later.{/tr}
                        </span>
                    </div>
                    {* Content Body *}
                    <div class="installer-step-body">
                        <div class="toast-cad toast-info">
                            <div class="toast-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                    <path
                                        d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                    <path
                                        d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                </svg>
                            </div>
                            <div class="toast-card-body">
                                <span>
                                    {tr}Refer to the <a href="https://doc.tiki.org/Admin-home" target="_blank"
                                        class="alert-link">documentation</a> for
                                    complete
                                    information on these, and
                                    other,
                                    settings.
                                    {/tr}</span>
                            </div>
                        </div>

                        <form id="installStep6Form" action="tiki-install.php" method="post">
                            <div class="clearfix">
                                <div class="accordion" id="accordionGeneralSettings">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#collapseGeneral" aria-expanded="true"
                                                aria-controls="collapseGeneral">
                                                <div class="accordion-button-content">
                                                    <span class="accordion-button-title">
                                                        {tr}General
                                                        {/tr} <a href="https://doc.tiki.org/general-admin" target="_blank"
                                                            title="
                                                    {tr}Help
                                                    {/tr}">

                                                            {* 
                                            {icon name="help"} *}
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                                                fill="currentColor" class="bi bi-question-square-fill"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927" />
                                                            </svg>
                                                        </a></span>
                                                    <span class="accordion-button-desc">
                                                        {tr}Basic site
                                                        settings
                                                        such
                                                        as the server domain name, browser title and
                                                        sender
                                                        email.
                                                        {/tr}</span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapseGeneral" class="accordion-collapse collapse show"
                                            data-bs-parent="#accordionGeneralSettings">
                                            <div class="accordion-body">
                                                <div class="form-group">
                                                    <label class="col-form-label" for="server_domain">

                                                        {tr}Server domain name
                                                        {/tr}
                                                    </label>
                                                    <div>
                                                        <input class="form-control" type="text" name="server_domain"
                                                            id="server_domain"
                                                            value="{if !empty($prefs.server_domain)}{$prefs.server_domain|escape}{else}{$default_server_domain_name|escape}{/if}">
                                                        <span class="form-text">

                                                            {tr}The value that will be used by
                                                            default as
                                                            the
                                                            server
                                                            domain name of your
                                                            Tiki site.
                                                            {/tr}<br>
                                                        </span>
                                                    </div>
                                                    <div class="notice-summary-card notice-summary-card--danger mt-2">
                                                        <div class="notice-summary-card__header">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                fill="currentColor" class="bi bi-exclamation-diamond"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M6.95.435c.58-.58 1.52-.58 2.1 0l6.515 6.516c.58.58.58 1.519 0 2.098L9.05 15.565c-.58.58-1.519.58-2.098 0L.435 9.05a1.48 1.48 0 0 1 0-2.098zm1.4.7a.495.495 0 0 0-.7 0L1.134 7.65a.495.495 0 0 0 0 .7l6.516 6.516a.495.495 0 0 0 .7 0l6.516-6.516a.495.495 0 0 0 0-.7L8.35 1.134z" />
                                                                <path
                                                                    d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z" />
                                                            </svg>
                                                            <span class="notice-summary-card__title">{tr}Warning{/tr}</span>
                                                        </div>
                                                        <p class="notice-summary-card__body">
                                                            {tr}Leave this blank if you are not entirely sure what you are
                                                            doing.{/tr}
                                                            {tr}If this value is incorrect or your DNS is not set up
                                                            properly it
                                                            will lock you out of your Tiki and require database or shell
                                                            access
                                                            to recover it.{/tr}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="combine-form">
                                                    <div class="form-group">
                                                        <label class="col-form-label" for="browsertitle">

                                                            {tr}Browser title
                                                            {/tr}
                                                        </label>
                                                        <div>
                                                            <input class="form-control" type="text" size="40"
                                                                name="browsertitle" id="browsertitle"
                                                                value="{if !empty($prefs.browsertitle)}{$prefs.browsertitle|escape}{else}{tr}My Tiki{/tr}{/if}">
                                                            <span class="form-text">

                                                                {tr}This will appear in the browser
                                                                title
                                                                bar.
                                                                {/tr}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-form-label" for="sender_email">

                                                            {tr}Sender email
                                                            {/tr}
                                                        </label>
                                                        <div>
                                                            <input type="text" class="form-control" size="40"
                                                                name="sender_email" id="sender_email"
                                                                value="{$prefs.sender_email|escape}"
                                                                placeholder="{tr}tiki@example.com{/tr}">
                                                            <span class="form-text">

                                                                {tr}Email sent by your site will use
                                                                this
                                                                address.
                                                                {/tr}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="proxy-option">
                                                    <div class="proxy-option-row">
                                                        <label class="proxy-option-label" for="networkProxySwitch">
                                                            {tr}Use a proxy server for outbound internet connections.
                                                            Configure
                                                            the settings below.{/tr}
                                                        </label>
                                                        <span class="form-check form-switch proxy-option-switch">
                                                            <input class="form-check-input proxy-option-toggle"
                                                                type="checkbox" role="switch" id="networkProxySwitch"
                                                                onchange="toggleProxySettings()">
                                                        </span>
                                                    </div>

                                                    <div id="proxySettings" style="display:none;" class="proxy-settings">

                                                        <div class="form-check square-check">
                                                            <input class="form-check-input" type="checkbox" name="use_proxy"
                                                                id="use_proxy">
                                                            <label class="form-check-label" for="use_proxy">

                                                                {tr}Use proxy
                                                                {/tr}
                                                            </label>
                                                        </div>

                                                        <div class="combine-form combine-form-split">
                                                            <div class="form-group">
                                                                <label for="proxy_host" class="form-label">
                                                                    {tr}Proxy
                                                                    host
                                                                    name
                                                                    {/tr}</label>
                                                                <input type="text" class="form-control" name="proxy_host"
                                                                    id="proxy_host">
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="proxy_port" class="form-label">
                                                                    {tr}Port
                                                                    {/tr}</label>
                                                                <input type="text" class="form-control" name="proxy_port"
                                                                    id="proxy_port">
                                                            </div>
                                                        </div>

                                                        <div class="combine-form">
                                                            <div class="form-group">
                                                                <label for="proxy_user" class="form-label">
                                                                    {tr}Proxy
                                                                    username
                                                                    {/tr}</label>
                                                                <input type="text" class="form-control" name="proxy_user"
                                                                    id="proxy_user">
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="proxy_pass" class="form-label">
                                                                    {tr}Proxy
                                                                    password
                                                                    {/tr}</label>
                                                                <input type="password" class="form-control"
                                                                    name="proxy_pass" id="proxy_pass">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <script type="text/javascript">
                                                        function toggleProxySettings() {
                                                            const section = document.getElementById(
                                                                'proxySettings');
                                                            const toggle = document.getElementById(
                                                                'networkProxySwitch');
                                                            section.style.display = toggle.checked ?
                                                                'flex' :
                                                                'none';
                                                        }
                                                    </script>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapseSecureLogin"
                                                aria-expanded="false" aria-controls="collapseSecureLogin">
                                                <div class="accordion-button-content">
                                                    <span class="accordion-button-title">
                                                        {tr}Secure Log
                                                        in
                                                        {/tr}
                                                        <a href="https://doc.tiki.org/login-config" target="_blank" title="
                                                    {tr}Help
                                                    {/tr}">
                                                            {* 
                                            {icon name="help"} *}
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                                                fill="currentColor" class="bi bi-question-square-fill"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927" />
                                                            </svg>
                                                        </a></span>
                                                    <span class="accordion-button-desc">
                                                        {tr}Configure HTTPS
                                                        (SSL)
                                                        login options and inline Smarty
                                                        execution.
                                                        {/tr}</span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapseSecureLogin" class="accordion-collapse collapse"
                                            data-bs-parent="#accordionGeneralSettings">
                                            <div class="accordion-body">
                                                <div class="toast-cad toast-info">
                                                    <div class="toast-card-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                            fill="currentColor" class="bi bi-chat-left-quote"
                                                            viewBox="0 0 16 16">
                                                            <path
                                                                d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                            <path
                                                                d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                        </svg>
                                                    </div>
                                                    <div class="toast-card-body">
                                                        <span>
                                                            {tr}It is recommended to choose the
                                                            "Require
                                                        secure
                                                        (HTTPS) login"
                                                            option for better
                                                            security. A security certificate and
                                                            dedicated
                                                            IP
                                                            address are
                                                            required to implement
                                                            a secure login.
                                                            {/tr}</span>
                                                    </div>
                                                </div>
                                                <div class="https-login-fields">
                                                    <div class="form-group">
                                                        <label class="form-label" for="https_login">
                                                            {tr}HTTPS login{/tr}
                                                        </label>
                                                        <select class="form-select" name="https_login" id="https_login"
                                                            onchange="hidedisabled('httpsoptions',this.value);">
                                                            <option value="disabled" {if $prefs.https_login eq 'disabled'}
                                                                selected="selected" {/if}>
                                                                {tr}Disabled{/tr}
                                                            </option>
                                                            <option value="allowed" {if $prefs.https_login eq 'allowed'}
                                                                selected="selected" {/if}>
                                                                {tr}Allow secure (HTTPS) login{/tr}
                                                            </option>
                                                            <option value="encouraged"
                                                                {if $prefs.https_login eq 'encouraged' or ($prefs.https_login eq '' and $detected_https eq 'on' )}
                                                                selected="selected" {/if}>
                                                                {tr}Encourage secure (HTTPS) login{/tr}
                                                            </option>
                                                            <option value="force_nocheck"
                                                                {if $prefs.https_login eq 'force_nocheck'}
                                                                selected="selected" {/if}>
                                                                {tr}Consider we are always in HTTPS, but do not check{/tr}
                                                            </option>
                                                            <option value="required" {if $prefs.https_login eq 'required'}
                                                                selected="selected" {/if}>
                                                                {tr}Require secure (HTTPS) login{/tr}
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div id="httpsoptions" class="form-stack"
                                                        style="display:{if $prefs.https_login eq 'disabled' or ( $prefs.https_login eq '' and $detected_https eq '')}none{else}block{/if};">
                                                        <div class="form-group form-group--narrow">
                                                            <label for="https_port" class="form-label">
                                                                {tr}HTTPS port{/tr}
                                                            </label>
                                                            <input type="text" class="form-control" name="https_port"
                                                                id="https_port" size="5" inputmode="numeric"
                                                                value="{$prefs.https_port|escape}">
                                                        </div>

                                                        <div class="card-table">
                                                            <span class="form-label">SSL Connection
                                                                Preferences</span>
                                                            <div class="card-table-body">
                                                                <div>
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            role="switch" id="feature_show_stay_in_ssl_mode"
                                                                            {if $prefs.feature_show_stay_in_ssl_mode eq 'y'}checked="checked"
                                                                            {/if}>
                                                                        <label class="form-check-label"
                                                                            for="feature_show_stay_in_ssl_mode">
                                                                            {tr}Users
                                                                            can
                                                                            choose
                                                                            to stay in SSL mode after an
                                                                            HTTPS
                                                                            login.
                                                                            {/tr}</label>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            role="switch" id="feature_switch_ssl_mode"
                                                                            {if $prefs.feature_switch_ssl_mode eq 'y'}checked="checked"
                                                                            {/if}>
                                                                        <label class="form-check-label"
                                                                            for="feature_switch_ssl_mode">
                                                                            {tr}Users
                                                                            can
                                                                            choose
                                                                            to stay in SSL mode after an
                                                                            HTTPS
                                                                            login.
                                                                            {/tr}</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-stack">
                                                    <div class="form-check square-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="smarty_enable_string_eval" name="smarty_enable_string_eval"
                                                            value="y"
                                                            {if $prefs.smarty_enable_string_eval eq 'y'}checked="checked"
                                                            {/if}>
                                                        <label class="form-check-label" for="smarty_enable_string_eval">

                                                            {tr}Enable inline Smarty string and
                                                            evaluation
                                                            execution.
                                                            {/tr}
                                                        </label>
                                                    </div>
                                                    <div class="toast-cad toast-danger">
                                                        <div class="toast-card-icon">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                fill="currentColor" class="bi bi-chat-square-quote"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                                <path
                                                                    d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                            </svg>
                                                        </div>
                                                        <div class="toast-card-body">
                                                            <span>
                                                                {tr}Allows execution of
                                                                inline Smarty
                                                                code in user modules. Only enable if
                                                                you
                                                                trust
                                                                admin
                                                                users.
                                                                {/tr}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapseLogging"
                                                aria-expanded="false" aria-controls="collapseLogging">
                                                <div class="accordion-button-content">
                                                    <span class="accordion-button-title">
                                                        {tr}Logging and
                                                        Reporting
                                                        {/tr} <a href="https://doc.tiki.org/Logs" target="_blank" title="
                                                        {tr}Help
                                                        {/tr}">
                                                            {* 
                                            {icon name="help"} *}
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                                                fill="currentColor" class="bi bi-question-square-fill"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927" />
                                                            </svg>
                                                        </a></span>
                                                    <span class="accordion-button-desc">
                                                        {tr}Set the PHP
                                                        error
                                                        reporting level and template logging
                                                        options.
                                                        {/tr}</span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapseLogging" class="accordion-collapse collapse"
                                            data-bs-parent="#accordionGeneralSettings">
                                            <div class="accordion-body">
                                                <div class="form-group">
                                                    <label class="form-label" for="general-error">
                                                        {tr}PHP
                                                        error
                                                        reporting
                                                        level
                                                        {/tr}</label>
                                                    <select class="form-select" name="error_reporting_level"
                                                        id="general-error">
                                                        <option value="0"
                                                            {if $prefs.error_reporting_level eq 0}selected="selected" {/if}>

                                                            {tr}No error reporting
                                                            {/tr}</option>
                                                        <option value="2047"
                                                            {if $prefs.error_reporting_level eq 2047}selected="selected"
                                                            {/if}>

                                                            {tr}Report all PHP errors except strict
                                                            {/tr}
                                                        </option>
                                                        <option value="-1"
                                                            {if $prefs.error_reporting_level eq -1}selected="selected"
                                                            {/if}>

                                                            {tr}Report all PHP errors
                                                            {/tr}</option>
                                                        <option value="2039"
                                                            {if $prefs.error_reporting_level eq 2039 or $prefs.error_reporting_level eq ''}selected="selected"
                                                            {/if}>
                                                            {tr}Report all errors except notices
                                                            {/tr}
                                                        </option>
                                                        <option value="1"
                                                            {if $prefs.error_reporting_level eq 1039}selected="selected"
                                                            {/if}>

                                                            {tr}According to the PHP configuration
                                                            {/tr}
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="card-table">
                                                    <span class="form-label">
                                                        {tr}Error Reporting
                                                        Settings
                                                        {/tr}</span>
                                                    <div class="card-table-body">
                                                        <div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="error_reporting_adminonly"
                                                                    name="error_reporting_adminonly"
                                                                    {if $prefs.error_reporting_adminonly eq 'y'}
                                                                    checked="checked" {/if}>
                                                                <label class="form-check-label"
                                                                    for="error_reporting_adminonly">
                                                                    {tr}Visible
                                                                    to
                                                                    Admin
                                                                    only
                                                                    {/tr}</label>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="smarty_notice_reporting"
                                                                    name="smarty_notice_reporting"
                                                                    {if $prefs.smarty_notice_reporting eq 'y'}
                                                                    checked="checked" {/if}>
                                                                <label class="form-check-label"
                                                                    for="smarty_notice_reporting">
                                                                    {tr}Include
                                                                    Smarty
                                                                    notices
                                                                    {/tr}</label>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" id="log_tpl"
                                                                    name="log_tpl" {if $prefs.log_tpl eq 'y'}
                                                                    checked="checked" {/if}>
                                                                <label class="form-check-label" for="log_tpl">
                                                                    {tr}Add
                                                                    HTML comment
                                                                    at start and end of each Smarty
                                                                    template
                                                                    (.tpl
                                                                    file)
                                                                    {/tr}</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="toast-cad toast-warning">
                                                    <div class="toast-card-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                            fill="currentColor" class="bi bi-exclamation-square"
                                                            viewBox="0 0 16 16">
                                                            <path
                                                                d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                            <path
                                                                d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z" />
                                                        </svg>
                                                    </div>
                                                    <div class="toast-card-body">
                                                        <span>
                                                            {tr}Use only for development, not in
                                                            production at
                                                            a
                                                            live site, because
                                                            these warnings are added to emails as
                                                            well, and
                                                            are
                                                            visible to users in
                                                            the page source.
                                                            {/tr}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapseAdministrator"
                                                aria-expanded="false" aria-controls="collapseAdministrator">
                                                <div class="accordion-button-content">
                                                    <span class="accordion-button-title">
                                                        {tr}Administrator
                                                        {/tr}
                                                        <a href="https://doc.tiki.org/Admins" target="_blank" title="
                                                        {tr}Help
                                                        {/tr}">
                                                            {* 
                                            {icon name="help"} *}
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                                                fill="currentColor" class="bi bi-question-square-fill"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927" />
                                                            </svg>
                                                        </a></span>
                                                    <span class="accordion-button-desc">
                                                        {tr}Email address
                                                        used
                                                        for
                                                        the administrator account.
                                                        {/tr}</span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapseAdministrator" class="accordion-collapse collapse"
                                            data-bs-parent="#accordionGeneralSettings">
                                            <div class="accordion-body">
                                                <div class="form-group">
                                                    <label class="col-form-label" for="admin_email">

                                                        {tr}Admin email
                                                        {/tr}
                                                    </label>
                                                    <div>
                                                        <input type="text" class="form-control" size="40" name="admin_email"
                                                            id="admin_email"
                                                            value="{if isset($admin_email)}{$admin_email|escape}{/if}"
                                                            placeholder="{tr}admin@example.com{/tr}">
                                                        <span class="form-text">

                                                            {tr}This is the email address for your
                                                            administrator
                                                            account.
                                                            {/tr}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {if not empty($htaccess_options)}
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapseHtaccess"
                                                    aria-expanded="false" aria-controls="collapseHtaccess">
                                                    <div class="accordion-button-content">
                                                        <span class="accordion-button-title">
                                                            {tr}Set up htaccess
                                                            file
                                                            {/tr} <a href="https://doc.tiki.org/Clean-URLs" target="_blank"
                                                                title="
                                                            {tr}Help
                                                            {/tr}">
                                                                {* 
                                                {icon name="help"} *}
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                                                    fill="currentColor" class="bi bi-question-square-fill"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927" />
                                                                </svg>
                                                            </a></span>
                                                        <span class="accordion-button-desc">
                                                            {tr}Choose how to
                                                            set up
                                                            the
                                                            .htaccess file for clean URLs.
                                                            {/tr}</span>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapseHtaccess" class="accordion-collapse collapse"
                                                data-bs-parent="#accordionGeneralSettings">
                                                <div class="accordion-body">
                                                    <div class="form-group">
                                                        <label class="col-form-label" for="htaccess_process">

                                                            {tr}Method
                                                            {/tr}
                                                        </label>
                                                        <select class="form-select" name="htaccess_process"
                                                            id="htaccess_process">

                                                            {foreach $htaccess_options as $k => $v}
                                                                <option value="{$k}">
                                                                    {$v}
                                                                </option>

                                                            {/foreach}
                                                        </select>
                                                        <span class="form-text">
                                                            {tr}Select how to set up your htaccess file.{/tr}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    {/if}

                                    {if $upgradefix eq 'y' && $install_type eq 'update'}
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapseUpgradeFix"
                                                    aria-expanded="false" aria-controls="collapseUpgradeFix">
                                                    <div class="accordion-button-content">
                                                        <span class="accordion-button-title"><span class="text-warning">
                                                                {icon name="warning"}</span>

                                                            {tr}Upgrade fix
                                                            {/tr} <a href="https://doc.tiki.org/Upgrade" target="_blank" title="
                                                            {tr}Help
                                                            {/tr}">
                                                                {* 

                                                                {icon name="help"} *}
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                                                                    fill="currentColor" class="bi bi-question-square-fill"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927" />
                                                                </svg>
                                                            </a></span>
                                                        <span class="accordion-button-desc">
                                                            {tr}Repair administrator privileges lost during an
                                                            upgrade.{/tr}</span>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapseUpgradeFix" class="accordion-collapse collapse"
                                                data-bs-parent="#accordionGeneralSettings">
                                                <div class="accordion-body">
                                                    <div>

                                                        <div class="toast-cad simple-toast">
                                                            <div class="toast-card-icon">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                    fill="currentColor" class="bi bi-chat-square-quote"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                                                    <path
                                                                        d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                                                </svg>
                                                            </div>
                                                            <div class="toast-card-body">
                                                                <span>

                                                                    {tr}Experiencing
                                                                    problems with
                                                                    the
                                                                    upgrade? Your administrator
                                                                    account lost its
                                                                    privileges? This may occur if you
                                                                    upgraded
                                                                    from
                                                                    a
                                                                    very old
                                                                    version of Tiki.

                                                                    {/tr}</span>
                                                            </div>
                                                        </div>

                                                        <p class="card-desc mt-3 mb-1">
                                                            {tr}We can fix it! Doing so will{/tr}
                                                        </p>
                                                        <ol>
                                                            <li class="card-desc">
                                                                {tr}Create the <em>Admins</em>
                                                                group, if missing{/tr}
                                                            </li>
                                                            <li class="card-desc">
                                                                {tr}Grant
                                                                <em>tiki_p_admin</em> to the group, if
                                                                missing{/tr}
                                                            </li>
                                                            <li class="card-desc">
                                                                {tr}Add the administrator
                                                                account to the group, if missing
                                                                {/tr}</li>
                                                        </ol>
                                                        <p class="card-desc mt-1">
                                                            {tr}To do so enter the name of the main admin account in the field
                                                            below
                                                            {/tr}</p>
                                                        <div class="form-group"><label class="col-form-label col-sm-4"
                                                                for="admin_account">
                                                                {tr}Administrator account
                                                                (optional)
                                                                {/tr}</label>
                                                            <input type="text" name="admin_account" class="form-control">
                                                            <span class="card-desc">
                                                                {tr}The default account is <span
                                                                    class="fw-bold">admin</span>{/tr}</span>
                                                        </div>

                                                        {if !empty($disableAccounts)}
                                                            <hr>
                                                            <div class="form-check square-check card-desc mt-3 mb-4">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="show-disable-accounts" name="smarty_enable_string_eval"
                                                                    value="y">
                                                                <label class="form-check-label" for="show-disable-accounts">
                                                                    {tr}Check
                                                                    this box if you
                                                                    have a lot of disabled accounts after an
                                                                    upgrade to tiki4. {/tr}
                                                                </label>
                                                            </div>

                                                            <div class="accounts-list-card form-group">
                                                                <span class="form-label">

                                                                    {tr}List of accounts that
                                                                    will
                                                                    be
                                                                    enabled

                                                                    {/tr}</span>
                                                                <ul class="list-accounts">
                                                                    {foreach from=$disableAccounts item=account}
                                                                        <li><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                                height="16" fill="currentColor"
                                                                                class="bi bi-person-circle" viewBox="0 0 16 16">
                                                                                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                                                                                <path fill-rule="evenodd"
                                                                                    d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
                                                                            </svg>{$account}</li>
                                                                    {/foreach}
                                                                </ul>
                                                            </div>
                                                        {/if}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    {/if}
                                </div>
                            </div>
                            {if $multi}<input type="hidden" name="multi" value="{$multi}">{/if}
                            {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                            <input type="hidden" name="install_step" value="7">
                            <input type="hidden" name="install_type" value="{$install_type}">
                            {if $install_type eq 'scratch'}
                            {$default_password_field}{/if}
                            <input type="hidden" name="general_settings" value="y">
                        </form>
                    </div> {* End of Content body *}
                </div>{* End of installer-step-content *}

                <div class="installer-step-footer">
                    <form action="tiki-install.php" method="post">
                        {if $multi}<input type="hidden" name="multi" value="{$multi}">{/if}
                        {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                        <button type="submit" name="install_step" value="5" class="tiki-btn tiki-btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-chevron-compact-left" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M9.224 1.553a.5.5 0 0 1 .223.67L6.56 8l2.888 5.776a.5.5 0 1 1-.894.448l-3-6a.5.5 0 0 1 0-.448l3-6a.5.5 0 0 1 .67-.223" />
                            </svg>
                            {tr}Previous{/tr}</button>
                    </form>
                    <button type="submit" form="installStep6Form" class="tiki-btn tiki-btn-primary">
                        {tr}Next{/tr}
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd"
                                d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                        </svg>
                    </button>
                </div>{* End of installer-step-footer *}
            </div>{* End of install-step6 *}
        {elseif $install_step eq '7'}
            <div class="install-step7 installer-step">
                <div class="installer-step-content">
                    <div class="installer-step-header">
                        <h2 class="installer-step-title tki-type-page-title">

                            {tr}Last Notes

                            {/tr}</h2>
                        <span class="installer-step-desc tki-type-body">


                            {tr}Complete these fields to configure common, general settings for your
                            site.

                            {/tr}<br>

                            {tr}The information you enter here can be changed later.

                            {/tr}
                        </span>
                    </div>
                    <div class="installer-step-body">
                        <div class="toast-cad simple-toast">
                            <div class="toast-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                    <path
                                        d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                    <path
                                        d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                </svg>
                            </div>
                            <div class="toast-card-body">
                                <span>

                                    {tr}Read the following information to ensure that your website
                                    data
                                    stays
                                    protected, your site
                                    healthy and you don|\t unnecessarily loose time or data while
                                    setting up
                                    your
                                    Tiki site now or
                                    while maintaining it in the future.

                                    {/tr}</span>
                            </div>
                        </div>
                        <form id="installStep7Form" action="tiki-install.php" method="post">
                            <div class="step7-notes">
                                {* 1 *}
                                <section class="text-content">
                                    <div class="text-content-header">
                                        <div class="text-content-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 16 16" fill="currentColor">
                                                <path
                                                    d="M12.12 6.963a.574.574 0 0 1 .76 0c.508.484 1.484 1.254 2.683 1.412c.24.031.437.212.437.438v2.11c0 2.839-2.784 3.87-3.386 4.06a.37.37 0 0 1-.227 0c-.602-.19-3.387-1.22-3.387-4.06v-2.11c0-.226.197-.407.437-.438c1.199-.158 2.174-.928 2.682-1.412M8 .5a3 3 0 0 1 3 3V4h.5A2.5 2.5 0 0 1 14 6.5v.106a6 6 0 0 1-.242-.196l-.187-.17l-.115-.1a1.58 1.58 0 0 0-1.912 0l-.115.1c-.432.41-1.147.955-1.96 1.116l-.269.045a1.58 1.58 0 0 0-.963.628A1 1 0 1 0 8 10v.925A4.7 4.7 0 0 0 9.108 14H4.5A2.5 2.5 0 0 1 2 11.5v-5A2.5 2.5 0 0 1 4.5 4H5v-.5a3 3 0 0 1 3-3m0 1a2 2 0 0 0-2 2V4h4v-.5a2 2 0 0 0-2-2" />
                                            </svg>
                                        </div>
                                        <h4 class="tki-type-card-title">

                                            {tr}Subscribe to Tiki Releases newsletter

                                            {/tr} -

                                            {tr}Critical & Security update

                                            {/tr}
                                        </h4>
                                    </div>
                                    <div class="text-content-desc tki-type-body">
                                        <p>

                                            {tr}It is highly recommended that you subscribe to the
                                            Tiki Releases
                                            newsletter,
                                            so
                                            that you receive important notices about new releases
                                            and critical
                                            security
                                            updates.

                                            {/tr}


                                            {tr}We don|\t share subscribed emails and we send very
                                            few of these
                                            newsletters
                                            per
                                            year.

                                            {/tr}
                                        </p>
                                        <p>

                                            {tr}Please use the following link and subscribe:

                                            {/tr}
                                            <a href="https://tiki.org/tiki-newsletters.php?nlId=8&info=1" title="
                                                            {tr}Subscribe
                                                            {/tr}" target="_blank" rel="noopener">

                                                {tr}Tiki Releases newsletter

                                                {/tr}
                                            </a>
                                        </p>
                                    </div>
                                </section>

                                {* 2 *}
                                <section class="text-content">
                                    <div class="text-content-header">
                                        <div class="text-content-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 32 32" fill="currentColor">
                                                <path
                                                    d="M16 30a1 1 0 0 1-1-1v-2h-3.5A3.5 3.5 0 0 1 8 23.5v-3a3.5 3.5 0 0 1 3.5-3.5H15v-2H8.5A3.5 3.5 0 0 1 5 11.5v-3A3.5 3.5 0 0 1 8.5 5H15V3a1 1 0 1 1 2 0v2h6.5A3.5 3.5 0 0 1 27 8.5v3a3.5 3.5 0 0 1-3.5 3.5H17v2h3.5a3.5 3.5 0 0 1 3.5 3.5v3a3.5 3.5 0 0 1-3.5 3.5H17v2a1 1 0 0 1-1 1" />
                                            </svg>
                                        </div>
                                        <h4 class="tki-type-card-title">

                                            {tr}First Wizards, then Control
                                            Panels
                                            with
                                            Preference Filter

                                            {/tr}
                                        </h4>
                                    </div>
                                    <div class="text-content-desc tki-type-body">
                                        <p>

                                            {tr}Tiki contains thousands of options and parameters
                                            (what we
                                            call
                                            "preferences"),
                                            which can be overwhelming for a new site administrator.

                                            {/tr}


                                            {tr}That|\s why we suggest you to start by using our <a target='tikihelp'
                                                href='https://doc.tiki.org/Wizards'>Wizards</a>
                                            (

                                            {icon name='magic'}).

                                            {/tr}


                                            {tr}Once basic parameters will be set, you will always
                                            be able
                                            to
                                            gain
                                            full
                                            control
                                            of the options using the Control Panels. Note: basic
                                            preferences
                                            will be
                                            displayed
                                            by default after a new install.

                                            {/tr}
                                        </p>
                                        <p>

                                            {tr}You can modify the default filter choice at your
                                            own
                                            convenience
                                            to
                                            also display Advanced, Experimental or Unavailable
                                            preferences
                                            in
                                            Control
                                            Panels.{/tr}
                                            {tr}You|\ll find the <a target='tikihelp'
                                                href='https://doc.tiki.org/Preference-Filters'>Preference
                                                Filter</a> at the top of the Navigation Bar in the <a target='tikihelp'
                                                href='https://doc.tiki.org/Control-Panels'>Control
                                                Panels</a> by clicking on the funnel icon ({icon name='filter'}) or use the
                                            search box
                                            provided.{/tr}
                                        </p>
                                    </div>
                                </section>

                                {* 3 *}
                                <section class="text-content">
                                    <div class="text-content-header">
                                        <div class="text-content-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    d="M2 5.5A2.5 2.5 0 0 1 4.5 3h2.482c.464 0 .91.184 1.238.513l1.28 1.28l-2.06 2.06A.5.5 0 0 1 7.085 7H2zM2 8v6.5A2.5 2.5 0 0 0 4.5 17h4.523A4 4 0 0 1 9 16.6c0-1.211.828-2.229 1.95-2.518a2.75 2.75 0 1 1 4.541-3A2.24 2.24 0 0 1 17 10.5c.36 0 .699.084 1 .234V7.5A2.5 2.5 0 0 0 15.5 5h-4.793l-2.56 2.56A1.5 1.5 0 0 1 7.085 8zm12.75 4.25a1.75 1.75 0 1 1-3.5 0a1.75 1.75 0 0 1 3.5 0m3.5.5a1.25 1.25 0 1 1-2.5 0a1.25 1.25 0 0 1 2.5 0M16 16.6c0 1.183-.8 2.4-3 2.4s-3-1.216-3-2.4a1.6 1.6 0 0 1 1.6-1.6h2.8a1.6 1.6 0 0 1 1.6 1.6m.704 1.4h.046c1.65 0 2.25-.912 2.25-1.8a1.2 1.2 0 0 0-1.2-1.2h-1.35c.345.441.55.997.55 1.6a3.4 3.4 0 0 1-.296 1.4" />
                                            </svg>
                                        </div>
                                        <h4 class="tki-type-card-title">

                                            {tr}Storing your uploaded files

                                            {/tr}
                                        </h4>
                                    </div>
                                    <div class="text-content-desc tki-type-body">
                                        <p>

                                            {tr}To ease the install process and first access, Tiki
                                            saves your
                                            uploaded files
                                            (office documents, images, pdf, etc. attached to wiki
                                            pages,
                                            forum
                                            posts,
                                            tracker
                                            items, file galleries, ...) by default in its database.

                                            {/tr}


                                            {tr}This works perfectly in most cases but it is not the
                                            recommended
                                            setup if
                                            you
                                            need to save many thousands of files or more.

                                            {/tr}
                                        </p>
                                        <p>

                                            {tr}In that case, consider switching from
                                            "<strong>Store
                                            to
                                            database</strong>" to "<strong>Store to
                                            directory</strong>",
                                            which
                                            you will
                                            find
                                            in the <em>Configuration Wizard - Set up File Gallery &
                                                Attachments</em>
                                            or in
                                            the
                                            <em>Control Panels - File Galleries</em> where you will
                                            be able
                                            to
                                            migrate your
                                            currently uploaded files from one to the other.
                                            {/tr}
                                        </p>
                                    </div>
                                </section>

                                {* 4 *}
                                <section class="text-content">
                                    <div class="text-content-header">
                                        <div class="text-content-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    d="M10 2a3 3 0 1 0 0 6a3 3 0 0 0 0-6M5.053 9.996q-.051.244-.051.504V14a4.99 4.99 0 0 0 1.767 3.814l-.171.05a4 4 0 0 1-4.9-2.828l-.647-2.415a1.5 1.5 0 0 1 1.061-1.837zm8.182 7.818A4.99 4.99 0 0 0 15.002 14v-3.5q-.001-.26-.05-.504l2.94.788a1.5 1.5 0 0 1 1.06 1.837l-.647 2.415a4 4 0 0 1-5.07 2.778M16.5 4a2.5 2.5 0 1 0 0 5a2.5 2.5 0 0 0 0-5m-13 0a2.5 2.5 0 1 0 0 5a2.5 2.5 0 0 0 0-5m4 5A1.5 1.5 0 0 0 6 10.5V14a4 4 0 0 0 8 0v-3.5A1.5 1.5 0 0 0 12.5 9z" />
                                            </svg>
                                        </div>
                                        <h4 class="tki-type-card-title">
                                            {tr}Tiki Community
                                            {/tr}
                                        </h4>
                                    </div>
                                    <div class="text-content-desc tki-type-body">
                                        <p>
                                            {tr}The Tiki Community is a worldwide group of
                                            developers, site
                                            operators,
                                            <a href='https://tiki.org/Consultants' target='_blank'
                                                rel="noopener">consultants</a>
                                            and users working together to improve and support Tiki.
                                            {/tr}
                                        </p>
                                        <ul class="step7-links">
                                            <li>
                                                <a href='https://tiki.org/Join' target='_blank' rel="noopener">
                                                    {tr}Join the Community
                                                    {/tr}
                                                </a>
                                            </li>
                                            <li>
                                                <a href='https://tiki.org/Help' target='_blank' rel="noopener">
                                                    {tr}Get Help with Tiki
                                                    {/tr}
                                                </a>
                                            </li>
                                            <li>
                                                <a href='https://tiki.org/Consultants' target='_blank' rel="noopener">
                                                    {tr}Hire a Tiki Service Provider
                                                    {/tr}
                                                </a>
                                            </li>
                                            <li>
                                                {tr}Help to improve the <a href='https://dev.tiki.org/' target='_blank'
                                                    rel="noopener">Code</a>
                                                and the <a href='https://doc.tiki.org/' target='_blank'
                                                    rel="noopener">Documentation</a>
                                                {/tr}
                                            </li>
                                        </ul>
                                    </div>
                                </section>
                            </div>

                            {if $multi}<input type="hidden" name="multi" value="{$multi}">
                            {/if}

                            {if $lang}<input type="hidden" name="lang" value="{$lang}">
                            {/if}
                            <input type="hidden" name="install_step" value="8">
                            <input type="hidden" name="install_type" value="{$install_type}">

                            {if $install_type eq 'scratch'}
                                {$default_password_field}

                            {/if}
                        </form>
                    </div>{* End of installer-step-body *}
                </div>{* End of installer-step-content *}

                <div class="installer-step-footer">
                    <form action="tiki-install.php" method="post">

                        {if $multi}<input type="hidden" name="multi" value="{$multi}">
                        {/if}

                        {if $lang}<input type="hidden" name="lang" value="{$lang}">
                        {/if}
                        <button type="submit" name="install_step" value="6" class="tiki-btn tiki-btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-chevron-compact-left" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M9.224 1.553a.5.5 0 0 1 .223.67L6.56 8l2.888 5.776a.5.5 0 1 1-.894.448l-3-6a.5.5 0 0 1 0-.448l3-6a.5.5 0 0 1 .67-.223" />
                            </svg>

                            {tr}Previous
                            {/tr}</button>
                    </form>
                    <button type="submit" form="installStep7Form" class="tiki-btn tiki-btn-primary">

                        {tr}Next
                        {/tr}
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd"
                                d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                        </svg>
                    </button>
                </div>{* End of installer-step-footer *}
            </div>{* End of install-step7 *}

        {elseif $install_step eq '8'}
            <div class="install-step8 installer-step">
                <div class="installer-step-content">
                    <div class="installer-step-header">
                        <h2 class="installer-step-title tki-type-page-title">
                            {tr}Enter Your Tiki
                            {/tr}
                        </h2>
                        <p class="installer-step-desc tki-type-body">
                            {tr}Your installation is ready. Choose how to enter Tiki and start
                            configuring your
                            site.
                            {/tr}
                        </p>
                    </div>

                    <div class="installer-step-body">
                        <div class="toast-cad simple-toast">
                            <div class="toast-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-chat-square-quote" viewBox="0 0 16 16">
                                    <path
                                        d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                                    <path
                                        d="M7.066 4.76A1.665 1.665 0 0 0 4 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112zm4 0A1.665 1.665 0 0 0 8 5.668a1.667 1.667 0 0 0 2.561 1.406c-.131.389-.375.804-.777 1.22a.417.417 0 1 0 .6.58c1.486-1.54 1.293-3.214.682-4.112z" />
                                </svg>
                            </div>
                            <div class="toast-card-body">
                                <span>
                                    <strong>{tr}Ready to run{/tr}</strong>
                                    {tr}The installation is complete!{/tr}
                                    {tr}Your database has been configured and Tiki is ready to run.{/tr}
                                </span>
                            </div>
                        </div>

                        {if isset($htaccess_error) and $htaccess_error eq 'y'}
                            <div class="step-group-card">
                                <div class="grp-head">
                                    <h5 class="grp-head-title">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                                            <path fill="currentColor"
                                                d="M5 5a3 3 0 0 1 3-3h10.172a3 3 0 0 1 2.12.879l5.83 5.828A3 3 0 0 1 27 10.828V27a3 3 0 0 1-3 3h-7.535c.34-.588.535-1.271.535-2h7a1 1 0 0 0 1-1V12h-5a3 3 0 0 1-3-3V4H8a1 1 0 0 0-1 1v9.416a5 5 0 0 0-2 1.583zm15 5h4.586L19 4.414V9a1 1 0 0 0 1 1m-1 11a1 1 0 1 0-2 0v4a1 1 0 1 0 2 0zm3-5a1 1 0 0 1 1 1v8a1 1 0 1 1-2 0v-8a1 1 0 0 1 1-1M5.5 19.5V19a3.5 3.5 0 1 1 7 0v.5h.5a2.5 2.5 0 0 1 2.5 2.5v6a2.5 2.5 0 0 1-2.5 2.5H5A2.5 2.5 0 0 1 2.5 28v-6A2.5 2.5 0 0 1 5 19.5zm2-.5v.5h3V19a1.5 1.5 0 0 0-3 0m3.5 6a2 2 0 1 0-4 0a2 2 0 0 0 4 0" />
                                        </svg>
                                        {tr}.htaccess File{/tr}
                                        <a href="https://doc.tiki.org/Installation" target="help" rel="noopener"
                                            title="{tr}Help{/tr}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="currentColor"
                                                viewBox="0 0 16 16" aria-hidden="true">
                                                <path
                                                    d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927" />
                                            </svg>
                                        </a>
                                    </h5>
                                    <p class="grp-head-desc">
                                        {tr}Enable search-engine friendly URLs and extra security for your Tiki.{/tr}
                                    </p>
                                </div>
                                <div class="grp-body">
                                    <div class="notice-summary-card notice-summary-card--flush-bottom">
                                        <div class="notice-summary-card__header">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 12 12">
                                                <path fill="currentColor"
                                                    d="M7.904 6.332a.7.7 0 0 1 1.193 0l.051.102l.255.62a1 1 0 0 0 .544.545l.62.254a.7.7 0 0 1 0 1.295l-.62.255a1 1 0 0 0-.544.545l-.255.62a.7.7 0 0 1-1.295 0l-.254-.62a1 1 0 0 0-.545-.545l-.62-.255a.7.7 0 0 1 0-1.295l.62-.254a1 1 0 0 0 .545-.545l.254-.62zM3.818 1.379c.328-.542 1.166-.503 1.421.116l.501 1.219c.102.247.298.443.545.545l1.219.5a.8.8 0 0 1 0 1.48l-1.219.501a1 1 0 0 0-.545.545l-.5 1.219a.8.8 0 0 1-1.48 0l-.501-1.219a1 1 0 0 0-.545-.545l-1.219-.5a.8.8 0 0 1 0-1.48l1.219-.501c.247-.102.443-.298.545-.545l.5-1.219z" />
                                            </svg>
                                            <span class="notice-summary-card__title">{tr}Recommendation{/tr}</span>
                                        </div>
                                        <p class="notice-summary-card__body">
                                            {tr}We recommend enabling the <code class="code_label">.htaccess</code> file for
                                            your
                                            Tiki.{/tr}
                                            {tr}This will enable you to use SEFURLs (search engine friendly URLs) and help
                                            improve
                                            site security.{/tr}
                                            {tr _0="<code class=\"code_label\">_htaccess</code>" _1="<code class=\"code_label\">.htaccess</code>"}To
                                            enable this file, simply copy the %0 file (located in the main directory of your
                                            Tiki
                                            installation) to %1.{/tr}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        {elseif not empty($htaccess_feedback)}
                            <div class="step-group-card">
                                <div class="grp-head">
                                    <h5 class="grp-head-title">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                                            <path fill="currentColor"
                                                d="M5 5a3 3 0 0 1 3-3h10.172a3 3 0 0 1 2.12.879l5.83 5.828A3 3 0 0 1 27 10.828V27a3 3 0 0 1-3 3h-7.535c.34-.588.535-1.271.535-2h7a1 1 0 0 0 1-1V12h-5a3 3 0 0 1-3-3V4H8a1 1 0 0 0-1 1v9.416a5 5 0 0 0-2 1.583zm15 5h4.586L19 4.414V9a1 1 0 0 0 1 1m-1 11a1 1 0 1 0-2 0v4a1 1 0 1 0 2 0zm3-5a1 1 0 0 1 1 1v8a1 1 0 1 1-2 0v-8a1 1 0 0 1 1-1M5.5 19.5V19a3.5 3.5 0 1 1 7 0v.5h.5a2.5 2.5 0 0 1 2.5 2.5v6a2.5 2.5 0 0 1-2.5 2.5H5A2.5 2.5 0 0 1 2.5 28v-6A2.5 2.5 0 0 1 5 19.5zm2-.5v.5h3V19a1.5 1.5 0 0 0-3 0m3.5 6a2 2 0 1 0-4 0a2 2 0 0 0 4 0" />
                                        </svg>
                                        {tr}.htaccess File{/tr}
                                    </h5>
                                    <p class="grp-head-desc">
                                        {tr}Enable search-engine friendly URLs and extra security for your Tiki.{/tr}
                                    </p>
                                </div>
                                <div class="grp-body">
                                    <div class="notice-summary-card notice-summary-card--flush-bottom">
                                        <div class="notice-summary-card__header">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 12 12">
                                                <path fill="currentColor"
                                                    d="M7.904 6.332a.7.7 0 0 1 1.193 0l.051.102l.255.62a1 1 0 0 0 .544.545l.62.254a.7.7 0 0 1 0 1.295l-.62.255a1 1 0 0 0-.544.545l-.255.62a.7.7 0 0 1-1.295 0l-.254-.62a1 1 0 0 0-.545-.545l-.62-.255a.7.7 0 0 1 0-1.295l.62-.254a1 1 0 0 0 .545-.545l.254-.62zM3.818 1.379c.328-.542 1.166-.503 1.421.116l.501 1.219c.102.247.298.443.545.545l1.219.5a.8.8 0 0 1 0 1.48l-1.219.501a1 1 0 0 0-.545.545l-.5 1.219a.8.8 0 0 1-1.48 0l-.501-1.219a1 1 0 0 0-.545-.545l-1.219-.5a.8.8 0 0 1 0-1.48l1.219-.501c.247-.102.443-.298.545-.545l.5-1.219z" />
                                            </svg>
                                            <span class="notice-summary-card__title">{tr}Success{/tr}</span>
                                        </div>
                                        <p class="notice-summary-card__body">
                                            {tr _0=$htaccess_feedback}Your .htaccess file has been set up (%0){/tr}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        {/if}

                        <div class="step-group-card">
                            <div class="grp-head">
                                <h5 class="grp-head-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                                        <path fill="currentColor"
                                            d="M9.472 4a4.25 4.25 0 0 0-4.095 3.113L4.436 10.5H6.51l.793-2.852A2.25 2.25 0 0 1 9.472 6H22.53a2.25 2.25 0 0 1 2.168 1.648l3.219 11.589a2.18 2.18 0 0 1-2.1 2.763H14.5v2h11.317c2.764 0 4.767-2.635 4.027-5.299L26.626 7.113A4.25 4.25 0 0 0 22.531 4zM14.5 26H24a1 1 0 1 1 0 2h-9.53q.03-.245.03-.5zm-10-12a.5.5 0 0 0-.5.5v13a.5.5 0 0 0 .5.5h6a.5.5 0 0 0 .5-.5v-13a.5.5 0 0 0-.5-.5zm-2.5.5A2.5 2.5 0 0 1 4.5 12h6a2.5 2.5 0 0 1 2.5 2.5v13a2.5 2.5 0 0 1-2.5 2.5h-6A2.5 2.5 0 0 1 2 27.5zM7.5 20a2 2 0 1 0 0-4a2 2 0 0 0 0 4" />
                                    </svg>
                                    {tr}Tiki Entry Type{/tr}
                                </h5>
                                <p class="grp-head-desc">
                                    {tr}Choose how to enter Tiki.{/tr}
                                </p>
                            </div>
                            <div class="grp-body">
                                <div class="step8-lead tki-type-body">
                                    {if $install_type eq 'scratch' && isset($defaultpass)}
                                        <p>
                                            {tr _0="<strong class=\"text-highlight\">{$defaultpass}</strong>"}A temporary
                                            default
                                            password, %0, has been created for the admin account. Please replace it with a
                                            password
                                            of your choice.{/tr}
                                        </p>
                                    {/if}
                                    <p>
                                        {tr}You can now log in into Tiki as user <strong>admin</strong> and start
                                        configuring
                                        the application.{/tr}
                                    </p>
                                </div>

                                {if isset($smarty.post.update)}
                                    <div class="toast-cad toast-info mt-1 mb-2">
                                        <div class="toast-card-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                                viewBox="0 0 16 16">
                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                                <path
                                                    d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                                            </svg>
                                        </div>
                                        <div class="toast-card-body">
                                            <span>
                                                {tr}If this is an upgrade, clean the Tiki caches manually (the
                                                <strong>temp/templates_c</strong> directory) or by using the <strong>Admin &gt;
                                                    System</strong> option from the Admin menu.{/tr}
                                            </span>
                                        </div>
                                    </div>
                                {/if}

                                {if $tikidb_is20}
                                    <div class="tiki-entry-type">
                                        <form method="post" action="tiki-install.php" class="enter-tiki-form" id="enterTikiForm"
                                            data-enter-tiki-choice>
                                            {if $multi}
                                                <input type="hidden" name="multi" value="{$multi|escape}">
                                            {/if}
                                            <input type="hidden" name="install_type" value="{$install_type}">
                                            {if $install_type eq 'scratch'}
                                                {$default_password_field}
                                            {/if}
                                            <input type="hidden" name="install_step" value="9">
                                            <input type="hidden" name="nolockenter" value="1" disabled id="enterTikiNolock">

                                            <ul class="tki-choice-list tki-choice-list--grid tiki-entry-type-list"
                                                role="radiogroup">
                                                <li class="entry-card is-recommended">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="enter_mode"
                                                            id="enter-tiki-lock" value="lock" checked>
                                                        <label class="form-check-label entry-card-inner" for="enter-tiki-lock">
                                                            <div class="entry-card-header">
                                                                <span class="entry-card-title-group">
                                                                    <span class="icon" aria-hidden="true">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                            height="16" fill="currentColor" viewBox="0 0 32 32">
                                                                            <path
                                                                                d="M9.472 4a4.25 4.25 0 0 0-4.095 3.113L4.436 10.5H6.51l.793-2.852A2.25 2.25 0 0 1 9.472 6H22.53a2.25 2.25 0 0 1 2.168 1.648l3.219 11.589a2.18 2.18 0 0 1-2.1 2.763H14.5v2h11.317c2.764 0 4.767-2.635 4.027-5.299L26.626 7.113A4.25 4.25 0 0 0 22.531 4zM14.5 26H24a1 1 0 1 1 0 2h-9.53q.03-.245.03-.5zm-10-12a.5.5 0 0 0-.5.5v13a.5.5 0 0 0 .5.5h6a.5.5 0 0 0 .5-.5v-13a.5.5 0 0 0-.5-.5zm-2.5.5A2.5 2.5 0 0 1 4.5 12h6a2.5 2.5 0 0 1 2.5 2.5v13a2.5 2.5 0 0 1-2.5 2.5h-6A2.5 2.5 0 0 1 2 27.5zM7.5 20a2 2 0 1 0 0-4a2 2 0 0 0 0 4" />
                                                                        </svg>
                                                                    </span>
                                                                    <span class="title">
                                                                        {tr}Enter Tiki &amp; Lock Installer{/tr}
                                                                    </span>
                                                                </span>
                                                            </div>
                                                            <div class="entry-card-body">
                                                                <span class="desc">
                                                                    {tr}Installs Tiki and secures the installation script to
                                                                    prevent
                                                                    unauthorized modifications.{/tr}
                                                                </span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </li>

                                                <li class="entry-card is-warning">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="enter_mode"
                                                            id="enter-tiki-unlock" value="unlock">
                                                        <label class="form-check-label entry-card-inner"
                                                            for="enter-tiki-unlock">
                                                            <div class="entry-card-header">
                                                                <span class="entry-card-title-group">
                                                                    <span class="icon" aria-hidden="true">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                            height="16" fill="currentColor" viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M4.665 2c-.896 0-1.683.596-1.926 1.459l-1.66 5.898A2.08 2.08 0 0 0 3.083 12h9.834a2.08 2.08 0 0 0 2.004-2.643l-1.66-5.898A2 2 0 0 0 11.335 2zm-.963 1.73A1 1 0 0 1 4.665 3h6.67a1 1 0 0 1 .963.73l1.66 5.898A1.08 1.08 0 0 1 12.918 11H3.082a1.08 1.08 0 0 1-1.04-1.372zM4.497 13a.5.5 0 1 0 0 1h7.005a.5.5 0 1 0 0-1z" />
                                                                        </svg>
                                                                    </span>
                                                                    <span class="title">
                                                                        {tr}Keep Installer Unlocked{/tr}
                                                                    </span>
                                                                </span>
                                                            </div>
                                                            <div class="entry-card-body">
                                                                <span class="desc">
                                                                    {tr}Installs Tiki but leaves the installation script
                                                                    accessible
                                                                    for troubleshooting or advanced setup.{/tr}
                                                                </span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </li>
                                            </ul>
                                        </form>
                                        <script type="text/javascript">
                                            {literal}
                                                (function() {
                                                    var form = document.getElementById('enterTikiForm');
                                                    var nolock = document.getElementById('enterTikiNolock');
                                                    if (!form || !nolock) { return; }

                                                    function syncChoice() {
                                                        var checked = form.querySelector('input[name="enter_mode"]:checked');
                                                        nolock.disabled = !(checked && checked.value === 'unlock');
                                                    }

                                                    form.addEventListener('change', syncChoice);
                                                    form.addEventListener('submit', syncChoice);
                                                    syncChoice();
                                                })();
                                            {/literal}
                                        </script>

                                        <div class="toast-cad toast-warning">
                                            <div class="toast-card-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-exclamation-triangle-fill"
                                                    viewBox="0 0 16 16">
                                                    <path
                                                        d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2" />
                                                </svg>
                                            </div>
                                            <div class="toast-card-body">
                                                <span>
                                                    {tr}Choosing the first option is highly recommended to secure your
                                                    application.
                                                    Leaving the installer unlocked poses a critical security risk.{/tr}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                {/if}
                            </div>
                        </div>

                        {if $install_type eq 'update'}
                            {if $double_encode_fix_attempted eq 'y'}
                                <div class="step-group-card">
                                    <div class="grp-head">
                                        <h5 class="grp-head-title">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 20 20">
                                                <path fill="currentColor"
                                                    d="M2 4.5A2.5 2.5 0 0 1 4.5 2h8A2.5 2.5 0 0 1 15 4.5v8a2.5 2.5 0 0 1-2.5 2.5h-1.585a1.5 1.5 0 0 0 0-1H12.5a1.5 1.5 0 0 0 1.5-1.5V6H3v5.085a1.5 1.5 0 0 0-.56.354l-.44.44zm1 0V5h11v-.5A1.5 1.5 0 0 0 12.5 3h-8A1.5 1.5 0 0 0 3 4.5M14 18H7.5c.384 0 .768-.146 1.06-.44l.561-.56H14a3 3 0 0 0 3-3V7.5a1.5 1.5 0 0 0-1-1.415V5.05a2.5 2.5 0 0 1 2 2.45V14a4 4 0 0 1-4 4m-4.146-3.146l-2 2a.5.5 0 0 1-.708-.708L8.293 15H2.707l1.147 1.146a.5.5 0 0 1-.708.708l-2-2a.5.5 0 0 1 0-.708l2-2a.5.5 0 0 1 .708.708L2.707 14h5.586l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708M3.243 14h1.325" />
                                            </svg>
                                            {tr}Upgrade{/tr}
                                        </h5>
                                        <p class="grp-head-desc">
                                            {tr}Encoding repair has already been attempted.{/tr}
                                        </p>
                                    </div>
                                    <div class="grp-body">
                                        <div class="notice-summary-card notice-summary-card--flush-bottom">
                                            <div class="notice-summary-card__header">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 12 12">
                                                    <path fill="currentColor"
                                                        d="M7.904 6.332a.7.7 0 0 1 1.193 0l.051.102l.255.62a1 1 0 0 0 .544.545l.62.254a.7.7 0 0 1 0 1.295l-.62.255a1 1 0 0 0-.544.545l-.255.62a.7.7 0 0 1-1.295 0l-.254-.62a1 1 0 0 0-.545-.545l-.62-.255a.7.7 0 0 1 0-1.295l.62-.254a1 1 0 0 0 .545-.545l.254-.62zM3.818 1.379c.328-.542 1.166-.503 1.421.116l.501 1.219c.102.247.298.443.545.545l1.219.5a.8.8 0 0 1 0 1.48l-1.219.501a1 1 0 0 0-.545.545l-.5 1.219a.8.8 0 0 1-1.48 0l-.501-1.219a1 1 0 0 0-.545-.545l-1.219-.5a.8.8 0 0 1 0-1.48l1.219-.501c.247-.102.443-.298.545-.545l.5-1.219z" />
                                                </svg>
                                                <span class="notice-summary-card__title">{tr}Ready{/tr}</span>
                                            </div>
                                            <p class="notice-summary-card__body">
                                                {tr}You can now access the site normally. Report back any issues that you might find
                                                (if
                                                any) to the Tiki forums or bug tracker{/tr}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            {elseif not isset($legacy_collation)}
                                <div class="step-group-card">
                                    <div class="grp-head">
                                        <h5 class="grp-head-title grp-head-title--warning">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 20 20">
                                                <path fill="currentColor"
                                                    d="M8.46 2.607a1.75 1.75 0 0 1 3.08 0l6.857 12.242A1.75 1.75 0 0 1 16.857 17.5H3.143a1.75 1.75 0 0 1-1.54-2.651zM10 6.25a.75.75 0 0 0-.75.75v4.25a.75.75 0 0 0 1.5 0V7a.75.75 0 0 0-.75-.75m0 8.5a.875.875 0 1 0 0-1.75a.875.875 0 0 0 0 1.75" />
                                            </svg>
                                            {tr}Upgrading and running into encoding issues?{/tr}
                                        </h5>
                                        <p class="grp-head-desc">
                                            {tr}We can try to fix it, but <strong>make sure you have backups, and can restore
                                                them</strong>.{/tr}
                                        </p>
                                    </div>
                                    <div class="grp-body">
                                        {if ($client_charset_in_file eq 'utf8' or $client_charset_in_file eq 'utf8mb4') and ($database_charset eq 'utf8mb4' or $database_charset eq 'utf8')}
                                            <form method="post" action="tiki-install.php" class="encoding-fix-form"
                                                onsubmit="return confirm('{tr escape='javascript'}Are you sure you want to attempt to fix the encoding of your entire database?{/tr}');">
                                                {if $multi}<input type="hidden" name="multi" value="{$multi|escape}">{/if}
                                                {if $lang}<input type="hidden" name="lang" value="{$lang}">{/if}
                                                <input type="hidden" name="install_step" value="8">
                                                <div class="form-group">
                                                    <label class="col-form-label" for="previous_encoding">
                                                        {tr}Previous table encoding:{/tr}
                                                    </label>
                                                    <div class="encoding-fix-row">
                                                        <select class="form-select" name="previous_encoding" id="previous_encoding"
                                                            required>
                                                            <option value="" selected disabled>{tr}Please select{/tr}</option>
                                                            <option value="armscii8" title="Armenian, Binary">armscii8</option>
                                                            <option value="ascii" title="West European (multilingual), Binary">ascii
                                                            </option>
                                                            <option value="big5" title="Traditional Chinese, Binary">big5</option>
                                                            <option value="binary" title="Binary">binary</option>
                                                            <option value="cp1250" title="Central European (multilingual), Binary">
                                                                cp1250
                                                            </option>
                                                            <option value="cp1251" title="Cyrillic (multilingual), Binary">cp1251
                                                            </option>
                                                            <option value="cp1256" title="Arabic, Binary">cp1256</option>
                                                            <option value="cp1257" title="Baltic (multilingual), Binary">cp1257</option>
                                                            <option value="cp850" title="West European (multilingual), Binary">cp850
                                                            </option>
                                                            <option value="cp852" title="Central European (multilingual), Binary">cp852
                                                            </option>
                                                            <option value="cp866" title="Russian, Binary">cp866</option>
                                                            <option value="cp932" title="Japanese, Binary">cp932</option>
                                                            <option value="dec8" title="West European (multilingual), Binary">dec8
                                                            </option>
                                                            <option value="eucjpms" title="Japanese, Binary">eucjpms</option>
                                                            <option value="euckr" title="Korean, Binary">euckr</option>
                                                            <option value="gb2312" title="Simplified Chinese, Binary">gb2312</option>
                                                            <option value="gbk" title="Simplified Chinese, Binary">gbk</option>
                                                            <option value="geostd8" title="Georgian, Binary">geostd8</option>
                                                            <option value="greek" title="Greek, Binary">greek</option>
                                                            <option value="hebrew" title="Hebrew, Binary">hebrew</option>
                                                            <option value="hp8" title="West European (multilingual), Binary">hp8
                                                            </option>
                                                            <option value="keybcs2" title="Czech-Slovak, Binary">keybcs2</option>
                                                            <option value="koi8r" title="Russian, Binary">koi8r</option>
                                                            <option value="koi8u" title="Ukrainian, Binary">koi8u</option>
                                                            <option value="latin1" title="West European (multilingual), Binary">latin1
                                                            </option>
                                                            <option value="latin2" title="Central European (multilingual), Binary">
                                                                latin2
                                                            </option>
                                                            <option value="latin5" title="Turkish, Binary">latin5</option>
                                                            <option value="latin7" title="Baltic (multilingual), Binary">latin7</option>
                                                            <option value="macce" title="Central European (multilingual), Binary">macce
                                                            </option>
                                                            <option value="macroman" title="West European (multilingual), Binary">
                                                                macroman
                                                            </option>
                                                            <option value="sjis" title="Japanese, Binary">sjis</option>
                                                            <option value="swe7" title="Swedish, Binary">swe7</option>
                                                            <option value="tis620" title="Thai, Binary">tis620</option>
                                                            <option value="ucs2" title="Unicode (multilingual), Binary">ucs2</option>
                                                            <option value="ujis" title="Japanese, Binary">ujis</option>
                                                        </select>
                                                        <button type="submit" name="fix_double_encoding" value="1"
                                                            class="tiki-btn tiki-btn-danger">
                                                            {tr}Click to fix double encoding (dangerous){/tr}
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        {else}
                                            <div class="notice-summary-card notice-summary-card--danger">
                                                <div class="notice-summary-card__header">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                                        viewBox="0 0 16 16">
                                                        <path
                                                            d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2" />
                                                    </svg>
                                                    <span class="notice-summary-card__title">{tr}Warning{/tr}</span>
                                                </div>
                                                <p class="notice-summary-card__body">
                                                    {tr}Oops. You need to make sure client charset and database encoding are forced to
                                                    UTF-8. Reset the database connection to continue.{/tr}
                                                </p>
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                            {/if}
                        {/if}
                    </div>{* End of installer-step-body *}
                </div>{* End of installer-step-content *}

                <div class="installer-step-footer">
                    <form action="tiki-install.php" method="post">
                        {if $multi}<input type="hidden" name="multi" value="{$multi}">
                        {/if}
                        {if $lang}<input type="hidden" name="lang" value="{$lang}">
                        {/if}
                        <button type="submit" name="install_step" value="7" class="tiki-btn tiki-btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-chevron-compact-left" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M9.224 1.553a.5.5 0 0 1 .223.67L6.56 8l2.888 5.776a.5.5 0 1 1-.894.448l-3-6a.5.5 0 0 1 0-.448l3-6a.5.5 0 0 1 .67-.223" />
                            </svg>
                            {tr}Previous
                            {/tr}
                        </button>
                    </form>

                    {if $tikidb_is20}
                        <button type="submit" form="enterTikiForm" class="tiki-btn tiki-btn-primary" id="enterTikiSubmit">
                            {tr}Enter Tiki
                            {/tr}
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                            </svg>
                        </button>
                    {else}
                        <button type="button" class="tiki-btn tiki-btn-primary" disabled>
                            {tr}Enter Tiki
                            {/tr}
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-chevron-compact-right" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M6.776 1.553a.5.5 0 0 1 .671.223l3 6a.5.5 0 0 1 0 .448l-3 6a.5.5 0 1 1-.894-.448L9.44 8 6.553 2.224a.5.5 0 0 1 .223-.671" />
                            </svg>
                        </button>
                    {/if}
                </div>{* End of installer-step-footer *}
            </div>{* End of install-step8 *}

        {/if}
        </main>
    </div>
</div>
</div>
</div>
