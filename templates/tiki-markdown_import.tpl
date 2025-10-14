{title help="Markdown import"}{tr}Markdown import{/tr}{/title}
<p class="text-muted">
    {tr}Import markdown files from various sources (local file/zip, FTP directory, or GitHub/GitLab repository) and convert them into Tiki wiki pages.{/tr}
</p>

{tabset name='md_tabs'}

    {* ================== TAB 1: Import now ================== *}
    {tab name="{tr}Import now{/tr}"}

        <form method="post" class="form-horizontal my-3" id="form_source">
            {ticket}

            <h5 class="mb-3">{tr}Source{/tr}</h5>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Source type{/tr}</label>
                <div class="col-sm-9">
                    <select name="source_type" class="form-select" id="source_type">
                        <option value="local" {if $md_source.type=='local'}selected{/if}>{tr}Local (upload){/tr}</option>
                        <option value="ftp"   {if $md_source.type=='ftp'}selected{/if}>{tr}FTP{/tr}</option>
                        <option value="repo"  {if $md_source.type=='repo'}selected{/if}>{tr}Repository (GitHub/GitLab){/tr}</option>
                    </select>
                    <div class="form-text">{tr}Choose where your markdown files will come from.{/tr}</div>
                </div>
            </div>

            <div class="border rounded p-3 mb-3" id="ftp_block">
                <h6 class="mb-2">{tr}FTP settings (used if Source = FTP){/tr}</h6>
                <div class="row mb-2">
                    <label class="col-sm-3 col-form-label">Host</label>
                    <div class="col-sm-9"><input class="form-control" name="ftp_host" value="{$md_source.ftp_host|escape}"></div>
                </div>
                <div class="row mb-2">
                    <label class="col-sm-3 col-form-label">Port</label>
                    <div class="col-sm-3"><input class="form-control" type="number" name="ftp_port" value="{$md_source.ftp_port|default:21|escape}"></div>
                    <label class="col-sm-3 col-form-label">Path</label>
                    <div class="col-sm-3"><input class="form-control" name="ftp_path" value="{$md_source.ftp_path|default:'/'|escape}"></div>
                </div>
                <div class="row mb-2">
                    <label class="col-sm-3 col-form-label">User</label>
                    <div class="col-sm-3"><input class="form-control" name="ftp_user" value="{$md_source.ftp_user|escape}"></div>
                    <label class="col-sm-3 col-form-label">Password</label>
                    <div class="col-sm-3"><input class="form-control" type="password" name="ftp_pass" value="{$md_source.ftp_pass|escape}"></div>
                </div>
                <div class="row">
                    <div class="col-sm-3"></div>
                    <div class="col-sm-9">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="ftp_ssl" {if $md_source.ftp_ssl}checked{/if} id="ftp_ssl">
                            <label for="ftp_ssl" class="form-check-label">SSL/TLS</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="ftp_passive" {if $md_source.ftp_passive|default:true}checked{/if} id="ftp_passive">
                            <label for="ftp_passive" class="form-check-label">{tr}Passive mode{/tr}</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-3" id="repo_block">
                <h6 class="mb-2">{tr}Repository settings (used if Source = Repository){/tr}</h6>
                <div class="row mb-2">
                    <label class="col-sm-3 col-form-label">{tr}Provider{/tr}</label>
                    <div class="col-sm-9">
                        <select name="repo_provider" class="form-select">
                            <option value="github" {if $md_source.repo_provider=='github'}selected{/if}>GitHub</option>
                            <option value="gitlab" {if $md_source.repo_provider=='gitlab'}selected{/if}>GitLab</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-sm-3 col-form-label">{tr}Owner/Namespace{/tr}</label>
                    <div class="col-sm-9"><input class="form-control" name="repo_owner" value="{$md_source.repo_owner|escape}" placeholder="myorg"></div>
                </div>
                <div class="row mb-2">
                    <label class="col-sm-3 col-form-label">{tr}Repository{/tr}</label>
                    <div class="col-sm-9"><input class="form-control" name="repo_name" value="{$md_source.repo_name|escape}" placeholder="myrepo"></div>
                </div>
                <div class="row mb-2">
                    <label class="col-sm-3 col-form-label">{tr}Branch/Ref{/tr}</label>
                    <div class="col-sm-9"><input class="form-control" name="repo_ref" value="{$md_source.repo_ref|default:'main'|escape}"></div>
                </div>
                <div class="row mb-2">
                    <label class="col-sm-3 col-form-label">{tr}Token (read-only){/tr}</label>
                    <div class="col-sm-9">
                        <input class="form-control" name="repo_token" value="{$md_source.repo_token|escape}" placeholder="{tr}Personal Access Token (if required){/tr}">
                    </div>
                </div>
            </div>
        </form>

        <form method="post" class="form-horizontal my-4" id="form_options">
            {ticket}
            <h5 class="mb-3">{tr}Import options{/tr}</h5>
            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Roots{/tr}</label>
                <div class="col-sm-9">
                    <input class="form-control" name="roots" value="{$md_wiki.roots|default:'.'|escape}" placeholder="{tr}. ; docs ; notes{/tr}">
                    <div class="form-text">{tr}Semicolon-separated. Use “.” for root.{/tr}</div>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Recursive{/tr}</label>
                <div class="col-sm-9">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="recursive" name="recursive" {if $md_wiki.recursive|default:true}checked{/if}>
                        <label class="form-check-label" for="recursive">{tr}Scan subfolders{/tr}</label>
                    </div>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Max depth{/tr}</label>
                <div class="col-sm-9">
                    <input class="form-control" type="number" min="0" step="1" name="max_depth" value="{$md_wiki.max_depth|default:0|escape}">
                    <div class="form-text">{tr}0 means unlimited{/tr}</div>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">{tr}Exclude globs{/tr}</label>
                <div class="col-sm-9">
                    <input class="form-control" name="exclude_globs" value="{$md_wiki.exclude_globs|escape}" placeholder="**/node_modules/** ; **/.obsidian/**">
                </div>
            </div>

            <hr>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Title strategy{/tr}</label>
                <div class="col-sm-9">
                    <select name="title_strategy" class="form-select">
                        <option value="fm_h1_filename" {if $md_wiki.title_strategy=='fm_h1_filename'}selected{/if}>{tr}Front-matter → H1 → Filename{/tr}</option>
                        <option value="h1_fm_filename" {if $md_wiki.title_strategy=='h1_fm_filename'}selected{/if}>{tr}H1 → Front-matter → Filename{/tr}</option>
                        <option value="filename_only" {if $md_wiki.title_strategy=='filename_only'}selected{/if}>{tr}Filename only{/tr}</option>
                    </select>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Naming mode{/tr}</label>
                <div class="col-sm-9">
                    <select name="naming_mode" class="form-select">
                        <option value="basename" {if $md_wiki.naming_mode=='basename'}selected{/if}>{tr}Basename{/tr}</option>
                        <option value="prefix"   {if $md_wiki.naming_mode=='prefix'}selected{/if}>{tr}Include N parent folders (prefix){/tr}</option>
                        <option value="suffix"   {if $md_wiki.naming_mode=='suffix'}selected{/if}>{tr}Include N child folders (suffix){/tr}</option>
                    </select>
                    <div class="form-text">{tr}Choose N below for prefix/suffix{/tr}.</div>
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Dir levels{/tr}</label>
                <div class="col-sm-9">
                    <input class="form-control" type="number" min="0" step="1" name="dir_levels" value="{$md_wiki.dir_levels|default:2|escape}">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Separator{/tr}</label>
                <div class="col-sm-9">
                    <input class="form-control" name="separator" value="{$md_wiki.separator|default:' '|escape}">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Namespace (optional){/tr}</label>
                <div class="col-sm-9">
                    <input class="form-control" name="namespace" value="{$md_wiki.namespace|escape}" placeholder="{tr}Applied to all pages{/tr}">
                </div>
            </div>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Markdown flavor{/tr}</label>
                <div class="col-sm-9">
                    <select name="markdown_source" id="markdown_source" class="form-select">
                        <option value="gfm" {if $md_wiki.markdown_source=='gfm'}selected{/if}>GitHub Flavored Markdown (GFM)</option>
                        <option value="commonmark" {if $md_wiki.markdown_source=='commonmark'}selected{/if}>CommonMark</option>
                        <option value="logseq" {if $md_wiki.markdown_source|default:'logseq'=='logseq'}selected{/if}>Logseq</option>
                    </select>
                    <div class="form-text">
                        {tr}Choose the Markdown dialect used by your source. This affects link syntax, tasks and block references processing.{/tr}
                    </div>
                </div>
            </div>

            <hr>

            <div class="row mb-2">
                <label class="col-sm-3 col-form-label">{tr}Detect journal pages{/tr}</label>
                <div class="col-sm-9">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="detect_journal" name="detect_journal" {if $md_wiki.detect_journal|default:true}checked{/if}>
                        <label class="form-check-label" for="detect_journal">{tr}Parse human dates to YYYY-MM-DD{/tr}</label>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">{tr}Date language{/tr}</label>
                <div class="col-sm-9">
                    <select name="journal_lang" class="form-select">
                        <option value="en" {if $md_wiki.journal_lang=='en'}selected{/if}>{tr}English{/tr}</option>
                        <option value="fr" {if $md_wiki.journal_lang=='fr'}selected{/if}>{tr}French{/tr}</option>
                    </select>
                </div>
            </div>
        </form>

        <form method="post" class="my-2" enctype="multipart/form-data" id="form_run">
            {ticket}
            <div class="row g-2 align-items-center mb-2 d-none" id="local_file_row">
                <div class="col-sm-3">
                    <label class="col-form-label">{tr}Local file/zip (optional){/tr}</label>
                </div>
                <div class="col-sm-9">
                    <input type="file" name="local_file" class="form-control" id="local_file_input">
                </div>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-3" id="action_toolbar">
            <button class="btn btn-outline-primary" form="form_source" name="action" value="save_source" id="btn_save_source">
                {tr}Save source{/tr}
            </button>
            <button class="btn btn-outline-secondary" form="form_source" name="action" value="test_source" id="btn_test_source">
                {tr}Test connection{/tr}
            </button>

            <button class="btn btn-primary" form="form_options" name="action" value="save_wiki" id="btn_save_options">
                {tr}Save import options{/tr}
            </button>

            <button class="btn btn-outline-info" form="form_run" name="action" value="dry_run" id="btn_preview">
                {tr}Preview (dry-run){/tr}
            </button>
            <button class="btn btn-success" form="form_run" name="action" value="import_now" id="btn_import">
                {tr}Import now{/tr}
            </button>
        </div>

        {if $md_preview}
            <div class="card my-3">
                <div class="card-header">{tr}Last import Preview{/tr}</div>
                <div class="card-body">
                    <pre style="white-space:pre-wrap">{$md_preview|escape}</pre>
                </div>
            </div>
        {/if}

    {/tab}

    {* ================== TAB 2: Automatic import / Scheduler ================== *}
    {tab name="{tr}Automatic import / Scheduler{/tr}"}

        <div class="alert alert-info d-flex align-items-start gap-3">
            {icon name="information"}
            <div>
                <strong>{tr}Automate imports{/tr}</strong><br>
                {tr}Use the command below in a Tiki Scheduler task. Configure at:{/tr}
                <a href="tiki-admin_schedulers.php?add=1">Tiki Scheduler</a>
            </div>
        </div>

        <h5 class="mb-2 text-primary">{tr}Command (copy & paste){/tr}</h5>

        <div class="mb-3">
            <label class="form-label fw-semibold">{tr}Preview command{/tr}</label>
            <div class="position-relative">
                <textarea id="cmd_preview" class="form-control pe-5" rows="3" readonly>{$md_cli_preview}</textarea>
                <button class="btn btn-sm btn-secondary position-absolute top-0 end-0 m-2" type="button" data-copy="#cmd_preview">
                    {icon name="clipboard"}
                    {tr}Copy{/tr}
                </button>
            </div>
            <div class="form-text">
                {tr}Runs a dry-run and prints JSON output without writing pages.{/tr}
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold">{tr}Import command{/tr}</label>
            <div class="position-relative">
                <textarea id="cmd_import" class="form-control pe-5" rows="3" readonly>{$md_cli_import}</textarea>
                <button class="btn btn-sm btn-secondary position-absolute top-0 end-0 m-2" type="button" data-copy="#cmd_import">
                    {icon name="clipboard"}    
                    {tr}Copy{/tr}
                </button>
            </div>
            <div class="form-text">
                {tr}Writes or updates wiki pages. For local source, replace{/tr} <code>path_to_your_zip_or_md</code> {tr}with an absolute path.{/tr}
                {if $md_cli_sourcetype == 'ftp' || $md_cli_sourcetype == 'repo'}
                    <br /><br /><span class="text-warning">{icon name='warning'} {tr}Credentials are included in the command.{/tr}</span>
                {/if}
            </div>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-success" href="tiki-admin_schedulers.php?add=1" target="_blank">
                {tr}Open Scheduler{/tr}
            </a>
            <a class="btn btn-outline-info" href="https://doc.tiki.org/Scheduler" target="_blank">
                {tr}Scheduler docs{/tr}
            </a>
        </div>

    {/tab}

