<form action="tiki-admin.php?page=payment" method="post">
    {ticket}
    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            <a role="link" class="btn btn-link" href="tiki-payment.php" title="{tr}List{/tr}">
                {icon name="list"} {tr}Payments{/tr}
            </a>
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>

    {if $prefs.payment_feature neq "y"}
        <fieldset class="mb-3 w-100">
            <legend class="h3">{tr}Activate the feature{/tr}</legend>
            {preference name=payment_feature visible="always"}
        </fieldset>
    {/if}
    {tabset}
        {tab name="{tr}Payment{/tr}"}
            <br>
            {remarksbox title="{tr}Choose payment system{/tr}"}
                {tr}You can use only one payment method: PayPal or Cclite or Tiki User Credits{/tr}<br>
                {tr}PayPal is working at the moment. See PayPal.com{/tr}<br>
                {tr}Cclite: Community currency accounting for local exchange trading systems (LETS). See {/tr}<a class="alert-link" href="http://sourceforge.net/projects/cclite/">{tr}sourceforge.net{/tr}</a><br>
                {tr}Tiki User Credits: Requires this other feature to be configured{/tr}
            {/remarksbox}

            <div class="adminoptionboxchild" id="payment_feature_childcontainer">
                <fieldset class="mb-3 w-100">
                    {preference name=payment_system}
                    {preference name=payment_currency}
                    {preference name=payment_default_delay}
                    {preference name=payment_manual}
                    {preference name=payment_user_only_his_own}
                    {preference name=payment_user_only_his_own_past}
                    {preference name=payment_anonymous_allowed}
                </fieldset>
                {accordion}
                    {accordion_group title="{tr}PayPal{/tr}"}
                        <div class="admin payment">
                            {preference name=payment_paypal_business}
                            {preference name=payment_paypal_password}
                            {preference name=payment_paypal_signature}

                            <div class="adminoptionboxchild">
                                {preference name=payment_paypal_environment}
                                {preference name=payment_paypal_ipn}
                                {preference name=payment_paypal_pdt}
                                {preference name=payment_paypal_pdt_token}
                                {preference name=payment_paypal_pdt_redirect}
                            </div>
                            {preference name=payment_invoice_prefix}
                        </div>
                    {/accordion_group}
                    {accordion_group title="{tr}Inter Ledger Protocol payment module{/tr}"}
                        <div class="admin payment">
                            {remarksbox title="{tr}Experimental{/tr}" type="warning" icon="bricks"}
                            {tr}Inter Ledger Protocol payment is currently experimental, you need to toggle the experimental settings to be able to use{/tr}
                            {tr}You will need to set the URL and Token to be able to setup as payment method{/tr}
                            {/remarksbox}
                            {preference name=payment_ilp_base_url}
                            {preference name=payment_ilp_token}
                            {preference name=payment_ilp_ssl}
                            {preference name=payment_ilp_scale}
                        </div>
                    {/accordion_group}
                    {accordion_group title="{tr}Cclite{/tr}"}
                        <div class="admin payment">
                            {remarksbox title="{tr}Experimental{/tr}" type="warning" icon="bricks"}
                                {tr}Cclite is for creating and managing alternative or complementary trading currencies and groups{/tr}
                                {tr}Work in progress since Tiki 6{/tr}
                            {/remarksbox}
                            {preference name=payment_cclite_registries}
                            {preference name=payment_cclite_currencies}
                            <div class="adminoptionboxchild">
                                {preference name=payment_cclite_gateway}
                                {preference name=payment_cclite_merchant_user}
                                {preference name=payment_cclite_merchant_key}
                                {preference name=payment_cclite_mode}
                                {preference name=payment_cclite_hashing_algorithm}
                                {preference name=payment_cclite_notify}
                            </div>
                        </div>
                    {/accordion_group}
                    {accordion_group title="{tr}Tiki user credits{/tr}"}
                        <div class="admin payment">
                            {preference name=payment_tikicredits_types}
                            {preference name=payment_tikicredits_xcrates}
                        </div>
                    {/accordion_group}
                {/accordion}
            </div>
        {/tab}
        {tab name="{tr}Advanced Shopping Cart{/tr}"}
            <br>
            <fieldset>
                <label>{tr}Cart settings{/tr}</label>
                {preference name=payment_cart_heading}
            </fieldset>
{*
            <fieldset>
                <legend class="h3">{tr}Advanced cart tracker names setup{/tr}</legend>
                {preference name=payment_cart_product_tracker_name}
                {preference name=payment_cart_orders_tracker_name}
                {preference name=payment_cart_orderitems_tracker_name}
                {preference name=payment_cart_productclasses_tracker_name}
            </fieldset>
*}
            <fieldset>
                <legend class="h3">{tr}Products tracker setup{/tr}</legend>
{*
                {remarksbox title="{tr}Choose payment system{/tr}"}
                    {tr}Depending on which feature you are using, you may need some or all of the following fields to be setup{/tr}
                {/remarksbox}
*}
                {preference name=payment_cart_product_tracker}
                {preference name=payment_cart_product_name_fieldname}
                {preference name=payment_cart_product_price_fieldname}
                {preference name=payment_cart_product_classid_fieldname}
                {preference name=payment_cart_bundles}
                <div class="adminoptionboxchild" id="payment_cart_bundles_childcontainer">
                    {preference name=payment_cart_products_inbundle_fieldname}
                </div>
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}Features{/tr}</legend>
                {preference name=payment_cart_inventory}
                <div class="adminoptionboxchild" id="payment_cart_inventory_childcontainer">
                    {preference name=payment_cart_inventory_type_field}
                    {preference name=payment_cart_inventory_total_field}
                    {preference name=payment_cart_inventory_lesshold_field}
                    {preference name=payment_cart_inventoryhold_expiry}
                </div>
                {preference name=payment_cart_bundles}
                {preference name=payment_cart_orders}
                <div class="adminoptionboxchild" id="payment_cart_orders_childcontainer">
                    {preference name=payment_cart_order_tracker}
                    {preference name=payment_cart_order_user_field}
                    {preference name=payment_cart_order_date_field}
                    {preference name=payment_cart_order_total_field}
                    {preference name=payment_cart_order_invoice_field}
                    {preference name=payment_cart_order_weight_field}
                    <hr>
                    {preference name=payment_cart_orderitems_tracker}

                    {preference name=payment_cart_orderitems_order_field}
                    {preference name=payment_cart_orderitems_product_field}
                    {preference name=payment_cart_orderitems_price_field}
                    {preference name=payment_cart_orderitems_quantity_field}
                    {preference name=payment_cart_orderitems_user_field}
                    {preference name=payment_cart_orderitems_inputedprice_field}
                </div>
                {preference name=payment_cart_anonymous}
                <div class="adminoptionboxchild" id="payment_cart_anonymous_childcontainer">
                    {preference name=payment_cart_anonshopper_profile}
                    {preference name=payment_cart_anon_reviewpage}
                    {preference name=payment_cart_anon_group}
                </div>
                {preference name=payment_cart_associatedevent}
                <div class="adminoptionboxchild" id="payment_cart_associatedevent_childcontainer">
                    {preference name=payment_cart_associated_event_fieldname}
                    {preference name=payment_cart_event_tracker}
                    {preference name=payment_cart_event_tracker_name}
                    {preference name=payment_cart_eventstart_fieldname}
                    {preference name=payment_cart_eventend_fieldname}
                    {preference name=payment_cart_orderitems_eventcode_field}
                    {preference name=payment_cart_orderitems_eventstart_field}
                    {preference name=payment_cart_orderitems_eventend_field}
                    {preference name=payment_cart_orderitems_parentcode_field}
                </div>
                {preference name=payment_cart_exchange}
                <div class="adminoptionboxchild" id="payment_cart_exchange_childcontainer">
                </div>
            </fieldset>
        {/tab}

        {tab name="{tr}Plugins{/tr}"}
            <br>

            <fieldset class="mb-3 w-100">
                <legend class="h3">{tr}Plugins{/tr}</legend>
                {preference name=wikiplugin_addtocart}
                {preference name=wikiplugin_adjustinventory}
                {preference name=wikiplugin_extendcarthold}
                {preference name=wikiplugin_memberpayment}
                {preference name=wikiplugin_payment}
                {preference name=wikiplugin_shopperinfo}
            </fieldset>

        {/tab}

        {tab name="{tr}Shipping{/tr}"}
            <br>
            {preference name=shipping_service}

            {preference name=shipping_fedex_enable}
            <div class="adminoptionboxchild" id="shipping_fedex_enable_childcontainer">
                {preference name=shipping_fedex_key}
                {preference name=shipping_fedex_password}
                {preference name=shipping_fedex_account}
                {preference name=shipping_fedex_meter}
            </div>

            {preference name=shipping_ups_enable}
            <div class="adminoptionboxchild" id="shipping_ups_enable_childcontainer">
                {preference name=shipping_ups_license}
                {preference name=shipping_ups_username}
                {preference name=shipping_ups_password}
            </div>
            {preference name=shipping_custom_provider}
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
