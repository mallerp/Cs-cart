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

namespace Tygh\Addons\Stripe\HookHandlers;

use Tygh\Application;

class PaymentsHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "get_payments" hook handler.
     *
     * Actions performed:
     *  - Adds an ability to search payments by the payment processor.
     *
     * @see \fn_get_payments()
     */
    public function onGetPayments($params, $fields, $join, $order, &$condition, $having)
    {
        if (isset($params['processor_script'])) {
            /** @var \Tygh\Database\Connection $db */
            $db = $this->application['db'];
            $condition['processor_script'] = $db->quote(
                '?:payment_processors.processor_script IN (?a)',
                (array) $params['processor_script']
            );
        }
    }
}
