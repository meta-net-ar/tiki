{remarksbox type="note" title="{tr}Tip{/tr}"}{tr}<b>Orphan preferences </b> are preferences that exist in previous versions of Tiki but for various reasons have been removed. This page allows you to view the values you have configured for these preferences and gives you the option to clear the data if necessary.{/tr}
{/remarksbox}

<div class="text-center mb-4">
    <a href="tiki-admin.php?page=orphanprefs&clear=all" class="btn btn-primary" title="{tr}Delete all{/tr}">{icon name="trash"} {tr}Clear all data{/tr}</a>
</div>

<div id="wpfancytableOrphanPrefs-div" style="visibility: visible;" class="table-responsive ts-wrapperdiv">
    <table class="table table-striped table-hover normal" id="wpfancytableOrphanPrefs" role="grid" aria-describedby="wpfancytableOrphanPrefs_pager_info" style="width: auto; min-width: auto;">
        <thead>
            <tr role="row" class="tablesorter-headerRow">
                <th style="user-select: none;" data-column="0" class="tablesorter-header tablesorter-headerAsc" tabindex="0" scope="col" role="columnheader" aria-disabled="false" aria-controls="wpfancytableOrphanPrefs" unselectable="on" aria-sort="ascending" aria-label="Preferences: Ascending sort applied, activate to apply a descending sort">
                    <div class="tablesorter-header-inner">Preferences <i class="tablesorter-icon"></i></div>
                </th>

                <th style="user-select: none;" data-column="1" class="tablesorter-header tablesorter-headerAsc" tabindex="0" scope="col" role="columnheader" aria-disabled="false" aria-controls="wpfancytableOrphanPrefs" unselectable="on" aria-sort="ascending" aria-label="Values: Ascending sort applied, activate to apply a descending sort">
                    <div class="tablesorter-header-inner">Values <i class="tablesorter-icon"></i></div>
                </th>

                <th style="user-select: none;" data-column="2" class="filter-false tablesorter-header tablesorter-headerUnSorted" tabindex="0" scope="col" role="columnheader" aria-disabled="false" aria-controls="wpfancytableOrphanPrefs" unselectable="on" aria-sort="none" aria-label=": No sort applied, activate to apply an ascending sort">
                    <div class="tablesorter-header-inner"><i class="tablesorter-icon"></i></div>
                </th>
            </tr>
        </thead>

        <tbody aria-live="polite" aria-relevant="all">
            {if ! (empty($orphanPrefs))}
                {foreach from=$orphanPrefs item=pref}
                <tr role="row">
                    <th style="width: 25%;">{$pref.name|escape}</th>
                    <td style="width: 100%;">{$pref.value|escape}</td>
                    <td style="width: 5%;"><a href="tiki-admin.php?page=orphanprefs&clear={$pref.name|escape}" class="tips" title=":{tr}Delete{/tr}">{icon name="trash"}</a></td>
                  </tr>
                {/foreach}
            {else}
                <tr role="row">
                    <td colspan="2" class="text-center" style="width: 100%;"><b>{tr}You have no orphan preferences. All is well !{/tr}</br></td>
                    <td></td>
                <tr>
            {/if}
        </tbody>
    </table>
</div>
<br>
{$msg|escape}
