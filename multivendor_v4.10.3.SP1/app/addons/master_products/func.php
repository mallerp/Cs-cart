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

use Tygh\BlockManager\Block;
use Tygh\BlockManager\ProductTabs;
use Tygh\Registry;
use Tygh\Addons\MasterProducts\ServiceProvider;
use Tygh\Addons\ProductVariations\ServiceProvider as VariationsServiceProvider;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductAddedEvent;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;

/**
 * Installs the add-on products block and the product tab.
 */
function fn_master_products_install()
{
    $company_ids = [0];

    /** @var \Tygh\BlockManager\Block $block */
    $block = Block::instance();
    /** @var ProductTabs $product_tabs */
    $product_tabs = ProductTabs::instance();

    foreach ($company_ids as $company_id) {
        $block_data = [
            'type'         => 'products',
            'properties'   => [
                'template' => 'addons/master_products/blocks/products/vendor_products.tpl',
            ],
            'content_data' => [
                'content' => [
                    'items' => [
                        'filling' => 'master_products.vendor_products_filling',
                        'limit'   => '0',
                    ],
                ],
            ],
            'company_id'   => $company_id,
        ];

        $block_description = [
            'lang_code' => DEFAULT_LANGUAGE,
            'name'      => __('master_products.vendor_products_block_name', [], DEFAULT_LANGUAGE),
        ];

        $block_id = $block->update($block_data, $block_description);

        $tab_data = [
            'tab_type'      => 'B',
            'block_id'      => $block_id,
            'template'      => '',
            'addon'         => 'master_products',
            'status'        => 'A',
            'is_primary'    => 'N',
            'position'      => 0,
            'product_ids'   => null,
            'company_id'    => $company_id,
            'show_in_popup' => 'N',
            'lang_code'     => DEFAULT_LANGUAGE,
            'name'          => __('master_products.vendor_products_tab_name', [], DEFAULT_LANGUAGE),
        ];

        $product_tabs->update($tab_data);
    }
}

/**
 * Hook handler: adds extra products search parameters.
 *
 * @param array  $params         Products search params
 * @param int    $items_per_page Amount of products shown per page
 * @param string $lang_code      Two-letter language code for product descriptions
 */
function fn_master_products_get_products_pre(&$params, $items_per_page, $lang_code)
{
    $params = array_merge([
        'show_master_products_only'  => false,
        'area'                       => AREA,
    ], $params);

    $params['runtime_company_id'] = (int) Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id'));

    // vendors must see only active master products
    if ($params['show_master_products_only'] && $params['runtime_company_id']) {
        $params['status'] = 'A';
    }
}

/**
 * Hook handler: modifies products obtaining process to include vendor products into the list.
 */
function fn_master_products_get_products(
    &$params,
    &$fields,
    $sortings,
    &$condition,
    &$join,
    $sorting,
    &$group_by,
    $lang_code,
    $having
) {
    $condition_replacements = [];

    if ($params['area'] === 'C') {
        $condition .= db_quote(' AND products.master_product_status IN (?a)', ['A']);

        $search = db_quote('AND companies.status = ?s', 'A');
        $replace = db_quote('AND (companies.status = ?s OR products.company_id = ?i)', 'A', 0);
        $condition_replacements[$search] = $replace;

        if (!empty($params['vendor_products_by_product_id'])) {
            $repository = ServiceProvider::getProductRepository();

            $master_product_id = $repository->findMasterProductId($params['vendor_products_by_product_id']);

            if (!$master_product_id) {
                $master_product_id = (int) $params['vendor_products_by_product_id'];
            }

            $condition .= db_quote(' AND products.master_product_id = ?i', $master_product_id);
        } elseif (empty($params['runtime_company_id'])) {
            $condition .= db_quote(' AND products.master_product_id = ?i AND (products.company_id > 0 OR products.master_product_offers_count > 0)', 0);
        }
    }

    if ($params['show_master_products_only']) {
        if ($params['runtime_company_id']) {
            $search = db_quote(' AND products.company_id = ?i', $params['runtime_company_id']);
            $replace = db_quote(' AND products.company_id = ?i', 0);
            $condition_replacements[$search] = $replace;
        } else {
            $condition .= db_quote(' AND products.company_id = ?i', 0);
        }
    } elseif ($params['area'] === 'A'
        && empty($params['pid'])
        && empty($params['show_all_products'])
        && empty($params['has_not_variation_group'])
        && !isset($params['master_product_id'])
        && !isset($params['parent_product_id'])
    ) {
        $condition .= db_quote(' AND products.company_id <> ?i ', 0);
    }

    if (!empty($params['remove_company_condition'])) {
        $search = db_quote(' AND products.company_id = ?i', $params['runtime_company_id']);
        $condition_replacements[$search] = '';
    }

    if (!empty($params['master_product_id'])) {
        if (is_array($params['master_product_id'])) {
            $condition .= db_quote(' AND products.master_product_id IN (?n)', $params['master_product_id']);
        } else {
            $condition .= db_quote(' AND products.master_product_id = ?i', $params['master_product_id']);
        }
    }

    // FIXME: Dirty hack
    if ($condition_replacements) {
        $condition = strtr($condition, $condition_replacements);
    }

    $fields['master_product_id'] = 'products.master_product_id';
    $fields['company_id'] = 'products.company_id';

    return;
}

