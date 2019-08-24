{$suffix = $payment_id|default:0}

<input type="hidden"
       name="payment_data[processor_params][is_stripe_connect]"
       value="Y"
/>

<div class="control-group">
    <label for="elm_client_id{$suffix}"
           class="control-label cm-required"
    >{__("stripe_connect.client_id")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][client_id]"
               id="elm_client_id{$suffix}"
               value="{$processor_params.client_id}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_publishable_key{$suffix}"
           class="control-label cm-required"
    >{__("stripe_connect.publishable_key")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][publishable_key]"
               id="elm_publishable_key{$suffix}"
               value="{$processor_params.publishable_key}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_secret_key{$suffix}"
           class="control-label cm-required"
    >{__("stripe_connect.secret_key")}:</label>
    <div class="controls">
        <input type="password"
               name="payment_data[processor_params][secret_key]"
               id="elm_secret_key{$suffix}"
               value="{$processor_params.secret_key}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_currency{$suffix}"
           class="control-label"
    >{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]"
                id="elm_currency{$suffix}"
        >
            {foreach $currencies as $code => $currency}
                <option value="{$code}"
                        {if $processor_params.currency == $code} selected="selected"{/if}
                >{$currency.description}</option>
            {/foreach}
        </select>
    </div>
</div>

{include file="common/widget_copy.tpl"
    widget_copy_title=__("stripe_connect.redirect_uri_vendor")
    widget_copy_text=__("stripe_connect.redirect_uris.description")
    widget_copy_code_text="companies.stripe_connect_auth"|fn_url:"V"
}

{if !$runtime.company_id}
    {include file="common/widget_copy.tpl"
        widget_copy_title=__("stripe_connect.redirect_uri_admin")
        widget_copy_text=__("stripe_connect.redirect_uris.description")
        widget_copy_code_text="companies.stripe_connect_auth"|fn_url:"A"
    }
{/if}
