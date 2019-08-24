(function (_, $) {
    var is_checkout_script_loaded,
        validation_loop;

    var methods = {
        /**
         * Changes default 'Submit my order' button ID.
         * Submit button ID must be altered to prevent 'button_already_has_paypal_click_listener' warning.
         *
         * @param {string} button_id Button ID
         * @returns {string} New button ID
         */
        set_submit_button_id: function (button_id) {
            var button_id_new = button_id + '_' + Date.now();
            var button = $('#' + button_id);
            button.attr('id', button_id_new);

            return button_id_new;
        },

        /**
         * Adds global error handler.
         * @see https://github.com/paypal/paypal-checkout/issues/469
         */
        set_window_close_error_handler: function() {
            window.onerror = function(e) {
                $.redirect(_.current_url);
            };
        },

        /**
         * Provides request to place an order.
         *
         * @param {jQuery} $payment_form
         * @returns {{redirect_on_charge: string, is_ajax: number}}
         */
        get_order_placement_request: function ($payment_form) {
            var form_data = {
                redirect_on_charge: 'N',
                is_ajax: 1
            };
            var fields = $payment_form.serializeArray();
            for (var i in fields) {
                form_data[fields[i].name] = fields[i].value;
            }
            form_data.result_ids = null;

            return form_data;
        },

        /**
         * Renders payment buttons.
         *
         * @param {Object} params Payment form config
         */
        setup_payment_form: function (params) {
            params = params || {};
            params.merchat_id = params.merchat_id || '';
            params.environment = params.environment || 'sandbox';
            params.payment_form = params.payment_form || null;
            params.submit_button_id = params.submit_button_id || '';
            params.style = params.style || {};
            params.style.size = params.style.size || 'medium';
            params.style.color = params.style.color || 'gold';
            params.style.shape = params.style.shape || 'pill';
            params.style.tagline = params.style.tagline || false;
            params.funding = params.funding || {};

            var funding = {
                disallowed: []
            };
            for (var payment_method in params.funding) {
                if (params.funding[payment_method] === '') {
                    payment_method = payment_method.toUpperCase();
                    if (typeof paypal.FUNDING[payment_method] !== 'undefined') {
                        funding.disallowed.push(paypal.FUNDING[payment_method]);
                    }
                }
            }

            $('<div id="' + params.submit_button_id + '_container"></div>')
                .insertAfter($('#' + params.submit_button_id));

            paypal.Button.render({
                env: params.environment,
                style: params.style,
                commit: true,
                validate: function (actions) {
                    validation_loop = setInterval(function () {
                        if (params.payment_form.ceFormValidator('check')) {
                            actions.enable();
                        } else {
                            actions.disable();
                        }
                    }, 300);
                },
                onClick: function () {
                    if (params.payment_form.ceFormValidator('check', false)) {
                        clearInterval(validation_loop);
                    }
                },
                payment: function (data, actions) {
                    return new paypal.Promise(function (resolve, reject) {
                        $.ceAjax('request', fn_url('checkout.place_order'), {
                            data: methods.get_order_placement_request(params.payment_form),
                            method: 'post',
                            hidden: true,
                            caching: false,
                            callback: function (res) {
                                if (res.error) {
                                    reject(null);
                                    return;
                                }
                                if (res.id) {
                                    resolve(res.id);
                                }
                            }
                        });
                    });
                },
                funding: funding,
                onAuthorize: function (data, actions) {
                    actions.redirect();
                },
                onCancel: function (data, actions) {
                    actions.redirect();
                },
                onError: function (data, actions) {
                    // needs to be set to handle errors
                }
            }, params.submit_button_id + '_container');
        },

        /**
         * Initailizes payment form.
         *
         * @param {jQuery} $payment Payment method
         */
        init: function ($payment) {
            var $payment_form = $payment.closest('form');

            var submit_button_id = methods.set_submit_button_id($payment.data('caPaypalForMarketplacesButton')),
                $submit_button = $('#' + submit_button_id);
            $submit_button.addClass('hidden');

            methods.set_window_close_error_handler();

            var checkout_script_load_callback = function () {

                is_checkout_script_loaded = true;

                var paypal_presence_checker = setInterval(function () {
                    if (typeof paypal !== 'undefined') {
                        clearInterval(paypal_presence_checker);
                        methods.setup_payment_form({
                            merchant_id: $payment.data('caPaypalForMarketplacesPayerId'),
                            environment: $payment.data('caPaypalForMarketplacesEnvironment'),
                            payment_form: $payment_form,
                            submit_button_id: submit_button_id,
                            style: {
                                color: $payment.data('caPaypalForMarketplacesStyleColor'),
                                size: $payment.data('caPaypalForMarketplacesStyleSize'),
                                shape: $payment.data('caPaypalForMarketplacesStyleShape'),
                                layout: 'vertical'
                            },
                            funding: $payment.data('caPaypalForMarketplacesFunding')
                        });
                    }
                }, 300);
            };

            if (is_checkout_script_loaded) {
                checkout_script_load_callback();
            } else {
                $.getScript('//www.paypalobjects.com/api/checkout.min.js', checkout_script_load_callback);
            }
        }
    };

    $.extend({
        cePaypalForMarketplacesCheckout: function (method) {
            if (methods[method]) {
                return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
            } else {
                $.error('ty.paypalForMarketplacesCheckout: method ' + method + ' does not exist');
            }
        }
    });

    $.ceEvent('on', 'ce.commoninit', function (context) {
        if (_.embedded) {
            return;
        }
        var $payment = $('[data-ca-paypal-for-marketplaces-checkout]', context);
        if ($payment.length) {
            $.cePaypalForMarketplacesCheckout('init', $payment);
        }
    });
})(Tygh, Tygh.$);