{*
int $company_id Company identifier
*}
<div class="cm-dialog-auto-size cm-keep-in-place hidden"
     data-ca-dialog-title="{__("vendor_plans.storefronts_update_for_vendor.title")}"
     id="update_company_vendors_update_dialog_{$company_id}"
>
    <div class="vendor-plan__storefronts-update__messages">
        <div class="vendor-plan__storefronts-update__message vendor-plan__storefronts-update__message--general">
            {__("vendor_plans.storefronts_update_for_vendor.general_message")}
        </div>
        <div class="vendor-plan__storefronts-update-action vendor-plan__storefronts-update-action--add">
            <label class="checkbox">
                <input type="checkbox" name="company_data[add_vendor_to_new_storefronts]">
                {__("vendor_plans.storefronts_update_for_vendor.add_storefronts_message")}
            </label>
        </div>
        <div class="vendor-plan__storefronts-action vendor-plan__storefronts-update-action--remove">
            <label class="checkbox">
                <input type="checkbox" name="company_data[remove_vendor_from_old_storefronts]">
                {__("vendor_plans.storefronts_update_for_vendor.remove_storefronts_message")}
            </label>
        </div>
    </div>
    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl"
            but_name="dispatch[companies.update]"
            but_text=__("continue")
        }
    </div>
</div>
