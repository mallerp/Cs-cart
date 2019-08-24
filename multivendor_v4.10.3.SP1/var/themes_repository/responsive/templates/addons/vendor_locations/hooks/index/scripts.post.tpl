<script type="text/javascript">
    (function (_, $) {
        _.vendor_locations = {
            api_key: '{$addons.vendor_locations.api_key|escape:"javascript"}',
            storage_key_geolocation: '{$smarty.const.VENDOR_LOCATIONS_STORAGE_KEY_GEO_LOCATION|escape:"javascript"}',
            storage_key_locality: '{$smarty.const.VENDOR_LOCATIONS_STORAGE_KEY_LOCALITY|escape:"javascript"}',
            customer_geolocation: '{$vendor_locations_geolocation|to_json|escape:"javascript" nofilter}',
            customer_locality: '{$vendor_locations_locality|to_json|escape:"javascript" nofilter}'
        };
    })(Tygh, Tygh.$);
</script>
{script src="js/addons/vendor_locations/geocomplete.js"}
{script src="js/addons/vendor_locations/geolocate.js"}
{script src="js/addons/vendor_locations/geomap.js"}
{script src="js/addons/vendor_locations/func.js"}
