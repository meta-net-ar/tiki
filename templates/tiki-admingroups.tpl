{title help="Groups Management" admpage="login"}{tr}Admin groups{/tr}{/title}
{if !$ts.ajax}
    <div class="t_navbar mb-4">
        {if $groupname && $tiki_p_admin eq 'y'} {* only full admins can manage groups, not tiki_p_admin_users *}
            {if $prefs.feature_tabs ne 'y'}
                {button href="tiki-admingroups.php?add=1&amp;cookietab=2#tab2" class="btn btn-primary" _icon_name="create" _text="{tr}Add New Group{/tr}"}
            {else}
                {button href="tiki-admingroups.php?add=1&amp;cookietab=2" class="btn btn-primary" _icon_name="create" _text="{tr}Add New Group{/tr}"}
            {/if}
        {/if}
        {if $tiki_p_admin eq 'y'} {* only full admins can manage groups, not tiki_p_admin_users *}
            <form method="post" class="d-inline">
                {ticket}
                <input type="hidden" name="clean" value="1">
                <button type="submit" class="btn btn-primary">
                    {icon name="trash"} {tr}Clear cache{/tr}
                </button>
            </form>
        {/if}
        {button href="tiki-adminusers.php" class="btn btn-primary" _type="link" _icon_name="user" _text="{tr}Admin Users{/tr}"}
        {if $tiki_p_admin eq 'y'} {* only full admins can manage groups, not tiki_p_admin_users *}
            {button href="tiki-objectpermissions.php" class="btn btn-link" _type="link" _icon_name="permission" _text="{tr}Permissions{/tr}"}
        {/if}
        {if $prefs.feature_invite eq 'y' and $tiki_p_invite eq 'y'}
            {button href="tiki-list_invite.php" class="btn btn-link" _type="link" _icon_name="thumbs-up" _text="{tr}Invitation List{/tr}"}
        {/if}
    </div>
{/if}
{tabset name='tabs_admingroups'}

