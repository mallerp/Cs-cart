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

use Tygh\Enum\ProfileTypes;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Languages\Languages;
use Tygh\Models\Company;
use Tygh\Models\VendorPlan;
use Tygh\Registry;
use Tygh\VendorPayouts;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_vendor_plans_install()
{
    // vendor_payouts table. These fields shouldn't remove: They are used by vendor_commission
    $fields = fn_get_table_fields('vendor_payouts');
    if (!in_array('commission_amount', $fields)) {
        db_query("ALTER TABLE ?:vendor_payouts ADD `commission_amount` decimal(12,2) NOT NULL default '0'");
    }
    if (!in_array('commission', $fields)) {
        db_query("ALTER TABLE ?:vendor_payouts ADD `commission` decimal(12,2) NOT NULL default '0'");
    }
    if (!in_array('commission_type', $fields)) {
        db_query("ALTER TABLE ?:vendor_payouts ADD `commission_type` char(1) NOT NULL default 'A'");
    }

    // import data exported from the vendor commission add-on
    $vendors_demo = Registry::get('config.dir.addons') . 'vendor_plans/database/demo_vendors.sql';
    if (file_exists($vendors_demo)) {
        db_import_sql_file($vendors_demo, 16348, false, false);
        fn_rm($vendors_demo);
    }

    db_query("REPLACE INTO ?:privileges (privilege, is_default, section_id, group_id, is_view) VALUES ('view_vendor_plans', 'Y', 'vendors', 'vendor_plans', 'Y')");
    db_query("REPLACE INTO ?:privileges (privilege, is_default, section_id, group_id, is_view) VALUES ('manage_vendor_plans', 'Y', 'vendors', 'vendor_plans', 'N')");

    // create new profile field type
    $field = array(
        'field_name'        => 'plan_id',
        'profile_show'      => 'Y',
        'profile_required'  => 'N',
        'checkout_show'     => 'N',
        'checkout_required' => 'N',
        'partner_show'      => 'N',
        'partner_required'  => 'N',
        'field_type'        => PROFILE_FIELD_TYPE_VENDOR_PLAN,
        'profile_type'      => ProfileTypes::CODE_SELLER,
        'position'          => 15,
        'is_default'        => 'Y',
        'section'           => 'C',
        'matching_id'       => 0,
        'class'             => 'plan-id',
        'autocomplete_type' => '',
        'description'       => __('vendor_plans.plan'),
    );

    $field_id = fn_update_profile_field($field, 0);

    if ($field_id) {
        $languages = Languages::getAvailable([
            'area'           => 'A',
            'include_hidden' => true,
        ]);

        foreach ($languages as $code => $lang) {
            fn_update_profile_field(array(
                'description' => __('vendor_plans.plan', array(), $code),
            ), $field_id, $code);
        }
    }
}

function fn_vendor_plans_uninstall()
{
    db_query("DELETE FROM ?:privileges WHERE privilege IN (?a)", array('view_vendor_plans', 'manage_vendor_plans'));
    $plan_field_id = db_get_field('SELECT field_id FROM ?:profile_fields WHERE profile_type = ?s AND field_name = ?s',
        ProfileTypes::CODE_SELLER,
        'plan_id'
    );

    if ($plan_field_id) {
        fn_delete_profile_field($plan_field_id);
    }
}

function fn_vendor_plans_get_companies(&$params, &$fields, &$sortings, &$condition, &$join, &$auth, &$lang_code, &$group)
{
    $fields[] = '?:vendor_plan_descriptions.plan';
    $sortings['plan'] = '?:vendor_plan_descriptions.plan';
    $join .= db_quote(
        ' LEFT JOIN ?:vendor_plan_descriptions'
        . ' ON ?:companies.plan_id = ?:vendor_plan_descriptions.plan_id'
        . ' AND ?:vendor_plan_descriptions.lang_code = ?s',
        $lang_code
    );
    if (!empty($params['plan_id'])) {
        $condition .= db_quote(' AND ?:companies.plan_id IN (?n)', (array)$params['plan_id']);
    }
}

