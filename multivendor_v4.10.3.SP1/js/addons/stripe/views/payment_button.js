(function (_, $) {

    var canMakePayment = false;

    function showUnsupportedPaymentMethodMessage($paymentButton) {
        var $message = $($paymentButton.data('caStripeUnsupportedPayment'));
        $message.removeClass('hidden');
    }

    _.stripe = _.stripe || {};
    _.stripe.view = {
        id: 'payment_button',
        name: 'Payment Button',

        render: function (stripeInstance, stripeElementsApi, stripeElements, elements) {
            var $paymentButton = elements.paymentButton,
                $form = elements.form,
                $submitButton = $('[type="submit"]', $form);

            $submitButton.addClass('hidden');

            _.stripe.paymentRequest = new stripeInstance.paymentRequest({
                country: $paymentButton.data('caStripeCountry').toUpperCase(),
                currency: $paymentButton.data('caStripeCurrency').toLowerCase(),
                total: {
                    amount: $paymentButton.data('caStripeTotal'),
                    label: $paymentButton.data('caStripePaymentLabel')
                },
                requestPayerName: false,
                requestPayerEmail: false,
                requestPayerPhone: false,
                requestShipping: false
            });

            _.stripe.paymentRequest.canMakePayment().then(function (result) {
                if (_.stripe.payment.canMakePayment(result)) {
                    canMakePayment = true;
                    $submitButton.removeClass('hidden');
                    return;
                }

                showUnsupportedPaymentMethodMessage($paymentButton);
            });
        },

        addSubmitHandler: function (stripeInstance, stripeElementsApi, stripeElements, elements) {
            var $form = elements.form,
                $token = elements.token,
                $submitButton = $('[type="submit"]', $form);

            // payment request must be shown within the click handler
            $submitButton.on('click', function(e) {
                if ($form.ceFormValidator('check')) {
                    e.preventDefault();
                    if (canMakePayment) {
                        _.stripe.paymentRequest.show();
                    }
                }
            });

            _.stripe.paymentRequest.on('token', function (result) {
                result.complete('success');

                if (result.token) {
                    $token.val(result.token.id);
                    $form.submit();
                }

                if (result.error) {
                    $.ceNotification('show', {
                        type: 'E',
                        title: _.tr('error'),
                        message: result.error.message
                    });
                }
            });

            $.ceEvent('on', 'ce.formpost_' + $form.prop('name'), function ($form) {
                $token = $('[data-ca-stripe-element="token"]', $form);
                if (!$token.length || $token.data('caStripeIsSubmitted')) {
                    return true;
                }

                if (!$token.val()) {
                    return false;
                }

                $token.data('caStripeIsSubmitted', true);
            });

            $token.data('caStripeIsFormReady', true);
        },

        isInitialized: function (elements) {
            return elements.token && elements.token.data('caStripeIsFormReady');
        },

        teardown: function (stripeElements) {
            stripeElements.paymentButton && stripeElements.paymentButton.destroy();
        },

        getElements: function ($form) {
            return {
                paymentButton: $('[data-ca-stripe-element="paymentButton"]', $form)
            };
        }
    };
})(Tygh, Tygh.$);
