{if $prefs.auth_webauthn_enabled eq 'y'}
    <div class="{if $form eq 'register'}col-sm-8 offset-sm-4 mb-3{/if}">
        <input type="checkbox" class="form-check-input" id="webauthn_checkbox_{$form}" is_passed="n">
        <label for="webauthn_checkbox_{$form}">
            {if $form eq 'register'}{tr}Webauth Registration{/tr}{else}{tr}Webauth Login{/tr}{/if}
        </label>
    </div>
{/if}
