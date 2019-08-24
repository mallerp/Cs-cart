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
use Tygh\Registry;

/**
 * Installs Paypal For Marketplaces payment processor.
 */
function fn_paypal_for_marketplaces_install()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    if (!$db->getField('SELECT type FROM ?:payment_processors WHERE processor_script = ?s',
        PaypalForMarketplaces::getScriptName())) {
        $db->query('INSERT INTO ?:payment_processors ?e', array(
            'processor'          => __('paypal_for_marketplaces.paypal_for_marketplaces'),
            'processor_script'   => PaypalForMarketplaces::getScriptName(),
            'processor_template' => 'addons/paypal_for_marketplaces/views/orders/components/payments/paypal_for_marketplaces.tpl',
            'admin_template'     => 'paypal_for_marketplaces.tpl',
            'callback'           => 'Y',
            'type'               => 'P',
            'addon'              => PaypalForMarketplaces::getPaymentName(),
        ));
    }
}

/**
 * Disables Paypal For Marketplaces payment methods upon add-on uninstallation.
 */
function fn_paypal_for_marketplaces_uninstall()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    $processor_id = $db->getField(
        'SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s',
        PaypalForMarketplaces::getScriptName()
    );

    if (!$processor_id) {
        return;
    }

    $db->query('DELETE FROM ?:payment_processors WHERE processor_id = ?i', $processor_id);
    $db->query(
        'UPDATE ?:payments SET ?u WHERE processor_id = ?i',
        array(
            'processor_id'     => 0,
            'processor_params' => '',
            'status'           => 'D',
        ),
        $processor_id
    );
}

/**
 * Hook handler: removes Paypal For Marketplaces method from customer area when products' vendor has no PayPal account
 * connected.
 *
 * @param array  $params
 * @param string $fields
 * @param string $join
 * @param string $order
 * @param array  $condition
 * @param string $having
 */
function fn_paypal_for_marketplaces_get_payments(&$params, &$fields, &$join, &$order, &$condition, &$having)
{
    if ($params['area'] == 'C' && !empty(Tygh::$app['session']['cart']['product_groups'])) {
        foreach (Tygh::$app['session']['cart']['product_groups'] as $product_group) {
            if (!PaypalForMarketplaces::getChargeReceiver($product_group['company_id'])) {
                $condition[] = db_quote(
                    '(?:payment_processors.processor_script IS NULL'
                    . ' OR ?:payment_processors.processor_script <> ?s)',
                    PaypalForMarketplaces::getScriptName()
                );
                break;
            }
        }
    }
}

/**
 * Hook handler: performs refund via Paypal For Marketplaces when returning order/products via RMA add-on.
 *
 * @param array  $data
 * @param bool   $show_confirmation_page
 * @param bool   $show_confirmation
 * @param bool   $is_refund
 * @param array  $_data
 * @param string $confirmed
 */
function fn_paypal_for_marketplaces_rma_update_details_post(
    &$data,
    &$show_confirmation_page,
    &$show_confirmation,
    &$is_refund,
    &$_data,
    &$confirmed
) {
    if (empty($data['change_return_status']['paypal_for_marketplaces_perform_refund'])) {
        return;
    }

    $change_return_status = $data['change_return_status'];

    $order_info = fn_get_order_info($change_return_status['order_id']);
    $return_statuses = fn_get_statuses(STATUSES_RETURN);

    if ($change_return_status['status_to'] != $change_return_status['status_from']
        && $return_statuses[$change_return_status['status_to']]['params']['inventory'] != 'D'
        && !empty($order_info['payment_method']['processor_params']['is_paypal_for_marketplaces'])
        && !empty($order_info['payment_info']['paypal_for_marketplaces.capture_id'])
        && empty($order_info['payment_info']['paypal_for_marketplaces.refund_id'])
    ) {
        $amount = 0;

        $return_data = fn_get_return_info($change_return_status['return_id']);

        if (!empty($order_info['returned_products'])) {
            foreach ($order_info['returned_products'] as $cart_id => $product) {
                if (isset($return_data['items']['A'][$cart_id])) {
                    $amount += $product['subtotal'];
                }
            }
        } elseif (!empty($order_info['products'])) {
            foreach ($order_info['products'] as $cart_id => $product) {
                if (isset($product['extra']['returns']) && isset($return_data['items']['A'][$cart_id])) {
                    foreach ($product['extra']['returns'] as $return_id => $product_return_data) {
                        $amount += $return_data['items']['A'][$cart_id]['price'] * $product_return_data['amount'];
                    }
                }
            }
        }

        if ($amount) {
            /** @var \Tygh\Addons\PaypalForMarketplaces\ProcessorFactory $processor_factory */
            $processor_factory = Tygh::$app['addons.paypal_for_marketplaces.processor.factory'];

            $processor = $processor_factory->getByPayment(
                $order_info['payment_id'],
                $order_info['payment_method']
            );

            $refund = $processor->refund($order_info, $amount);

            if ($refund->isSuccess()) {
                fn_update_order_payment_info($order_info['order_id'], array(
                    'paypal_for_marketplaces.refund_id' => $refund->getData(),
                ));

                if ($order_status = Registry::get('addons.paypal_for_marketplaces.rma_refunded_order_status')) {
                    fn_change_order_status($order_info['order_id'], $order_status, '', false);
                }

                fn_set_notification('N', __('notice'), __('paypal_for_marketplaces.rma.refund_performed'));
            } else {
                fn_set_notification('E', __('error'), $refund->getFirstError());
            }
        }
    }
}

/**
 * Hook handler: adds PayPal for Marketplaces columns into the list of selected from ?:companies table fields.
 *
 * @param array  $params
 * @param array  $fields
 * @param array  $sortings
 * @param string $condition
 * @param string $join
 * @param array  $auth
 * @param string $lang_code
 * @param string $group
 */
function fn_paypal_for_marketplaces_get_companies(
    &$params,
    &$fields,
    &$sortings,
    &$condition,
    &$join,
    &$auth,
    &$lang_code,
    &$group
) {
    $fields[] = db_quote('?:companies.paypal_for_marketplaces_account_id');
}
