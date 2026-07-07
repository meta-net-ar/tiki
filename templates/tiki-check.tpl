{title help="Server Check"}{tr}Server Check{/tr}{/title}

{* Include the dashboard template *}
{include file="tiki-check-dashboard.tpl"}

{* Dashboard JavaScript *}
<script type="text/javascript">
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    if (typeof $ !== 'undefined' && typeof $.fn.tooltip !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip({
            html: true,
            placement: 'top',
            trigger: 'hover'
        });
    }
    
    // Add click handler for View Details links using vanilla JS
    var viewDetailsLinks = document.querySelectorAll('.critical-issues-summary a[href^="#"]');
    
    viewDetailsLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            var targetId = this.getAttribute('href').substring(1);
            
            var targetElement = document.getElementById(targetId);
            
            if (targetElement === null) {
                return;
            } else {
                // Prevent default anchor behavior
                e.preventDefault();
                
                // Calculate proper scroll position with offset for fixed header
                var headerOffset = calculateHeaderOffset();
                var elementPosition = targetElement.getBoundingClientRect().top;
                var offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                // Check if target element is in a collapsed section and expand it
                var parentSection = targetElement.closest('.showhide_heading');
                if (parentSection) {
                    var sectionContent = parentSection.nextElementSibling;
                    if (sectionContent && sectionContent.style.display === 'none') {
                        // Trigger click on the heading to expand
                        parentSection.click();
                    }
                }
                
                // Smooth scroll to the target element
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                // Add highlight after scroll completes
                setTimeout(function() {
                    targetElement.classList.add('highlight-row');
                    setTimeout(function() {
                        targetElement.classList.remove('highlight-row');
                    }, 2000);
                }, 800); // Increased delay to let the scroll and expand complete
            }
        });
    });
});

// Helper function to calculate header offset
function calculateHeaderOffset() {
    // Try to find common header elements
    var headerSelectors = [
        'header',
        '.navbar',
        '.navbar-fixed-top',
        '.navbar-fixed-bottom',
        '.topbar',
        '.header',
        '#header',
        '.top-navbar',
        '.main-header'
    ];
    
    for (var i = 0; i < headerSelectors.length; i++) {
        var header = document.querySelector(headerSelectors[i]);
        if (header) {
            var rect = header.getBoundingClientRect();
            if (rect.height > 0) {
                return rect.height + 20; // Add 20px buffer
            }
        }
    }
    
    // Fallback to default offset
    return 80;
}

// Make functions globally available
window.runAllChecks = function() {
    // Show loading state
    var buttons = document.querySelectorAll('.btn-group-vertical .btn');
    buttons.forEach(function(button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running...';
    });
    
    // Reload page to run all checks
    setTimeout(function() {
        window.location.reload();
    }, 1000);
};

window.showCriticalIssues = function() {
    // Scroll to critical issues section or show modal
    var criticalIssuesSummary = document.querySelector('.critical-issues-summary');
    if (criticalIssuesSummary) {
        var targetOffset = criticalIssuesSummary.offsetTop - 100;
        window.scrollTo({
            top: targetOffset,
            behavior: 'smooth'
        });
    }
};

// Auto-refresh dashboard every 5 minutes
setInterval(function() {
    // Only refresh if user is on the page
    if (!document.hidden) {
        $.get('tiki-check.php?ajax=dashboard', function(data) {
            $('.dashboard-summary').html(data);
            // Reinitialize tooltips after AJAX update
            if (typeof $ !== 'undefined' && typeof $.fn.tooltip !== 'undefined') {
                $('[data-toggle="tooltip"]').tooltip({
                    html: true,
                    placement: 'top',
                    trigger: 'hover'
                });
            }
        });
    }
}, 300000); // 5 minutes
</script>

<h2  class="showhide_heading" id="Server_Compatibility">{tr}Server compatibility{/tr} <a href="#Server_compatibility" class="heading-link" aria-label="{tr}Server compatibility{/tr}><span class="icon icon-link fas fa-link "></span></a></h2>

{if ! $is_compatible}
    {remarksbox type='error' title="{tr}Server Compatibility Issue{/tr}" close='n'}
        <p>{tr}The server does not meet the minimum requirements for this Tiki version due to the following issues:{/tr}</p>
        <ul>
            {foreach from=$server_req key=key item=item}
                {if $item.fitness_status === FITNESS_STATUS_BAD}
                    <li>{$key}: {$item.message}</li>
                {/if}
            {/foreach}
        </ul>
        <p>{tr}Please upgrade your server configuration to meet the Tiki requirements.{/tr}</p>
        <p><a href="https://doc.tiki.org/Requirements" target="_blank">{tr}View the Tiki Requirements documentation{/tr}</a></p>
    {/remarksbox}
{/if}

<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>{tr}Properties{/tr}</th>
            <th>{tr}Value{/tr}</th>
            <th>{tr}Fitness{/tr}</th>
            <th>{tr}Explanation{/tr}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$server_req key=key item=item}
            <tr id="Server_Properties_{$key|regex_replace:'/[^a-zA-Z0-9]/':'_'}">
                <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                <td data-th="{tr}Value:{/tr}" class="text">&nbsp;{$item.value}</td>
                <td data-th="{tr}Tiki Fitness:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                </td>
                <td data-th="{tr}Explanation : {/tr}" class="text">{$item.message}</td>
            </tr>
            {foreachelse}
            {norecords _colspan=2}
        {/foreach}
        </tbody>
    </table>
