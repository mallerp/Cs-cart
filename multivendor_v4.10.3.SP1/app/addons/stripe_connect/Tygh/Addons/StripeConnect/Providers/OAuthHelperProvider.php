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

namespace Tygh\Addons\StripeConnect\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\StripeConnect\OAuthHelper;
use Tygh\Payments\Addons\StripeConnect\StripeConnect;

/**
 * Class OAuthHelperProvider
 *
 * @package Tygh\Addons\StripeConnect\Providers
 */
class OAuthHelperProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.stripe_connect.oauth_helper'] = function(Container $app) {
            return new OAuthHelper(
                StripeConnect::getProcessorParameters(),
                fn_url('companies.stripe_connect_auth')
            );
        };
    }
}
