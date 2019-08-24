<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Addons\Stripe\PaymentButton;

use Tygh\Application;
use Tygh\Enum\YesNo;

/**
 * Class DataLoader loads payment information to perform an order placement using Stripe instant payment buttons.
 *
 * @package Tygh\Addons\Stripe\PaymentButton
 */
class DataLoader
{
    /**
     * @var \Tygh\Application
     */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Gets payment methods that can provide payment buttons.
     *
     * @param string $script
     *
     * @return array
     */
    public function getSupportedPayments($script = '')
    {
        $payments = [];

        $stripe_payments = fn_get_payments([
            'processor_script' => $script,
        ]);

        foreach ($stripe_payments as $payment_info) {
            if (!$payment_info['processor_params']) {
                continue;
            }

            $payment_id = $payment_info['payment_id'];
            $button = @unserialize($payment_info['processor_params']);
            if (!isset(
                $button['payment_type'],
                $button['show_payment_button'],
                $button['publishable_key'],
                $button['secret_key'],
                $button['currency']
            )) {
                continue;
            }

            if ($button['payment_type'] !== 'card'
                && $button['publishable_key']
                && $button['secret_key']
                && $button['currency']
            ) {
                $payments[$payment_id] = [
                    'payment_id'          => $payment_id,
                    'payment_type'        => $button['payment_type'],
                    'publishable_key'     => $button['publishable_key'],
                    'currency'            => $button['currency'],
                    'country'             => $button['country'],
                    'show_payment_button' => YesNo::toBool($button['show_payment_button']),
                    'is_test'             => YesNo::toBool($button['is_test']),
                    'is_setup'            => false,
                ];
            }
        }

        return $payments;
    }

    /**
     * Forms and calculates cart content using the provided data
     *
     * @param int        $product_id      Purchased product ID
     * @param array|null $product_options Purchased product options
     * @param array      $user_data       Customer information
     * @param int|null   $shipping_id     If the specific shipping ID is passed, it will be used to calculate cart
     *                                    total.
     *                                    If null is passed, the first available shipping method will be used.
     *
     * @return array
     */
    public function calculateCartContent(
        $product_id,
        array $product_options = null,
        array $user_data = [],
        $shipping_id = null
    ) {
        $auth = &$this->application['session']['auth'];

        fn_clear_cart($cart);

        /** @var \Tygh\Location\Manager $manager */
        $manager = $this->application['location'];

        // prefill some address fields from default settings when it's necessary
        list($cart['user_data'],) = $manager->setLocationFromUserData($user_data);

        fn_add_product_to_cart([
            $product_id => [
                'product_id'      => $product_id,
                'amount'          => 1,
                'product_options' => $product_options,
            ],
        ], $cart, $auth);

        if ($shipping_id) {
            fn_checkout_update_shipping($cart, [$shipping_id]);
        }

        fn_calculate_cart_content($cart, $auth);

        return $cart;
    }

    /**
     * Loads payment data for buttons.
     *
     * @param array          $payment_buttons Payment buttons
     * @param int            $product_id      Purchased product ID
     * @param array|null     $product_options Purchased product options
     * @param array          $user_data       Customer information
     * @param int|null|false $shipping_id     If the specific shipping ID is passed, it will be used to calculate cart
     *                                        total.
     *                                        If null is passed, the first available shipping method will be used.
     *                                        If false is passed, the shipping estimation will not be performed at all.
     *
     * @return array
     */
    public function loadPaymentRequestData(
        array $payment_buttons,
        $product_id,
        array $product_options = null,
        array $user_data = [],
        $shipping_id = null
    ) {
        $cart = $this->calculateCartContent($product_id, $product_options, $user_data, $shipping_id);
        $product = reset($cart['products']);
        $shippings = reset($cart['product_groups'])['shippings'];

        /** @var \Tygh\Addons\Stripe\PriceFormatter $formatter */
        $formatter = $this->application['addons.stripe.price_formatter'];

        foreach ($payment_buttons as &$btn) {
            /**
             * Payment icon should be displayed alonside another payment methods' icons, if there are payment methods
             * that have the specified payment type configured.
             * However, if the "Show payment button on product pages" setting is disabled for a payment method,
             * its button shouldn't be displayed on product pages.
             */
            if (!$btn['show_payment_button']) {
                continue;
            }

            $btn['product_id'] = $product_id;
            $btn['product_options'] = $product['product_options'];
            $btn['total'] = $formatter->asCents($cart['total'], $btn['currency']);

            $btn['display_items'] = [];
            $btn['display_items'][] = [
                'amount'  => $formatter->asCents($product['price'], $btn['currency']),
                'label'   => $product['product'],
                'pending' => true,
            ];

            $btn['shipping_options'] = [];
            if ($shipping_id !== false) {
                foreach ($shippings as $shipping) {
                    $btn['shipping_options'][] = [
                        'id'     => 'shipping_' . $shipping['shipping_id'],
                        'label'  => $shipping['shipping'],
                        'detail' => strip_tags($shipping['description']),
                        'amount' => $formatter->asCents($shipping['rate'], $btn['currency']),
                    ];
                }
            }

            $btn['is_setup'] = true;
        }
        unset($btn);

        return $payment_buttons;
    }
}
