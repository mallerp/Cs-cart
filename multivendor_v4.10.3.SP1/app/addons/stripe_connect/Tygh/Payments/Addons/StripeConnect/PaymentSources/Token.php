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

class Token implements SourceInterface
{
    /** @var string $token */
    protected $token;

    /** @var string $source */
    protected $source = 'source';

    /** @inheritdoc */
    public function __construct(array $order_info)
    {
        $this->token = $order_info['payment_info']['stripe_connect.token'];
    }

    /** @inheritdoc */
    public function getChargeRequestParameterName()
    {
        return $this->source;
    }

    /** @inheritdoc */
    public function getChargeRequestParameterValue()
    {
        return $this->token;
    }

    /** @inheritdoc */
    public function destroy()
    {
        $this->token = null;
    }
}