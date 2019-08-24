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
 * 'copyright.txt' FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Addons\MasterProducts\Product\Repository;

/**
 * Updates mater product status for vendor products
 *
 * @param int   $product_id
 * @param int[] $destination_product_ids
 */
function fn_master_products_sync_product_status($product_id, $destination_product_ids)
{
    $query = ServiceProvider::getQueryFactory()->createQuery(
        Repository::TABLE_PRODUCTS,
        ['product_id' => $product_id],
        ['status']
    );

    $status = $query->scalar();

    $query = ServiceProvider::getQueryFactory()->createQuery(
        Repository::TABLE_PRODUCTS,
        ['product_id' => $destination_product_ids]
    );

    $query->update(['master_product_status' => $status]);
}

function fn_master_products_sync_update_products_count($source_product_id, $destination_product_ids, $source_data_list, $update_pk_list, $insert_pk_list, $delete_pk_list)
{
    $category_ids = array_merge(array_column($insert_pk_list, 'category_id'), array_column($delete_pk_list, 'category_id'));

    if ($category_ids) {
        fn_update_product_count($category_ids);
    }
}