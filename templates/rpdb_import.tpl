<h2>Related Party Database &mdash; CSV Import</h2>

{if $display eq 'table'}
<form class="no-ajax" method="post">
	<input type="hidden" name="tzname" value="{$serverTimezone|escape}">
	<div class="table-responsive">
		<table id="rpdb-table" class="table table-striped table-hover">
			<thead>
				<tr>
					<th class="auto" style="width:40px"><input type="checkbox" id="rpdb-select-all" class="form-check-input" title="Select / deselect all" checked></th>
					<th class="auto">{$idColumnLabel|escape}</th>
					<th class="auto">Change Type</th>
					{foreach from=$tableColumns item=col}
					<th class="auto">{$col.label}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach from=$changes key=ix item=change}
				<tr class="
					{if $change.type eq 'new'}table-success
					{elseif $change.type eq 'update'}table-warning
					{elseif $change.type eq 'reactivate'}table-info
					{elseif $change.type eq 'inactive'}table-danger
					{/if}
				">
					<td class="checkbox-cell">
						{if $change.type eq 'no_change'}
						<input type="checkbox" class="form-check-input" disabled>
						{else}
						<input type="checkbox" name="changes[]" value="{$ix}" class="form-check-input rpdb-row-check" checked>
						{/if}
					</td>
					<td class="auto">{if $change.csv_item.rpdbID}{$change.csv_item.rpdbID|escape}{elseif $change.db_item.rpdbID}{$change.db_item.rpdbID|escape}{elseif $change.type eq 'new'}<em>auto</em>{/if}</td>
					<td class="auto">
						{if $change.type eq 'new'}<span class="badge bg-success">New</span>
						{elseif $change.type eq 'update'}<span class="badge bg-warning text-dark">Changed</span>
						{elseif $change.type eq 'reactivate'}<span class="badge bg-info text-dark">Re-activate</span>
						{elseif $change.type eq 'inactive'}<span class="badge bg-danger">Inactive</span>
						{else}<span class="badge bg-secondary">No Change</span>
						{/if}
					</td>
					{foreach from=$change.columns item=col}
					<td class="auto">
					{if $col.kind eq 'date'}
						{if $col.show}
						<input type="text" class="form-control" name="date_{$col.dateField}[{$ix}]" value="{$col.value|escape}" placeholder="YYYY-MM-DD" maxlength="10" style="min-width:11em">
						{/if}
						{elseif $col.changed}
							New: <span class="text-danger fw-bold">{$col.value|escape}</span><br>
							Current: {$col.old|escape}
						{else}
							{$col.value|escape}
						{/if}
					</td>
					{/foreach}
				</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	<div class="submit mt-3">
		<input class="btn btn-primary" type="submit" value="Apply Selected Changes">
		<a href="rpdb_import.php" class="btn btn-secondary ms-2">Cancel</a>
	</div>
</form>
{jq}
$(document).ready(function() {
	$('#rpdb-select-all').on('change', function() {
		$('.rpdb-row-check').prop('checked', $(this).prop('checked'));
	});
	$('.rpdb-row-check').on('change', function() {
		var total = $('.rpdb-row-check').length;
		var checked = $('.rpdb-row-check:checked').length;
		$('#rpdb-select-all').prop('checked', total === checked);
	});
});
{/jq}

{elseif $display eq 'done'}
<div class="alert alert-success" role="alert">
	{$msg}
</div>
<a href="rpdb_import.php" class="btn btn-primary">Upload Another File</a>

{else}
{if $msg}
<div class="alert alert-danger" role="alert">
	{$msg}
</div>
{/if}
<form class="no-ajax" method="post" enctype="multipart/form-data">
	<div class="mb-3">
		<label class="form-label" for="rpdb-csv-file">
			Related Party Database CSV file (please convert to .csv before uploading)
		</label>
		<input type="file" class="form-control" name="file" id="rpdb-csv-file" accept=".csv,text/csv">
	</div>
	<p class="text-muted">
		Required column: <strong>rpdbID</strong>.
		Each row is matched to an existing record by this unique ID.
		Leave the ID blank for new records; it will be assigned automatically on import.
		All other recognized columns will be compared for changes.
	</p>
	<div class="submit">
		<input class="btn btn-primary" type="submit" value="Upload &amp; Compare">
	</div>
</form>
{/if}