function fn_master_products_gather_additional_products_data_params($product_ids, &$params, &$products, $auth)
{
    if (!isset($params['get_vendor_products'])) {
        $params['get_vendor_products'] = false;
    }

    if (!isset($params['get_vendor_product_ids'])) {
        $params['get_vendor_product_ids'] = false;
    }

    if (!isset($params['runtime_company_id'])) {
        $params['runtime_company_id'] = (int) Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id'));
    }

    if ($params['get_vendor_products'] && !empty($params['runtime_company_id'])) {
        list($vendor_products) = fn_get_products([
            'master_product_id' => $product_ids,
            'company_id'        => $params['runtime_company_id']
        ]);

        $product_id_map = array_column($vendor_products, 'product_id', 'master_product_id');

        foreach ($products as &$product) {
            $product_id = $product['product_id'];
            $vendor_product_id = isset($product_id_map[$product_id]) ? (int) $product_id_map[$product_id] : null;

            $product['vendor_product'] = isset($vendor_products[$vendor_product_id]) ? $vendor_products[$vendor_product_id] : null;
            $product['vendor_product_id'] = $vendor_product_id;
        }
        unset($product);
    }

    if ($params['get_vendor_product_ids'] && !empty($params['runtime_company_id'])) {
        $repository = ServiceProvider::getProductRepository();

        $product_id_map = $repository->findVendorProductIdsByMasterProductIds($product_ids, $params['runtime_company_id']);

        foreach ($products as &$product) {
            $product_id = $product['product_id'];
            $product['vendor_product_id'] = isset($product_id_map[$product_id]) ? (int) $product_id_map[$product_id] : null;
        }
        unset($product);
    }
}

function fn_master_products_gather_additional_products_data_post($product_ids, $params, $products, $auth, $lang_code)
{
    if (AREA !== 'C') {
        return;
    }

    $vendor_id = fn_master_products_get_runtime_company_id();

    if (empty($vendor_id)) {
        return;
    }

    $repository = VariationsServiceProvider::getProductRepository();
    $product_id_map = VariationsServiceProvider::getProductIdMap();

    foreach ($products as &$product) {
        if (empty($product['company_id'])
            || empty($product['master_product_id'])
            || empty($product['detailed_params']['info_type'])
            || $product['detailed_params']['info_type'] !== 'D'
        ) {
            continue;
        }

        $master_product_id = $product['master_product_id'];

        if (!$product_id_map->isChildProduct($master_product_id) && !$product_id_map->isParentProduct($master_product_id)) {
            continue;
        }

        $master_product = $repository->findProduct($master_product_id);

        if (!$master_product) {
            continue;
        }

        $master_product = $repository->loadProductGroupInfo($master_product);
        $master_product = $repository->loadProductFeaturesVariants($master_product);

        if (empty($master_product['variation_features_variants'])) {
            continue;
        }

        $product['variation_features_variants'] = $master_product['variation_features_variants'];
        $master_product_ids = [];

        foreach ($product['variation_features_variants'] as $features) {
            if (empty($features['variants'])) {
                continue;
            }

            foreach ($features['variants'] as $variant) {
                if (empty($variant['product'])) {
                    continue;
                }

                $master_product_ids[$variant['product']['product_id']] = $variant['product']['product_id'];
            }
        }

        if (!$master_product_ids) {
            continue;
        }

        $vendor_products_map = ServiceProvider::getProductRepository()->findVendorProductIdsByMasterProductIds($master_product_ids, $vendor_id, ['A']);

        if (empty($vendor_products_map)) {
            continue;
        }

        foreach ($product['variation_features_variants'] as &$features) {
            if (empty($features['variants'])) {
                continue;
            }

            foreach ($features['variants'] as &$variant) {
                if (empty($variant['product'])) {
                    continue;
                }

                $variant_product_id = $variant['product']['product_id'];

                if (empty($vendor_products_map[$variant_product_id])) {
                    continue;
                }

                $variant['product']['product_id'] = $vendor_products_map[$variant_product_id];
            }
            unset($variant);
        }
        unset($features);
    }
    unset($product);
}

