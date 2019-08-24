(function (_, $) {

    function isFormValid($form) {
        var isValid = true;

        $('.sc-field', $form).each(function () {
            var $checkoutField = $(this);
            var isFieldComplete = $checkoutField.hasClass('sc-field--complete');
            $checkoutField.closest('.ty-control-group').toggleClass('error', !isFieldComplete)
                .find('.cm-required').toggleClass('cm-failed-label', !isFieldComplete);
            isValid = isValid && isFieldComplete;
        });

        return isValid;
    }

    _.stripe = _.stripe || {};
    _.stripe.view = {
        id: 'card',
        name: 'Credit Card',

        render: function (stripeInstance, stripeElementsApi, stripeElements, elements) {
            var $card = elements.card,
                $expiryDate = elements.expiry,
                $cvc = elements.cvc;

            var options = {
                classes: {
                    base: 'sc-field',
                    complete: 'sc-field--complete',
                    empty: 'sc-field--empty',
                    focus: 'sc-field--focus',
                    invalid: 'sc-field--invalid',
                    webkitAutofill: 'sc-field--autofill'
                },
                style: {
                    base: {
                        fontSize: '18px',
                        color: '#2e3a47'
                    },
                    invalid: {
                        color: '#bf4d4d'
                    }
                }
            };

            stripeElements.card = stripeElementsApi.create('cardNumber', options);
            stripeElements.card.mount($card[0]);

            stripeElements.expiry = stripeElementsApi.create('cardExpiry', options);
            stripeElements.expiry.mount($expiryDate[0]);

            stripeElements.cvc = stripeElementsApi.create('cardCvc', options);
            stripeElements.cvc.mount($cvc[0]);

            return stripeElements;
        },

        addSubmitHandler: function (stripeInstance, stripeElementsApi, stripeElements, elements) {
            var $form = elements.form,
                $token = elements.token,
                $name = elements.name;

            $.ceEvent('on', 'ce.formpost_' + $form.prop('name'), function ($form, $clickedElm) {
                if ($('[data-ca-stripe-element="form"]', $form).length === 0
                    || $token.data('caStripeIsTokenized')
                ) {
                    return true;
                }

                if (isFormValid($form)) {
                    stripeInstance.createToken(stripeElements.card, {name: $name.val()}).then(function (result) {
                        if (result.token) {
                            $token.val(result.token.id);
                            $token.data('caStripeIsTokenized', true);
                            $clickedElm.trigger('click');
                        }
                        if (result.error) {
                            $.ceNotification('show', {
                                type: 'E',
                                title: _.tr('error'),
                                message: result.error.message
                            });
                        }
                    });
                }

                return false;
            });

            $token.data('caStripeIsFormReady', true);
        },

        isInitialized: function (elements) {
            return elements.token && elements.token.data('caStripeIsFormReady');
        },

        teardown: function (stripeElements) {
            stripeElements.card && stripeElements.card.destroy();
            stripeElements.expiry && stripeElements.expiry.destroy();
            stripeElements.cvc && stripeElements.cvc.destroy();
        },

        getElements: function ($form) {
            return {
                card: $('[data-ca-stripe-element="card"]', $form),
                expiry: $('[data-ca-stripe-element="expiry"]', $form),
                cvc: $('[data-ca-stripe-element="cvc"]', $form),
                name: $('[data-ca-stripe-element="name"]', $form)
            };
        }
    };
})(Tygh, Tygh.$);