function fn_vendor_plans_get_company_data(&$company_id, &$lang_code, &$extra, &$fields, &$join, &$condition)
{
    $fields[] = '?:vendor_plan_descriptions.plan';
    $join .= db_quote(
        ' LEFT JOIN ?:vendor_plan_descriptions'
        . ' ON companies.plan_id = ?:vendor_plan_descriptions.plan_id'
        . ' AND ?:vendor_plan_descriptions.lang_code = ?s',
        $lang_code
    );
}

function fn_vendor_plans_update_company_pre(&$company_data, &$company_id, &$lang_code, &$can_update)
{
    // Getting current plan
    $company_data['current_plan'] = 0;
    if ($company_id) {
        $curent_data = db_get_row("SELECT plan_id, status FROM ?:companies WHERE company_id = ?i", $company_id);
        $company_data['current_plan'] = $curent_data['plan_id'];
        if (empty($company_data['status'])) {
            $company_data['status'] = $curent_data['status'];
        }
        if (empty($company_data['plan_id'])) {
            $company_data['plan_id'] = $company_data['current_plan'];
        }
    }

    // Check plan availability
    if (!empty($company_data['plan_id'])) {
        $selected_plan = VendorPlan::model()->find($company_data['plan_id'], array(
            'allowed_for_company_id' => $company_id
        ));
        if (!$selected_plan) {
            $company_data['plan_id'] = $company_data['current_plan'] ?: 0;
        }
    }

    // Set default plan
    if (empty($company_data['plan_id']) && empty($company_data['current_plan'])) {
        $default_plan = VendorPlan::model()->find(array(
            'is_default' => 1,
            'allowed_for_company_id' => $company_id,
        ));
        if ($default_plan) {
            $company_data['plan_id'] = $default_plan->plan_id;
        }
    }

    // Check params availability
    if (
        Registry::get('runtime.company_id')
        && !empty($company_data['plan_id'])
        && $company_data['plan_id'] != $company_data['current_plan']
    ) {
        $plan = VendorPlan::model()->find($company_data['plan_id'], array(
            'allowed_for_company_id' => $company_id,
            'check_availability' => true,
        ));
        if (!empty($plan->avail_errors) || Registry::ifGet('addons.vendor_plans.allow_vendors_to_change_plan', 'N') == 'N') {
            fn_set_notification('E', __('error'), __('vendor_plans.plan_not_available_text'));
            $can_update = false;
        }
    }
}

function fn_vendor_plans_update_company(&$company_data, &$company_id, &$lang_code, &$action)
{
    if (
        isset($company_data['plan_id'])
        && isset($company_data['current_plan'])
        && $company_data['plan_id'] != $company_data['current_plan']
        && $company_data['status'] != VendorStatuses::NEW_ACCOUNT
    ) {
        $company = Company::model()->find($company_id);
        /** @var \Tygh\Mailer\Mailer $mailer */
        $mailer = Tygh::$app['mailer'];

        $mailer->send(array(
            'to' => $company->getEmail('company_support_department') ?: 'company_support_department',
            'from' => 'default_company_support_department',
            'data' => array(
                'vendor' => $company,
                'plan' => $company->plan,
            ),
            'template_code' => 'vendor_plans_plan_changed',
            'tpl' => 'addons/vendor_plans/companies/plan_changed.tpl',
        ), 'A', $company->lang_code);

        $current_plan = VendorPlan::model()->find($company_data['current_plan']);
        $new_plan = VendorPlan::model()->find($company_data['plan_id']);

        if (!empty($company_data['remove_vendor_from_old_storefronts'])) {
            /** @var \Tygh\Storefront\Repository $storefront_repository */
            $storefront_repository = Tygh::$app['storefront.repository'];
            $storefront_repository->removeCompaniesFromStorefronts($company_id, $current_plan->storefront_ids);
        }

        if (!empty($company_data['add_vendor_to_new_storefronts'])) {
            /** @var \Tygh\Storefront\Repository $storefront_repository */
            $storefront_repository = Tygh::$app['storefront.repository'];
            $storefront_repository->addCompaniesToStorefronts($company_id, $new_plan->storefront_ids);
        }

        if ($company_data['status'] == VendorStatuses::ACTIVE) {
            $company->payment();
        }
    }
}

