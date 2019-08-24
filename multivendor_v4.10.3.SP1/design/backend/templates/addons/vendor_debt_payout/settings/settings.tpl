<div class="control-group setting-wide">
    <label class="control-label"
           for="elm_vendor_debt_limit"
    >{__("vendor_debt_payout.vendor_debt_limit")}:</label>
    <div class="controls">
        {include file="common/price.tpl"
                 input_name="addon_data[options][`$vendor_debt_limit_id`]"
                 value=$addons.vendor_debt_payout.vendor_debt_limit
                 view="input"
        }
    </div>
</div>

{if $addons.vendor_plans.status|default:"D" == "A"}
    <div class="control-group setting-wide">
        <label class="control-label"
               for="elm_payout_overdue_limit"
        >{__("vendor_debt_payout.payout_overdue_limit")}:</label>
        <div class="controls">
            <input type="text"
                   class="input-small cm-numeric"
                   data-m-dec="0"
                   data-a-sign=" {__("vendor_debt_payout.day_or_days")}"
                   data-p-sign="s"
                   id="elm_payout_overdue_limit"
                   name="addon_data[options][{$payout_overdue_limit_id}]"
                   value="{$addons.vendor_debt_payout.payout_overdue_limit}"
            >
        </div>
    </div>
{/if}