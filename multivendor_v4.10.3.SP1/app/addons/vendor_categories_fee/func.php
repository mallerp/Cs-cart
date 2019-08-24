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

use Tygh\Registry;
use Tygh\VendorPayouts;
use Tygh\Models\VendorPlan;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Hook handler: updates category fee values after category main data saved
 *
 * @param array  $category_data Category data
 * @param int    $category_id   Category identifier
 * @param string $lang_code     Two-letter language code
 */
function fn_vendor_categories_fee_update_category_post($category_data, $category_id, $lang_code)
{
    if ($category_id && !empty($category_data['vendor_fee'])) {
        fn_vendor_categories_fee_update_category_fee($category_id, $category_data['vendor_fee']);
    }
}

/**
 * Updates category fee values
 *
 * @param int $category_id Category identifier
 * @param array $fee_data
 *
 * @return mixed
 */
function fn_vendor_categories_fee_update_category_fee($category_id, $fee_data)
{
    $plan_ids = array_keys($fee_data);

    if (empty($plan_ids)) {
        return false;
    }

    fn_vendor_categories_fee_delete_category_plans_fee($category_id, $plan_ids);
    $result = fn_vendor_categories_fee_insert_category_fee($category_id, $fee_data);

    return $result;
}

/**
 * Deletes all fee values for provided plans and category
 *
 * @param int   $category_id Category identifier
 * @param array $plan_ids    Vendor plan identifiers
 *
 * @return mixed|resource
 */
function fn_vendor_categories_fee_delete_category_plans_fee($category_id, $plan_ids)
{
    return db_query('DELETE FROM ?:vendor_categories_fee WHERE category_id = ?i AND plan_id IN (?n)', $category_id, $plan_ids);
}

/**
 * Inserts category fee to database
 *
 * @param int   $category_id Category identifier
 * @param array $fee_data    Category fee data
 *
 * @return bool|mixed|resource
 */
function fn_vendor_categories_fee_insert_category_fee($category_id, $fee_data)
{
    $result = false;
    $data = [];

    foreach ($fee_data as $plan_id => $amounts) {
        $data[] = [
            'plan_id'      => $plan_id,
            'category_id'  => $category_id,
            'percent_fee'  => isset($amounts['percent_fee']) && is_numeric($amounts['percent_fee']) ? $amounts['percent_fee'] : null,
        ];
    }

    if ($data) {
        $result = db_query('INSERT INTO ?:vendor_categories_fee ?m', $data);
    }

    return $result;
}

/**
 * Fetches fee for specified category
 *
 * @param int $category_id Category identifier
 *
 * @return mixed
 */
function fn_vendor_categories_fee_get_category_fee($category_id)
{
    $category_fee = fn_vendor_categories_fee_get_categories_fee((array) $category_id);
    return isset($category_fee[$category_id]) ? $category_fee[$category_id] : [];
}

/**
 * Fetches fee for specified categories
 *
 * @param array[int] $category_ids Category ids
 *
 * @return array
 */
function fn_vendor_categories_fee_get_categories_fee(array $category_ids)
{
    $categories_fee = db_get_hash_multi_array(
        'SELECT plan_id, category_id, percent_fee FROM ?:vendor_categories_fee WHERE category_id IN (?n)',
        ['category_id', 'plan_id'],
        $category_ids
    );

    return $categories_fee;
}

/**
 * Fetches parent category fee (inherit values from first parent up to root category)
 *
 * @param int $category_id Category identifier
 *
 * @return array
 */
function fn_vendor_categories_fee_get_parent_category_fee($category_id)
{
    $result = [];
    $parent_category_ids = fn_vendor_categories_fee_get_parent_category_ids_in_desc_order($category_id);

    if ($parent_category_ids) {
        $categories_fee = fn_vendor_categories_fee_get_categories_fee($parent_category_ids);
    }

    foreach ($parent_category_ids as $parent_id) {
        $parent_fee = isset($categories_fee[$parent_id]) ? $categories_fee[$parent_id] : [];
        $non_null_items = fn_array_filter_recursive($result, function ($item) {
            return isset($item);
        });
        $result = fn_array_merge($parent_fee, $non_null_items);

        if (fn_vendor_categories_fee_has_all_fee_set($result)) {
            break;
        }
    }

    $result = fn_vendor_categories_fee_fill_missing_fee_from_vendor_plans($result);

    return $result;
}

