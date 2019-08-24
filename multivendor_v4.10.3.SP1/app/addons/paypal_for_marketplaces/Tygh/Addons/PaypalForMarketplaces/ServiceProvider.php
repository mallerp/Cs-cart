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

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\PaypalForMarketplaces\Api\Requestor;
use Tygh\Languages\Languages;
use Tygh\Payments\Addons\PaypalForMarketplaces\PaypalForMarketplaces;
use Tygh\Registry;

/**
 * Class OAuthHelperProvider
 *
 * @package Tygh\Addons\PaypalForMarketplaces\Providers
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.paypal_for_marketplaces.oauth_helper'] = function (Container $app) {

            $processor_params = PaypalForMarketplaces::getProcessorParameters();

            $api_requestor = new Requestor(
                $processor_params['bn_code'],
                $processor_params['client_id'],
                $processor_params['secret'],
                $processor_params['access_token'],
                $processor_params['expiry_time'],
                $processor_params['mode'] == 'test'
            );

            $redirect_url = fn_url('companies.paypal_for_marketplaces_auth');

            $company_id = Registry::get('runtime.company_id');

            $user_id = $app['session']['auth']['user_id'];

            $locale = Languages::getLocaleByLanguageCode(CART_LANGUAGE);

            $currency = CART_PRIMARY_CURRENCY;

            return new OAuthHelper(
                $api_requestor,
                $processor_params,
                $redirect_url,
                $company_id,
                $user_id,
                $locale,
                $currency
            );
        };

        $app['addons.paypal_for_marketplaces.processor.factory'] = function (Container $app) {

            $factory = new ProcessorFactory();

            $factory->setFormatter($app['formatter']);
            $factory->setDb($app['db']);
            $factory->setAddonSettings(Registry::get('addons.paypal_for_marketplaces'));
            $factory->setOAuthHelper($app['addons.paypal_for_marketplaces.oauth_helper']);

            return $factory;
        };
    }
}
