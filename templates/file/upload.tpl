{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
fileId={$fileId}<br/>
name={$name}<br/>
size={$size}<br/>
type={$type}<br/>
galleryId={$galleryId}<br/>
md5sum={$md5sum}<br/>
{/block}