function fn_vendor_plans_change_company_status_before_mail(&$company_id, &$status_to, &$reason, &$status_from, &$skip_query, &$notify, &$company_data, &$user_data)
{
    $company = Company::model()->find($company_id);
    $user_data['plan'] = $company->plan; // Need for email notifications
    if ($status_from != VendorStatuses::ACTIVE && $status_to == VendorStatuses::ACTIVE) {
        $company->initialPayment();
    }
    if ($status_from == VendorStatuses::NEW_ACCOUNT && ($status_to == VendorStatuses::ACTIVE || $status_to == VendorStatuses::PENDING)) {
        /** @var \Tygh\Storefront\Repository $storefront_repository */
        $storefront_repository = Tygh::$app['storefront.repository'];
        $storefront_repository->addCompaniesToStorefronts($company_id, $company->storefront_ids);
    }
}

function fn_vendor_plans_delete_category_after(&$category_id)
{
    db_query("UPDATE ?:vendor_plans SET categories = ?p", fn_remove_from_set('categories', $category_id));
}

function fn_vendor_plans_storefront_repository_delete_post($storefront, $operation_result)
{
    db_query("UPDATE ?:vendor_plans SET storefronts = ?p", fn_remove_from_set('storefronts', $storefront->storefront_id));
}

/**
 * Hook handler: adds commission values based on the order totals when the order is placed.
 *
 * @param array  $order_info   Order infromation from ::fn_get_order_info()
 * @param array  $company_data Company data the order belongs to
 * @param string $action       Performed action: '' when editing the order, 'save' when saving
 * @param string $order_status Order status
 * @param array  $cart         Cart contents and user information necessary for purchase
 * @param array  $data         Payout data to be stored in the DB
 * @param int    $payout_id    Payout ID
 * @param array  $auth         User authentication data (e.g. uid, usergroup_ids, etc.)
 */
function fn_vendor_plans_mve_place_order(&$order_info, &$company_data, &$action, &$order_status, &$cart, &$data, &$payout_id, &$auth)
{
    $data = fn_calculate_commission_for_payout($order_info, $company_data, $data);
}

/**
 * Hook handler: adds commission values based on the difference between the order totals when the order is changed.
 *
 * @param array $new_order_info New order information from ::fn_get_order_info()
 * @param array $order_id       Order ID
 * @param array $old_order_info Old order information from ::fn_get_order_info()
 * @param array $company_data   Company data the order belongs to
 * @param int   $payout_id      Existing payout ID
 * @param array $payout_data    Payout data to be stored in the DB
 */
function fn_vendor_plans_mve_update_order($new_order_info, $order_id, $old_order_info, $company_data, $payout_id, &$payout_data)
{
    if (empty($payout_data)) {
        return;
    }

    $new_order_tax_amount = $old_order_tax_amount = 0;
    $new_order_tax_amount_included_to_shipping = $old_order_tax_amount_included_to_shipping = 0;
    $new_order_shipping_cost = $old_order_shipping_cost = 0;

    if (Registry::get('addons.vendor_plans.include_taxes_in_commission') == 'N') {
        $new_order_tax_amount = array_sum(array_column($new_order_info['taxes'], 'tax_subtotal'));
        $old_order_tax_amount = array_sum(array_column($old_order_info['taxes'], 'tax_subtotal'));
        $new_order_tax_amount_included_to_shipping = fn_vendor_plans_get_tax_amount_included_to_shipping($new_order_info['taxes']);
        $old_order_tax_amount_included_to_shipping = fn_vendor_plans_get_tax_amount_included_to_shipping($old_order_info['taxes']);
    }
    if (Registry::get('addons.vendor_plans.include_shipping') == 'N') {
        $new_order_shipping_cost = $new_order_info['shipping_cost'] - $new_order_tax_amount_included_to_shipping;
        $old_order_shipping_cost = $old_order_info['shipping_cost'] - $old_order_tax_amount_included_to_shipping;
    }

    $payout_data['order_amount'] =
        ($new_order_info['total'] - ($new_order_shipping_cost + $new_order_tax_amount)) -
        ($old_order_info['total'] - ($old_order_shipping_cost + $old_order_tax_amount));

    $payout_data = fn_calculate_commission_for_payout($new_order_info, $company_data, $payout_data);
}

