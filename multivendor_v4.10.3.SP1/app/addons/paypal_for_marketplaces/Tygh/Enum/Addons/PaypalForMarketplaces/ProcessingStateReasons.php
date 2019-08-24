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

class ProcessingStateReasons
{
    const INTERNAL_ERROR = 'INTERNAL_ERROR';
    const NOT_ENOUGH_BALANCE = 'NOT_ENOUGH_BALANCE';
    const AMOUNT_CHECK_FAILED = 'AMOUNT_CHECK_FAILED';
    const MERCHANT_PARTNER_PERMISSIONS_ISSUE = 'MERCHANT_PARTNER_PERMISSIONS_ISSUE';
    const MERCHANT_RESTRICTIONS = 'MERCHANT_RESTRICTIONS';
    const TRANSACTION_UNDER_DISPUTE = 'TRANSACTION_UNDER_DISPUTE';
    const TRANSACTION_NOT_VALID = 'TRANSACTION_NOT_VALID';
    const UNSUPPORTED_CURRENCY = 'UNSUPPORTED_CURRENCY';
}