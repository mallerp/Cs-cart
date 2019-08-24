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

use Tygh\Registry;
use Tygh\VendorPayouts;

/** @var string $mode */

if ($mode == 'index') {
    $vendor_id = Registry::get('runtime.company_id');
    if (!$vendor_id || !defined('AJAX_REQUEST')) {
        return array(CONTROLLER_STATUS_OK);
    }

    $payouts_manager = VendorPayouts::instance(array('vendor' => $vendor_id));
    list($is_debt_limit_exceeded, $balance,) = fn_vendor_debt_payout_is_debt_limit_exceeded($payouts_manager);

    $show_pay_button = $balance < 0;
    $pay_debt_text = __('vendor_debt_payout.pay_fees');

    if (fn_vendor_debt_payout_is_vendor_plans_addon_active()) {
        list($is_payout_overdue, , , $pending_payouts) = fn_vendor_debt_payout_has_overdue_payouts($payouts_manager);
        $show_pay_button = $show_pay_button || $pending_payouts;
    }

    $pay_debt_url = fn_vendor_debt_payout_get_pay_url($vendor_id, Tygh::$app['session']['auth']);

    $block_alert = __('vendor_debt_payout.block_alert', array(
        '[current_balance]' => Tygh::$app['formatter']->asPrice($balance),
        '[pay_url]'         => $pay_debt_url,
    ));
    $show_block_alert = !empty($is_debt_limit_exceeded) || !empty($is_payout_overdue);

    Tygh::$app['view']->assign(array(
        'pay_debt_url'     => $pay_debt_url,
        'pay_debt_text'    => $pay_debt_text,
        'block_alert'      => $block_alert,
        'show_block_alert' => $show_block_alert,
        'show_pay_button'  => $show_pay_button && !$show_block_alert,
    ));
}