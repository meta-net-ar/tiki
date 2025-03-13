{title help="Spreadsheet"}{tr}Spreadsheet History:{/tr} {$title}{/title}

<div>
    {$description|escape}
</div>
<form>
    {tabset}
        {if not $grid_content eq ''}
            {if $history|@count == 0}
                {tab name="{tr}View{/tr}"}
                    <h2>{tr}View{/tr}</h2>
                    <div class="alert alert-info" style="text-align: center;">
                        {tr}This spreadsheet has no recorded history.{/tr}
                    </div>
                {/tab}
            {elseif $history|@count == 1}
                {tab name="{tr}View{/tr}"}
                    <h2>{tr}View{/tr}</h2>
                    <div id="tiki_sheets_container">
                        <table style="width: 100%;" id="tiki_sheet_container">
                            <tr>
                                <td class="sheet_sibling">
                                    <div style="font-size: 1.5em; text-align: center;">
                                        Revision: {$history[0].prettystamp}
                                    </div>
                                    <div class="tiki_sheet" {if !empty($tiki_sheet_div_style)} style="{$tiki_sheet_div_style}"{/if}>
                                        {$grid_content[1]}
                                    </div>
                                    <div style="text-align: center;">
                                        {button _keepall='y' href="tiki-view_sheets.php" sheetId=$sheetId readdate=$history[0].stamp parse="y" class="view_button" _text="{tr}View{/tr}" _htmlelement="role_main" _title="{tr}View{/tr}"}
                                        {button _keepall='y' href="tiki-view_sheets.php" sheetId=$sheetId readdate=$history[0].stamp parse="clone" class="clone_button" _text="{tr}Clone{/tr}" _htmlelement="role_main" _title="{tr}Clone{/tr}"}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                {/tab}
            {else}
                {tab name="{tr}View{/tr}"}
                    <h2>{tr}View{/tr}</h2>
                    <div id="tiki_sheets_container">
                        <table style="width: 100%;" id="tiki_sheet_container">
                            <tr>
                                {section name=date loop=$grid_content}
                                    <td class="sheet_sibling" style="width: 50%;">
                                        {pagination_links cant=$ver_cant itemname="{tr}Sheet{/tr}" offset_arg="idx_{$smarty.section.date.index}" offset=$sheetIndexes[$smarty.section.date.index] show_numbers=n}{/pagination_links}
                                    </td>
                                {/section}
                            </tr>
                            <tr>
                                {section name=date loop=$grid_content}
                                    <td class="sheet_sibling">
                                        <div style="font-size: 1.5em; text-align: center;">
                                            Revision: {$history[$sheetIndexes[$smarty.section.date.index]].prettystamp}
                                        </div>
                                    </td>
                                {/section}
                            </tr>
                            <tr>
                                {section name=date loop=$grid_content}
                                    <td>
                                        <div class="tiki_sheet" {if !empty($tiki_sheet_div_style)} style="{$tiki_sheet_div_style}"{/if}>
                                            {$grid_content[$smarty.section.date.index]}
                                        </div>
                                    </td>
                                {/section}
                            </tr>
                            <tr>
                                {section name=date loop=$grid_content}
                                    <td class="sheet_sibling">
                                        <div style="text-align: center;">
                                            {button _keepall='y' href="tiki-view_sheets.php" sheetId=$sheetId readdate=$history[$sheetIndexes[$smarty.section.date.index]].stamp parse="y" class="view_button" _text="{tr}View{/tr}" _htmlelement="role_main" _title="{tr}View{/tr}"}
                                            {button _keepall='y' href="tiki-view_sheets.php" sheetId=$sheetId readdate=$history[$sheetIndexes[$smarty.section.date.index]].stamp parse="clone" class="clone_button" _text="{tr}Clone{/tr}" _htmlelement="role_main" _title="{tr}Clone{/tr}"}
                                            <form style="display: inline" action="tiki-view_sheets.php" method="post">
                                                {ticket}
                                                <input type="hidden" name="sheetId" value="{$sheetId}">
                                                <input type="hidden" name="readdate" value="{$history[$sheetIndexes[$smarty.section.date.index]].stamp}">
                                                <input type="hidden" name="parse" value="rollback">
                                                <input type="hidden" name="class" value="rollback_button">
                                                <button type="submit" class="btn btn-primary" onclick="confirmPopup('{tr}Are you sure you want to roll back this spreadsheet?{/tr}')">
                                                    {icon _menu_text='y' _menu_icon='n' alt="{tr}Roll back{/tr}"}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                {/section}
                            </tr>
                        </table>
                        <div class="navbar" style="text-align: center;">
                            {button _id="go_fullscreen" _text="{tr}Toggle Full Screen{/tr}"}
                        </div>
                    </div>
                {/tab}
            {/if}
        {/if}

        {tab name="{tr}Date Selection{/tr}"}
            <h2>{tr}Date Selection{/tr}</h2>
            <div class="table-responsive">
                <table class="table">
                    <tr>
                        <th>{tr}Edit Date{/tr}</th>
                        <th>{tr}User{/tr}</th>
                        <th>{tr}Actions{/tr}</th>
                        <th colspan="2">
                            <input type="hidden" name="sheetId" id="sheetId" value="{$sheetId}">
                            <input type="submit" id="compareSheetsSubmit" class="btn btn-primary" value="{tr}compare{/tr}">
                        </th>
                    </tr>
                    {section name=revision_date loop=$history}
                        <tr>
                            <td>{$history[revision_date].prettystamp}</td>
                            <td>{$history[revision_date].user}</td>
                            <td style="text-align: center;">
                                <a href="tiki-view_sheets.php?sheetId={$sheetId}&readdate={$history[revision_date].stamp}&parse=y" title="{tr}View Spreadsheet{/tr}">{tr}View{/tr}</a> |
                                <a href="tiki-view_sheets.php?sheetId={$sheetId}&readdate={$history[revision_date].stamp}&parse=clone" title="{tr}Clone Spreadsheet{/tr}">{tr}Clone{/tr}</a> |
                                <a href="tiki-view_sheets.php?sheetId={$sheetId}&readdate={$history[revision_date].stamp}&parse=rollback" title="{tr}Roll back spreadsheet{/tr}">{tr}Roll back{/tr}</a>
                            </td>
                            <td style="vertical-align: middle; text-align: center;">
                                <input type="radio" name="idx_0" class="compareSheet1" value="{$smarty.section.revision_date.index}" onclick="$.sheet.compareSheetClick($('input.compareSheet1'), $('input.compareSheet2'));"></td>
                            <td style="vertical-align: middle; text-align: center;">
                                <input type="radio" name="idx_1" class="compareSheet2" value="{$smarty.section.revision_date.index}" onclick="$.sheet.compareSheetClick($('input.compareSheet1'), $('input.compareSheet2'));"></td>
                        </tr>
                    {/section}
                </table>
            </div>
        {/tab}
    {/tabset}
</form>
