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

use Tygh\Enum\Addons\VendorDebtPayout\CategoryTypes;
use Tygh\Enum\Addons\VendorDebtPayout\ProductTypes;
use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Registry;
use Tygh\VendorPayouts;
use Tygh\Tools\Url;

/**
 * Adds add-on data upon its installation.
 */
function fn_vendor_debt_payout_install()
{
    // prevent other add-ons from interrupting the category and the product creation
    define('DISABLE_HOOK_CACHE', true);

    // indicate that add-on is being installed
    fn_define('VENDOR_DEBT_PAYOUT_INSTALL', true);

    $current_hooks = Registry::get('hooks');
    Registry::set('hooks', array(), true);

    $category_id = fn_update_category(
        array(
            'category'             => __('vendor_debt_payout.debt_payout'),
            'parent_id'            => 0,
            'description'          => '',
            'status'               => 'H',
            'page_title'           => '',
            'meta_description'     => '',
            'meta_keywords'        => '',
            'usergroup_ids'        => 0,
            'position'             => '',
            'product_details_view' => 'default',
            'use_custom_templates' => 'N',
            'category_type'        => CategoryTypes::DEBT_PAYOUT,
        )
    );

    // FIXME: find a better way to attach product image
    $_REQUEST['product_main_image_data'] = array(
        array(
            'detailed_alt' => '',
            'type'         => 'M',
            'object_id'    => 0,
            'position'     => 0,

        ),
    );

    $current_allow_external_uploads = Registry::ifGet('runtime.allow_upload_external_paths', false);
    Registry::set('runtime.allow_upload_external_paths', true, true);

    $_REQUEST['file_product_main_image_detailed'] = array(
        fn_get_theme_path('[themes]/[theme]/media/images/addons/vendor_debt_payout/product_image.png'),
    );
    $_REQUEST['type_product_main_image_detailed'] = array(
        'server',
    );

    $product_id = fn_update_product(
        array(
            'product'              => __('vendor_debt_payout.debt_payout'),
            'company_id'           => 0,
            'category_ids'         => array($category_id),
            'main_category'        => $category_id,
            'price'                => 0,
            'full_description'     => '',
            'status'               => 'H',
            'options_type'         => 'P',
            'exceptions_type'      => 'F',
            'product_code'         => '',
            'list_price'           => 0,
            'amount'               => '1',
            'zero_price_action'    => 'R',
            'tracking'             => 'D',
            'min_qty'              => 0,
            'max_qty'              => 0,
            'qty_step'             => 0,
            'list_qty_count'       => 0,
            'tax_ids'              => '',
            'usergroup_ids'        => 0,
            'avail_since'          => '',
            'out_of_stock_actions' => 'N',
            'details_layout'       => 'default',
            'short_description'    => '',
            'search_words'         => '',
            'promo_text'           => '',
            'page_title'           => '',
            'meta_description'     => '',
            'meta_keywords'        => '',
            'weight'               => 0,
            'free_shipping'        => 'Y',
            'shipping_freight'     => 0,
            'min_items_in_box'     => 0,
            'max_items_in_box'     => 0,
            'prices'               => array(
                1 => array(
                    'lower_limit'  => '',
                    'price'        => 0,
                    'type'         => 'A',
                    'usergroup_id' => 0,
                ),
            ),
            'product_features'     => array(),
            'product_type'         => ProductTypes::DEBT_PAYOUT,
            'is_edp'               => 'Y',
        )
    );

    Registry::set('runtime.allow_upload_external_paths', $current_allow_external_uploads, true);
    Registry::set('hooks', $current_hooks, true);
}

/**
 * Uninstalls add-on data upon its removal.
 */
function fn_vendor_debt_payout_uninstall()
{
    // indicate that add-on is being uninstalled
    fn_define('VENDOR_DEBT_PAYOUT_UNINSTALL', true);

    $product_id = fn_vendor_debt_payout_get_payout_product();

    fn_delete_product($product_id);

    $category_id = fn_vendor_debt_payout_get_payout_category();

    fn_delete_category($category_id);
}

/**
 * Obtains ID of the category where the debt payout product is stored.
 *
 * @return int Category ID
 */
