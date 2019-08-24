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

namespace Tygh\Enum\Addons\PaypalForMarketplaces;

class WebhookEventTypes
{
    const CHECKOUT_ORDER_PROCESSED = 'CHECKOUT.ORDER.PROCESSED';
    const PAYMENT_CAPTURE_COMPLETED = 'PAYMENT.CAPTURE.COMPLETED';
    const PAYMENT_CAPTURE_DENIED = 'PAYMENT.CAPTURE.DENIED';
    const PAYMENT_CAPTURE_REFUNDED = 'PAYMENT.CAPTURE.REFUNDED';
    const PAYMENT_REFERENCED_PAYOUT_ITEM_COMPLETED = 'PAYMENT.REFERENCED-PAYOUT-ITEM.COMPLETED';
}