{extends file="file_displays/diagram.tpl"}
{block name="diagram_extra"}
    {if $allow_edit}
        <div class="text-end">
            {if $file_id and $template ne $file_id}
                {if $slide_page ne 'tiki-slideshow.php'}
                    <a class="btn btn-link" target="_blank" role="button" href="tiki-editdiagram.php?fileId={$file_id}">{icon name="pencil"} Edit diagram</a>
                {/if}
            {else}
            <form id="edit-diagram-{$index}" target="_blank" action="tiki-editdiagram.php" method="post">
                    <input type="hidden" value="{$graph_data_base64}" name="xml">
                    <input type="hidden" value="{$sourcepage}" name="page">
                    <input type="hidden" value="{$template}" name="template">
                    <input type="hidden" value="{$gallery_id}" name="galleryId">
                    <input type="hidden" value="{$file_name}" name="fileName">
                    <input type="hidden" value="{$index}" name="index">
                    <input type="hidden" value="{if !$compressXml}false{else}true{/if}" name="compressXml">
                    <input type="hidden" value="{$compressXmlParam}" name="compressXmlParam">
                <a class="btn btn-link" role="button" href="javascript:void(0)" onclick="$('#edit-diagram-{$index}').trigger('submit')">{icon name="pencil"} Edit diagram</a>
            </form>
            {/if}
        </div>
    {/if}
{/block}
