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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Tools\Url;
use Tygh\VendorPayouts;
use Tygh\Registry;

if ($mode == 'pay') {
    fn_define('ORDER_MANAGEMENT', true);

    $cart = &Tygh::$app['session']['cart'];
    $auth = &Tygh::$app['session']['auth'];
    $vendor_id = $auth['company_id'];

    unset($auth['act_as_user']);

    fn_clear_cart($cart, true);
    $cart['user_data'] = fn_get_user_info($auth['user_id'], true, $cart['profile_id']);

    $product_id = fn_vendor_debt_payout_get_payout_product();

    $payouts_manager = VendorPayouts::instance(array('vendor' => $vendor_id));
    list($balance,) = $payouts_manager->getBalance();

    if ($balance < 0) {
        $product_cost = -$balance;
    } elseif (fn_vendor_debt_payout_is_vendor_plans_addon_active()) {
        $pending_payouts = fn_vendor_debt_payout_get_pending_vendor_plan_payouts($payouts_manager);
        $product_cost = array_reduce($pending_payouts, function ($carry, $item) {
            return $carry + $item['payout_amount'];
        }, 0);
    }

    $product_extra = array(
        'vendor_debt_payout' => array(
            'vendor_id' => $vendor_id,
        ),
    );

    fn_add_product_to_cart(
        array(
            $product_id => array(
                'product_id'      => $product_id,
                'amount'          => 1,
                'price'           => $product_cost,
                'product_options' => array(),
                'stored_price'    => 'Y',
                'stored_discount' => 'Y',
                'discount'        => 0,
                'company_id'      => 0,
                'extra'           => $product_extra,
            ),
        ),
        $cart,
        $auth
    );

    list($status, $redirect_params) = fn_checkout_update_steps($cart, $auth, array(
        'update_step' => 'step_three',
        'next_step'   => 'step_four',
    ));

    return array(
        CONTROLLER_STATUS_REDIRECT,
        Url::buildUrn(array('checkout', 'checkout'), $redirect_params),
    );
}