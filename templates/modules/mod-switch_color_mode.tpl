{tikimodule  error=$module_error|default:null title=$tpl_module_title name=$tpl_module_name flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle type=$module_type|default:null}
<style>
    .color-mode-navbar .btn#color-mode-theme {
        border-radius: 100%;
        display: grid;
        place-items: center;
        height: 40px;
        width: 40px;
        transition: all 0.1s;
    }

    #color-mode-theme.loading {
        opacity: 0;
    }

    .dropdown-item .theme-selected-check {
        display: none;
    }

    .dropdown-item.active .theme-selected-check {
        display: inline;
    }
</style>
{if $error}
    {$message}
{else}
    <div class="color-mode-navbar">
        <div class="dropdown">
            <button class="btn btn-link dropdown-toggle py-2 px-0 px-lg-2 d-flex align-items-center loading" id="color-mode-theme" data-bs-toggle="dropdown" type="button" aria-expanded="false" data-bs-display="static" aria-label="Toggle theme (auto)">
                {icon name=$default_icon}
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="color-mode-theme" data-bs-popper="static">
                {foreach $default_mode item=mode}
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-1" data-bs-theme-value="{$mode['name']}" aria-pressed="false">
                            <span class='theme_icon'>{icon name=$mode['icon']}</span>
                            {$mode['name']}
                            <span class='theme-selected-check'>{icon name='check'}</span>
                        </button>
                    </li>
                {/foreach}

                {foreach $custom_mode item=mode}
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-1" data-bs-theme-value="{$mode['name']}" aria-pressed="false">
                            <span class='theme_icon'>{icon name=$mode['icon']}</span>
                            {$mode['name']}
                            <span class='theme-selected-check'>{icon name='check'}</span>
                        </button>
                    </li>
                {/foreach}
            </ul>
        </div>
    </div>
{/if}
{/tikimodule}
