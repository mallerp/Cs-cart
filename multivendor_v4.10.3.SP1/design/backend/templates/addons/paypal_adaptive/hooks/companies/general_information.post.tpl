{if "MULTIVENDOR"|fn_allowed_for}

    {include file="common/subheader.tpl" title=__("pp_adaptive_payments")}
    <div class="control-group">
        <label for="email" class="control-label cm-email">{__("paypal_adaptive.paypal_email")}:</label>
        <div class="controls">
            <input type="text" id="email" name="company_data[paypal_email]" class="input-text" size="32" maxlength="128" value="{$company_data.paypal_email}"/>
        </div>
    </div>
    <div class="control-group">
        <label for="ppa_first_name" class="control-label">{__("addons.paypal_adaptive.first_name")}:</label>
        <div class="controls">
            <input type="text" id="ppa_first_name" name="company_data[ppa_first_name]" class="input-text" size="32" maxlength="128" value="{$company_data.ppa_first_name}"/>
        </div>
    </div>
    <div class="control-group">
        <label for="ppa_last_name" class="control-label">{__("addons.paypal_adaptive.last_name")}:</label>
        <div class="controls">
            <input type="text" id="ppa_last_name" name="company_data[ppa_last_name]" class="input-text" size="32" maxlength="128" value="{$company_data.ppa_last_name}"/>
        </div>
    </div>

{/if}