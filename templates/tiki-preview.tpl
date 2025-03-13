{* templates/tiki-preview.tpl start *}
<div class="wikipreview border-bottom border-4" {if $prefs.ajax_autosave eq "y"}style="display:none;" id="autosave_preview"><div{/if}>
    {if $prefs.ajax_autosave eq "y"}
        <div class="mb-3 float-sm-end text-end">
            <div class="">
                {self_link _icon_name="back" _ajax="n" _class="tips" _title=":{tr}Popup preview{/tr}" _onclick="ajax_preview( 'editwiki', autoSaveId );$('#autosave_preview').hide();return false;"}
                {/self_link}
                {self_link _icon_name="remove" _ajax="n" _class="tips" _title=":{tr}Close preview{/tr}" _onclick="$('#autosave_preview').hide();return false;"}
                {/self_link}
            </div>
            <div>
                <select name="diff_style" id="preview_diff_style" class="form-select">
                    <option value="" {if empty($diff_style)}selected="selected"{/if}>{tr}Preview{/tr}</option>
                     {foreach  $diff_styles.options as $value => $label}
                     {if $value eq 'htmldiff' or $value eq 'sidediff' }
                     <option value="{$value}" {if $diff_style == $value} selected="selected"{/if}>{tr}{$label}{/tr}</option>
                     {/if}
                     {/foreach}
                </select>
            </div>
            {jq}
                $("#preview_diff_style").on("change", function(){
                    ajaxLoadingShow($("#autosave_preview .wikitext"));
                    setCookie("preview_diff_style", $(this).val(), "preview", "session");
                    $.get($.service("edit", "preview"), {
                        editor_id: 'editwiki',
                        autoSaveId: autoSaveId,
                        inPage: 1,
                        {{if isset($smarty.request.hdr)}hdr: {$smarty.request.hdr},{/if}}
                        diff_style: $(this).val()
                    }, function(data) {
                        $("#autosave_preview .wikitext").html(data);
                        ajaxLoadingHide();
                    });
                });
            {/jq}
        </div>
    {/if}
    {jq}
        $('#autosave_preview').height(getCookie("wiki", "preview", ""));
        interact('#autosave_preview').resizable({
            edges: { bottom: true },
            inertia: true,
            listeners: {
                move: (e) => {
                    const target = e.target;
                    let height = parseFloat(getComputedStyle(target).height.slice(0, -2));
                    height += e.deltaRect.top + e.deltaRect.bottom;
                    target.style.height = `${height}px`;
                }
            }
        }).on('resizeend', function (event) {
            setCookie("wiki", $('#autosave_preview').height(), "preview", "session");
        });
    {/jq}
    <h2>{tr}Preview:{/tr} {$page|escape}</h2>
    {if $prefs.feature_wiki_description eq 'y'}
        <small>{$description}</small>
    {/if}
    <div align="center" class="attention" style="font-weight:bold">
        {tr}Note: Remember that this is only a preview, and has not yet been saved!{/tr}
    </div>
    <div class="preview_contents">
        <article class="wikitext">
            {$parsed}
        </article>
        {if $has_footnote and isset($parsed_footnote)}
            <div class="wikitext">{$parsed_footnote}</div>
        {/if}
    </div>
    {if $prefs.ajax_autosave eq "y"}
        </div>
    {/if}
</div>
<hr style="clear:both; height:0px;"/> {* Information below the wiki content
must not overlap the wiki content that could contain floated elements *}
{* templates/tiki-preview.tpl end *}
