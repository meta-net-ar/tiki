{* Content moved from here to shared-help-icon.tpl *}

{if !empty($p.warning)}
    <a href="#" target="tikihelp" class="tikihelp text-warning" title="{tr}Warning:{/tr} {$p.warning|escape}" role="button">
        {icon name="warning"}
    </a>
{/if}

{if $p.modified and $p.available}
    <span class="pref-reset-wrapper">
        <input class="pref-reset system" type="checkbox" aria-label="{tr}Reset to default value{/tr}" name="lm_reset[]" value="{$p.preference|escape}" style="display:none" data-preference-default="{if $p.separator and is_array($p.default)}{$p.default|join:$p.separator|escape}{else}{$p.default|escape}{/if}" />
        <a href="#" class="pref-reset-undo tips" title="{tr}Reset{/tr}|{tr}Reset to default value{/tr}" role="button">{icon name="undo"}</a>
        <a href="#" class="pref-reset-redo tips" title="{tr}Restore{/tr}|{tr}Restore current value{/tr}" style="display:none" role="button">{icon name="repeat"}</a>
    </span>
{/if}

{if !empty($p.popup_html)}
    <a class="tips" title="{tr}Actions{/tr}" href="#" role="button" style="padding:0; margin:0; border:0" {popup fullhtml=1 center="true" text=$p.popup_html trigger="click"}>
        {icon name="actions"}
    </a>
{/if}
{if !empty($p.voting_html)}
    {$p.voting_html}
{/if}

{$p.pages}

{if isset($pref_filters) and in_array('advanced', $p.tags)}
    <span class="badge bg-warning tips pref-tag-badge pref-tag-advanced" title=":{tr}Change your preference filter settings in order to view advanced preferences by default{/tr}"{if $pref_filters.advanced.selected} style="display:none"{/if}>
        {tr}advanced{/tr}
    </span>
{/if}
{if isset($pref_filters) and in_array('experimental', $p.tags)}
    <span class="badge bg-danger tips pref-tag-badge pref-tag-experimental" title=":{tr}Change your preference filter settings in order to view experimental preferences by default{/tr}"{if $pref_filters.experimental.selected} style="display:none"{/if}>
        {tr}experimental{/tr}
    </span>
{/if}

<div class="d-inline-flex flex-column">
    {if !empty($p.conflicts)}
        {foreach from=$p.conflicts.active item=conflict}
            <div class="alert alert-danger pref_conflict d-none alert-sm" data-conflict="{$conflict.name|escape}" data-preference="{$p.preference|escape}">
                {if isset($conflict.message)}
                    {$conflict.message} <br/>
                {else}
                    {tr}Conflict: this preference is incompatible with{/tr} <a href="{$conflict.link|escape}" class="alert-link">{$conflict.label|escape}</a>. {tr} Please disable one!{/tr}
                {/if}
            </div>
        {/foreach}
        {foreach from=$p.conflicts.inactive item=conflict}
            <div class="alert alert-warning pref_conflict d-none alert-sm" data-conflict="{$conflict.name|escape}" data-preference="{$p.preference|escape}">
                {if isset($conflict.message)}
                    {$conflict.message} <br/>
                {else}
                    {tr}This preference is incompatible with{/tr} <a href="{$conflict.link|escape}" class="alert-link">{$conflict.label|escape}</a>. {tr}Please disable one!{/tr}
                {/if}
            </div>
        {/foreach}
    {/if}
    {if !empty($p.dependencies)}
        {foreach from=$p.dependencies item=dep}
            {if !empty($dep.met)}
                {icon name="ok" class="pref_dependency tips text-success" title="{tr}Requires:{/tr} "|cat:$dep.label|escape|cat:" (OK)"}
            {elseif $dep.type eq 'profile'}
                <div class="alert alert-warning pref_dependency d-inline-block"{if not $p.modified} style="display:none;"{/if}>{tr}You need apply profile{/tr} <a href="{$dep.link|escape}" class="alert-link">{$dep.label|escape}</a></div>
            {else}
                <div class="alert alert-warning pref_dependency d-inline-block"{if not $p.modified} style="display:none;"{/if}>{tr}You need to set{/tr} <a href="{$dep.link|escape}" class="alert-link">{$dep.label|escape}</a></div>
            {/if}
        {/foreach}
    {/if}
</div>

{* Contents moved to shared-form-text.tpl, to display under input. *}

{* Used by some preferences of type text (and textarea) *}
{if $p.translatable eq 'y'}
    {button _class="btn btn-link tips" _type="link" href="tiki-preference_translate.php?pref={$p.preference|escape}" _icon_name="language" _text="" _title=":{tr}Translate{/tr} {$p.name|escape}"}
{/if}

<input class="system" type="hidden" name="lm_preference[]" value="{$p.preference|escape}">
{if !empty($p.packages_required)}
    {foreach from=$p.packages_required item=dep}
        {if !empty($dep.met)}
            {icon name="ok" class="pref_dependency tips text-success" title="{tr}Requires package:{/tr} "|cat:$dep.label|escape|cat:" (OK)"}
        {else}
            <div class="alert alert-warning pref_dependency d-inline-block"{if not $p.modified and not $p.value} style="display:none;"{/if}>
                {tr}A Tiki package is missing:{/tr} <a href="tiki-admin.php?page=packages&amp;package_highlight={$dep.label|escape:'url'}" class="alert-link">{$dep.label|escape}</a>
            </div>
        {/if}
    {/foreach}
{/if}
{foreach from=$p.notes item=note}
    <div class="form-text pref_note">{$note|escape}</div>
{/foreach}
