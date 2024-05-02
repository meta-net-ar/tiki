{* \brief Show plugins help
 * included by tiki-show_help.tpl via smarty_block_add_help() *}

{if count($plugins) ne 0}

    <h5>{tr}Plugins{/tr}</h5>
    <div class="help_section">
        {remarksbox type="info"}
        {tr}Note: plugin arguments can be enclosed with double quotes (<code>&quot;</code>). This allows them to contain <code>,</code> or <code>=</code> or <code>&gt;</code>.{/tr} {if $prefs.feature_help eq 'y'}{tr}More information:{/tr} <a href="{$prefs.helpurl}Plugins" target="tikihelp" class="tikihelp alert-link" title="{tr}Plugins:{/tr}{tr}Wiki plugins extend the function of wiki syntax with more specialized commands.{/tr}">
                    Plugins {icon name="link-external" istyle="font-size: 70%"}</a>.{/if}
        {/remarksbox}

        {if $tiki_p_admin eq 'y'}
            <a href="tiki-admin.php?page=textarea&amp;cookietab=2" target="tikihelp" class="tikihelp">
                {tr}Activate/deactivate plugins{/tr}
            </a>
        {/if}

        {if isset($editorId)}
            {listfilter selectors='#plugins_help_table tr' editorId=$editorId parentTabId="plugin_help"}
        {else}
            {listfilter selectors='#plugins_help_table tr' parentTabId="plugin_help"}
        {/if}
        <table id="plugins_help_table"  class="table table-condensed table-hover">
            <tr><th>{tr}Description{/tr}</th></tr>

            {section name=i loop=$plugins}
                {if !empty($plugins[i])}
                    <tr>
                        <td>{* $plugins[i].help is generated using the tiki-plugin_help.tpl template *}
                            {$plugins[i].help}
                        </td>
                    </tr>
                {/if}
            {/section}
        </table>
    </div>
{/if}
