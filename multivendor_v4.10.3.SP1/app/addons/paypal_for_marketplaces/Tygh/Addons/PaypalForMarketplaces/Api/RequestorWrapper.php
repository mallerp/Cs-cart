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

namespace Tygh\Addons\PaypalForMarketplaces\Api;

use Tygh\Database\Connection;
use Tygh\Http;

class RequestorWrapper
{
    /** @var int $payment_id */
    protected $payment_id;

    /** @var array $processor_params */
    protected $processor_params;

    /** @var \Tygh\Database\Connection $db */
    protected $db;

    /** @var \Tygh\Addons\PaypalForMarketplaces\Api\Requestor $api_requestor */
    protected $api_requestor;

    /**
     * RequestorWrapper constructor.
     *
     * @param                           $payment_id
     * @param                           $processor_params
     * @param \Tygh\Database\Connection $db
     */
    public function __construct($payment_id, $processor_params, Connection $db)
    {
        $this->payment_id = $payment_id;
        $this->processor_params = $processor_params;
        $this->db = $db;

        $this->initRequestor();
    }

    /**
     * Performs API request and updates oauth token.
     *
     * @see \Tygh\Addons\PaypalForMarketplaces\Api\Requestor::signedRequest
     *
     * @param string $url    API method URL
     * @param array  $data   API request data
     * @param array  $extra  Extra settings for curl
     * @param string $method HTTP method to perform request
     *
     * @return mixed
     * @throws \Tygh\Addons\PaypalForMarketplaces\Exceptions\ApiException
     * @throws \Tygh\Addons\PaypalForMarketplaces\Exceptions\ContentException
     */
    public function request($url = '', $data = array(), $extra = array(), $method = Http::POST)
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }

        list($response, $new_token) = $this->api_requestor->signedRequest($url, $data, $extra, $method);

        if ($new_token) {
            $this->updateProcessorParameters($new_token);
            $this->initRequestor();
        }

        return $response;
    }

    /**
     * Initializes API requestor.
     */
    protected function initRequestor()
    {
        $this->api_requestor = new Requestor(
            $this->processor_params['bn_code'],
            $this->processor_params['client_id'],
            $this->processor_params['secret'],
            $this->processor_params['access_token'],
            $this->processor_params['expiry_time'],
            $this->processor_params['mode'] == 'test'
        );
    }

    /**
     * Updates oauth token and expiry time for the payment method.
     *
     * @param array $parameters New parameters
     */
    protected function updateProcessorParameters(array $parameters)
    {
        foreach ($parameters as $parameter => $value) {
            $this->processor_params[$parameter] = $value;
        }

        $this->db->query('UPDATE ?:payments SET ?u WHERE ?w',
            array(
                'processor_params' => serialize($this->processor_params),
            ),
            array(
                'payment_id' => $this->payment_id,
            )
        );
    }

    /**
     * Calculates first-party delegation proof.
     *
     * @param string $payer_id Payer ID to calculate assertion for
     *
     * @return string
     */
    public function getAuthAssertion($payer_id)
    {
        $assertion = '';

        $assertion .= base64_encode(json_encode(array(
            'alg' => 'none',
        )));

        $assertion .= '.';

        $assertion .= base64_encode(json_encode(array(
            'iss'      => $this->processor_params['client_id'],
            'payer_id' => $payer_id,
        )));

        $assertion .= '.';

        return $assertion;
    }
}