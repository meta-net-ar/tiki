{extends $global_extend_layout|default:'layout_plain.tpl'}

{block name="title"}
    {* {title}{tr}Configuration Wizard{/tr}{/title} *}
{/block}

{block name="content"}
<form action="tiki-wizard_admin.php" method="post">
    <div class="col-sm-12">
        {include file="wizard/wizard_bar_admin.tpl"}
    </div>
    <hr>
    <div id="wizardBody">
        <div class="row">
            <div class="w-100">
            {$wizardBody}
            </div>
        </div>
    </div>
    <hr>
    {include file="wizard/wizard_bar_admin.tpl"}
</form>
{/block}
