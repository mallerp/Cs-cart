<td class="row-status {if $company.stripe_connect_account_id}text-success{/if}">
    {if $company.stripe_connect_account_id}
        {__("stripe_connect.connected")}
    {else}
        {__("stripe_connect.not_connected")}
    {/if}
</td>