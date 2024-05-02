{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
<form role="form" method="post" action="{service controller=search_stored action=select}">
    <div class="card">
        <div class="card-header">
            <input id="create_new" type="radio" name="queryId" value="" checked>
            <label for="create_new">
                {tr}Create New{/tr}
            </label>
        </div>
        <div class="card-body">
            <div class="mb-3 row">
                <label for="label" class="col-form-label">{tr}Label{/tr}</label>
                <input type="text" class="form-control" name="label" id="label">
                <span class="form-text">{tr}This will help you recognize your stored queries if ever you want to modify or remove them.{/tr}</span>
            </div>
            <div class="mb-3 row">
                <label for="priority" class="col-form-label">Priority</label>
                <select id="priority" name="priority" class="form-select">
                    {foreach $priorities as $key => $info}
                        <option value="{$key|escape}">{$info.label|escape} - {$info.description|escape}</option>
                    {/foreach}
                </select>
            </div>
            <div class="mb-3 row">
                <label for="description" class="col-form-label">{tr}Description{/tr}</label>
                <textarea class="form-control" id="description" name="description" rows="5" data-codemirror="true" data-syntax="tiki">{$description|escape}</textarea>
            </div>
            <div class="mb-3 row">
                <input type="submit" class="btn btn-primary" value="{tr}Create{/tr}"/>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h4>{tr}Use Existing{/tr}</h4>
        </div>
        <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>{tr}Label{/tr}</th>
                    <th>{tr}Last Modification{/tr}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $queries as $query}
                    <tr>
                        <td>
                            <div class="form-check">
                            <input class="form-check-input" id="queryId" type="radio" name="queryId" value="{$query.queryId|escape}">
                            <label class="form-check-label" for="queryId">
                                 {$query.label|escape}
                                <span class="label {$priorities[$query.priority].class|escape}">{$priorities[$query.priority].label|escape}</span>
                            </label>
                            </div>
                        </td>
                        <td>
                            {if !empty($query.lastModif)}
                                {$query.lastModif|tiki_short_datetime}
                            {else}
                                {tr}Never{/tr}
                            {/if}
                        </td>
                    </tr>
                {foreachelse}
                    <tr>
                        <td>
                            {tr}No stored queries!{/tr}
                        </td>
                        <td>{tr}Never{/tr}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
        <div class="mb-3 row">
            <input type="submit" class="btn btn-primary" value="{tr}Select{/tr}"/>
        </div>
        </div>
    </div>
</form>
{/block}
