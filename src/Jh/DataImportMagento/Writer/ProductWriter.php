<?php

namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Writer\AbstractWriter;
use Jh\DataImportMagento\Exception\AttributeNotExistException;
use Jh\DataImportMagento\Exception\MagentoSaveException;
use Jh\DataImportMagento\Service\ConfigurableProductService;
use Jh\DataImportMagento\Service\RemoteImageImporter;

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
     * @var \Mage_Eav_Model_Entity_Attribute
     */
    protected $eavAttrModel;

    /**
     * @var \Mage_Eav_Model_Entity_Attribute_Source_Table
     */
    protected $eavAttrSrcModel;

    /**
     * @var null
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
     * @var RemoteImageImporter
     */
    protected $remoteImageImporter;

    /**
     * @var ConfigurableProductService
     */
    protected $configurableProductService;

    /**
     * @param \Mage_Catalog_Model_Product                   $productModel
     * @param \Mage_Eav_Model_Entity_Attribute              $eavAttrModel
     * @param \Mage_Eav_Model_Entity_Attribute_Source_Table $eavAttrSrcModel
     * @param RemoteImageImporter                           $remoteImageImporter
     */
    public function __construct(
        \Mage_Catalog_Model_Product $productModel,
        \Mage_Eav_Model_Entity_Attribute $eavAttrModel,
        \Mage_Eav_Model_Entity_Attribute_Source_Table $eavAttrSrcModel,
        RemoteImageImporter $remoteImageImporter
    ) {
        $this->productModel                 = $productModel;
        $this->eavAttrModel                 = $eavAttrModel;
        $this->eavAttrSrcModel              = $eavAttrSrcModel;
        $this->remoteImageImporter          = $remoteImageImporter;
        //TODO: Move this outside, create a factory for this class
        $this->configurableProductService   = new ConfigurableProductService;
    }

    /**
     * TODO: More performant way to keep tracking of already present/newly added attribute options
     * EG. We could load the existing options for each attribute in the @see ProductWriter::prepare() method
     * When we search and create attribute options in @see ProductWriter::getAttrCodeCreateIfNotExist add them
     * to a class variable
     * holding all attribute options so we don't have to query DB again???? #winning
     *
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
            'type_id'       => 'simple'
        ];
    }

    /**
     * TODO: Move to AttributeService
     *
     * @param string $attrCode
     * @param string $attrValue
     *
     * @return string
     * @throws AttributeNotExistException
     */
    public function getAttrCodeCreateIfNotExist($attrCode, $attrValue)
    {
        $attrModel              = clone $this->eavAttrModel;
        $attributeOptionsModel  = clone $this->eavAttrSrcModel;

        $attributeId            = $attrModel->getIdByCode('catalog_product', $attrCode);

        if (false === $attributeId) {
            throw new AttributeNotExistException($attrCode);
        }

        $attribute = $attrModel->load($attributeId);

        if (!$attribute->usesSource()) {
            return $attrValue;
        }

        $attributeOptionsModel->setAttribute($attribute);
        $options = $attributeOptionsModel->getAllOptions(false);

        foreach ($options as $option) {
            if (strtolower($option['label']) == strtolower($attrValue)) {
                return $option['value'];
            }
        }

        //not found - create it
        $attribute->setData('option', array(
            'value' => array(
                'option' => array($attrValue, $attrValue)
            )
        ));
        $attribute->save();

        $attributeOptionsModel  = clone $this->eavAttrSrcModel;
        $attributeOptionsModel->setAttribute($attribute);
        $id = $attributeOptionsModel->getOptionId(strtolower($attrValue));

        return $id;
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

        $product->setData($item);

        if (isset($item['attributes'])) {
            $this->processAttributes($item['attributes'], $product);
        }

        if (isset($item['type_id']) && $item['type_id'] === 'configurable') {
            $this->setupConfigurableProduct($product, $item['configurableAttributes']);
        }

        try {
            $product->save();
        } catch (\Exception $e) {
            throw new MagentoSaveException($e);
        }

        if (
            isset($item['type_id']) &&
            $item['type_id'] === 'simple' &&
            isset($item['configurableAttributes']) &&
            isset($item['parent_sku'])
        ) {
            try {
                $this->configurableProductService
                    ->assignSimpleProductToConfigurable(
                        $product,
                        $item['configurableAttributes'],
                        $item['parent_sku']
                    );
            } catch (MagentoSaveException $e) {
                //TODO: Collect these errors and throw an exception
                //should we continue saving the product or bail?
            }
        }

        if (isset($item['images']) && is_array($item['images'])) {
            foreach ($item['images'] as $image) {
                $this->remoteImageImporter->importImage($product, $image);
            }
        }
    }

    /**
     * @param array $attributes
     * @param \Mage_Catalog_Model_Product $product
     */
    public function processAttributes(array $attributes, \Mage_Catalog_Model_Product $product)
    {
        foreach ($attributes as $attributeCode => $attributeValue) {

            if (!$attributeValue) {
                continue;
            }

            $attrId = $this->getAttrCodeCreateIfNotExist($attributeCode, $attributeValue);
            $product->setData($attributeCode, $attrId);
        }
    }
}
