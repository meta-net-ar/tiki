{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}


{block name="content"}
    <div class="alert alert-light">
        <div class="alert alert-warning">
            {tr}The following menu options have invalid optionId values:{/tr}
        </div>

        <table class="table table-condensed table-striped table-bordered">
            <thead>
            <tr>
                <th>{tr}Option ID{/tr}</th>
                <th>{tr}Name{/tr}</th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$mismatch_options item=option}
                <tr>
                    <td>{$option.optionId|escape}</td>
                    <td>{$option.name|escape}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>

    <form id="menu-form" action="{service controller=menu action=import_menu_options menuId=$menuId}" method="post" enctype="multipart/form-data" class="no-ajax d-flex flex-column flex-wrap">

        <div class="mb-3">
            <label for="csvfile" class="me-2">{tr}Choose a File{/tr}</label>
            <input name="csvfile" type="file" required="required" class="form-control">

            <div class="submit mt-3">
                {ticket mode=confirm}
                <h6>{tr}Re-import the Correct Options{/tr}</h6>
                <input type="submit" name="import" value="{tr}Import{/tr}" id="btn-import" class="btn btn-primary mt-2">
            </div>

            <div class="form-group mt-3">
                <h6>{tr}Or click here to import the mismatched items as new options after uploading the same file{/tr}</h6>
                <button type="submit" name="action" value="import_mismatched_options" id="btn-import-new" class="btn btn-success mt-2">
                    {tr}Import as New Options{/tr}
                </button>
            </div>
        </div>
    </form>

{/block}
