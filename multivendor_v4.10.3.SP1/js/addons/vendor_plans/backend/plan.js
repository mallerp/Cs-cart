(function (_, $) {
    var isConfirmationRequired = true;

    /**
     * Checks whether any item of an array is present in another array.
     *
     * @param {Array} arr1
     * @param {Array} arr2
     *
     * @returns {boolean}
     */
    function intersects(arr1, arr2) {
        for (var i in arr1) {
            if (arr2.indexOf(arr1[i]) !== -1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether an array includes all elements of another array.
     *
     * @param {Array} arr1
     * @param {Array} arr2
     *
     * @returns {boolean}
     */
    function includes(arr1, arr2) {
        var diff = $(arr1).not(arr2).get();

        return diff.length === 0;
    }

    /**
     * Builds information about added and removed storefronts.
     *
     * @param {jQuery} $oldState Old storefronts state
     * @param {jQuery} $newState New storefronts state
     *
     * @returns {object} {storefrontId: int, companyIds: array, isChecked: bool}
     */
    function buildStorefrontsState($oldState, $newState)
    {
        var result = {};

        $oldState.each(function(i, storefront) {
            var $storefront = $(storefront),
                storefrontId = $storefront.data('caStorefrontId'),
                companyIds = $storefront.data('caStorefrontCompanyIds');
            if (!storefrontId) {
                return;
            }

            result[storefrontId] = {
                storefrontId: storefrontId,
                companyIds: companyIds,
                isChecked: false,
            };
        });
        
        $newState.each(function (i, storefront) {
            var $storefront = $(storefront),
                storefrontId = $storefront.data('caStorefrontId'),
                companyIds = $storefront.data('caStorefrontCompanyIds');
            if (!storefrontId) {
                return;
            }

            result[storefrontId] = {
                storefrontId: storefrontId,
                companyIds: companyIds,
                isChecked: true,
            };
        });

        return result;
    }

    $.ceEvent('on', 'ce.commoninit', function (context) {
        var $configForms = $('[data-ca-vendor-plans-is-update-form="true"]', context);
        if ($configForms.length === 0) {
            return;
        }

        $configForms.each(function (i, form) {
            var $form = $(form);

            var formName = $form.prop('name');
            var selectedStorefronts = $form.data('caVendorPlansSelectedStorefronts').map(function(i) {
                return parseInt(i);
            });
            var affectedVendors = $form.data('caVendorPlansAffectedVendors');
            if (!affectedVendors || !affectedVendors.length) {
                return;
            }

            var $storefrontsOldState = $('.storefront:not(.cm-clone)', $form);

            $.ceEvent('on', 'ce.formpost_' + formName, function () {
                if (!isConfirmationRequired) {
                    return true;
                }

                var hasAddedStorefronts = false,
                    hasRemovedStorefronts = false;

                var $storefrontsNewState = $('.storefront', $form);

                var storefronts = buildStorefrontsState($storefrontsOldState, $storefrontsNewState);
                $.each(storefronts, function (j, storefront) {
                    var storefrontId = storefront.storefrontId;
                    var storefrontVendors = storefront.companyIds;
                    if (storefront.isChecked &&
                        selectedStorefronts.indexOf(storefrontId) === -1 &&
                        !includes(affectedVendors, storefrontVendors)
                    ) {
                        hasAddedStorefronts = true;
                        return;
                    }
                    if (!storefront.isChecked &&
                        selectedStorefronts.indexOf(storefrontId) !== -1 &&
                        intersects(affectedVendors, storefrontVendors)
                    ) {
                        hasRemovedStorefronts = true;
                    }
                });

                if (!hasAddedStorefronts && !hasRemovedStorefronts) {
                    return;
                }

                var confirmationDialogId = $form.data('caVendorPlansVendorsUpdateDialogId'),
                    $confirmationDialog = $('#' + confirmationDialogId);

                $('.vendor-plan__storefronts-update-action--add', $confirmationDialog).toggleClass('hidden', !hasAddedStorefronts);
                $('.vendor-plan__storefronts-update-action--remove', $confirmationDialog).toggleClass('hidden', !hasRemovedStorefronts);

                var params = $.ceDialog('get_params', $confirmationDialog);
                params.onClose = function () {
                    isConfirmationRequired = true;
                };

                $confirmationDialog.ceDialog('open', params);
                isConfirmationRequired = false;

                return false;
            });
        });
    });
})(Tygh, Tygh.$);
