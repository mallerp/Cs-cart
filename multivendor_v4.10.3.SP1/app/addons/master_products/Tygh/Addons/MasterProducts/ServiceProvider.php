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

namespace Tygh\Addons\MasterProducts;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\MasterProducts\Product\ProductIdMap;
use Tygh\Addons\MasterProducts\Product\Repository as ProductRepository;
use Tygh\Addons\ProductVariations\ServiceProvider as VariationsServiceProvider;
use Tygh\Tygh;

/**
 * Class ServiceProvider is intended to register services and components of the "Master products" add-on to the
 * application container.
 *
 * @package Tygh\Addons\MasterProducts
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.master_products.product.repository'] = function (Container $app) {
            return new ProductRepository(
                VariationsServiceProvider::getQueryFactory(),
                array_keys($app['languages'])
            );
        };

        $app['addons.master_products.service'] = function (Container $app) {
            return new Service(
                self::getProductRepository(),
                function () { return (array) fn_get_schema('master_products', 'product_data_sync'); },
                function () { return (array) fn_get_schema('master_products', 'product_data_copy'); }
            );
        };

        $app['addons.master_products.product.product_id_map'] = function (Container $app) {
            return new ProductIdMap(self::getProductRepository());
        };
    }

    /**
     * @return \Tygh\Addons\MasterProducts\Service
     */
    public static function getService()
    {
        return Tygh::$app['addons.master_products.service'];
    }

    /**
     * @return \Tygh\Addons\MasterProducts\Product\Repository
     */
    public static function getProductRepository()
    {
        return Tygh::$app['addons.master_products.product.repository'];
    }

    /**
     * @return \Tygh\Addons\MasterProducts\Product\ProductIdMap
     */
    public static function getProductIdMap()
    {
        return Tygh::$app['addons.master_products.product.product_id_map'];
    }
}
