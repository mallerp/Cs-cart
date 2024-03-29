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

use Tygh\Addons\MasterProducts\ServiceProvider;

Tygh::$app->register(new ServiceProvider());

fn_register_hooks(
    // general products list management
    'get_products_pre',
    'get_products',
    'get_product_data',
    'get_product_data_post',
    'gather_additional_products_data_params',
    'gather_additional_products_data_post',
    'load_products_extra_data_pre',

    // administration panel products management
    'company_products_check',
    'is_product_company_condition_required_post',

    // product update routine
    'update_product_post',
    'update_product_categories_pre',
    'update_product_categories_post',
    'add_global_option_link_post',
    'delete_global_option_link_post',
    'update_product_features_value_post',
    'variation_group_save_group',
    'clone_product_data',
    'variation_group_create_products_by_combinations_item',
    'variation_sync_flush_sync_events',
    'update_image_pairs',
    'delete_image_pair',

    // master and vendor products data actualization on products removal/disable
    'delete_product_pre',
    'delete_product_post',
    'tools_change_status',

    'product_type_create_by_product',
    ['get_route', 1950],
    ['url_pre', 1450],

    ['get_attachments_pre', 500],
    ['get_discussion_pre', 500],

    ['master_products_create_vendor_product', '', 'product_variations']
);
