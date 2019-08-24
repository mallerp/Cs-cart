{if $is_refund == "Y"
    && $order_info.payment_method.processor_params
    && $order_info.payment_method.processor_params.is_paypal_for_marketplaces|default:null
}
    <div class="control-group notify-department">
        <label class="control-label"
               for="elm_paypal_for_marketplaces_perform_refund"
        >{__("paypal_for_marketplaces.rma.perform_refund")}</label>
        <div class="controls">
            {if $order_info.payment_info["paypal_for_marketplaces.refund_id"]}
                <p class="label label-success">{__("refunded")}</p>
            {else}
                <label class="checkbox">
                    <input type="checkbox"
                           name="change_return_status[paypal_for_marketplaces_perform_refund]"
                           id="elm_paypal_for_marketplaces_perform_refund"
                           value="Y"
                    />
                </label>
            {/if}
        </div>
    </div>
{/if}