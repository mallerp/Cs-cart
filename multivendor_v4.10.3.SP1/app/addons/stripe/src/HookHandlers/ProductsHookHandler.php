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

class ProductsHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "after_options_calculation" hook handler.
     *
     * Actions performed:
     *  - Actualizes price of a product for instant payment button when chaning product options.
     *
     * @see \fn_get_data_of_changed_product()
     */
    public function onOptionsChange($mode, $data)
    {
        if (AREA !== 'C' || $mode !== 'options') {
            return;
        }

        $product = reset($data['product_data']);

        $product_id = key($data['product_data']);
        $product_options = empty($product['product_options'])
            ? []
            : $product['product_options'];
        $user_data = empty($this->application['session']['cart']['user_data'])
            ? []
            : $this->application['session']['cart']['user_data'];

        $notifications = $this->storeNotifications();
        if (!$this->checkProductAmount($product_id, 1, $product_options)) {
            $this->restoreNotifications($notifications);
            return;
        }

        /** @var array $payment_buttons */
        $payment_buttons = $this->application['addons.stripe.payment_button.buttons'];

        if ($payment_buttons) {
            /** @var \Tygh\Addons\Stripe\PaymentButton\DataLoader $loader */
            $loader = $this->application['addons.stripe.payment_button.data_loader'];
            $payment_buttons = $loader->loadPaymentRequestData($payment_buttons, $product_id, $product_options, $user_data, false);
        }

        /** @var \Tygh\SmartyEngine\Core $view */
        $view = $this->application['view'];

        $view->assign('stripe_payment_buttons', $payment_buttons);
    }

    /**
     * Checks whether a product is in stock.
     *
     * @param int   $product_id      Product ID
     * @param int   $amount          Requested product amount
     * @param array $product_options Selected product options
     *
     * @return bool|int False if product is out of stock, in stock amount otherwise
     */
    protected function checkProductAmount($product_id, $amount = 1, array $product_options = [])
    {
        $cart = [];

        /** @var \Tygh\Database\Connection $db */
        $db = $this->application['db'];
        $is_edp = $db->getField('SELECT is_edp FROM ?:products WHERE product_id = ?i', $product_id);

        return fn_check_amount_in_stock($product_id, $amount, $product_options, 0, $is_edp, 0, $cart);
    }

    /**
     * Extracts users notifications.
     *
     * @return array
     */
    protected function storeNotifications()
    {
        return $this->application['session']['notifications']
            ?: [];
    }

    /**
     * Replaces user notifications with the specified ones.
     *
     * @param array $notifications Notifications to restore
     *
     * @return array
     */
    protected function restoreNotifications(array $notifications)
    {
        return $this->application['session']['notifications'] = $notifications;
    }
}
