<!--Stripe payment buttons-->
{foreach $stripe_payment_buttons as $button}
    {if $button.is_setup}
        {$button_label = __("stripe.online_payment")}
        {if $button.is_test}
            {$button_label = __("stripe.test_payment")}
        {/if}
        <a class="stripe-payment-button stripe-payment-button--{$button.payment_type} hidden"
           data-ca-stripe-element="instantPaymentButton"
           data-ca-stripe-payment-type="{$button.payment_type}"
           data-ca-stripe-publishable-key="{$button.publishable_key}"
           data-ca-stripe-currency="{$button.currency}"
           data-ca-stripe-total="{$button.total}"
           data-ca-stripe-country="{$button.country}"
           data-ca-stripe-display-items="{$button.display_items|json_encode}"
           data-ca-stripe-shipping-options="{$button.shipping_options|json_encode}"
           data-ca-stripe-payment-id="{$button.payment_id}"
           data-ca-stripe-product-id="{$button.product_id}"
           data-ca-stripe-product-options="{$button.product_options|json_encode}"
           data-ca-stripe-payment-label="{$button_label}"
           lang="{$smarty.const.CART_LANGUAGE}"
        ></a>
        {if $button.is_test}
            {capture name="stripe_test_mode_notification"}
                <div class="stripe-test-mode-notification hidden">
                    {__("stripe.test_payment.description")}
                </div>
            {/capture}
        {/if}
    {/if}
{/foreach}
<!--/Stripe payment buttons-->
