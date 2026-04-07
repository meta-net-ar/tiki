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
                <li>{tr}Modernized authentication and configuration systems{/tr}</li>
                <li>{tr}Support for modern PHP environments (including PHP 8.4+){/tr}</li>
            </ul>
        </fieldset>

        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}User Interface & UX{/tr}</legend>
            <ul>
                <li>{tr}Improved admin interface and navigation{/tr}</li>
                <li>{tr}Responsive UI improvements and better mobile experience{/tr}</li>
                <li>{tr}Enhanced modals, menus, and notifications{/tr}</li>
                <li>{tr}New theme improvements (including Tikiben layout){/tr}</li>
            </ul>
        </fieldset>

        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Editors{/tr}</legend>
            <ul>
                <li>{tr}Improved Markdown and editing experience{/tr}</li>
                <li>{tr}Integration of grammar tools (LanguageTool){/tr}</li>
                <li>{tr}New and enhanced editors (ToastUI, Summernote){/tr}</li>
            </ul>
        </fieldset>

        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Integrations{/tr}</legend>
            <ul>
                <li>{tr}BigBlueButton and PeerTube integration{/tr}</li>
                <li>{tr}CryptPad support for document collaboration{/tr}</li>
                <li>{tr}Improved XMPP / Converse.js integration{/tr}</li>
            </ul>
        </fieldset>

        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Security{/tr}</legend>
            <ul>
                <li>{tr}Two-Factor Authentication (2FA){/tr}</li>
                <li>{tr}Improved password reset security{/tr}</li>
                <li>{tr}Brute-force protection (experimental){/tr}</li>
                <li>{tr}Enhanced HTTP security headers and access control{/tr}</li>
            </ul>
        </fieldset>

        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Developers & API{/tr}</legend>
            <ul>
                <li>{tr}Improved API and object categorization{/tr}</li>
                <li>{tr}Mermaid support for diagrams{/tr}</li>
                <li>{tr}CI improvements and static analysis (PHPStan){/tr}</li>
                <li>{tr}Modern build system (Node.js 24 support){/tr}</li>
            </ul>
        </fieldset>

        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Notable Settings{/tr}</legend>
            {preference name=url_fragment_format}
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
        <legend>{tr}Key Settings{/tr}</legend>

        {preference name=url_fragment_format}
        {preference name=feature_wysiwyg}
        {preference name=feature_ajax}
        {preference name=feature_two_factor_auth}
        {preference name=feature_file_galleries}

        </fieldset>

        <i>
            {tr}This release focuses on stability and long-term improvements rather than major breaking changes.{/tr}
        </i>

    </div>
</div>
