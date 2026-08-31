{strip}
    <div class="adminoptionbox preference mb-3 row clearfix {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}" style="text-align: left;">
        <label class="col-form-label col-sm-3" for="{$p.id|escape}">{$p.name|escape}
            {include file="prefs/shared-help-icon.tpl"}
        </label>
        <div class="col">
            {textarea
                _simple="y"
                _toolbars="n"
                _wysiwyg="n"
                name="{$p.preference|escape}"
                id="{$p.id|escape}"
                _syntax="{$syntax|escape|default:'none'}"
                codemirror="{$codemirror|escape|default:'false'}"
                class="form-control"
                rows="{$p.size|escape|default:null}"
                disabled="{if $p.parameters.disabled|default:''}disabled{else}{/if}"
            }
                {* No |escape here: _wysiwyg="n" forces wiki_edit.tpl which already applies |escape on $textareadata. Adding it here would cause double-escaping. *}
                {$p.value}
            {/textarea}
            {include file="prefs/shared-form-text.tpl"}
        </div>
        <div class="tikihelp-reset-wrapper col-sm-2">
            {include file="prefs/shared.tpl"}
        </div>
    </div>
{/strip}
