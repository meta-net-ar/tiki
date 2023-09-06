<div class="apply_fields">
    <div class="tiki-form-group row">
        <label class="col-form-label col-sm-3">
            {tr}Repository{/tr}
            <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}Click the button next to the field to Load the available profiles from the repository. {/tr}">
                {icon name=information}
            </a>
        </label>
        <div class="col-sm-9">
            <div class="input-group">
                <input value="{$default_repository}" class="form-control" id="repository" type="text" name="repository" aria-label="e.g profiles.tiki.org" aria-describedby="button-load">
                <button class="btn btn-outline-secondary" type="button" id="button-load">Load</button>
            </div>
        </div>

    </div>
    <div class="tiki-form-group row">
        <label class="col-form-label col-sm-3">
            {tr}Profile{/tr}
            <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}Profile To Apply{/tr}">
                {icon name=information}
            </a>
        </label>
        <div class="col-sm-9" id="profile-field">
            <select class="form-control" name="profile">
                <option value="" selected disabled>{tr}Pick one please{/tr}</option>
                {foreach item=profile from=$profiles}
                    <option value="{$profile|escape}">{$profile}</option>
                {/foreach}
            </select>
        </div>

    </div>
</div>
