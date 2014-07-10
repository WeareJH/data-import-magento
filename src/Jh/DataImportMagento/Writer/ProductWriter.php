<?php

namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Writer\AbstractWriter;
use Jh\DataImportMagento\Exception\MagentoSaveException;

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
     * @param \Mage_Catalog_Model_Product $productModel
     * @param \Mage_Eav_Model_Entity_Attribute $eavAttrModel
     * @param \Mage_Eav_Model_Entity_Attribute_Source_Table $eavAttrSrcModel
     */
    public function __construct(
        \Mage_Catalog_Model_Product $productModel,
        \Mage_Eav_Model_Entity_Attribute $eavAttrModel,
        \Mage_Eav_Model_Entity_Attribute_Source_Table $eavAttrSrcModel
    ) {
        $this->productModel     = $productModel;
        $this->eavAttrModel     = $eavAttrModel;
        $this->eavAttrSrcModel  = $eavAttrSrcModel;
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
     * @param string $attrCode
     * @param string $attrValue
     * @return string
     */
    public function getAttrCodeCreateIfNotExist($attrCode, $attrValue)
    {
        $attrModel              = clone $this->eavAttrModel;
        $attributeOptionsModel  = clone $this->eavAttrSrcModel;

        $attributeId            = $attrModel->getIdByCode('catalog_product', $attrCode);
        $attribute              = $attrModel->load($attributeId);
        $attributeOptionsModel->setAttribute($attribute);
        $options                = $attributeOptionsModel->getAllOptions(false);

        foreach ($options as $option) {
            if (strtolower($option['label']) == strtolower($attrValue)) {
                return $option['value'];
            }
        }

        //not found - create it
        $attribute->setData('option', array(
            'value' => array(
                'option' => array(strtolower($attrValue), $attrValue)
            )
        ));
        $attribute->save();

        //return the key of the attribute option
        //equivalent to $option['value'] <- stupidly named by Magento
        return strtolower($attrValue);
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

        try {
            $product->save($product);
        } catch (\Mage_Core_Exception $e) {
            throw new MagentoSaveException($e);
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
