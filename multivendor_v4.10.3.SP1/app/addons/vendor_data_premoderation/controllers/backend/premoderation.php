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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($dispatch_extra)) {
        if (!empty($_REQUEST['approval_data'][$dispatch_extra])) {
            $_REQUEST['approval_data'] = $_REQUEST['approval_data'][$dispatch_extra];
        }
    }

    if ($mode == 'products_approval' && !empty($_REQUEST['approval_data'])) {
        $status = Registry::get('runtime.action') == 'approve' ? 'Y' : 'N';
        fn_change_approval_status($_REQUEST['approval_data']['product_id'], $status);

        fn_set_notification('N', __('notice'), __('status_changed'));

        if (
            isset($_REQUEST['approval_data']['notify_user_' . $status])
            && $_REQUEST['approval_data']['notify_user_' . $status] == 'Y'
        ) {
            $lang_code = fn_get_company_language($_REQUEST['approval_data']['company_id']);

            $product_id = $_REQUEST['approval_data']['product_id'];
            $products[$product_id]['product'] = fn_get_product_name($product_id, $lang_code);
            $products[$product_id]['url'] = fn_url('products.update?product_id=' . $product_id, 'V', 'http', $lang_code);
            $company_placement_info = fn_get_company_placement_info($_REQUEST['approval_data']['company_id']);

            /** @var \Tygh\Mailer\Mailer $mailer */
            $mailer = Tygh::$app['mailer'];

            $mailer->send(array(
                'to' => isset($company_placement_info['company_support_department']) ? $company_placement_info['company_support_department'] : 'company_support_department',
                'from' => 'default_company_support_department',
                'data' => array(
                    'products' => $products,
                    'status' => $status,
                    'reason' => $_REQUEST['approval_data']['reason_' . $status]
                ),
                'template_code' => 'vendor_data_premoderation_notification',
                'tpl' => 'addons/vendor_data_premoderation/notification.tpl', // this parameter is obsolete and is used for back compatibility
            ), 'A', $lang_code);
        }

    } elseif (($mode == 'm_approve' || $mode == 'm_decline') && !empty($_REQUEST['product_ids'])) {
        if ($mode == 'm_approve') {
            $status = 'Y';
            $reason = $_REQUEST['action_reason_approved'];
            $send_notification =
                isset($_REQUEST['action_notification_approved']) && $_REQUEST['action_notification_approved'] == 'Y';
        } else {
            $status = 'N';
            $reason = $_REQUEST['action_reason_declined'];
            $send_notification =
                isset($_REQUEST['action_notification_declined']) && $_REQUEST['action_notification_declined'] == 'Y';
        }

        $product_ids = $_REQUEST['product_ids'];

        if ($send_notification) {
            list($products_data) = fn_get_products(array('pid' => $product_ids));

            // Group updated products by companies
            $_companies = array();
            foreach ($products_data as $product) {
                if ($product['approved'] != $status) {
                    $_companies[$product['company_id']]['product_ids'][] = $product['product_id'];
                    if (empty($_companies[$product['company_id']]['lang_code'])) {
                        $_companies[$product['company_id']]['lang_code'] = fn_get_company_language($product['company_id']);
                    }
                }
            }
        }

        fn_change_approval_status($product_ids, $status);

        fn_set_notification('N', __('notice'), __('status_changed'));

        if ($send_notification) {
            /** @var \Tygh\Mailer\Mailer $mailer */
            $mailer = Tygh::$app['mailer'];

            foreach ($_companies as $company_id => $_data) {
                $products = array();
                foreach ($_data['product_ids'] as $product_id) {
                    $products[$product_id]['product'] = fn_get_product_name($product_id, $_data['lang_code']);
                    $products[$product_id]['url'] = fn_url('products.update?product_id=' . $product_id, 'V', 'http', $_data['lang_code']);
                }

                $company_placement_info = fn_get_company_placement_info($company_id);

                $mailer->send(array(
                    'to' => isset($company_placement_info['company_support_department']) ? $company_placement_info['company_support_department'] : 'company_support_department',
                    'from' => 'default_company_support_department',
                    'data' => array(
                        'products' => $products,
                        'status' => $status,
                        'reason' => $reason
                    ),
                    'template_code' => 'vendor_data_premoderation_notification',
                    'tpl' => 'addons/vendor_data_premoderation/notification.tpl', // this parameter is obsolete and is used for back compatibility
                ), 'A', $_data['lang_code']);
            }
        }
    }
}

if ($mode == 'products_approval' && !Registry::get('runtime.company_id')) {
    $params = $_REQUEST;
    $params['extend'][] = 'companies';

    list($products, $search) = fn_get_products(
        $params,
        Registry::get('settings.Appearance.admin_elements_per_page'),
        DESCR_SL
    );

    fn_gather_additional_products_data($products, [
        'get_icon'      => true,
        'get_detailed'  => true,
        'get_options'   => false,
        'get_discounts' => false
    ]);

    Tygh::$app['view']->assign('products', $products);
    Tygh::$app['view']->assign('search', $search);
}
