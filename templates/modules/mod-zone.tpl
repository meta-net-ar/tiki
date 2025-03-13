{if $module_params.accordion == 'y'}
    {assign var="unique" value="modZoneAccordion"}
    {assign var="header" value=$tpl_module_title}
    {assign var="isOpen" value=$module_params.isOpen}
    {assign var="icon" value=$module_params.icon}
    {assign var="zone" value=$module_params.name}
    {assign var="zoneclass" value=$module_params.zoneclass}
    
    {if $module_params.error}
        <div class="alert alert-danger" role="alert">
            {$module_params.error}
        </div>
    {/if}
    
    <div class="accordion{if $module_params.decorations} decorated{/if}" id="{$unique}">
        <div class="accordion-item{if $module_params.nobox} no-box{/if}">
            <h3 class="fs-5 border-bottom py-2 px-4 bg-light rounded-top">
                {$header}
            </h3>
            <div id="collapse{$unique}" class="accordion-collapse collapse show" aria-labelledby="heading{$unique}" data-bs-parent="#{$unique}">
                <div class="accordion-body" id="accordionBody_{$zone}">
                    {modulelist zone=$zone class=$zoneclass}
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var navbar = document.querySelector('#accordionBody_{$zone}').firstElementChild;
            var modules = Array.from(navbar.children);
            modules.forEach(function(module, index) {
                var moduleId = module.id || 'module_' + index;
                var header = module.querySelector('.card-header');
                var body = module.querySelector('.card-body');
                if (header && body) {
                    header.classList.add('accordion-button', 'collapsed');
                    const attributes = {
                        'type': 'button',
                        'data-bs-toggle': 'collapse',
                        'data-bs-target': '#' + module.id + '_collapse',
                        'aria-expanded': 'false',
                        'aria-controls': module.id + '_collapse'
                    };
                    Object.keys(attributes).forEach(attr => header.setAttribute(attr, attributes[attr]));
                    var collapseDiv = document.createElement('div');
                    collapseDiv.id = moduleId + '_collapse';
                    collapseDiv.className = 'accordion-collapse collapse';
                    collapseDiv.setAttribute('aria-labelledby', 'heading' + moduleId);
                    collapseDiv.setAttribute('data-bs-parent', '#accordionBody_{$zone}');
                    body.parentElement.insertBefore(collapseDiv, body);
                    collapseDiv.appendChild(body);
                }
            });
        });
    </script>
{else}
    {tikimodule error=$module_params.error title=$tpl_module_title name="zone" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
        {modulelist zone=$module_params.name class=$module_params.zoneclass}
    {/tikimodule}
{/if}
