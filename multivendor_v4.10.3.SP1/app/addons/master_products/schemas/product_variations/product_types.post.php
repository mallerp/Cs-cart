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
 * @var array $schema
 */

$schema[PRODUCT_TYPE_VENDOR_PRODUCT_OFFER] = [
    'name'          => __('master_products.product_type.offer'),
    'tabs'          => ['detailed', 'shippings', 'qty_discounts', 'variations'],
    'fields'        => [
        'product_id',
        'prices',
        'amount',
        'status',
        'timestamp',
        'updated_timestamp',
        'lang_code',
        'shippings',
        'weight',
        'shipping_freight',
        'box_height',
        'box_length',
        'box_width',
        'min_items_in_box',
        'max_items_in_box',
        'min_qty',
        'max_qty',
        'qty_step',
        'list_qty_count',
        'free_shipping',
        'product_type',
        'parent_product_id',
        'company_id',
        'master_product_id',
        'master_product_status',
    ],
    'field_aliases' => [
        'detailed_id' => 'detailed_image',
        'image_id'    => 'detailed_image',
        'price'       => 'prices',
        'taxes'       => 'tax_ids',
        'main_pair'   => 'detailed_image',
    ],
    'search_criteria_callback' => function ($table) {
        return sprintf('%s.master_product_id > 0', $table);
    },
    'allow_generate_variations' => false,
];

return $schema;