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


namespace Tygh\Addons\MasterProducts;

use Tygh\Addons\MasterProducts\Product\Repository as ProductRepository;
use Tygh\Addons\ProductVariations\Product\Type\Type as ProductType;
use Tygh\Common\OperationResult;

/**
 * Class Service
 *
 * @package Tygh\Addons\MasterProducts
 */
class Service
{
    /** @var ProductRepository */
    protected $product_repository;

    /** @var callable */
    protected $sync_schema_factory;

    /** @var callable */
    protected $copy_schema_factory;

    /** @var null|\Tygh\Addons\ProductVariations\Product\Sync\ISyncItem[] */
    protected $sync_schema;

    /** @var null|\Tygh\Addons\ProductVariations\Product\Sync\ISyncItem[] */
    protected $copy_schema;

    /**
     * Service constructor.
     *
     * @param \Tygh\Addons\MasterProducts\Product\Repository $product_repository
     * @param callable                                       $sync_schema_factory
     * @param callable                                       $copy_schema_factory
     */
    public function __construct(ProductRepository $product_repository, callable $sync_schema_factory, callable $copy_schema_factory)
    {
        $this->product_repository = $product_repository;
        $this->sync_schema_factory = $sync_schema_factory;
        $this->copy_schema_factory = $copy_schema_factory;
    }

    /**
     * Creates a vendor product from a master product.
     *
     * @param int $master_product_id Master product ID
     * @param int $company_id        Vendor company ID
     *
     * @return \Tygh\Common\OperationResult Operation result with vendor product ID
     */
    public function createVendorProduct($master_product_id, $company_id)
    {
        $result = new OperationResult();

        $product = $this->product_repository->findProduct($master_product_id);

        if (empty($product)) {
            $result->addError('product', 'Product not found');
            return $result;
        }

        $vendor_product_id = $this->product_repository->findVendorProductId($master_product_id, $company_id);

        if ($vendor_product_id) {
            $result->setSuccess(true);
            $result->setData(true, 'vendor_product_exists');
            $result->setData($vendor_product_id, 'vendor_product_id');

            return $result;
        }

        $vendor_product_id = $this->product_repository->createProduct(array_merge($product, [
            'master_product_id'     => $master_product_id,
            'master_product_status' => $product['status'],
            'parent_product_id'     => 0,
            'timestamp'             => time(),
            'updated_timestamp'     => time(),
            'amount'                => 1,
            'product_type'          => ProductType::PRODUCT_TYPE_SIMPLE,
            'company_id'            => $company_id
        ]));

        if (empty($vendor_product_id)) {
            $result->addError('product', 'Product could not created');
            return $result;
        }

        $this->copyAllData($master_product_id, [$vendor_product_id]);
        $this->actualizeMasterProductOffersCount($master_product_id);
        $this->actualizeMasterProductQuantity($master_product_id);

        $result->setSuccess(true);
        $result->setData($vendor_product_id, 'vendor_product_id');

        /**
         * Executes after vendor product created.
         *
         * @param int                          $master_product_id Master product ID
         * @param int                          $company_id        Vendor ID
         * @param array                        $product           Master product data
         * @param int                          $vendor_product_id Vendor product ID
         * @param \Tygh\Common\OperationResult $result            Result of operation
         *
         */
        fn_set_hook('master_products_create_vendor_product', $master_product_id, $company_id, $product, $vendor_product_id, $result);

        return $result;
    }

    /**
     * Sets minimal price of all vendor product prices as the master product price.
     *
     * @param int $product_id Master product ID or Vendor product ID
     */
    public function actualizeMasterProductPrice($product_id)
    {
        $master_product_id = $this->product_repository->findMasterProductId($product_id);

        if (!$master_product_id) {
            $master_product_id = $product_id;
        }

        $vendor_product_ids = $this->product_repository->findVendorProductIds($master_product_id, ['A'], ['A']);

        if (!$vendor_product_ids) {
            return;
        }

        $master_product_price = 0;

        foreach ($vendor_product_ids as $vendor_product_id) {
            $vendor_product_data = [];
            fn_get_product_prices($vendor_product_id, $vendor_product_data, []);

            $vendor_product_price = (float) reset($vendor_product_data['prices'])['price'];
            if (!$master_product_price || $vendor_product_price && $vendor_product_price < $master_product_price) {
                $master_product_price = $vendor_product_price;
            }
        }

        fn_update_product_prices($master_product_id, [
            'price'  => $master_product_price,
        ]);
    }