</div>
<h3>{tr}Supported Tiki versions{/tr}</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th>{tr}Tiki Version{/tr}</th>
            <th>{tr}PHP{/tr}</th>
            <th>{tr}MySQL{/tr}</th>
            <th>{tr}MariaDB{/tr}</th>
            <th>{tr}Fitness{/tr}</th>
            <th>{tr}Explanation{/tr}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$available_tiki_properties key=key item=item}
            <tr>
                <th class="text">{$item.name}</th>
                <td class="text">
                    {$item.php.min}{if $item.php.max && $item.php.max != $item.php.min} - {$item.php.max}{elseif !$item.php.max}+{/if}
                </td>
                <td class="text">
                    {$item.mysql.min}{if $item.mysql.max && $item.mysql.max != $item.mysql.min} - {$item.mysql.max}{elseif !$item.mysql.max}+{/if}
                </td>
                <td class="text">
                    {$item.mariadb.min}{if $item.mariadb.max && $item.mariadb.max != $item.mariadb.min} - {$item.mariadb.max}{elseif !$item.mariadb.max}+{/if}
                </td>
                <td class="text">
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                </td>
                <td class="text">{$item.message}</td>
            </tr>
        {foreachelse}
            {norecords _colspan=4}
        {/foreach}
        </tbody>
    </table>
</div>
<div>
    <p>{tr}For more details, check the <a href="https://doc.tiki.org/Requirements" target="_blank">Tiki Requirements</a> documentation.{/tr}</p>
</div>

<h2  class="showhide_heading" id="MySQL_or_MariaDB_Database_Properties">{tr}MySQL or MariaDB Database Properties{/tr} <a href="#MySQL_or_MariaDB_Database_Properties" class="heading-link" aria-label="{tr}MySQL or MariaDB Database Properties{/tr}><span class="icon icon-link fas fa-link "></span></a></h2>
<form method="post" action="tiki-check.php">
<input class="registerSubmit btn btn-primary" type="submit" name="acknowledge" value="{tr}Acknowledge (OK){/tr}">
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th>{tr}Properties{/tr}</th>
            <th>{tr}Value{/tr}</th>
            <th>{tr}Tiki Fitness{/tr}</th>
            <th class="tips" title="{tr}Acknowledge{/tr}">{tr}OK{/tr}</th>
            <th>{tr}Explanation{/tr}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$mysql_properties key=key item=item}
            <tr id="MySQL_or_MariaDB_Database_Properties_{$key|regex_replace:'/[^a-zA-Z0-9]/':'_'}">
                <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                <td data-th="{tr}Value:{/tr}" class="text">&nbsp;{$item.setting}</td>
                <td data-th="{tr}Tiki Fitness:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                </td>
                <td data-th="{tr}OK:{/tr}" class="text">&nbsp;<input type="checkbox" name="{$key}" {if $item.fitness_status eq $FITNESS_STATUS_GOOD}disabled{/if} {if !empty($item.ack)}checked{/if} /></td>
                <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message}</td>
            </tr>
        {foreachelse}
            {norecords _colspan=4}
        {/foreach}
        </tbody>
    </table>
</div>

{if $engineTypeNote}
    {remarksbox type="note" title="{tr}New database engine{/tr}"}{tr}Your website is using a 18.x or higher version of tiki wiki and your database tables are not using the InnoDB database engine, you should consider migrate to InnoDB, that is now the default database engine for Tiki{/tr}{/remarksbox}
{/if}

<h2 class="showhide_heading" id="MySQL_crashed_Tables">{tr}MySQL crashed Tables{/tr}<a href="#MySQL_crashed_Tables" class="heading-link" aria-label="{tr}MySQL crashed Tables{/tr}"<span class="icon icon-link fas fa-link "></span></a></h2>
{remarksbox type="note" title="{tr}Be careful{/tr}"}{tr}The following list is just a very quick look at SHOW TABLE STATUS that tells you, if tables have been marked as crashed. If you are experiencing database problems you should still run CHECK TABLE or myisamchk to make sure{/tr}.{/remarksbox}
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th>{tr}Table{/tr}</th>
            <th>{tr}Comment{/tr}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$mysql_crashed_tables key=key item=item}
            <tr>
                <th class="text">Table name: {$key}</th>
                <td data-th="{tr}Comment:{/tr}" class="text">&nbsp;{$item.Comment}</td>
            </tr>
        {foreachelse}
            {norecords _colspan=2}
        {/foreach}
        </tbody>
    </table>
</div>

