{if $key == "paypal_for_marketplaces.withdrawal"}
    {include file="common/price.tpl" value=$item}
{else}
    {$item}
{/if}