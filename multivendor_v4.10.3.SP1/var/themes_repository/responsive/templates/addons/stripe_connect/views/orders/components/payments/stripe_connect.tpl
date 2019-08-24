{if $payment_method.processor_params|default:[]}
    {$processor_params = $payment_method.processor_params}
{else}
    {$processor_params = $payment_info.processor_params|default:[]}
{/if}

{if $processor_params.is_stripe_connect|default:false}
    <div class="clearfix"
     data-ca-stripe-connect-element="form"
     data-ca-stripe-connect-publishable-key="{$processor_params.publishable_key}"
>
    <input type="hidden"
           name="payment_info[stripe_connect.token]"
           data-ca-stripe-connect-element="token"
    />

    <div class="ty-credit-card cm-cc_form">
        <div class="ty-credit-card__control-group ty-control-group">
            <label for="credit_card_number"
                   class="ty-control-group__title cm-cc-number cc-number cm-required"
            >{__("card_number")}</label>
            <div class="stripe-connect-payment-form__card"
                 data-ca-stripe-connect-element="card"
            >{* Card number field *}</div>
        </div>

        <div class="ty-credit-card__control-group ty-control-group">
            <label for="credit_card_month"
                   class="ty-control-group__title cm-cc-date cc-date cm-cc-exp-month cm-required"
            >{__("valid_thru")}</label>
            <div class="stripe-connect-payment-form__expiry"
                 data-ca-stripe-connect-element="expiry"
            >{* Expriry field *}</div>
        </div>

        <div class="ty-credit-card__control-group ty-control-group">
            <label for="credit_card_name"
                   class="ty-control-group__title cm-required"
            >{__("cardholder_name")}</label>
            <input size="35"
                   type="text"
                   id="credit_card_name"
                   value=""
                   class="cm-cc-name ty-credit-card__input ty-uppercase"
                   data-ca-stripe-connect-element="name"
            />
        </div>
    </div>

    <div class="ty-control-group ty-credit-card__cvv-field cvv-field">
        <label for="credit_card_cvv2" class="ty-control-group__title cm-required cm-cc-cvv2  cc-cvv2 cm-autocomplete-off">{__("cvv2")}</label>
        <div class="stripe-connect-payment-form__cvc"
             data-ca-stripe-connect-element="cvc"
        >{* CVC field *}</div>

        <div class="ty-cvv2-about">
            <span class="ty-cvv2-about__title">{__("what_is_cvv2")}</span>
            <div class="ty-cvv2-about__note">

                <div class="ty-cvv2-about__info mb30 clearfix">
                    <div class="ty-cvv2-about__image">
                        <img src="{$images_dir}/visa_cvv.png" alt="" />
                    </div>
                    <div class="ty-cvv2-about__description">
                        <h5 class="ty-cvv2-about__description-title">{__("visa_card_discover")}</h5>
                        <p>{__("credit_card_info")}</p>
                    </div>
                </div>
                <div class="ty-cvv2-about__info clearfix">
                    <div class="ty-cvv2-about__image">
                        <img src="{$images_dir}/express_cvv.png" alt="" />
                    </div>
                    <div class="ty-cvv2-about__description">
                        <h5 class="ty-cvv2-about__description-title">{__("american_express")}</h5>
                        <p>{__("american_express_info")}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{/if}