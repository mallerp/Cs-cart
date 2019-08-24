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
use Tygh\Tygh;
use Tygh\Addons\MasterProducts\ServiceProvider;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 * @var string $action
 * @var array  $auth
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'process') {
        $service = ServiceProvider::getService();
        $products_map = db_get_hash_multi_array(
            'SELECT product_id, master_product_id FROM ?:products WHERE master_product_id > 0',
            ['master_product_id', 'product_id', 'product_id']
        );

        foreach ($products_map as $master_product_id => $vendor_product_ids) {
            $service->syncAllData($master_product_id, $vendor_product_ids);
        }
    }

    return [CONTROLLER_STATUS_OK];
}