function fn_vendor_debt_payout_get_payout_category()
{
    return (int) db_get_field(
        'SELECT category_id'
        . ' FROM ?:categories'
        . ' WHERE category_type = ?s',
        CategoryTypes::DEBT_PAYOUT
    );
}

/**
 * Obtains ID of the debt payout product.
 *
 * @return int Product ID
 */
function fn_vendor_debt_payout_get_payout_product()
{
    return (int) db_get_field(
        'SELECT product_id'
        . ' FROM ?:products'
        . ' WHERE product_type = ?s',
        ProductTypes::DEBT_PAYOUT
    );
}

/**
 * Obtains vendor's root admin or the first admin if root is not found.
 *
 * @param int $vendor_id Vendor ID
 *
 * @return int Admin ID
 */
function fn_vendor_debt_payout_get_vendor_admin($vendor_id)
{
    $query_template = 'SELECT user_id FROM ?:users'
        . ' WHERE ?w'
        . ' ORDER BY user_id ASC'
        . ' LIMIT 1';
    $search_params = array(
        'is_root'    => 'Y',
        'user_type'  => 'V',
        'company_id' => $vendor_id,
    );

    $admin_id = db_get_field($query_template, $search_params);

    if (!$admin_id) {
        unset($search_params['is_root']);

        $admin_id = db_get_field($query_template, $search_params);
    }

    return (int) $admin_id;
}

function fn_vendor_debt_payout_is_vendor_plans_addon_active()
{
    static $is_active;
    if ($is_active === null) {
        $is_active = Registry::ifGet('addons.vendor_plans.status', 'D') === 'A';
    }

    return $is_active;
}

/**
 * Provides "Pay the debt" URL.
 *
 * @param int   $vendor_id Company ID of the vendor
 * @param array $auth      Authentication data from the session
 *
 * @return string URL
 */
function fn_vendor_debt_payout_get_pay_url($vendor_id, array $auth)
{
    if ($auth['user_type'] == 'A') {
        $user_id = fn_vendor_debt_payout_get_vendor_admin($vendor_id);
    } else {
        $user_id = $auth['user_id'];
    }

    $pay_debt_url = Url::buildUrn(array('debt', 'pay'));

    $pay_debt_url = Url::buildUrn(array('profiles', 'act_as_user'), array(
        'area'         => 'C',
        'user_id'      => $user_id,
        'redirect_url' => $pay_debt_url,
    ));

    return fn_url($pay_debt_url);
}

/**
 * Hook handler: removes payouts from the list of products.
 */
function fn_vendor_debt_payout_get_products($params, $fields, $sortings, &$condition)
{
    if (empty($params['pid'])) {
        $condition .= db_quote(' AND products.product_type != ?s', ProductTypes::DEBT_PAYOUT);
    }
}

/**
 * Hook handler: removes payouts from the list of categories.
 */
function fn_vendor_debt_payout_get_categories_pre(&$params, &$lang_code)
{
    if (empty($params['except_id'])) {
        $params['except_id'] = fn_vendor_debt_payout_get_payout_category();
    }
}

/**
 * Hook handler: creates compensating vendor payout when the status of the debt payout order is changed.
 */
function fn_vendor_debt_payout_change_order_status(
    $status_to,
    $status_from,
    $order_info,
    $force_notification,
    $order_statuses,
    $place_order
) {
    if ($place_order
        || $status_from === $status_to
        || $status_from === STATUS_INCOMPLETED_ORDER
        || $order_statuses[$status_to]['params']['inventory'] !== 'D'
        || !empty($order_info['is_debt_paid'])
    ) {
        return;
    }

    foreach ($order_info['products'] as $cart_id => $product) {
        if (!isset($product['extra']['vendor_debt_payout'])) {
            continue;
        }

        $vendor_id = $product['extra']['vendor_debt_payout']['vendor_id'];
        $payouts_manager = VendorPayouts::instance(array('vendor' => $vendor_id));

        $payouts_to_approve = $payouts_manager->getSimple(array(
            'payout_type'     => array(VendorPayoutTypes::PAYOUT),
            'approval_status' => VendorPayoutApprovalStatuses::PENDING,
            'sort_by'         => 'sort_period',
            'sort_order'      => 'desc',
        ));
        foreach ($payouts_to_approve as $payout) {
            $payouts_manager->update(array(
                'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
            ), $payout['payout_id']);
        }

        $payouts_manager->update(array(
            'payout_type'     => VendorPayoutTypes::PAYOUT,
            'payout_amount'   => -$order_info['total'],
            'comments'        => __('vendor_debt_payout.debt_payout_w_order', array('[id]' => $order_info['order_id'])),
            'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
        ));

        // mark order as paid-off
        db_replace_into('order_data', array(
            'order_id' => $order_info['order_id'],
            'type'     => 'D',
            'data'     => serialize(true),
        ));
    }
}

