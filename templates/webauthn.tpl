{if $prefs.auth_webauthn_enabled eq 'y' and $form eq 'login'}
    <div>
        <input type="checkbox" class="form-check-input" id="webauthn_checkbox_{$form}" is_passed="n">
        <label for="webauthn_checkbox_{$form}">
            {tr}Use passkey to login{/tr}
        </label>
    </div>
{/if}
