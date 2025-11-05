{extends $global_extend_layout|default:'layout_plain.tpl'}

{block name="title"}
    {* {title}{tr}Configuration Wizard{/tr}{/title} *}
{/block}

{block name="content"}
<form action="tiki-wizard_admin.php" method="post">
    <div class="col-sm-12">
        {include file="wizard/wizard_bar_admin.tpl"}
    </div>
    <div id="wizardBody" class="mt-5">
        <div class="row">
            {$wizardBody}
        </div>
    </div>
    {include file="wizard/wizard_bar_admin.tpl"}
</form>
{/block}
