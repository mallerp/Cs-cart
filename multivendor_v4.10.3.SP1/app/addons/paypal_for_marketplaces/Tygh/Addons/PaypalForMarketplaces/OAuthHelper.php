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

namespace Tygh\Addons\PaypalForMarketplaces;

use Exception;
use Tygh\Addons\PaypalForMarketplaces\Api\Requestor;
use Tygh\Common\OperationResult;
use Tygh\Http;

class OAuthHelper
{
    /** @var \Tygh\Addons\PaypalForMarketplaces\Api\Requestor $api_requestor */
    protected $api_requestor;

    /** @var array $config */
    protected $config;

    /** @var string $redirect_uri */
    protected $redirect_uri;

    /** @var int $company_id */
    protected $company_id;

    /** @var int $user_id */
    protected $user_id;

    /** @var string $locale */
    protected $locale;

    /** @var string $currency */
    protected $currency;

    public function __construct(
        Requestor $api_requestor,
        array $config,
        $redirect_uri,
        $company_id,
        $user_id,
        $locale,
        $currency
    ) {
        $this->api_requestor = $api_requestor;
        $this->config = $config;
        $this->redirect_uri = $redirect_uri;
        $this->company_id = (int) $company_id;
        $this->user_id = (int) $user_id;
        $this->locale = $locale;
        $this->currency = $currency;
    }

    /**
     * Provides URL to initiate OAuth flow to connect vendor to the store.
     *
     * @return OperationResult
     */
    public function getAuthorizeUrl()
    {
        $referal_request = $this->buildReferalRequest();

        $result = new OperationResult();

        try {
            list($response,) = $this->api_requestor->signedRequest(
                '/v1/customer/partner-referrals',
                json_encode($referal_request)
            );
            $result->setSuccess(true);
            foreach ($response['links'] as $link_data) {
                if ($link_data['rel'] === 'action_url') {
                    $result->setData($link_data['href']);
                }
            }
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->setErrors(array($e->getMessage()));
        }

        return $result;
    }

    /**
     * Checks if account is the account of store's owner.
     * This method is used to prevent vendors from connecting admin account.
     *
     * @param string $account_id
     *
     * @return bool
     */
    protected function isOwnAccount($account_id)
    {
        return $this->config['payer_id'] == $account_id;
    }

    /**
     * Build merchant onboarding request body.
     *
     * @return array
     */
    protected function buildReferalRequest()
    {
        $user_info = $this->getUserInfo();
        $placement_info = $this->getCompanyPlacementInfo();
        $partner_logo_url = $this->getMarketplaceLogoUrl();

        $partner_client_id = $this->config['client_id'];
        $partner_payer_id = $this->config['payer_id'];

        $request = array(
            'customer_data'             => array(
                'customer_type'                => 'MERCHANT',
                'person_details'               => array(
                    'email_address' => $user_info['email'],
                ),
                'business_details'             => array(
                    'email_contacts' => array(
                        array(
                            'email_address' => $placement_info['company_support_department'] ?: $user_info['email'],
                            'role'          => 'CUSTOMER_SERVICE',
                        ),
                    ),
                ),
                'preferred_language_code'      => $this->locale,
                'primary_currency_code'        => $this->currency,
                'referral_user_payer_id'       => array(
                    'type'  => 'PAYER_ID',
                    'value' => $partner_payer_id,
                ),
                'partner_specific_identifiers' => array(
                    array(
                        'type'  => 'TRACKING_ID',
                        'value' => $this->company_id,
                    ),
                ),
            ),
            'requested_capabilities'    => array(
                array(
                    'capability'                 => 'API_INTEGRATION',
                    'api_integration_preference' => array(
                        'rest_api_integration'     => array(
                            'integration_method' => 'PAYPAL',
                            'integration_type'   => 'THIRD_PARTY',
                        ),
                        'rest_third_party_details' => array(
                            'partner_client_id' => $partner_client_id,
                            'feature_list'      => array(
                                'PAYMENT',
                                'REFUND',
                                'PARTNER_FEE',
                                'DELAY_FUNDS_DISBURSEMENT',
                            ),
                        ),
                        'partner_id'               => $partner_payer_id,
                    ),
                ),
            ),
            'web_experience_preference' => array(
                'partner_logo_url'   => $partner_logo_url,
                'return_url'         => $this->redirect_uri,
                'action_renewal_url' => $this->redirect_uri,
            ),
            'collected_consents'        => array(
                array(
                    'type'    => 'SHARE_DATA_CONSENT',
                    'granted' => true,
                ),
            ),
            'products'                  => array('EXPRESS_CHECKOUT'),
        );

        return $request;
    }

    /**
     * Provides store's logo URL to display in registration form.
     *
     * @return string URL
     */
    protected function getMarketplaceLogoUrl()
    {
        $logos = fn_get_logos(0);

        $logo_url = $logos['theme']['image']['image_path'];

        return $logo_url;
    }

    /**
     * Provides company placement information.
     *
     * @return array
     */
    protected function getCompanyPlacementInfo()
    {
        $placement_info = fn_get_company_placement_info($this->company_id);

        return $placement_info;
    }

    /**
     * Provides authenticated user info.
     *
     * @return array
     */
    protected function getUserInfo()
    {
        $user_info = fn_get_user_info($this->user_id);

        return $user_info;
    }

    /**
     * Obtains merchant info from PayPal.
     *
     * @param string $account_id Merchant ID in PayPal
     *
     * @return OperationResult Contains merchant info as data on success
     */
    public function getAccountInfo($account_id)
    {
        $result = new OperationResult();

        $url = sprintf(
            '/v1/customer/partners/%s/merchant-integrations/%s',
            $this->config['payer_id'],
            $account_id
        );

        $vendor = null;

        try {
            $vendor = $this->api_requestor->signedRequest(
                $url,
                '',
                array(),
                Http::GET
            );

            $result->setSuccess(true);
            foreach ($vendor as $key => $info) {
                if (isset($info['merchant_id'])) {
                    $result->setData($info);
                    break;
                }
            }
        } catch (Exception $e) {
            $result->addError($e->getCode(), $e->getMessage());
        }

        return $result;
    }
}