<a name="dbmismatches"></a>
<h2 class="showhide_heading" id="Database_mismatches">{tr}Database mismatches{/tr}<a href="#Database mismatches" class="heading-link" aria-label="{tr}Database mismatches{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
{tr}Check for database tables and columns that are not synced with db/tiki.sql{/tr}.<br>
<a href="tiki-check.php?dbmismatches=run&ts={$smarty.now}#dbmismatches" class="btn btn-primary btn-sm" style="margin-bottom: 10px;">{tr}Check{/tr}</a>
{if !empty($diffDatabase)}
    <br />
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th colspan="2">{tr}Tables in database and missing in db/tiki.sql{/tr}</th>
                </tr>
                <tr>
                    <th>{tr}Table{/tr}</th>
                    <th>{tr}Number of records{/tr}</th>
                </tr>
            </thead>
            <tbody>
            {if !empty($diffDbTables)}
                {foreach $diffDbTables as $item}
                <tr>
                    <td class="text">{$item.tableName}</td>
                    <td class="text">
                        {if $item.tableSize > 0}
                            {$item.tableSize}
                        {else}
                            <a href="tiki-check.php?removeTable={$item.tableName}" class="text-danger" onclick="confirmPopup('{tr _0=$item.tableName}Remove the table %0?{/tr}', {$ticket})"> {icon name='trash'} {tr}delete table{/tr}</a>
                        {/if}
                    </td>
                </tr>
                {/foreach}
            {else}
                <td class="text" colspan="2">
                    <span class="text-success">
                        <span class="icon icon-ok fas fa-check-circle "></span> good
                    </span>
                </td>
            {/if}
            </tbody>
        </table>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <tr>
                <th>{tr}Columns in database and missing in db/tiki.sql{/tr}</th>
            </tr>
            {if !empty($diffDbColumns)}
                {foreach from=$diffDbColumns key=key item=item}
                    <tr>
                        <td class="text">{$key}
                            <ul>
                            {foreach from=$diffDbColumns[$key] key=key item=item}
                                <li>{$item}</li>
                            {/foreach}
                            </ul>
                        </td>
                    </tr>
                {/foreach}
            {else}
                <td class="text">
                    <span class="text-success">
                        <span class="icon icon-ok fas fa-check-circle "></span> good
                    </span>
                </td>
            {/if}
        </table>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <tr>
                <th>{tr}Tables in db/tiki.sql and missing in database{/tr}</th>
            </tr>
            {if !empty($diffFileTables)}
                {foreach from=$diffFileTables key=key item=item}
                    <tr>
                        <td class="text">{$item}</td>
                    </tr>
                {/foreach}
            {else}
                <td class="text">
                    <span class="text-success">
                        <span class="icon icon-ok fas fa-check-circle "></span> good
                    </span>
                </td>
            {/if}
        </table>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <tr>
                <th>{tr}Columns in db/tiki.sql and missing in database{/tr}</th>
            </tr>
            {if !empty($diffFileColumns)}
                {foreach from=$diffFileColumns key=key item=item}
                    <tr>
                        <td class="text">{$key}
                            <ul>
                                {foreach from=$diffFileColumns[$key] key=key item=item}
                                    <li>{$item}</li>
                                {/foreach}
                            </ul>
                        </td>
                    </tr>
                {/foreach}
            {else}
                <td class="text">
                    <span class="text-success">
                        <span class="icon icon-ok fas fa-check-circle "></span> good
                    </span>
                </td>
            {/if}
        </table>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <tr>
                <th colspan="4">{tr}Column definition in the database differ from the one from db/tiki.sql{/tr}</th>
            </tr>
            {if !empty($diffColDefs)}
                <tr>
                    <th>{tr}Table Name{/tr}</th>
                    <th>{tr}Column{/tr}</th>
                    <th>{tr}File Definition{/tr}</th>
                    <th>{tr}Database Definition{/tr}</th>
                </tr>
                <tbody>
                {foreach from=$diffColDefs key=tableName item=details}
                    <tr>
                        <td>{$tableName}</td>
                        <td>
                            <ul class="list-unstyled">
                                {$details.columnNames}
                            </ul>
                        </td>
                        <td>
                            <ul class="list-unstyled">
                                {$details.fileDefs|unescape:'html'}
                            </ul>
                        </td>
                        <td>
                            <ul class="list-unstyled">
                                {$details.dbDefs|unescape:'html'}
                            </ul>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            {else}
                <td class="text">
                    <span class="text-success">
                        <span class="icon icon-ok fas fa-check-circle "></span> good
                    </span>
                </td>
            {/if}
        </table>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <tr>
                <th colspan="3">{tr}Index definition in the database differs from the one in db/tiki.sql{/tr}</th>
            </tr>
            {if !empty($diffIndexDefis)}
                <tr>
                    <th>{tr}Table Name{/tr}</th>
                    <th>{tr}File Definition{/tr}</th>
                    <th>{tr}Database Definition{/tr}</th>
                </tr>
                <tbody>
                {foreach from=$diffIndexDefis key=tableName item=item}
                    {if !empty($item.file) || !empty($item.db)}
                        <tr>
                            <td>{$tableName}</td>
                            <td>
                                {if !empty($item.file)}
                                    {$item.file|unescape:'html'}
                                {else}
                                    <span>Not available</span>
                                {/if}
                            </td>
                            <td>
                                {if !empty($item.db)}
                                    {$item.db|unescape:'html'}
                                {else}
                                    <span>Not available</span>
                                {/if}
                            </td>
                        </tr>
                    {/if}
                {/foreach}
                </tbody>
            {else}
                <td class="text">
                    <span class="text-success">
                        <span class="icon icon-ok fas fa-check-circle "></span> good
                    </span>
                </td>
            {/if}
        </table>
    </div>

    {if !empty($dynamicTables)}
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <tr>
                    <th>{tr}Dynamic tables in database{/tr}</th>
                </tr>
                {foreach from=$dynamicTables key=key item=item}
                    <tr>
                        <td class="text">{$item}</td>
                    </tr>
                {/foreach}
            </table>
        </div>
    {/if}
{/if}
<h2 class="showhide_heading" id="Test_sending_emails">{tr}Test sending emails{/tr}<a href="#Test_sending_emails" class="heading-link" aria-label="{tr}Test sending emails{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
{tr}To test if your installation is capable of sending emails please visit the <a href="tiki-install.php">Tiki Installer</a>{/tr}.

