{*
Payment form.
*}

{if $payment_method.processor_params|default:[]}
    {$processor_params = $payment_method.processor_params}
{else}
    {$processor_params = $payment_info.processor_params|default:[]}
{/if}

{if $processor_params.is_stripe|default:false}
    {$payment_type = $processor_params.payment_type|default:"card"}
    {if $payment_type === "card"}
        {script src="js/addons/stripe/views/card.js" class="cm-ajax-force"}
    {else}
        {script src="js/addons/stripe/views/payment_button.js" class="cm-ajax-force"}
        {script src="js/addons/stripe/payments/{$payment_type}.js" class="cm-ajax-force"}
    {/if}
    <div class="litecheckout__group clearfix"
         data-ca-stripe-element="form"
         data-ca-stripe-publishable-key="{$processor_params.publishable_key}"
    >
        <input type="hidden"
               name="payment_info[stripe.token]"
               data-ca-stripe-element="token"
        />

        {include file = "addons/stripe/views/checkout/components/payments/{$payment_type}.tpl"
            payment_type = $payment_type
            total = $stripe_cart_total
            currency = $processor_params.currency
            country = $cart.user_data.b_country
        }
    </div>
{/if}
