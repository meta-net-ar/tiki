<div class="panel panel-default">
    <div class="panel-heading">
        <strong>{tr}Server Check Summary{/tr}</strong>
    </div>
    <div class="panel-body">
        <div class="dashboard-summary">
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="status-card critical" data-toggle="tooltip" data-html="true" 
                 title="Critical Breakdown: Main Checks: {$source_breakdown.critical.main|default:0}, Packages: {$source_breakdown.critical.packages|default:0}, OCR: {$source_breakdown.critical.ocr|default:0}">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>{tr}Critical Issues{/tr}</h4>
                </div>
                <div class="card-body">
                    <span class="count">{$critical_count|default:0}</span>
                    <p class="description">{tr}Requires immediate attention{/tr}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="status-card warning" data-toggle="tooltip" data-html="true"
                 title="Warning Breakdown: Main Checks: {$source_breakdown.warning.main|default:0}, Packages: {$source_breakdown.warning.packages|default:0}, OCR: {$source_breakdown.warning.ocr|default:0}">
                <div class="card-header">
                    <i class="fas fa-exclamation-circle"></i>
                    <h4>{tr}Warnings{/tr}</h4>
                </div>
                <div class="card-body">
                    <span class="count">{$warning_count|default:0}</span>
                    <p class="description">{tr}Should be addressed soon{/tr}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="status-card info" data-toggle="tooltip" data-html="true"
                 title="Information Breakdown: Main Checks: {$source_breakdown.info.main|default:0}, Packages: {$source_breakdown.info.packages|default:0}, OCR: {$source_breakdown.info.ocr|default:0}">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                    <h4>{tr}Information{/tr}</h4>
                </div>
                <div class="card-body">
                    <span class="count">{$info_count|default:0}</span>
                    <p class="description">{tr}For your reference{/tr}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="status-card success" data-toggle="tooltip" data-html="true"
                 title="Good Breakdown: Main Checks: {$source_breakdown.good.main|default:0}, Packages: {$source_breakdown.good.packages|default:0}, OCR: {$source_breakdown.good.ocr|default:0}">
                <div class="card-header">
                    <i class="fas fa-check-circle"></i>
                    <h4>{tr}Good{/tr}</h4>
                </div>
                <div class="card-body">
                    <span class="count">{$good_count|default:0}</span>
                    <p class="description">{tr}Working correctly{/tr}</p>
                </div>
            </div>
        </div>
    </div>
    
    {* Overall System Health Indicator *}
    <div class="system-health-indicator mt-3">
        <div class="row">
            <div class="col-md-8">
                <div class="health-bar">
                    <div class="health-label">{tr}Overall System Health{/tr}</div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: {$health_percentage|default:100}%"></div>
                        <div class="progress-bar bg-warning" style="width: {$warning_percentage|default:0}%"></div>
                        <div class="progress-bar bg-danger" style="width: {$critical_percentage|default:0}%"></div>
                    </div>
                    <div class="health-score" data-toggle="tooltip" data-html="true" 
                         title="Health Score Calculation: Good items: {$good_count|default:0} (count 100%), Info items: {$info_count|default:0} (count 50%), Warning items: {$warning_count|default:0} (count 0%), Critical items: {$critical_count|default:0} (count 0%). Formula: ({$good_count|default:0} + {$info_count|default:0} × 0.5) ÷ {$total_checks|default:0} × 100 = {$health_score|default:100}%">
                        {$health_score|default:100}/100
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="quick-actions">
                    <h5>{tr}Quick Actions{/tr}</h5>
                    <div class="btn-group-vertical w-100">
                        <button class="btn btn-outline-primary btn-sm" onclick="window.runAllChecks()">
                            <i class="fas fa-sync-alt"></i> {tr}Run All Checks{/tr}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {* Critical Issues Summary *}
    {if $server_critical_issues && count($server_critical_issues) > 0}
    <div class="critical-issues-summary critical-issues-summary--critical mt-3">
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> {tr}Critical Issues (Server Configuration - Must Fix){/tr}</h5>
            <ul class="mb-0">
                {foreach from=$server_critical_issues item=issue}
                <li>
                    <strong>{$issue.title}</strong>: {$issue.message}
                    {if $issue.section}
                    <a href="#{if !empty($issue.row_id)}{$issue.row_id}{else}{$issue.section|cat:'_'|cat:$issue.title|regex_replace:'/[^a-zA-Z0-9]/':'_'}{/if}" class="btn btn-sm btn-outline-danger ml-2">{tr}View Details{/tr}</a>
                    {/if}
                </li>
                {/foreach}
            </ul>
        </div>
    </div>
    {/if}

    {* Security Warnings *}
    {if $security_warning_issues && count($security_warning_issues) > 0}
    <div class="critical-issues-summary critical-issues-summary--security-warning mt-3">
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-circle"></i> {tr}Security Warnings (Verify and Harden){/tr}</h5>
            <ul class="mb-0">
                {foreach from=$security_warning_issues item=issue}
                <li>
                    <i class="fas fa-exclamation-circle text-warning list-item-section-icon" aria-hidden="true"></i><strong>{$issue.title}</strong>: {$issue.message}
                    {if $issue.section}
                    <a href="#{if !empty($issue.row_id)}{$issue.row_id}{else}{$issue.section|cat:'_'|cat:$issue.title|regex_replace:'/[^a-zA-Z0-9]/':'_'}{/if}" class="btn btn-sm btn-outline-warning ml-2">{tr}View Details{/tr}</a>
                    {/if}
                </li>
                {/foreach}
            </ul>
        </div>
    </div>
    {/if}

    {* Optional Features / Recommendations *}
    {if $optional_recommendations && count($optional_recommendations) > 0}
    <div class="critical-issues-summary critical-issues-summary--optional mt-3">
        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> {tr}Optional Features / Recommendations{/tr}</h5>
            <ul class="mb-0">
                {foreach from=$optional_recommendations item=issue}
                <li>
                    <i class="fas fa-info-circle text-info list-item-section-icon" aria-hidden="true"></i><strong>{$issue.title}</strong>: {$issue.message}
                    {if $issue.section}
                    <a href="#{if !empty($issue.row_id)}{$issue.row_id}{else}{$issue.section|cat:'_'|cat:$issue.title|regex_replace:'/[^a-zA-Z0-9]/':'_'}{/if}" class="btn btn-sm btn-outline-info ml-2">{tr}View Details{/tr}</a>
                    {/if}
                </li>
                {/foreach}
            </ul>
        </div>
    </div>
    {/if}
        </div>
    </div>
</div>
