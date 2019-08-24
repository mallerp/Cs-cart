{include file="common/subheader.tpl" title=__("paypal_for_marketplaces.paypal_for_marketplaces")}
<div class="control-group">
    <label for="elm_paypal_for_marketplaces_auth"
           class="control-label"
    >{__("paypal_for_marketplaces.paypal_account")}:</label>
    <div class="controls">
        <input type="hidden"
               name="company_data[paypal_for_marketplaces_account_id]"
               value="{$company_data.paypal_for_marketplaces_account_id}"
        />
        <input type="hidden"
               name="company_data[paypal_for_marketplaces_email]"
               value="{$company_data.paypal_for_marketplaces_email}"
        />
        <p class="paypal-for-marketplaces__account">
            {if $company_data.paypal_for_marketplaces_account_id}
                {$company_data.paypal_for_marketplaces_account_id}
            {else}
                {__("paypal_for_marketplaces.not_connected")}
            {/if}
        </p>
    </div>
</div>
{if $company_data.company_id && ($paypal_for_marketplaces_connect_url || $paypal_for_marketplaces_disconnect_url)}
    <div class="control-group">
        <label class="control-label">&nbsp;</label>
        <div class="controls">
            {if $paypal_for_marketplaces_connect_url}
                <a class="btn btn-primary"
                   href="{$paypal_for_marketplaces_connect_url}"
                >{__("paypal_for_marketplaces.connect_with_paypal")}</a>
            {/if}
            {if $paypal_for_marketplaces_disconnect_url}
                <a class="btn cm-post"
                   href="{$paypal_for_marketplaces_disconnect_url}"
                >{__("paypal_for_marketplaces.disconnect")}</a>
            {/if}
        </div>
    </div>
{/if}
