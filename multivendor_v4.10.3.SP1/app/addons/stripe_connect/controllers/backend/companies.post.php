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

use Tygh\Addons\StripeConnect\OAuthHelper;
use Tygh\Registry;

/** @var string $mode */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'stripe_connect_disconnect') {

        $company_id = Registry::get('runtime.company_id');

        $company_data = fn_get_company_data($company_id);

        if (!empty($company_data['stripe_connect_account_id'])) {

            /** @var OAuthHelper $oauth_helper */
            $oauth_helper = Tygh::$app['addons.stripe_connect.oauth_helper'];

            $disconnect_result = $oauth_helper->disconnect($company_data['stripe_connect_account_id']);
            if ($disconnect_result->isSuccess()) {
                fn_set_notification('N', __('notice'), __('stripe_connect.account_disconnected'));
            } else {
                fn_set_notification('E', __('error'), $disconnect_result->getFirstError());
            }

            fn_update_company(
                array(
                    'stripe_connect_account_id' => '',
                    'email'                     => $company_data['email'],
                ),
                $company_id
            );
        }

        return array(CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $company_id);
    }
}

if ($mode == 'update') {

    $company_data = Tygh::$app['view']->getTemplateVars('company_data');

    if (!empty($company_data['company_id'])) {

        if (empty($company_data['stripe_connect_account_id'])) {

            /** @var OAuthHelper $oauth_helper */
            $oauth_helper = Tygh::$app['addons.stripe_connect.oauth_helper'];

            $authorize_result = $oauth_helper->getAuthorizeUrl();

            if ($authorize_result->isSuccess()) {
                Tygh::$app['view']->assign(
                    'stripe_connect_url',
                    $authorize_result->getData()
                );
            }
        } else {

            Tygh::$app['view']->assign(
                'stripe_disconnect_url',
                fn_url('companies.stripe_connect_disconnect')
            );
        }
    }
} elseif ($mode == 'stripe_connect_auth') {

    $company_id = Registry::get('runtime.company_id');

    if (!empty($_REQUEST['code'])) {

        /** @var OAuthHelper $oauth_helper */
        $oauth_helper = Tygh::$app['addons.stripe_connect.oauth_helper'];

        $token_result = $oauth_helper->getToken($_REQUEST['code']);
        if ($token_result->isSuccess()) {

            $company_data = fn_get_company_data($company_id);

            fn_update_company(
                array(
                    'stripe_connect_account_id' => $token_result->getData(),
                    'email'                     => $company_data['email'],
                ),
                $company_id
            );

            fn_set_notification('N', __('notice'), __('stripe_connect.account_connected'));
        } else {
            fn_set_notification('E', __('error'), $token_result->getFirstError());
        }
    }

    return array(CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $company_id);
}