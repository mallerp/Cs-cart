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

namespace Tygh\Addons\StripeConnect;

use Stripe\Account;
use Stripe\OAuth;
use Stripe\Stripe;
use Tygh\Common\OperationResult;

class OAuthHelper
{
    /**
     * Add-on config.
     *
     * @var array $config
     */
    protected $config;

    /**
     * OAuth redirect URI.
     *
     * @var string $redirect_uri
     */
    protected $redirect_uri;

    /**
     * AuthHelper constructor.
     *
     * @param array  $config Add-on config
     * @param string $redirect_uri
     */
    public function __construct(array $config, $redirect_uri)
    {
        $this->config = $config;
        $this->redirect_uri = $redirect_uri;

        Stripe::setApiKey($this->config['secret_key']);
        Stripe::setClientId($this->config['client_id']);
    }

    /**
     * Provides URL to initiate OAuth flow to connect vendor to the store.
     *
     * @return OperationResult
     */
    public function getAuthorizeUrl()
    {
        $params = array(
            'redirect_uri' => $this->redirect_uri,
            'scope'        => 'read_write',
        );

        $result = new OperationResult();

        try {
            $url = OAuth::authorizeUrl($params);
            $result->setSuccess(true);
            $result->setData($url);
        } catch (\Exception $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
        }

        return $result;
    }

    /**
     * Obtains OAuth token.
     *
     * @param string $code Auth code
     *
     * @return \Tygh\Common\OperationResult
     */
    public function getToken($code)
    {
        $params = array(
            'code'       => $code,
            'grant_type' => 'authorization_code',
        );

        $decoded_response = Oauth::token($params);

        $result = new OperationResult();

        if (isset($decoded_response['stripe_user_id'])) {
            if ($this->isOwnAccount($decoded_response['stripe_user_id'])) {
                $result->setSuccess(false);
                $result->addError(0, __('stripe_connect.own_account_cant_be_used_for_vendor'));
            } else {
                $result->setSuccess(true);
                $result->setData($decoded_response['stripe_user_id']);
            }
        } elseif (isset($decoded_response['error_description'])) {
            $result->setSuccess(false);
            $result->addError($decoded_response['error'], $decoded_response['error_description']);
        } else {
            $result->setSuccess(false);
        }

        return $result;
    }

    /**
     * Disconnects Stripe account.
     *
     * @param $stripe_user_id
     *
     * @return \Tygh\Common\OperationResult
     */
    public function disconnect($stripe_user_id)
    {
        $params = array(
            'stripe_user_id' => $stripe_user_id,
        );

        $result = new OperationResult();

        try {
            OAuth::deauthorize($params);
            $result->setSuccess(true);
        } catch (\Exception $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
        }

        return $result;
    }

    /**
     * Checks if account is the account of store's owner.
     * This method is used to prevent vendors from connecting admin account.
     *
     * @param string $stripe_user_id
     *
     * @return bool
     */
    protected function isOwnAccount($stripe_user_id)
    {
        $root_account = Account::retrieve();

        return $root_account->id == $stripe_user_id;
    }
}