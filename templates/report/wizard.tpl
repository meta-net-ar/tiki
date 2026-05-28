{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="content"}
<style>
    #report-wizard .wizard-step { min-height: 250px; }
</style>
<div id="report-wizard">
    <div class="wizard-steps mb-3">
        <ul class="nav nav-pills nav-justified">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-step="1">{tr}1. Data Source{/tr}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" href="#" data-step="2">{tr}2. Report Type{/tr}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" href="#" data-step="3">{tr}3. Fields{/tr}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" href="#" data-step="4">{tr}4. Options{/tr}</a>
            </li>
        </ul>
    </div>

    {* Step 1: Data Source *}
    <div class="wizard-step" data-step="1">
        <div class="mb-3">
            <label class="form-label fw-bold" for="report-tracker-select">{tr}Select Tracker{/tr}</label>
            <select id="report-tracker-select" class="form-select">
                <option value="">{tr}-- Choose a tracker --{/tr}</option>
                {foreach from=$trackers item=tracker}
                    <option value="{$tracker.trackerId|escape}">{$tracker.name|escape}</option>
                {/foreach}
            </select>
            <div class="form-text">{tr}Choose the tracker that contains the data for your report.{/tr}</div>
        </div>
    </div>

    {* Step 2: Report Type *}
    <div class="wizard-step d-none" data-step="2">
        <div class="mb-3">
            <label class="form-label fw-bold">{tr}Report Type{/tr}</label>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card report-type-card" data-type="simple_table">
                        <div class="card-body text-center">
                            <i class="fas fa-table fa-2x mb-2 text-primary"></i>
                            <h6 class="card-title">{tr}Simple Table{/tr}</h6>
                            <p class="card-text small text-muted">{tr}A tabular list of tracker items with selected columns.{/tr}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card report-type-card" data-type="aggregation_table">
                        <div class="card-body text-center">
                            <i class="fas fa-calculator fa-2x mb-2 text-success"></i>
                            <h6 class="card-title">{tr}Aggregation Table{/tr}</h6>
                            <p class="card-text small text-muted">{tr}Group data and compute metrics like sums, averages, or counts.{/tr}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card report-type-card" data-type="chart">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-2x mb-2 text-info"></i>
                            <h6 class="card-title">{tr}Chart{/tr}</h6>
                            <p class="card-text small text-muted">{tr}Visualize grouped data as a chart (bar, pie, line, etc.).{/tr}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card report-type-card" data-type="full_report">
                        <div class="card-body text-center">
                            <i class="fas fa-file-alt fa-2x mb-2 text-danger"></i>
                            <h6 class="card-title">{tr}Full Report{/tr}</h6>
                            <p class="card-text small text-muted">{tr}Chart + table combination with optional PDF cover page.{/tr}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {* Step 3: Fields *}
    <div class="wizard-step d-none" data-step="3">
        <div id="report-fields-simple" class="d-none">
            <label class="form-label fw-bold">{tr}Select Columns{/tr}</label>
            <div class="form-text mb-2">{tr}Choose fields to display as table columns.{/tr}</div>
            <div id="report-field-list" class="row g-2"></div>
        </div>
        <div id="report-fields-aggregation" class="d-none">
            <div class="mb-3">
                <label class="form-label fw-bold" for="report-group-field">{tr}Group By Field{/tr}</label>
                <select id="report-group-field" class="form-select">
                    <option value="">{tr}-- Select field --{/tr}</option>
                </select>
                <div class="form-text">{tr}The field to group data by (categories, status, etc.).{/tr}</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold" for="report-metric-op">{tr}Metric Operation{/tr}</label>
                <select id="report-metric-op" class="form-select">
                    <option value="count">{tr}Count{/tr}</option>
                    <option value="sum">{tr}Sum{/tr}</option>
                    <option value="avg">{tr}Average{/tr}</option>
                    <option value="min">{tr}Minimum{/tr}</option>
                    <option value="max">{tr}Maximum{/tr}</option>
                </select>
            </div>
            <div class="mb-3" id="report-value-field-group">
                <label class="form-label fw-bold" for="report-value-field">{tr}Value Field{/tr}</label>
                <select id="report-value-field" class="form-select">
                    <option value="">{tr}-- Select field --{/tr}</option>
                </select>
                <div class="form-text">{tr}The numeric field to aggregate (not needed for Count).{/tr}</div>
            </div>
        </div>
        <div id="report-fields-chart" class="d-none">
            <div class="mb-3">
                <label class="form-label fw-bold" for="report-chart-group-field">{tr}Group By Field{/tr}</label>
                <select id="report-chart-group-field" class="form-select">
                    <option value="">{tr}-- Select field --{/tr}</option>
                </select>
                <div class="form-text">{tr}The field for chart labels/categories.{/tr}</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold" for="report-chart-metric-op">{tr}Metric Operation{/tr}</label>
                <select id="report-chart-metric-op" class="form-select">
                    <option value="count">{tr}Count{/tr}</option>
                    <option value="sum">{tr}Sum{/tr}</option>
                    <option value="avg">{tr}Average{/tr}</option>
                    <option value="min">{tr}Minimum{/tr}</option>
                    <option value="max">{tr}Maximum{/tr}</option>
                </select>
            </div>
            <div class="mb-3" id="report-chart-value-field-group">
                <label class="form-label fw-bold" for="report-chart-value-field">{tr}Value Field{/tr}</label>
                <select id="report-chart-value-field" class="form-select">
                    <option value="">{tr}-- Select field --{/tr}</option>
                </select>
            </div>
            <div class="mb-3" id="report-chart-type-group">
                <label class="form-label fw-bold" for="report-chart-type">{tr}Chart Type{/tr}</label>
                <select id="report-chart-type" class="form-select">
                    <option value="bar">{tr}Bar{/tr}</option>
                    <option value="pie">{tr}Pie{/tr}</option>
                    <option value="line">{tr}Line{/tr}</option>
                    <option value="doughnut">{tr}Doughnut{/tr}</option>
                </select>
            </div>
        </div>
        <div id="report-fields-full" class="d-none">
            <div class="mb-3">
                <label class="form-label fw-bold">{tr}Table Columns{/tr}</label>
                <div class="form-text mb-2">{tr}Choose fields for the detail table.{/tr}</div>
                <div id="report-full-field-list" class="row g-2"></div>
            </div>
            <hr>
            <div class="mb-3">
                <label class="form-label fw-bold" for="report-full-group-field">{tr}Chart Group By{/tr}</label>
                <select id="report-full-group-field" class="form-select">
                    <option value="">{tr}-- Select field --{/tr}</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold" for="report-full-metric-op">{tr}Chart Metric{/tr}</label>
                <select id="report-full-metric-op" class="form-select">
                    <option value="count">{tr}Count{/tr}</option>
                    <option value="sum">{tr}Sum{/tr}</option>
                    <option value="avg">{tr}Average{/tr}</option>
                    <option value="min">{tr}Minimum{/tr}</option>
                    <option value="max">{tr}Maximum{/tr}</option>
                </select>
            </div>
            <div class="mb-3" id="report-full-value-field-group">
                <label class="form-label fw-bold" for="report-full-value-field">{tr}Value Field{/tr}</label>
                <select id="report-full-value-field" class="form-select">
                    <option value="">{tr}-- Select field --{/tr}</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold" for="report-full-chart-type">{tr}Chart Type{/tr}</label>
                <select id="report-full-chart-type" class="form-select">
                    <option value="bar">{tr}Bar{/tr}</option>
                    <option value="pie">{tr}Pie{/tr}</option>
                    <option value="line">{tr}Line{/tr}</option>
                    <option value="doughnut">{tr}Doughnut{/tr}</option>
                </select>
            </div>
        </div>
    </div>

    {* Step 4: Options *}
    <div class="wizard-step d-none" data-step="4">
        <div class="mb-3">
            <label class="form-label fw-bold" for="report-title">{tr}Report Title{/tr}</label>
            <input type="text" id="report-title" class="form-control" placeholder="{tr}My Report{/tr}">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold" for="report-orientation">{tr}PDF Orientation{/tr}</label>
            <select id="report-orientation" class="form-select">
                <option value="P">{tr}Portrait{/tr}</option>
                <option value="L">{tr}Landscape{/tr}</option>
            </select>
        </div>
        <div class="mb-3">
            <div class="form-check">
                <input type="checkbox" id="report-cover-page" class="form-check-input" checked>
                <label class="form-check-label" for="report-cover-page">{tr}Include PDF cover page{/tr}</label>
            </div>
        </div>
        <div class="card bg-light mt-3">
            <div class="card-header">
                <h6 class="mb-0">{tr}Preview{/tr}</h6>
            </div>
            <div class="card-body">
                <pre id="report-syntax-preview" class="mb-0 small" style="max-height: 300px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
        <button type="button" class="btn btn-secondary" id="report-wizard-back" disabled>{tr}Back{/tr}</button>
        <div>
            <button type="button" class="btn btn-primary" id="report-wizard-next" disabled>{tr}Next{/tr}</button>
            <button type="button" class="btn btn-success d-none" id="report-wizard-insert">{tr}Insert into Page{/tr}</button>
            <button type="button" class="btn btn-link" data-bs-dismiss="modal">{tr}Cancel{/tr}</button>
        </div>
    </div>
</div>

<script type="module">import "@jquery-tiki/report-wizard";</script>
{/block}
