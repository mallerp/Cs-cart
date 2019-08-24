(function (_, $) {
    var isConfirmationRequired = true;

    $.ceEvent('on', 'ce.commoninit', function (context) {
        var $planSelectors = $('[data-ca-vendor-plans-is-plan-selector="true"]', context);
        if ($planSelectors.length === 0) {
            return;
        }

        $planSelectors.each(function (i, planSelector) {
            var $planSelector = $(planSelector);
            var $form = $planSelector.closest('form');
            var formName = $form.prop('name');

            var selectedStorefronts = $planSelector.data('caVendorPlansSelectedStorefronts');

            $.ceEvent('on', 'ce.formpost_' + formName, function () {
                if (!isConfirmationRequired) {
                    return true;
                }

                var hasAddedStorefronts = false,
                    hasRemovedStorefronts = false;

                var $newPlan = $planSelector.find(':selected');
                var newStorefronts = $newPlan.data('caVendorPlansStorefronts');
                newStorefronts.forEach(function(storefrontId) {
                    if (selectedStorefronts.indexOf(storefrontId) === -1) {
                        hasAddedStorefronts = true;
                    }
                });

                selectedStorefronts.forEach(function(storefrontId) {
                    if (newStorefronts.indexOf(storefrontId) === -1) {
                        hasRemovedStorefronts = true;
                    }
                });

                if (!hasAddedStorefronts && !hasRemovedStorefronts) {
                    return;
                }

                var confirmationDialogId = $planSelector.data('caVendorPlansVendorsUpdateDialogId'),
                    $confirmationDialog = $('#' + confirmationDialogId);

                $('.vendor-plan__storefronts-update-action--add', $confirmationDialog).toggleClass('hidden', !hasAddedStorefronts);
                $('.vendor-plan__storefronts-update-action--remove', $confirmationDialog).toggleClass('hidden', !hasRemovedStorefronts);

                var params = $.ceDialog('get_params', $confirmationDialog);
                params.onClose = function() {
                    isConfirmationRequired = true;
                };

                $confirmationDialog.ceDialog('open', params);
                isConfirmationRequired = false;

                return false;
            });
        });
    });
})(Tygh, Tygh.$);
