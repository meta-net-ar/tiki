<div class="card shadow-sm mt-4" id="passkey-prompt-card" style="max-width:520px; margin:0 auto;">
    <div class="card-body text-center py-5 px-4">
        <div class="mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" fill="currentColor"
                 class="text-primary" viewBox="0 0 16 16">
                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2
                         2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
            </svg>
        </div>
        <h4 class="card-title mb-2">{tr}Secure your account with a Passkey{/tr}</h4>
        <p class="text-muted mb-4">
            {tr}A passkey lets you sign in using your device's biometrics or security key — no password needed. It's faster and phishing-resistant.{/tr}
        </p>

        {* Initial action buttons *}
        <div id="passkey-actions">
            <button class="btn btn-primary btn-lg me-2" id="addPasskeyBtn">
                {tr}Create Passkey{/tr}
            </button>
            <button class="btn btn-outline-secondary btn-lg" id="skipPasskeyBtn">
                {tr}Skip for now{/tr}
            </button>
        </div>

        {* Spinner shown while waiting for device prompt *}
        <div id="passkey-spinner" class="mt-4" style="display:none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{tr}Loading...{/tr}</span>
            </div>
            <p class="text-muted mt-2">{tr}Follow your device's prompt to create a passkey…{/tr}</p>
        </div>

        {* Error state with Retry / Skip *}
        <div id="passkey-error" class="mt-4" style="display:none;">
            <div class="alert alert-danger" id="passkey-error-msg"></div>
            <button class="btn btn-primary me-2" id="retryPasskeyBtn">{tr}Retry{/tr}</button>
            <button class="btn btn-outline-secondary" id="skipAfterErrorBtn">{tr}Skip{/tr}</button>
        </div>
    </div>
</div>

{jq}
    $.fn.passkeyPrompt.init("{{$username|escape:'javascript'}}");
{/jq}