<?php
namespace Jh\DataImportMagento\Writer;

use Ddeboer\DataImport\Writer\AbstractWriter;
use Jh\DataImportMagento\Exception\MagentoSaveException;

/**
 * Class ProductWriter
 * @author Adam Paterson <adam@wearejh.com>
 * @package Jh\DataImportMagento\Writer
 */
class ProductWriter extends AbstractWriter
{
    /**
     * @var \Mage_Catalog_Model_Product
     */
    protected $productModel;
    protected $attributeModel;
    protected $defaultAttributeSetId;
    protected $defaultProductData;
    protected $defaultStockData;
    protected $productAttributes;

    /**
     * @param \Mage_Catalog_Model_Product $productModel
     */
    public function __construct(
        \Mage_Catalog_Model_Product $productModel,
        \Mage_Eav_Model_Config $eavModel
    ) {
        $this->productModel = $productModel;
        $this->attributeModel = $eavModel;
    }

    public function getAttributeCollection()
    {
        $this->productAttributes = $this->attributeModel->loadByCode($this->getEntityTypeCode())
            ->getAttributeCollection()
            ->addFieldToFilter('frontend_input', array('select', 'multiselect'))
            ->addFieldToFilter('is_user_defined', true);
    }

    protected function getEntityTypeCode()
    {
        return $this->productModel->getResource()->getEntityType()->getEntityTypeCode();
    }

    /**
     * TODO: More performant way to keep tracking of already present/newly added attribute options
     * EG. We could load the existing options for each attribute in the @see ProductWriter::prepare() method
     * When we search and create attribute options in @see ProductWriter::getAttrCodeCreateIfNotExist add them to a class variable
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
            'weight'    => '0',
            'status'    => '1',
            'tax_class_id'  => 1,
            'website_ids'   => '1',
            'type'  => 'simple'
        ];
        $this->getAttributeCollection();
    }


    /**
     * @param string $attrCode
     * @param string $attrValue
     * @return string
     */
    public function getAttrCodeCreateIfNotExist($attrCode, $attrValue)
    {
        $attrModel              = \Mage::getModel('eav/entity_attribute');
        $attributeOptionsModel  = \Mage::getModel('eav/entity_attribute_source_table') ;

        $attributeId            = $attrModel->getIdByCode('catalog_product', $attrCode);
        $attribute              = $attrModel->load($attributeId);

        $options                = $attributeOptionsModel->getAllOptions(false);

        foreach($options as $option) {
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

        //return the key of the attribute option
        //equivalent to $option['value'] <- stupidly named by Magento
        return strtolower($attrValue);
    }

    /**
     * TODO: Put all attributes in an array of attributes. Eg nested 'attributes' => ['color' => 'Blue', 'size' => 'Fat']
     * TODO: Then we can loop them and call @see ProductWriter::getAttrCodeCreateIfNotExist
     *
     * @param array $item
     * @throws \Jh\DataImportMagento\Exception\MagentoSaveException
     */
    public function writeItem(array $item)
    {
        $product = clone $this->productModel;
        $attribute = clone $this->attributeModel;

        if (!isset($item['attribute_set_id'])) {
            $item['attribute_set_id'] = $this->defaultAttributeSetId;
        }

        if(!isset($item['stock_data'])) {
            $item['stock_data'] = $this->defaultStockData;
        }

        if(!isset($item['weight'])) {
            $item['weight'] = '0';
        }

        $item = array_merge($this->defaultProductData, $item);

        $product->setData($item);

        if(isset($product['attributes'])) {
            $this->processAttributes($item['attributes'], $product);
        }

        try {
            $product->getResource()->save($product);
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
        foreach($attributes as $attributeCode => $attributeValue) {

            if (!$attributeValue) {
                continue;
            }

            $attrId = $this->getAttrCodeCreateIfNotExist($attributeCode, $attributeValue);
            $product->setData($attributeCode, $attrId);
        }
    }
}