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

namespace Tygh\Payments\Addons\PaypalForMarketplaces;

use Exception;
use Tygh\Addons\PaypalForMarketplaces\Api\RequestorWrapper;
use Tygh\Addons\PaypalForMarketplaces\OAuthHelper;
use Tygh\Addons\PaypalForMarketplaces\PayoutsManager;
use Tygh\Common\OperationResult;
use Tygh\Database\Connection;
use Tygh\Enum\Addons\PaypalForMarketplaces\ProcessingStateStatuses;
use Tygh\Http;
use Tygh\Tools\Formatter;
use Tygh\Tools\Url;

class PaypalForMarketplaces
{
    /** @var string $processor_script */
    protected static $processor_script = 'paypal_for_marketplaces.php';

    /** @var string $payment_name */
    protected static $payment_name = 'paypal_for_marketplaces';

    /** @var array $receivers_cache */
    protected static $receivers_cache = array();

    /** @var array $order_info */
    protected $order_info = array();

    /** @var array $processor_params */
    protected $processor_params = array();

    /** @var int $payment_id */
    protected $payment_id;

    /** @var \Tygh\Database\Connection $db */
    protected $db;

    /** @var array $addon_settings */
    protected $addon_settings;

    /** @var \Tygh\Tools\Formatter $formatter */
    protected $formatter;

    /** @var \Tygh\Addons\PaypalForMarketplaces\Api\RequestorWrapper $api_requestor */
    protected $api;

    /** @var float $minimal_fee */
    protected $minimal_fee = 0.01;

    /** @var bool $commission_value */
    protected $commission_value;

    /** @var string $payment_approval_url */
    protected $payment_approval_url;

    /** @var bool $redirect_on_charge */
    protected $redirect_on_charge = true;

    /** @var \Tygh\Addons\PaypalForMarketplaces\OAuthHelper $oauth_helper */
    protected $oauth_helper;

    /** @var string $payment_approval_id */
    protected $payment_approval_id;

    /**
     * PaypalForMarketplaces constructor.
     *
     * @param int        $payment_id     Payment method ID
     * @param array|null $processor_data Payment method configuration.
     *                                   When set to null, will be obtained from the database
     */
    public function __construct($payment_id, $processor_data = null)
    {
        $this->payment_id = $payment_id;

        if ($processor_data === null) {
            $this->processor_params = $this->getProcessorParameters($payment_id);
        } else {
            $this->processor_params = $processor_data['processor_params'];
        }
    }

