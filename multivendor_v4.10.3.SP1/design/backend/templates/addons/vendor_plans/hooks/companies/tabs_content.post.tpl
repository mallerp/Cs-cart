<div id="content_plan" class="hidden">

    {if $runtime.company_id}
        <p>{__("vendor_plans.choose_your_plan")}</p>
        {include file="addons/vendor_plans/views/vendor_plans/components/plans_selector.tpl" plans=$vendor_plans current_plan_id=$company_data.plan_id name="company_data[plan_id]"}
    {else}
        <div class="control-group">
            <label class="control-label" for="elm_company_plan">{__("vendor_plans.plan")}:</label>
            <div class="controls">
                {$current_plan = null}
                {capture name="vendor_plans"}
                    {foreach from=$vendor_plans item="plan"}
                        {if ($plan.plan_id == $company_data.plan_id) || (!$company_data.plan_id && $plan.is_default)}
                            {$current_plan = $plan}
                        {/if}
                        {strip}
                            <option value="{$plan.plan_id}"
                                    {if ($plan.plan_id == $company_data.plan_id) || (!$company_data.plan_id && $plan.is_default)}selected="selected"{/if}
                                    data-ca-vendor-plans-storefronts="{$plan.storefront_ids|json_encode}"
                            >
                                {$plan->plan}
                                ({include file="common/price.tpl" value=$plan->price})
                            </option>
                        {/strip}
                    {/foreach}
                {/capture}
                <select name="company_data[plan_id]"
                        id="elm_company_plan"
                        class="cm-object-selector"
                        data-ca-vendor-plans-is-plan-selector="true"
                        data-ca-vendor-plans-selected-storefronts="{$current_plan.storefront_ids|json_encode}"
                        data-ca-vendor-plans-vendors-update-dialog-id="update_company_vendors_update_dialog_{$id}"
                >
                    {$smarty.capture.vendor_plans nofilter}
                </select>
            </div>
        </div>
    {/if}

    {if $id}
        {include file="addons/vendor_plans/views/vendor_plans/components/storefronts_update_for_vendor_dialog.tpl"
            company_id = $id
        }
    {/if}
</div>