function fn_vendor_plans_mve_place_order_post(&$order_id, &$action, &$order_status, &$cart, &$auth, &$order_info, &$company_data, &$data, &$payout_id)
{
    if ($order_info['is_parent_order'] != 'Y' && !empty($order_info['company_id'])) {
        if ($company = Company::model()->find($order_info['company_id'])) {
            $company->canGetRevenue(true);
        }
    }
}

/**
 * Hook handler: excludes the commission amount from a transaction value for a vendor
 * and sets a transaction value to the commission amount for an admin.
 *
 * @param VendorPayouts $instance       VendorPayouts instance
 * @param array         $params         Search parameters
 * @param int           $items_per_page Items per page
 * @param array         $fields         SQL query fields
 * @param string        $join           JOIN statement
 * @param string        $condition      General condition to filter payouts
 * @param string        $date_condition Additional condition to filter payouts by date
 * @param string        $sorting        ORDER BY statemet
 * @param string        $limit          LIMIT statement
 */
function fn_vendor_plans_vendor_payouts_get_list(&$instance, &$params, &$items_per_page, &$fields, &$join, &$condition, &$date_condition, &$sorting, &$limit)
{
    if ($instance->getVendor()) {
        $fields['payout_amount'] = 'CASE WHEN payouts.order_id <> 0 THEN payouts.order_amount - payouts.commission_amount ELSE payouts.payout_amount END';
    } else {
        $fields['payout_amount'] = 'CASE WHEN payouts.order_id <> 0 THEN payouts.commission_amount                        ELSE payouts.payout_amount END';
    }
}

/**
 * Hook handler: excludes commission from vendor income and sets admin income as a sum of commissions.
 *
 * @param VendorPayouts $instance       VendorPayouts instance
 * @param array         $params         Search parameters
 * @param array         $fields         SQL query fields
 * @param string        $join           JOIN statement
 * @param string        $condition      General condition to filter payouts
 * @param string        $date_condition Additional condition to filter payouts by date
 */
function fn_vendor_plans_vendor_payouts_get_income(&$instance, &$params, &$fields, &$join, &$condition, &$date_condition)
{
    if ($instance->getVendor()) {
        $fields['orders_summary'] = 'SUM(payouts.order_amount) - SUM(payouts.commission_amount)';
    } else {
        $fields['orders_summary'] = 'SUM(payouts.commission_amount)';
    }
}

function fn_vendor_plans_get_categories(&$params, &$join, &$condition, &$fields, &$group_by, &$sortings, &$lang_code)
{
    if (!empty($params['ignore_company_condition'])) {
        return;
    }

    if (Registry::get('runtime.company_id')) {
        $company_id = Registry::get('runtime.company_id');
    } elseif (!empty($params['company_ids'])) {
        $company_id = (int) $params['company_ids'];
    }

    if (!empty($company_id)) {
        $plan = VendorPlan::model()->find(array('company_id' => $company_id));
        if ($plan && $plan->category_ids) {

            // This workaround is required when vendor has restricted categories, and total categories number
            // is below the CATEGORY_THRESHOLD, so vendor cannot see allowed categories in the picker
            // Here we add parent categories into the conditions, so vendor could navigate them from the root category
            // up to the allowed one
            if ($params['visible'] == true && empty($params['b_id'])) {
                $category_ids = fn_get_category_ids_with_parent($plan->category_ids);
                $condition .= db_quote(' AND ?:categories.category_id IN (?n)', $category_ids);

                Registry::set('runtime.vendor_plans_company_category_ids', $plan->category_ids);
            } else {
                $company_condition = db_quote(' AND ?:categories.category_id IN (?n)', $plan->category_ids);
                Registry::set('runtime.vendor_plans_company_condition', $company_condition);
                $condition .= $company_condition;
            }
        }
    }
}

