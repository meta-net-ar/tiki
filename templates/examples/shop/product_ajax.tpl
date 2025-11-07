{include file='templates/examples/shop/product_list_inner.tpl'}
{if $prefs.feature_elementplus neq 'y' || $prefs.elementplus_infinite_scroll neq 'y'}
    {pagination_links offset_jsvar='customsearch_0.offset' _onclick="$('#customsearch_0').trigger('submit');return false;" resultset=$results}{/pagination_links}
{/if}
{* Note: the _onclick param in pagination now needs to be single quoted to avoid js compile errors *}