<h2 class="showhide_heading" id="Server_Information">{tr}Server Information{/tr}<a href="#Server_Information" class="heading-link" aria-label="{tr}Server Information{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>{tr}Properties{/tr}</th>
            <th>{tr}Value{/tr}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$server_information key=key item=item}
            <tr>
                <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                <td data-th="{tr}Value:{/tr}" class="text">&nbsp;{$item.value}</td>
            </tr>
        {foreachelse}
            {norecords _colspan=2}
        {/foreach}
        </tbody>
    </table>
</div>
<h2 class="showhide_heading" id="Server_Properties">{tr}Server Properties{/tr}<a href="#Server_Properties" class="heading-link" aria-label="{tr}Server Properties{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>{tr}Properties{/tr}</th>
            <th>{tr}Value{/tr}</th>
            <th>{tr}Tiki Fitness{/tr}</th>
            <th class="tips" title="{tr}Acknowledge{/tr}">{tr}OK{/tr}</th>
            <th>{tr}Explanation{/tr}</th>
        </tr>
</thead>
        <tbody>
        {foreach from=$server_properties key=key item=item}
            <tr id="Server_Properties_{$key|regex_replace:'/[^a-zA-Z0-9]/':'_'}">
                <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                <td data-th="{tr}Value:{/tr}" class="text">&nbsp;{$item.setting}</td>
                <td data-th="{tr}Tiki Fitness:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                </td>
                <td data-th="{tr}OK:{/tr}" class="test">&nbsp;<input type="checkbox" name="{$key}" {if $item.fitness_status eq $FITNESS_STATUS_GOOD}disabled{/if} {if !empty($item.ack)}checked{/if} /></td>
                <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message}</td>
            </tr>
        {foreachelse}
            {norecords _colspan=4}
        {/foreach}
        </tbody>
    </table>
</div>

<h2 class="showhide_heading" id="Special_directories">{tr}Special directories{/tr}<a href="#Special_directories" class="heading-link" aria-label="{tr}Special directories{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
{tr}To backup these directories go to <a href="tiki-admin_system.php">Admin->Tiki Cache/SysAdmin</a>{/tr}.
{if count($dirs)}
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>{tr}Directories{/tr}</th>
                <th>{tr}Fitness{/tr}</th>
                <th>{tr}Explanation{/tr}</th>
            </tr>
</thead>
        <tbody>
            {foreach from=$dirs item=d key=k}
                {if $useDatabase[$k] !== 'y'}
                    <tr>
                        <th class="text">Directory: {$d|escape}</th>
                        <td data-th="{tr}Fitness:{/tr}" class="text">&nbsp;
                            {if $dirsWritable[$k]}
                                {icon name='ok' iclass='text-success'}
                            {else}
                                {icon name='remove' iclass='text-danger'}
                            {/if}
                        </td>
                        <td data-th="{tr}Explanation:{/tr} " >
                            {if $dirsWritable[$k]}
                                {tr}Directory is writeable{/tr}.
                            {else}
                                {tr}Directory is not writeable!{/tr}
                            {/if}
                        </td>
                    </tr>
                {/if}
            {/foreach}
             </tbody>
        </table>
    </div>
{/if}

<h2 class="showhide_heading" id="Apache_properties">{tr}Apache properties{/tr}<a href="#Apache_properties" class="heading-link" aria-label="{tr}Apache properties{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
{if $apache_properties}
    <div class="table-responsive">
        <table class="table">
            <thead>
        <tr>
            <th>{tr}Properties{/tr}</th>
            <th>{tr}Value{/tr}</th>
            <th>{tr}Tiki Fitness{/tr}</th>
            <th class="tips" title="{tr}Acknowledge{/tr}">{tr}OK{/tr}</th>
            <th>{tr}Explanation{/tr}</th>
        </tr>
        </thead>
<tbody>
            {foreach from=$apache_properties key=key item=item}
                <tr id="Apache_properties_{$key|regex_replace:'/[^a-zA-Z0-9]/':'_'}">
                    <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                    <td data-th="{tr}Value:{/tr}" class="text">{$item.setting}</td>
                    <td data-th="{tr}Tiki Fitness:{/tr}" class="text">
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                    </td>
                    <td data-th="{tr}OK:{/tr}" class="test">&nbsp;<input type="checkbox" name="{$key}" {if $item.fitness_status eq $FITNESS_STATUS_GOOD}disabled{/if} {if !empty($item.ack)}checked{/if} /></td>
                    <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message}</td>
                </tr>
            {foreachelse}
                {norecords _colspan=4}
            {/foreach}
            </tbody>
        </table>
    </div>
{else}
    {$no_apache_properties}
{/if}

<h2 class="showhide_heading" id="IIS_properties">{tr}IIS properties{/tr}<a href="#IIS_properties" class="heading-link" aria-label="{tr}IIS properties{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
{if $iis_properties}
    <div class="table-responsive">
        <table class="table">
            <thead>
        <tr>
            <th>{tr}Properties{/tr}</th>
            <th>{tr}Value{/tr}</th>
            <th>{tr}Tiki Fitness{/tr}</th>
            <th class="tips" title="{tr}Acknowledge{/tr}">{tr}OK{/tr}</th>
            <th>{tr}Explanation{/tr}</th>
        </tr>
        </thead>
