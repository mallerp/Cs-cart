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

use Tygh\Addons\PaypalForMarketplaces\Exceptions\ApiException;
use Tygh\Addons\PaypalForMarketplaces\Exceptions\ContentException;
use Tygh\Http;

class Requestor
{
    /**
     * @var string $client_id REST API application client ID
     */
    protected $client_id;

    /**
     * @var string $secret REST API application secret
     */
    protected $secret;

    /**
     * @var string $access_token OAuth access token
     */
    protected $access_token;

    /**
     * @var int $expiry_time OAuth token expiry time (unixtime)
     */
    protected $expiry_time;

    /**
     * @var bool $is_test If true, sandbox request will be performed
     */
    protected $is_test;

    /**
     * @var string $bn_code Partner integration code
     */
    protected $bn_code;

    /**
     * Sandbox URL.
     */
    const URL_TEST = 'https://api.sandbox.paypal.com';
    /**
     * Production URL.
     */
    const URL_LIVE = 'https://api.paypal.com';

    /**
     * Api constructor.
     *
     * @param string $bn_code      Partner integration code
     * @param string $client_id    REST API application client ID
     * @param string $secret       REST API application secret
     * @param string $access_token OAuth access token
     * @param int    $expiry_time  OAuth token expiry time
     * @param bool   $is_test      If true, sandbox request will be performed
     */
    public function __construct($bn_code, $client_id, $secret, $access_token = '', $expiry_time = 0, $is_test = false)
    {
        $this->bn_code = $bn_code;
        $this->client_id = $client_id;
        $this->secret = $secret;
        $this->access_token = $access_token;
        $this->expiry_time = $expiry_time;

        $this->setTestMode($is_test);
    }

    /**
     * Checks if OAuth token is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expiry_time <= time();
    }

    /**
     * Sets test mode.
     *
     * @param bool $is_test
     */
    public function setTestMode($is_test = true)
    {
        $this->is_test = $is_test === true || $is_test === 'Y';
    }

    /**
     * Obtains OAuth token.
     *
     *
     * @throws ApiException     If an API error occurred
     * @throws ContentException If a response is not a valid JSON
     */
    public function obtainToken()
    {
        $data = array(
            'grant_type' => 'client_credentials',
        );

        $extra = array(
            'basic_auth' => array($this->client_id, $this->secret),
        );

        // disable logging
        $logging = Http::$logging;
        Http::$logging = false;

        $response = $this->request('/v1/oauth2/token', $data, $extra, Http::POST);

        // restore logging state
        Http::$logging = $logging;

        $this->access_token = $response['access_token'];
        $this->expiry_time = time() + $response['expires_in'];
    }

    /**
     * Decodes JSON encoded API response, checks if any errors are reported.
     *
     * @param string $response API response
     * @param int    $status   HTTP status
     * @param string $headers  Response headers
     *
     * @return array Decoded response
     *
     * @throws \Tygh\Addons\PaypalForMarketplaces\Exceptions\ApiException If an API error occurred
     * @throws \Tygh\Addons\PaypalForMarketplaces\Exceptions\ContentException If a response is not a valid JSON
     */
    public function decodeResponse($response = '', $status = 200, $headers = '')
    {
        $decoded_response = json_decode($response, true);

        if ($decoded_response === null) {
            throw new ContentException($response);
        }

        if ($this->isErrorStatus($status)) {

            if (isset($decoded_response['message'])) {
                $error_message = $decoded_response['message'];
            } elseif (isset($decoded_response['error_decsription'])) {
                $error_message = $decoded_response['error_decsription'];
            } else {
                $error_message = $response;
            }

            throw new ApiException($error_message);
        }

        return $decoded_response;
    }

    /**
     * Performs API request signed with access token.
     *
     * @param string $url    API method URL
     * @param array  $data   API request data
     * @param array  $extra  Extra settings for curl
     * @param string $method HTTP method to perform request
     *
     * @return array API response and new token data if one is obtained
     *
     * @throws ApiException     If an API error occurred
     * @throws ContentException If a content is not a valid JSON
     */
    public function signedRequest($url = '', $data = array(), $extra = array(), $method = Http::POST)
    {
        $new_token = array();

        if ($this->isExpired()) {
            $this->obtainToken();
            $new_token = array(
                'access_token' => $this->getAccessToken(),
                'expiry_time'  => $this->getExpiryTime(),
            );
        }

        $extra = array_merge_recursive(
            $extra,
            array(
                'headers' => array(
                    'PayPal-Request-Id: ' . time(),
                    'Content-type: application/json',
                    'PayPal-Partner-Attribution-Id: ' . $this->bn_code,
                    'Authorization: Bearer ' . $this->access_token,
                ),
            )
        );

        $extra['headers'] = array_unique($extra['headers']);

        $response = $this->request($url, $data, $extra, $method);

        return array($response, $new_token);
    }

    /**
     * Gets OAuth access token.
     *
     * @return string OAuth access token
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * Gets OAuth access token expiry time.
     *
     * @param string $format Date format
     *
     * @return string OAuth access token expiry time
     */
    public function getExpiryTime($format = '')
    {
        if ($format) {
            return date($format, $this->expiry_time);
        }

        return $this->expiry_time;
    }

    /**
     * Checks whether HTTP status code indicates an error.
     *
     * @param int $status Code
     *
     * @return bool
     */
    protected function isErrorStatus($status)
    {
        return $status < 200 || $status > 299;
    }

    /**
     * Checks if the requestor is configured to perform API requests.
     *
     * @return bool
     */
    protected function isConfigured()
    {
        return $this->bn_code
            && $this->client_id
            && $this->secret;
    }

    /**
     * Performs request to API endpoint.
     *
     * @param string $url    API method URL
     * @param array  $data   API request data
     * @param array  $extra  Extra settings for curl
     * @param string $method HTTP method to perform request
     *
     * @return array API response
     *
     * @throws ApiException     If an API error occurred
     * @throws ContentException If a content is not a valid JSON
     */
    protected function request($url = '', $data = array(), $extra = array(), $method = Http::POST)
    {
        if (!$this->isConfigured()) {
            throw new ApiException('Configuration error');
        }

        $service_url = $this->is_test
            ? self::URL_TEST
            : self::URL_LIVE;

        $response = call_user_func(
            array('\\Tygh\\Http', strtolower($method)),
            $service_url . '/' . ltrim($url, '/'),
            $data,
            $extra
        );

        $headers = Http::getHeaders();
        $status = Http::getStatus();

        $response = $this->decodeResponse($response, $status, $headers);

        return $response;
    }
}