function fn_vendor_plans_get_categories_after_sql(&$categories, &$params, &$join, &$condition, &$fields, &$group_by, &$sortings, &$sorting, &$limit, &$lang_code)
{
    // If we search by category name we do not need to change categories array
    if (isset($params['search_query'])) {
        Registry::del('runtime.vendor_plans_company_category_ids');
        Registry::del('runtime.vendor_plans_company_condition');
    } elseif ($category_ids = Registry::get('runtime.vendor_plans_company_category_ids')) {
        Registry::del('runtime.vendor_plans_company_category_ids');

        foreach ($categories as &$category) {
            if (!in_array($category['category_id'], $category_ids)) {
                $category['disabled'] = true;
            }
        }

        unset($category);
    } elseif ($company_condition = Registry::get('runtime.vendor_plans_company_condition')) {
        // we can't build the correct tree for vendors if there are not available parent categories
        Registry::del('runtime.vendor_plans_company_condition');
        $selected_ids = array_keys($categories);
        // so get skipped parent categories ids
        $parent_ids = array();
        foreach ($categories as $v) {
            if ($v['parent_id'] && !in_array($v['parent_id'], $selected_ids)) {
                $parent_ids = array_merge($parent_ids, explode('/', $v['id_path']));
            }
        }
        if ($parent_ids) {
            $_condition = str_replace($company_condition, '', $condition);
            $_condition .= db_quote(' AND ?:categories.category_id IN (?a)', array_unique($parent_ids));
            $fields[] = '1 as disabled'; //mark such categories as disabled
            $parent_categories = db_get_hash_array(
                "SELECT " . implode(',', $fields)
                . " FROM ?:categories"
                . " LEFT JOIN ?:category_descriptions"
                . "  ON ?:categories.category_id = ?:category_descriptions.category_id"
                . "  AND ?:category_descriptions.lang_code = ?s $join"
                . " WHERE 1 ?p $group_by $sorting ?p",
                'category_id', $lang_code, $_condition, $limit
            );
            $categories = $categories + $parent_categories;
        }
    }
}

function fn_vendor_plans_get_category_data(&$category_id, &$field_list, &$join, &$lang_code, &$conditions)
{
    if ($company_id = Registry::get('runtime.company_id')) {
        $plan = VendorPlan::model()->find(array('company_id' => $company_id));
        if ($plan && $plan->category_ids) {
            $conditions .= db_quote(" AND ?:categories.category_id IN(?n)", $plan->category_ids);
        }
    }
}

function fn_vendor_plans_set_admin_notification(&$user_data)
{
    Tygh::$app['session']['vendor_plans_payments'] = true;
}

function fn_vendor_plans_dispatch_before_display()
{
    if (!empty(Tygh::$app['session']['vendor_plans_payments'])) {
        unset(Tygh::$app['session']['vendor_plans_payments']);
        Tygh::$app['view']->assign('vendor_plans_payments', true);
    }
}

function fn_vendor_plans_update_product_pre(&$product_data, &$product_id, &$lang_code, &$can_update)
{
    if ($can_update) {

        $company_id = Registry::get('runtime.company_id');
        if (!$company_id) {
            if (isset($product_data['company_id'])) {
                $company_id = $product_data['company_id'];
            } else {
                $company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
            }
        }

        if ($company_id) {

            $company = Company::model()->find($company_id);
            if (!$product_id && !$company->canAddProduct(true)) {
                $can_update = false;
            }

            if ($company->category_ids) {
                if (
                    !empty($product_data['main_category'])
                    && !in_array($product_data['main_category'], $company->category_ids)
                ) {
                    unset($product_data['main_category']);
                }
                if (empty($product_data['category_ids'])) {
                    $product_data['category_ids'] = db_get_fields(
                        "SELECT category_id FROM ?:products_categories WHERE product_id = ?i", $product_id
                    );
                }
                $product_data['category_ids'] = array_intersect($product_data['category_ids'], $company->category_ids);
                if (empty($product_data['category_ids'])) {
                    $can_update = false;
                }
                if (!$can_update) {
                    fn_set_notification('E', __('error'), __('vendor_plans.category_is_empty'));
                }
            }
        }

    }
}