/**
 * Transfers percent commission value from vendor plan to category fee (if not set)
 *
 * @param array $category_fee Category fee
 *
 * @return array
 */
function fn_vendor_categories_fee_fill_missing_fee_from_vendor_plans($category_fee)
{
    $result = $category_fee;
    $vendor_plans = fn_vendor_categories_fee_get_vendor_plans();

    foreach ($vendor_plans as $plan) {
        $plan_id = $plan['plan_id'];

        if (!isset($result[$plan_id]['percent_fee'])) {
            $result[$plan_id]['percent_fee'] = $plan['commission'];
            unset($result[$plan_id]['category_id']);
        }
    }

    return $result;
}

/**
 * @param array $category_fee Category fee
 *
 * @return bool
 */
function fn_vendor_categories_fee_has_all_fee_set($category_fee)
{
    return !empty($category_fee) && empty(fn_vendor_categories_fee_get_missing_fee($category_fee));
}

/**
 * Filters out fee records where all fee values are set (not equal NULL)
 *
 * @param array $category_fee Category fee
 *
 * @return array
 */
function fn_vendor_categories_fee_get_missing_fee($category_fee)
{
    return array_filter($category_fee, function ($fee) {
        return !isset($fee['percent_fee']);
    });
}

/**
 * Fetches parent category ids (the direct parent first)
 *
 * @param int $category_id Category identifier
 *
 * @return array
 */
function fn_vendor_categories_fee_get_parent_category_ids_in_desc_order($category_id)
{
    $parent_category_ids = fn_get_category_ids_with_parent($category_id);
    unset($parent_category_ids[array_search($category_id, $parent_category_ids)]);

    return array_reverse($parent_category_ids);
}

/**
 * Fetches available vendor plans
 *
 * @return array
 */
function fn_vendor_categories_fee_get_vendor_plans()
{
    static $vendor_plans;

    if ($vendor_plans === null) {
        $params = [
            'to_array' => true,
        ];

        $vendor_plans = VendorPlan::model()->findMany($params);
    }

    return $vendor_plans;
}

/**
 * Calculates tax amount that included in product price for calculation method based on subtotal
 *
 * @param array $subtotals Subtotals list for products in order
 * @param array $taxes     Order taxes
 *
 * @return array
 */
function fn_vendor_categories_fee_get_included_product_taxes_based_on_subtotal($subtotals, $taxes)
{
    $cart_ids = array_keys($subtotals);
    $product_taxes = array_fill_keys($cart_ids, 0.0);
    
    foreach ($taxes as $tax) {
        if ($tax['price_includes_tax'] === 'N' || !isset($tax['applies']['P'])) {
            continue;
        }

        $tax_amount = $tax['applies']['P'];
        $subtotals_used_in_tax = array_filter($subtotals, function ($cart_id) use ($tax) {
            return isset($tax['applies']['items']['P'][$cart_id]);
        }, ARRAY_FILTER_USE_KEY);
        
        $tax_subtotal_sum = array_sum($subtotals_used_in_tax);
        foreach ($subtotals_used_in_tax as $cart_id => $subtotal) {
            $subtotal_percent_of_tax_subtotal = ($subtotal * 100) / $tax_subtotal_sum;
            $product_tax = ($subtotal_percent_of_tax_subtotal * $tax_amount) / 100;
            $product_taxes[$cart_id] += round($product_tax, 2);
        }
    }

    return $product_taxes;
}

/**
 * Calculates tax amount that included in product price for calculation method based on unit price
 *
 * @param array $taxes Order taxes
 *
 * @return array
 */
