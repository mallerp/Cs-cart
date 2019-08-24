{if $vendor_plans_payments}
<script type="text/javascript">
Tygh.$(document).ready(function() {
    Tygh.$.get('{'vendor_plans.async?is_ajax=1'|fn_url:'A':'current' nofilter}');
});
</script>
{/if}

{script src="js/addons/vendor_plans/backend/plan.js"}
{script src="js/addons/vendor_plans/backend/vendor.js"}