{/tabset}

{jq}
(function () {
  var $sourceSelect = $('#source_type');

  var $ftpBlock    = $('#ftp_block');
  var $repoBlock   = $('#repo_block');
  var $localRow    = $('#local_file_row');
  var $localInput  = $('#local_file_input');

  var $btnSaveSrc  = $('#btn_save_source');
  var $btnTestSrc  = $('#btn_test_source');
  var $btnSaveOpt  = $('#btn_save_options'); // toujours visible
  var $btnPreview  = $('#btn_preview');
  var $btnImport   = $('#btn_import');

  function updateUI () {
    var t = ($sourceSelect.val() || 'local');

    // Hide all blocks
    $ftpBlock.addClass('d-none');
    $repoBlock.addClass('d-none');
    $localRow.addClass('d-none');

    // By default, hide repo/ftp-only buttons
    $btnSaveSrc.addClass('d-none');
    $btnTestSrc.addClass('d-none');

    if (t === 'local') {
      // LOCAL
      $localRow.removeClass('d-none');
      $localInput.prop('required', false); // laisser facultatif pour dry-run rapide
      $localInput.val(''); // évite de garder un ancien fichier quand on change d’onglet
    } else if (t === 'ftp') {
      // FTP
      $ftpBlock.removeClass('d-none');
      $btnSaveSrc.removeClass('d-none');
      $btnTestSrc.removeClass('d-none');
    } else if (t === 'repo') {
      // REPO
      $repoBlock.removeClass('d-none');
      $btnSaveSrc.removeClass('d-none');
      $btnTestSrc.removeClass('d-none');
    }

    $btnPreview.removeClass('d-none');
    $btnImport.removeClass('d-none');
  }

  $(document).on('click', '[data-copy]', function () {
    var sel = $(this).attr('data-copy');
    var $el = $(sel);
    if (!$el.length) return;
    $el[0].select();
    $el[0].setSelectionRange(0, 99999);
    try {
      document.execCommand('copy');
      $(this).text('{tr}Copied!{/tr}');
      var btn = this;
      setTimeout(function(){ $(btn).text('{tr}Copy{/tr}'); }, 1500);
    } catch(e) {}
  });

  updateUI();
  $sourceSelect.on('change', updateUI);
})();
{/jq}
