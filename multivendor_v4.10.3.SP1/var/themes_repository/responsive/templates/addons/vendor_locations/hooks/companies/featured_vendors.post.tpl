{if isset($company.distance)}
    <div class="ty-grid-list__company-distance">
        <a href="{"companies.products?company_id=`$company.company_id`"|fn_url}" class="ty-company-distance">
        {if round($company.distance, 2) > 1}
            <i class="ty-icon-location-arrow"></i>&nbsp;
            {round($company.distance, 2)} {$addons.vendor_locations.distance_unit}</a>
        {else}
            {__("vendor_locations.nearby")}
        {/if}
    </div>
{/if}
