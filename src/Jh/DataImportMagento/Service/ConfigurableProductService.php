<?php

namespace Jh\DataImportMagento\Service;

use Jh\DataImportMagento\Exception\MagentoSaveException;

/**
 * Class ConfigurableProductService
 * @package Jh\DataImportMagento\Service
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ConfigurableProductService
{

    /**
     * @var \Mage_Eav_Model_Entity_Attribute
     */
    protected $eavAttrModel;

    /**
     * @param \Mage_Eav_Model_Entity_Attribute $eavAttrModel
     */
    public function __construct(\Mage_Eav_Model_Entity_Attribute $eavAttrModel)
    {
        $this->eavAttrModel = $eavAttrModel;
    }

    /**
     * @param \Mage_Catalog_Model_Product $product
     * @param array                       $attributes
     * @param string                      $parentSku
     *
     * @throws MagentoSaveException
     */
    public function assignSimpleProductToConfigurable(
        \Mage_Catalog_Model_Product $product,
        array $attributes,
        $parentSku
    ) {
        $configProduct  = \Mage::getModel('catalog/product')
            ->loadByAttribute('sku', $parentSku);

        if (false === $configProduct) {
            throw new MagentoSaveException('Product does not exist');
        }

        $configData = [];
        foreach ($attributes as $attribute) {
            $configData[$attribute] = [
                'attribute_id'  => $this->eavAttrModel->getIdByCode('catalog_product', $attribute),
                'label'         => $product->getAttributeText($attribute),
                'value_index'   => $product->getData($attribute),
                'pricing_value' => $product->getPrice(),
            ];
        }

        /** @see \Mage_Catalog_Model_Product_Type_Configurable::save */
        $configProduct->setConfigurableProductsData([$product->getId() => null]);

        $configurableAttributesData = $configProduct
            ->getTypeInstance()
            ->getConfigurableAttributesAsArray();

        foreach ($configurableAttributesData as $key => $configAttributeData) {
            if (isset($configData[$configAttributeData['attribute_code']])) {
                //i'm sorry this is gross
                //TODO: rewrite
                $configurableAttributesData[$key]['values'][] = $configData[$configAttributeData['attribute_code']];
            }
        }

        //TODO: This is not working yet. Debug??!!!!
        $configProduct->setConfigurableAttributesData($configurableAttributesData);
        $configProduct->setCanSaveConfigurableAttributes(true);
        $configProduct->save();
    }

    /**
     * @param \Mage_Catalog_Model_Product $product
     * @param array                       $configurableAttributes
     */
    public function setupConfigurableProduct(\Mage_Catalog_Model_Product $product, array $configurableAttributes)
    {
        $attributeCodes = [];

        //get attribute ID's
        foreach ($configurableAttributes as $attribute) {
            $attributeCodes[] = $this->eavAttrModel->getIdByCode('catalog/product', $attribute);
        }

        //set the attributes that should be configurable for this product
        $product->getTypeInstance()->setUsedProductAttributeIds($attributeCodes);

        $product->setCanSaveConfigurableAttributes(true);
    }
}