/**
 * Hook handler: adds master product ID into a list of fetched product fields.
 */
function fn_master_products_get_product_data($product_id, &$field_list, &$join, $auth, $lang_code, $condition)
{
    $field_list .= ', ?:products.master_product_id, ?:products.master_product_status';
}

/**
 * Fetches company ID from any passed object or runtime.
 * FIXME: Obtaining company_id from the $_REQUEST is ugly. Must be redone.
 *
 * @param array|null $object Object to extract company_id from
 * @param string     $area   Site area
 *
 * @return int Company ID
 */
function fn_master_products_get_runtime_company_id($object = null, $area = AREA)
{
    if ($object === null && $area === 'C') {
        // FIXME
        $object = $_REQUEST;
    }

    static $runtime_company_id;

    if (isset($object['vendor_id'])) {
        return (int) $object['vendor_id'];
    }

    if ($runtime_company_id === null) {
        $runtime_company_id = (int) Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id'));
    }

    return $runtime_company_id;
}

/**
 * Helper function that generates sidebar menu with master and vendor products on the products management pages.
 *
 * @param string $controller Currently dispatched controller
 * @param string $mode       Currently dispatched controller mode
 */
function fn_master_products_generate_navigation_sections($controller, $mode)
{
    $section = $controller . '.' . $mode;

    Registry::set('navigation.dynamic.sections', [
        'products.manage'          => [
            'title' => __('master_products.products_being_sold'),
            'href'  => 'products.manage',
        ],
        'products.master_products' => [
            'title' => __('master_products.products_that_vendors_can_sell'),
            'href'  => 'products.master_products',
        ],
    ]);

    Registry::set('navigation.dynamic.active_section', $section);
}

/**
 * Hook handler: allows viewing master products.
 */
function fn_master_products_company_products_check(&$product_ids, $notify, &$company_condition)
{
    $controller = Registry::ifGet('runtime.controller', 'products');
    $mode = Registry::ifGet('runtime.mode', 'update');
    $request_method = isset($_SERVER['REQUEST_METHOD']) // FIXME
        ? $_SERVER['REQUEST_METHOD']
        : 'GET';

    if ($controller !== 'products' || $mode !== 'update' || $request_method !== 'GET') {
        return;
    }

    $company_condition = fn_get_company_condition(
        '?:products.company_id',
        true,
        Registry::get('runtime.company_id'),
        true
    );
}

/**
 * Hook handler: allows viewing master products.
 */
function fn_master_products_is_product_company_condition_required_post($product_id, &$is_required)
{
    $product_company_id = (int) db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);

    if ($product_company_id === 0) {
        $is_required = false;
    }
}

/**
 * Hook handler: updates vendor products descriptions when editing a master product.
 */
function fn_master_products_update_product_post($product_data, $product_id, $lang_code, $create)
{
    if ($create) {
        return;
    }

    $product_id_map = ServiceProvider::getProductIdMap();

    if (!$product_id_map->isMasterProduct($product_id) && !$product_id_map->isVendorProduct($product_id)) {
        return;
    }

    $service = ServiceProvider::getService();

    $service->onTableChanged('products', $product_id);
    $service->onTableChanged('product_descriptions', $product_id);
    $service->onTableChanged('product_status', $product_id);

    $service->actualizeMasterProductPrice($product_id);
    $service->actualizeMasterProductOffersCount($product_id);
    $service->actualizeMasterProductQuantity($product_id);
}

/**
 * Hook handler: prevents vendor product categories update.
 */
function fn_master_products_update_product_categories_pre($product_id, &$product_data, $rebuild, $company_id)
{
    if (empty($product_data['category_ids'])) {
        return;
    }

    $repository = ServiceProvider::getProductRepository();

    if ($repository->findMasterProductId($product_id)) {
        $product_data['category_ids'] = [];
    }
}

