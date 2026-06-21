{strip}
{if $prefs.cookie_consent_mode eq 'dialog'}
    <div class="modal" tabindex="-1" role="dialog" id="{$prefs.cookie_consent_dom_id}">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{tr}Cookie Consent{/tr}</h5>
                </div>
                <div class="modal-body">
                    <form method="POST">
{else}
    <div id="{$prefs.cookie_consent_dom_id}" role="alert" class="fixed-bottom"
            {if not empty($prefs.cookie_consent_mode)}class="{$prefs.cookie_consent_mode}" {/if}>
        <form method="POST" class="position-fixed bottom-0 start-0 end-0 collapse show">
            <div class="alert alert-primary rounded-2 border-top border-secondary border-1 mb-0 py-4 collapse show">
                <div class="container">
{/if}
                    <div class="row mb-4">
                        <div class="col-12">
                            <p class="mb-0 lead">
                                {wiki}{tr}{$prefs.cookie_consent_description}{/tr}{/wiki}
                            </p>
                        </div>
                    </div>

                    <div id="customConsentSectionBanner" class="row mb-4">
                        <div class="col-12 d-flex flex-wrap gap-3 gap-md-4">
                            {foreach from=$cookie_categories key=category item=data}
                                <div class="form-check form-switch d-flex align-items-center flex-shrink-0">
                                    <input class="form-check-input me-2" type="checkbox" id="toggle{$category|capitalize}"
                                           name="cookie_consent_{$category}"
                                           {if $category == 'essential'}checked disabled aria-disabled="true"{/if}>
                                    <label class="form-check-label me-2 fw-semibold" for="toggle{$category|capitalize}">
                                        {tr}{$data.name}{/tr}
                                    </label>
                                    <button type="button" class="btn btn-link p-0 text-decoration-none text-dark"
                                            data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="{tr}{$data.description}{/tr}"
                                            aria-label="{tr}{$data.description}{/tr}">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            {/foreach}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 d-flex flex-wrap gap-2 justify-content-center justify-content-md-end">
                            {if $prefs.cookie_consent_disable eq 'n'}
                                <button type="submit" class="btn btn-outline-danger"
                                        id="cookie_decline_unnecessary_button">
                                    {tr}Refuse unnecessary{/tr}
                                </button>
                            {/if}
                            <button type="submit" class="btn btn-secondary"
                                    id="cookie_save_button">
                                {tr}Save preferences{/tr}
                            </button>
                            <button type="submit" class="btn btn-primary"
                                    id="cookie_consent_preference" name="cookie_consent_preference">
                                {tr}Accept all{/tr}
                            </button>
                        </div>
                    </div>

            {if $prefs.cookie_consent_mode neq 'dialog'}</div></div>{/if}
        </form>

    {if $prefs.cookie_consent_mode eq 'dialog'}</div></div></div>{/if}
</div>
{/strip}
