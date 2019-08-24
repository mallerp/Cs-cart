{$payment_info = $cart.payment_id|fn_get_payment_method_data}
{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

<div class="clearfix"
     data-ca-stripe-connect-element="form"
     data-ca-stripe-connect-publishable-key="{$payment_info.processor_params.publishable_key}"
>
    <input type="hidden"
           name="payment_info[stripe_connect.token]"
           data-ca-stripe-connect-element="token"
    />

    <div class="credit-card">
        <div class="control-group ty-control-group">
            <label for="credit_card_number_{$id_suffix}"
                   class="control-label cm-cc-number cm-required"
            >{__("card_number")}</label>
            <div class="controls">
                <div class="stripe-connect-payment-form__card"
                     data-ca-stripe-connect-element="card"
                >{* Card number field *}</div>
            </div>
        </div>

        <div class="control-group ty-control-group">
            <label for="credit_card_month_{$id_suffix}"
                   class="control-label cm-required"
            >{__("valid_thru")}</label>
            <div class="controls">
                <div class="stripe-connect-payment-form__expiry"
                     data-ca-stripe-connect-element="expiry"
                >{* Expriry field *}</div>
            </div>
        </div>

        <div class="control-group ty-control-group">
            <label for="credit_card_name_{$id_suffix}"
                   class="control-label cm-required"
            >{__("cardholder_name")}</label>
            <div class="controls">
                <input size="35"
                       type="text"
                       id="credit_card_name_{$id_suffix}"
                       value=""
                       class="input-text uppercase"
                       data-ca-stripe-connect-element="name"
                />
            </div>
        </div>

        <div class="control-group ty-control-group">
            <label for="credit_card_cvv2_{$id_suffix}"
                   class="control-label cm-required"
            >{__("cvv2")}</label>
            <div class="controls">
                <div class="stripe-connect-payment-form__cvc"
                     data-ca-stripe-connect-element="cvc"
                >{* CVC field *}</div>
                <div class="cvv2">
                    <a>{__("what_is_cvv2")}</a>
                    <div class="popover fade bottom in">
                        <div class="arrow"></div>
                        <h3 class="popover-title">{__("what_is_cvv2")}</h3>
                        <div class="popover-content">
                            <div class="cvv2-note">
                                <div class="card-info clearfix">
                                    <div class="cards-images">
                                        <img src="{$images_dir}/visa_cvv.png" border="0" alt=""/>
                                    </div>
                                    <div class="cards-description">
                                        <strong>{__("visa_card_discover")}</strong>
                                        <p>{__("credit_card_info")}</p>
                                    </div>
                                </div>
                                <div class="card-info ax clearfix">
                                    <div class="cards-images">
                                        <img src="{$images_dir}/express_cvv.png" border="0" alt=""/>
                                    </div>
                                    <div class="cards-description">
                                        <strong>{__("american_express")}</strong>
                                        <p>{__("american_express_info")}</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>