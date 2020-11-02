{if $show_recordrtc_module === true}
	{tikimodule error=$module_error title=$tpl_module_title name="recordrtc" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
		{if empty($module_error)}
		<form class="form">
			<div class="form-group row">
				<input id="record-name" class="form-control" type="text" value="" placeholder="Record name">
			</div>
			<div class="form-group row">
				<button id="btn-start-recording" class="btn btn-primary">
					<span class="icon fa fa-video"></span> {tr}Start Recording{/tr}
				</button>
				<button id="btn-stop-recording" class="btn btn-danger" style="display:none">
					<span class="icon fa fa-stop"></span> {tr}Stop Recording{/tr}
				</button>
				<video style="display:none;" controls autoplay playsinline></video>
				<input id="record-rtc-url" type="hidden" value="{service controller=recordrtc action=upload}">
				<input id="record-rtc-ticket" type="hidden" value="{ticket mode=get}">
			</div>
			<div class="form-group">
				<input id="record-rtc-auto-upload" type="checkbox" name="auto-upload"> {tr}auto upload{/tr}
			</div>
			<div class="form-group row">
				<span id="upload-feedback"></span>
			</div>
			<div class="form-group row">
				<button id="btn-upload-recording" class="btn btn-primary" style="display:none">
					<span class="icon fa fa-upload"></span> {tr}Upload Record{/tr}
				</button>
			</div>
		</form>
		{/if}
	{/tikimodule}
{/if}
