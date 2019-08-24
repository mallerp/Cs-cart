(function(_, $) {

    var stripe_instance;
    var stripe_elements_api;
    var stripe_elements;

    var methods = {

        init: function(publishable_key, lang_code, elements) {

            elements = elements || {};
            elements.form = elements.form || {};
            elements.card = elements.card || {};
            elements.expiry = elements.expiry || {};
            elements.cvc = elements.cvc || {};
            elements.name = elements.name || {};
            elements.token = elements.token || {};

            if (elements.token.data('caStripeConnectIsFormReady')) {
                return;
            }

            stripe_instance = stripe_instance || Stripe(publishable_key);
            stripe_elements_api = stripe_elements_api || stripe_instance.elements({
                locale: lang_code
            });

            // remove previously rendered form
            stripe_elements && methods.teardown_form();

            // render form
            methods.render_form(elements.card, elements.expiry, elements.cvc);

            methods.add_submit_hander(elements.form, elements.name, elements.token);
        },

        render_form: function(jelm_card, jelm_expiry, jelm_cvc) {

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

            stripe_elements = stripe_elements || {};

            stripe_elements.card = stripe_elements_api.create('cardNumber', options);
            stripe_elements.card.mount(jelm_card[0]);

            stripe_elements.expiry = stripe_elements_api.create('cardExpiry', options);
            stripe_elements.expiry.mount(jelm_expiry[0]);

            stripe_elements.cvc = stripe_elements_api.create('cardCvc', options);
            stripe_elements.cvc.mount(jelm_cvc[0]);
        },

        add_submit_hander: function(jelm_form, jelm_name, jelm_token) {

            $.ceEvent('on', 'ce.formpost_' + jelm_form.prop('name'), function(form, clicked_elm) {
                if ($('[data-ca-stripe-connect-element]').length === 0
                    || jelm_token.data('caStripeConnectIsCardTokenized')
                ) {
                    return true;
                }

                if (methods.is_form_valid(form)) {
                    stripe_instance.createToken(stripe_elements.card, {name: jelm_name.val()}).then(function(result) {
                        if (result.token) {
                            jelm_token.val(result.token.id);
                            jelm_token.data('caStripeConnectIsCardTokenized', true);
                            clicked_elm.trigger('click');
                        }
                        if (result.error) {
                            $.ceNotification('show', {
                                type: 'E',
                                title: _.tr('error'),
                                message: result.error.message
                            })
                        }
                    });
                }

                return false;
            });

            jelm_token.data('caStripeConnectIsFormReady', true);
        },

        teardown_form: function() {
            stripe_elements.card.destroy();
            stripe_elements.expiry.destroy();
            stripe_elements.cvc.destroy();
        },

        is_form_valid: function(form) {
            var is_form_complete = true;

            $('.sc-field', form).each(function() {
                var jelm = $(this);
                var is_elm_complete = jelm.hasClass('sc-field--complete');
                jelm.closest('.ty-control-group').toggleClass('error', !is_elm_complete)
                    .find('.cm-required').toggleClass('cm-failed-label', !is_elm_complete);
                is_form_complete = is_form_complete && is_elm_complete;
            });

            return is_form_complete;
        }
    };

    $.extend({
        ceStripeConnectCheckout: function(method) {
            if (methods[method]) {
                return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
            } else {
                $.error('ty.stripeConnectCheckout: method ' + method + ' does not exist');
            }
        }
    });

    $.ceEvent('on', 'ce.commoninit', function() {
        var form = $('[data-ca-stripe-connect-element="form"]');
        var publishable_key = form.data('caStripeConnectPublishableKey');

        if (publishable_key) {
            $.ceStripeConnectCheckout(
                'init',
                publishable_key,
                _.cart_language,
                {
                    form: form.closest('form'),
                    card: $('[data-ca-stripe-connect-element="card"]'),
                    expiry: $('[data-ca-stripe-connect-element="expiry"]'),
                    cvc: $('[data-ca-stripe-connect-element="cvc"]'),
                    name: $('[data-ca-stripe-connect-element="name"]'),
                    token: $('[data-ca-stripe-connect-element="token"]')
                }
            );
        }
    });
})(Tygh, Tygh.$);