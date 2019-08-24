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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Addons\PaypalForMarketplaces\OAuthHelper;
use Tygh\Payments\Addons\PaypalForMarketplaces\PaypalForMarketplaces;
use Tygh\Registry;
use Tygh\Tygh;

/** @var string $mode */

$runtime_company_id = Registry::get('runtime.company_id');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'paypal_for_marketplaces_disconnect') {

        if (!$runtime_company_id) {
            return array(CONTROLLER_STATUS_DENIED);
        }

        $company_data = fn_get_company_data($runtime_company_id);

        if (!empty($company_data['paypal_for_marketplaces_account_id'])) {

            fn_update_company(
                array(
                    'paypal_for_marketplaces_account_id' => '',
                    'email'                              => $company_data['email'],
                ),
                $runtime_company_id
            );

            fn_set_notification('N', __('notice'), __('paypal_for_marketplaces.account_disconnected'));
        }

        return array(CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $runtime_company_id);
    }
}

if ($mode == 'update') {

    $company_data = Tygh::$app['view']->getTemplateVars('company_data');

    if (!empty($company_data['company_id'])
        && $runtime_company_id == $company_data['company_id']
    ) {

        if (empty($company_data['paypal_for_marketplaces_account_id'])) {

            /** @var OAuthHelper $oauth_helper */
            $oauth_helper = Tygh::$app['addons.paypal_for_marketplaces.oauth_helper'];

            $authorize_result = $oauth_helper->getAuthorizeUrl();

            if ($authorize_result->isSuccess()) {
                Tygh::$app['view']->assign(
                    'paypal_for_marketplaces_connect_url',
                    $authorize_result->getData()
                );
            }
        } else {

            Tygh::$app['view']->assign(
                'paypal_for_marketplaces_disconnect_url',
                fn_url('companies.paypal_for_marketplaces_disconnect')
            );
        }
    }
} elseif ($mode == 'paypal_for_marketplaces_auth') {

    if (!$runtime_company_id) {
        return array(CONTROLLER_STATUS_DENIED);
    }

    $_REQUEST = array_merge(array(
        'merchantIdInPayPal' => null,
        'merchantId'         => null,
    ), $_REQUEST);

    $merchant_account_id = $_REQUEST['merchantIdInPayPal'];
    $owner_account_id = PaypalForMarketplaces::getOwnerAccountId();

    if ($merchant_account_id
        && $_REQUEST['merchantId'] == $runtime_company_id
        && $merchant_account_id != $owner_account_id
    ) {
        /** @var OAuthHelper $oauth_helper */
        $oauth_helper = Tygh::$app['addons.paypal_for_marketplaces.oauth_helper'];

        $merchant = $oauth_helper->getAccountInfo($merchant_account_id);

        if ($merchant->isSuccess()) {
            $company_data = fn_get_company_data($runtime_company_id);

            fn_update_company(
                array(
                    'paypal_for_marketplaces_account_id' => $merchant_account_id,
                    'email'                              => $company_data['email'],
                ),
                $runtime_company_id
            );

            fn_set_notification('N', __('notice'), __('paypal_for_marketplaces.account_connected'));
        } else {
            fn_set_notification('E', __('error'), $merchant->getFirstError());
        }
    } elseif ($merchant_account_id == $owner_account_id) {
        fn_set_notification('E', __('error'), __('paypal_for_marketplaces.own_account_cant_be_used_for_vendor'));
    } else {
        fn_set_notification('E', __('error'), __('paypal_for_marketplaces.account_connection_failure'));
    }

    return array(CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $runtime_company_id);
}