<tbody>
            {foreach from=$iis_properties key=key item=item}
                <tr id="IIS_properties_{$key|regex_replace:'/[^a-zA-Z0-9]/':'_'}">
                    <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                    <td data-th="{tr}Value:{/tr}" class="text">&nbsp;{$item.setting}</td>
                    <td data-th="{tr}Tiki Fitness:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                    </td>
                    <td data-th="{tr}OK:{/tr}" class="test">&nbsp;<input type="checkbox" name="{$key}" {if $item.fitness_status eq $FITNESS_STATUS_GOOD}disabled{/if} {if !empty($item.ack)}checked{/if} /></td>
                    <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message}</td>
                </tr>
            {foreachelse}
                {norecords _colspan=4}
            {/foreach}
            </tbody>
        </table>
    </div>
{else}
    {$no_iis_properties}
{/if}

<h2 class="showhide_heading" id="PHP_scripting_language_properties">{tr}PHP scripting language properties{/tr}<a href="#PHP_scripting_language_properties" class="heading-link" aria-label="{tr}PHP scripting language properties{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>{tr}Properties{/tr}</th>
            <th>{tr}Value{/tr}</th>
            <th>{tr}Tiki Fitness{/tr}</th>
            <th class="tips" title="{tr}Acknowledge{/tr}">{tr}OK{/tr}</th>
            <th>{tr}Explanation{/tr}</th>
        </tr>
        </thead>
<tbody>
        {foreach from=$php_properties key=key item=item}
<tr id="PHP_scripting_language_properties_{$key|regex_replace:'/[^a-zA-Z0-9]/':'_'}">
                    <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                    <td data-th="{tr}Value:{/tr}" class="text">&nbsp;{$item.setting}</td>
                    <td data-th="{tr}Tiki Fitness:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                    </td>
                    <td data-th="{tr}OK:{/tr}" class="test">&nbsp;<input type="checkbox" name="{$key}" {if $item.fitness_status eq $FITNESS_STATUS_GOOD}disabled{/if} {if !empty($item.ack)}checked{/if} /></td>
                    <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message}</td>
                </tr>
        {foreachelse}
            {norecords _colspan=4}
        {/foreach}
        <tbody>
    </table>
</div>

{remarksbox type="note" id="php_conf_info" title="{tr}Change PHP configuration values{/tr}"}
    {if $php_sapi_info}
        <p>
        {if !empty($php_sapi_info.message)}
            {tr}{$php_sapi_info.message}{/tr}
        {/if}
        {if !empty($php_sapi_info.link)}
            <a href="{$php_sapi_info.link}" class="alert-link">{$php_sapi_info.link}</a>
        {/if}
        </p>
    {/if}

    <p>
        {tr _0='<a href="http://www.php.net/manual/en/configuration.php" class="alert-link">http://www.php.net/manual/en/configuration.php</a>'}You can check the full documentation on how to change the configurations values in %0{/tr}
    </p>
{/remarksbox}

<h2 class="showhide_heading" id="PHP_scripting_language_properties">{tr}PHP scripting language properties{/tr}<a href="#PHP_scripting_language_properties" class="heading-link" aria-label="{tr}PHP scripting language properties{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
{tr}To check the file integrity of your Tiki installation, go to <a href="tiki-admin_security.php">Admin->Security</a>{/tr}.
<div class="table-responsive">
    <table class="table"><thead>
        <tr>
            <th>{tr}Properties{/tr}</th>
            <th>{tr}Value{/tr}</th>
            <th>{tr}Tiki Fitness{/tr}</th>
            <th class="tips" title="{tr}Acknowledge{/tr}">{tr}OK{/tr}</th>
            <th>{tr}Explanation{/tr}</th>
        </tr></thead>
        <tbody>

        {foreach from=$security key=key item=item}
            <tr id="Tiki_Security_{$key|regex_replace:'/[^a-zA-Z0-9]/':'_'}">
                    <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                    <td data-th="{tr}Value:{/tr}" class="text">&nbsp;{$item.setting}</td>
                    <td data-th="{tr}Tiki Fitness:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                    </td>
                    <td data-th="{tr}OK:{/tr}" class="test">&nbsp;<input type="checkbox" name="{$key}" {if $item.fitness_status eq $FITNESS_STATUS_GOOD}disabled{/if} {if !empty($item.ack)}checked{/if} /></td>
                    <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message}</td>
                </tr>
        {foreachelse}
            {norecords _colspan=4}
        {/foreach}
        </tbody>
    </table>
</div>
</form>

<h2 class="showhide_heading" id="Tiki_Security">{tr}Tiki Security{/tr}<a href="#Tiki_Security" class="heading-link" aria-label="{tr}Tiki Security{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>

{* Enhanced Security Checks Table *}
{if $tiki_security}
<div class="table-responsive">
    <table class="table">
        <thead>
        <tr>
            <th>{tr}Security Check{/tr}</th>
            <th>{tr}Status{/tr}</th>
            <th>{tr}Explanation{/tr}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$tiki_security key=key item=item}
            <tr id="Tiki_Security_{$key|regex_replace:'/[^a-zA-Z0-9]/':'_'}">
                <th class="text"><span class="only-on-mobile">{tr}Check:{/tr}</span>&nbsp;{$key}</th>
                <td data-th="{tr}Status:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                </td>
                <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>
{/if}

{$sensitive_data_box_title="{tr}Sensitive Data Exposure{/tr}"}
{if $sensitive_data_detected_files}
{remarksbox type='error' title="{$sensitive_data_box_title}" close='n'}
    <p>{tr}Tiki detected that there are temporary files in the db folder which may expose credentials or other sensitive information.{/tr}</p>
    <ul>
        {foreach from=$sensitive_data_detected_files item=file}
            <li>
                {$file}
            </li>
        {/foreach}
    </ul>
{/remarksbox}
{else}
{remarksbox type='info' title="{$sensitive_data_box_title}" close='n'}
    <p>{tr}Tiki did not detect temporary files in the db folder which may expose credentials or other sensitive information.{/tr}</p>
{/remarksbox}
{/if}