    /**
     * Updates the master product offers count
     *
     * @param int $product_id Master product ID or Vendor product ID
     */
    public function actualizeMasterProductOffersCount($product_id)
    {
        $master_product_id = $this->product_repository->findMasterProductId($product_id);

        if (!$master_product_id) {
            $master_product_id = $product_id;
        }

        $vendor_products_count = $this->product_repository->getVendorProductsCount($master_product_id, ['A'], ['A']);

        $this->product_repository->updateProduct($master_product_id, [
            'master_product_offers_count' => $vendor_products_count
        ]);
    }

    /**
     * Updates the master product quantity
     *
     * @param int $product_id Master product ID or Vendor product ID
     */
    public function actualizeMasterProductQuantity($product_id)
    {
        $master_product_id = $this->product_repository->findMasterProductId($product_id);

        if (!$master_product_id) {
            $master_product_id = $product_id;
        }

        $quantity = $this->product_repository->getVendorProductsSumQuantity($master_product_id, ['A'], ['A']);

        $this->product_repository->updateProduct($master_product_id, [
            'amount' => $quantity
        ]);
    }

    /**
     * Executes sync data for specific table
     *
     * @param string $table_id                  Table name
     * @param int    $source_product_id         Source product identifier
     * @param array  $destination_product_ids   List of destination product identifiers
     * @param array  $conditions                Primary key conditions
     */
    public function syncData($table_id, $source_product_id, array $destination_product_ids, array $conditions = [])
    {
        $schema = $this->getSyncSchema();

        if (!isset($schema[$table_id])) {
            return;
        }

        $schema[$table_id]->sync($source_product_id, $destination_product_ids, $conditions);
    }

    /**
     *
     * @param string $table_id      Table name
     * @param int    $product_id    Source product identifier
     * @param array  $conditions    Primary key conditions
     */
    public function onTableChanged($table_id, $product_id, array $conditions = [])
    {
        $vendor_product_ids = $this->product_repository->findVendorProductIds($product_id);

        if ($vendor_product_ids) {
            $this->syncData($table_id, $product_id, $vendor_product_ids, $conditions);
        }
    }

    /**
     * Executes sync the product data by all tables, described on sync schema
     *
     * @param int   $source_product_id          Source product identifier
     * @param array $destination_product_ids    List of destination product identifiers
     * @param array $table_ids                  List of table names
     */
    public function syncAllData($source_product_id, array $destination_product_ids, array $table_ids = [])
    {
        $schema = $this->getSyncSchema();

        foreach ($schema as $table_id => $table) {
            if ($table_ids && !in_array($table_id, $table_ids, true)) {
                continue;
            }

            $table->sync($source_product_id, $destination_product_ids);
        }
    }

    /**
     * Executes copy the product data by all tables, described on copy schema
     *
     * @param int   $source_product_id          Source product identifier
     * @param array $destination_product_ids    List of destination product identifiers
     */
    protected function copyAllData($source_product_id, array $destination_product_ids)
    {
        $schema = $this->getCopySchema();

        foreach ($schema as $table_id => $table) {
            $table->sync($source_product_id, $destination_product_ids);
        }
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Sync\ISyncItem[]|null
     */
    protected function getSyncSchema()
    {
        if ($this->sync_schema === null) {
            $this->sync_schema = call_user_func($this->sync_schema_factory);
        }

        return $this->sync_schema;
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Sync\ISyncItem[]|null
     */
    protected function getCopySchema()
    {
        if ($this->copy_schema === null) {
            $this->copy_schema = call_user_func($this->copy_schema_factory);
        }

        return $this->copy_schema;
    }
}