<td class="row-status">
    <span class="paypal-for-marketplaces__account">
        {if $company.paypal_for_marketplaces_account_id}
            {$company.paypal_for_marketplaces_account_id}
        {else}
            {__("paypal_for_marketplaces.not_connected")}
        {/if}
    </span>
</td>