/**
 * Hook handler: updates vendor products categories when editing a master product.
 */
function fn_master_products_update_product_categories_post(
    $product_id,
    $product_data,
    $existing_categories,
    $rebuild,
    $company_id
) {
    $service = ServiceProvider::getService();
    $service->onTableChanged('products_categories', $product_id);
}

/**
 * Hook handler: actualizers master product price on vendor product removal.
 */
function fn_master_products_delete_product_pre($product_id, $status)
{
    Registry::del('master_products.removed_product');

    if (!$status) {
        return;
    }

    $repository = ServiceProvider::getProductRepository();

    $master_product_id = $repository->findMasterProductId($product_id);

    if ($master_product_id) {
        Registry::set('master_products.removed_product.master_product_id', $master_product_id, true);
    }
}

/**
 * Hook handler: removes vendor products after master product removal.
 */
function fn_master_products_delete_product_post($product_id, $is_deleted)
{
    if (!$is_deleted) {
        return;
    }

    $repository = ServiceProvider::getProductRepository();
    $service = ServiceProvider::getService();

    $vendor_product_ids = $repository->findVendorProductIds($product_id);

    foreach ($vendor_product_ids as $vendor_product_id) {
        fn_delete_product($vendor_product_id);
    }

    $master_product_id = Registry::get('master_products.removed_product.master_product_id');

    if ($master_product_id) {
        $service->actualizeMasterProductPrice($master_product_id);
        $service->actualizeMasterProductOffersCount($master_product_id);
        $service->actualizeMasterProductQuantity($master_product_id);
    }
    Registry::del('master_products.removed_product');
}


/**
 * Hook handler: actualizes master product price when the disabling/enabling vendor products.
 */
function fn_master_products_tools_change_status($params, $result)
{
    if (!$result || $params['table'] !== 'products') {
        return;
    }

    $product_id = $params['id'];

    $product_id_map = ServiceProvider::getProductIdMap();

    if (!$product_id_map->isMasterProduct($product_id) && !$product_id_map->isVendorProduct($product_id)) {
        return;
    }

    $service = ServiceProvider::getService();

    $service->onTableChanged('product_status', $product_id);
    $service->actualizeMasterProductPrice($product_id);
    $service->actualizeMasterProductOffersCount($product_id);
    $service->actualizeMasterProductQuantity($product_id);
}

function fn_master_products_product_type_create_by_product($product, $product_id, &$type)
{
    if (!empty($product['master_product_id'])) {
        $type = PRODUCT_TYPE_VENDOR_PRODUCT_OFFER;
    }
}

/**
 * Hook handler: normalize request for children products
 */
function fn_master_products_get_route(&$req, &$result, $area, &$is_allowed_url)
{
    if ($area !== 'C'
        || empty($req['dispatch'])
        || $req['dispatch'] !== 'products.view'
        || empty($req['product_id'])
        || empty($req['vendor_id'])
    ) {
        return;
    }

    $repository = ServiceProvider::getProductRepository();

    if (isset($req['variation_id'])) {
        $vendor_product_id = $repository->findVendorProductId($req['variation_id'], $req['vendor_id']);

        if (!$vendor_product_id) {
            return;
        }

        $product_id_map = VariationsServiceProvider::getProductIdMap();

        $parent_product_id = $product_id_map->getParentProductId($vendor_product_id);

        if ($parent_product_id) {
            $req['variation_id'] = $vendor_product_id;
            $req['product_id'] = $parent_product_id;
        } else {
            $req['product_id'] = $vendor_product_id;
            unset($req['variation_id']);
        }
    } else {
        $vendor_product_id = $repository->findVendorProductId($req['product_id'], $req['vendor_id']);

        if (!$vendor_product_id) {
            return;
        }

        $req['product_id'] = $vendor_product_id;
    }
}

/**
 * Hook handler: sync global options
 */
function fn_master_products_add_global_option_link_post($product_id, $option_id)
{
    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('product_global_option_links', $product_id, ['option_id' => $option_id]);
}

/**
 * Hook handler: sync global options
 */
function fn_master_products_delete_global_option_link_post($product_id, $option_id)
{
    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('product_global_option_links', $product_id, ['option_id' => $option_id]);
}

/**
 * Hook handler: sync feature values
 */
