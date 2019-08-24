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

namespace Tygh\Payments\Addons\StripeConnect;

use Exception;
use Stripe\Account;
use Stripe\Charge;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Token as StripeToken;
use Tygh\Addons\StripeConnect\PayoutsManager;
use Tygh\Database\Connection;
use Tygh\Payments\Addons\StripeConnect\PaymentSources\Customer;
use Tygh\Payments\Addons\StripeConnect\PaymentSources\SourceInterface;
use Tygh\Payments\Addons\StripeConnect\PaymentSources\Token;
use Tygh\Tools\Formatter;

/**
 * Class StripeConnect implements Stipe Connect payment method.
 * Uses Direct charges: Stripe fees are collected from vendors (connected accounts).
 *
 * @package Tygh\Payments\Addons\StripeConnect
 */
class StripeConnect
{
    /** @var string $processor_script */
    protected static $processor_script = 'stripe_connect.php';

    /** @var string $payment_name */
    protected static $payment_name = 'stripe_connect';

    /** @var array $receivers_cache */
    protected static $receivers_cache = array();

    /** @var array $order_info */
    protected $order_info = array();

    /** @var array $processor_params */
    protected $processor_params = array();

    /** @var int $payment_id */
    protected $payment_id;

    /** @var Connection $db */
    protected $db;

    /** @var array $addon_settings */
    protected $addon_settings;

    /** @var  Formatter $formatter */
    private $formatter;

    /**
     * StripeConnect constructor.
     *
     * @param int        $payment_id     Payment method ID
     * @param array|null $processor_data Payment method configuration.
     *                                   When set to null, will be obtained from the database
     */
    public function __construct($payment_id, $processor_data = null)
    {
        $this->payment_id = $payment_id;

        if ($processor_data === null) {
            $this->processor_params = static::getProcessorParameters($payment_id);
        } else {
            $this->processor_params = $processor_data['processor_params'];
        }

        Stripe::setApiKey($this->processor_params['secret_key']);
        Stripe::setClientId($this->processor_params['client_id']);
    }

    /**
     * Obtains Stripe Connect based payment method processor parameters.
     *
     * @param int|null $payment_id If specified, processor parameters of the specified payment method will be returned.
     *                             Otherwise, first suitable method will be used.
     *
     * @return array
     */
    public static function getProcessorParameters($payment_id = null)
    {
        if ($payment_id === null) {
            if ($processor = fn_get_processor_data_by_name(static::getScriptName())) {
                if ($payment = fn_get_payment_by_processor($processor['processor_id'])) {
                    $payment = reset($payment);
                    $payment_id = $payment['payment_id'];
                }
            }
        }

        if ($processor_data = fn_get_processor_data($payment_id)) {
            return $processor_data['processor_params'];
        }

        return array(
            'client_id'       => null,
            'publishable_key' => null,
            'secret_key'      => null,
        );
    }

    /**
     * Gets payment processor script name.
     *
     * @return string
     */
    public static function getScriptName()
    {
        return static::$processor_script;
    }

    /**
     * Gets payment method name.
     *
     * @return string
     */
    public static function getPaymentName()
    {
        return static::$payment_name;
    }

