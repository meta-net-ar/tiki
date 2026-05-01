<div class="panel panel-default">
    <div class="panel-heading">
        <strong>{tr}Server Check Summary{/tr}</strong>
    </div>
    <div class="panel-body">
        <div class="dashboard-summary">
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="status-card critical" data-toggle="tooltip" data-html="true" 
                 title="{$tooltip_html.critical|default:''}">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>{tr}Critical Findings{/tr}</h4>
                </div>
                <div class="card-body">
                    <span class="count">{$critical_count|default:0}</span>
                    <p class="description">{tr}Sum of checks with BAD, UNSAFE, or RISKY status{/tr}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="status-card warning" data-toggle="tooltip" data-html="true"
                 title="{$tooltip_html.warning|default:''}">
                <div class="card-header">
                    <i class="fas fa-exclamation-circle"></i>
                    <h4>{tr}Checks Requiring Attention{/tr}</h4>
                </div>
                <div class="card-body">
                    <span class="count">{$warning_count|default:0}</span>
                    <p class="description">{tr}Sum of checks with UNSURE status{/tr}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="status-card info" data-toggle="tooltip" data-html="true"
                 title="{$tooltip_html.info|default:''}">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                    <h4>{tr}Informational Findings{/tr}</h4>
                </div>
                <div class="card-body">
                    <span class="count">{$info_count|default:0}</span>
                    <p class="description">{tr}Sum of checks with INFO status{/tr}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="status-card success" data-toggle="tooltip" data-html="true"
                 title="{$tooltip_html.good|default:''}">
                <div class="card-header">
                    <i class="fas fa-check-circle"></i>
                    <h4>{tr}Passed Checks{/tr}</h4>
                </div>
                <div class="card-body">
                    <span class="count">{$good_count|default:0}</span>
                    <p class="description">{tr}Sum of checks with GOOD or SAFE status{/tr}</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="quick-actions mt-3">
        <h5>{tr}Quick Actions{/tr}</h5>
        <div class="btn-group-vertical w-100">
            <button class="btn btn-outline-primary btn-sm" onclick="window.runAllChecks()">
                <i class="fas fa-sync-alt"></i> {tr}Run All Checks{/tr}
            </button>
        </div>
    </div>

    {if $dashboard_debug_enabled}
    <div class="alert alert-secondary mt-3">
        <h5>{tr}Dashboard Count Debug{/tr}</h5>
        <p class="mb-2">{tr}Use this table to reconcile summary cards with per-source contributions{/tr}</p>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-2">
                <thead>
                    <tr>
                        <th>{tr}Source{/tr}</th>
                        <th>{tr}Critical{/tr}</th>
                        <th>{tr}Warning{/tr}</th>
                        <th>{tr}Info{/tr}</th>
                        <th>{tr}Good{/tr}</th>
                        <th>{tr}Counted{/tr}</th>
                        <th>{tr}Ignored{/tr}</th>
                        <th>{tr}Total Items{/tr}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$dashboard_debug_sources key=source item=counts}
                    <tr>
                        <td>{$source|escape}</td>
                        <td>{$counts.critical|default:0}</td>
                        <td>{$counts.warning|default:0}</td>
                        <td>{$counts.info|default:0}</td>
                        <td>{$counts.good|default:0}</td>
                        <td>{$counts.total_counted|default:0}</td>
                        <td>{$counts.ignored_items|default:0}</td>
                        <td>{$counts.total_items|default:0}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        <p class="mb-0">
            {tr}Global ignored items{/tr}:
            {$dashboard_debug_ignored.non_array_item|default:0}
            ({tr}non-array{/tr}),
            {$dashboard_debug_ignored.unknown_or_unmapped_status|default:0}
            ({tr}unknown or unmapped status{/tr})
        </p>
    </div>
    {/if}
    
    {* Critical Issues Summary *}
    {if $server_critical_issues && count($server_critical_issues) > 0}
    <div class="critical-issues-summary critical-issues-summary--critical mt-3">
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> {tr}Immediate Actions Required{/tr}</h5>
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
            <h5><i class="fas fa-exclamation-circle"></i> {tr}Recommended Security Reviews{/tr}</h5>
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
            <h5><i class="fas fa-info-circle"></i> {tr}Optional Improvements & Recommendations{/tr}</h5>
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
