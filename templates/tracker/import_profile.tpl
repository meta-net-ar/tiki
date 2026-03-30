{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}
{function name="render_editor" content=""}
    {textarea name='yaml' id='importFromProfileYaml' codemirror='true' syntax='yaml' class='form-control' style="height: 400px;" required="required"}{/textarea}
{/function}
{$loadtextarea={render_editor}}
{block name="content"}
    <form class="form" id="forumImportFromProfile" action="{service controller=tracker action=import_profile trackerId=$trackerId confirm=1}" method="post" enctype="multipart/form-data">
        {remarksbox type="warning" title="{tr}Warning{/tr}"}
            {tr}Please note: This is an experimental new feature - work in progress{/tr}
        {/remarksbox}
        <div class="mb-3 row mx-0">
            <label class="col-form-label">{tr}YAML{/tr}</label>
            {$loadtextarea}
        </div>
        <div class="submit text-center">
            {if !$modal}
                <a href="tiki-list_trackers.php" class="btn btn-link" role="button">{tr}Cancel{/tr}</a>
            {/if}
            <input type="submit" class="btn btn-primary" value="{tr}Import{/tr}">
        </div>
    </form>
    {jq}
        $('#forumImportFromProfile').on("submit", function() {
            $.tikiModal(tr('Loading...'));
            $.post($(this).attr('action'), { yaml: $('#importFromProfileYaml').val()}, function(feedback) {
                $.tikiModal();
                if (feedback.length) {
                    for(i in feedback) {
                        showMessage(feedback[i], "success");
                    }
                    document.location = document.location + '';
                } else {
                    showMessage(tr("Error, profile not applied"), "error");
                }
            }, 'json');
            return false;
        });
    {/jq}
{/block}