/**
 * Checks whether vendor's negative balance is lower than max allowed vendor debt.
 *
 * @param \Tygh\VendorPayouts $payouts_manager Pre-configured payouts manager
 *
 * @return array Array with the following values:
 *               - Whether debt limit is exceeded
 *               - Vendor balance
 *               - Minimal allowed balance
 */
function fn_vendor_debt_payout_is_debt_limit_exceeded(VendorPayouts $payouts_manager)
{
    list($balance,) = $payouts_manager->getBalance();

    $debt_limit = Registry::get('addons.vendor_debt_payout.vendor_debt_limit');
    if (!is_numeric($debt_limit)) {
        return array(false, $balance, null);
    }

    $minimal_balance = -abs($debt_limit);

    return array($balance < $minimal_balance, $balance, $minimal_balance);
}

/**
 * Checks whether vendor has overdue payouts.
 *
 * @param \Tygh\VendorPayouts $payouts_manager Configured payouts manager
 *
 * @return array
 */
function fn_vendor_debt_payout_has_overdue_payouts(VendorPayouts $payouts_manager)
{
    $pending_payouts = fn_vendor_debt_payout_get_pending_vendor_plan_payouts($payouts_manager);

    $overdue_limit = Registry::get('addons.vendor_debt_payout.payout_overdue_limit');
    if (!is_numeric($overdue_limit)) {
        return array(false, null, null, $pending_payouts);
    }

    foreach ($pending_payouts as $payout_data) {
        $overdue = (int) ceil((TIME - $payout_data['payout_date']) / SECONDS_IN_DAY);
        if ($overdue > $overdue_limit) {
            return array(true, $overdue, $overdue_limit, $pending_payouts);
        }
    }

    return array(false, null, $overdue_limit, $pending_payouts);
}

/**
 * Checks whether vendor can access specified dispatch when his/her debt exceeds allowed limits.
 *
 * @param string $controller Dispatch controller
 * @param string $mode       Dispatch mode
 * @param array  $schema     Permission schema
 *
 * @return bool
 */
function fn_vendor_debt_payout_is_dispatch_allowed_for_blocked_vendor($controller, $mode, array $schema)
{
    if (isset($schema[$controller]['modes'][$mode]['permissions_blocked'])) {
        return $schema[$controller]['modes'][$mode]['permissions_blocked'];
    }

    if (isset($schema[$controller]['permissions_blocked'])) {
        return $schema[$controller]['permissions_blocked'];
    }

    return false;
}

/**
 * Notifies vendor about ongoing block if his/her debt exceeds half of the allowed debt limit.
 *
 * @param int   $vendor_id Company ID of the vendor
 * @param array $auth      User authentication data
 *
 * @return array
 */