function fn_master_products_update_product_features_value_post($product_id)
{
    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('product_features_values', $product_id);
}

/**
 * @param \Tygh\Addons\ProductVariations\Service                       $group_service
 * @param \Tygh\Addons\ProductVariations\Product\Group\Group           $group
 * @param \Tygh\Addons\ProductVariations\Product\Group\Events\AEvent[] $events
 */
function fn_master_products_variation_group_save_group($group_service, $group, $events)
{
    if (Registry::get('runtime.product_variations_converter_mode') !== 'combinations') {
        return;
    }

    static $service;
    static $product_repository;
    static $query_factory;
    static $group_repository;

    $product_ids = [];
    $product_map = [];

    foreach ($events as $event) {
        if ($event instanceof ProductAddedEvent) {
            $product_id = $event->getProduct()->getProductId();
            $parent_product_id = $event->getProduct()->getParentProductId();

            $product_map[$parent_product_id][$product_id] = $product_id;

            if ($parent_product_id) {
                continue;
            }

            $product_ids[] = $product_id;
        }
    }

    if (!$product_ids) {
        return;
    }

    if ($service === null) {
        $service = ServiceProvider::getService();
    }

    if ($product_repository === null) {
        $product_repository = ServiceProvider::getProductRepository();
    }

    if ($query_factory === null) {
        $query_factory = VariationsServiceProvider::getQueryFactory();
    }

    if ($group_repository === null) {
        $group_repository = VariationsServiceProvider::getGroupRepository();
    }

    foreach ($product_ids as $product_id) {
        $vendor_product_ids = $product_repository->findVendorProductIds($product_id);

        if (empty($vendor_product_ids)) {
            continue;
        }

        $service->syncAllData($product_id, $vendor_product_ids);

        $query = $query_factory->createQuery(
            $product_repository::TABLE_PRODUCTS,
            ['product_id' => $vendor_product_ids],
            ['product_id', 'company_id']
        );

        foreach ($query->select() as $item) {
            $group_product_ids = [$item['product_id']];

            foreach ($product_map[$product_id] as $variation_product_id) {
                $result = $service->createVendorProduct($variation_product_id, $item['company_id']);

                if ($result->isSuccess()) {
                    $group_product_ids[] = $result->getData('vendor_product_id');
                }
            }

            if (count($group_product_ids) <= 1) {
                continue;
            }

            $group_id = $group_repository->findGroupIdByProductId($item['product_id']);

            if ($group_id) {
                $result = $group_service->attachProductsToGroup($group_id, $group_product_ids);
            } else {
                $result = $group_service->createGroup($group_product_ids, null, $group->getFeatures());
            }

            if (!$result->isSuccess()) {
                foreach ($group_product_ids as $item_product_id) {
                    if ($item_product_id != $item['product_id']) {
                        fn_delete_product($item_product_id);
                    }
                }
            }
        }
    }
}

function fn_master_products_clone_product_data($product_id, &$data, $is_cloning_allowed)
{
    if (empty($data)) {
        return;
    }

    unset(
        $data['master_product_id'],
        $data['master_product_status'],
        $data['master_product_offers_count']
    );
}

function fn_master_products_variation_group_create_products_by_combinations_item($service, $parent_product_id, $combination_id, $combination, &$product_data)
{
    if (empty($product_data)) {
        return;
    }

    unset(
        $product_data['master_product_id'],
        $product_data['master_product_status'],
        $product_data['master_product_offers_count']
    );
}

/**
 * @param \Tygh\Addons\ProductVariations\SyncService $sync_service
 * @param array $events
 */
function fn_master_products_variation_sync_flush_sync_events($sync_service, $events)
{
    if (Registry::get('runtime.product_variations_converter_mode')) {
        return;
    }

    $product_ids = [];
    $table_product_ids = [];

    foreach ($events as $event) {
        if (empty($event['destination_product_ids'])
            || empty($event['table_id'])
        ) {
            continue;
        }

        foreach ($event['destination_product_ids'] as $product_id) {
            $product_ids[$product_id] = $product_id;
            $table_product_ids[$product_id][$event['table_id']] = $event['table_id'];
        }
    }

    if (empty($product_ids)) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();
    $service = ServiceProvider::getService();

    $vendor_product_ids_map = $product_repository->findVendorProductIdsByMasterProductIds($product_ids);

    foreach ($vendor_product_ids_map as $master_product_id => $vendor_product_ids) {
        foreach ($table_product_ids[$master_product_id] as $table_id) {
            $service->syncData($table_id, $master_product_id, (array) $vendor_product_ids);
        }
    }
}

