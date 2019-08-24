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

/**
 * Fetches vendor products additional data for vendor_products_filling in products block
 *
 * @param array $products All vendor products
 */
function fn_master_products_get_vendor_products(&$products, $params)
{
    list($companies,) = fn_get_companies([
        'company_id' => fn_array_column($products, 'company_id'),
        'extend'     => [
            'product_count'  => 'N',
            'logos'          => true,
            'placement_info' => true,
        ],
    ], Tygh::$app['session']['auth'], count($products));

    $companies = fn_array_combine(fn_array_column($companies, 'company_id'), $companies);

    fn_gather_additional_products_data($products, $params);

    foreach ($products as $i => &$product) {
        $product['company'] = $companies[$product['company_id']];
        $product['is_vendor_products_list_item'] = true;
    }
    unset($product);

    return;
}

/**
 * Fetches current master product id for blocks with vendor_products_filling
 *
 * @param array $block_data
 *
 * @return int
 */
function fn_master_products_blocks_get_current_master_product_id($block_data)
{
    if (
        !isset($block_data['content']['items']['filling'])
        || $block_data['content']['items']['filling'] !== 'master_products.vendor_products_filling'
    ) {
        return 0;
    }

    return isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;
}