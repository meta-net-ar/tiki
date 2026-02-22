<div class="row justify-content-center">
    <div class="col-sm-6">
        {module module=login_box
            mode="module"
            show_register="y"
            show_forgot="y"
            show_two_factor_auth="{$showTwoFactorForm}"
            error=""
            flip=""
            decorations=""
            nobox=""
            notitle=""
            start_session=$prefs.session_silent
        }
    </div>
</div>