{if $prefs.print_pdf_from_url === "mpdf" && !empty($mPDFClassMissing)}
    <h2 class="showhide_heading" id="Print_configurations">{tr}Print configurations{/tr}<a href="#Print_configurations" class="heading-link" aria-label="{tr}Print configurations{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
    {remarksbox type='error' title="{tr}mPDF Information{/tr}" close='n'}
        <p>{tr}mPDF is selected as Print option, however the class can't be loaded, please check "Print Settings" in /tiki-admin.php?page=print{/tr}</p>
    {/remarksbox}
{/if}

<h2 class="showhide_heading" id="File_Gallery_Search_Indexing">{tr}File Gallery Search Indexing{/tr}<a href="#File_Gallery_Search_Indexing" class="heading-link" aria-label="{tr}File Gallery Search Indexing{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
{icon name='help' href='https://doc.tiki.org/Search+within+files'} <em>{tr _0='<a href="https://doc.tiki.org/Search+within+files">' _1='</a>'}More information %0 here %1{/tr}</em>
{if $prefs.fgal_enable_auto_indexing eq 'y'}
    {if $security.shell_exec.setting eq 'Disabled'}
        {remarksbox type='error' title='{tr}Command Missing{/tr}' close='n'}
            <p>{tr}The command "shell_exec" is required for file gallery search indexing{/tr}</p>
        {/remarksbox}
    {/if}
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>{tr}MIME types{/tr}</th>
                <th>{tr}Tiki Fitness{/tr}</th>
                <th>{tr}Explanation{/tr}</th>
            </tr></thead>
        <tbody>

            {foreach from=$file_handlers key=key item=item}
                <tr>
                    <th class="text">Mime type: {$key}</th>
                    <td data-th="{tr}Tiki Fitness:{/tr} " lass="text">
                        <span class="text-{$fmap[$item.fitness_status]['class']}">
                            {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                        </span>
                    </td>
                    <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message|escape}</td>
                </tr>
            {foreachelse}
                {norecords _colspan=3}
            {/foreach}
        </tbody></table>
    </div>
{else}
    {remarksbox type='info' title='{tr}Feature disabled{/tr}' close='n'}
        <p>{tr _0='<a href="tiki-admin.php?page=fgal" class="alert-link">' _1='</a>'}Go to the %0 File Gallery Control Panel %1 (with advanced preferences showing) to enable{/tr}</p>
    {/remarksbox}
{/if}

<h2 class="showhide_heading" id="MySQL_Variable_Information">{tr}MySQL Variable Information{/tr}<a href="#MySQL_Variable_Information" class="heading-link" aria-label="{tr}MySQL Variable Information{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<div class="table-responsive">
    <table class="table table-striped table-hover"><thead>
        <tr>
            <th>{tr}Properties{/tr}</th>
            <th>{tr}Value{/tr}</th>
        </tr></thead>
        <tbody>

        {foreach from=$mysql_variables key=key item=item}
            <tr>
                <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                <td data-th="{tr}Value:{/tr}" class="text">&nbsp;{$item.value|escape}</td>
            </tr>
        {foreachelse}
            {norecords _colspan=2}
        {/foreach}</tbody>
    </table>
</div>

<h2 class="showhide_heading" id="PHP_Info">{tr}PHP Info{/tr}<a href="#PHP_Info" class="heading-link" aria-label="{tr}PHP Info{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
{tr}For more detailed information about your PHP installation see <a href="tiki-phpinfo.php">Admin->phpinfo</a>{/tr}.

<a name="benchmark"></a>
<h2 class="showhide_heading" id="Benchmark_PHP/MySQL">{tr}Benchmark PHP/MySQL{/tr}<a href="#Benchmark_PHP/MySQL" class="heading-link" aria-label="{tr}Benchmark PHP/MySQL{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<a href="tiki-check.php?benchmark=run&ts={$smarty.now}#benchmark" class="btn btn-primary btn-sm" style="margin-bottom: 10px;">{tr}Check{/tr}</a>
{if !empty($benchmark)}
    <br />
    <div class="table-responsive">
        <table class="table table-striped table-hover">
             <thead>
            <tr>
                <th>{tr}Properties{/tr}</th>
                <th>{tr}Seconds{/tr}</th>
            </tr></thead>
        <tbody>

            {foreach from=$benchmark key=key item=item}
                <tr>
                    <th class="text"><span class="only-on-mobile">{tr}Property:{/tr}</span>&nbsp;{$key}</th>
                    <td data-th="{tr}Seconds:{/tr}" class="text">&nbsp;{$item.value}</td>
                </tr>
            {/foreach}</tbody>
        </table>
    </div>
{/if}

<a name="bomscanner"></a>
<h2 class="showhide_heading" id="BOM_Detected_Files">{tr}BOM Detected Files{/tr}<a href="#BOM_Detected_Files" class="heading-link" aria-label="{tr}BOM Detected Files{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<a href="tiki-check.php?bomscanner=run&ts={$smarty.now}#bomscanner" class="btn btn-primary btn-sm" style="margin-bottom: 10px;">{tr}Check{/tr}</a>
{if $bomscanner}
    <p>{tr}Scanned files:{/tr} {$bom_total_files_scanned}</p>
    {if ! empty($bom_detected_files)}
        <p>{tr}BOM files detected:{/tr}</p>
        <ul>
            {foreach from=$bom_detected_files item=file}
                <li>
                    {$file}
                </li>
            {/foreach}
        </ul>
    {else}
        <p><span class="icon icon-information fas fa-info-circle fa-fw"></span>&nbsp;<b>{tr}No BOM files detected{/tr}</b></p>
    {/if}
{/if}