    /**
     * Obtains Paypal for Marketplaces based payment method processor parameters.
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
            'bn_code'      => null,
            'payer_id'     => null,
            'client_id'    => null,
            'secret'       => null,
            'access_token' => null,
            'expiry_time'  => 0,
            'mode'         => 'test',
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
            'order_status' => STATUS_INCOMPLETED_ORDER,
            'reason_text'  => '',
        );

        $this->order_info = $order_info;

        $orders_queue = $this->getOrdersToCharge($order_info);

        // Check that all receivers are valid accounts
        if (!$this->validateOrdersQueueReceivers($orders_queue)) {
            $pp_response['order_status'] = 'F';

            return $pp_response;
        }

        try {

            $order_specification = array(
                'purchase_units' => array(),
                'redirect_urls'  => array(
                    'return_url' => $this->getNotifyUrl('return', array('order_id' => $order_info['order_id'])),
                    'cancel_url' => $this->getNotifyUrl('cancel', array('order_id' => $order_info['order_id'])),
                ),
            );

            $tracking_order = array(
                'total' => 0,
            );
            foreach ($orders_queue as $order_id => $company_id) {
                $order_info = fn_get_order_info($order_id);

                if ($order_info['total'] >= $tracking_order['total']) {
                    $tracking_order = $order_info;
                }

                $payouts_manager = new PayoutsManager($company_id);

                $unit = $this->buildPurchaseUnit($order_info, $payouts_manager);
                $order_specification['purchase_units'][] = $unit;

                // store financial stats for further order processing
                fn_update_order_payment_info($order_id, array(
                    'paypal_for_marketplaces.withdrawal' => $unit['metadata']['postback_data'][0]['value'],
                ));
            }

            $tracking_id = $this->generateTrackingId($tracking_order);

            $response = $this->api->request(
                '/v1/checkout/orders',
                $order_specification,
                array(
                    'headers' => array(
                        'Paypal-Client-Metadata-Id: ' . $tracking_id,
                    ),
                )
            );

            $pp_response['paypal_for_marketplaces.order_id'] = $response['id'];
            fn_update_order_payment_info($this->order_info['order_id'], $pp_response);

            $this->setPaymentApprovalId($response['id']);

            foreach ($response['links'] as $link) {
                if ($link['rel'] == 'approval_url') {
                    $this->setPaymentApprovalUrl($link['href']);
                    if ($this->redirect_on_charge) {
                        fn_redirect($link['href'], true);
                    }
                    break;
                }
            }
        } catch (Exception $e) {
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = $e->getMessage();
        }

        return $pp_response;
    }

    /**
     * Refunds charge.
     *
     * @param array $order_info Refunded order info
     * @param float $amount     Refunded amount
     *
     * @return OperationResult
     */
    public function refund(array $order_info, $amount)
    {
        $result = new OperationResult();

        $capture_id = $order_info['payment_info']['paypal_for_marketplaces.capture_id'];

        $data = array(
            'amount' => $this->formatAmount($amount, CART_PRIMARY_CURRENCY),
        );

        $payer_id = static::getChargeReceiver($order_info['company_id']);

        $headers = array(
            'PayPal-Auth-Assertion: ' . $this->api->getAuthAssertion($payer_id),
        );

        try {
            $response = $this->api->request(
                '/v1/payments/capture/' . $capture_id . '/refund',
                $data,
                array(
                    'headers' => $headers,
                )
            );
            $result->setSuccess(true);
            $result->setData($response['id']);
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
        }

        return $result;
    }

    /**
     * Checks whether vendor is able to receive payments.
     *
     * @param string $account_id Merchant's account ID
     *
     * @return bool
     */
    protected function isMerchantValid($account_id)
    {
        $merchant = $this->getMerchant($account_id);

        if ($merchant->isSuccess()) {
            $merchant = $merchant->getData();

            return !empty($merchant['payments_receivable'])
                && !empty($merchant['primary_email_confirmed']);
        }

        return false;
    }

    /**
     * Builds the `Paypal-Client-Metadata-Id` header value for the order creation API request.
     *
     * @param array $order Order info
     *
     * @return string|null
     */
    protected function generateTrackingId(array $order)
    {
        $account_id = static::getChargeReceiver($order['company_id']);

        $tracking_id = $order['order_id'] . '_' . rand(1000, 9999);

        $url = sprintf(
            '/v1/risk/transaction-contexts/%s/%s',
            $account_id,
            $tracking_id
        );

        $additional_data = $this->populateAdditionalDataForTracking($order);

        $data = array(
            'tracking_id'     => $tracking_id,
            'additional_data' => $additional_data,
        );

        $result = null;
        try {
            $this->api->request($url, $data, array(), Http::PUT);
            $result = $tracking_id;
        } catch (Exception $e) {
        }

        return $result;
    }

    /**
     * Performs actual payment.
     *
     * @param string $token Order token in PayPal
     *
     * @return \Tygh\Common\OperationResult
     */
    public function pay($token)
    {
        $result = new OperationResult();

        try {
            $this->api->request(
                '/v1/checkout/orders/' . $token . '/pay',
                array(
                    'disbursement_mode' => 'DELAYED',
                )
            );
            $result->setSuccess(true);
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
        }

        return $result;
    }

