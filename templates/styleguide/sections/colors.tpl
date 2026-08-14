<div class="colors">
    <h2>Colors</h2>
    <div class="input">
        {if $prefs.site_layout eq 'classic' or $prefs.site_layout eq 'header_middle_footer_containers_3-6-3'}
            {$header_selector = '.header_outer'}
        {else}
            {$header_selector = '.page-header,.topbar'}
        {/if}
        <p class="regular">{tr}Header{/tr}</p>
        <p class="picker" data-selector="{$header_selector}" data-element="background-color">
            <span class="input-group-addon"><i></i></span>
            <input id="tc-header-color" data-selector="{$header_selector}" data-element="background-color" type="text">
        </p>
    </div>

    <div class="input">
        <p class="regular">{tr}Body{/tr}</p>
        <p class="picker" data-selector="body" data-element="background-color">
            <span class="input-group-addon"><i></i></span>
            <input id="tc-body-color" data-selector="body" data-element="background-color" data-var="@body-bg" type="text">
        </p>
    </div>

    <div class="input">
        <p class="regular">{tr}Footer{/tr}</p>
        <p class="picker" data-selector="footer" data-element="background-color">
            <span class="input-group-addon"><i></i></span>
            <input id="tc-footer-color" data-selector="footer" data-element="background-color" type="text">
        </p>
    </div>

    <div class="input">
        <p class="regular">{tr}Text{/tr}</p>
        <p class="picker" data-selector="body" data-element="color">
            <span class="input-group-addon"><i></i></span>
            <input id="tc-text-color" data-selector="body" data-element="color" data-var="@text-color" type="text">
        </p>
    </div>

    <div class="input">
        <p class="regular">{tr}Links{/tr}</p>
        <p class="picker" data-selector="a" data-element="color">
            <span class="input-group-addon"><i></i></span>
            <input id="tc-link-color" data-selector="a" data-element="color" data-var="@link-color" type="text">
        </p>
    </div>

    <p>{tr}Sample Text{/tr}</p>
    <p>In mauris integer etiam aliquet integer duis rhoncus <a href="#" role="button">ultricies cras</a> in habitasse ac sociis
        porttitor placerat ac porttitor, in ac. A, tristique <a href="#" role="button">dapibus mauris</a>, vut et porta? Enim, porta
        penatibus, augue egestas aliquam eu velit placerat, sociis hac et, pulvinar <a href="#" role="button">tincidunt amet</a> ut
        turpis dapibus. Dolor. Lundium rhoncus elementum vel. Tempor sit nisi aliquam ut augue tincidunt tincidunt cum
        <a href="#" role="button">egestas massa</a>, nunc etiam ac scelerisque auctor sed sed facilisis!
    </p>
</div>

{jq}
$('.colors').find('.picker').each(function() {
        $(this).find('sl-color-picker').each(function() {
            this.trigger.style.cssText = 'visibility: hidden; position: absolute; margin-left: -150px; margin-top: -30px;';
        });
        $(this).find('.input-group-addon').on('click', () => {
            this.querySelector('sl-color-picker').trigger.click();
        });
    });
{/jq}
