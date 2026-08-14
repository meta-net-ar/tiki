{tikimodule error=$module_error title=$tpl_module_title name=$tpl_module_name flip=$module_params.flip decorations=$module_params.decorations nobox="y" notitle="y" type=$module_type}
    <button type="button" class="btn btn-primary btn-toggle collapsed" data-bs-toggle="collapse" data-bs-target="{$module_params.target}" {if !empty($module_params.parent)} data-bs-parent="{$module_params.parent}"{/if} aria-expanded="false">
        <span class="btn-text">
            <span class="show-text">{tr}Show{/tr}{if !empty($module_params.button_label)} {$module_params.button_label}{/if}</span>
            <span class="hide-text">{tr}Hide{/tr}{if !empty($module_params.button_label)} {$module_params.button_label}{/if}</span>
        </span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </button>
{/tikimodule}
