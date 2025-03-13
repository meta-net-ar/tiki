<div class="card flex-fill p-3 bg-info-subtle text-info-emphasis tikihelp plugin plugin-link" role="button" tabindex="1" title="{$plugin.name}" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-content="{$plugin.description}" data-plugin-name="{$plugin_name|lower|@addslashes}" data-area-id="{$area_id}">
    <div class="">
        <div class="d-flex flex-column align-items-center">
            <span class="fs-2">
            {icon name=$plugin.iconname|default:"plugin" _text="{tr}Insert{/tr}"}
            </span>
            <span class="card-title text-info-emphasis">{$plugin.name|escape}</span>
        </div>
        <span class="position-absolute bottom-0 end-0 me-1">
            {if $prefs.feature_help eq 'y'}
                {if !empty($plugin.documentation)}
                    <a href="{$plugin.documentation|escape}" onclick="needToConfirm=false;" target="tikihelp" class="tikihelp text-info">
                        {icon name='help'}
                    </a>
                {/if}
            {/if}
        </span>
    </div>
</div>
<style>
    .tiki .popover {
        z-index: 1061;
    }
</style>
