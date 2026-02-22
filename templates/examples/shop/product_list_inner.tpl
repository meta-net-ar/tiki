{* this inner list tpl is separate as it is used both in product_list.tpl and product_ajax.tpl for the search page*}
<ul class="product-list row list-unstyled mb-5">
    {foreach $results as $row}
        {$link = "tiki-index.php?page=cart+product&itemId={$row.object_id}"}
        <li class="col-sm-3 text-center">
            <a class="list-thumb" href="{$link}" title="{$row.title}">
                {$img = $row.images|split:','|nonp}
                {if count({$img})}
                    <img src="tiki-download_file.php?fileId={$img[0]}&thumbnail" width="auto" height="200">
                {else}
                    <img src="img/trans.png" width="200" height="200" alt="{tr}Image coming soon{/tr}">
                {/if}
            </a>
            <h3>
                <a href="{$link}">{$row.name}</a>
            </h3>
            <p itemprop="category" class="list-cat">{$row.category}</p>
            <p><strong>{$row.price}</strong></p>
            {if $row.stock|nonp gt 0}
                {include file="templates/examples/shop/add_to_cart.tpl"}
                <meta itemprop="acceptedPaymentMethod" content="http://www.heppnetz.de/ontologies/goodrelations/v1#PayPal">
                <meta itemprop="availability" content="InStock">
            {else}
                <em>{tr}Awaiting stock{/tr}</em>
                <meta itemprop="availability" content="OutOfStock">
            {/if}
        </li>
    {/foreach}
</ul>
