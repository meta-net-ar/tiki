<div class="row">
    <div class="col-sm-9 offset-sm-3">
        <div id="recurrenceRules" style=" {if ( !($calitem.recurrenceId gt 0) and $recurrent neq 1 )} display:none; {/if}">
            {if $calitem.recurrenceId gt 0}
                <input type="hidden" name="recurrenceId" value="{$recurrence.id}">
            {/if}
            {if $recurrence.id eq 0}
                <div class="w-100">
                    <div class="input-group my-2">
                        <span class="input-group-text">{tr}Recurrence Type:{/tr}</span>
                        <select name="recurrenceType" id="recurrenceType" class="form-control">
                            <option value="daily" {if $recurrence.daily or $recurrence.id eq 0} selected="selected" {/if}>{tr}On a daily basis{/tr}</option>
                            <option value="weekly" {if $recurrence.weekly} selected="selected" {/if}>{tr}On a weekly basis{/tr}</option>
                            <option value="monthly" {if $recurrence.monthly} selected="selected" {/if}>{tr}On a monthly basis{/tr}</option>
                            <option value="yearly" {if $recurrence.yearly} selected="selected" {/if}>{tr}On a yearly basis{/tr}</option>
                        </select>
                    </div>
                </div>
            {/if}
            {if $recurrence.id gt 0}
                {if $recurrence.daily}
                    <input type="hidden" name="recurrenceType" value="daily">
                    {tr}On a daily basis{/tr}
                    <br>
                {/if}
            {/if}
            {if $recurrence.id eq 0 or $recurrence.daily}
                <div class="recurrenceTypeFieldsDaily">
                    <div class="mb-3 px-5">
                        <div class="input-group">
                            <span class="input-group-text">{tr}Every{/tr}</span>
                            <select name="days" class="form-control">
                                {for $i=1 to 31}
                                    <option value="{$i}"{if $recurrence.days == $i} selected="selected" {/if}>
                                        {$i}
                                    </option>
                                {/for}
                            </select>
                            <span class="input-group-text">{tr}day(s){/tr}</span>
                        </div>
                    </div>
                    <hr/>
                </div>
            {/if}
            {if $recurrence.id gt 0}
                {if $recurrence.weekly}
                    <input type="hidden" name="recurrenceType" value="weekly">
                    {tr}On a weekly basis{/tr}
                    <br>
                {/if}
            {/if}
            {if $recurrence.id eq 0 or $recurrence.weekly}
                <div class="recurrenceTypeFieldsWeekly">
                    <div class="mb-3 px-5">
                        <div class="input-group">
                            <span class="input-group-text">{tr}Every{/tr}</span>
                            <select name="weeks" class="form-control">
                                {for $i=1 to 52}
                                    <option value="{$i}"{if $recurrence.weeks == $i} selected="selected" {/if}>
                                        {$i}
                                    </option>
                                {/for}
                            </select>
                            <span class="input-group-text">{tr}week(s){/tr}</span>
                        </div>
                        <hr/>
                    </div>
                    <div class="mb-3 px-5">
                        <div class="input-group">
                            <span class="input-group-text">{tr}Each{/tr}</span>
                            <select name="weekdays[]" class="form-control" multiple>
                                {foreach $daynames as $abbr => $dayname}
                                    <option value="{$abbr}"{if in_array($abbr, $recurrence.weekdays)} selected="selected" {/if}>
                                        {$dayname}
                                    </option>
                                {/foreach}
                            </select>
                            <span class="input-group-text">{tr}of the week{/tr}</span>
                        </div>
                    </div>
                    <hr>
                </div>
            {/if}
            {if $recurrence.id gt 0}
                {if $recurrence.monthly}
                    <input type="hidden" name="recurrenceType" value="monthly">
                    {tr}On a monthly basis{/tr}
                    <br>
                {/if}
            {/if}
            {if $recurrence.id eq 0 or $recurrence.monthly}
                <div class="recurrenceTypeFieldsMonthly">
                    <div class="mb-3 px-5">
                        <div class="input-group">
                            <span class="input-group-text">{tr}Every{/tr}</span>
                            <select name="months" class="form-control">
                                {for $i=1 to 36}
                                    <option value="{$i}"{if $recurrence.months == $i} selected="selected" {/if}>
                                        {$i}
                                    </option>
                                {/for}
                            </select>
                            <span class="input-group-text">{tr}month(s){/tr}</span>
                        </div>
                        <hr/>
                    </div>
                    <div class="mb-3 px-5">
                        {if $recurrence.id neq 0}<input type="hidden" name="recurrenceTypeMonthy" value="{$recurrence.monthlyType}">{/if}
                        {if $recurrence.id eq 0 or $recurrence.monthlyType eq 'date'}
                            <div class="input-group">
                                {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" {if $recurrence.monthlyType eq 'date'}checked="checked"{/if} name="recurrenceTypeMonthy" value="date"></span>{/if}
                                <span class="input-group-text">{tr}Each{/tr}</span>
                                <select name="dayOfMonth[]" class="form-control" multiple>
                                    {for $k = 1 to 31}
                                        <option value="{$k}" {if in_array($k, $recurrence.dayOfMonth)} selected="selected" {/if} >
                                            {if $k lt 10}0{/if}{$k}
                                        </option>
                                    {/for}
                                </select>
                                <span class="input-group-text">{tr}of the month{/tr}</span>
                            </div>
                        {/if}
                        {if $recurrence.id eq 0}
                            <div class="text-center py-2"><span>{tr}OR{/tr}</span></div>
                        {/if}
                        {if $recurrence.id eq 0 or $recurrence.monthlyType eq 'weekday'}
                            <div class="input-group">
                                {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" {if $recurrence.monthlyType eq 'weekday'}checked="checked"{/if} name="recurrenceTypeMonthy" value="weekday"></span>{/if}
                                <span class="input-group-text">{tr}Every{/tr}</span>
                                <select name="monthlyWeekNumber" class="form-control" {if $recurrence.id neq 0}readonly{/if}>
                                    <option value="1" {if $recurrence.monthlyWeekdayValue[0] eq '1'} selected="selected" {/if}>
                                        {tr}First{/tr}
                                    </option>
                                    <option value="2" {if $recurrence.monthlyWeekdayValue[0] eq '2'} selected="selected" {/if}>
                                        {tr}Second{/tr}
                                    </option>
                                    <option value="3" {if $recurrence.monthlyWeekdayValue[0] eq '3'} selected="selected" {/if}>
                                        {tr}Third{/tr}
                                    </option>
                                    <option value="4" {if $recurrence.monthlyWeekdayValue[0] eq '4'} selected="selected" {/if}>
                                        {tr}Fourth{/tr}
                                    </option>
                                    <option value="5" {if $recurrence.monthlyWeekdayValue[0] eq '5'} selected="selected" {/if}>
                                        {tr}Fifth{/tr}
                                    </option>
                                    <option value="-1" {if strpos($recurrence.monthlyWeekdayValue, '-1') === 0} selected="selected" {/if}>
                                        {tr}Last{/tr}
                                    </option>
                                </select>
                                <select name="monthlyWeekday" class="form-control" {if $recurrence.id neq 0}readonly{/if}>
                                    <option value="SU" {if strpos($recurrence.monthlyWeekdayValue, 'SU') neq false} selected="selected" {/if}>
                                        {tr}Sunday{/tr}
                                    </option>
                                    <option value="MO" {if strpos($recurrence.monthlyWeekdayValue, 'MO') neq false} selected="selected" {/if}>
                                        {tr}Monday{/tr}
                                    </option>
                                    <option value="TU" {if strpos($recurrence.monthlyWeekdayValue, 'TU') neq false} selected="selected" {/if}>
                                        {tr}Tuesday{/tr}
                                    </option>
                                    <option value="WE" {if strpos($recurrence.monthlyWeekdayValue, 'WE') neq false} selected="selected" {/if}>
                                        {tr}Wednesday{/tr}
                                    </option>
                                    <option value="TH" {if strpos($recurrence.monthlyWeekdayValue, 'TH') neq false} selected="selected" {/if}>
                                        {tr}Thursday{/tr}
                                    </option>
                                    <option value="FR" {if strpos($recurrence.monthlyWeekdayValue, 'FR') neq false} selected="selected" {/if}>
                                        {tr}Friday{/tr}
                                    </option>
                                    <option value="SA" {if strpos($recurrence.monthlyWeekdayValue, 'SA') neq false} selected="selected" {/if}>
                                        {tr}Saturday{/tr}
                                    </option>
                                </select>
                                <span class="input-group-text">{tr}of the month{/tr}</span>
                            </div>
                        {/if}
                        {if $recurrence.id eq 0}
                            <div class="text-center py-2"><span>{tr}OR{/tr}</span></div>
                        {/if}
                        {if $recurrence.id eq 0 or $recurrence.monthlyType eq 'firstlastweekday'}
                            <div class="input-group">
                                {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" {if $recurrence.monthlyType eq 'firstlastweekday'}checked="checked"{/if} name="recurrenceTypeMonthy" value="firstlastweekday"></span>{/if}
                                <span class="input-group-text">{tr}Every{/tr}</span>
                                <select name="monthlyFirstLastWeekNumber" class="form-control" {if $recurrence.id neq 0}readonly{/if}>
                                    <option value="1" {if $recurrence.monthlyFirstlastWeekdayValue[0] eq '1'} selected="selected" {/if}>
                                        {tr}First weekday{/tr}
                                    </option>
                                    <option value="-1" {if strpos($recurrence.monthlyFirstlastWeekdayValue, '-1') === 0} selected="selected" {/if}>
                                        {tr}Last weekday{/tr}
                                    </option>
                                </select>
                                <span class="input-group-text">{tr}of the month{/tr}</span>
                            </div>
                        {/if}
                    </div>
                    <hr>
                </div>
            {/if}
            {if $recurrence.id gt 0}
                {if $recurrence.yearly}
                    <input type="hidden" name="recurrenceType" value="yearly">
                    {tr}On a yearly basis{/tr}
                    <br>
                {/if}
            {/if}
            {if $recurrence.id eq 0 or $recurrence.yearly}
                <div class="recurrenceTypeFieldsYearly">
                    <div class="mb-3 px-5">
                        <div class="input-group">
                            <span class="input-group-text">{tr}Every{/tr}</span>
                            <select name="years" class="form-control">
                                {for $i=1 to 20}
                                    <option value="{$i}"{if $recurrence.years == $i} selected="selected" {/if}>
                                        {$i}
                                    </option>
                                {/for}
                            </select>
                            <span class="input-group-text">{tr}year(s){/tr}</span>
                        </div>
                        <hr/>
                    </div>
                    <div class="mb-3 px-5">
                        {if $recurrence.id neq 0}<input type="hidden" name="recurrenceTypeYearly" value="{$recurrence.yearlyType}">{/if}
                        {if $recurrence.id eq 0 or $recurrence.yearlyType eq 'date'}
                            <div class="input-group">
                                {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" {if $recurrence.yearlyType eq 'date'}checked="checked"{/if} name="recurrenceTypeYearly" value="date"></span>{/if}
                                <span class="input-group-text">{tr}Each{/tr}</span>
                                <select name="yearlyDay" class="form-control" onChange="checkDateOfYear(this.options[this.selectedIndex].value,document.forms['f'].elements['yearlyMonth'].options[document.forms['f'].elements['yearlyMonth'].selectedIndex].value);">
                                    {section name=k start=1 loop=32}
                                        <option value="{$smarty.section.k.index}" {if $recurrence.yearlyDay eq $smarty.section.k.index} selected="selected" {/if} >
                                            {if $smarty.section.k.index lt 10}
                                                0
                                            {/if}
                                            {$smarty.section.k.index}
                                        </option>
                                    {/section}
                                </select>
                                <span class="input-group-text">{tr}of{/tr}</span>
                                <select name="yearlyMonth" class="form-control" onChange="checkDateOfYear(document.forms['f'].elements['yearlyDay'].options[document.forms['f'].elements['yearlyDay'].selectedIndex].value,this.options[this.selectedIndex].value);">
                                    <option value="1" {if $recurrence.yearlyMonth eq '1'} selected="selected" {/if}>
                                        {tr}January{/tr}
                                    </option>
                                    <option value="2" {if $recurrence.yearlyMonth eq '2'} selected="selected" {/if}>
                                        {tr}February{/tr}
                                    </option>
                                    <option value="3" {if $recurrence.yearlyMonth eq '3'} selected="selected" {/if}>
                                        {tr}March{/tr}
                                    </option>
                                    <option value="4" {if $recurrence.yearlyMonth eq '4'} selected="selected" {/if}>
                                        {tr}April{/tr}
                                    </option>
                                    <option value="5" {if $recurrence.yearlyMonth eq '5'} selected="selected" {/if}>
                                        {tr}May{/tr}
                                    </option>
                                    <option value="6" {if $recurrence.yearlyMonth eq '6'} selected="selected" {/if}>
                                        {tr}June{/tr}
                                    </option>
                                    <option value="7" {if $recurrence.yearlyMonth eq '7'} selected="selected" {/if}>
                                        {tr}July{/tr}
                                    </option>
                                    <option value="8" {if $recurrence.yearlyMonth eq '8'} selected="selected" {/if}>
                                        {tr}August{/tr}
                                    </option>
                                    <option value="9" {if $recurrence.yearlyMonth eq '9'} selected="selected" {/if}>
                                        {tr}September{/tr}
                                    </option>
                                    <option value="10" {if $recurrence.yearlyMonth eq '10'} selected="selected" {/if}>
                                        {tr}October{/tr}</option>
                                    <option value="11" {if $recurrence.yearlyMonth eq '11'} selected="selected" {/if}>
                                        {tr}November{/tr}
                                    </option>
                                    <option value="12" {if $recurrence.yearlyMonth eq '12'} selected="selected" {/if}>
                                        {tr}December{/tr}
                                    </option>
                                </select>
                            </div>
                        {/if}
                        {if $recurrence.id eq 0}
                            <div class="text-center py-2"><span>{tr}OR{/tr}</span></div>
                        {/if}
                        {if $recurrence.id eq 0 or $recurrence.yearlyType eq 'weekday'}
                            <div class="input-group">
                                {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" {if $recurrence.yearlyType eq 'weekday'}checked="checked"{/if} name="recurrenceTypeYearly" value="weekday"></span>{/if}
                                <span class="input-group-text">{tr}Every{/tr}</span>
                                <select name="yearlyWeekNumber" class="form-control" {if $recurrence.id neq 0}readonly{/if}>
                                    <option value="1" {if $recurrence.yearlyWeekdayValue[0] eq '1'} selected="selected" {/if}>
                                        {tr}First{/tr}
                                    </option>
                                    <option value="2" {if $recurrence.yearlyWeekdayValue[0] eq '2'} selected="selected" {/if}>
                                        {tr}Second{/tr}
                                    </option>
                                    <option value="3" {if $recurrence.yearlyWeekdayValue[0] eq '3'} selected="selected" {/if}>
                                        {tr}Third{/tr}
                                    </option>
                                    <option value="4" {if $recurrence.yearlyWeekdayValue[0] eq '4'} selected="selected" {/if}>
                                        {tr}Fourth{/tr}
                                    </option>
                                    <option value="5" {if $recurrence.yearlyWeekdayValue[0] eq '5'} selected="selected" {/if}>
                                        {tr}Fifth{/tr}
                                    </option>
                                    <option value="-1" {if strpos($recurrence.yearlyWeekdayValue, '-1') === 0} selected="selected" {/if}>
                                        {tr}Last{/tr}
                                    </option>
                                </select>
                                <select name="yearlyWeekday" class="form-control" {if $recurrence.id neq 0}readonly{/if}>
                                    <option value="SU" {if strpos($recurrence.yearlyWeekdayValue, 'SU') neq false} selected="selected" {/if}>
                                        {tr}Sunday{/tr}
                                    </option>
                                    <option value="MO" {if strpos($recurrence.yearlyWeekdayValue, 'MO') neq false} selected="selected" {/if}>
                                        {tr}Monday{/tr}
                                    </option>
                                    <option value="TU" {if strpos($recurrence.yearlyWeekdayValue, 'TU') neq false} selected="selected" {/if}>
                                        {tr}Tuesday{/tr}
                                    </option>
                                    <option value="WE" {if strpos($recurrence.yearlyWeekdayValue, 'WE') neq false} selected="selected" {/if}>
                                        {tr}Wednesday{/tr}
                                    </option>
                                    <option value="TH" {if strpos($recurrence.yearlyWeekdayValue, 'TH') neq false} selected="selected" {/if}>
                                        {tr}Thursday{/tr}
                                    </option>
                                    <option value="FR" {if strpos($recurrence.yearlyWeekdayValue, 'FR') neq false} selected="selected" {/if}>
                                        {tr}Friday{/tr}
                                    </option>
                                    <option value="SA" {if strpos($recurrence.yearlyWeekdayValue, 'SA') neq false} selected="selected" {/if}>
                                        {tr}Saturday{/tr}
                                    </option>
                                </select>
                                <span class="input-group-text">{tr}of{/tr}</span>
                                <select name="yearlyWeekMonth" class="form-control">
                                    <option value="1" {if $recurrence.yearlyWeekMonth eq '1'} selected="selected" {/if}>
                                        {tr}January{/tr}
                                    </option>
                                    <option value="2" {if $recurrence.yearlyWeekMonth eq '2'} selected="selected" {/if}>
                                        {tr}February{/tr}
                                    </option>
                                    <option value="3" {if $recurrence.yearlyWeekMonth eq '3'} selected="selected" {/if}>
                                        {tr}March{/tr}
                                    </option>
                                    <option value="4" {if $recurrence.yearlyWeekMonth eq '4'} selected="selected" {/if}>
                                        {tr}April{/tr}
                                    </option>
                                    <option value="5" {if $recurrence.yearlyWeekMonth eq '5'} selected="selected" {/if}>
                                        {tr}May{/tr}
                                    </option>
                                    <option value="6" {if $recurrence.yearlyWeekMonth eq '6'} selected="selected" {/if}>
                                        {tr}June{/tr}
                                    </option>
                                    <option value="7" {if $recurrence.yearlyWeekMonth eq '7'} selected="selected" {/if}>
                                        {tr}July{/tr}
                                    </option>
                                    <option value="8" {if $recurrence.yearlyWeekMonth eq '8'} selected="selected" {/if}>
                                        {tr}August{/tr}
                                    </option>
                                    <option value="9" {if $recurrence.yearlyWeekMonth eq '9'} selected="selected" {/if}>
                                        {tr}September{/tr}
                                    </option>
                                    <option value="10" {if $recurrence.yearlyWeekMonth eq '10'} selected="selected" {/if}>
                                        {tr}October{/tr}</option>
                                    <option value="11" {if $recurrence.yearlyWeekMonth eq '11'} selected="selected" {/if}>
                                        {tr}November{/tr}
                                    </option>
                                    <option value="12" {if $recurrence.yearlyWeekMonth eq '12'} selected="selected" {/if}>
                                        {tr}December{/tr}
                                    </option>
                                </select>
                            </div>
                        {/if}
                        {if $recurrence.id eq 0}
                            <div class="text-center py-2"><span>{tr}OR{/tr}</span></div>
                        {/if}
                        {if $recurrence.id eq 0 or $recurrence.yearlyType eq 'firstlastweekday'}
                            <div class="input-group">
                                {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" {if $recurrence.yearlyType eq 'firstlastweekday'}checked="checked"{/if} name="recurrenceTypeYearly" value="firstlastweekday"></span>{/if}
                                <span class="input-group-text">{tr}Every{/tr}</span>
                                <select name="yearlyFirstLastWeekNumber" class="form-control" {if $recurrence.id neq 0}readonly{/if}>
                                    <option value="1" {if $recurrence.yearlyFirstlastWeekdayValue[0] eq '1'} selected="selected" {/if}>
                                        {tr}First weekday{/tr}
                                    </option>
                                    <option value="-1" {if strpos($recurrence.yearlyFirstlastWeekdayValue, '-1') === 0} selected="selected" {/if}>
                                        {tr}Last weekday{/tr}
                                    </option>
                                </select>
                                <span class="input-group-text">{tr}of{/tr}</span>
                                <select name="yearlyWeekMonth" class="form-control">
                                    <option value="1" {if $recurrence.yearlyWeekMonth eq '1'} selected="selected" {/if}>
                                        {tr}January{/tr}
                                    </option>
                                    <option value="2" {if $recurrence.yearlyWeekMonth eq '2'} selected="selected" {/if}>
                                        {tr}February{/tr}
                                    </option>
                                    <option value="3" {if $recurrence.yearlyWeekMonth eq '3'} selected="selected" {/if}>
                                        {tr}March{/tr}
                                    </option>
                                    <option value="4" {if $recurrence.yearlyWeekMonth eq '4'} selected="selected" {/if}>
                                        {tr}April{/tr}
                                    </option>
                                    <option value="5" {if $recurrence.yearlyWeekMonth eq '5'} selected="selected" {/if}>
                                        {tr}May{/tr}
                                    </option>
                                    <option value="6" {if $recurrence.yearlyWeekMonth eq '6'} selected="selected" {/if}>
                                        {tr}June{/tr}
                                    </option>
                                    <option value="7" {if $recurrence.yearlyWeekMonth eq '7'} selected="selected" {/if}>
                                        {tr}July{/tr}
                                    </option>
                                    <option value="8" {if $recurrence.yearlyWeekMonth eq '8'} selected="selected" {/if}>
                                        {tr}August{/tr}
                                    </option>
                                    <option value="9" {if $recurrence.yearlyWeekMonth eq '9'} selected="selected" {/if}>
                                        {tr}September{/tr}
                                    </option>
                                    <option value="10" {if $recurrence.yearlyWeekMonth eq '10'} selected="selected" {/if}>
                                        {tr}October{/tr}</option>
                                    <option value="11" {if $recurrence.yearlyWeekMonth eq '11'} selected="selected" {/if}>
                                        {tr}November{/tr}
                                    </option>
                                    <option value="12" {if $recurrence.yearlyWeekMonth eq '12'} selected="selected" {/if}>
                                        {tr}December{/tr}
                                    </option>
                                </select>
                            </div>
                        {/if}
                    </div>
                    <div id="errorDateOfYear" class="text-danger offset-sm-1"></div>
                    <hr>
                </div>
            {/if}
            {tr}Start date{/tr}
            <div class="offset-sm-1 col-sm-6 input-group">
                {if empty($recurrence.startPeriod)}{$startPeriod = $calitem.start}{else}{$startPeriod = $recurrence.startPeriod}{/if}
                {jscalendar id="startPeriod" date=$startPeriod fieldname="startPeriod" align="Bc" showtime='n'}
            </div>
            <hr/>
            <input type="radio" id="id_endTypeNb" name="endType" value="nb" {if $recurrence.nbRecurrences or $calitem.calitemId eq 0 or empty($recurrence.id)} checked="checked" {/if}>
            <label for="id_endTypeNb"> &nbsp;{tr}End after{/tr}</label>
            <div class="offset-sm-1 col-sm-6">
                <div class="input-group">
                    <input type="number" min="1" name="nbRecurrences" class="form-control" value="{if $recurrence.nbRecurrences gt 0}{$recurrence.nbRecurrences}{else}1{/if}">
                    <span class="input-group-text">
                        {if $recurrence.nbRecurrences gt 1}{tr}occurrences{/tr}{else}{tr}occurrence{/tr}{/if}
                    </span>
                </div>
            </div>
            <br>
            <input type="radio" id="id_endTypeDt" name="endType" value="dt" {if $recurrence.endPeriod gt 0} checked="checked" {/if}>
            <label for="id_endTypeDt"> &nbsp;{tr}End before{/tr}
            </label>
            <div class="offset-sm-1 col-sm-6 input-group">
                {jscalendar id="endPeriod" date=$recurrence.endPeriod fieldname="endPeriod" align="Bc" showtime='n'}
            </div>
        </div>
    </div>
</div>