function fn_vendor_debt_payout_get_vendor_debt_notifications($vendor_id, array $auth)
{
    $vendor_id = $vendor_id ?: $auth['company_id'];
    if (!$vendor_id) {
        return array();
    }

    $notifications = array();

    $payouts_manager = VendorPayouts::instance(array('vendor' => $vendor_id));
    list(, $balance, $minimal_balance) = fn_vendor_debt_payout_is_debt_limit_exceeded($payouts_manager);
    if ($balance < 0 && $minimal_balance !== null) {
        $notify_threshold = Registry::ifGet('addons.vendor_debt_payout.vendor_debt_limit_notify_threshold', 50) / 100;
        if ($balance > $minimal_balance && $balance < $minimal_balance * $notify_threshold) {

            /** @var \Tygh\Tools\Formatter $formatter */
            $formatter = Tygh::$app['formatter'];

            $textual_replacements = array(
                '[current_balance]' => $formatter->asPrice($balance),
                '[minimal_balance]' => $formatter->asPrice($minimal_balance),
                '[pay_url]'         => fn_vendor_debt_payout_get_pay_url($vendor_id, $auth),
            );

            $notifications['debt_near_limit'] = array(
                'type'   => 'W',
                'title'  => 'warning',
                'text'   => 'vendor_debt_payout.debt_near_limit_message',
                'params' => $textual_replacements,
                'state'  => 'S',
            );
        }
    }

    if (fn_vendor_debt_payout_is_vendor_plans_addon_active()) {

        $overdue_limit = Registry::get('addons.vendor_debt_payout.payout_overdue_limit');

        if (is_numeric($overdue_limit)) {

            $pending_payouts = fn_vendor_debt_payout_get_pending_vendor_plan_payouts($payouts_manager);

            if ($pending_payouts) {

                $overdue_time = ((int) $overdue_limit) * SECONDS_IN_DAY;
                $fee_amount = 0;

                foreach ($pending_payouts as $payout_data) {
                    $fee_amount += $payout_data['payout_amount'];
                }

                /** @var array $payout_data */

                /** @var \Tygh\Tools\Formatter $formatter */
                $formatter = Tygh::$app['formatter'];
                $fee_amount = $formatter->asPrice($fee_amount);
                $balance = $formatter->asPrice($balance);
                $plan = \Tygh\Models\VendorPlan::model()->find($payout_data['plan_id']);
                $overdue_date = $formatter->asDatetime($payout_data['payout_date'] + $overdue_time);

                $textual_replacements = array(
                    '[fee_amount]'      => $fee_amount,
                    '[plan_name]'       => $plan['plan'],
                    '[overdue_date]'    => $overdue_date,
                    '[current_balance]' => $balance,
                    '[pay_url]'         => fn_vendor_debt_payout_get_pay_url($vendor_id, $auth),
                );

                $notifications['pending_payouts'] = array(
                    'type'   => 'W',
                    'title'  => 'warning',
                    'text'   => 'vendor_debt_payout.pending_payout_message',
                    'params' => $textual_replacements,
                    'state'  => 'S',
                );

                unset($notifications['debt_near_limit']);
            }
        }
    }

    return $notifications;
}

/**
 * Obtains pending vendor plan payouts for a vendor.
 *
 * @param \Tygh\VendorPayouts $payouts_manager Pre-configured payouts manager
 *
 * @return array Pending payouts
 */
function fn_vendor_debt_payout_get_pending_vendor_plan_payouts(VendorPayouts $payouts_manager)
{
    $pending_payouts = $payouts_manager->getSimple(array(
        'payout_type'     => array(VendorPayoutTypes::PAYOUT),
        'approval_status' => VendorPayoutApprovalStatuses::PENDING,
        'sort_by'         => 'sort_period',
        'sort_order'      => 'desc',
    ));
    $pending_payouts = array_filter($pending_payouts, function ($payout) {
        return !empty($payout['plan_id']);
    });

    return $pending_payouts;
}

/**
 * Hook handler: prevents vendor access to everything if his/her debt exceeds maximum allowed value.
 *
 * @param bool   $permission        Whether the action is allowed
 * @param string $controller        Dispatch controller
 * @param string $mode              Dispatch mode
 * @param string $request_method    Request method ('GET', 'POST')
 * @param array  $request_variables Request parameters
 * @param string $extra             (Not used) Legacy paramter
 * @param array  $schema            Permission schema
 */
function fn_vendor_debt_payout_check_company_permissions(
    &$permission,
    &$controller,
    &$mode,
    &$request_method,
    &$request_variables,
    &$extra,
    &$schema
) {
    static $is_debt_limit_exceeded;
    static $is_payout_overdue;

    static $vendor_id;
    static $payouts_manager;

    if ($vendor_id === null) {
        $vendor_id = Registry::get('runtime.company_id');
    }

    if (!$permission || !$vendor_id) {
        return;
    }

    if ($is_debt_limit_exceeded === null) {
        $payouts_manager = VendorPayouts::instance(array('vendor' => $vendor_id));

        list($is_debt_limit_exceeded,) = fn_vendor_debt_payout_is_debt_limit_exceeded($payouts_manager);
    }

    if ($is_debt_limit_exceeded === true) {
        $permission = fn_vendor_debt_payout_is_dispatch_allowed_for_blocked_vendor($controller, $mode, $schema);

        return;
    }

    if (!fn_vendor_debt_payout_is_vendor_plans_addon_active()) {
        return;
    }

    if ($is_payout_overdue === null) {
        list($is_payout_overdue,) = fn_vendor_debt_payout_has_overdue_payouts($payouts_manager);
    }

    if ($is_payout_overdue === true) {
        $permission = fn_vendor_debt_payout_is_dispatch_allowed_for_blocked_vendor($controller, $mode, $schema);
    }
}

