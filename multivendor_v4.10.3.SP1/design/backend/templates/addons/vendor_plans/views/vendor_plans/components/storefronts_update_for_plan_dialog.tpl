{*
int $plan_id                Vendor plan identifier
int $affected_vendors_count Amount of vendors that are affected by the vendor plan change
*}
<div class="cm-dialog-auto-size cm-dialog-keep-in-place hidden"
     data-ca-dialog-title="{__("vendor_plans.storefronts_update_for_plan.title")}"
     id="update_plan_vendors_update_dialog_{$plan_id}"
>
    <div class="vendor-plan__storefronts-update__messages">
        <div class="vendor-plan__storefronts-update__message vendor-plan__storefronts-update__message--general">
            {__("vendor_plans.storefronts_update_for_plan.general_message", [$affected_vendors_count, "[search_url]" => "companies.manage?plan_id={$plan_id}"|fn_url])}
        </div>
        <div class="vendor-plan__storefronts-update-action vendor-plan__storefronts-update-action--add">
            <label class="checkbox">
                <input type="checkbox" name="plan_data[add_vendors_to_new_storefronts]">
                {__("vendor_plans.storefronts_update_for_plan.add_storefronts_message", [$affected_vendors_count])}
            </label>
        </div>
        <div class="vendor-plan__storefronts-action vendor-plan__storefronts-update-action--remove">
            <label class="checkbox">
                <input type="checkbox" name="plan_data[remove_vendors_from_old_storefronts]">
                {__("vendor_plans.storefronts_update_for_plan.remove_storefronts_message", [$affected_vendors_count])}
            </label>
        </div>
    </div>
    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl"
            but_name="dispatch[vendor_plans.update]"
            but_text=__("continue")
        }
    </div>
</div>
