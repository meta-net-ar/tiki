{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
{wikiplugin _name="vimeo" fileId={$file_id} width="100%"}{/wikiplugin}
{/block}