{if $tiki_p_admin eq 'y'} {* only full admins can manage groups, not tiki_p_admin_users *}
    {tab name="{tr}List{/tr}"}
    {* ----------------------- tab with list --------------------------------------- *}
        {if !$ts.ajax}
            <h2>{tr}List of existing groups{/tr}</h2>
            {if !$ts.enabled}
                {include file='find.tpl' find_show_num_rows='y'}
                {if $cant_pages > $maxRecords or !empty($initial) or !empty($find)}
                    {initials_filter_links}
                {/if}
            {/if}
            <form id="checkform1" method="post">
            <div class="{if $js}table-responsive {/if}ts-wrapperdiv"> {* table-responsive class cuts off css drop-down menus *}
        {/if}
            <table id="{$ts.tableid}" class="table normal table-striped table-hover" data-count="{$cant_pages|escape}">
                <thead>
                <tr>
                    <th id="checkbox">{select_all checkbox_names='checked[]' tablesorter="{$ts.enabled}"}</th>
                    <th id="id">{self_link _sort_arg='sort_mode' _sort_field='id'}{tr}ID{/tr}{/self_link}</th>
                    <th id="group">{self_link _sort_arg='sort_mode' _sort_field='groupName'}{tr}Name{/tr}{/self_link}</th>
                    <th id="inherits">{tr}Inherits Permissions from{/tr}</th>
                    {if $prefs.useGroupHome eq 'y'}
                        <th id="home">{self_link _sort_arg='sort_mode' _sort_field='groupHome'}{tr}Homepage{/tr}{/self_link}</th>
                    {/if}
                    <th id="choice">{self_link _sort_arg='sort_mode' _sort_field='userChoice'}{tr}User Choice{/tr}{/self_link}</th>
                    <th id="actions"></th>
                </tr>
                </thead>
                <tbody>
                {section name=user loop=$users}
                    {if $groupname == $users[user].groupName}
                        {$href = '#'}
                        {$onclick = "onclick='showTab(2); return false;'"}
                    {else}
                        {$href = "tiki-admingroups.php?group={$users[user].groupName|escape:'url'}"}
                        {$onclick = ''}
                    {/if}
                    <tr>
                        <td class="checkbox-cell">
                            {if $users[user].groupName ne 'Admins' and $users[user].groupName ne 'Anonymous' and $users[user].groupName ne 'Registered'}
                                    <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="checked[]" value="{$users[user].groupName|escape}">
                                {/if}
                        </td>
                        <td class="id">{$users[user].id|escape}</td>
                        <td class="text">
                            <a class="link tips" href="{$href}"{$onclick} title="{tr}Edit group:{/tr}{$users[user].groupName|escape}">
                                {$users[user].groupName|escape}
                            </a>
                            {if $users[user].isTplGroup eq 'y' and $prefs.feature_templated_groups eq 'y'}
                                <sup class="tikihelp" title="{tr}Templated Groups Container{/tr}"> T </sup>
                            {/if}
                            {if $users[user].isRole eq 'y' and $prefs.feature_templated_groups eq 'y'}
                                <sup class="tikihelp" title="{tr}Role Group{/tr}"> R </sup>
                            {/if}
                            <div class="text">{tr}{$users[user].groupDesc|escape|nl2br}{/tr}</div>
                        </td>
                        <td class="text">
                            {foreach $users[user].included as $incl}
                                <div>
                                    {if in_array($incl, $users[user].included_direct)}
                                        {$incl|escape}
                                    {else}
                                        <i>{$incl|escape}</i>
                                    {/if}
                                </div>
                            {/foreach}
                        </td>

                        {if $prefs.useGroupHome eq 'y'}
                            <td class="text">
                                <a class="link" href="{$users[user].groupHome|sefurl}" title="{tr}Group Homepage{/tr}">{tr}{$users[user].groupHome}{/tr}</a>
                            </td>
                        {/if}

                        <td class="text">{tr}{$users[user].userChoice}{/tr}</td>
                        <td class="action">
                            {actions}
                            {strip}
                                <action>
                                    <a href="tiki-admingroups.php?group={$users[user].groupName|escape:"url"}{if $prefs.feature_tabs ne 'y'}#tab2{/if}">
                                        {icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    {permission_link mode=text group=$users[user].groupName count=$users[user].permcant}
                                </action>
                                {if $users[user].groupName ne 'Anonymous' and $users[user].groupName ne 'Registered' and $users[user].groupName ne 'Admins'}
                                    <action>
                                        <a href="{bootstrap_modal controller=group action=remove_groups checked=$users[user].groupName}">
                                            {icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                        </a>
                                    </action>
                                {/if}
                            {/strip}
                            {/actions}
                        </td>
                    </tr>
                {/section}
                </tbody>
            </table>
        {if !$ts.ajax}
                </div>
                    <div class="input-group col-sm-8">
                        <label for="submit_mult" class="col-form-label sr-only">{tr}Select action to perform with checked{/tr}</label>
                            <select name="action" class="form-select">
                                <option value="no_action" selected disabled>{tr}Select action to perform with checked{/tr}...</option>
                                <option value="remove_groups">{tr}Remove{/tr}</option>
                            </select>
                            <input
                                type="submit"
                                form="checkform1"
                                formaction="{service controller=group}"
                                class="btn btn-primary"
                                value="{tr}OK{/tr}"
                                onclick="confirmPopup()"
                            >
                    </div>
            </form>
            {if !$ts.enabled}
                {pagination_links cant=$cant_pages step=$prefs.maxRecords offset=$offset}{/pagination_links}
            {/if}
        {/if}
    {/tab}

    {if $groupname}
        {assign var=tabaddeditgroup_admgrp value="{tr}Edit{/tr}"}
        {$gname = "<i>{$groupname|escape}</i>"}
    {else}
        {assign var=tabaddeditgroup_admgrp value="{tr}Create group{/tr}"}
        {$gname = ""}
    {/if}

    {tab name="<span class='d-block'>{$tabaddeditgroup_admgrp}</span>{$gname}"}
    {* ----------------------- tab with form --------------------------------------- *}
        {if !$ts.ajax}
            {if !empty($user) and $prefs.feature_user_watches eq 'y' && !empty($groupname)}
                <div class="float-sm-end">
                    <form method="post">
                        {ticket}
                        {if not $group_info.isWatching}
                            <input type="hidden" name="watch" value="{$groupname}">
                            {$title = "{$groupname}:{tr}Group is NOT being monitored. Click icon to START monitoring.{/tr}"}
                            {$iconname = 'watch'}
                        {else}
                            <input type="hidden" name="unwatch" value="{$groupname}">
                            {$title = "{$groupname}:{tr}Group IS being monitored. Click icon to STOP monitoring.{/tr}"}
                            {$iconname = 'stop-watching'}
                        {/if}
                        <button type="submit" class="tips btn btn-link" title="{$title}">
                            {icon name="{$iconname}"}
                        </button>
                    </form>
                </div>
            {/if}
            <h2>{$tabaddeditgroup_admgrp}</h2>
            <form action="tiki-admingroups.php" id="groupEdit" method="post">
                <div class="mb-3 row">
                    <label for="groups_group" class="col-form-label col-md-3">{tr}Group{/tr}</label>
                    <div class="col-md-9">
                        {if $groupname neq 'Anonymous' and $groupname neq 'Registered' and $groupname neq 'Admins'}
                            <input type="text" name="name" id="groups_group" value="{$groupname|escape}" class="form-control">
                        {else}
                            <input type="hidden" name="name" id="groups_group" value="{$groupname|escape}">
                            {$groupname|escape}
                        {/if}
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="groups_desc" class="col-form-label col-md-3">{tr}Description{/tr}</label>
                    <div class="col-md-9">
                        <textarea rows="5" name="desc" id="groups_desc" class="form-control">{$groupdesc|escape}</textarea>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="groups_inc" class="col-form-label col-md-3">{tr}Inheritance{/tr}</label>
                    <div class="col-md-9">
                        {if $inc|@count > 20 and $hasOneIncludedGroup eq "y"}
                            <ul>
                                {foreach key=gr item=yn from=$inc}
                                    {if $yn eq 'y'}
                                        <li>{$gr|escape}</li>
                                    {/if}
                                {/foreach}
                            </ul>
                        {/if}
                        <select name="include_groups[]" id="groups_inc" multiple="multiple" size="8" class="form-select">
                            {if !empty($groupname)}
                                <option value="">{tr}None{/tr}</option>{/if}
                            {foreach key=gr item=yn from=$inc}
                                <option value="{$gr|escape}" {if $yn eq 'y'} selected="selected"{/if}>{$gr|truncate:"52"|escape}</option>
                            {/foreach}
                        </select>
                        <div class="form-text">
                            <p>{tr}Permissions will be inherited from these groups.{/tr} {if $prefs.jquery_select2 neq 'y'}{tr}Use Ctrl+Click to select multiple options{/tr}</p>{/if}
                        </div>
                        {if $indirectly_inherited_groups|@count > 0}
                            <p>{tr}Indirectly included groups:{/tr}</p>
                            <ul>
                                {foreach $indirectly_inherited_groups as $gr}
                                    <li>{$gr|escape}</li>
                                {/foreach}
                            </ul>
                        {/if}
                    </div>
                </div>
                {if $prefs.useGroupHome eq 'y'}
                    <div class="mb-3 row">
                        <label for="groups_home" class="col-form-label col-md-3">{tr}Group Home{/tr}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="home" id="groups_home" value="{$grouphome|escape}">
                            {autocomplete element='#groups_home' type='pagename'}
                            <div class="form-text">
                                {tr}Use wiki page name or full URL.{/tr}
                                {tr}For other Tiki features, use links relative to the Tiki root (such as
                                    <em>/tiki-forums.php</em>
                                    ).{/tr}
                            </div>
                        </div>
                    </div>
                {/if}
                {if $prefs.feature_categories eq 'y'}
                    <div class="mb-3 row">
                        <label for="groups_defcat" class="col-form-label col-md-3">{tr}Default Category{/tr}</label>
                        <div class="col-md-9">
                            <select name="defcat" id="groups_defcat" class="form-select">
                                <option value="" {if ($groupdefcat eq "") or ($groupdefcat eq 0)} selected="selected"{/if}>{tr}none{/tr}</option>
                                {foreach $categories as $id=>$category}
                                    <option value="{$id|escape}" {if $id eq $groupdefcat}selected="selected"{/if}>{$category.categpath|escape}</option>
                                {/foreach}
                            </select>
                            <div class="form-text">
                                {tr}Default category assigned to uncategorized objects edited by a user with this default group.{/tr}
                            </div>
                        </div>
                    </div>
                {/if}
                {if $prefs.useGroupTheme eq 'y'}
                    <div class="mb-3 row">
                        <label for="groups_theme" class="col-form-label col-md-3">{tr}Group theme{/tr}</label>
                        <div class="col-md-9">
                            <select name="theme" id="groups_theme" class="form-select">
                                <option value="" {if $grouptheme eq ""} selected="selected"{/if}>{tr}none{/tr}
                                    ({tr}Use site default{/tr})
                                </option>
                                {foreach from=$group_themes key=theme item=theme_name}
                                    <option value="{$theme|escape}" {if $grouptheme eq $theme}selected="selected"{/if}>{$theme_name}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                {/if}
                {if $prefs.feature_conditional_formatting eq 'y'}
                    <div class="mb-3 row">
                        <label for="groups_color" class="col-form-label col-md-3">{tr}Group color{/tr}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="color" id="groups_color" value="{$groupcolor|escape}">
                            <div class="form-text">
                                {tr}Default color to use when plotting values for this group in charts. Use HEX notation, e.g. #FF0000 for red color.{/tr}
                            </div>
                        </div>
                    </div>
                {/if}
                {if $prefs.groupTracker eq 'y'}
                    <div class="mb-3 row">
                        <label for="groupstracker" class="col-form-label col-md-3">{tr}Group Information Tracker{/tr}</label>
                        <div class="col-md-9">
                            <select name="groupstracker" id="groupstracker" class="form-select">
                                <option value="0">{tr}choose a group tracker ...{/tr}</option>
                                {foreach key=tid item=tit from=$trackers}
                                    <option value="{$tid}"{if isset($grouptrackerid) && $tid eq $grouptrackerid} {assign var="ggr" value="$tit"}selected="selected"{/if}>{$tit|escape}</option>
                                {/foreach}
                            </select>
                            <div class="form-text">
                                {tr}Choose a group tracker which can be used to add user registration fields or allow group permissions on a tracker. The tracker must have one user selector field that is set to auto-assign.{/tr}
                            </div>
                            {if isset($grouptrackerid)}
                                <div id="groupfielddiv"{if empty($grouptrackerid) and $prefs.jquery_select2 neq 'y'} style="display: none;"{/if}>
                                    <select name="groupfield" class="form-select">
                                        <option value="0">{tr}choose a field ...{/tr}</option>
                                        {if isset($groupFields)}
                                            {section name=ix loop=$groupFields}
                                                <option value="{$groupFields[ix].fieldId}"{if $groupFields[ix].fieldId eq $groupfieldid} selected="selected"{/if}>{$groupFields[ix].name|escape}</option>
                                            {/section}
                                        {/if}
                                    </select>
                                    <div class="form-text">
                                        {tr}Select the user selector field from the above tracker.{/tr}
                                    </div>
                                </div>
                            {/if}
                            {if isset($grouptrackerid)}
                                {button href=$grouptrackerid|sefurl:'trackerfields' _text="{tr}Admin{/tr} $ggr"}
                            {else}
                                {button href="tiki-list_trackers.php" _text="{tr}Go to trackers list{/tr}"}
                            {/if}
                        </div>
                    </div>
                {/if}
                {if $prefs.userTracker eq 'y'}
                    <div class="mb-3 row">
                        <label for="userstracker" class="col-form-label col-md-3">{tr}User Registration Tracker{/tr}</label>
                        <div class="col-md-9">
                            <select name="userstracker" id="userstracker" class="form-select">
                                <option value="0">{tr}choose a user tracker ...{/tr}</option>
                                {foreach key=tid item=tit from=$trackers}
                                    <option value="{$tid}"{if isset($userstrackerid) && $tid eq $userstrackerid} {assign var="ugr" value="$tit"}selected="selected"{/if}>{$tit|escape}</option>
                                {/foreach}
                            </select>
                            <div class="form-text">
                                {tr}Choose a user tracker to provide fields for a new user to complete upon registration. The tracker must have one user selector field that is set to auto-assign.{/tr}
                            </div>
                            {if (isset($userstrackerid))}
                                <div id="usersfielddiv"{if empty($userstrackerid) and $prefs.jquery_select2 neq 'y'} style="display: none;"{/if}>
                                    <label>{tr}Select user field{/tr}</label> <select name="usersfield" class="form-select">
                                        {if !empty($usersFields)}
                                            <option value="0">{tr}Choose a field ...{/tr}</option>
                                            {section name=ix loop=$usersFields}
                                                <option value="{$usersFields[ix].fieldId}"{if $usersFields[ix].fieldId eq $usersfieldid} selected="selected"{/if}>{$usersFields[ix].fieldId}
                                                    - {$usersFields[ix].name|escape}</option>
                                            {/section}
                                        {else}
                                            <option value="0">{tr}No fields in tracker ...{/tr}</option>
                                        {/if}
                                    </select>
                                    <div class="form-text">
                                        {tr}Select the user selector field from the above tracker to link a tracker item to the user upon registration.{/tr}
                                    </div>
                                </div>
    {jq}
        $("#userstracker, #groupstracker").on("change", function () {
            var $element = this.id,
                $fields = $element == 'userstracker' ? $('select[name=usersfield]') : $('select[name=groupfield]'),
                $showid = $element == 'userstracker' ? '#usersfielddiv' : '#groupfielddiv';
            if ($(this).val() > 0) {
                $.getJSON($.service('tracker', 'list_fields'), {trackerId: $(this).val()}, function (data) {
                    if (data && data.fields) {
                        if (data.fields.length > 0) {
                            $fields.empty().append('<option value="0">{tr}choose a field ...{/tr}</option>');
                            var sel = '';
                            $(data.fields).each(function () {
                                if (this.type === 'u' && this.options_array[0] == 1) {
                                    sel = ' selected="selected"';
                                } else {
                                    sel = '';
                                }
                                $fields.append('<option value="' + this.fieldId + '"' + sel + '>' + this.fieldId + ' - ' + this.name + '</option>');
                            });
                        } else {
                            $fields.empty().append('<option value="0">{tr}No fields in this tracker{/tr}</option>');
                        }
                        $($showid).show();
                        $('#registerfields').show();
                        if (jqueryTiki.select2) {
                            $fields.trigger("change.select2");
                        }
                    }
                });
            } else {
                $fields.empty();
                $($showid).hide();
            }
        });
    {/jq}
                            {/if}
                            {if isset($userstrackerid)}
                                {button href=$userstrackerid|sefurl:'trackerfields' _text="{tr}Admin{/tr} $ugr"}
                            {else}
                                {button href="tiki-list_trackers.php" _text="{tr}Go to tracker list{/tr}"}
                            {/if}
                        </div>
                    </div>
                    {if $prefs.feature_wizard_user eq 'y' and $groupname == 'Registered'}
                        <div class="mb-3 row">
                            <label class="col-form-label col-md-3">{tr}User Wizard Fields{/tr}</label>
                            <div class="col-md-9">
                                {tr}By default, the same fields as in registration are used.{/tr} {tr _0="tiki-admin.php?page=login"}You can choose in the
                                <a href="%0">Login admin
                                    panel</a> to show different fields in User Wizard than the ones asked at Registration Time{/tr}
                                .</td>
                            </div>
                        </div>
                    {/if}
                {/if}
                {if $prefs.userTracker == 'y' || $prefs.useGroupTheme == 'y'}
                    <div id="registerfields" class="mb-3 row"{if empty($userstrackerid) && empty($grouptrackerid) && $prefs.jquery_select2 != 'y'} style="display: none;"{/if}>
                        <label for="registrationUserFieldIds" class="col-form-label col-md-3">{tr}Group or User Tracker Registration Fields{/tr}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="registrationUsersFieldIds" value="{$registrationUsersFieldIds|escape}">
                            <div class="form-text">
                                <p>{tr}If either a group information tracker or user registration tracker has been selected above, enter colon-separated field ID numbers for the tracker fields in the above tracker to include on the registration form for a new user to complete.{/tr}</p>
                            </div>
                        </div>
                    </div>
                {/if}
                {if $groupname neq 'Anonymous' and $groupname neq 'Registered' and $groupname neq 'Admins'}
                    <div class="mb-3 row">
                        <label class="col-form-label col-md-3">{tr}User Choice{/tr}</label>
                        <div class="col-md-9">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" name="userChoice"{if $userChoice eq 'y'} checked="checked"{/if}>
                                    {tr}User can assign himself or herself to the group{/tr}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-md-3">{tr}Membership expiry{/tr}</label>
                        <div class="col-md-9">
                            <label>{tr}Anniversary{/tr}</label>
                        <input type="text" name="anniversary" class="form-control" value="{if is_array($group_info)}{$group_info.anniversary|escape}{else}{/if}">
                            <div class="form-text">{tr}Use MMDD to specify an annual date as of which all users will be unassigned from the group, or DD to specify a monthly date.{/tr}</div>
                            <label>{tr}Or{/tr}</label><br> <label>{tr}Number of Days{/tr}</label>
                        <input type="text" class="form-control" name="expireAfter" value="{if is_array($group_info)}{$group_info.expireAfter|escape}{else}{/if}">
                            <div class="form-text">
                                {tr}Number of days after which all users will be unassigned from the group.{/tr}
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="prorateInterval" class="col-form-label col-md-3">{tr}Pro-rata Membership{/tr}</label>
                        <div class="col-md-9">
                            <select name="prorateInterval" class="form-select">
                                <option value="day" {if is_array($group_info) && $group_info.prorateInterval eq 'day'}selected="selected"{/if}>{tr}Day{/tr}</option>
                                <option value="month" {if is_array($group_info) && $group_info.prorateInterval eq 'month'}selected="selected"{/if}>{tr}Month{/tr}</option>
                                <option value="year" {if is_array($group_info) && $group_info.prorateInterval eq 'year'}selected="selected"{/if}>{tr}Year{/tr}</option>
                            </select>
                            <div class="form-text">
                                {tr}Payment for membership extension is prorated at a minimum interval.{/tr}
                            </div>
                        </div>
                    </div>
                {/if}
                <div class="mb-3 row">
                    <label class="col-form-label col-md-3">{tr}Email Pattern{/tr}</label>
                    <div class="col-md-9">
                        <input class="form-control" type="text" size="40" name="emailPattern" value="{if is_array($group_info)}{$group_info.emailPattern|escape}{else}{/if}">
                        <div class="form-text">
                            <p>{tr}Users are automatically assigned at registration in the group if their emails match the pattern.{/tr}</p>
                            <p>{tr}Example:{/tr} /@(tw.org$)|(tw\.com$)/</p>
                        </div>
                    </div>
                </div>
                {if $prefs.feature_templated_groups eq 'y'}
                    <div class="mb-3 row">
                        <label class="col-form-label col-md-3">{tr}Role Group{/tr}</label>
                        <div class="col-md-9">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" name="isRole"{if $isRole eq 'y'} checked="checked"{/if}>
                                    {tr}This group is used as a role{/tr}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-md-3">{tr}Templated Groups{/tr}</label>
                        <div class="col-md-9">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" name="isTplGroup"{if $isTplGroup eq 'y'} checked="checked"{/if}>
                                    {tr}This group is a container for templated groups{/tr}
                                </label>
                            </div>
                        </div>
                    </div>
                {/if}
                {if $group ne '' and $groupname neq 'Anonymous'}
                    <div class="mb-3 row">
                        <label class="col-form-label col-md-3">{tr}Assign group <em>management</em> permissions{/tr}</label>
                        <div class="col-md-9">
                            {self_link _script="tiki-objectpermissions.php" objectType="group" objectId=$groupname objectName=$groupname permType="group"}
                            {icon _text="{tr}Assign Permissions{/tr}" name="key"}
                            {/self_link}
                        </div>
                    </div>
                {/if}

                <div class="submit mb-3 row">
                    <div class="col-md-9 offset-md-3">
                        {if $group ne ''}
                            <input type="hidden" name="olgroup" value="{$group|escape}">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                form="groupEdit"
                                formaction="{service controller=group action=modify_group}"
                                onclick="confirmPopup()"
                            >
                                {tr}Save{/tr}
                            </button>
                        {else}
                            <button
                                type="submit"
                                class="btn btn-primary"
                                form="groupEdit"
                                formaction="{service controller=group action=new_group}"
                                onclick="confirmPopup()"
                            >
                                {tr}Add{/tr}
                            </button>
                        {/if}
                    </div>
                </div>
                <br><br>

                {if $prefs.groupTracker eq 'y'}
                    <div class="mb-3 row">
                        <div class="col-md-9 offset-md-3">
                            {if !empty($grouptrackerid) and $groupitemid}
                                {tr}Group tracker item : {$groupitemid}{/tr}
                                {button href="tiki-view_tracker_item.php?trackerId=$grouptrackerid&amp;itemId=$groupitemid&amp;show=mod" _text="{tr}Edit Item{/tr}"}
                            {elseif !empty($grouptrackerid)}
                                {if $groupfieldid}
                                    {tr}Group tracker item not found{/tr}
                                    {button href="tiki-view_tracker.php?trackerId=$grouptrackerid" _text="{tr}Create Item{/tr}"}
                                {else}
                                    {tr}Choose a field ...{/tr}
                                {/if}
                            {/if}
                            <br><br>
                        </div>
                    </div>
                {/if}
            </form>
        {/if}
    {/tab}
{/if}

{if $groupname}
    {assign var=tabgroup_memberstabgroup value="{tr}Members{/tr}"}
    {$gname = "{$groupname|escape}"}

    {tab name="<span class='d-block'><span class='badge bg-secondary'>{$membersCount}</span>{$tabgroup_memberstabgroup}</span>{$gname}"}
    {* ----------------------- tab with memberlist --------------------------------------- *}
        <div class="mb-3 row">
            {if $membersCount > 0}
                {if !$ts.ajax}
                    <div class="col-lg-8">
                    <h2>{tr}Members{/tr} <span class="badge bg-secondary">{$membersCount}</span></h2>
                    <form id="checkform2" method="post">
                    <input type="hidden" name="group" value="{$group|escape}"/>
                    <div class="ts-wrapperdiv">
                {/if}
                <table id="groupsMembers" class="table normal table-striped table-hover" {if $tiki_p_admin eq 'y'}data-count="{$membersCount}"{/if}>
                    <thead>
                    <tr>
                        {if $tiki_p_admin eq 'y'}
                            <th id="checkbox" class="auto">{if $memberslist}{select_all checkbox_names='checked[]' tablesorter="{$ts.enabled}"}{/if}</th>
                        {/if}
                        <th id="user">{self_link _sort_arg='sort_mode_member' _sort_field='login'}{tr}User{/tr}{/self_link}</th>
                        <th id="assigned">{self_link _sort_arg='sort_mode_member' _sort_field='created'}{tr}Assigned{/tr}{/self_link}</th>
                        <th id="expires">{self_link _sort_arg='sort_mode_member' _sort_field='expire'}{tr}Expires{/tr}{/self_link}</th>
                        <th id="actions"></th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach from=$memberslist item=member}
                        <tr>
                            {if $tiki_p_admin eq 'y'}
                                <td class="checkbox-cell">
                                    <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="checked[]" value="{$member.login}">
                                </td>
                            {/if}
                            <td class="username">{$member.login|userlink}</td>
                            <td class="date">{if not empty($member.created)}{$member.created|tiki_short_datetime}{/if}</td>
                            <td class="date">{if not empty($member.expire)}{$member.expire|tiki_short_datetime}{/if}</td>
                            <td class="action">
                                {actions}
                                {strip}
                                    {if $tiki_p_admin eq 'y'}
                                        <action>
                                            <a href="tiki-adminusers.php?user={$member.userId|escape:"url"}{if $prefs.feature_tabs ne 'y'}#tab2{/if}">
                                                {icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit user{/tr}"}
                                            </a>
                                        </action>
                                    {/if}
                                    {if $groupname neq 'Registered' and $tiki_p_group_remove_member eq 'y'}
                                        <action>
                                            <a href="{bootstrap_modal controller=user action=manage_groups checked=$member.login groupremove=$groupname anchor='#contenttabs_admingroups-3'}">
                                                {icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Remove from group{/tr}"}
                                            </a>
                                        </action>
                                    {/if}
                                {/strip}
                                {/actions}
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
                {if !$ts.ajax}
                    </div>

                            {if $groupname neq 'Registered' && $tiki_p_admin eq 'y'}
                                <div class="input-group">
                                    <select class="form-select" name="action">
                                        <option value="no_action" selected disabled>
                                            {tr}Select action to perform with checked{/tr}...
                                        </option>
                                        <option value="manage_groups">{tr}Unassign{/tr}</option>
                                    </select>
                                    <input
                                            type="submit"
                                            class="btn btn-primary"
                                            form="checkform2"
                                            formaction="{service controller=user groupremove="$groupname" anchor='#contenttabs_admingroups-3'}"
                                            value="{tr}OK{/tr}"
                                            onclick="confirmPopup(event, true)"
                                        >
                        </div>
                    {/if}
                    </form>
                    </div>
                    {if !$ts.enabled}
                        {pagination_links cant=$membersCount step=$prefs.maxRecords offset=$membersOffset offset_arg='membersOffset'}{/pagination_links}
                    {/if}
                {/if}
            {else}
                <div class="col-lg-8">
                    <h2>{tr}Members{/tr} <span class="badge bg-secondary">{$membersCount}</span></h2>
                    <em>{tr}No members{/tr}</em>
                </div>
            {/if}
                <div class="col-lg-4">
                    <form id="addorban" method="post" action="tiki-admingroups.php">
                        <h2>{tr}{if $tiki_p_admin eq 'y'}Add or ban users{elseif $tiki_p_group_add_member eq 'y'}Add users{/if}{/tr}</h2>
                        {if $tiki_p_group_add_member eq 'y'}
                            <div class="form-group">
                                <select name="user[]" multiple="multiple" size="10" class="{*custom-select*} form-select"  style="width: 100%; display: block;">
                                    {foreach from=$userslist item=iuser}
                                        <option>{$iuser|escape}</option>
                                    {/foreach}
                                </select>
                            </div>
                        {/if}
                        <div>
                            {if $tiki_p_group_add_member eq 'y'}
                                <button
                                    type="submit"
                                    class="btn btn-link tips"
                                    form="addorban"
                                    formaction="{service controller=group action=add_user anchor='#contenttabs_admingroups-3'}"
                                    title=":{tr}Add to group{/tr}"
                                    onclick="confirmPopup(event, true)"
                                >
                                    {icon name=add size=2}
                                </button>
                            {/if}
                            {if $tiki_p_admin eq 'y'}
                                <button
                                    type="submit"
                                    class="btn btn-link tips"
                                    form="addorban"
                                    formaction="{service controller=group action=ban_user anchor='#contenttabs_admingroups-4'}"
                                    title=":{tr}Ban from group{/tr}"
                                    onclick="confirmPopup(event, true)"
                                >
                                    {icon name=ban iclass="alert-danger" size=2}
                                </button>
                            {/if}
                        </div>
                        <input type="hidden" name="group" value="{$groupname|escape}">
                    </form>
                </div>
            </div>
        {/tab}

    {if $tiki_p_admin eq 'y'}
        {assign var="tabgroup_bannedtabgroup" value="{tr}Users banned from{/tr}"}
        {assign var="gname" value="{$groupname|escape}"}

        {assign var="tabgroup_bannedtabgroup" value="{tr}Users banned from{/tr}"}
        {assign var="gname" value="{$groupname|escape}"}

        {tab name="<span class='d-block'><span class='badge bg-secondary'>{$bannedCount} </span>{$tabgroup_bannedtabgroup}</span>{$gname}"}

            {* ----------------------- tab with users banned from group --------------------------------------- *}
                <h2>{tr}Banned members{/tr} <span class="badge bg-secondary">{$bannedCount}</span></h2>
                {if $bannedlist|count > 0}
                    <div class="{if $js}table-responsive {/if}ts-wrapperdiv"> {* table-responsive class cuts off css drop-down menus *}
                        <form id="checkform3" method="post">
                            <table id="bannedMembers" class="table normal table-striped table-hover" data-count="{$bannedCount}">
                                <thead>
                                    <tr>
                                        <th id="checkbox" class="auto">{select_all checkbox_names='user[]' tablesorter="{$ts.enabled}"}</th>
                                        <th id="user">{tr}User{/tr}</th>
                                        <th id="unban">{tr}Unban user{/tr}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$bannedlist item=member}
                                        <tr>
                                            <td class="checkbox-cell">
                                                <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="user[]" value="{$member}">
                                            </td>
                                            <td class="username">{$member|userlink}</td>
                                            <td class="action">
                                                <a href="{bootstrap_modal controller=group action=unban_user user=$member group=$groupname}" class="tips" title=":{tr _0=$member _1=$group}Unban user %0 from group %1{/tr}">
                                                    {icon name="ok"}
                                                </a>
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                            <input type="hidden" name="group" value="{$groupname}">
                    </div>
                {if !$ts.ajax}
                    <div class="input-group col-sm-8">
                        <select class="form-select" name="action">
                            <option value="no_action" selected disabled>
                                {tr}Select action to perform with checked{/tr}...
                            </option>
                            <option value="unban_user">{tr}Unban{/tr}</option>
                        </select>
                        <input
                                type="submit"
                                class="btn btn-primary"
                                form="checkform3"
                                formaction="{service controller=group anchor='#contenttabs_admingroups-4'}"
                                value="{tr}OK{/tr}"
                                onclick="confirmPopup(event, true)"
                            >
                    </div>
                    </form>
                    <br>
                {/if}
            {else}
                <div class="col-sm-12">
                    <em>{tr}No banned members{/tr}</em>
                </div>
                <br>
            {/if}
        {/tab}
    {/if}
{/if}

{if $tiki_p_admin eq 'y'}
    {if $groupname}
        {assign var=tabgroup_importexporttabgroup value="{tr}Import/export{/tr}"}
        {$gname = "{$groupname|escape}"}

        {tab name="<span class='d-block'>{$tabgroup_importexporttabgroup}</span>{$gname}"}

            {* ----------------------- tab with import/export --------------------------------------- *}

            {if !$ts.ajax}
                <form method="post" action="tiki-admingroups.php" enctype="multipart/form-data">
                    {ticket}
                    <input type="hidden" name="group" value="{$groupname|escape}">

                    <h2>{tr}Export group users (CSV file){/tr}</h2>                <br>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">{tr}Charset encoding{/tr}</label>
                        <div class="col-sm-7">
                            <select name="encoding" class="form-select">
                                <option value="UTF-8" selected="selected">{tr}UTF-8{/tr}</option>
                                <option value="ISO-8859-1">{tr}ISO-8859-1{/tr}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">{tr}Fields{/tr}</label>
                        <div class="col-sm-7">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" name="username" checked="checked"> {tr}Username{/tr}
                                </label>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" name="email"> {tr}Email{/tr}
                                </label>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" name="lastLogin"> {tr}Last login{/tr}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label"></label>
                        <div class="col-sm-7">
                            <input type="submit" class="btn btn-primary" name="export" value="{tr}Export{/tr}">
                        </div>
                    </div>
                    <br>
                    <h2>{tr}Import users to group (CSV file){/tr}</h2>                <br>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">
                            {tr}CSV File{/tr}
                            <a title="{tr}Help{/tr}" {popup text='user<br>user1<br>user2'}>{icon name='help'}</a> </label>
                        <div class="col-sm-7">
                        <input name="csvlist" type="file" accept=".csv" class="form-control">
                            <div class="form-text">
                                {tr}Imported users must already exist. To create users and assign them to groups, go to
                                    <a href="tiki-adminusers.php">admin->users</a>
                                    .{/tr}
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label"></label>
                        <div class="col-sm-7">
                            <input type="submit" class="btn btn-primary" name="import" value="{tr}Import{/tr}">
                        </div>
                    </div>
                </form>
            {/if}
        {/tab}
    {/if}
{/if}
{/tabset}
