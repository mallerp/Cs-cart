{if $show_block_alert}
    <div class="alert alert-block alert-error debt-notification">
        <div class="debt-notification__text">
            {$block_alert nofilter}
        </div>
        <div class="debt-notification__button">
            {include file="addons/vendor_debt_payout/views/vendor_debt_payout/components/pay_debt_button.tpl"
                     pay_debt_class="btn-large"
            }
        </div>
    </div>
{/if}