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

use Tygh\Payments\Addons\StripeConnect\StripeConnect;
use Tygh\Registry;

/** @var array $order_info */
/** @var array $processor_data */

if (!empty($order_info['payment_info']['stripe_connect.token'])) {

    $processor = new StripeConnect($order_info['payment_id'], $processor_data);
    $processor->setDb(Tygh::$app['db']);
    $processor->setAddonsSettings(Registry::get('addons.stripe_connect'));
    $processor->setFormatter(Tygh::$app['formatter']);

    $pp_response = $processor->charge($order_info);
}