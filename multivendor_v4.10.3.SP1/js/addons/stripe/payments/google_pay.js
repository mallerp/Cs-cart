(function (_, $) {
    _.stripe = _.stripe || {};
    _.stripe.payment = {
        id: 'google_pay',
        name: 'Google Pay',
        canMakePayment: function (result) {
            return result && !result.applePay;
        }
    };
})(Tygh, Tygh.$);
