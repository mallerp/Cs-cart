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

namespace Tygh\Addons\Stripe;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\Stripe\HookHandlers\CheckoutHookHandler;
use Tygh\Addons\Stripe\HookHandlers\DispatchHookHandler;
use Tygh\Addons\Stripe\HookHandlers\PaymentsHookHandler;
use Tygh\Addons\Stripe\HookHandlers\ProductsHookHandler;
use Tygh\Addons\Stripe\PaymentButton\DataLoader;
use Tygh\Addons\Stripe\Payments\Stripe;
use Tygh\Registry;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.stripe.hook_handlers.payments'] = function (Container $app) {
            return new PaymentsHookHandler($app);
        };

        $app['addons.stripe.hook_handlers.dispatch'] = function (Container $app) {
            return new DispatchHookHandler($app);
        };

        $app['addons.stripe.hook_handlers.products'] = function (Container $app) {
            return new ProductsHookHandler($app);
        };
        $app['addons.stripe.hook_handlers.checkout'] = function (Container $app) {
            return new CheckoutHookHandler($app);
        };

        $app['addons.stripe.payment_button.data_loader'] = function (Container $app) {
            return new DataLoader($app);
        };

        $app['addons.stripe.price_formatter'] = function (Container $app) {
            return new PriceFormatter($app['formatter']);
        };

        $app['addons.stripe.payment_button.buttons'] = function (Container $app) {
            Registry::registerCache(
                'stripe_payment_buttons',
                ['payments', 'payment_processors'],
                Registry::cacheLevel('static'),
                true
            );

            $payment_buttons = Registry::ifGet('stripe_payment_buttons', null);
            if ($payment_buttons === null) {
                /** @var \Tygh\Addons\Stripe\PaymentButton\DataLoader $data_loader */
                $data_loader = $app['addons.stripe.payment_button.data_loader'];
                $payment_buttons = $data_loader->getSupportedPayments(Stripe::getScriptName());

                Registry::set('stripe_payment_buttons', $payment_buttons);
            }

            return $payment_buttons;
        };
    }
}
