{tikimodule  error=$module_error|default:null title=$tpl_module_title name=$tpl_module_name flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle type=$module_type|default:null}
<style>
    .color-mode-navbar .btn#color-mode-theme {
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
            <button class="btn btn-link dropdown-toggle py-2 px-0 px-lg-2 d-flex align-items-center loading" id="color-mode-theme" data-bs-toggle="dropdown" type="button" aria-expanded="false" data-bs-display="static" aria-label="{tr}Toggle theme (auto){/tr}">
                {icon name=$default_icon}
            </button>
            <ul class="dropdown-menu" aria-labelledby="color-mode-theme">
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
{literal}
<script>
(function () {
    var btn = document.getElementById('color-mode-theme');
    if (!btn) return;
    var menu = document.querySelector('#color-mode-theme ~ ul.dropdown-menu, #color-mode-theme + ul.dropdown-menu');
    if (!menu) return;

    btn.addEventListener('show.bs.dropdown', function () {
        var rect = btn.getBoundingClientRect();
        var isRightHalf = rect.left + rect.width / 2 > window.innerWidth / 2;
        menu.classList.toggle('dropdown-menu-end', isRightHalf);
        menu.classList.toggle('dropdown-menu-start', !isRightHalf);
    });
}());
</script>
{/literal}
{/tikimodule}