    /**
     * Fetches order data from PayPal.
     *
     * @param string $order_id Order ID in PayPal.
     *
     * @return array|null Order data or null on error
     */
    public function getOrder($order_id)
    {
        $order = null;

        try {
            $response = $this->api->request(
                '/v1/checkout/orders/' . $order_id,
                '',
                array(),
                Http::GET
            );
            $order = $response;
        } catch (Exception $e) {
        }

        return $order;
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
     * Obtains PayPal account ID to transfer funds to.
     *
     * @param int $company_id Vendor company ID.
     *
     * @return string PayPal account ID
     */
    public static function getChargeReceiver($company_id)
    {
        if (!isset(static::$receivers_cache[$company_id])) {
            if ($company_id) {
                $company_data = fn_get_company_data($company_id);
                static::$receivers_cache[$company_id] = $company_data['paypal_for_marketplaces_account_id'];
            } else {
                static::$receivers_cache[$company_id] = static::getOwnerAccountId();
            }
        }

        return static::$receivers_cache[$company_id];
    }

    /**
     * Formats payment amount by currency.
     *
     * @param float  $amount        Payment amount
     * @param string $currency_from Secondary currency code
     * @param string $value_key     Name of the field to hold monetary value
     *
     * @return array Monetary amount definition
     */
    protected function formatAmount($amount, $currency_from, $value_key = 'total')
    {
        if ($currency_from != $this->processor_params['currency']) {
            $amount = fn_format_price_by_currency($amount, $currency_from, $this->processor_params['currency']);
        }

        $amount_formatted = fn_format_rate_value($amount, 'F', 2, '.', '');

        return array(
            'currency' => $this->processor_params['currency'],
            $value_key => $amount_formatted,
        );
    }

    /**
     * Calculated application fee that will be excluded from the charge transaction.
     *
     * @param array          $order           Order to charge
     * @param PayoutsManager $payouts_manager Configured payouts manager
     *
     * @return float Application fee
     */
    protected function getApplicationFee(array $order, PayoutsManager $payouts_manager)
    {
        $commission = $payouts_manager->getOrderFee($order['order_id']);

        // hold back vendor payouts
        if ($this->addon_settings['collect_payouts']) {
            $commission += $payouts_manager->getPendingPayoutsFee();
        }

        $commission = min($commission, $order['total']);

        return $commission;
    }

    /**
     * Sets Paypal for Marketplaces add-on settings for payment method.
     *
     * @param array $settings Add-on settings
     */
    public function setAddonSettings(array $settings)
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
     * Checks that all companies in an order have valid PayPal account.
     *
     * @param array $orders_queue Orders queue
     *
     * @return bool
     */
    protected function validateOrdersQueueReceivers(array $orders_queue)
    {
        foreach ($orders_queue as $company_id) {
            $account_id = PaypalForMarketplaces::getChargeReceiver($company_id);
            if (!$this->isMerchantValid($account_id)) {
                return false;
            }
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

        return $params['payer_id'];
    }

    /**
     * Builds the `purchase_unit` field for the order creation API request.
     *
     * @param array                                             $order           Order info to get purchase unit for
     * @param \Tygh\Addons\PaypalForMarketplaces\PayoutsManager $payouts_manager Configured payouts manager
     *
     * @return array
     */
    protected function buildPurchaseUnit(array $order, PayoutsManager $payouts_manager)
    {
        $reference_id = $order['order_id'];

        $amount = $this->formatAmount($order['total'], CART_PRIMARY_CURRENCY);

        // the receiver of the payment
        $payee = array(
            'merchant_id' => static::getChargeReceiver($order['company_id']),
        );

        $description = $this->getCompanyDescription($order['company_id']);

        $items = $this->buildItems($order);

        $shipping_address = $this->buildShippingAddress($order);

        // commission details
        $commission = $this->getApplicationFee($order, $payouts_manager);
        $partner_fee_details = $this->buildPartnerFeeDetails($commission, CART_PRIMARY_CURRENCY);

        // metadata
        $metadata = array(
            'postback_data' => array(
                array(
                    'name'  => 'withdrawal',
                    'value' => $order['total'] - $commission,
                ),
            ),
        );

        return array(
            'reference_id'        => $reference_id,
            'amount'              => $amount,
            'payee'               => $payee,
            'description'         => $description,
            'items'               => $items,
            'shipping_address'    => $shipping_address,
            'partner_fee_details' => $partner_fee_details,
            'metadata'            => $metadata,
        );
    }

    /**
     * Builds the `purchase_unit.partner_fee_details` field for the order creation API request.
     *
     * @param float  $commission    Fee amount
     * @param string $currency_from Secondary currency code
     *
     * @return array
     */
    protected function buildPartnerFeeDetails($commission, $currency_from)
    {
        // fee MUST exceed zero
        $commission = max($commission, $this->minimal_fee);

        $amount = $this->formatAmount($commission, $currency_from, 'value');

        $receiver = array(
            'merchant_id' => $this->processor_params['payer_id'],
        );

        return array(
            'receiver' => $receiver,
            'amount'   => $amount,
        );
    }

    /**
     * Provides URL to payment notification for an order.
     *
     * @param string $method Notification type
     * @param array  $params Query parameters
     *
     * @return string
     */
    protected function getNotifyUrl($method, array $params = array())
    {
        $params = array_merge(array(
            'payment' => static::getPaymentName(),
        ), $params);

        $urn = Url::buildUrn(array('payment_notification', $method), $params);

        $url = fn_url($urn);

        return $url;
    }

    /**
     * @param RequestorWrapper|null $requestor_wrapper
     */
    public function setRequestorWrapper($requestor_wrapper = null)
    {
        $this->api = $requestor_wrapper;
    }

    /**
     * Reads add-on schema.
     *
     * @param $schema
     *
     * @return array
     */
    protected function getSchema($schema)
    {
        $schema = fn_get_schema('paypal_for_marketplaces', $schema);

        return $schema;
    }

    /**
     * Builds data for tracking ID request.
     *
     * @param array      $order  Order info
     * @param array|null $schema Additional data specification
     *
     * @return array
     */
    protected function populateAdditionalDataForTracking(array $order, array $schema = null)
    {
        if ($schema === null) {
            $schema = $this->getSchema('additional_data');
        }

        $additional_data = array();

        foreach ($schema as $additional_data_field => $order_field) {
            $order_field = (array) $order_field;
            $value = $order;
            foreach ($order_field as $order_field_path) {
                $value = $value[$order_field_path];
            }
            $additional_data[] = array(
                'key'   => $additional_data_field,
                'value' => $value,
            );
        }

        // filter-out empty values
        $additional_data = array_filter($additional_data, function ($item) {
            return !empty($item['value']);
        });

        return array_values($additional_data);
    }

    /**
     * @return array
     */
    public function getProcessorParams()
    {
        return $this->processor_params;
    }

    /**
     * @return int
     */
    public function getPaymentId()
    {
        return $this->payment_id;
    }

    /**
     * Converts capture status into order status.
     * TODO: Add status conversion as in PayPal
     *
     * @param string     $status Capture status
     * @param array|null $schema Status conversion map
     *
     * @return mixed
     */
    public function getStatusByCapture($status, array $schema = null)
    {
        if ($schema === null) {
            $schema = $this->getSchema('status_conversion');
        }

        return $schema[$status];
    }

    /**
     * Disburses funds to vendors.
     *
     * @param \Tygh\Addons\PaypalForMarketplaces\Webhook\Order $order      Order info
     * @param string                                           $capture_id Capture ID to disburse
     *
     * @return \Tygh\Common\OperationResult
     */
    public function disbursePayout($order, $capture_id)
    {
        $result = new OperationResult();

        try {
            $response = $this->api->request(
                '/v1/payments/referenced-payouts-items',
                array(
                    'reference_id'   => $capture_id,
                    'reference_type' => 'TRANSACTION_ID',
                ),
                array(
                    'headers' => array('Prefer: respond-sync'),
                )
            );
            if ($response['processing_state']['status'] == ProcessingStateStatuses::SUCCESS) {
                $result->setSuccess(true);
                $result->setData($response['item_id']);

                $payouts_manager = new PayoutsManager($order->getCompanyId());

                $payouts_manager->createWithdrawal($order->getWithdrawalAmount(), $order->getId());

                if ($this->addon_settings['collect_payouts']) {
                    $payouts_manager->acceptPayouts();
                }
            } else {
                $result->addError(0, $response['processing_state']['reason']);
            }
        } catch (Exception $e) {
            $result->addError($e->getCode(), $e->getMessage());
        }

        return $result;
    }

    /**
     * Builds purchase items for an order.
     * TODO: Add real items population.
     *
     * @param array $order Order info
     *
     * @return array
     */
    protected function buildItems(array $order)
    {
        $price = $this->formatAmount($order['total'], CART_PRIMARY_CURRENCY);

        $item = array(
            'name'     => $this->getCompanyDescription($order['company_id']),
            'quantity' => 1,
            'price'    => $price['total'],
            'currency' => $price['currency'],
        );

        return array($item);
    }

    /**
     * Provides company name.
     *
     * @param int $company_id Company ID
     *
     * @return string Company name
     */
    protected function getCompanyDescription($company_id)
    {
        return fn_get_company_name($company_id, 'order');
    }

    /**
     * Sets whether a customer should be immediately redirected to the payment gateway when creating a charge.
     *
     * @param bool $redirect_on_charge
     */
    public function setRedirectOnCharge($redirect_on_charge)
    {
        $this->redirect_on_charge = $redirect_on_charge;
    }

    /**
     * Sets URL to redirect a customer to after a charge is successfully created.
     *
     * @param string $url URL
     */
    protected function setPaymentApprovalUrl($url)
    {
        $this->payment_approval_url = $url;
    }

    /**
     * Gets URL to redirect customer to.
     *
     * @return string URL
     */
    public function getPaymentApprovalUrl()
    {
        return $this->payment_approval_url;
    }

    /**
     * Builds purchase unit shipping address for an order.
     *
     * @param array $order Order info
     *
     * @return array
     */
    protected function buildShippingAddress(array $order)
    {
        $address = array(
            'line1'        => $order['s_address'],
            'country_code' => $order['s_country'],
        );

        if (!empty($order['s_address_2'])) {
            $address['line2'] = $order['s_address_2'];
        }
        if (!empty($order['s_city'])) {
            $address['city'] = $order['s_city'];
        }
        if (!empty($order['s_zipcode'])) {
            $address['postal_code'] = $order['s_zipcode'];
        }
        if (!empty($order['s_state'])) {
            $address['state'] = $order['s_state'];
        }

        return $address;
    }

    /**
     * Sets OAuth helper.
     *
     * @param \Tygh\Addons\PaypalForMarketplaces\OAuthHelper $oauth_helper
     */
    public function setOauthHelper(OAuthHelper $oauth_helper)
    {
        $this->oauth_helper = $oauth_helper;
    }

    /**
     * Obtains merchant info from PayPal.
     *
     * @param string $account_id Merchant ID in PayPal
     *
     * @return OperationResult Contains merchant info as data on success
     */
    public function getMerchant($account_id)
    {
        return $this->oauth_helper->getAccountInfo($account_id);
    }

    /**
     * Sets payment ID to use with In-Context Checkout.
     *
     * @param string $id
     */
    protected function setPaymentApprovalId($id)
    {
        $this->payment_approval_id = $id;
    }

    /**
     * Gets payment ID to use with In-Context Checkout.
     *
     * @return string
     */
    public function getPaymentApprovalId()
    {
        return $this->payment_approval_id;
    }
}