function fn_vendor_categories_fee_get_included_product_taxes_based_on_unit_price($taxes)
{
    $product_taxes = [];
    foreach ($taxes as $tax) {
        if ($tax['price_includes_tax'] === 'N' || !isset($tax['applies'])) {
            continue;
        }

        foreach ($tax['applies'] as $tax_hash => $tax_amount) {
            list($tax_entity, $cart_id) = explode('_', $tax_hash);

            $is_tax_for_product = $tax_entity === 'P';
            if (!$is_tax_for_product) {
                continue;
            }

            $product_taxes[$cart_id] = isset($product_taxes[$cart_id])
                ? $product_taxes[$cart_id] + $tax_amount
                : $tax_amount;
        }
    }

    return $product_taxes;
}

/**
 * Hook handler: calculates and adds categories fee to payout data
 *
 * @param array $order_info   Order information
 * @param array $company_data Company information
 * @param array $payout_data  Payout information
 */
function fn_vendor_categories_fee_vendor_plans_calculate_commission_for_payout_post($order_info, $company_data, &$payout_data)
{
    $products = $products_subtotal = $main_category_ids = [];
    $exclude_taxes_from_calculation = Registry::get('addons.vendor_plans.include_taxes_in_commission') == 'N';

    foreach ($order_info['product_groups'] as $product_group) {

        foreach ($product_group['products'] as $cart_id => $product) {
            $main_category_ids[] = $product['main_category'];

            if (!empty($product['extra']['exclude_from_calculate'])) {
                $price = $discount = 0;
            } else {
                $price = isset($order_info['products'][$cart_id]['original_price'])
                    ? $order_info['products'][$cart_id]['original_price']
                    : $product['price'];
                $discount = isset($product['discount']) ? $product['discount'] : 0;
            }

            $products[$product['product_id']] = [
                'main_category' => $product['main_category'],
                'subtotal'      => ($price - $discount) * $product['amount'],
                'cart_id'       => $cart_id,
            ];

            $products_subtotal[$cart_id] = isset($order_info['products'][$cart_id]['subtotal'])
                ? $order_info['products'][$cart_id]['subtotal']
                :  $price * $product['amount'];
        }
    }

    if ($exclude_taxes_from_calculation) {
        if (Registry::get('settings.Checkout.tax_calculation') == 'subtotal') {
            $product_taxes = fn_vendor_categories_fee_get_included_product_taxes_based_on_subtotal($products_subtotal, $order_info['taxes']);
        } else {
            $product_taxes = fn_vendor_categories_fee_get_included_product_taxes_based_on_unit_price($order_info['taxes']);
        }

        foreach ($product_taxes as $cart_id => $product_tax) {
            $product_id = $order_info['products'][$cart_id]['product_id'];
            $products[$product_id]['subtotal'] -= $product_tax;
        }
    }

    $main_category_ids = array_unique($main_category_ids);
    $main_categories_fee = fn_vendor_categories_fee_get_categories_fee($main_category_ids);
    $parent_categories_fee = [];

    foreach ($main_category_ids as $main_category_id) {

        if (!isset($parent_categories_fee[$main_category_id])
            && (!isset($main_categories_fee[$main_category_id])
                || !fn_vendor_categories_fee_has_all_fee_set($main_categories_fee[$main_category_id]))
        ) {
            $parent_categories_fee[$main_category_id] = fn_vendor_categories_fee_get_parent_category_fee($main_category_id);
        }
    }

    $total = $order_info['total'] - $order_info['payment_surcharge'];
    $tax_amount_included_to_shipping = 0;
    if ($exclude_taxes_from_calculation) {
        $tax_amount_included_to_shipping = fn_vendor_plans_get_tax_amount_included_to_shipping($order_info['taxes']);
        $order_tax_amount = array_sum(array_column($order_info['taxes'], 'tax_subtotal'));
        $total -= $order_tax_amount;
    }
    $shipping_cost = $order_info['shipping_cost'] - $tax_amount_included_to_shipping;
    $total -= $shipping_cost;

    $payouts_history = fn_vendor_categories_fee_get_order_payouts_history($payout_data['order_id']);
    $payout_data = fn_vendor_categories_fee_calculate_payout($total, $payout_data, $products, $main_categories_fee, $parent_categories_fee, $payouts_history);

    if (Registry::get('addons.vendor_plans.include_shipping') == 'Y') {
        $shipping_fee = $shipping_cost * ($payout_data['commission'] / 100);
        $shipping_fee = round($shipping_fee, 2);
        $payout_data['extra']['shipping_fee'] = $shipping_fee;
        $payout_data['commission_amount'] += $shipping_fee;
    }
}

