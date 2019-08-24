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

$schema = array(
    'default_permission' => false,
    'controllers' => array (
        'auth' => array (
            'permissions' => true,
        ),
        'index' => array (
            'permissions' => true,
        ),
        'elf_connector' => array (
            'permissions' => true,
        ),

        'profiles' => array (
            'modes' => array (
                'update_cards' => array (
                    'permissions' => false
                ),
                'delete_profile' => array (
                    'permissions' => false
                ),
                'delete_card' => array (
                    'permissions' => false
                ),
                'request_usergroup' => array (
                    'permissions' => false
                ),
                'manage' => array (
                    'param_permissions' => array(
                        'user_type' => array(
                            'P' => false,
                        ),
                        'default_permission' => true,
                    ),
                    'condition' => array(
                        'user_type' => array(
                            'A' => array(
                                'operator' => 'and',
                                'function' => array('fn_check_permission_manage_profiles', 'A'),
                            ),
                            'V' => array(
                                'operator' => 'and',
                                'function' => array('fn_check_permission_manage_profiles', 'V'),
                            ),
                        )
                    ),
                ),
                'view_product_as_user' => array(
                    'permissions' => true,
                ),
                'act_as_user' => array (
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array('fn_check_permission_act_as_user'),
                    ),
                )
            ),
            'permissions' => true,
        ),
        'companies' => array (
            'modes' => array (
                'add' => array (
                    'permissions' => false
                ),
                'invite' => array(
                    'permissions' => false
                ),
                'invitations' => array(
                    'permissions' => false
                ),
                'm_delete_invitations' => array(
                    'permissions' => false,
                ),
                'delete_invitation' => array(
                    'permissions' => false,
                ),
                'delete' => array (
                    'permissions' => false
                ),
                'update_status' => array (
                    'permissions' => false
                ),
                'm_activate' => array (
                    'permissions' => false
                ),
                'm_disable' => array (
                    'permissions' => false
                ),
                'm_delete' => array (
                    'permissions' => false
                ),
                'export_range' => array(
                    'permissions' => false,
                ),
                'update' => array(
                    'permissions' => array('GET' => true, 'POST' => true),
                ),
            ),
            'permissions' => true,
        ),
        'profile_fields' => array (
            /*'modes' => array (
                'manage' => array (
                    'permissions' => true
                ),
            ),*/
            'permissions' => false,
        ),
        'usergroups' => array (
            /*'modes' => array (
                'manage' => array (
                    'permissions' => true
                ),
                'assign_privileges' => array (
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'update_status' => array (
                    'permissions' => true,
                ),
            ),*/
            'permissions' => false,
        ),

        'sales_reports' => array (
            'modes' => array(
                'view' => array (
                    'permissions' => true,
                ),
                'set_report_view' => array (
                    'permissions' => true,
                ),
            ),
            'permissions' => false,
        ),

        'categories' => array (
            'modes' => array (
                'delete' => array (
                    'permissions' => false
                ),
                // Why .add was true ???
                'add' => array (
                    'permissions' => false
                ),
                'm_add' => array (
                    'permissions' => false
                ),
                'm_update' => array (
                    'permissions' => false
                ),
                'picker' => array (
                    'permissions' => true
                ),
            ),
            'permissions' => array ('GET' => true, 'POST' => false),
        ),

        'taxes' => array (
            'modes' => array(
                'update' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'manage' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
            ),
            'permissions' => false,
        ),

        'image' => array (
            'modes' => array(
                'barcode' => array(
                    'permissions' => true,
                ),
                'delete_image' => array(
                    'permissions' => true,
                ),
                'thumbnail' => array(
                    'permissions' => true,
                ),
                'upload' => array(
                    'permissions' => true,
                ),
            ),
            'permissions' => false,
        ),

        'search' => array (
            'modes' => array(
                'results' => array(
                    'permissions' => true,
                ),
            ),
            'permissions' => false,
        ),

        'states' => array (
            'modes' => array(
                'manage' => array(
                    'permissions' => true,
                ),
            ),
            'permissions' => false,
        ),

        'countries' => array (
            'modes' => array(
                'manage' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
            ),
            'permissions' => false,
        ),

        'destinations' => array (
            'modes' => array(
                'update' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'manage' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
            ),
            'permissions' => false,
        ),

        'localizations' => array (
            /*'modes' => array(
                'update' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'manage' => array(
                    'permissions' => true,
                ),
            ),*/
            'permissions' => false,
        ),

        'languages' => array (
            /*'modes' => array(
                'manage' => array(
                    'permissions' => true,
                ),
            ),*/
            'permissions' => false,
        ),

        'product_features' => array (
            'modes' => array(
                'update' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'manage' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'groups' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'get_feature_variants_list' => array(
                    'permissions' => true,
                ),
                'get_variants_list' => array(
                    'permissions' => true,
                ),
                'get_variants' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                )
            ),
            'permissions' => false,
        ),

        'statuses' => array (
            /*'modes' => array(
                'update' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'manage' => array(
                    'permissions' => true,
                ),
            ),*/
            'permissions' => false,
        ),

        'currencies' => array (
            'modes' => array(
                'update' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'manage' => array(
                    'permissions' => true,
                ),
            ),
            'permissions' => false,
        ),
        'exim' => array (
            'modes' => array(
                'export' => array(
                    'param_permissions' => array(
                        'section' => array(
                            'features' => false,
                            'orders' => true,
                            'products' => true,
                            'translations' => false,
                            'users' => false,
                        ),
                    ),
                ),
                'import' => array(
                    'param_permissions' => array(
                        'section' => array(
                            'features' => false,
                            'orders' => false,
                            'products' => true,
                            'translations' => false,
                            'users' => false,
                        ),
                    )
                ),
            ),
            'permissions' => true,
        ),

        'product_filters' => array (
            'modes' => array(
                'update' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'manage' => array(
                    'permissions' => array ('GET' => true, 'POST' => false),
                ),
                'delete' => array(
                    'permissions' => false,
                ),
            ),
            'permissions' => true,
        ),

        'orders' => array (
            'modes' => array(
                'details' => array(
                    'permissions' => true,
                ),
                'delete' => array(
                    'permissions' => false,
                ),
                'delete_orders' => array(
                    'permissions' => false,
                ),
                'manage' => array(
                    'permissions' => true,
                ),
                'export_range' => array(
                    'permissions' => true,
                ),
            ),
            'permissions' => true,
        ),

        'shippings' => array (
            'permissions' => true,
        ),

        'tags' => array (
            'modes' => array(
                'list' => array(
                    'permissions' => true,
                ),
            ),
            'permissions' => false,
        ),

        'pages' => array (
            'modes' => array(
                /*'m_add' => array(
                    'permissions' => false,
                ),
                'm_update' => array(
                    'permissions' => false,
                ),*/
            ),
            'permissions' => true,
        ),

        'products' => array (
            'modes' => array(
            ),
            'permissions' => true,
        ),

        'product_options' => array (
            'permissions' => true,
        ),

        'promotions' => array (
            'permissions' => false,
        ),

        'shipments' => array (
            'permissions' => true,
        ),

        'attachments' => array (
            'permissions' => true,
        ),

        'block_manager' => array (
            'modes' => array()
        ),

        'tools' => array (
            'modes' => array (
                'update_position' => array(
                    'param_permissions' => array (
                        'table' => array (
                            'images_links' => true,
                        )
                    )
                ),
                'update_status' => array (
                    'param_permissions' => array (
                        'table' => array (
                            'shippings' => true,
                            'products' => true,
                            'product_options' => true,
                            'attachments' => true,
                            'product_files' => true,
                            'pages' => true,
                            'shipments' => true,
                            //'users' => true,
                            /*'categories' => 'manage_catalog',
                            'states' => 'manage_locations',
                            'usergroups' => 'manage_usergroups',
                            'currencies' => 'manage_currencies',
                            'blocks' => 'edit_files',
                            'taxes' => 'manage_taxes',
                            'promotions' => 'manage_promotions',
                            'static_data' => 'manage_static_data',
                            'statistics_reports' => 'manage_reports',
                            'countries' => 'manage_locations',

                            'languages' => 'manage_languages',
                            'sitemap_sections' => 'manage_sitemap',
                            'localizations' => 'manage_locations',
                            'products' => 'manage_catalog',
                            'destinations' => 'manage_locations',
                            'product_options' => 'manage_catalog',
                            'product_features' => 'manage_catalog',
                            'payments' => 'manage_payments',
                            'product_filters' => 'manage_catalog',
                            'product_files' => 'manage_catalog'
                            */
                        )
                    )
                ),
                'cleanup_history' => array(
                    'permissions' => true
                ),
                'view_changes' => array(
                    'permissions' => false
                )
            )
        ),
        'logs' => array(
            'permissions' => true,
        ),
        'debugger' => array(
            'permissions' => true,
        ),
        'file_editor' => array(
            'permissions' => true
        ),
        'themes' => array(
            'modes' => array(),
        ),
        'customization' => array(
            'modes' => array(
                'update_mode' => array(
                    'param_permissions' => array(
                        'type' => array(
                            'theme_editor' => array(
                                'permissions' => false,
                                'condition' => array(
                                    'operator' => 'or',
                                    'function' => array('fn_get_styles_owner'),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),

        'notifications' => array(
            'permissions' => true,
        ),

        'notifications_center' => [
            'permissions' => true,
        ],
        'bottom_panel' => array(
            'permissions' => true
        ),
    ),
    'export' => array(
        'sections' => array(
            'translations' => array(
                'permission' => false,
            ),
            'users' => array(
                'permission' => false,
            ),
            'features' => array(
                'permission' => false,
            ),
            'vendors' => array(
                'permission' => false,
            ),
        ),
        'patterns' => array(
            'google' => array(
                'permission' => false,
            ),
        ),
    ),
    'import' => array(
        'sections' => array(
            'translations' => array(
                'permission' => false,
            ),
            'orders' => array(
                'permission' => false,
            ),
            'users' => array(
                'permission' => false,
            ),
            'features' => array(
                'permission' => false,
            ),
            'vendors' => array(
                'permission' => false,
            ),
        ),
        'patterns' => array(),
    ),
);

foreach (array('manage', 'update_block', 'update_status', 'snapping', 'grid', 'block_selection',
             'manage_in_tab', 'set_custom_container', 'update_grid') as $mode) {
    $schema['controllers']['block_manager']['modes'][$mode] = array(
        'permissions' => false,
        'condition' => array(
            'operator' => 'or',
            'function' => array('fn_get_blocks_owner'),
        ),
    );
}

foreach (array('manage', 'styles') as $mode) {
    $schema['controllers']['themes']['modes'][$mode] = array(
        'permissions' => false,
        'condition' => array(
            'operator' => 'or',
            'function' => array('fn_get_styles_owner'),
        ),
    );
}

return $schema;
