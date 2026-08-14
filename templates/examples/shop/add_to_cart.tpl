{if $row.tracker_field_productInventoryTotal|nonp gt 0}
    {wikiplugin _name='addtocart' code=$row.object_id description=$row.title|nonp price=$row.tracker_field_productPrice ajaxaddtocart='y' href=$link weight=$row.tracker_field_productWeight}{/wikiplugin}
{/if}
