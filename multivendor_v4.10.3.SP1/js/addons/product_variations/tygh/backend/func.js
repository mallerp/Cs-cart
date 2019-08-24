(function(_, $) {
    /**
     * Toggles save buttons on variations tabs of product update page
     */
    $.ceEvent('on', 'ce.tab.show', function () {
        $('.cm-product-save-buttons').toggleClass('hidden', $('#variations').hasClass('active'));
    });
}(Tygh, Tygh.$));
