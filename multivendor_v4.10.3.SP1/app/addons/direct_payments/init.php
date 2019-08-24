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

use Tygh\Addons\DirectPayments\ServiceProvider;

Tygh::$app->register(new ServiceProvider());

fn_register_hooks(
    'get_route_runtime',
    'get_payments',
    'user_init',
    'init_user_session_data',
    'pre_extract_cart',
    'save_cart_content_pre',
    'save_cart_content_before_save',
    'user_session_products_condition',
    'get_promotions',
    'promotion_apply_before_get_promotions',
    'change_order_status',
    'get_order_info',
    'update_payment_pre',
    'update_shipping',
    'get_available_shippings',
    'update_promotion_pre',
    'vendor_plans_calculate_commission_for_payout_before',
    'vendor_payouts_update',
    'user_logout_before_save_cart',
    'user_logout_before_clear_cart',
    'prepare_checkout_payment_methods_before_get_payments',
    'prepare_checkout_payment_methods_after_get_payments',
    'checkout_update_user_data_post',
    'prepare_repay_data'
);