    /**
     * Sets database connection to use for queries.
     *
     * @param Connection $db
     */
    public function setDb(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Performs payment.
     *
     * @param array $order_info Order to pay for.
     *
     * @return array Payment processor response
     */
    public function charge(array $order_info)
    {
        $pp_response = array(
            'order_status'         => STATUS_INCOMPLETED_ORDER,
            'reason_text'          => '',
            'stripe_connect.token' => '',
        );

        $this->order_info = $order_info;

        $orders_queue = $this->getOrdersToCharge($order_info);

        // Check that all receivers are valid accounts
        if (!$this->validateOrdersQueueReceivers($orders_queue)) {
            $pp_response['order_status'] = 'F';

            return $pp_response;
        }

        try {
            if (count($orders_queue) > 1) {
                // store customer to use single card token multiple times
                $payment_source = new Customer($order_info);
            } else {
                // use tokenized card
                $payment_source = new Token($order_info);
            }

            foreach ($orders_queue as $order_id => $company_id) {
                $order_info = fn_get_order_info($order_id);

                $payouts_manager = new PayoutsManager($company_id);

                $charge = $this->chargeOrder($order_info, $payment_source, $payouts_manager);

                if ($company_id) {

                    $withdrawal_ammount = $charge->metadata['total'] - $charge->metadata['commission'];

                    if ($withdrawal_ammount) {

                        $payouts_manager->createWithdrawal($withdrawal_ammount, $order_id);

                        if ($this->addon_settings['collect_payouts']) {
                            $payouts_manager->acceptPayouts();
                        }
                    }
                }

                fn_update_order_payment_info($order_id, array(
                    'transaction_id' => $charge->id,
                ));
            }

            $payment_source->destroy();

            $pp_response['order_status'] = 'P';
        } catch (Exception $e) {
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = $e->getMessage();
        }

        return $pp_response;
    }

    /**
     * Gets orders that should be paid.
     *
     * @param array $order Parent order info
     *
     * @return array Keys are order IDs, values are vendors IDs
     */
    protected function getOrdersToCharge(array $order)
    {
        if ($order['status'] == STATUS_PARENT_ORDER) {
            $queue = $this->db->getSingleHash(
                'SELECT order_id, company_id FROM ?:orders WHERE parent_order_id = ?i',
                array('order_id', 'company_id'),
                $order['order_id']
            );
        } else {
            $queue = array(
                $order['order_id'] => $order['company_id'],
            );
        }

        return $queue;
    }

    /**
     * Charges suborder in parent order.
     *
     * @param array           $order           Order to charge info
     * @param SourceInterface $payment_source  Payment source
     * @param PayoutsManager  $payouts_manager Configured payouts manager
     *
     * @return Charge Charge object
     */
    protected function chargeOrder(array $order, SourceInterface $payment_source, PayoutsManager $payouts_manager)
    {
        $amount = $this->formatAmount($order['total']);

        $params = array(
            'amount'                                         => $amount,
            'currency'                                       => $this->processor_params['currency'],
            $payment_source->getChargeRequestParameterName() => $payment_source->getChargeRequestParameterValue(),
            'metadata'                                       => array(
                'order_id' => $order['order_id'],
                'total'    => $order['total'],
            ),
        );

        $options = null;

        if ($order['company_id']) {
            $receiver = static::getChargeReceiver($order['company_id']);

            list($commission, $fee) = $this->getApplicationFee($order, $payouts_manager);

            $params['application_fee'] = $fee;
            $params['metadata']['commission'] = $commission;

            // payment receiver must be specified in options to perform Direct charge
            $options = array('stripe_account' => $receiver);

            // customer account must be shared to a connected account by converting it into a payment token
            if (isset($params['customer'])) {
                $params['source'] = $this->shareCustomer($params['customer'], $receiver);
                unset($params['customer']);
            }
        }

        $charge = Charge::create($params, $options);

        return $charge;
    }

    /**
     * Obtains Stripe account ID to transfer funds to.
     *
     * @param int $company_id Vendor company ID.
     *
     * @return string Stripe account ID
     */
    public static function getChargeReceiver($company_id)
    {
        if (!isset(static::$receivers_cache[$company_id])) {
            if ($company_id) {
                $company_data = fn_get_company_data($company_id);
                static::$receivers_cache[$company_id] = $company_data['stripe_connect_account_id'];
            } else {
                static::$receivers_cache[$company_id] = static::getOwnerAccountId();
            }
        }

        return static::$receivers_cache[$company_id];
    }

    /**
     * Formats payment amount by currency.
     *
     * @param float $amount Payment amount
     *
     * @return int Order amount <b>in cents</b>
     */
    protected function formatAmount($amount)
    {
        $amount = $this->formatter->asPrice($amount, $this->processor_params['currency'], false, false);

        $amount_in_cents = $this->convertToCents($amount);

        return $amount_in_cents;
    }

    /**
     * Converts amount to smallest currency unit.
     *
     * @param float $amount Monetary amount.
     *
     * @return int Amount in cents
     */
    protected function convertToCents($amount)
    {
        $amount = preg_replace('/\D/', '', $amount);

        $amount_in_cents = (int) ltrim($amount, '0');

        return $amount_in_cents;
    }

    /**
     * Calculated application fee that will be excluded from the charge transaction.
     *
     * @param array          $order_info      Order to charge
     * @param PayoutsManager $payouts_manager Configured payouts manager
     *
     * @return array Application fee in primary currency and in configured payment method's currency (cents)
     */
    protected function getApplicationFee(array $order_info, PayoutsManager $payouts_manager)
    {
        $commission = $payouts_manager->getOrderFee($order_info['order_id']);

        // hold back vendor payouts
        if ($this->addon_settings['collect_payouts']) {
            $commission += $payouts_manager->getPendingPayoutsFee();
        }

        $commission = min($commission, $order_info['total']);

        $fee_in_cents = $this->formatAmount($commission);

        return array($commission, $fee_in_cents);
    }

    /**
     * Sets Stripe Connect add-on settings for payment method.
     *
     * @param array $settings Add-on settings
     */
    public function setAddonsSettings(array $settings)
    {
        $default_settings = array(
            'collect_payouts' => 'Y',
        );

        $settings = array_merge($default_settings, $settings);

        $settings['collect_payouts'] = $settings['collect_payouts'] == 'Y';

        $this->addon_settings = $settings;
    }

    /**
     * Sets formatter to use when converting prices.
     *
     * @param Formatter $formatter
     */
    public function setFormatter(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Refunds charge.
     *
     * @param array $order_info Refunded order info
     * @param float $amount     Refunded amount
     *
     * @return Refund
     */
    public function refund(array $order_info, $amount)
    {
        $params = [
            'charge' => $order_info['payment_info']['transaction_id'],
            'amount' => $this->formatAmount($amount),
            'reason' => 'requested_by_customer',
        ];

        $options = null;
        if ($order_info['company_id']) {
            $receiver = static::getChargeReceiver($order_info['company_id']);
            $options = ['stripe_account' => $receiver];
        }

        $refund = Refund::create($params, $options);

        return $refund;
    }

    /**
     * Checks that all companies in an order have valid Stripe account.
     *
     * @param array $orders_queue Orders queue
     *
     * @return bool
     */
    protected function validateOrdersQueueReceivers(array $orders_queue)
    {
        try {
            foreach ($orders_queue as $company_id) {
                $account_id = StripeConnect::getChargeReceiver($company_id);
                Account::retrieve($account_id);
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Gets store's owner account ID.
     *
     * @return null|string Account ID or null if an error occured
     */
    public static function getOwnerAccountId()
    {
        $params = static::getProcessorParameters();

        Stripe::setApiKey($params['secret_key']);
        Stripe::setClientId($params['client_id']);

        $owner_id = null;
        try {
            $owner = Account::retrieve();
            $owner_id = $owner->id;
        } catch (Exception $e) {
        }

        return $owner_id;
    }

    /**
     * Shares customer to a connected vendor account.
     *
     * @param string $customer_id          Stripe customer identifier
     * @param string $connected_account_id Connected account identifier
     *
     * @return string Payment token
     */
    protected function shareCustomer($customer_id, $connected_account_id)
    {
        $token = StripeToken::create(array(
            'customer' => $customer_id,
        ), array('stripe_account' => $connected_account_id));

        return $token->id;
    }
}
