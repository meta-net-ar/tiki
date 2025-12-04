{include file='templates/examples/shop/product_list_inner.tpl'}
{if $prefs.feature_elementplus neq 'y' || $prefs.elementplus_infinite_scroll neq 'y'}
    {pagination_links resultset=$results}{/pagination_links}
{/if}
