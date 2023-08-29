{title}{tr}Stats for survey:{/tr} {$survey_info.name}{/title}

<div class="t_navbar mb-4">
    {self_link print='y' _icon_name='print' hspace='1' _class='tips float-end' _title=":{tr}Print{/tr}"}
    {/self_link}
    {button href="tiki-list_surveys.php" class="btn btn-info" _icon_name='list' _text="{tr}List Surveys{/tr}"}
    {button href="tiki-survey_stats.php" class="btn btn-info" _icon_name='chart' _text="{tr}Survey Stats{/tr}"}
    {if $tiki_p_admin_surveys eq 'y'}
        {button _keepall='y' href="tiki-admin_surveys.php" surveyId=$surveyId class="btn btn-primary" _icon_name='edit' _text="{tr}Edit this Survey{/tr}"}
        {button _keepall='y' href="tiki-survey_stats_survey.php" surveyId=$surveyId clear=$surveyId class="btn btn-primary" _icon_name='trash' _text="{tr}Clear Stats{/tr}"}
        {button href="tiki-admin_surveys.php" class="btn btn-primary" _icon_name='cog' _text="{tr}Admin Surveys{/tr}"}
    {/if}
</div>
<br>

{foreach from=$channels item=channel key=key}
    <div class="table-responsive">
        <table class="table ">
            <tr>
                <th colspan="4">{$channel.question|escape|nl2br}</th>
            </tr>
            {if $channel.type eq 'r'}
                <tr>
                    <td class="odd">{tr}Votes:{/tr}</td>
                    <td class="odd">
                        <div class="accordion accordion-flush" id="accordionFlushR{$key}">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseR{$key}" aria-expanded="false" aria-controls="flush-collapseR{$key}">
                                        {$channel.votes}
                                    </button>
                                </h2>
                                <div id="flush-collapseR{$key}" class="accordion-collapse collapse" data-bs-parent="#accordionFlushR{$key}">
                                    <div class="accordion-body">
                                        <ul class="list-group list-group-flush">
                                            {foreach from=$channel.qoptions item=qoption}
                                                {assign var="users" value=[]}
                                                {foreach from=$usersthatvoted item=userthatvoted}
                                                    {if $qoption.optionId == $userthatvoted.optionId}
                                                        {if ! isset($users[$userthatvoted.user])}
                                                            {$users[$userthatvoted.user] = [1, $qoption.qoption]}
                                                        {else}
                                                            {$users[$userthatvoted.user] = [$users[$userthatvoted.user][0] + 1,  $qoption.qoption]}
                                                        {/if}
                                                    {/if}
                                                {/foreach}
                                                {foreach from=$users item=counts key=username}
                                                    <li class="list-group-item">{icon name="user"} {$username|userlink} {tr}answered:{/tr} {$counts[0]} {if $counts[0] == 1}{tr}time{/tr}{else}{tr}times{/tr}{/if} {tr}for the option {/tr} {$counts[1]}</li>
                                                {/foreach}
                                            {/foreach}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="odd">{tr}Average:{/tr}</td>
                    <td class="odd">{$channel.average|string_format:"%.2f"}</td>
                </tr>
            {elseif $channel.type eq 's'}
                <tr>
                    <td class="odd">{tr}Votes:{/tr}</td>
                    <td class="odd">
                        <div class="accordion accordion-flush" id="accordionFlushS{$key}">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseS{$key}" aria-expanded="false" aria-controls="flush-collapseS{$key}">
                                        {$channel.votes}
                                    </button>
                                </h2>
                                <div id="flush-collapseS{$key}" class="accordion-collapse collapse" data-bs-parent="#accordionFlushS{$key}">
                                    <div class="accordion-body">
                                        <ul class="list-group list-group-flush">
                                            {foreach from=$channel.qoptions item=qoption}
                                                {assign var="users" value=[]}
                                                {foreach from=$usersthatvoted item=userthatvoted}
                                                    {if $qoption.optionId == $userthatvoted.optionId}
                                                        {if ! isset($users[$userthatvoted.user])}
                                                            {$users[$userthatvoted.user] = [1, $qoption.qoption]}
                                                        {else}
                                                            {$users[$userthatvoted.user] = [$users[$userthatvoted.user][0] + 1,  $qoption.qoption]}
                                                        {/if}
                                                    {/if}
                                                {/foreach}
                                                {foreach from=$users item=counts key=username}
                                                    <li class="list-group-item">{icon name="user"} {$username|userlink} {tr}answered:{/tr} {$counts[0]} {if $counts[0] == 1}{tr}time{/tr}{else}{tr}times{/tr}{/if} {tr}for the option {/tr} {$counts[1]}</li>
                                                {/foreach}
                                            {/foreach}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="odd">{tr}Average:{/tr}</td>
                    <td class="odd">{$channel.average|string_format:"%.2f"}/10</td>
                </tr>
            {elseif $channel.type neq 'h'}
                {foreach from=$channel.qoptions item=qoption key=j}
                    <tr>
                        <td class="odd">
                            <div class="accordion accordion-flush" id="accordionFlushH{$key}{$j}">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseH{$key}{$j}" aria-expanded="false" aria-controls="flush-collapseH{$key}{$j}">
                                            {if $channel.type eq 'g'}
                                                <div style="float:left">
                                                    {thumb _id=$qoption.qoption _max=40 name='thumb' style='margin:3px;'}
                                                </div>
                                                <div>
                                                    {fileinfo _id=$qoption.qoption _field='name' _link='thumb'}
                                                    <br>{fileinfo _id=$qoption.qoption _field='description'}
                                                </div>
                                            {elseif !$qoption.qoption}
                                                ({tr}no answer{/tr})
                                            {else}
                                                {$qoption.qoption}
                                            {/if}
                                        </button>
                                    </h2>
                                    <div id="flush-collapseH{$key}{$j}" class="accordion-collapse collapse" data-bs-parent="#accordionFlushH{$key}{$j}">
                                        <div class="accordion-body">
                                            <ul class="list-group list-group-flush">
                                                {assign var="users" value=[]}
                                                {foreach from=$usersthatvoted item=userthatvoted}
                                                    {if $userthatvoted.optionId == $qoption.optionId}
                                                        {if ! isset($users[$userthatvoted.user])}
                                                            {$users[$userthatvoted.user] = 1}
                                                        {else}
                                                            {$users[$userthatvoted.user] = $users[$userthatvoted.user] + 1}
                                                        {/if}
                                                    {/if}
                                                {/foreach}
                                                {foreach from=$users item=counter key=username}
                                                <li class="list-group-item">{icon name="user"} {$username|userlink} {tr}answered:{/tr} {$counter} {if $counter == 1}{tr}time{/tr}{else}{tr}times{/tr}{/if}</li>
                                                {/foreach}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="odd"><span class="badge bg-primary rounded-pill">{$qoption.votes}</span></td>
                        <td class="odd">
                            <div class="progress" role="progressbar" aria-label="Example with label" aria-valuenow="{$qoption.width}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-danger" style="width: {$qoption.width}%">{$qoption.average|string_format:"%.2f"}%</div>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            {/if}
        </table>
    </div>
    <br>
{/foreach}
