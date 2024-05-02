<form action="{$smarty.server.SCRIPT_NAME}?{query}" method="post" class="d-flex flex-row flex-wrap align-items-center">
    <div class="input-group">
        <input type="text" class="form-control" name="{$wp_addfreetag|escape}" aria-label="{tr}Add Tag{/tr}">
        <input type="submit" class="btn btn-primary btn-sm" value="{tr}Add Tag{/tr}">
    </div>
</form>
