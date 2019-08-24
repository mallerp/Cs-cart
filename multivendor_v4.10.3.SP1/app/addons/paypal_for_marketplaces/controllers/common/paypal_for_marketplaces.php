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

use Tygh\Addons\PaypalForMarketplaces\Webhook\OrderProcessedEvent;
use Tygh\Enum\Addons\PaypalForMarketplaces\CaptureStatuses;
use Tygh\Enum\Addons\PaypalForMarketplaces\OrderStatuses;
use Tygh\Enum\Addons\PaypalForMarketplaces\WebhookEventTypes;
use Tygh\Payments\Addons\PaypalForMarketplaces\PaypalForMarketplaces;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($mode == 'webhook') {

    $payload_body = file_get_contents('php://input');

    $event = json_decode($payload_body);
    if (!isset($event->event_type)
        || $event->event_type !== WebhookEventTypes::CHECKOUT_ORDER_PROCESSED
    ) {
        die('err_event');
    }

    fn_log_event('general', 'runtime', array(
        'message' => __('paypal_for_marketplaces.payment_notification', array('[payload]' => $payload_body))
    ));

    $event = new OrderProcessedEvent($event);

    $paypal_order_id = $event->getOrderId();

    $primary_order = $event->getFirstOrder();
    if (!$primary_order
        || !fn_check_payment_script(PaypalForMarketplaces::getScriptName(), $primary_order->getId())
    ) {
        die('err_order');
    }

    $order_info = $primary_order->getInfo();
    if (isset($order_info['payment_info']['paypal_for_marketplaces.capture_id'])) {
        die('err_processed');
    }

    /** @var \Tygh\Addons\PaypalForMarketplaces\ProcessorFactory $processor_factory */
    $processor_factory = Tygh::$app['addons.paypal_for_marketplaces.processor.factory'];

    /** @var \Tygh\Payments\Addons\PaypalForMarketplaces\PaypalForMarketplaces $processor */
    $processor = $processor_factory->getByPayment($order_info['payment_id'], $order_info['payment_method']);

    $paypal_order = $processor->getOrder($paypal_order_id);
    if (!$paypal_order
        || $paypal_order['status'] !== OrderStatuses::COMPLETED
    ) {
        die('err_status');
    }

    $pp_response = array(
        'reason_text' => $event->getSummary(),
    );

    foreach ($event->getOrders() as $order) {

        $order_status = $order->getStatus();

        $pp_response['order_status'] = $processor->getStatusByCapture($order_status);

        if ($order_status === CaptureStatuses::CAPTURED) {

            $pp_response['paypal_for_marketplaces.capture_id'] = $order->getCaptureId();

            if ($order->getWithdrawalAmount()) {
                $disbursement = $processor->disbursePayout($order, $order->getCaptureId());
                if ($disbursement->isSuccess()) {
                    $pp_response['paypal_for_marketplaces.payout_id'] = $disbursement->getData();
                } else {
                    $pp_response['paypal_for_marketplaces.payout_failure_reason'] = $disbursement->getFirstError();
                }
            }
        }

        fn_update_order_payment_info($order->getId(), $pp_response);
        fn_change_order_status($order->getId(), $pp_response['order_status']);
    }
}

exit;