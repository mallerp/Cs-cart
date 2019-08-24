<div class="control-group">
    <label for="elm_company_location" class="control-label">{__("vendor_locations.location")}:</label>
    <div class="controls">
        {$place_id = null}
        {if $company_data.vendor_location}
            {$place_id=$company_data.vendor_location->getPlaceId()}
        {/if}
        <input type="text" class="cm-geocomplete input-large" data-ca-geocomplete-type="address" data-ca-geocomplete-place-id="{$place_id}" data-ca-geocomplete-value-elem-id="elm_company_vendor_location_value" id="elm_company_location" />

        <input type="hidden" name="company_data[vendor_location]" id="elm_company_vendor_location_value" />
    </div>
</div>