function fn_master_products_get_attachments_pre($object_type, &$object_id, $type, $lang_code)
{
    if ($object_type !== 'product' || !empty($params['skip_check_vendor_product'])) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();

    $master_product_id = $product_repository->findMasterProductId($object_id);

    if ($master_product_id) {
        $object_id = $master_product_id;
    }
}

function fn_master_products_get_discussion_pre(&$object_id, $object_type, $get_posts, $params)
{
    if ($object_type !== DISCUSSION_OBJECT_TYPE_PRODUCT || !empty($params['skip_check_vendor_product'])) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();

    $master_product_id = $product_repository->findMasterProductId($object_id);

    if ($master_product_id) {
        $object_id = $master_product_id;
    }
}

function fn_master_products_get_product_data_post(&$product_data, $auth, $preview, $lang_code)
{
    ServiceProvider::getProductIdMap()->setMastertProductIdMapByProducts([$product_data]);
}

function fn_master_products_load_products_extra_data_pre(&$products, $params, $lang_code)
{
    ServiceProvider::getProductIdMap()->setMastertProductIdMapByProducts($products);
}

function fn_master_products_url_pre(&$url, $area, $protocol, $lang_code)
{
    if ($area !== 'C'
        || strpos($url, 'products.view') === false
        || Registry::get('addons.seo.status') !== 'A'
    ) {
        return;
    }

    $parsed_url = parse_url($url);
    $dispatch = null;

    if (empty($parsed_url['query'])) {
        return;
    }

    parse_str($parsed_url['query'], $parsed_query);

    if (isset($parsed_query['dispatch'])) {
        $dispatch = $parsed_query['dispatch'];
    } elseif (isset($parsed_url['path'])) {
        $dispatch = $parsed_url['path'];
    }

    if (empty($parsed_query['product_id']) || $dispatch !== 'products.view') {
        return;
    }

    $product_id = $parsed_query['product_id'];
    $master_product_id = ServiceProvider::getProductIdMap()->getMasterProductId($product_id);

    if (!$master_product_id) {
        return;
    }

    $company_id = ServiceProvider::getProductIdMap()->getVendorProductCompanyId($product_id);

    if (Registry::get('runtime.seo.is_creating_canonical_url')) {
        $url = strtr($url, ["product_id={$product_id}" => "product_id={$master_product_id}"]);
    } else {
        $url = strtr($url, ["product_id={$product_id}" => "product_id={$master_product_id}&vendor_id={$company_id}"]);
    }
}

function fn_master_products_update_image_pairs($pair_ids, $icons, $detailed, $pairs_data, $object_id, $object_type)
{
    if (empty($pair_ids) || empty($object_id) || $object_type !== 'product') {
        return;
    }

    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('images_links', $object_id);
}

function fn_master_products_delete_image_pair($pair_id, $object_type, $image)
{
    if (empty($image) || empty($image['object_id']) || $image['object_type'] !== 'product') {
        return;
    }

    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('images_links', $image['object_id']);
}

function fn_product_variations_master_products_create_vendor_product($master_product_id, $company_id, $product, $vendor_product_id)
{
    $group_repository = VariationsServiceProvider::getGroupRepository();
    $variation_service = VariationsServiceProvider::getService();
    $product_repository = ServiceProvider::getProductRepository();

    $master_product_group = $group_repository->findGroupInfoByProductId($master_product_id);

    if (empty($master_product_group)) {
        return;
    }

    $product_ids = $group_repository->findGroupProductIdsByGroupIds([$master_product_group['id']]);
    $vendor_product_ids = $product_repository->findVendorProductIdsByMasterProductIds($product_ids, $company_id);

    if (empty($vendor_product_ids)) {
        return;
    }

    $group_ids = $group_repository->findGroupIdsByProductIds($vendor_product_ids);

    if (empty($group_ids)) {
        $group_id = null;
    } else {
        $group_id = reset($group_ids);
    }

    if ($group_id !== null) {
        $variation_service->attachProductsToGroup($group_id, [$vendor_product_id]);
    } else {
        $variation_service->createGroup([$vendor_product_id], null, GroupFeatureCollection::createFromFeatureList($master_product_group['feature_collection']));
    }
}