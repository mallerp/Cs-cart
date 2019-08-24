{$suffix = $payment_id|default:0}

<p>
    {__("paypal_for_marketplaces.webhook_notice", ["[url]" => fn_url("paypal_for_marketplaces.webhook", "C")])}
</p>

<hr>

{include file="common/subheader.tpl" title=__("paypal_for_marketplaces.settings.account")}

<input type="hidden"
       name="payment_data[processor_params][is_paypal_for_marketplaces]"
       value="Y"
/>

<input type="hidden"
       name="payment_data[processor_params][access_token]"
       value="{$processor_params.access_token|default:""}"
/>

<input type="hidden"
       name="payment_data[processor_params][expiry_time]"
       value="{$processor_params.expiry_time|default:0}"
/>

<div class="control-group">
    <label for="elm_bn_code{$suffix}"
           class="control-label cm-required"
    >{__("paypal_for_marketplaces.bn_code")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][bn_code]"
               id="elm_bn_code{$suffix}"
               value="{$processor_params.bn_code}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_payer_id{$suffix}"
           class="control-label cm-required"
    >{__("paypal_for_marketplaces.payer_id")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][payer_id]"
               id="elm_payer_id{$suffix}"
               value="{$processor_params.payer_id}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_client_id{$suffix}"
           class="control-label cm-required"
    >{__("paypal_for_marketplaces.client_id")}:</label>
    <div class="controls">
        <input type="text"
               name="payment_data[processor_params][client_id]"
               id="elm_client_id{$suffix}"
               value="{$processor_params.client_id}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_secret{$suffix}"
           class="control-label cm-required"
    >{__("paypal_for_marketplaces.secret")}:</label>
    <div class="controls">
        <input type="password"
               name="payment_data[processor_params][secret]"
               id="elm_secret{$suffix}"
               value="{$processor_params.secret}"
        />
    </div>
</div>

<div class="control-group">
    <label for="elm_mode{$suffix}"
           class="control-label"
    >{__("test_live_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][mode]"
                id="elm_mode{$suffix}"
        >
            <option value="test"
                    {if $processor_params.mode == "test"}selected="selected"{/if}
            >{__("test")}</option>
            <option value="live"
                    {if $processor_params.mode == "live"}selected="selected"{/if}
            >{__("live")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label for="elm_currency{$suffix}"
           class="control-label"
    >{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]"
                id="elm_currency{$suffix}"
                data-ca-paypal-for-marketplaces-element="currency"
                data-ca-paypal-for-marketplaces-credit-selector="#elm_funding_credit{$suffix}"
        >
            {foreach $currencies as $code => $currency}
                <option value="{$code}"
                        {if $processor_params.currency == $code}selected="selected"{/if}
                >{$currency.description}</option>
            {/foreach}
        </select>
    </div>
</div>

{include file="common/subheader.tpl" title=__("paypal_for_markeplaces.settings.funding")}


{foreach ["card", "credit", "elv"] as $source}
    <div class="control-group">
        <label for="elm_funding_{$source}{$suffix}"
               class="control-label"
        >{__("paypal_for_marketplaces.source.`$source`")}:</label>
        <div class="controls">
            <input type="hidden"
                   name="payment_data[processor_params][funding][{$source}]"
                   value=""
            />
            <input type="checkbox"
                   name="payment_data[processor_params][funding][{$source}]"
                   id="elm_funding_{$source}{$suffix}"
                   value="{$source}"
                   {if $processor_params.funding.$source|default:""}checked="checked"{/if}
            />
        </div>
    </div>
{/foreach}


{include file="common/subheader.tpl" title=__("paypal_for_markeplaces.settings.style")}

<div class="control-group">
    <label for="elm_shape{$suffix}"
           class="control-label"
    >{__("paypal_for_marketplaces.style.shape")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][style][shape]"
                id="elm_shape{$suffix}"
        >
            {foreach ["pill", "rect"] as $shape}
                <option value="{$shape}"
                        {if $processor_params.style.shape|default:"pill" == $shape}selected="selected"{/if}
                >{__("paypal_for_marketplaces.shape.`$shape`")}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label for="elm_color{$suffix}"
           class="control-label"
    >{__("paypal_for_marketplaces.style.color")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][style][color]"
                id="elm_color{$suffix}"
        >
            {foreach ["gold", "blue", "silver", "black"] as $color}
                <option value="{$color}"
                        {if $processor_params.style.color|default:"gold" == $color}selected="selected"{/if}
                >{__("paypal_for_marketplaces.color.`$color`")}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label for="elm_size{$suffix}"
           class="control-label"
    >{__("paypal_for_marketplaces.style.size")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][style][size]"
                id="elm_size{$suffix}"
        >
            {foreach ["small", "medium", "large", "responsive"] as $size}
                <option value="{$size}"
                        {if $processor_params.style.size|default:"medium" == $size}selected="selected"{/if}
                >{__("paypal_for_marketplaces.size.`$size`")}</option>
            {/foreach}
        </select>
    </div>
</div>
