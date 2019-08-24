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

use Tygh\Payments\Addons\PaypalForMarketplaces\PaypalForMarketplaces;
use Tygh\Tygh;

/** @var array $order_info */
/** @var array $processor_data */

/** @var \Tygh\Addons\PaypalForMarketplaces\ProcessorFactory $processor_factory */
$processor_factory = Tygh::$app['addons.paypal_for_marketplaces.processor.factory'];

if (defined('PAYMENT_NOTIFICATION')) {

    $_REQUEST = array_merge(array(
        'order_id' => null,
        'token'    => null,
    ), $_REQUEST);

    $order_id = $_REQUEST['order_id'];

    $order_info = fn_get_order_info($order_id);

    if (!$order_info
        || !fn_check_payment_script(PaypalForMarketplaces::getScriptName(), $order_id)
        || $order_info['payment_info']['paypal_for_marketplaces.order_id'] !== $_REQUEST['token']
    ) {
        die('Access denied');
    }

    $processor = $processor_factory->getByPayment($order_info['payment_id'], $order_info['payment_method']);

    $pp_response = array();

    if ($mode == 'return') {
        $result = $processor->pay($_REQUEST['token']);
        if ($result->isSuccess()) {
            $pp_response['order_status'] = 'O'; // keep order open until IPN arrives
        } else {
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = $result->getFirstError();
        }
    } elseif ($mode == 'cancel') {
        $pp_response['order_status'] = STATUS_INCOMPLETED_ORDER;
    }

    fn_finish_payment($order_id, $pp_response);
    fn_order_placement_routines('route', $order_id);
} else {
    $params = array_merge(array(
        'redirect_on_charge' => 'Y',
    ), $_REQUEST);

    $redirect_on_charge = $params['redirect_on_charge'] == 'Y';

    $processor = $processor_factory->getByPayment($order_info['payment_id'], $processor_data);
    $processor->setRedirectOnCharge($redirect_on_charge);

    $pp_response = $processor->charge($order_info);

    if (!$redirect_on_charge && defined('AJAX_REQUEST')) {
        if ($pp_response['order_status'] == 'F') {
            fn_set_notification('E', __('error'), $pp_response['reason_text']);
            Tygh::$app['ajax']->assign('error', true);
        } else {
            Tygh::$app['ajax']->assign('id', $processor->getPaymentApprovalId());
        }
        exit;
    }
}