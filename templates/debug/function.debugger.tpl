{if $tiki_p_admin eq 'y' and $prefs.feature_debug_console eq 'y'}
    <div class="debugconsole" id="debugconsole" style="{$debugconsole_style}">

        {* Command prompt form *}
        <form method="post" action="{$console_father|escape}" id="command_form">
            <b>{tr}Debugger console{/tr}</b>
            <span style="float: right">
                <a href='#' onclick="toggle('debugconsole');" title=":{tr}Close{/tr}" class="tips">
                    {icon name='delete'}
                </a>
            </span>
            <table class="table">
                <tr>
                    <td><small>{tr}Current URL:{/tr}</small></td>
                    <td>{$console_father|escape}</td>
                </tr>
                <tr>
                    <td>{tr}Command:{/tr}</td>
                    <td>
                        <div class="d-flex">
                            <select class="form-select" id="command_preselect" style="width:140px; border-top-right-radius:0; border-bottom-right-radius:0;">
                                <option selected value="">Select</option>
                                <option value="features [partial-name]">features</option>
                                <option value="perm [partial-name]">perm</option>
                                <option value="print $var1 $var2 ...">print</option>
                                <option value="slist">slist</option>
                                <option value="sprint $var1 $var2 $var3 ...">sprint</option>
                                <option value="sql [sql-query]">sql</option>
                                <option value="test">test</option>
                                <option value="tikitables  [partial-name]">tikitables</option>
                                <option value="watch (add|rm) $php_var1 smarty_var2 $php_var3 smarty_var4 ...">{tr}watch{/tr}</option>
                            </select>
                            <input type="text" id="command_input" name="command" class="form-control selectable" style="border-top-left-radius:0; border-bottom-left-radius:0;" value='{$command|escape:"html"}'>
                        </div>
                        <p class="text-secondary" id="command_meta" style="display: none;">
                            <span id="command_description"></span>
                            <br>
                            {tr}Example{/tr} :
                            <span id="command_example"></span>&nbsp;
                            <span id="copyButton">{icon name="clone"}</span>&nbsp;
                            <span id="success_copy_icon" style="display:none;" class="text-primary">{icon name="check"}</span>
                        </p>
                        <input type="submit" class="btn btn-primary btn-sm mt-2 mb-2" id="command_execute" name="exec" value="{tr}execute{/tr}">
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        {tr _0='<button class="btn btn-primary btn-sm" onclick="window.viewHelp(event)">' _1='</button>&nbsp;<small>' _2='<code>help</code>' _3='</small>'}%0Click to view help%1 or type %2 to get list of avaible commands%3{/tr}
                    </td>
                </tr>
            </table>
        </form>

        {* Generate tabs code if more than one tab, else make one div w/o button *}

        {* 1) Buttons bar *}
        {if count($tabs) > 1}
            <table>
                <tr>
                    {section name=i loop=$tabs}
                        <td>
                            {$thistabshref=$tabs[i].button_href}
                            {$thistabscaption=$tabs[i].button_caption}
                            {button _onclick=$thistabshref _text=$thistabscaption _ajax="n"}
                        </td>
                    {/section}
                </tr>
            </table>
        {/if}

        {* 2) Divs with tabs *}
        {section name=i loop=$tabs}
            <div class="debugger-tab selectable" id="{$tabs[i].tab_id}" style="display:{if $tabs[i].button_caption == 'console'}block{else}none{/if};">
                {$tabs[i].tab_code}
            </div>{* Tab: {$tabs[i].tab_id} *}
        {/section}

    </div>{* debug console *}
{/if}
