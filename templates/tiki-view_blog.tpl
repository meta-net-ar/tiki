{include file='token_view_actions.tpl'}
{if !isset($show_heading) or $show_heading neq "n"}
    {if strlen($heading) > 0 and $prefs.feature_blog_heading eq 'y'}
        {eval var=$heading}
    {else}
        {include file='blog_heading.tpl'}
    {/if}
    {if $use_find eq 'y'}
        <div class="search-button-container clearfix">
            <button class="btn btn-info btn-sm mb-2 dropdown-toggle float-end" type="button" data-bs-toggle="collapse" data-bs-target="#searchBlogs" aria-expanded="false" aria-controls="searchBlogs" title="{tr}Search blogs{/tr}" aria-label="{tr}Search blogs{/tr}>
                {icon name="search"}
            </button>
        </div>
        <div class="collapse" id="searchBlogs">
            {include file='find.tpl' find_show_num_rows='y'}
        </div>
    {/if}
{/if}
{if !empty($excerpt) and $excerpt eq 'y'}
    {$request_context="excerpt"}
{else}
    {$request_context="view_blog"}
{/if}

{if $listpages|@count > 0}
    {foreach from=$listpages item=post_info}
        <article class="card blogpost clearfix d-block mb-5{if !empty($container_class)} {$container_class}{/if}">
            {include file='blog_wrapper.tpl' blog_post_context=$request_context}
        </article>
    {/foreach}
{else}
    {remarksbox type="warning" close="y" id="warningMessage"}
    {tr}The blog has no post yet{/tr}
    {/remarksbox}
{/if}

{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}
