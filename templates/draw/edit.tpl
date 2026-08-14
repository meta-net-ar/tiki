{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    {* 
    Let's keep the CSS here instead of having it on page load via tiki-setup.php, because this file contains some rules that overwrite Bootstrap styles. Notably the dopdown.
    While this will still be overwritten when this tpl has been displayed. To overcome that, we remove the link element having the css file from the DOM when the editor modal has been closed.
    *}
    <link rel="stylesheet" href="public/generated/js/tiki-svgedit_draw.css" id="svgedit_css">
    <form id="tiki_draw" class="submit no-ajax" style="text-align: center;" onsubmit="return false;">
        <span style="display: none;">
            <textarea id="fileData">{$data}</textarea>
        </span>

        <input type="hidden" id="fileId" value="{$fileId}">
        <input type="hidden" id="galleryId" value="{$galleryId}">
        <input type="hidden" id="fileName" value="{$name}">
        <input type="hidden" id="fileWidth" value="{$width}">
        <input type="hidden" id="fileHeight" value="{$height}">
        <input type="hidden" id="archive" value="{$archive}">
        <input type="hidden" name="action" value="replace">

        <div id="drawEditor">
            <div id="drawMenu" class="submit">
                <button id="drawRename" href="#" class="btn btn-secondary" onclick="$('#fileName').val($('#tiki_draw').renameDraw());return false;">{tr}Rename{/tr}</button>
                <input type="submit" class="btn btn-primary" value="{tr}Save{/tr}">
            </div>
        </div>
    </form>
    <script type="module">
    {literal}
        import {handleDraw} from "@jquery-tiki/tiki-handle_svgedit";
    {/literal}
    handleDraw('{$fileId}', '{$galleryId}', '{$name}', '{json_encode($imgParams)}');
    </script>
{/block}

{jq}
    const modal = $('#tiki_draw').parents('.modal');
    modal.on('hide.bs.modal', function () {
        $('#svgedit_css').remove();
    });
{/jq}
