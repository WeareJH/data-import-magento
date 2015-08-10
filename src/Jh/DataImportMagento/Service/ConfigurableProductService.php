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
     * @param string                      $parentSku
     *
     * @throws MagentoSaveException
     */
    public function assignSimpleProductToConfigurable(
        \Mage_Catalog_Model_Product $product,
        $parentSku
    ) {
        $configProduct  = \Mage::getModel('catalog/product')
            ->loadByAttribute('sku', $parentSku);

        if (false === $configProduct) {
            throw new MagentoSaveException('Product does not exist');
        }

        $configType                = $configProduct->getTypeInstance(true);
        $attributes                = $configType->getConfigurableAttributesAsArray($configProduct);

        $configData = [];
        foreach ($attributes as $attribute) {
            $attributeCode    = $attribute['attribute_code'];
            $configData[]     = [
                'attribute_id'  => $this->eavAttrModel->getIdByCode('catalog_product', $attributeCode),
                'label'         => $product->getAttributeText($attributeCode),
                'value_index'   => $product->getData($attributeCode),
                'pricing_value' => $product->getPrice(),
            ];
        }
        $newProductsRelations = [$product->getId() => $configData];


        //We wanna keep the old used products as well so we add them to the config too. Their ids are enough.
        $oldProductsRelations      = [];
        $existingUsedProductsId    = $configProduct->getTypeInstance()->getUsedProductIds();
        foreach ($existingUsedProductsId as $existingUsedProductId) {
            $oldProductsRelations[$existingUsedProductId] = array();
        }

        $productRelations = $oldProductsRelations + $newProductsRelations;

        /** @see \Mage_Catalog_Model_Product_Type_Configurable::save */
        $configProduct->setConfigurableProductsData($productRelations);
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
            $attributeCodes[] = $this->eavAttrModel->getIdByCode('catalog_product', $attribute);
        }

        //set the attributes that should be configurable for this product
        $product->getTypeInstance()->setUsedProductAttributeIds($attributeCodes);
        $configurableAttributesData = $product->getTypeInstance()->getConfigurableAttributesAsArray();

        $product->setCanSaveConfigurableAttributes(true);
        $product->setConfigurableAttributesData($configurableAttributesData);
    }
}