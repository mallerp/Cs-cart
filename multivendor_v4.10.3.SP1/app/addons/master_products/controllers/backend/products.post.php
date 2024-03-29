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

use Tygh\Enum\ProductFeatures;
use Tygh\Addons\MasterProducts\ServiceProvider;
use Tygh\Registry;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $controller */
/** @var string $mode */
/** @var string $action */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'sell_master_product') {
        $service = ServiceProvider::getService();
        $company_id = Registry::get('runtime.company_id');

        $result = $service->createVendorProduct($_REQUEST['master_product_id'], Registry::get('runtime.company_id'));

        if (!$result->isSuccess()) {
            $result->showNotifications();
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        return [CONTROLLER_STATUS_OK, 'products.update?product_id=' . $result->getData('vendor_product_id')];
    }

    if ($mode === 'm_sell_master_product') {
        $service = ServiceProvider::getService();

        $updated_products = [];

        if (!empty($_REQUEST['master_product_ids'])) {
            foreach ($_REQUEST['master_product_ids'] as $master_product_id) {

                $result = $service->createVendorProduct($master_product_id, Registry::get('runtime.company_id'));

                if ($result->isSuccess() && !$result->getData('vendor_product_exists', false)) {
                    $updated_products[] = $result->getData('vendor_product_id');
                }
            }
        }

        if ($updated_products) {
            fn_set_notification(
                'N',
                __('notice'),
                __('master_products.products_were_added_to_your_products', [count($updated_products)])
            );

            unset($_REQUEST['redirect_url']);

            return [CONTROLLER_STATUS_REDIRECT, 'products.manage?' . http_build_query(['pid' => $updated_products])];
        }

        return [CONTROLLER_STATUS_REDIRECT, 'products.master_products'];
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'manage' || $mode === 'master_products') {
    fn_master_products_generate_navigation_sections($controller, $mode);
}

if ($mode === 'master_products') {
    unset(Tygh::$app['session']['product_ids']);
    unset(Tygh::$app['session']['selected_fields']);

    $params = $_REQUEST;
    $params['only_short_fields'] = true;
    $params['apply_disabled_filters'] = true;
    $params['show_master_products_only'] = true;
    $params['extend'][] = 'companies';

    list($products, $search) = fn_get_products(
        $params,
        Registry::get('settings.Appearance.admin_elements_per_page'),
        DESCR_SL
    );
    fn_gather_additional_products_data($products, [
        'get_icon'            => true,
        'get_detailed'        => true,
        'get_options'         => false,
        'get_discounts'       => false,
        'get_vendor_products' => true
    ]);

    $page = $search['page'];
    $valid_page = db_get_valid_page($page, $search['items_per_page'], $search['total_items']);

    if ($page > $valid_page) {
        $_REQUEST['page'] = $valid_page;

        return [CONTROLLER_STATUS_REDIRECT, Registry::get('config.current_url')];
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $view->assign('products', $products);
    $view->assign('search', $search);

    $selected_fields = fn_get_product_fields();

    $view->assign('selected_fields', $selected_fields);
    $filter_params = [
        'get_variants' => true,
        'short'        => true,
        'feature_type' => str_split(ProductFeatures::getAllTypes()),
    ];
    list($filters) = fn_get_product_filters($filter_params);
    $view->assign('filter_items', $filters);
    unset($filters);

    $feature_params = [
        'plain'           => true,
        'statuses'        => ['A', 'H'],
        'variants'        => true,
        'exclude_group'   => true,
        'exclude_filters' => true,
    ];

    // Preload variants selected at search form. They will be shown at AJAX variants loader as pre-selected.
    if (!empty($_REQUEST['feature_variants'])) {
        $feature_params['variants_only'] = $_REQUEST['feature_variants'];
    }

    list($features, $features_search) = fn_get_product_features($feature_params, PRODUCT_FEATURES_THRESHOLD);

    if ($features_search['total_items'] <= PRODUCT_FEATURES_THRESHOLD) {
        $view->assign('feature_items', $features);
    } else {
        $view->assign('feature_items_too_many', true);
    }
}

if (!Registry::get('runtime.company_id') && ($mode === 'update' || $mode === 'add' || $mode === 'm_add' || $mode === 'm_update')) {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $view->assign('zero_company_id_name_lang_var', 'master_products.all_vendors_master_product');
}

return [CONTROLLER_STATUS_OK];