<h2 class="showhide_heading" id="Tiki_Manager">{tr}Tiki Manager{/tr}<a href="#Tiki_Manager" class="heading-link" aria-label="{tr}Tiki Manager{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
{tr}For more detailed information about Tiki Manager please check <a href="https://doc.tiki.org/Manager">doc.tiki.org</a>{/tr}.

{if trim_capable}
    <h3>{tr}Server Instance{/tr}</h3>
    <div class="table-responsive">
        <table class="table table-striped"><thead>
            <tr>
                <th>{tr}Requirements{/tr}</th>
                <th>{tr}Status{/tr}</th>
                <th>{tr}Message{/tr}</th>
            </tr></thead>
        <tbody>
            {foreach from=$trim_server_requirements key=key item=item}
                <tr>
                    <th class="text">{tr}Requirement:{/tr} {$key}</th>
                    <td data-th="{tr}Status:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                    </td>
                    <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message}</td>
                </tr>
            {/foreach}
        </tbody></table>
    </div>

    <h3>{tr}Client Instance{/tr}</h3>
    <div class="table-responsive">
        <table class="table table-striped"><thead>
            <tr>
                <th>{tr}Requirements{/tr}</th>
                <th>{tr}Status{/tr}</th>
                <th>{tr}Message{/tr}</th>
            </tr></thead>
        <tbody>
            {foreach from=$trim_client_requirements key=key item=item}
                <tr>
                    <th class="text">{tr}Requirement:{/tr} {$key}</th>
                    <td data-th="{tr}Status:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                    </td>
                    <td data-th="{tr}Explanation:{/tr}" class="text">&nbsp;{$item.message}</td>
                </tr>
            {/foreach}
        </tbody></table>
    </div>
{else}
    {remarksbox type='error' title="{tr}OS not supported{/tr}" close='n'}
        <p>{tr}Apparently tiki is running on a Windows based server. This feature is not supported natively.{/tr}</p>
    {/remarksbox}
{/if}

<h2 class="showhide_heading" id="User_Data_Encryption">{tr}User Data Encryption{/tr}<a href="#User_Data_Encryption" class="heading-link" aria-label="{tr}User Data Encryption{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<div class="table-responsive">
    <table class="table"><thead>
        <tr>
            <th>{tr}Encryption Method{/tr}</th>
            <th>{tr}Encrypted Preferences{/tr}</th>
            <th>{tr}Message{/tr}</th>
        </tr></thead>
        <tbody>
        {foreach from=$user_encryption_stats key=method item=stats}
            <tr>
                <th class="text">{tr}Encryption Method:{/tr} {$method}</th>
                <td data-th="{tr}Encrypted Preferences:{/tr}" class="text">&nbsp;{$stats}</td>
                <td data-th="{tr}Message:{/tr}" class="text">&nbsp;
                    {if ($method eq 'MCrypt' or $method eq 'OpenSSL') and $stats > 0}
                        <p>{tr _0=$method}If %0 library gets removed, non-converted user encrypted data can no longer be decrypted. The data is
                            thus lost and must be re-entered.{/tr}</p>
                    {/if}
                </td>
            </tr>
        {foreachelse}
            {norecords _colspan=2}
        {/foreach}
    </tbody></table>
</div>

<h2 class="showhide_heading" id="Tiki_Packages">{tr}Tiki Packages{/tr}<a href="#Tiki_Packages" class="heading-link" aria-label="{tr}Tiki Packages{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<div class="table-responsive">
    <table class="table"><thead>
        <tr>
            <th>{tr}Requirements{/tr}</th>
            <th>{tr}Status{/tr}</th>
            <th>{tr}Message{/tr}</th>
        </tr></thead><tbody>
        {foreach from=$composer_checks key=key item=item}
                <tr>
                    <th class="text">{tr}Requirement:{/tr} {$key}</th>
                    <td data-th="{tr}Status:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"}&nbsp;{$item.fitness}
                    </span>
                    </td>
                    <td data-th="{tr}Message:{/tr}" class="text">&nbsp;{$item.message}</td>
                </tr>
            {/foreach}
    </tbody></table>
</div>

{if ! $composer_available}
    {remarksbox type="warning" title="{tr}Composer not found{/tr}"}
        {tr}Composer could not be executed, so the automated check on the packages cannot be performed.{/tr}
    {/remarksbox}
{/if}
<div class="table-responsive">
    <table class="table">
        <thead><tr>
            <th>{tr}Package Name{/tr}</th>
            <th>{tr}Version{/tr}</th>
            <th>{tr}Status{/tr}</th>
            <th>{tr}Message{/tr}</th>
        </tr>
</thead><tbody>
        {foreach from=$packages key=key item=item}
            <tr>
                <th class="text">{tr}Package name:{/tr} {$item.name}</th>
                <td data-th="{tr}Version:{/tr}" class="text">&nbsp;{$item.version}</td>
                <td data-th="{tr}Status:{/tr}" class="text">&nbsp;
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"} {$item.status}
                    </span>
                </td>
                <td data-th="{tr}Message:{/tr}" class="text">&nbsp;
                    {foreach from=$item.message key=message_key item=message}
                        {$message}<br/>
                    {/foreach}
                </td>
            </tr>
        {foreachelse}
            {norecords _colspan=4}
        {/foreach}
   </tbody> </table>
