{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    {$headerlib->clear_js()}
    <div>
        <div>{tr}Item{/tr}</div>
        {textarea name="forum_deliberation_description[]" class="forum_deliberation_description" _simple="y" codemirror="y" _toolbars=$prefs.feature_forum_parse}{/textarea}
        {rating_override_menu type="comment"}
        {$headerlib->output_js_files()}
        <script>{$headerlib->output_js()}</script>
    </div>
{/block}
