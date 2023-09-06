<form name="aform" formId='editpageform' action="{$form_action|default:'tiki-take_survey.php'}" method="post">
    <input type="hidden" name="surveyId" value="{$surveyId|escape}">
    <input type="hidden" name="vote" value="yes">
    {if !isset($show_name) or $show_name eq 'y'}
        {title url="tiki-take_survey.php?surveyId=$surveyId"}{$survey_info.name}{/title}
    {/if}
    <div class="surveyquestions">
        <div class="description form-text questionblock page0">{wiki}{$survey_info.description}{/wiki}</div>
        {$pageCount=0}
        {section name=ix loop=$questions}
            {$questionId = 'question_'|cat:$questions[ix].questionId}
            {if empty($smarty.request.$questionId)}{$answer=''}{else}{$answer = $smarty.request.$questionId}{/if}
            {if $questions[ix].type eq 'h' and not empty($questions[ix].explode.0) and $questions[ix].explode.0 eq 'y'}
                {$pageCount=$pageCount+1}
            {/if}
            {$blockClass=' page'|cat:$pageCount}
            <div class="questionblock{$blockClass}">
                {if $questions[ix].type eq 'h'}
                    {$htag = (empty($questions[ix].explode.1)) ? 'h3' : $questions[ix].explode.1}
                    <{$htag}>{$questions[ix].question|escape|nl2br}{if $questions[ix].mandatory eq 'y'} <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>{/if}</{$htag}>
                {else}
                    <div class="quizquestion">{$questions[ix].question|escape|nl2br}{if $questions[ix].mandatory eq 'y'} <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>{/if}</div>
                {/if}
                {if $questions[ix].type eq 'c'}
                    <div class="quizoptions">
                        {section name=jx loop=$questions[ix].qoptions}
                            <label>
                                <input type="radio" value="{$questions[ix].qoptions[jx].optionId|escape}" name="{$questionId}"
                                    {if $answer eq $questions[ix].qoptions[jx].optionId} checked="checked"{/if}>
                                {$questions[ix].qoptions[jx].qoption}
                            </label>
                        {/section}
                    </div>
                {elseif $questions[ix].type eq 't'}
                    <div class="quizoptions">
                        {if !empty($questions[ix].cols)}
                            {assign var='textcols' value=$questions[ix].cols}
                        {else}
                            {assign var='textcols' value=80}
                        {/if}
                        <div class="d-flex flex-row flex-wrap align-items-center">
                            <input type="text" size="{$textcols}" name="{$questionId}" value="{$answer}" class="form-control">
                        </div>
                    </div>
                {elseif $questions[ix].type eq 'x'}
                    {assign var='area' value=$questions[ix].questionId}

                    {if $questions[ix].explode.0 > 0}
                        {assign var='textrows' value=$questions[ix].explode.0}
                    {else}
                        {assign var='textrows' value=20}
                    {/if}

                    {if $questions[ix].explode.1 > 0}
                        {assign var='textcols' value=$questions[ix].explode.1}
                    {else}
                        {assign var='textcols' value=80}
                    {/if}
                    {if !empty($questions[ix].explode.2)}
                        {$showToolBars = ($questions[ix].explode.2 neq 'n')}
                        {$commentToolBar = ($questions[ix].explode.2 eq 'c')?'y':'n'}
                    {else}
                        {$commentToolBar = 'n'}
                    {/if}
                    <div class="quizoptions">
                        <div class="d-flex flex-row flex-wrap align-items-center">
                            {if $showToolBars}{toolbars area_id="question_$area" comments=$commentToolBar}{/if}
                            <textarea id="{$questionId}" name="{$questionId}"
                            rows="{$textrows}" cols="{$textcols}" class="form-control">{$answer}
                            </textarea>
                        </div>
                    </div>
                {elseif $questions[ix].type eq 'm'}
                    {if empty($answer)}{$answer=[]}{/if}
                    <div class="quizoptions form-check">
                        {section name=jx loop=$questions[ix].qoptions}
                            <label class="form-check-label">
                                <input class="form-check-input" type="checkbox" value="{$questions[ix].qoptions[jx].optionId|escape}" name="{$questionId}[{$questions[ix].qoptions[jx].optionId}]"
                                        {if in_array($questions[ix].qoptions[jx].optionId, $answer)}checked="checked"{/if}>
                                {$questions[ix].qoptions[jx].qoption}
                            </label>
                        {/section}
                    </div>
                {elseif $questions[ix].type eq 'r' or $questions[ix].type eq 's'}
                    {assign var="max" value={$questions[ix].options}}
                    <div class="range_container">
                    <input type="range" min="1" max="{$max}" name="{$questionId}" value="{$answer}" class="range_slider">
                    <div class="range_selector">
                        <div class="range_selectBtn"></div>
                        <div class="range_selectValue"></div>
                    </div>
                    <div class="range_progressBar"></div>
                    <div class="range_label">1</div>
                    <div class="range_label">{$max}</div>
                </div>
                
                {elseif $questions[ix].type eq 'g'}
                    {fgal_browse _id=$questions[ix].explode.0 show_selectall='n' show_infos='n' checkbox_label="{tr}Choose{/tr}" file_checkbox_name=$questionId}
                {/if}
            </div>
        {/section}
    </div>
    <div class="navbar">
        {if $pageCount gt 0}
                {button _text="{tr}Previous{/tr}" _class='btn-sm btn-prev'}
                {tr}Page{/tr} <span class="pageNum">0</span> / <span class="pageCount">0</span>
                {button _text="{tr}Next{/tr}" _class='btn-sm btn-next'}
        {/if}
        <input type="submit" class="btn btn-primary btn-sm submit" value="{tr}Send Answers{/tr}" name="ans">
    </div>
</form>
