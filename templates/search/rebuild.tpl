{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    {if !empty($prefs.unified_last_rebuild)}
        <div class="alert alert-warning">
            <p>{tr _0=$prefs.unified_last_rebuild|tiki_long_datetime}Your index was last fully rebuilt on %0.{/tr}</p>
        </div>
    {/if}

    {if !empty($search_engine)}
        <div class="alert alert-info">
            <p>{tr}Unified search engine:{/tr} <b>{$search_engine}</b>{if !empty($search_version)}, {tr}version{/tr} <b>{$search_version}</b>{/if}{if $search_index}, index <b>{$search_index}</b>{/if}</p>
            {if !empty($fallback_search_engine)}
                <p>{tr}Unified search engine fallback:{/tr} <b>{$fallback_search_engine}</b>{if !empty($fallback_search_version)}, {tr}version{/tr} <b>{$fallback_search_version}</b>{/if}{if $fallback_search_index}, index <b>{$fallback_search_index}</b>{/if}</p>
            {/if}
        </div>
    {/if}

    {if !empty($formattedStats)}
        {$formattedStats}
    {/if}

    {if $showForm}
        <form method="post"{if ! $isAjax}class="no-ajax"{/if} action="{service controller=search action=rebuild}" onsubmit="$(this).parent().tikiModal('{tr}Rebuilding index...{/tr}')">
            <div class="mb-3 row mx-2">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="loggit" id="loggit" value="1">
                    <label class="form-check-label" for="loggit">
                        {tr}Enable logging{/tr}
                    </label>
                    <div class="form-text">{tr _0=$log_file_browser}Log file is saved as %0{/tr}</div>
                    {if !empty($fallback_search_engine)}
                        <div class="form-text">{tr _0=$fallback_log_file_browser}Fallback engine log file is saved as %0{/tr}</div>
                    {/if}
                </div>
            </div>
            <div class="mb-3 submit">
                <input type="submit" class="btn btn-primary" value="{tr}Rebuild{/tr}">
                {if $queue_count > 0}
                    <a class="btn btn-primary" href="{service controller=search action=process_queue}">{tr}Process Queue{/tr} <span class="badge bg-secondary">{$queue_count|escape}</span></a>
                {/if}
            </div>
        </form>

        {* If the indexing succeeded, there are clearly no problems, free up some screen space *}
        {remarksbox type=tip title="{tr}Indexing Problems?{/tr}"}
            <p>{tr}If the indexing does not complete, check the log file to see where it ended.{/tr}</p>
            <p style="overflow-wrap: break-word">{tr}Last line of log file (web):{/tr} <strong>{$lastLogItemWeb|escape}</strong></p>
            <p style="overflow-wrap: break-word">{tr}Last line of log file (console):{/tr} <strong>{$lastLogItemConsole|escape}</strong></p>

            <p>{tr}Common failures include:{/tr}</p>
            <ul>
                <li><strong>{tr}Not enough memory.{/tr}</strong> {tr}Larger sites require more memory to re-index{/tr}.</li>
                <li><strong>{tr}Time limit too short.{/tr}</strong> {tr}It may be required to run the rebuild through the command line{/tr}.</li>
                <li><strong>{tr}High resource usage.{/tr}</strong> {tr}Some plugins in your pages may cause excessive load. Blacklisting some plugins during indexing can help{/tr}.</li>
            </ul>
        {/remarksbox}

        {remarksbox type=tip title="{tr}Command Line Utilities{/tr}"}
            <kbd>php console.php{if not empty($tikidomain)} --site={$tikidomain|replace:'/':''}{/if} index:optimize</kbd><br>
            <kbd>php console.php{if not empty($tikidomain)} --site={$tikidomain|replace:'/':''}{/if} index:rebuild</kbd><br>
            <kbd>php console.php{if not empty($tikidomain)} --site={$tikidomain|replace:'/':''}{/if} index:rebuild --log</kbd><br>
            <p>{tr _0=$log_file_console}Log file is saved as %0{/tr}</p>
            {if !empty($fallback_search_engine)}
                <p>{tr _0=$fallback_log_file_console}Fallback engine log file is saved as %0{/tr}</p>
            {/if}
        {/remarksbox}
    {/if}
{/block}
