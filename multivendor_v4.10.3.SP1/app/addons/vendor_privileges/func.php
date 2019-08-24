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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Enum\UserTypes;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Addons\VendorPrivileges\ServiceProvider;

/**
 * Add-on install handler
 */
function fn_vendor_privileges_install()
{
    $usergroup_id = (int) db_get_field('SELECT usergroup_id FROM ?:usergroups WHERE type = ?s LIMIT 1', USERGROUP_TYPE_VENDOR);

    if (empty($usergroup_id)) {
        return;
    }

    $privileges = ServiceProvider::createPrivileges();

    foreach ($privileges->getVendorPrivileges() as $privilege) {
        db_query('INSERT INTO ?:usergroup_privileges ?e', [
            'usergroup_id' => $usergroup_id,
            'privilege'    => $privilege
        ]);
    }

    Settings::instance()->updateValue('default_vendor_usesrgroup', $usergroup_id, 'vendor_privileges');
}

/**
 * Hook handler for adding new user group type
 */
function fn_vendor_privileges_usergroup_types_get_list(&$usergroup_types)
{
    $usergroup_types[USERGROUP_TYPE_VENDOR] = __('vendor');
}

/**
 * Hook handler for adding new user group type
 */
function fn_vendor_privileges_usergroup_types_get_map_user_type(&$map)
{
    $map[UserTypes::VENDOR] = USERGROUP_TYPE_VENDOR;
}

/**
 * Hook handler for filtering out privileges that are not allowed to Vendor user group
 */
function fn_vendor_privileges_get_privileges_post($usergroup, &$privileges)
{
    if (empty($privileges) || $usergroup['type'] !== USERGROUP_TYPE_VENDOR) {
        return;
    }

    $vendor_allowed_privileges = Tygh::$app['addons.vendor_privileges.privileges']->getVendorPrivileges();

    foreach ($privileges as $key => $privilege) {
        if (!in_array($privilege['privilege'], $vendor_allowed_privileges, true)) {
            unset($privileges[$key]);
        }
    }
}

/**
 * Hook handler for checking vendor administrator privilege for editing other users (vendor administrators from the same company)
 */
function fn_vendor_privileges_check_editable_permissions_post($auth, $user_data, &$has_permissions)
{
    if (!$has_permissions) {
        return;
    }

    if ($auth['user_type'] === UserTypes::VENDOR
        && $auth['user_id'] !== $user_data['user_id']
        && !empty($auth['usergroup_ids'])
    ) {
        $has_permissions = fn_check_current_user_access('manage_users');
    }
}

/**
 * Hook handler for adding default user group after creating new vendor administrator
 */
function fn_vendor_privileges_update_profile($action, $user_data, $current_user_data)
{
    if ($action === 'add'
        && $user_data['user_type'] === UserTypes::VENDOR
        && $default_usergroup_id = Registry::get('addons.vendor_privileges.default_vendor_usesrgroup')
    ) {
        fn_change_usergroup_status('A', $user_data['user_id'], $default_usergroup_id);
    }
}

/**
 * Hook handler
 * Allows privileges for the vendor usergroups
 */
function fn_vendor_privileges_check_can_usergroup_have_privileges_post($usergroup, &$result)
{
    if ($result) {
        return;
    }

    $result = $usergroup['type'] === USERGROUP_TYPE_VENDOR;
}

/**
 * Hook handler: adds vendor usergroups for the payment configuration page.
 */
function fn_vendor_privileges_get_payment_usergroups(&$params, $lang_code)
{
    if (isset($params['type'])) {
        $params['type'][] = USERGROUP_TYPE_VENDOR;
    }
}

/**
 * Hook handler: adds vendor usergroups for logged in user.
 */
function fn_vendor_privileges_define_usergroups($user_data, $area, &$usergroup_types)
{
    if (isset($user_data['user_type']) && $user_data['user_type'] === 'V') {
        $usergroup_types[] = USERGROUP_TYPE_VENDOR;
    }
}
