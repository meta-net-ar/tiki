{tikimodule error=$module_params.error title=$tpl_module_title name="months_links" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
{$i=0 }
{if $feature eq 'cms'}
    {$itemlink='tiki-read_article.php?articleId='}
{else}
    {$itemlink='tiki-view_blog_post.php?postId='}
{/if}
<script type="text/javascript" >
<!--
    function mlchange(id) {
        var e = document.getElementById('ml-sub-' + id);
        var zip = document.getElementById('ml-icon-' + id);
        if(zip.innerHTML == '►' ) {
            e.style.display = 'block';
            zip.innerHTML = '▼'
        } else {
            e.style.display = 'none';
            zip.innerHTML = '►'
        }
}
//-->
</script>
{modules_list list=$archives nonums='y'}
    {foreach from=$archives key=year_number item=year_data}
        {if $year_expanded == 0 }
            {$year_expanded=$year_number }
        {/if}
        {if $year_number == $year_expanded }
            {$i=$i+1}
            <li class='archivedate expanded ps-0' id='ml-li-{$module_id}-{$i}' >
                <a class="toggle" href="javascript:void();" >
                    <span class="zippy " id='ml-icon-{$module_id}-{$i}' >○</span>
                </a>
                <a class="linkmodule" href="javascript:void();">{$year_number}</a>
                <span class="post-count badge bg-secondary" dir="ltr">{$year_data.count}</span>
                <ul class="list-unstyled ps-0" id='ml-sub-{$module_id}-{$i}' >
                    {foreach from=$year_data.monthlist key=month_name item=month_data}
                        {if $month_name == $month_expanded }
                            {$i=$i+1}
                            <li class='archivedate expanded ps-1' id='ml-{$module_id}-{$i}' >
                                <a class="toggle" href="javascript:mlchange('{$module_id}-{$i}')" >
                                    <span class="zippy " id='ml-icon-{$module_id}-{$i}' >▼</span>
                                </a>
                                <a class="linkmodule" href="{$month_data.link}">{$month_name}</a>
                                <span class="post-count badge bg-secondary" dir="ltr">{$month_data.count}</span>
                                <ul class="list-unstyled ps-0" id='ml-sub-{$module_id}-{$i}' >
                                    {foreach from=$month_data.postlist key=articleId item=title}
                                        <li class='archivedate collapsed ps-2' >
                                            <a class="linkmodule" href="{$itemlink}{$articleId}">{$title}</a>
                                        </li>
                                    {/foreach}
                                </ul>
                            </li>
                        {else}
                            {$i=$i+1}
                            <li class='archivedate collapsed ps-1' id='ml-{$module_id}-{$i}' >
                                <a class="toggle" href="javascript:mlchange('{$module_id}-{$i}')" >
                                    <span class="zippy " id='ml-icon-{$module_id}-{$i}'>►</span>
                                </a>
                                <a class="linkmodule" href="{$month_data.link}">{$month_name}</a>
                                <span class="post-count badge bg-secondary" dir="ltr">{$month_data.count}</span>
                                <ul class="list-unstyled ps-0" id='ml-sub-{$module_id}-{$i}' >
                                    {foreach from=$month_data.postlist key=articleId item=title}
                                        <li class='archivedate collapsed ps-2' >
                                            <a class="linkmodule" href="{$itemlink}{$articleId}">{$title}</a>
                                        </li>
                                    {/foreach}
                                </ul>
                            </li>
                        {/if}
                    {/foreach}
                </ul>
            </li>
        {else}
            {$i=$i+1}
            <li class='archivedate collapsed ps-1' id='ml-li-{$module_id}-{$i}' >
                <a class="toggle" href="{$year_data.link}" >
                    <span class="zippy " id='ml-icon-{$module_id}-{$i}' >●</span>
                </a>
                <a class="linkmodule" href="{$year_data.link}">{$year_number}</a>
                <span class="post-count badge bg-secondary" dir="ltr">{$year_data.count}</span>
            </li>
        {/if}
    {foreachelse}
        {if $feature eq 'cms'}
            {tr}No article found{/tr}
        {else}
            {tr}No blog post found{/tr}
        {/if}
    {/foreach}
{/modules_list}

{/tikimodule}