/**
 * Fetches all order's previous payouts
 *
 * @param int $order_id Order identifier
 *
 * @return array
 */
function fn_vendor_categories_fee_get_order_payouts_history($order_id)
{
    list($payouts) = VendorPayouts::instance()->getList([
        'order_id' => $order_id,
        'simple'   => true,
    ]);

    return $payouts;
}

/**
 * Calculates commission for products' categories
 *
 * @param float $order_total           Order total
 * @param array $payout_data           Payout data
 * @param array $products              Prepared products data from order
 * @param array $main_categories_fee   Main categories fee
 * @param array $parent_categories_fee Parent categories fee
 * @param array $payouts_history       Previous payouts
 *
 * @return mixed
 */
function fn_vendor_categories_fee_calculate_payout($order_total, $payout_data, $products, $main_categories_fee, $parent_categories_fee, $payouts_history)
{
    $products_subtotal = array_sum(array_column($products, 'subtotal'));

    foreach ($products as $product_id => $data) {
        $percent_of_product_subtotal = $products[$product_id]['percent_of_product_subtotal'] = ($data['subtotal'] * 100) / $products_subtotal;
        $products[$product_id]['relative_price_from_order_total'] = ($percent_of_product_subtotal * $order_total) / 100;
    }

    $fee = 0.0;
    $plan_id = isset($payout_data['plan_id']) ? $payout_data['plan_id'] : 0;

    foreach ($products as $product_id => $data) {
        $main_category = $data['main_category'];
        $main_category_fee = isset($main_categories_fee[$main_category][$plan_id]) ? $main_categories_fee[$main_category][$plan_id] : [];
        $parent_category_fee = isset($parent_categories_fee[$main_category][$plan_id]) ? $parent_categories_fee[$main_category][$plan_id] : [];
        $percent_fee = 0.0;

        if (isset($main_category_fee['percent_fee'])) {
            $percent_fee = $main_category_fee['percent_fee'];
        } elseif (isset($parent_category_fee['percent_fee'])) {
            $percent_fee = $parent_category_fee['percent_fee'];
        }

        $payout_data['extra']['category_fee'][$product_id]['percent_fee_value'] = $percent_fee;
        $percent_fee_amount = $payout_data['extra']['category_fee'][$product_id]['percent_fee_amount'] = ($data['relative_price_from_order_total'] * $percent_fee) / 100;
        $fee += $percent_fee_amount;
    }

    $previous_payouts_sum = 0.0;

    foreach ($payouts_history as $payout) {
        $prev_category_fee = isset($payout['extra']['category_fee']) ? $payout['extra']['category_fee'] : [];
        $previous_payouts_sum += isset($prev_category_fee['category_fee_amount']) ? (float) $prev_category_fee['category_fee_amount'] : 0.0;
        $previous_payouts_sum += isset($payout['extra']['shipping_fee']) ? (float) $payout['extra']['shipping_fee'] : 0.0;
    }

    $payout_data['extra']['category_fee']['products'] = $products;
    $payout_data['extra']['category_fee']['category_fee_calculated_amount'] = $fee;
    $payout_data['extra']['category_fee']['order_total'] = $order_total;

    $relative_fee = $fee - $previous_payouts_sum;
    $payout_data['extra']['category_fee']['category_fee_amount'] = $relative_fee;

    $payout_data['commission_amount'] -= $payout_data['extra']['percent_commission'];
    $payout_data['commission_amount'] += round($relative_fee, 2);

    return $payout_data;
}