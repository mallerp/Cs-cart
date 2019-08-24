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

class Order
{
    /** @var int $id */
    protected $id;

    /** @var string $capture_id */
    protected $capture_id;

    /** @var string $status */
    protected $status;

    /** @var array $info */
    protected $info;

    /**
     * Order constructor.
     *
     * @param int    $id         Order ID in store
     * @param string $capture_id Capture ID in PayPal
     * @param string $status     Status in PayPal
     */
    public function __construct($id, $capture_id, $status)
    {
        $this->id = $id;
        $this->capture_id = $capture_id;
        $this->status = $status;
    }

    /**
     * Provides order ID in store.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Provides capture ID in PayPal.
     *
     * @return string
     */
    public function getCaptureId()
    {
        return $this->capture_id;
    }

    /**
     * Provides order status in PayPal.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Provides order info from the database.
     *
     * @see \fn_get_order_info()
     *
     * @return array
     */
    public function getInfo()
    {
        if ($this->info === null) {
            $this->info = fn_get_order_info($this->id);
        }

        return $this->info;
    }

    /**
     * Provides order vendor ID.
     *
     * @return int
     */
    public function getCompanyId()
    {
        $info = $this->getInfo();

        return $info['company_id'];
    }

    /**
     * Provides order withdrawal ammount.
     *
     * @return float
     */
    public function getWithdrawalAmount()
    {
        $info = $this->getInfo();

        return empty($info['payment_info']['paypal_for_marketplaces.withdrawal'])
            ? 0
            : $info['payment_info']['paypal_for_marketplaces.withdrawal'];
    }
}