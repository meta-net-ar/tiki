{$headerlib->add_jsfile('lib/jquery_tiki/tiki-toast.js')}
{strip}
    <div id="{$toast_id|escape}" class="{$toast_class|escape}" role="status" data-toast-type="{$toast_type|escape}"
        {if $toast_duration gt 0} data-toast-duration="{$toast_duration|escape}" {/if}>
        <div class="tki-toast__main">
            <div class="tki-toast__icon" aria-hidden="true">
                {icon name=$toast_icon}
            </div>

            <div class="tki-toast__content">
                {if $toast_title ne ''}
                    <div class="tki-toast__title">{$toast_title|escape}</div>
                {/if}
                {if $toast_description ne ''}
                    <div class="tki-toast__description">{$toast_description}</div>
                {/if}
            </div>

            {if $toast_action}
                <div class="tki-toast__actions">
                    <button type="button" class="tki-toast__btn tki-toast__btn--primary"
                        {if $toast_callback ne ''}onclick="{$toast_callback|escape}(this); return false;" {/if}>
                        {$toast_action_label|escape}
                    </button>

                    {if $toast_secondary_label ne ''}
                        {if $toast_secondary_href ne ''}
                            <a class="tki-toast__btn tki-toast__btn--secondary" href="{$toast_secondary_href|escape}"
                                {if $toast_secondary_callback ne ''}onclick="{$toast_secondary_callback|escape}(this);" {/if}>
                                {$toast_secondary_label|escape}
                            </a>
                        {else}
                            <button type="button" class="tki-toast__btn tki-toast__btn--secondary"
                                {if $toast_secondary_callback ne ''}onclick="{$toast_secondary_callback|escape}(this); return false;"
                                {/if}>
                                {$toast_secondary_label|escape}
                            </button>
                        {/if}
                    {/if}
                </div>
            {/if}

            {if $toast_close}
                <button type="button" class="tki-toast__close" data-tki-toast-dismiss aria-label="{tr}Close{/tr}">
                    <span aria-hidden="true">&times;</span>
                </button>
            {/if}
        </div>

        {if $toast_duration gt 0}
            <div class="tki-toast__timer">
                {tr}This message will automatically close in{/tr}
                <strong class="tki-toast__timer-value"><span data-tki-toast-seconds>{$toast_duration|escape}</span>
                    {tr}sec{/tr}</strong>
            </div>
        {/if}
    </div>
{/strip}

{jq}
if (window.TikiToast) {
TikiToast.activate("#{{$toast_id|escape}}");
}
{/jq}
