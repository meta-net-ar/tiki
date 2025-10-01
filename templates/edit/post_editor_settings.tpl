{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="content"}
    {textarea name=$domName id=$domId _wysiwyg=$wysiwyg syntax=$syntax}{$content}{/textarea}
{/block}
