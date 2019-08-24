(function (_, $) {
    function getPaymentRequestData(result, $paymentButton) {
        var paymentId = $paymentButton.data('caStripePaymentId'),
            productId = $paymentButton.data('caStripeProductId'),
            productOptions = $paymentButton.data('caStripeProductOptions'),
            shippingId,
            userData,
            paymentInfo;

        if (result.shippingOption) {
            $paymentButton.data('caStripeShippingId', parseInt(result.shippingOption.id.split('_').pop()));
        }

        if ($paymentButton.data('caStripeShippingId')) {
            shippingId = $paymentButton.data('caStripeShippingId');
        }

        if (result.shippingAddress) {
            userData = userData || {};
            userData.s_country = result.shippingAddress.country;
            userData.s_state = result.shippingAddress.region;
            userData.s_city = result.shippingAddress.city;
            userData.s_zipcode = result.shippingAddress.postalCode;
            userData.s_address = result.shippingAddress.addressLine[0];
        }

        if (result.payerName) {
            userData = userData || {};
            var name = result.payerName.split(' '),
                lastname = name.pop(),
                firstname = name.join(' ');

            if (!firstname) {
                [firstname, lastname] = [lastname, firstname];
            }

            userData.firstname = userData.s_firstname = firstname;
            userData.lastname = userData.s_lastname = lastname;
        }

        if (result.payerEmail) {
            userData = userData || {};
            userData.email = result.payerEmail;
        }

        if (result.payerPhone) {
            userData = userData || {};
            userData.phone = userData.s_phone = result.payerPhone;
        }

        if (result.token) {
            paymentInfo = {
                'stripe.token': result.token.id
            }
        }

        return {
            products: [
                {
                    product_id: productId,
                    product_options: productOptions
                }
            ],
            user_data: userData
                ? userData
                : null,
            shipping_ids: shippingId
                ? [shippingId]
                : null,
            payment_id: paymentId,
            payment_info: paymentInfo
                ? paymentInfo
                : null,
            redirect_mode: 'checkout',
            redirect_url: _.current_url
        };
    }

    function updatePaymentRequest(result, response, $paymentButton) {
        // if any error occured, payment request must fail
        if (!response.stripe_payment_buttons) {
            result.updateWith({
                status: 'fail'
            });
            return;
        }

        var paymentId = $paymentButton.data('caStripePaymentId'),
            btn = response.stripe_payment_buttons[paymentId];

        result.updateWith({
            status: 'success',
            total: {
                amount: btn.total,
                label: $paymentButton.data('caStripePaymentLabel'),
                pending: true
            },
            displayItems: btn.display_items,
            shippingOptions: btn.shipping_options
        });
    }

    function setupPaymentButton(stripeInstance, $paymentButton) {
        var paymentRequest = new stripeInstance.paymentRequest({
            country: $paymentButton.data('caStripeCountry').toUpperCase(),
            currency: $paymentButton.data('caStripeCurrency').toLowerCase(),
            total: {
                amount: $paymentButton.data('caStripeTotal'),
                label: $paymentButton.data('caStripePaymentLabel'),
                pending: true
            },
            requestPayerName: true,
            requestPayerEmail: true,
            requestPayerPhone: true,
            requestShipping: true,
            displayItems: $paymentButton.data('caStripeDisplayItems'),
            shippingOptions: $paymentButton.data('caStripeShippingOptions')
        });

        paymentRequest.on('shippingaddresschange', function (result) {
            $.ceAjax('request', fn_url('checkout.customer_info.instant_payment'), {
                method: 'post',
                data: getPaymentRequestData(result, $paymentButton),
                hidden: true,
                caching: false,
                callback: function (response) {
                    updatePaymentRequest(result, response, $paymentButton);
                }
            });
        });

        paymentRequest.on('shippingoptionchange', function (result) {
            $.ceAjax('request', fn_url('checkout.update_shipping.instant_payment'), {
                method: 'post',
                data: getPaymentRequestData(result, $paymentButton),
                hidden: true,
                caching: false,
                callback: function (response) {
                    updatePaymentRequest(result, response, $paymentButton);
                }
            });
        });

        paymentRequest.on('token', function (result) {
            $.ceAjax('request', fn_url('checkout.place_order.instant_payment'), {
                method: 'post',
                data: getPaymentRequestData(result, $paymentButton),
                hidden: true,
                caching: false,
                callback: function (response) {
                    if (response.order_id) {
                        for (var i in response.notifications) {
                            if (response.notifications[i].type === 'N') {
                                delete response.notifications[i];
                            }
                        }

                        result.complete('success');
                        $.redirect(response.current_url);
                    } else {
                        result.complete('fail');
                    }
                }
            });
        });

        paymentRequest.canMakePayment().then(function (result) {
            if (result && result.applePay && $paymentButton.data('caStripePaymentType') === 'apple_pay') {
                $paymentButton.removeClass('hidden');
                $('.stripe-test-mode-notification').removeClass('hidden');
            }

            if (result && !result.applePay && $paymentButton.data('caStripePaymentType') === 'google_pay') {
                $paymentButton.removeClass('hidden');
                $('.stripe-test-mode-notification').removeClass('hidden');
            }
        });

        $paymentButton.unbind('click');
        $paymentButton.on('click', function (e) {
            e.preventDefault();
            paymentRequest.show();
        });
    }

    var stripeInstances = {};
    $.ceEvent('on', 'ce.commoninit', function (context) {
        var $buttons = $('[data-ca-stripe-element="instantPaymentButton"]', context);
        if (!$buttons.length) {
            return;
        }

        $buttons.each(function (i, button) {
            var $paymentButton = $(button),
                publishableKey = $paymentButton.data('caStripePublishableKey');

            if (!stripeInstances[publishableKey]) {
                stripeInstances[publishableKey] = Stripe(publishableKey);
            }

            var stripeInstance = stripeInstances[publishableKey];
            try {
                setupPaymentButton(stripeInstance, $paymentButton);
            } catch (exception) {
                console.error(exception);
            }
        });
    });

    $.ceEvent('on', 'ce.product_option_changed_post', function (productId, variantId, optionId, updateIds) {
        /**
         * New option values are delivered in the ajax response when changing an option that affects the product price.
         * Manual processing is required only when product has no options that affect price.
         */
        if (updateIds.length) {
            return;
        }

        var $btn = $('[data-ca-stripe-product-id="' + productId + '"]');
        if (!$btn.length) {
            return;
        }

        var options = $btn.data('caStripeProductOptions');
        options[optionId] = parseInt(variantId);
        $btn.data('caStripeProductOptions', options);
    });
})(Tygh, Tygh.$);
