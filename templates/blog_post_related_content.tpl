{if isset($post_info.related_posts) && !empty($post_info.related_posts)}
    <div class="related_posts mt-5">
        <h4 class="m-0">{tr}Related content:{/tr}</h4>
        <ul>
            {foreach from=$post_info.related_posts item=related}
                <li>{self_link postId=$related.postId}{$related.name}{/self_link}</li>
            {/foreach}
        </ul>
    </div>
{/if}
