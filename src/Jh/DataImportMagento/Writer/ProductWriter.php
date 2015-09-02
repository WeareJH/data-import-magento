<?php

namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Exception\WriterException;
use Ddeboer\DataImport\Writer\AbstractWriter;
use Jh\DataImportMagento\Exception\MagentoSaveException;
use Jh\DataImportMagento\Service\AttributeService;
use Jh\DataImportMagento\Service\ConfigurableProductService;
use Jh\DataImportMagento\Service\RemoteImageImporter;
use Jh\DataImportMagento\Factory\ConfigurableProductServiceFactory;
use Psr\Log\LoggerInterface;

/**
 * Class ProductWriter
 * @author Adam Paterson <adam@wearejh.com>
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 * @package Jh\DataImportMagento\Writer
 */
class ProductWriter extends AbstractWriter
{

    /**
     * @var \Mage_Catalog_Model_Product
     */
    protected $productModel;

    /**
     * @var RemoteImageImporter
     */
    protected $remoteImageImporter;

    /**
     * @var ConfigurableProductService
     */
    protected $configurableProductService;

    /**
     * @var AttributeService
     */
    protected $attributeService;

    /**
     * @var null|string
     */
    protected $defaultAttributeSetId = null;

    /**
     * @var array
     */
    protected $defaultProductData = array();

    /**
     * @var array
     */
    protected $defaultStockData = array();

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Mage_Catalog_Model_Product $productModel
     * @param RemoteImageImporter         $remoteImageImporter
     * @param AttributeService            $attributeService
     * @param ConfigurableProductService  $configurableProductService
     * @param LoggerInterface             $logger
     */
    public function __construct(
        \Mage_Catalog_Model_Product $productModel,
        RemoteImageImporter $remoteImageImporter,
        AttributeService $attributeService,
        ConfigurableProductService $configurableProductService,
        LoggerInterface $logger
    ) {
        $this->productModel                 = $productModel;
        $this->remoteImageImporter          = $remoteImageImporter;
        $this->configurableProductService   = $configurableProductService;
        $this->attributeService             = $attributeService;
        $this->logger                       = $logger;
    }

    /**
     * @return \Ddeboer\DataImport\Writer\WriterInterface|void
     */
    public function prepare()
    {
        $this->defaultAttributeSetId = $this->productModel->getDefaultAttributeSetId();
        $this->defaultStockData = [
            'manage_stock'                  => 1,
            'use_config_manage_stock'       => 1,
            'qty'                           => 0,
            'min_qty'                       => 0,
            'use_config_min_qty'            => 1,
            'min_sale_qty'                  => 1,
            'use_config_min_sale_qty'       => 1,
            'max_sale_qty'                  => 10000,
            'use_config_max_sale_qty'       => 1,
            'is_qty_decimal'                => 0,
            'backorders'                    => 0,
            'use_config_backorders'         => 1,
            'notify_stock_qty'              => 1,
            'use_config_notify_stock_qty'   => 1,
            'enable_qty_increments'         => 0,
            'use_config_enable_qty_inc'     => 1,
            'qty_increments'                => 0,
            'use_config_qty_increments'     => 1,
            'is_in_stock'                   => 0,
            'low_stock_date'                => null,
            'stock_status_changed_auto'     => 0
        ];
        $this->defaultProductData = [
            'weight'        => '0',
            'status'        => '1',
            'tax_class_id'  => 2,   //Taxable Goods Tax Class
            'website_ids'   => [1],
            'type_id'       => 'simple',
            'url_key'       => null
        ];
    }

    /**
     *
     * @param array $item
     * @return \Ddeboer\DataImport\Writer\WriterInterface|void
     * @throws \Jh\DataImportMagento\Exception\MagentoSaveException
     */
    public function writeItem(array $item)
    {
        $product = clone $this->productModel;

        if (!isset($item['attribute_set_id'])) {
            $item['attribute_set_id'] = $this->defaultAttributeSetId;
        }

        if (!isset($item['stock_data'])) {
            $item['stock_data'] = $this->defaultStockData;
        }

        if (!isset($item['weight'])) {
            $item['weight'] = '0';
        }

        $item = array_merge($this->defaultProductData, $item);

        if (isset($item['attributes'])) {
            $this->processAttributes($item['attributes'], $product);
            unset($item['attributes']);
        }

        $product->addData($item);
        if ($this->isConfigurable($item)) {
            $this->processConfigurableProduct($item, $product);
        }

        try {
            $product->save();
        } catch (\Exception $e) {
            throw new MagentoSaveException($e);
        }

        if (isset($item['type_id']) &&
            $item['type_id'] === 'simple' &&
            isset($item['parent_sku'])
        ) {
            try {
                $this->configurableProductService
                    ->assignSimpleProductToConfigurable(
                        $product,
                        $item['parent_sku']
                    );
            } catch (MagentoSaveException $e) {
                //TODO: Collect these errors and throw an exception
                //should we continue saving the product or bail?
            }
        }

        if (isset($item['images']) && is_array($item['images'])) {
            $product->setData('url_key', false);
            foreach ($item['images'] as $image) {
                try {
                    $this->remoteImageImporter->importImage($product, $image);
                } catch (\RuntimeException $e) {
                    $this->logger->error(
                        sprintf(
                            'Error importing image for product with SKU: "%s". Error: "%s"',
                            $item['sku'],
                            $e->getMessage()
                        )
                    );
                    continue;
                }
            }
        }
    }

    /**
     * @param array $attributes
     * @param \Mage_Catalog_Model_Product $product
     */
    private function processAttributes(array $attributes, \Mage_Catalog_Model_Product $product)
    {
        foreach ($attributes as $attributeCode => $attributeValue) {
            if (!$attributeValue) {
                continue;
            }

            $attrId = $this->attributeService
                ->getAttrCodeCreateIfNotExist('catalog_product', $attributeCode, $attributeValue);

            $product->setData($attributeCode, $attrId);
        }
    }

    /**
     * @param array $item
     * @return bool
     */
    private function isConfigurable(array $item)
    {
        return isset($item['type_id']) && $item['type_id'] === 'configurable';
    }

    /**
     * @param array                       $item
     * @param \Mage_Catalog_Model_Product $product
     *
     * @throws MagentoSaveException
     */
    private function processConfigurableProduct(array $item, \Mage_Catalog_Model_Product $product)
    {
        $attributes = [];
        if (isset($item['configurable_attributes']) && is_array($item['configurable_attributes'])) {
            $attributes = $item['configurable_attributes'];
        }

        if (count($attributes) === 0) {
            throw new MagentoSaveException(
                sprintf(
                    'Configurable product with SKU: "%s" must have at least one "configurable_attribute" defined',
                    $item['sku']
                )
            );
        }

        $this->configurableProductService
            ->setupConfigurableProduct(
                $product,
                $attributes
            );
    }
}
