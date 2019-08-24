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

namespace Tygh\Addons\PaypalForMarketplaces\Webhook;

class OrderProcessedEvent extends Event
{
    /** @var \stdClass[] $purchase_units */
    protected $purchase_units;

    /** @var \Tygh\Addons\PaypalForMarketplaces\Webhook\Order[] $orders */
    protected $orders;

    /**
     * Payload constructor.
     *
     * @param \stdClass $payload
     */
    public function __construct($payload)
    {
        parent::__construct($payload);

        $this->purchase_units = $this->getResource()->purchase_units;
    }

    /**
     * Provides order ID in PayPal.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->getResource()->id;
    }

    /**
     * Provides suborders of the PayPal order.
     *
     * @return \Tygh\Addons\PaypalForMarketplaces\Webhook\Order[]
     */
    public function getOrders()
    {
        if ($this->orders === null) {
            $this->orders = array();

            foreach ($this->purchase_units as $unit) {

                $capture = reset($unit->payment_summary->captures);

                $this->orders[] = new Order(
                    (int) $unit->reference_id,
                    $capture->id,
                    $unit->status
                );
            }
        }

        return $this->orders;
    }

    /**
     * Provides the first of all suborders.
     *
     * @return null|\Tygh\Addons\PaypalForMarketplaces\Webhook\Order
     */
    public function getFirstOrder()
    {
        $this->getOrders();

        if ($this->orders) {
            $first_order = reset($this->orders);

            return $first_order;
        }

        return null;
    }
}