</div>


<h2 class="showhide_heading" id="OCR_Status">{tr}OCR Status{/tr}<a href="#OCR_Status" class="heading-link" aria-label="{tr}OCR Status{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<div class="table-responsive">
    <table class="table"><thead>
        <tr>
            <th>{tr}Requirements{/tr}</th>
            <th>{tr}Version{/tr}</th>
            <th>{tr}Status{/tr}</th>
            <th>{tr}Message{/tr}</th>
        </tr>
</thead><tbody>
        {foreach from=$ocr key=key item=item}
            <tr>
                <th class="text">{tr}Requirement:{/tr} {$item.name}</th>
                <td data-th="{tr}Version:{/tr}" class="text">&nbsp;{$item.version}</td>
                <td data-th="{tr}Status:{/tr} " data-th="{tr}Version : {/tr}" class="text">
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"} {$item.status}
                    </span>
                </td>
                <td data-th="{tr}Message:{/tr}" class="text">&nbsp;{$item.message}</td>
            </tr>
        {/foreach}
   </tbody> </table>

{*Additional table style - Dashboard and tooltip styles moved to themes/base_files/feature_css/tiki-check.css *}
<style type="text/css">
{literal}
table {
  border-spacing: 0;
}
td,th {
  padding: 0.5em;
}

th {
  font-weight: bold;
  text-align: left;
}
td > div {
  float: right;
}
.only-on-mobile{
    display:none;
}

/* fold columns into rows when we have mobile screens. */
@media only screen and (max-width: 40em) {
  thead th:not(:first-child) {
    display: none;
  }
  td, th {
    display: block;
    clear: both;
  }
  td[data-th]:before {
    content: attr(data-th);
    float: left;
  }.only-on-mobile{
    display:inline!important;
}
}
{/literal}
</style>
</div>

<h2 class="showhide_heading" id="Realtime_Tiki">{tr}Realtime Tiki{/tr}<a href="#Realtime_Tiki" class="heading-link" aria-label="{tr}Realtime Tiki{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<div class="table-responsive">
    <table class="table">
    <thead>
        <tr>
            <th>{tr}Requirements{/tr}</th>
            <th>{tr}Status{/tr}</th>
            <th>{tr}Message{/tr}</th>
        </tr>
    </thead>
    <tbody>
        {foreach $realtime as $key => $item}
        <tr id="js-{$key}">
            <th class="text">{tr}{$item.requirement}{/tr}</th>
            <td data-th="{tr}Status:{/tr}" class="text">
                {if $item.status eq 'js'}
                    <span class="text-{$fmap['good']['class']} js-good d-none">
                        {icon name="{$fmap['good']['icon']}"} good
                    </span>
                    <span class="text-{$fmap['bad']['class']} js-bad d-none">
                        {icon name="{$fmap['bad']['icon']}"} bad
                    </span>
                {else}
                    <span class="text-{$fmap[$item.fitness_status]['class']}">
                        {icon name="{$fmap[$item.fitness_status]['icon']}"} {$item.status}
                    </span>
                {/if}
            </td>
            <td data-th="{tr}Message:{/tr}" data-message-good="{$item.message_good}" data-message-bad="{$item.message_bad}" class="text">&nbsp;{tr}{$item.message}{/tr}</td>
        </tr>
        {/foreach}
    </tbody>
    </table>
{jq}
var ws, ws_status_update = function(req, status) {
    document.querySelectorAll('#js-' + req + ' .js-good, #js-' + req + ' .js-bad').forEach(function(el) {
        el.classList.add('d-none');
    });
    document.querySelectorAll('#js-' + req + ' .js-' + status).forEach(function(el) {
        el.classList.remove('d-none');
    });
    $('#js-' + req + ' td').last().text($('#js-' + req + ' td').last().data('message-' + status));
}
ws_status_update('connectivity', 'bad');
ws_status_update('message_exchange', 'bad');
try {
    ws = new WebSocket("{{$realtime_url}}ping");
} catch (e) {
    ws = null;
}
if (ws) {
    ws.onmessage = function(e) {
        if (e.data.trim() == 'pong') {
            ws_status_update('message_exchange', 'good');
        } else {
            ws_status_update('message_exchange', 'bad');
        }
    };
    ws.onopen = function(e) {
        ws_status_update('connectivity', 'good');
        ws.send('ping');
    };
    ws.onerror = function(e) {
        ws_status_update('connectivity', 'bad');
    };
}
{/jq}
</div>

<h2 class="showhide_heading" id="System_Locales">{tr}System Locales{/tr}<a href="#System_Locales" class="heading-link" aria-label="{tr}System Locales{/tr}"><span class="icon icon-link fas fa-link "></span></a></h2>
<div class="table-responsive">
    <table class="table table-striped table-hover"><thead>
        <tr>
            <th>{tr}Locale{/tr}</th>
        </tr></thead>
        <tbody>
        {if is_array($locales)}
            {foreach from=$locales item=item}
                <tr>
                    <td class="text">{$item|escape}</td>
                </tr>
            {foreachelse}
                {norecords}
            {/foreach}
        {else}
            <td class="text">{$locales}</td>
        {/if}
        </tbody>
    </table>
</div>
