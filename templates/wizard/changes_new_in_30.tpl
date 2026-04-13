<div class="d-flex">
    <div class="me-4">
        <span class="float-start fa-stack fa-lg margin-right-18em" title="{tr}Changes Wizard{/tr}">
            {icon name='arrow-circle-up' iclass='fa-stack-2x'}
            {icon name='magic' iclass='fa-flip-horizontal fa-stack-1x ms-4 mt-4'}
        </span>
    </div>
    <div class="flex-grow-1 ms-3">
        <h2>{tr}New in Tiki 30 (LTS){/tr}</h2>
        <p>
            {tr}Tiki 30 is a Long-Term Support (LTS) release, supported until 2031, focused on stability, performance and modern improvements.{/tr}
        </p>
        <a href="https://doc.tiki.org/Tiki30" target="tikihelp" class="tikihelp text-info">
            {icon name="help"} {tr}Read full documentation{/tr}
        </a>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Infrastructure & Core{/tr}</legend>
            <ul>
                <li>{tr}Background job queue for asynchronous task execution{/tr}</li>
                <li>{tr}Refactored admin page language and section handling{/tr}</li>
                <li>{tr}Support for modern PHP environments (including PHP 8.4+){/tr}</li>
            </ul>
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}User Interface & UX{/tr}</legend>
            <ul>
                <li>{tr}Tikiben theme with new bento-style layout{/tr}</li>
                <li>{tr}Collapsible sidebar restored in admin{/tr}</li>
                <li>{tr}Improved modals with resizable windows{/tr}</li>
                <li>{tr}Better mobile dropdown positioning{/tr}</li>
                <li>{tr}User info tooltips enabled by default{/tr}</li>
            </ul>
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Editors{/tr}</legend>
            <ul>
                <li>{tr}Markdown editor improvements with extended help{/tr}</li>
                <li>{tr}Summernote integration with LanguageTool{/tr}</li>
                <li>{tr}New ToastUI editor support{/tr}</li>
            </ul>
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}New External Integrations{/tr}</legend>
            <ul>
                <li>
                    {tr}CryptPad integration for collaborative Office document editing{/tr}<br>
                    <a href="https://doc.tiki.org/Tiki30#External_Services_Integrations" target="tikihelp">
                        {tr}More Information{/tr}
                    </a>
                </li>
                <li>
                    {tr}PeerTube integration for decentralized video hosting{/tr}<br>
                    <a href="https://doc.tiki.org/Tiki30#External_Services_Integrations" target="tikihelp">
                        {tr}More Information{/tr}
                    </a>
                </li>
                <li>
                    {tr}Prosody and Converse.js integration with Tiki as identity provider{/tr}<br>
                    <a href="https://doc.tiki.org/Prosody" target="tikihelp">
                        {tr}More Information{/tr}
                    </a>
                </li>
            </ul>
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}New Wiki Plugins{/tr}</legend>
            <ul>
            <li>{preference name=wikiplugin_lottie}<br></li>
            <li>{preference name=wikiplugin_model3dviewer}<br></li>
            <li>{preference name=wikiplugin_fancylink}<br></li>
            <li>{preference name=wikiplugin_contributionsdashboard}<br></li>
            </ul>
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Security{/tr}</legend>
            <ul>
                <li>{tr}Two-Factor Authentication (2FA){/tr}</li>
                <li>{tr}Brute-force protection (experimental){/tr}</li>
                <li>{tr}Improved password reset with cryptographic tokens{/tr}</li>
                <li>{tr}Extended HTTP security headers and CORS support{/tr}</li>
                <li>{tr}Usernames cannot contain spaces{/tr}</li>
            </ul>
        </fieldset>
        <i>
            {tr}This release focuses on stability and long-term improvements rather than major breaking changes.{/tr}
        </i>
    </div>
</div>