// Exim

function fn_vendor_plans_import_check_object_id($primary_object_id, &$processed_data, &$skip_record)
{
    $company = Company::current();
    if ($company && empty($primary_object_id) && !$company->canAddProduct(true)) {
        $skip_record = true;
        $processed_data['S'] ++;
    }
}

/**
 * Calculates commission based on payout.
 *
 * @param array $order_info   Order information
 * @param array $company_data Company to which order belongs to
 * @param array $payout_data  Payout data to be written to database
 *
 * @return array Payout data with calculated commission
 */
function fn_calculate_commission_for_payout($order_info, $company_data, $payout_data)
{
    if (
        $payout_data
        && $company_data['plan_id']
        && ($plan = VendorPlan::model()->find($company_data['plan_id']))
    ) {
        $commission = $order_info['total'] > 0 ? $plan->commission : 0;
        $fixed_commission = $order_info['total'] > 0 ? $plan->fixed_commission : 0;
        $total = $order_info['total'];
        $shipping_cost = 0;
        $taxes = 0;

        if ($payout_data['payout_type'] == VendorPayoutTypes::ORDER_CHANGED
            || $payout_data['payout_type'] == VendorPayoutTypes::ORDER_REFUNDED
        ) {
            // Commission is calculated as the difference between orders
            $total = $payout_data['order_amount'];
            $fixed_commission = 0;
        } else {
            $tax_amount_included_to_shipping = 0;
            if (Registry::get('addons.vendor_plans.include_taxes_in_commission') == 'N') {
                $tax_amount_included_to_shipping = fn_vendor_plans_get_tax_amount_included_to_shipping($order_info['taxes']);
                $taxes = array_sum(array_column($order_info['taxes'], 'tax_subtotal'));
            }
            if (Registry::get('addons.vendor_plans.include_shipping') == 'N') {
                // Calculate commission amount and check if we need to include shipping cost
                $shipping_cost = ($order_info['shipping_cost'] - $tax_amount_included_to_shipping);
            }
        }

        $surcharge_from_total = $surcharge_to_commission = $order_info['payment_surcharge'];

        /**
         * This hook is executed before the commission amount was calculated for a payout.
         * Allows to modify the values that payout calculation is based on.
         *
         * @param array $order_info              Order information
         * @param array $company_data            Company to which order belongs to
         * @param array $payout_data             Payout data to be written to database
         * @param float $total                   Order total amount
         * @param float $shipping_cost           Order shipping cost amount
         * @param float $surcharge_from_total    Order payment surcharge to be subtracted from total
         * @param float $surcharge_to_commission Order payment surcharge to be added to commission amount
         * @param float $commission              The transaction percent value
         */
        fn_set_hook('vendor_plans_calculate_commission_for_payout_before', $order_info, $company_data, $payout_data, $total, $shipping_cost, $surcharge_from_total, $surcharge_to_commission, $commission);

        // Calculate commission excluding payment surcharge
        $percent_commission = ($total - $shipping_cost - $surcharge_from_total - $taxes) * $commission / 100;
        $percent_commission = round($percent_commission, 2);
        $commission_amount = $percent_commission + $fixed_commission + $surcharge_to_commission; // Payment surcharge has always go to the admin

        if (abs($commission_amount) > abs($total)) {
            $commission_amount = $total;
        }

        $payout_data['commission'] = $commission;
        $payout_data['commission_amount'] = $commission_amount;
        $payout_data['commission_type'] = 'P'; // Backward compatibility
        $payout_data['plan_id'] = $company_data['plan_id'];
        $payout_data['extra'] = [
            'commission_amount'       => $commission_amount,
            'percent_commission'      => $percent_commission,
            'surcharge_to_commission' => $surcharge_to_commission,
            'shipping_cost'           => $shipping_cost,
            'taxes'                   => $taxes,
            'fixed_commission'        => $fixed_commission,
            'total'                   => $total,
            'commission'              => $commission,
        ];

        /**
         * This hook is executed after the commission amount was calculated for a payout.
         * Allows to modify payout data.
         *
         * @param array $order_info   Order information
         * @param array $company_data Company to which order belongs to
         * @param array $payout_data  Payout data to be written to database
         */
        fn_set_hook('vendor_plans_calculate_commission_for_payout_post', $order_info, $company_data, $payout_data);
    }

    return $payout_data;
}

