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

use Tygh\Addons\GraphqlApi\Type;

$schema = [
    'name'        => 'Category',
    'description' => 'Represents a category',
    'fields'      => [
        'category_id' => [
            'type'        => Type::int(),
            'description' => 'Category ID',
        ],
        'parent_id'   => [
            'type'        => Type::int(),
            'description' => 'Parent category ID',
        ],
        'category'    => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'status'      => [
            'type'        => Type::string(),
            'description' => 'Status',
        ],
    ],
];

return $schema;
