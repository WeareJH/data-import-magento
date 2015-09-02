<?php

namespace Jh\DataImportMagento\Service;

use Exception;
use Jh\DataImportMagento\Exception\MagentoSaveException;
use Mage_Catalog_Model_Product;
use Mage_Eav_Model_Entity_Attribute;

/**
 * Class ConfigurableProductService
 * @package Jh\DataImportMagento\Service
 * @author  Aydin Hassan <aydin@hotmail.co.uk>
 */
class ConfigurableProductService
{

    /**
     * @var Mage_Eav_Model_Entity_Attribute
     */
    protected $eavAttrModel;

    /**
     * @var Mage_Catalog_Model_Product
     */
    protected $productModel;

    /**
     * @param Mage_Eav_Model_Entity_Attribute $eavAttrModel
     * @param Mage_Catalog_Model_Product       $product
     */
    public function __construct(Mage_Eav_Model_Entity_Attribute $eavAttrModel, Mage_Catalog_Model_Product $product)
    {
        $this->eavAttrModel = $eavAttrModel;
        $this->productModel = $product;
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
        $configProduct  = $this->productModel
            ->loadByAttribute('sku', $parentSku);

        if (false === $configProduct) {
            throw new MagentoSaveException(sprintf('Parent product with SKU: "%s" does not exist', $parentSku));
        }

        if ($configProduct->getData('type_id') !== 'configurable') {
            throw new MagentoSaveException(sprintf('Parent product with SKU: "%s" is not configurable', $parentSku));
        }

        $configType = $configProduct->getTypeInstance();
        $attributes = $configType->getConfigurableAttributesAsArray($configProduct);

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

        //We wanna keep the old used products as well so we add them to the config too. Their ids are enough.
        $newProductsRelations = [];
        foreach ($configType->getUsedProductIds() as $existingUsedProductId) {
            $newProductsRelations[$existingUsedProductId] = [];
        }

        $newProductsRelations[$product->getId()] = $configData;
        $configProduct->setData('configurable_products_data', $newProductsRelations);

        try {
            $configProduct->save();
        } catch (Exception $e) {
            throw new MagentoSaveException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param \Mage_Catalog_Model_Product $product
     * @param array                       $configurableAttributes
     *
     * @throws MagentoSaveException
     */
    public function setupConfigurableProduct(\Mage_Catalog_Model_Product $product, array $configurableAttributes)
    {
        $attributeIds = [];

        //get attribute ID's
        foreach ($configurableAttributes as $attribute) {
            $attributeCode = $this->eavAttrModel->getIdByCode('catalog_product', $attribute);
            if (false === $attributeCode) {
                throw new MagentoSaveException(
                    sprintf(
                        'Cannot create configurable product with SKU: "%s". Attribute: "%s" does not exist',
                        $product->getData('sku'),
                        $attribute
                    )
                );
            }

            $attributeIds[] = $attributeCode;
        }

        //set the attributes that should be configurable for this product
        $productTypeInstance = $product->getTypeInstance();
        $productTypeInstance->setUsedProductAttributeIds($attributeIds);
        $configurableAttributesData = $productTypeInstance->getConfigurableAttributesAsArray();

        $product->addData([
            'can_save_configurable_attributes' => true,
            'configurable_attributes_data'     => $configurableAttributesData,
        ]);
    }
}
