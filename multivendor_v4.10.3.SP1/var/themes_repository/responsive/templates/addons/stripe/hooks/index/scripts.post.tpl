{script src="https://js.stripe.com/v3/"}
{script src="js/addons/stripe/checkout.js"}
{script src="js/addons/stripe/views/instant_payment.js"}

<script type="application/javascript">
    (function (_) {
        _.tr({
            'stripe.online_payment': '{__("stripe.online_payment")|escape:javascript}'
        });
    })(Tygh);
</script>
