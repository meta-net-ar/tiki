{extends $global_extend_layout|default:'layout_plain.tpl'}

{block name="title"}
{title}{tr}User Wizard{/tr}{/title}

{/block}

{block name="content"}
<form action="tiki-wizard_user.php" method="post">
    <div class="col-sm-12">
        {include file="wizard/wizard_bar_user.tpl"}
    </div>
    <hr>
<div id="wizardBody">
    <div class="row">
        {if !empty($wizard_toc)}
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-header font-weight-bold adminWizardTOCTitle">
                        {tr}Wizard Steps{/tr}
                    </div>
                    {$wizard_toc}
                </div>
            </div>
        {/if}
        <div class="{if !empty($wizard_toc)}col-sm-8{else}col-sm-12{/if}">
            {$wizardBody}
        </div>
    </div>
</div>
{include file="wizard/wizard_bar_user.tpl"}
</form>
{/block}
