{tikimodule
decorations="{$module_params.decorations}"
error="{$error}"
flip="{$module_params.flip}"
nobox="{$module_params.nobox}"
nonums="{$module_params.nonums}"
notitle="{$module_params.notitle}"
overflow="{$module_params.overflow}"
title=$tpl_module_title
style="{$module_params.style}"
}
{if empty($error)}
    <div class="mod-git_detail cvsup">
        <span class="label">{tr}Git information:{/tr}</span>&nbsp;
        <span class="branch">{$content.branch}:<a href="https://gitlab.com/tikiwiki/tiki/-/commit/{$content.commit.hash}">{$content.commit.hash|substr:0:8}</a></span>&nbsp;
        <span class="date">{tr}from{/tr} {$content.mdate|tiki_short_datetime}</span>
    </div>
{else}
    <div class="mod-git_detail cvsup">
        <span class="label">{tr}Tiki version:{/tr} {$content.version}&nbsp;
    </div>
{/if}
{/tikimodule}
