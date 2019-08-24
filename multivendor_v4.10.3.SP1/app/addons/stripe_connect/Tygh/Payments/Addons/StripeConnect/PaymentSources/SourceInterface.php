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

namespace Tygh\Payments\Addons\StripeConnect\PaymentSources;

interface SourceInterface
{
    /**
     * SourceInterface constructor.
     *
     * @param array $order_info Charged info
     */
    public function __construct(array $order_info);

    /**
     * Provides charge source parameter name for API request.
     *
     * @return string
     */
    public function getChargeRequestParameterName();

    /**
     * Provides charge source parameter value for API request.
     *
     * @return string
     */
    public function getChargeRequestParameterValue();

    /**
     * Removes stored data.
     */
    public function destroy();
}