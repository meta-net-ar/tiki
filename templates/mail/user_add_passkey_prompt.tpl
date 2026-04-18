<div class="card shadow-sm mt-4" id="passkey-prompt-card" style="max-width:520px; margin:0 auto;">
    <div class="card-body text-center py-5 px-4">
        <div class="mb-3 fs-3">
            {icon name='key' iclass='text-primary fa-2x'}
        </div>
        <h4 class="card-title mb-2">{tr}Secure your account with a Passkey{/tr}</h4>
        <p class="text-muted mb-4">
            {tr}A passkey lets you sign in using your device's biometrics or security key — no password needed. It's faster and phishing-resistant.{/tr}
        </p>

        <div id="passkey-actions">
            <button class="btn btn-primary btn-lg me-2" id="addPasskeyBtn">
                {tr}Create Passkey{/tr}
            </button>
            <button class="btn btn-outline-secondary btn-lg" id="skipPasskeyBtn">
                {tr}Skip for now{/tr}
            </button>
        </div>

        <div id="passkey-spinner" class="mt-4" style="display:none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{tr}Loading...{/tr}</span>
            </div>
            <p class="text-muted mt-2">{tr}Follow your device's prompt to create a passkey…{/tr}</p>
        </div>

        <div id="passkey-error" class="mt-4" style="display:none;">
            <div class="alert alert-danger" id="passkey-error-msg"></div>
            <button class="btn btn-primary me-2" id="retryPasskeyBtn">{tr}Retry{/tr}</button>
            <button class="btn btn-outline-secondary" id="skipAfterErrorBtn">{tr}Skip{/tr}</button>
        </div>
    </div>
</div>

<div id="passkey-success" hidden>
    {include file='mail/user_welcome_msg.tpl'}
</div>

{jq}
    $.fn.passkeyPrompt.init("{{$username|escape:'javascript'}}");
{/jq}