{include file="common/subheader.tpl" title=__("stripe_connect.stripe_connect")}
<div class="control-group">
    <label for="elm_stripe_connect_auth"
           class="control-label"
    >{__("stripe_connect.stripe_account")}:</label>
    <div class="controls">
        <input type="hidden"
               name="company_data[stripe_connect_account_id]"
               value="{$company_data.stripe_connect_account_id}"
        />
        {if $company_data.stripe_connect_account_id}
            <p class="text-success">{__("stripe_connect.connected")}</p>
        {else}
            <p>{__("stripe_connect.not_connected")}</p>
        {/if}
    </div>
</div>
{if $company_data.company_id && $runtime.company_id && ($stripe_connect_url || $stripe_disconnect_url)}
    <div class="control-group">
        <label class="control-label">&nbsp;</label>
        <div class="controls">
            {if $stripe_connect_url}
                <a class="btn btn-primary"
                   href="{$stripe_connect_url}"
                >{__("stripe_connect.connect_with_stripe")}</a>
            {/if}
            {if $stripe_disconnect_url}
                <a class="btn cm-post"
                   href="{$stripe_disconnect_url}"
                >{__("stripe_connect.disconnect")}</a>
            {/if}
        </div>
    </div>
{/if}
