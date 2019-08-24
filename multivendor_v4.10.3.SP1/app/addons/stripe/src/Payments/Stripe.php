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

namespace Tygh\Addons\Stripe\Payments;

use Exception;
use Stripe\Charge;
use Stripe\Refund;
use Stripe\Stripe as StripeSetup;
use Tygh\Addons\Stripe\PaymentSources\SourceInterface;
use Tygh\Addons\Stripe\PaymentSources\Token;
use Tygh\Addons\Stripe\PriceFormatter;
use Tygh\Database\Connection;

/**
 * Class Stripe implements Stipe Connect payment method.
 * Uses Direct charges: Stripe fees are collected from vendors (connected accounts).
 *
 * @package Tygh\Payments\Addons\Stripe
 */
class Stripe
{
    /** @var string $processor_script */
    protected static $processor_script = 'stripe.php';

    /** @var string $addon_name */
    protected static $addon_name = 'stripe';

    /** @var array $processor_params */
    protected $processor_params = [];

    /** @var int $payment_id */
    protected $payment_id;

    /** @var Connection $db */
    protected $db;

    /** @var  PriceFormatter $formatter */
    private $price_formatter;

    /**
     * Stripe constructor.
     *
     * @param int                                $payment_id       Payment method ID
     * @param \Tygh\Database\Connection          $db               Database connection
     * @param \Tygh\Addons\Stripe\PriceFormatter $price_formatter  Formatter
     * @param array|null                         $processor_params Payment processor configuration.
     *                                                             When set to null, will be obtained from the database
     */
    public function __construct($payment_id, Connection $db, PriceFormatter $price_formatter, $processor_params = null)
    {
        $this->payment_id = $payment_id;
        $this->db = $db;
        $this->price_formatter = $price_formatter;

        if ($processor_params === null) {
            $this->processor_params = static::getProcessorParameters($payment_id);
        } else {
            $this->processor_params = $processor_params;
        }

        StripeSetup::setApiKey($this->processor_params['secret_key']);
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

        return [
            'publishable_key' => null,
            'secret_key'      => null,
            'currency'        => null,
        ];
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
     * Gets add-on name.
     *
     * @return string
     */
    public static function getAddonName()
    {
        return static::$addon_name;
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
        $pp_response = [
            'order_status' => STATUS_INCOMPLETED_ORDER,
            'reason_text'  => '',
            'stripe.token' => '',
        ];

        $order_id = $order_info['order_id'];

        try {
            $payment_source = new Token($order_info);

            $charge = $this->chargeOrder($order_info, $payment_source);

            fn_update_order_payment_info($order_id, [
                'transaction_id' => $charge->id,
            ]);

            $payment_source->destroy();

            $pp_response['order_status'] = 'P';
        } catch (Exception $e) {
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = $e->getMessage();
        }

        return $pp_response;
    }

    /**
     * Charges suborder in parent order.
     *
     * @param array           $order          Order to charge info
     * @param SourceInterface $payment_source Payment source
     *
     * @return Charge Charge object
     */
    protected function chargeOrder(array $order, SourceInterface $payment_source)
    {
        $amount = $this->formatAmount($order['total']);

        $params = [
            'amount'                                         => $amount,
            'currency'                                       => $this->processor_params['currency'],
            $payment_source->getChargeRequestParameterName() => $payment_source->getChargeRequestParameterValue(),
            'metadata'                                       => [
                'order_id' => $order['order_id'],
                'total'    => $order['total'],
            ],
        ];

        $charge = Charge::create($params);

        return $charge;
    }

    /**
     * Formats payment amount by currency.
     *
     * @param float $amount Payment amount
     *
     * @return int Order amount <b>in cents</b>
     */
    public function formatAmount($amount)
    {
        return $this->price_formatter->asCents($amount, $this->processor_params['currency']);
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

        $refund = Refund::create($params);

        return $refund;
    }
}
