s{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
    {if not empty($info)}
        <div class="rounded bg-dark text-light p-3">{$info|nl2br}</div>
    {else}
        <form method="post" action="{service controller=manager action=setup_clone}" id="tiki-manager-clone-form">
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Do You Want to Upgrade{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help.upgrade}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="upgrade" name="upgrade" data-tiki-admin-child-block=".type_childcontainer">
                        <option></option>
                        <option value="yes">{tr}yes{/tr}</option>
                        <option value="no">{tr}No{/tr}</option>
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Instances Source{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help.source}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="source" name="source" data-tiki-admin-child-block=".type_childcontainer" required>
                    </select>
                    <div class="form-text" id="source_detail">{tr}Some instances are not upgradeable and thus, they are not listed here.{/tr}</div>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Instances Destination{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help.target}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="target" name="target" data-tiki-admin-child-block=".type_childcontainer" required>
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Tiki Branch{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help.branch}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="branch" name="branch" data-tiki-admin-child-block=".type_childcontainer" >
                        <option value=""></option>
                        {foreach item=branch from=$inputValues['branches']}
                            <option value="{$branch|escape}">{$branch}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Time for Backup{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help.time}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input required value="" class="form-control" id="crontime" type="text" name="crontime" placeholder="0 0 * * *">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Prevent using the backup step{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help.direct}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="direct" name="direct" data-tiki-admin-child-block=".type_childcontainer">
                        <option></option>
                        <option value="yes">{tr}Yes{/tr}</option>
                        <option value="no">{tr}No{/tr}</option>
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Use last backup?{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help['use-last-backup']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="use_last_backup" name="use_last_backup" data-tiki-admin-child-block=".type_childcontainer">
                        <option></option>
                        <option value="yes">{tr}Yes{/tr}</option>
                        <option value="no">{tr}No{/tr}</option>
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Keep Backup?{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help['keep-backup']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="keep_backup" name="keep_backup" data-tiki-admin-child-block=".type_childcontainer">
                        <option></option>
                        <option value="yes">{tr}Yes{/tr}</option>
                        <option value="no">{tr}No{/tr}</option>
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Live reindex{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help['live-reindex']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="live_reindex" name="live_reindex" data-tiki-admin-child-block=".type_childcontainer">
                        <option></option>
                        <option value="yes">{tr}Yes{/tr}</option>
                        <option value="no">{tr}No{/tr}</option>
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Skip ReIndex{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help['skip-reindex']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="skip_reindex" name="skip_reindex" data-tiki-admin-child-block=".type_childcontainer">
                        <option></option>
                        <option value="yes">{tr}Yes{/tr}</option>
                        <option value="no">{tr}No{/tr}</option>
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Skip cache warmup?{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}| {tr}{$help['skip-cache-warmup']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="skip_cache_warmup" name="skip_cache_warmup" data-tiki-admin-child-block=".type_childcontainer">
                        <option></option>
                        <option value="yes">{tr}yes{/tr}</option>
                        <option value="no">{tr}No{/tr}</option>
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="clone" value="{tr}Creat Cron Job{/tr}">
                </div>
            </div>
        </form>
    {/if}
    <script>
    let instances = {$inputValues['instances']|@json_encode};
    </script>
{/block}
{jq}
    $(function () {
        if(instances){
            var instance_id= Object.keys(instances)
            var formated = [];
            for(var i = 0, l = instance_id.length; i < l; i++) {
                let instance = instances[instance_id[i]];
                instance["id"] = instance_id[i];
                formated.push(instance)
            }

            $remain_instances =[];
            $filter_instances = [];
            var $mcform = $('#tiki-manager-clone-form');
            var $upgrade = $mcform.find('select[name=upgrade]');
            var $source = $mcform.find('select[name=source]');
            var $source_detail = $("#source_detail");
            var $target = $mcform.find('select[name=target]');
            var $branch = $mcform.find('select[name=branch]');
            $source_detail.hide()
            var upgradable_instances = function(){
                $source.empty();
                $target.empty();
                $source_detail.hide();
                $branch.attr("required",false);

                if (! $upgrade.val()) {
                    return;
                }
                ajaxLoadingShow($source[0]);
                instances = formated;
                if($upgrade.val() === "yes"){
                    instances = formated.filter(instance => (instance.revision != null && instance.revision !=''));
                    $source_detail.show();
                    $branch.attr("required",true);
                }
                var len = instances.length;
                $source.append("<option value='' disabled selected>{tr}Select the source instance{/tr}</option>");
                for(var x = 0; x < len ;x++){
                    $source.append("<option value='"+ instances[x].id +"'>"+ instances[x].name +"</option>");
                }
                ajaxLoadingHide();
            }
            var available_instances = function() {
                $target.empty();
                if (! $source.val()) {
                    return;
                }
                ajaxLoadingShow($target[0]);
                instances = formated.filter(instance => parseInt(instance.id) != parseInt($source.val()));

                var len = instances.length;
                $target.append("<option value='' disabled selected>{tr}Select the target instance{/tr}</option>");
                for(var x = 0; x < len ;x++){
                    $target.append("<option value='"+ instances[x].id +"'>"+ instances[x].name +"</option>");
                }
                ajaxLoadingHide();
            }
            if ($mcform.length > 0) {
                $mcform.find('select[name=upgrade]').on('change', upgradable_instances);
            }

            if ($mcform.length > 0) {
                $mcform.find('select[name=source]').on('change', available_instances);
            }
        }
    });
{/jq}