/**
 * Hook handler: prevents fees product removal.
 *
 * @param int  $product_id Product ID
 * @param bool $status     Whether removal is allowed
 */
function fn_vendor_debt_payout_delete_product_pre(&$product_id, &$status)
{
    if ($product_id == fn_vendor_debt_payout_get_payout_product()
        && !defined('VENDOR_DEBT_PAYOUT_UNINSTALL')
    ) {
        $status = false;
    }
}

/**
 * Hook handler: prevents fees category removal.
 *
 * @param int  $category_id Category ID
 * @param bool $recurse     Whether to remove all nested categories
 */
function fn_vendor_debt_payout_delete_category_pre(&$category_id, &$recurse)
{
    if ($category_id != fn_vendor_debt_payout_get_payout_category()
        || defined('VENDOR_DEBT_PAYOUT_UNINSTALL')
    ) {
        return;
    }

    $category_id = null;
}

/**
 * Hook handler: prevents crucial data modificaiton for fees product.
 *
 * @param array  $product_data Edited product data
 * @param int    $product_id   Product ID
 * @param string $lang_code    Two-letter language code
 * @param bool   $can_update   Whether product can be edited
 */
function fn_vendor_debt_payout_update_product_pre(&$product_data, &$product_id, &$lang_code, &$can_update)
{
    if ($product_id != fn_vendor_debt_payout_get_payout_product()
        || defined('VENDOR_DEBT_PAYOUT_INSTALL')
    ) {
        return;
    }

    $product_data['company_id'] = 0;
    $product_data['category_id'] = array(fn_vendor_debt_payout_get_payout_category());
    $product_data['status'] = 'H';
    $product_data['zero_price_action'] = 'R';
    $product_data['tracking'] = 'D';
    $product_data['out_of_stock_actions'] = 'N';
    $product_data['is_edp'] = 'Y';
    $product_data['product_type'] = ProductTypes::DEBT_PAYOUT;
}

/**
 * Hook handler: removes all tabs but General from fee payout product update page.
 */
function fn_vendor_debt_payout_dispatch_before_display()
{
    $controller = Registry::get('runtime.controller');
    $mode = Registry::get('runtime.mode');

    if (AREA !== 'A' || $controller !== 'products' || $mode !== 'update') {
        return;
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $product_data */
    $product_data = $view->getTemplateVars('product_data');
    if ($product_data['product_type'] !== ProductTypes::DEBT_PAYOUT) {
        return;
    }

    $tabs = Registry::get('navigation.tabs');

    if (isset($tabs['detailed'])) {
        $tabs = array(
            'detailed' => $tabs['detailed'],
        );

        Registry::set('navigation.tabs', $tabs);
    }
}

/**
 * Hook handler: sets order paid-off status.
 *
 * @param array $order           Order info
 * @param array $additional_data Additional order data
 */
function fn_vendor_debt_payout_get_order_info(&$order, &$additional_data)
{
    if (!empty($additional_data['D'])) {
        $order['is_debt_paid'] = unserialize($additional_data['D']);
    }
}
/**
 * Hook handler: allows to skip clearing the cart when the catalog mode is enabled
 *
 * @param array $product_data List of products data
 * @param array $cart         Array of cart content and user information necessary for purchase
 * @param array $auth         Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param bool  $update       Flag, if true that is update mode. Usable for order management
 * @param bool  $can_delete   Flag, if true that is cart cleared. Usable to pay off the vendor debt.
 */
function fn_vendor_debt_payout_catalog_mode_pre_add_to_cart(&$product_data, $cart, $auth, $update, &$can_delete)
{
    foreach ($product_data as $product) {
        if (isset($product['extra'])) {
            foreach ($product['extra'] as $key => $value) {
                if ($key == 'vendor_debt_payout') {
                    $can_delete = false;
                }
            }
        }
    }
}
