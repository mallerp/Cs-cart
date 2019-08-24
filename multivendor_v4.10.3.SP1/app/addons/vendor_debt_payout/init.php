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

fn_register_hooks(
    'get_products',
    'get_categories_pre',
    'change_order_status',
    'check_company_permissions',
    'delete_product_pre',
    'delete_category_pre',
    'update_product_pre',
    'dispatch_before_display',
    'get_order_info',
    'catalog_mode_pre_add_to_cart'
);
