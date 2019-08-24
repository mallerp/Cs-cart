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

use Stripe\Customer as StripeCustomer;

class Customer extends Token
{
    /** @var \Stripe\Customer $customer */
    protected $customer;

    /** @var string $source */
    protected $source = 'customer';

    /** @inheritdoc */
    public function __construct(array $order_info)
    {
        parent::__construct($order_info);

        $params = array(
            'email'  => $order_info['email'],
            'source' => $this->token,
        );

        $this->customer = StripeCustomer::create($params);
    }

    /** @inheritdoc */
    public function getChargeRequestParameterValue()
    {
        return $this->customer->id;
    }

    /**
     * Removes stored data.
     */
    public function destroy()
    {
        $this->customer->delete();
    }
}