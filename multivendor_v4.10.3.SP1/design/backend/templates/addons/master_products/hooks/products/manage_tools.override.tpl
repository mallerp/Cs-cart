<!-- Overridden by the Common Products for Vendors add-on -->
{if "MULTIVENDOR"|fn_allowed_for && $runtime.company_id && $addons.master_products.allow_vendors_to_create_products == 'Y'}
    {capture name="tools_list"}
        <li>{btn type="list" text=__("master_products.add_product_from_catalog") href="products.master_products"}</li>
        <li>{btn type="list" text=__("master_products.create_new_product") href="products.add"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list icon="icon-plus" no_caret=true placement="right"}
{elseif "MULTIVENDOR"|fn_allowed_for && $runtime.company_id && !$addons.master_products.allow_vendors_to_create_products != 'Y'}
    {include file="common/tools.tpl" tool_href="products.master_products" prefix="top" title=__("add_product") hide_tools=true icon="icon-plus"}
{else}
    {include file="common/tools.tpl" tool_href="products.add" prefix="top" title=__("add_product") hide_tools=true icon="icon-plus"}
{/if}