/**
 * Calculates tax amount that already included to shipping price
 *
 * @param array $taxes Order taxes
 *
 * @return mixed
 */
function fn_vendor_plans_get_tax_amount_included_to_shipping($taxes)
{
    $calculate_by_subtotal = Registry::get('settings.Checkout.tax_calculation') == 'subtotal';
    return array_reduce($taxes, function ($tax_amount, $tax) use ($calculate_by_subtotal) {
        if ($tax['price_includes_tax'] === 'Y') {
            if ($calculate_by_subtotal) {
                $amount = isset($tax['applies']['S']) ? $tax['applies']['S'] : 0;
            } else {
                $amount = 0;
                foreach ($tax['applies'] as $hash => $amt) {
                    list($code) = explode('_', $hash);
                    $is_shipping_tax = $code === 'S';
                    if ($is_shipping_tax) {
                        $amount = $amt;
                        break;
                    }
                }
            }
            return $tax_amount + $amount;
        }
        return $tax_amount;
    }, 0);
}

/**
 * Hook handler: adds commission values to refunds performed via RMA add-on.
 *
 * @param array $data        Request parameters
 * @param array $order_info  Order information from ::fn_get_orders()
 * @param array $return_info Return request from ::fn_get_return_info()
 * @param array $payout_data Payout data to be stored in the DB
 */
function fn_vendor_plans_rma_update_details_create_payout(&$data, &$order_info, &$return_info, &$payout_data)
{
    $company_data = fn_get_company_data($order_info['company_id']);

    $payout_data = fn_calculate_commission_for_payout($order_info, $company_data, $payout_data);
}

/**
 * Hook handler: adds commission values to refunds performed via PayPal add-on.
 *
 * @param int   $order_id    Order ID
 * @param array $data        IPN request parameters
 * @param array $order_info  Order info from ::fn_get_order_info()
 * @param array $payout_data Payout data to be stored in the DB
 */
function fn_vendor_plans_process_paypal_ipn_create_payout(&$order_id, &$data, &$order_info, &$payout_data)
{
    $company_data = fn_get_company_data($order_info['company_id']);

    $payout_data = fn_calculate_commission_for_payout($order_info, $company_data, $payout_data);
}

/**
 * Hook handler: to add vendor plans data to corresponding profile field
 *
 * @param $location
 * @param $_auth
 * @param $lang_code
 * @param $params
 * @param $profile_fields
 * @param $sections
 */
function fn_vendor_plans_get_profile_fields_post($location, $_auth, $lang_code, $params, &$profile_fields, $sections)
{
    static $vendor_plans = null;

    foreach ($profile_fields as $section => &$fields) {

        foreach ($fields as &$field) {

            if ($field['field_type'] != PROFILE_FIELD_TYPE_VENDOR_PLAN) {
                continue;
            }

            if ($vendor_plans === null) {
                $vendor_plans = VendorPlan::model()->findMany(array(
                    'allowed_for_company_id' => Registry::get('runtime.company_id'),
                ));
            }

            $field['plans'] = $vendor_plans;
        }
    }
}

/**
 * Hook handler: prepares extra data before saving to the database
 *
 * @param VendorPayouts $vendor_payouts Class instance
 * @param array         $data           Payout data
 * @param int           $payout_id      Payout identifier
 * @param string        $action         Current action (create or update)
 */
function fn_vendor_plans_vendor_payouts_update($vendor_payouts, &$data, $payout_id, $action)
{
    if (isset($data['extra']) && is_array($data['extra'])) {
        $data['extra'] = json_encode($data['extra']);
    }
}

